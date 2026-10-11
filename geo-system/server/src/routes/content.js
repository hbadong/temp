const express = require('express');
const path = require('path');
const fs = require('fs');
const { db, DATA_DIR } = require('../db');
const { ok, fail, now, paginate } = require('../lib');
const { requireUser, requireKyc } = require('../auth');
const { prices, chargeMoney, checkWriteQuota } = require('../billing');
const { enqueue } = require('../tasks');
const llm = require('../providers/llm');

const router = express.Router();
router.use(requireUser);

function crud(base, table, kycWrite) {
  router.get(base, (req, res) => {
    const { page, pageSize, offset } = paginate(req.query);
    const q = String(req.query.q || '').trim();
    let where = 'user_id=?';
    const args = [req.user.id];
    if (q) {
      where += ` AND (CAST(id AS TEXT) LIKE ? OR ${table === 'articles' ? 'title' : table === 'keywords' ? 'word' : 'name'} LIKE ?)`;
      if (table === 'articles') {
        where = 'user_id=? AND title LIKE ?';
        args.length = 1;
        args.push('%' + q + '%');
      } else if (table === 'keywords') {
        where = 'user_id=? AND word LIKE ?';
        args.length = 1;
        args.push('%' + q + '%');
      } else if (table === 'writing_titles') {
        where = 'user_id=? AND title LIKE ?';
        args.length = 1;
        args.push('%' + q + '%');
      } else if (table === 'knowledge') {
        where = 'user_id=? AND title LIKE ?';
        args.length = 1;
        args.push('%' + q + '%');
      } else {
        where = 'user_id=? AND name LIKE ?';
        args.length = 1;
        args.push('%' + q + '%');
      }
    }
    if (table === 'articles' && req.query.type_id) {
      where += ' AND type_id=?';
      args.push(Number(req.query.type_id));
    }
    if (table === 'articles' && req.query.keyword) {
      where += ' AND keyword LIKE ?';
      args.push('%' + req.query.keyword + '%');
    }
    const total = db.prepare(`SELECT COUNT(*) AS c FROM ${table} WHERE ${where}`).get(...args).c;
    const list = db.prepare(`SELECT * FROM ${table} WHERE ${where} ORDER BY id DESC LIMIT ? OFFSET ?`).all(...args, pageSize, offset);
    ok(res, { list, total, page, page_size: pageSize });
  });
  const writeMw = kycWrite ? [requireKyc] : [];
  router.post(base, ...writeMw, (req, res) => {
    const b = req.body || {};
    let fields;
    if (table === 'keywords') fields = { user_id: req.user.id, word: String(b.word || '').trim(), created_at: now() };
    else if (table === 'writing_titles') fields = { user_id: req.user.id, keyword_id: b.keyword_id || 0, title: String(b.title || '').trim(), created_at: now() };
    else if (table === 'instructions') fields = { user_id: req.user.id, name: String(b.name || '').trim(), body: String(b.body || ''), status: b.status || 'on', created_at: now() };
    else if (table === 'article_types') fields = { user_id: req.user.id, name: String(b.name || '').trim(), created_at: now() };
    else if (table === 'knowledge') fields = { user_id: req.user.id, title: String(b.title || '').trim(), body: String(b.body || ''), created_at: now() };
    else return fail(res, '不支持');
    const nameVal = fields.word || fields.title || fields.name;
    if (!nameVal) return fail(res, '请填写必填项');
    const keys = Object.keys(fields);
    const info = db.prepare(`INSERT INTO ${table} (${keys.join(',')}) VALUES (${keys.map(() => '?').join(',')})`).run(...keys.map((k) => fields[k]));
    ok(res, db.prepare(`SELECT * FROM ${table} WHERE id=?`).get(info.lastInsertRowid));
  });
  router.put(`${base}/:id`, ...writeMw, (req, res) => {
    const row = db.prepare(`SELECT * FROM ${table} WHERE id=? AND user_id=?`).get(req.params.id, req.user.id);
    if (!row) return fail(res, '不存在');
    const b = req.body || {};
    const patch = {};
    ['word', 'title', 'body', 'name', 'status', 'keyword_id', 'type_id', 'content', 'keyword'].forEach((k) => {
      if (b[k] !== undefined) patch[k] = b[k];
    });
    const keys = Object.keys(patch);
    if (keys.length) {
      db.prepare(`UPDATE ${table} SET ${keys.map((k) => `${k}=?`).join(',')} WHERE id=? AND user_id=?`)
        .run(...keys.map((k) => patch[k]), req.params.id, req.user.id);
    }
    ok(res, db.prepare(`SELECT * FROM ${table} WHERE id=?`).get(req.params.id));
  });
  router.delete(`${base}/:id`, ...writeMw, (req, res) => {
    const info = db.prepare(`DELETE FROM ${table} WHERE id=? AND user_id=?`).run(req.params.id, req.user.id);
    if (!info.changes) return fail(res, '不存在');
    ok(res, {});
  });
}

