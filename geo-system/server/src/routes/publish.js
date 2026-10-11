const express = require('express');
const { db } = require('../db');
const { ok, fail, now, paginate, readJson } = require('../lib');
const { requireUser, requireKyc } = require('../auth');
const { prices, chargeMoney, checkPublishQuota } = require('../billing');
const { enqueue } = require('../tasks');

const router = express.Router();
router.use(requireUser);

function simpleCrud(pathName, table, fieldsFn) {
  router.get(pathName, (req, res) => {
    const { page, pageSize, offset } = paginate(req.query);
    const total = db.prepare(`SELECT COUNT(*) AS c FROM ${table} WHERE user_id=?`).get(req.user.id).c;
    const list = db.prepare(`SELECT * FROM ${table} WHERE user_id=? ORDER BY id DESC LIMIT ? OFFSET ?`)
      .all(req.user.id, pageSize, offset);
    ok(res, { list, total, page, page_size: pageSize });
  });
  router.post(pathName, requireKyc, (req, res) => {
    const fields = fieldsFn(req);
    const keys = Object.keys(fields);
    const id = db.prepare(`INSERT INTO ${table} (${keys.join(',')}) VALUES (${keys.map(() => '?').join(',')})`)
      .run(...keys.map((k) => fields[k])).lastInsertRowid;
    ok(res, db.prepare(`SELECT * FROM ${table} WHERE id=?`).get(id));
  });
  router.put(`${pathName}/:id`, requireKyc, (req, res) => {
    const fields = fieldsFn(req);
    const keys = Object.keys(fields).filter((k) => k !== 'user_id' && k !== 'created_at');
    db.prepare(`UPDATE ${table} SET ${keys.map((k) => `${k}=?`).join(',')} WHERE id=? AND user_id=?`)
      .run(...keys.map((k) => fields[k]), req.params.id, req.user.id);
    const row = db.prepare(`SELECT * FROM ${table} WHERE id=? AND user_id=?`).get(req.params.id, req.user.id);
    if (!row) return fail(res, '不存在');
    ok(res, row);
  });
  router.delete(`${pathName}/:id`, requireKyc, (req, res) => {
    db.prepare(`DELETE FROM ${table} WHERE id=? AND user_id=?`).run(req.params.id, req.user.id);
    ok(res, {});
  });
}

simpleCrud('/contacts', 'contacts', (req) => ({
  user_id: req.user.id,
  name: (req.body || {}).name || '',
  phone: (req.body || {}).phone || '',
  email: (req.body || {}).email || '',
  company: (req.body || {}).company || '',
  created_at: now(),
}));

simpleCrud('/sites', 'sites', (req) => ({
  user_id: req.user.id,
  name: (req.body || {}).name || '',
  domain: (req.body || {}).domain || '',
  config_json: JSON.stringify((req.body || {}).config || {}),
  created_at: now(),
}));

simpleCrud('/proxy', 'proxy_ips', (req) => ({
  user_id: req.user.id,
  ip: (req.body || {}).ip || '',
  port: parseInt((req.body || {}).port, 10) || 0,
  protocol: (req.body || {}).protocol || 'http',
  account: (req.body || {}).account || '',
  password: (req.body || {}).password || '',
  status: (req.body || {}).status || 'on',
  created_at: now(),
}));

simpleCrud('/tdk', 'tdk_groups', (req) => ({
  user_id: req.user.id,
  name: (req.body || {}).name || '',
  items_json: JSON.stringify((req.body || {}).items || []),
  created_at: now(),
}));

simpleCrud('/media-favs', 'media_favs', (req) => ({
  user_id: req.user.id,
  kind: parseInt((req.body || {}).kind || 1, 10),
  name: (req.body || {}).name || '',
  channel_ids_json: JSON.stringify((req.body || {}).channel_ids || []),
  created_at: now(),
}));

router.get('/channels/:kind', (req, res) => {
  const kind = req.params.kind;
  const list = db.prepare('SELECT * FROM channel_catalog WHERE kind=? AND status=?').all(kind, 'on');
  ok(res, { list });
});

