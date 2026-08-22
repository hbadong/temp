const buckets = new Map();

function rateLimit({ windowMs = 60000, max = 30, message = '请求过于频繁，请稍后再试' } = {}) {
  setInterval(() => {
    const cutoff = Date.now() - windowMs;
    for (const [key, list] of buckets) {
      const alive = list.filter((t) => t > cutoff);
      if (alive.length === 0) buckets.delete(key);
      else buckets.set(key, alive);
    }
  }, windowMs).unref();

  return (req, res, next) => {
    const ip = req.ip || req.socket.remoteAddress || 'unknown';
    const nowTs = Date.now();
    const cutoff = nowTs - windowMs;
    const list = (buckets.get(ip) || []).filter((t) => t > cutoff);
    if (list.length >= max) {
      return res.status(429).json({ error: message });
    }
    list.push(nowTs);
    buckets.set(ip, list);
    next();
  };
}

module.exports = { rateLimit };
