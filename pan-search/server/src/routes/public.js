const express = require('express');
const store = require('../services/store');

const router = express.Router();

router.get('/search', (req, res) => {
  const { q, cloud, type, time, page, size, sort, fileSize } = req.query;
  const result = store.search({ q, cloud, type, time, page, size, sort, fileSize });
  if (q && q.trim()) {
    store.logSearch(q.trim(), result.total, result.took_ms, req.ip);
  }
  res.json(result);
});

router.get('/search/suggest', (req, res) => {
  const { q, limit } = req.query;
  const suggestions = store.searchSuggestions(q, parseInt(limit, 10) || 8);
  res.json({ suggestions });
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
  if (!Number.isInteger(id) || id <= 0) {
    return res.status(400).json({ error: '无效的资源 ID' });
  }
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

router.get('/resources/:id/detail', (req, res) => {
  const id = parseInt(req.params.id, 10);
  const { db, prepare } = require('../db');
  const resource = prepare('SELECT * FROM resources WHERE id = ? AND status != \'blocked\'').get(id);
  if (!resource) return res.status(404).json({ error: '资源不存在或已屏蔽' });

  const files = prepare(`
    SELECT id, parent_id, name, is_dir, size, created_at
    FROM resource_files
    WHERE resource_id = ?
    ORDER BY is_dir DESC, name ASC
  `).all(id);

  const buildTree = (parentId = null) => {
    return files
      .filter(f => f.parent_id === parentId)
      .map(f => ({
        ...f,
        is_dir: Boolean(f.is_dir),
        size: f.size ? Number(f.size) : null,
        children: f.is_dir ? buildTree(f.id) : undefined
      }));
  };

  res.json({
    id: resource.id,
    title: resource.title,
    link: resource.link,
    password: resource.password,
    cloud_type: resource.cloud_type,
    res_type: resource.res_type,
    channel: resource.channel,
    size_text: resource.size_text,
    status: resource.status,
    published_at: resource.published_at,
    files: buildTree()
  });
});

router.get('/resources/:id/check', async (req, res) => {
  const id = parseInt(req.params.id, 10);
  const { prepare } = require('../db');
  const resource = prepare('SELECT id, link, password, cloud_type, status FROM resources WHERE id = ? AND status != \'blocked\'').get(id);
  if (!resource) return res.status(404).json({ valid: false, error: '资源不存在' });

  const link = resource.password && resource.link.includes('pan.baidu.com') && !resource.link.includes('pwd=')
    ? `${resource.link}?pwd=${resource.password}`
    : resource.link;

  const cloudType = resource.cloud_type;

  if (cloudType === 'baidu') {
    try {
      const controller = new AbortController();
      const timeout = setTimeout(() => controller.abort(), 8000);
      const resp = await fetch(link, {
        method: 'GET',
        redirect: 'follow',
        signal: controller.signal,
        headers: { 'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36' },
      });
      clearTimeout(timeout);
      const html = await resp.text();
      const invalidMarkers = [
        '啊哦，你来晚了',
        '分享的文件已经被取消了',
        '分享的文件已经被删除',
        '你所访问的页面不存在',
        '页面不存在',
      ];
      const isInvalid = invalidMarkers.some((m) => html.includes(m));
      if (isInvalid && resource.status === 'ok') {
        prepare('UPDATE resources SET status = \'expired\' WHERE id = ?').run(id);
      }
      return res.json({ valid: !isInvalid, url: link, cloud_type: cloudType, checked: true });
    } catch (err) {
      return res.json({ valid: resource.status !== 'expired', url: link, cloud_type: cloudType, checked: false, error: '校验超时' });
    }
  }

  res.json({ valid: resource.status !== 'expired', url: link, cloud_type: cloudType, checked: false });
});

router.get('/resources/:id/url', (req, res) => {
  const id = parseInt(req.params.id, 10);
  const { prepare } = require('../db');
  const resource = prepare('SELECT link, password FROM resources WHERE id = ? AND status != \'blocked\'').get(id);
  if (!resource) return res.status(404).json({ error: '资源不存在' });

  const url = resource.password && resource.link.includes('pan.baidu.com') && !resource.link.includes('pwd=')
    ? `${resource.link}?pwd=${resource.password}`
    : resource.link;

  res.json({ url });
});

router.post('/resources/submit', (req, res) => {
  const { title, link, password, cloud_type, res_type, size_text, submitter } = req.body || {};
  if (!title || !link) {
    return res.status(400).json({ error: '请填写资源标题和链接' });
  }
  if (String(title).length < 2) {
    return res.status(400).json({ error: '标题至少 2 个字符' });
  }
  const words = store.getSensitiveWords();
  const blocked = store.containsSensitive(title, words) || store.containsSensitive(link, words);
  if (blocked) {
    return res.status(400).json({ error: '提交的内容包含敏感信息' });
  }
  store.submitResource({ title, link, password, cloud_type, res_type, size_text, submitter });
  res.json({ ok: true });
});

module.exports = router;
