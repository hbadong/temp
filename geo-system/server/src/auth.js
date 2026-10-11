const { db } = require('./db');
const {
  now, ok, fail, verifyPassword, hashPassword, randomToken,
  parseCookies, setCookie, clearCookie,
} = require('./lib');

const SESSION_TTL = 8 * 3600;
const KEEP_TTL = 86400;

function readSessionUser(req) {
  const cookies = parseCookies(req.headers.cookie);
  const token = cookies.geo_sid;
  if (token) {
    const row = db.prepare(`SELECT u.* FROM sessions s JOIN users u ON u.id=s.user_id
      WHERE s.token=? AND s.expire_at > ?`).get(token, now());
    if (row) return row;
  }
  const keep = cookies.geo_keep;
  if (keep) {
    const row = db.prepare(`SELECT u.* FROM keep_tokens k JOIN users u ON u.id=k.user_id
      WHERE k.token=? AND k.expire_at > ?`).get(keep, now());
    if (row) {
      req._restoreKeep = { user: row, keep };
      return row;
    }
  }
  return null;
}

function publicUser(u) {
  return {
    id: u.id,
    username: u.username,
    nickname: u.nickname,
    balance: u.balance,
    score: u.score,
    kyc_status: u.kyc_status,
    kyc_reject_reason: u.kyc_reject_reason,
    entitlement_plan: u.entitlement_plan,
    entitlement_expire: u.entitlement_expire,
    daily_write_quota: u.daily_write_quota,
    daily_pub_quota: u.daily_pub_quota,
  };
}

function requireUser(req, res, next) {
  const user = readSessionUser(req);
  if (!user) {
    res.status(401).json({ code: 0, msg: '未登录', data: {} });
    return;
  }
  if (req._restoreKeep && !parseCookies(req.headers.cookie).geo_sid) {
    const token = randomToken(32);
    db.prepare('INSERT INTO sessions (user_id, token, expire_at, created_at) VALUES (?,?,?,?)')
      .run(user.id, token, now() + SESSION_TTL, now());
    setCookie(res, 'geo_sid', token, SESSION_TTL);
  }
  req.user = user;
  next();
}

function requireKyc(req, res, next) {
  if (!req.user || req.user.kyc_status !== 'approved') {
    fail(res, '请先完成实名认证');
    return;
  }
  next();
}

function requireAdmin(req, res, next) {
  const cookies = parseCookies(req.headers.cookie);
  const token = cookies.geo_admin;
  if (!token) {
    res.status(401).json({ code: 0, msg: '未登录', data: {} });
    return;
  }
  const row = db.prepare(`SELECT a.* FROM admin_sessions s JOIN admin_users a ON a.id=s.admin_id
    WHERE s.token=? AND s.expire_at > ?`).get(token, now());
  if (!row) {
    res.status(401).json({ code: 0, msg: '未登录', data: {} });
    return;
  }
  req.admin = row;
  next();
}

function login(req, res) {
  const { username, password, keeplogin } = req.body || {};
  if (!username || !password) return fail(res, '账号或密码错误');
  const user = db.prepare('SELECT * FROM users WHERE username=?').get(String(username).trim());
  if (!user || !verifyPassword(password, user.password_hash)) return fail(res, '账号或密码错误');
  const token = randomToken(32);
  db.prepare('INSERT INTO sessions (user_id, token, expire_at, created_at) VALUES (?,?,?,?)')
    .run(user.id, token, now() + SESSION_TTL, now());
  setCookie(res, 'geo_sid', token, SESSION_TTL);
  if (keeplogin) {
    const keep = randomToken(32);
    db.prepare('INSERT INTO keep_tokens (user_id, token, expire_at, created_at) VALUES (?,?,?,?)')
      .run(user.id, keep, now() + KEEP_TTL, now());
    setCookie(res, 'geo_keep', keep, KEEP_TTL);
  }
  ok(res, publicUser(user));
}

function logout(req, res) {
  const cookies = parseCookies(req.headers.cookie);
  if (cookies.geo_sid) db.prepare('DELETE FROM sessions WHERE token=?').run(cookies.geo_sid);
  if (cookies.geo_keep) db.prepare('DELETE FROM keep_tokens WHERE token=?').run(cookies.geo_keep);
  clearCookie(res, 'geo_sid');
  clearCookie(res, 'geo_keep');
  ok(res, {});
}

function me(req, res) {
  const fresh = db.prepare('SELECT * FROM users WHERE id=?').get(req.user.id);
  ok(res, publicUser(fresh));
}

function changePassword(req, res) {
  const { old_password, new_password } = req.body || {};
  if (!old_password || !new_password) return fail(res, '请填写原密码和新密码');
  const user = db.prepare('SELECT * FROM users WHERE id=?').get(req.user.id);
  if (!verifyPassword(old_password, user.password_hash)) return fail(res, '原密码不正确');
  db.prepare('UPDATE users SET password_hash=? WHERE id=?').run(hashPassword(new_password), user.id);
  ok(res, {});
}

function adminLogin(req, res) {
  const { username, password } = req.body || {};
  if (!username || !password) return fail(res, '账号或密码错误');
  const admin = db.prepare('SELECT * FROM admin_users WHERE username=?').get(String(username).trim());
  if (!admin || !verifyPassword(password, admin.password_hash)) return fail(res, '账号或密码错误');
  const token = randomToken(32);
  db.prepare('INSERT INTO admin_sessions (admin_id, token, expire_at, created_at) VALUES (?,?,?,?)')
    .run(admin.id, token, now() + SESSION_TTL, now());
  setCookie(res, 'geo_admin', token, SESSION_TTL);
  ok(res, { username: admin.username });
}

function adminLogout(req, res) {
  const cookies = parseCookies(req.headers.cookie);
  if (cookies.geo_admin) db.prepare('DELETE FROM admin_sessions WHERE token=?').run(cookies.geo_admin);
  clearCookie(res, 'geo_admin');
  ok(res, {});
}

module.exports = {
  requireUser, requireKyc, requireAdmin, publicUser,
  login, logout, me, changePassword, adminLogin, adminLogout,
};
