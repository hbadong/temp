const { db } = require('./db');
const { now, readJson } = require('./lib');
const llm = require('./providers/llm');
const channel = require('./providers/channel');

const MAX_IN_FLIGHT = 2;
let inFlight = 0;
let timer = null;

function enqueue(userId, type, payload, billed) {
  const info = db.prepare(`INSERT INTO tasks (user_id, type, status, payload_json, billed, created_at)
    VALUES (?,?,?,?,?,?)`).run(userId, type, 'queued', JSON.stringify(payload || {}), billed ? 1 : 0, now());
  return info.lastInsertRowid;
}

function retryTask(userId, taskId) {
  const row = db.prepare('SELECT * FROM tasks WHERE id=? AND user_id=?').get(taskId, userId);
  if (!row) return { ok: false, msg: '任务不存在' };
  if (row.status !== 'failed') return { ok: false, msg: '仅失败任务可重试' };
  db.prepare('UPDATE tasks SET status=?, error_message=?, started_at=0, finished_at=0 WHERE id=?')
    .run('queued', '', taskId);
  return { ok: true };
}

function parseCompetitors(str) {
  return String(str || '').split(/[,，;；]/).map((s) => s.trim()).filter(Boolean);
}

function handleDiagnose(task) {
  const p = readJson(task.payload_json, {});
  const job = db.prepare('SELECT * FROM analyze_jobs WHERE task_id=?').get(task.id);
  if (!job) throw new Error('诊断任务不存在');
  const industries = readJson(job.industry_json, []);
  const platforms = readJson(job.platforms_json, []);
  const keyword = industries[0] || job.brand;
  const competitors = job.kind === 'pro' ? parseCompetitors(job.competitors) : [];
  const entities = [job.brand].concat(competitors);
  db.prepare('DELETE FROM analyze_reports WHERE job_id=?').run(job.id);
  const ins = db.prepare(`INSERT INTO analyze_reports (job_id, user_id, entity, platform, mentioned, score, snippet, advice_json, screenshot_path)
    VALUES (?,?,?,?,?,?,?,?,?)`);
  entities.forEach((entity) => {
    platforms.forEach((plat) => {
      const r = llm.mockDiagnose({
        brand: entity,
        keyword,
        platform: plat,
        needAdvice: job.need_advice && entity === job.brand,
      });
      ins.run(job.id, job.user_id, entity, r.platform, r.mentioned, r.score, r.snippet, JSON.stringify(r.advice), '');
    });
  });
  db.prepare('UPDATE analyze_jobs SET status=? WHERE id=?').run('done', job.id);
  return { jobId: job.id, kind: job.kind, entities: entities.length };
}

function handleWrite(task) {
  const p = readJson(task.payload_json, {});
  const inst = p.instruction_id
    ? db.prepare('SELECT * FROM instructions WHERE id=? AND user_id=?').get(p.instruction_id, task.user_id)
    : null;
  const kn = db.prepare('SELECT body FROM knowledge WHERE user_id=? ORDER BY id DESC LIMIT 1').get(task.user_id);
  const count = Math.max(1, Math.min(20, parseInt(p.count, 10) || 1));
  const keyword = p.keyword || (p.title) || 'GEO优化';
  const ins = db.prepare(`INSERT INTO articles (user_id, task_id, type_id, title, content, keyword, source, created_at)
    VALUES (?,?,?,?,?,?,?,?)`);
  const created = [];
  for (let i = 0; i < count; i++) {
    const art = llm.mockWrite({
      instruction: inst ? inst.body : '',
      keyword,
      index: i,
      knowledge: kn ? kn.body : '',
    });
    const id = ins.run(task.user_id, task.id, p.type_id || 0, art.title, art.content, keyword, 'write', now()).lastInsertRowid;
    created.push(id);
  }
  return { articleIds: created };
}