crud('/keywords', 'keywords', true);
crud('/titles', 'writing_titles', true);
crud('/instructions', 'instructions', true);
crud('/article-types', 'article_types', true);
crud('/knowledge', 'knowledge', true);
crud('/articles', 'articles', true);

router.post('/titles/distill', requireKyc, (req, res) => {
  const ids = (req.body || {}).keyword_ids || [];
  const words = ids.length
    ? db.prepare(`SELECT * FROM keywords WHERE user_id=? AND id IN (${ids.map(() => '?').join(',')})`).all(req.user.id, ...ids)
    : [];
  if (!words.length) return fail(res, '请选择关键词');
  const created = [];
  const ins = db.prepare('INSERT INTO writing_titles (user_id, keyword_id, title, created_at) VALUES (?,?,?,?)');
  words.forEach((w) => {
    llm.mockDistill(w.word).slice(0, 5).forEach((title) => {
      const id = ins.run(req.user.id, w.id, title, now()).lastInsertRowid;
      created.push(id);
    });
  });
  ok(res, { count: created.length });
});

router.post('/keywords/import', requireKyc, (req, res) => {
  const text = String((req.body || {}).text || '');
  const lines = text.split(/\r?\n/).map((s) => s.trim()).filter(Boolean);
  const ins = db.prepare('INSERT INTO keywords (user_id, word, created_at) VALUES (?,?,?)');
  lines.forEach((w) => ins.run(req.user.id, w, now()));
  ok(res, { count: lines.length });
});

router.post('/write-tasks', requireKyc, (req, res) => {
  const b = req.body || {};
  const count = Math.max(1, Math.min(20, parseInt(b.count, 10) || 1));
  const keyword = String(b.keyword || b.title || '').trim();
  if (!keyword) return fail(res, '请选择关键词或写作标题');
  const q = checkWriteQuota(req.user.id);
  if (!q.ok) return fail(res, q.msg);
  const unit = prices().write;
  const amount = unit * count;
  let taskId;
  try {
    db.transaction(() => {
      const billed = chargeMoney(req.user.id, amount, { type: 'write', remark: `AI写作x${count}`, refType: 'write' });
      if (!billed.ok) throw Object.assign(new Error(billed.msg), { biz: true });
      taskId = enqueue(req.user.id, 'write', {
        instruction_id: b.instruction_id || 0,
        type_id: b.type_id || 0,
        count,
        keyword,
      }, 1);
    })();
  } catch (e) {
    return fail(res, e.biz ? e.message : '创建失败');
  }
  ok(res, { task_id: taskId });
});

router.get('/write-tasks', requireUser, (req, res) => {
  const { page, pageSize, offset } = paginate(req.query);
  const total = db.prepare("SELECT COUNT(*) AS c FROM tasks WHERE user_id=? AND type IN ('write','reprint')").get(req.user.id).c;
  const list = db.prepare("SELECT * FROM tasks WHERE user_id=? AND type IN ('write','reprint') ORDER BY id DESC LIMIT ? OFFSET ?")
    .all(req.user.id, pageSize, offset);
  ok(res, { list, total, page, page_size: pageSize });
});

router.post('/reprint', requireKyc, (req, res) => {
  const source = String((req.body || {}).source || '').trim();
  if (!source) return fail(res, '请填写源 URL 或标题');
  const taskId = enqueue(req.user.id, 'reprint', { sources: [source] }, 0);
  ok(res, { task_id: taskId });
});

