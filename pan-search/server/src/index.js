const express = require('express');
const cluster = require('node:cluster');
const { seedDatabase, startScheduler } = require('./services/collector');
const { rateLimit } = require('./middleware/rateLimit');

const PORT = process.env.PORT || 3001;
const WORKERS = Math.max(1, parseInt(process.env.WORKERS, 10) || 1);

if (cluster.isPrimary && WORKERS > 1) {
  const seedResult = seedDatabase();
  if (seedResult.seeded) {
    console.log(`[seed] 已导入 ${seedResult.inserted} 条演示资源`);
  }
  startScheduler();
  for (let i = 0; i < WORKERS; i++) cluster.fork();
  cluster.on('exit', (worker) => {
    console.error(`[cluster] worker ${worker.process.pid} 退出，重新拉起`);
    cluster.fork();
  });
} else {
  const app = express();

  app.use(express.json({ limit: '2mb' }));
  app.set('trust proxy', true);

  app.use((req, res, next) => {
    res.setHeader('X-Content-Type-Options', 'nosniff');
    next();
  });

  const globalLimiter = rateLimit({
    windowMs: 60000,
    max: parseInt(process.env.GLOBAL_RATE_LIMIT, 10) || 240,
  });
  const searchLimiter = rateLimit({
    windowMs: 60000,
    max: parseInt(process.env.SEARCH_RATE_LIMIT, 10) || 30,
    message: '搜索过于频繁，请稍后再试',
  });

  app.use('/api', globalLimiter);
  app.use('/api/search', searchLimiter);

  app.get('/api/health', (req, res) => res.json({ ok: true }));

  app.use('/api', require('./routes/public'));
  app.use('/api/admin', require('./routes/admin'));

  app.use((err, req, res, next) => {
    console.error('[server]', err.message);
    res.status(500).json({ error: '服务器内部错误' });
  });

  if (WORKERS <= 1) {
    const seedResult = seedDatabase();
    if (seedResult.seeded) {
      console.log(`[seed] 已导入 ${seedResult.inserted} 条演示资源`);
    }
    startScheduler();
  }

  app.listen(PORT, () => {
    console.log(`[server] pan-search backend listening on http://localhost:${PORT} (worker ${process.pid})`);
  });
}