function handleReprint(task) {
  const p = readJson(task.payload_json, {});
  const sources = p.sources || (p.source ? [p.source] : []);
  const ins = db.prepare(`INSERT INTO articles (user_id, task_id, type_id, title, content, keyword, source, created_at)
    VALUES (?,?,?,?,?,?,?,?)`);
  const results = [];
  sources.forEach((src) => {
    const s = String(src).trim();
    if (!s) {
      results.push({ source: src, status: 'failed', error: '源内容无法抓取' });
      return;
    }
    if (/^https?:\/\/fail\.invalid/i.test(s)) {
      results.push({ source: s, status: 'failed', error: '源内容无法抓取' });
      return;
    }
    const titles = llm.mockReprint(s.replace(/^https?:\/\//, ''));
    titles.forEach((title, i) => {
      ins.run(task.user_id, task.id, 0, title, `${title}\n\n根据源内容「${s}」改写的 GEO 文章第 ${i + 1} 篇。`, '', 'reprint', now());
    });
    results.push({ source: s, status: 'done' });
  });
  const failed = results.filter((r) => r.status === 'failed');
  if (failed.length) throw new Error(`存在抓取失败的源：${failed.map((r) => r.source).join(', ')}`);
  return { results };
}

function handlePublish(task) {
  const p = readJson(task.payload_json, {});
  const job = db.prepare('SELECT * FROM publish_jobs WHERE task_id=?').get(task.id);
  if (!job) throw new Error('发布任务不存在');
  const articleIds = readJson(job.article_ids_json, []);
  const channelIds = readJson(job.channel_ids_json, []);
  const ins = db.prepare(`INSERT INTO publish_records (user_id, job_id, channel_id, article_id, url, status, error, published_at)
    VALUES (?,?,?,?,?,?,?,?)`);
  articleIds.forEach((aid) => {
    channelIds.forEach((cid) => {
      const r = channel.publishOne({ channelKind: job.kind, channelId: cid, articleId: aid });
      ins.run(task.user_id, job.id, cid, aid, r.url, r.status, r.error, r.status === 'success' ? now() : 0);
    });
  });
  db.prepare('UPDATE publish_jobs SET status=? WHERE id=?').run('done', job.id);
  return { jobId: job.id };
}

function handleSitePublish(task) {
  const p = readJson(task.payload_json, {});
  const articleIds = p.article_ids || [];
  const siteIds = p.site_ids || [];
  const ins = db.prepare(`INSERT INTO site_pub_records (user_id, site_id, article_id, url, status, created_at)
    VALUES (?,?,?,?,?,?)`);
  siteIds.forEach((sid) => {
    const site = db.prepare('SELECT * FROM sites WHERE id=? AND user_id=?').get(sid, task.user_id);
    articleIds.forEach((aid) => {
      const url = site ? `https://${site.domain}/article/${aid}.html` : '';
      ins.run(task.user_id, sid, aid, url, url ? 'success' : 'failed', now());
    });
  });
  return { ok: true };
}

function handleVideo(task) {
  const p = readJson(task.payload_json, {});
  const job = db.prepare('SELECT * FROM video_jobs WHERE task_id=?').get(task.id);
  if (!job) throw new Error('视频任务不存在');
  const count = Math.max(1, job.count || 1);
  const ins = db.prepare(`INSERT INTO video_records (user_id, job_id, platform, title, status, error, published_at)
    VALUES (?,?,?,?,?,?,?)`);
  if (job.kind === 'mix') {
    for (let i = 0; i < count; i++) {
      ins.run(task.user_id, job.id, 'mix', `混剪成片 ${i + 1}`, 'success', '', now());
    }
  } else {
    for (let i = 0; i < count; i++) {
      const r = channel.publishOne({ channelKind: 'video', channelId: i + 1, articleId: job.id });
      ins.run(task.user_id, job.id, '矩阵', `视频发布 ${i + 1}`, r.status, r.error, r.status === 'success' ? now() : 0);
    }
  }
  db.prepare('UPDATE video_jobs SET status=? WHERE id=?').run('done', job.id);
  return { jobId: job.id };
}

function handleInclusion(task) {
  const p = readJson(task.payload_json, {});
  const r = channel.inclusionResult(p.query || '', p.target || '');
  db.prepare(`INSERT INTO inclusion_queries (user_id, task_id, query, target, included, snippet, screenshot_path, created_at)
    VALUES (?,?,?,?,?,?,?,?)`).run(task.user_id, task.id, p.query || '', p.target || '', r.included, r.snippet, r.screenshot_path, now());
  return r;
}

function handleDistill(task) {
  const p = readJson(task.payload_json, {});
  return { questions: llm.mockDistill(p.keyword || '') };
}

function handleBrandParse(task) {
  const p = readJson(task.payload_json, {});
  return llm.mockBrandParse(p.brand_name);
}

const HANDLERS = {
  diagnose: handleDiagnose,
  diagnose_pro: handleDiagnose,
  brand_parse: handleBrandParse,
  write: handleWrite,
  reprint: handleReprint,
  publish_b2b: handlePublish,
  publish_webmedia: handlePublish,
  publish_zimedia: handlePublish,
  publish_weixin: handlePublish,
  publish_site: handleSitePublish,
  video_mix: handleVideo,
  video_publish: handleVideo,
  inclusion: handleInclusion,
  distill: handleDistill,
};

function runOne(row) {
  inFlight += 1;
  const t = now();
  db.prepare('UPDATE tasks SET status=?, started_at=? WHERE id=?').run('running', t, row.id);
  try {
    const handler = HANDLERS[row.type];
    if (!handler) throw new Error('未知任务类型');
    const result = handler(row);
    db.prepare('UPDATE tasks SET status=?, result_json=?, finished_at=?, error_message=? WHERE id=?')
      .run('done', JSON.stringify(result || {}), now(), '', row.id);
  } catch (e) {
    db.prepare('UPDATE tasks SET status=?, error_message=?, finished_at=? WHERE id=?')
      .run('failed', String(e.message || e), now(), row.id);
  } finally {
    inFlight -= 1;
  }
}

function tick() {
  if (inFlight >= MAX_IN_FLIGHT) return;
  const slots = MAX_IN_FLIGHT - inFlight;
  const rows = db.prepare('SELECT * FROM tasks WHERE status=? ORDER BY id ASC LIMIT ?').all('queued', slots);
  rows.forEach((row) => runOne(row));
}

function start() {
  if (timer) return;
  timer = setInterval(tick, 200);
  if (timer.unref) timer.unref();
}

function stop() {
  if (timer) clearInterval(timer);
  timer = null;
}

function waitAll(timeoutMs) {
  const startAt = Date.now();
  return new Promise((resolve, reject) => {
    const iv = setInterval(() => {
      const left = db.prepare("SELECT COUNT(*) AS c FROM tasks WHERE status IN ('queued','running')").get().c;
      if (left === 0) {
        clearInterval(iv);
        resolve();
      } else if (Date.now() - startAt > (timeoutMs || 15000)) {
        clearInterval(iv);
        reject(new Error('任务超时'));
      }
    }, 50);
  });
}

module.exports = { enqueue, retryTask, start, stop, tick, waitAll, handleDiagnose };