router.post('/publish/:kind', requireKyc, (req, res) => {
  const kind = req.params.kind;
  const map = { b2b: 'publish_b2b', webmedia: 'publish_webmedia', zimedia: 'publish_zimedia', weixin: 'publish_weixin' };
  const type = map[kind];
  if (!type) return fail(res, '未知渠道');
  const b = req.body || {};
  const articleIds = Array.isArray(b.article_ids) ? b.article_ids.map((x) => parseInt(x, 10)).filter((x) => x > 0) : [];
  const channelIds = Array.isArray(b.channel_ids) ? b.channel_ids.map((x) => parseInt(x, 10)).filter((x) => x > 0) : [];
  if (!articleIds.length || !channelIds.length) return fail(res, '请选择文章和目标渠道');
  const owned = db.prepare(`SELECT id FROM articles WHERE user_id=? AND id IN (${articleIds.map(() => '?').join(',')})`)
    .all(req.user.id, ...articleIds);
  if (owned.length !== articleIds.length) return fail(res, '存在无权操作的文章');
  if (kind === 'weixin') {
    const auths = db.prepare(`SELECT id FROM weixin_auths WHERE user_id=? AND status='valid' AND id IN (${channelIds.map(() => '?').join(',')})`)
      .all(req.user.id, ...channelIds);
    if (auths.length !== channelIds.length) return fail(res, '账号授权已失效，请重新授权');
  } else {
    const chs = db.prepare(`SELECT id FROM channel_catalog WHERE kind=? AND status='on' AND id IN (${channelIds.map(() => '?').join(',')})`)
      .all(kind, ...channelIds);
    if (chs.length !== channelIds.length) return fail(res, '包含无效的目标渠道');
  }
  const unit = prices().publish;
  const amount = unit * articleIds.length * channelIds.length;
  const q = checkPublishQuota(req.user.id);
  if (!q.ok) return fail(res, q.msg);
  let jobId;
  let taskId;
  try {
    db.transaction(() => {
      const billed = chargeMoney(req.user.id, amount, { type: 'publish', remark: `${kind}发布x${articleIds.length * channelIds.length}条`, refType: 'publish' });
      if (!billed.ok) throw Object.assign(new Error(billed.msg), { biz: true });
      taskId = enqueue(req.user.id, type, { kind }, 1);
      jobId = db.prepare(`INSERT INTO publish_jobs (user_id, task_id, kind, article_ids_json, channel_ids_json, contact_id, status, created_at)
        VALUES (?,?,?,?,?,?,?,?)`)
        .run(req.user.id, taskId, kind, JSON.stringify(articleIds), JSON.stringify(channelIds), b.contact_id || 0, 'queued', now()).lastInsertRowid;
    })();
  } catch (e) {
    return fail(res, e.biz ? e.message : '创建失败');
  }
  ok(res, { job_id: jobId, task_id: taskId });
});

router.get('/publish/:kind/records', requireKyc, (req, res) => {
  const { page, pageSize, offset } = paginate(req.query);
  const kind = req.params.kind;
  const jobs = db.prepare('SELECT id FROM publish_jobs WHERE user_id=? AND kind=?').all(req.user.id, kind).map((j) => j.id);
  if (!jobs.length) return ok(res, { list: [], total: 0, page, page_size: pageSize });
  const ph = jobs.map(() => '?').join(',');
  const total = db.prepare(`SELECT COUNT(*) AS c FROM publish_records WHERE user_id=? AND job_id IN (${ph})`).get(req.user.id, ...jobs).c;
  const list = db.prepare(`SELECT r.*, c.name AS channel_name, a.title AS article_title
    FROM publish_records r
    LEFT JOIN channel_catalog c ON c.id=r.channel_id
    LEFT JOIN articles a ON a.id=r.article_id
    WHERE r.user_id=? AND r.job_id IN (${ph})
    ORDER BY r.id DESC LIMIT ? OFFSET ?`).all(req.user.id, ...jobs, pageSize, offset);
  ok(res, { list, total, page, page_size: pageSize });
});

router.get('/publish/:kind/jobs', requireKyc, (req, res) => {
  const list = db.prepare('SELECT * FROM publish_jobs WHERE user_id=? AND kind=? ORDER BY id DESC').all(req.user.id, req.params.kind);
  ok(res, { list });
});

router.post('/weixin/auth', requireKyc, (req, res) => {
  const name = String((req.body || {}).account_name || '个人号').trim();
  const id = db.prepare('INSERT INTO weixin_auths (user_id, account_name, status, created_at) VALUES (?,?,?,?)')
    .run(req.user.id, name, 'valid', now()).lastInsertRowid;
  ok(res, db.prepare('SELECT * FROM weixin_auths WHERE id=?').get(id));
});

