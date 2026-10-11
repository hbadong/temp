const crypto = require('crypto');

function now() {
  return Math.floor(Date.now() / 1000);
}

function ok(res, data, msg) {
  res.json({ code: 1, msg: msg || 'ok', data: data == null ? {} : data });
}

function fail(res, msg, data) {
  res.json({ code: 0, msg: msg || '失败', data: data == null ? {} : data });
}

function hashPassword(password) {
  const salt = crypto.randomBytes(16);
  const hash = crypto.scryptSync(String(password), salt, 32);
  return salt.toString('hex') + ':' + hash.toString('hex');
}

function verifyPassword(password, stored) {
  if (!stored || !stored.includes(':')) return false;
  const [saltHex, hashHex] = stored.split(':');
  try {
    const hash = crypto.scryptSync(String(password), Buffer.from(saltHex, 'hex'), 32);
    const expected = Buffer.from(hashHex, 'hex');
    if (hash.length !== expected.length) return false;
    return crypto.timingSafeEqual(hash, expected);
  } catch {
    return false;
  }
}

function randomToken(bytes) {
  return crypto.randomBytes(bytes || 32).toString('hex');
}

function parseCookies(header) {
  const out = {};
  if (!header) return out;
  String(header).split(';').forEach((part) => {
    const i = part.indexOf('=');
    if (i < 0) return;
    const k = part.slice(0, i).trim();
    const v = decodeURIComponent(part.slice(i + 1).trim());
    out[k] = v;
  });
  return out;
}

function setCookie(res, name, value, maxAge) {
  const parts = [
    `${name}=${encodeURIComponent(value)}`,
    'Path=/',
    'HttpOnly',
    'SameSite=Lax',
  ];
  if (maxAge != null) parts.push(`Max-Age=${maxAge}`);
  const prev = res.getHeader('Set-Cookie');
  const all = prev ? (Array.isArray(prev) ? prev : [prev]) : [];
  all.push(parts.join('; '));
  res.setHeader('Set-Cookie', all);
}

function clearCookie(res, name) {
  setCookie(res, name, '', 0);
}

function seedHash(parts) {
  return crypto.createHash('sha256').update(parts.join('|')).digest('hex');
}

function seededInt(hex, min, max) {
  const n = parseInt(hex.slice(0, 8), 16);
  return min + (n % (max - min + 1));
}

function paginate(query) {
  const page = Math.max(1, parseInt(query.page, 10) || 1);
  const pageSize = Math.min(100, Math.max(1, parseInt(query.page_size, 10) || 20));
  return { page, pageSize, offset: (page - 1) * pageSize };
}

function readJson(value, fallback) {
  if (value == null || value === '') return fallback;
  if (typeof value !== 'string') return value;
  try {
    return JSON.parse(value);
  } catch {
    return fallback;
  }
}

module.exports = {
  now,
  ok,
  fail,
  hashPassword,
  verifyPassword,
  randomToken,
  parseCookies,
  setCookie,
  clearCookie,
  seedHash,
  seededInt,
  paginate,
  readJson,
};
