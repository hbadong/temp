const path = require('path');
const fs = require('fs');
const express = require('express');
const { DATA_DIR } = require('./db');
const { login, logout, me, changePassword, requireUser } = require('./auth');
const tasks = require('./tasks');

const PORT = process.env.PORT || 3001;
const app = express();

app.use(express.json({ limit: '4mb' }));
app.set('trust proxy', true);
app.use((req, res, next) => {
  res.setHeader('X-Content-Type-Options', 'nosniff');
  next();
});

app.get('/api/health', (req, res) => res.json({ code: 1, msg: 'ok', data: { ok: true } }));

app.post('/api/auth/login', login);
app.post('/api/auth/logout', logout);
app.get('/api/me', requireUser, me);
app.put('/api/me/password', requireUser, changePassword);

app.use('/api/admin', require('./routes/admin'));
app.use('/api/analyze', require('./routes/analyze'));
app.use('/api', require('./routes/content'));
app.use('/api', require('./routes/publish'));
app.use('/api', require('./routes/tools'));

app.get('/api/files/uploads/:userId/:file', requireUser, (req, res) => {
  if (String(req.user.id) !== String(req.params.userId)) {
    res.status(403).json({ code: 0, msg: '无权限', data: {} });
    return;
  }
  const full = path.join(DATA_DIR, 'uploads', req.params.userId, req.params.file);
  if (!full.startsWith(path.join(DATA_DIR, 'uploads'))) {
    res.status(400).end();
    return;
  }
  if (!fs.existsSync(full)) {
    res.status(404).end();
    return;
  }
  res.sendFile(full);
});

app.get('/api/placeholder/screenshot', requireUser, (req, res) => {
  const q = String(req.query.q || '');
  const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="640" height="360">
    <rect width="100%" height="100%" fill="#eef2ff"/>
    <text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="#4f46e5" font-size="20">收录截图占位：${q.replace(/[<>&]/g, '')}</text>
  </svg>`;
  res.setHeader('Content-Type', 'image/svg+xml');
  res.send(svg);
});

app.use((err, req, res, next) => {
  console.error('[server]', err.message);
  res.status(500).json({ code: 0, msg: '服务器内部错误', data: {} });
});

tasks.start();

if (require.main === module) {
  app.listen(PORT, () => {
    console.log(`[server] geo-system API listening on http://localhost:${PORT}`);
  });
}

module.exports = { app };
