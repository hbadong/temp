const express = require('express');
const store = require('../services/store');

const router = express.Router();

router.get('/search', (req, res) => {
  const { q, cloud, type, page, size } = req.query;
  const result = store.search({ q, cloud, type, page, size });
  if (q && q.trim()) {
    store.logSearch(q.trim(), result.total, result.took_ms, req.ip);
  }
  res.json(result);
});

router.get('/hot', (req, res) => {
  const rows = store.hotKeywords(10);
  let keywords = rows.map((r) => r.keyword);
  if (keywords.length < 5) {
    const defaults = ['三体', 'Photoshop', '4K 电影', '考研资料', '无损音乐', '壁纸素材'];
    keywords = [...new Set([...keywords, ...defaults])].slice(0, 10);
  }
  res.json({ keywords });
});

router.get('/stats', (req, res) => {
  res.json(store.publicStats());
});

router.post('/resources/:id/report', (req, res) => {
  const id = parseInt(req.params.id, 10);
  const { type, content, contact } = req.body || {};
  if (!['invalid', 'infringe'].includes(type)) {
    return res.status(400).json({ error: 'type 必须为 invalid 或 infringe' });
  }
  const ok = store.reportResource(id, type, content, contact);
  if (!ok) return res.status(404).json({ error: '资源不存在' });
  res.json({ ok: true });
});

router.post('/feedback', (req, res) => {
  const { type, content, contact } = req.body || {};
  if (!content || !contact) {
    return res.status(400).json({ error: '请填写投诉说明和联系方式' });
  }
  const { db, prepare, now } = require('../db');
  prepare('INSERT INTO feedbacks (resource_id, type, content, contact, created_at) VALUES (NULL, ?, ?, ?, ?)')
    .run(type === 'other' ? 'other' : 'infringe', String(content).slice(0, 2000), String(contact).slice(0, 200), now());
  res.json({ ok: true });
});

module.exports = router;