router.get('/weixin/auth', (req, res) => {
  ok(res, { list: db.prepare('SELECT * FROM weixin_auths WHERE user_id=?').all(req.user.id) });
});

router.post('/site-publish', requireKyc, (req, res) => {
  const b = req.body || {};
  const articleIds = Array.isArray(b.article_ids) ? b.article_ids.map((x) => parseInt(x, 10)).filter((x) => x > 0) : [];
  const siteIds = Array.isArray(b.site_ids) ? b.site_ids.map((x) => parseInt(x, 10)).filter((x) => x > 0) : [];
  if (!articleIds.length || !siteIds.length) return fail(res, '请选择站点与文章');
  const owned = db.prepare(`SELECT id FROM articles WHERE user_id=? AND id IN (${articleIds.map(() => '?').join(',')})`)
    .all(req.user.id, ...articleIds);
  if (owned.length !== articleIds.length) return fail(res, '存在无权操作的文章');
  const sites = db.prepare(`SELECT id FROM sites WHERE user_id=? AND id IN (${siteIds.map(() => '?').join(',')})`)
    .all(req.user.id, ...siteIds);
  if (sites.length !== siteIds.length) return fail(res, '包含无效的目标站点');
  const q = checkPublishQuota(req.user.id);
  if (!q.ok) return fail(res, q.msg);
  const taskId = enqueue(req.user.id, 'publish_site', { article_ids: articleIds, site_ids: siteIds }, 0);
  ok(res, { task_id: taskId });
});

router.get('/site-publish/records', requireKyc, (req, res) => {
  const list = db.prepare(`SELECT r.*, s.name AS site_name, s.domain, a.title AS article_title
    FROM site_pub_records r
    LEFT JOIN sites s ON s.id=r.site_id
    LEFT JOIN articles a ON a.id=r.article_id
    WHERE r.user_id=? ORDER BY r.id DESC`).all(req.user.id);
  ok(res, { list });
});

router.post('/video-mix', requireKyc, (req, res) => {
  const b = req.body || {};
  const count = Math.max(1, parseInt(b.count, 10) || 1);
  let jobId;
  let taskId;
  db.transaction(() => {
    taskId = enqueue(req.user.id, 'video_mix', b, 0);
    jobId = db.prepare('INSERT INTO video_jobs (user_id, task_id, kind, tdk_id, count, status, created_at) VALUES (?,?,?,?,?,?,?)')
      .run(req.user.id, taskId, 'mix', b.tdk_id || 0, count, 'queued', now()).lastInsertRowid;
  })();
  ok(res, { job_id: jobId, task_id: taskId });
});

router.post('/video-publish', requireKyc, (req, res) => {
  const b = req.body || {};
  const count = Math.max(1, parseInt(b.count, 10) || 1);
  let jobId;
  let taskId;
  db.transaction(() => {
    taskId = enqueue(req.user.id, 'video_publish', b, 0);
    jobId = db.prepare('INSERT INTO video_jobs (user_id, task_id, kind, tdk_id, count, status, created_at) VALUES (?,?,?,?,?,?,?)')
      .run(req.user.id, taskId, 'publish', b.tdk_id || 0, count, 'queued', now()).lastInsertRowid;
  })();
  ok(res, { job_id: jobId, task_id: taskId });
});

router.get('/video-jobs', requireKyc, (req, res) => {
  ok(res, { list: db.prepare('SELECT * FROM video_jobs WHERE user_id=? ORDER BY id DESC').all(req.user.id) });
});

router.get('/video-records', requireKyc, (req, res) => {
  ok(res, { list: db.prepare('SELECT * FROM video_records WHERE user_id=? ORDER BY id DESC').all(req.user.id) });
});

router.get('/video-assets', (req, res) => {
  ok(res, { list: db.prepare('SELECT * FROM video_assets WHERE user_id=? ORDER BY id DESC').all(req.user.id) });
});

router.delete('/video-assets/:id', requireKyc, (req, res) => {
  const info = db.prepare('DELETE FROM video_assets WHERE id=? AND user_id=?').run(req.params.id, req.user.id);
  if (!info.changes) return fail(res, '素材不存在');
  ok(res, {});
});

module.exports = router;
