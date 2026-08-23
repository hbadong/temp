const express = require('express');
const { db, prepare, now } = require('../db');
const store = require('../services/store');
const { collectFreshRound } = require('../services/collector');
const { ftsText } = require('../db');

const router = express.Router();

function auth(req, res, next) {
  const token = req.headers['x-admin-token'];
  if (!token || token !== adminToken()) {
    return res.status(401).json({ error: '未授权' });
  }
  next();
}

function adminToken() {
  return process.env.ADMIN_TOKEN || 'admin123';
}

router.post('/login', (req, res) => {
  const { token } = req.body || {};
  if (token && token === adminToken()) {
    return res.json({ ok: true });
  }
  res.status(401).json({ error: '管理令牌错误' });
});

router.use(auth);

router.get('/dashboard', (req, res) => {
  const total = prepare('SELECT COUNT(*) AS c FROM resources').get().c;
  const todayStart = new Date();
  todayStart.setHours(0, 0, 0, 0);
  const todayNew = prepare('SELECT COUNT(*) AS c FROM resources WHERE created_at >= ?')
    .get(todayStart.toISOString()).c;
  const expired = prepare("SELECT COUNT(*) AS c FROM resources WHERE status = 'expired' OR invalid_reports >= 5").get().c;
  const blocked = prepare("SELECT COUNT(*) AS c FROM resources WHERE status = 'blocked'").get().c;
  const pendingFeedbacks = prepare("SELECT COUNT(*) AS c FROM feedbacks WHERE status = 'pending'").get().c;
  const byCloud = prepare('SELECT cloud_type, COUNT(*) AS c FROM resources GROUP BY cloud_type ORDER BY c DESC').all();
  const byChannel = prepare(`
    SELECT username, collected, enabled, blocked FROM channels ORDER BY collected DESC LIMIT 20
  `).all();
  const recentLogs = prepare('SELECT * FROM collect_logs ORDER BY id DESC LIMIT 10').all();
  const searchCount24h = prepare(`
    SELECT COUNT(*) AS c FROM search_logs WHERE created_at > ?
  `).get(new Date(Date.now() - 24 * 3600 * 1000).toISOString()).c;
  res.json({
    total,
    today_new: todayNew,
    expired,
    blocked,
    pending_feedbacks: pendingFeedbacks,
    search_count_24h: searchCount24h,
    by_cloud: byCloud,
    channels: byChannel,
    recent_logs: recentLogs,
  });
});

router.get('/resources', (req, res) => {
  const page = Math.max(1, parseInt(req.query.page, 10) || 1);
  const size = Math.min(50, Math.max(1, parseInt(req.query.size, 10) || 20));
  const conditions = [];
  const params = [];
  if (req.query.q) {
    conditions.push('(title LIKE ? OR link LIKE ?)');
    params.push(`%${req.query.q}%`, `%${req.query.q}%`);
  }
  if (req.query.cloud) {
    conditions.push('cloud_type = ?');
    params.push(req.query.cloud);
  }
  if (req.query.status) {
    conditions.push('status = ?');
    params.push(req.query.status);
  }
  const where = conditions.length ? `WHERE ${conditions.join(' AND ')}` : '';
  const total = prepare(`SELECT COUNT(*) AS c FROM resources ${where}`).get(...params).c;
  const items = prepare(`
    SELECT * FROM resources ${where} ORDER BY id DESC LIMIT ? OFFSET ?
  `).all(...params, size, (page - 1) * size);
  res.json({ total, page, size, items });
});

router.post('/resources', (req, res) => {
  const { title, link, password, cloud_type, res_type, size_text } = req.body || {};
  if (!title || !link) return res.status(400).json({ error: 'title 和 link 必填' });
  const hit = store.containsSensitive(title, store.getSensitiveWords());
  if (hit) return res.status(400).json({ error: `标题包含敏感词：${hit}` });
  const ts = now();
  const info = prepare(`
    INSERT OR IGNORE INTO resources
      (fingerprint, title, title_t, link, password, cloud_type, res_type, channel, size_text, status, created_at, updated_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, 'manual', ?, 'ok', ?, ?)
  `).run(
    `manual:${link}`, title, ftsText(title), link, password || null,
    cloud_type || 'other', res_type || 'other', size_text || null, ts, ts
  );
  if (info.changes === 0) return res.status(409).json({ error: '该链接已存在' });
  store.flushSearchCache();
  res.json({ ok: true, id: Number(info.lastInsertRowid) });
});

router.put('/resources/:id', (req, res) => {
  const id = parseInt(req.params.id, 10);
  const allowed = ['status', 'title'];
  const resource = store.getResource(id);
  if (!resource) return res.status(404).json({ error: '资源不存在' });
  const updates = [];
  const params = [];
  for (const key of allowed) {
    if (req.body && req.body[key] !== undefined) {
      if (key === 'status' && !['ok', 'expired', 'blocked'].includes(req.body[key])) continue;
      updates.push(`${key} = ?`);
      params.push(req.body[key]);
    }
  }
  if (updates.length === 0) return res.status(400).json({ error: '无可更新字段' });
  updates.push('updated_at = ?');
  params.push(now(), id);
  prepare(`UPDATE resources SET ${updates.join(', ')} WHERE id = ?`).run(...params);
  store.flushSearchCache();
  res.json({ ok: true });
});