router.post('/reprint/batch', requireKyc, (req, res) => {
  const text = String((req.body || {}).text || '');
  const sources = text.split(/\r?\n/).map((s) => s.trim()).filter(Boolean);
  if (!sources.length) return fail(res, '请填写源内容');
  const taskIds = sources.map((s) => enqueue(req.user.id, 'reprint', { sources: [s] }, 0));
  ok(res, { task_ids: taskIds });
});

router.get('/image-groups', (req, res) => {
  const kind = parseInt(req.query.kind || req.query.type || '1', 10);
  const list = db.prepare('SELECT * FROM image_groups WHERE user_id=? AND kind=? ORDER BY id DESC').all(req.user.id, kind);
  ok(res, { list });
});

router.post('/image-groups', requireKyc, (req, res) => {
  const name = String((req.body || {}).name || '').trim();
  const kind = parseInt((req.body || {}).kind || 1, 10);
  if (!name) return fail(res, '请填写分组名');
  const id = db.prepare('INSERT INTO image_groups (user_id, name, kind, created_at) VALUES (?,?,?,?)')
    .run(req.user.id, name, kind, now()).lastInsertRowid;
  ok(res, db.prepare('SELECT * FROM image_groups WHERE id=?').get(id));
});

router.delete('/image-groups/:id', requireKyc, (req, res) => {
  db.prepare('DELETE FROM images WHERE group_id=? AND user_id=?').run(req.params.id, req.user.id);
  db.prepare('DELETE FROM image_groups WHERE id=? AND user_id=?').run(req.params.id, req.user.id);
  ok(res, {});
});

router.get('/images', (req, res) => {
  const groupId = parseInt(req.query.group_id || '0', 10);
  const list = groupId
    ? db.prepare('SELECT * FROM images WHERE user_id=? AND group_id=? ORDER BY id DESC').all(req.user.id, groupId)
    : db.prepare('SELECT * FROM images WHERE user_id=? ORDER BY id DESC').all(req.user.id);
  ok(res, { list });
});

router.delete('/images/:id', requireKyc, (req, res) => {
  db.prepare('DELETE FROM images WHERE id=? AND user_id=?').run(req.params.id, req.user.id);
  ok(res, {});
});

router.post('/uploads', requireKyc, (req, res) => {
  const chunks = [];
  let received = 0;
  let tooBig = false;
  req.on('data', (c) => {
    received += c.length;
    if (received > 80 * 1024 * 1024) {
      tooBig = true;
      return;
    }
    chunks.push(c);
  });
  req.on('end', () => {
    if (tooBig) return fail(res, '文件过大');
    const buf = Buffer.concat(chunks);
    const filename = decodeURIComponent(req.headers['x-filename'] || `file-${Date.now()}`);
    const ext = path.extname(filename).toLowerCase();
    const allow = ['.jpg', '.jpeg', '.png', '.gif', '.bmp', '.mp4', '.mov', '.webp'];
    if (!allow.includes(ext)) return fail(res, '不支持的文件类型');
    const dir = path.join(DATA_DIR, 'uploads', String(req.user.id));
    fs.mkdirSync(dir, { recursive: true });
    const saved = `${Date.now()}${ext}`;
    const full = path.join(dir, saved);
    fs.writeFileSync(full, buf);
    const rel = `uploads/${req.user.id}/${saved}`;
    const groupId = parseInt(req.headers['x-group-id'] || '0', 10);
    const kind = String(req.headers['x-kind'] || 'image');
    let rec;
    if (kind === 'video') {
      rec = db.prepare('INSERT INTO video_assets (user_id, group_id, filename, path, kind, created_at) VALUES (?,?,?,?,?,?)')
        .run(req.user.id, groupId, filename, rel, ext === '.mp4' || ext === '.mov' ? 'video' : 'cover', now());
      ok(res, { id: rec.lastInsertRowid, path: `/api/files/${rel}`, filename });
    } else {
      rec = db.prepare('INSERT INTO images (user_id, group_id, filename, path, created_at) VALUES (?,?,?,?,?)')
        .run(req.user.id, groupId, filename, rel, now());
      ok(res, { id: rec.lastInsertRowid, path: `/api/files/${rel}`, filename });
    }
  });
});

module.exports = router;