router.delete('/resources/:id', (req, res) => {
  const info = prepare('DELETE FROM resources WHERE id = ?').run(parseInt(req.params.id, 10));
  store.flushSearchCache();
  res.json({ ok: info.changes > 0 });
});

router.get('/channels', (req, res) => {
  res.json({
    items: prepare('SELECT * FROM channels ORDER BY id DESC').all(),
  });
});

router.post('/channels', (req, res) => {
  const { username, note } = req.body || {};
  if (!username || !/^@[A-Za-z0-9_]{4,}$/.test(username.trim())) {
    return res.status(400).json({ error: '频道格式应为 @username' });
  }
  try {
    prepare('INSERT INTO channels (username, note, created_at) VALUES (?, ?, ?)')
      .run(username.trim(), note || null, now());
    res.json({ ok: true });
  } catch (err) {
    res.status(409).json({ error: '频道已存在' });
  }
});

router.put('/channels/:id', (req, res) => {
  const id = parseInt(req.params.id, 10);
  const { enabled, blocked } = req.body || {};
  if (enabled !== undefined) prepare('UPDATE channels SET enabled = ? WHERE id = ?').run(enabled ? 1 : 0, id);
  if (blocked !== undefined) prepare('UPDATE channels SET blocked = ? WHERE id = ?').run(blocked ? 1 : 0, id);
  res.json({ ok: true });
});

router.delete('/channels/:id', (req, res) => {
  const info = prepare('DELETE FROM channels WHERE id = ?').run(parseInt(req.params.id, 10));
  res.json({ ok: info.changes > 0 });
});

router.get('/sensitive', (req, res) => {
  res.json({ items: prepare('SELECT * FROM sensitive_words ORDER BY id DESC').all() });
});

router.post('/sensitive', (req, res) => {
  const { word } = req.body || {};
  const w = (word || '').trim().toLowerCase();
  if (!w) return res.status(400).json({ error: 'word 必填' });
  try {
    prepare('INSERT INTO sensitive_words (word, created_at) VALUES (?, ?)').run(w, now());
    res.json({ ok: true });
  } catch (err) {
    res.status(409).json({ error: '敏感词已存在' });
  }
});

router.delete('/sensitive/:id', (req, res) => {
  const info = prepare('DELETE FROM sensitive_words WHERE id = ?').run(parseInt(req.params.id, 10));
  res.json({ ok: info.changes > 0 });
});

router.get('/feedbacks', (req, res) => {
  const { status } = req.query;
  const where = status ? 'WHERE f.status = ?' : '';
  const params = status ? [status] : [];
  const items = prepare(`
    SELECT f.*, r.title AS resource_title, r.link AS resource_link
    FROM feedbacks f LEFT JOIN resources r ON r.id = f.resource_id
    ${where} ORDER BY f.id DESC LIMIT 100
  `).all(...params);
  res.json({ items });
});

router.put('/feedbacks/:id', (req, res) => {
  const { status } = req.body || {};
  if (!['pending', 'resolved', 'rejected'].includes(status)) {
    return res.status(400).json({ error: 'status 无效' });
  }
  prepare('UPDATE feedbacks SET status = ? WHERE id = ?').run(status, parseInt(req.params.id, 10));
  res.json({ ok: true });
});

router.get('/collect-logs', (req, res) => {
  res.json({
    items: prepare('SELECT * FROM collect_logs ORDER BY id DESC LIMIT 50').all(),
  });
});

router.post('/collect/run', (req, res) => {
  const result = collectFreshRound();
  res.json({ ok: true, ...result });
});

router.post('/import', (req, res) => {
  const { messages, source } = req.body || {};
  if (!Array.isArray(messages) || messages.length === 0) {
    return res.status(400).json({ error: 'messages 必须为非空数组' });
  }
  if (messages.length > 1000) {
    return res.status(400).json({ error: '单次导入上限 1000 条' });
  }
  const normalized = messages
    .map((m) => (typeof m === 'string' ? { text: m } : m))
    .map((m) => {
      const parsed = require('../parser').parseMessage(m.text || '', m.channel, m.date);
      if (parsed && m.msg_id) parsed.fingerprint = `tg:${m.channel || ''}:${m.msg_id}`;
      return parsed;
    })
    .filter(Boolean);
  const result = store.importMessages(normalized, source || 'tg-importer');
  res.json({ ok: true, ...result });
});

router.get('/submissions', (req, res) => {
  const status = req.query.status || 'pending';
  const page = Math.max(1, parseInt(req.query.page, 10) || 1);
  const size = Math.min(50, Math.max(1, parseInt(req.query.size, 10) || 20));
  res.json(store.listSubmissions({ status, page, size }));
});

router.post('/submissions/:id/approve', (req, res) => {
  const id = parseInt(req.params.id, 10);
  const ok = store.approveSubmission(id);
  if (!ok) return res.status(404).json({ error: '提交不存在或已处理' });
  store.flushSearchCache();
  res.json({ ok: true });
});

router.post('/submissions/:id/reject', (req, res) => {
  const id = parseInt(req.params.id, 10);
  const { note } = req.body || {};
  const ok = store.rejectSubmission(id, note);
  if (!ok) return res.status(404).json({ error: '提交不存在或已处理' });
  res.json({ ok: true });
});

module.exports = router;
