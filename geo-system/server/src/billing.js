const { db, getSetting } = require('./db');
const { now } = require('./lib');

function prices() {
  return {
    diagnose: parseInt(getSetting('diagnose_price', '10000'), 10),
    diagnoseAdvice: parseInt(getSetting('diagnose_advice_price', '10200'), 10),
    proParse: parseInt(getSetting('pro_parse_price', '0'), 10),
    proDiagnose: parseInt(getSetting('pro_diagnose_price', '15000'), 10),
    indexScore: parseInt(getSetting('index_score_cost', '10'), 10),
    distillScore: parseInt(getSetting('distill_score_cost', '10'), 10),
    write: parseInt(getSetting('write_price', '200'), 10),
    publish: parseInt(getSetting('publish_price', '100'), 10),
  };
}

function chargeMoney(userId, amount, meta) {
  if (amount === 0) {
    const user = db.prepare('SELECT balance FROM users WHERE id=?').get(userId);
    return { ok: true, balance: user ? user.balance : 0 };
  }
  if (amount < 0) {
    return creditMoney(userId, -amount, meta);
  }
  const info = db.prepare('UPDATE users SET balance = balance - ? WHERE id=? AND balance >= ?').run(amount, userId, amount);
  if (info.changes === 0) return { ok: false, msg: '余额不足' };
  const user = db.prepare('SELECT balance FROM users WHERE id=?').get(userId);
  db.prepare(`INSERT INTO ledger_money (user_id, delta, balance_after, type, remark, ref_type, ref_id, operator, created_at)
    VALUES (?,?,?,?,?,?,?,?,?)`).run(
    userId, -amount, user.balance, meta.type || 'consume', meta.remark || '',
    meta.refType || '', meta.refId || 0, meta.operator || 'self', now()
  );
  return { ok: true, balance: user.balance };
}

function creditMoney(userId, amount, meta) {
  db.prepare('UPDATE users SET balance = balance + ? WHERE id=?').run(amount, userId);
  const user = db.prepare('SELECT balance FROM users WHERE id=?').get(userId);
  db.prepare(`INSERT INTO ledger_money (user_id, delta, balance_after, type, remark, ref_type, ref_id, operator, created_at)
    VALUES (?,?,?,?,?,?,?,?,?)`).run(
    userId, amount, user.balance, meta.type || 'adjust', meta.remark || '',
    meta.refType || '', meta.refId || 0, meta.operator || 'self', now()
  );
  return { ok: true, balance: user.balance };
}

function chargeScore(userId, amount, meta) {
  if (amount <= 0) {
    const user = db.prepare('SELECT score FROM users WHERE id=?').get(userId);
    return { ok: true, score: user ? user.score : 0 };
  }
  const info = db.prepare('UPDATE users SET score = score - ? WHERE id=? AND score >= ?').run(amount, userId, amount);
  if (info.changes === 0) return { ok: false, msg: '点数不足' };
  const user = db.prepare('SELECT score FROM users WHERE id=?').get(userId);
  db.prepare(`INSERT INTO ledger_score (user_id, delta, balance_after, type, remark, ref_type, ref_id, operator, created_at)
    VALUES (?,?,?,?,?,?,?,?,?)`).run(
    userId, -amount, user.score, meta.type || 'consume', meta.remark || '',
    meta.refType || '', meta.refId || 0, meta.operator || 'self', now()
  );
  return { ok: true, score: user.score };
}

function creditScore(userId, amount, meta) {
  db.prepare('UPDATE users SET score = score + ? WHERE id=?').run(amount, userId);
  const user = db.prepare('SELECT score FROM users WHERE id=?').get(userId);
  db.prepare(`INSERT INTO ledger_score (user_id, delta, balance_after, type, remark, ref_type, ref_id, operator, created_at)
    VALUES (?,?,?,?,?,?,?,?,?)`).run(
    userId, amount, user.score, meta.type || 'adjust', meta.remark || '',
    meta.refType || '', meta.refId || 0, meta.operator || 'self', now()
  );
  return { ok: true, score: user.score };
}

function dayStart() {
  const d = new Date();
  d.setHours(0, 0, 0, 0);
  return Math.floor(d.getTime() / 1000);
}

function checkEntitlement(userId) {
  const u = db.prepare('SELECT * FROM users WHERE id=?').get(userId);
  if (!u) return { ok: false, msg: '用户不存在' };
  if (u.entitlement_expire > 0 && u.entitlement_expire <= now()) {
    return { ok: false, msg: '套餐已到期，日配额视为 0，请先续购套餐' };
  }
  return { ok: true, user: u, start: dayStart() };
}

function checkWriteQuota(userId) {
  const e = checkEntitlement(userId);
  if (!e.ok) return e;
  const used = db.prepare("SELECT COUNT(*) AS c FROM tasks WHERE user_id=? AND type IN ('write') AND created_at>=?")
    .get(userId, e.start).c;
  if (used >= e.user.daily_write_quota) {
    return { ok: false, msg: `今日写作配额已用完（${e.user.daily_write_quota}/天）` };
  }
  return { ok: true, used, quota: e.user.daily_write_quota };
}

function checkPublishQuota(userId) {
  const e = checkEntitlement(userId);
  if (!e.ok) return e;
  const used = db.prepare('SELECT COUNT(*) AS c FROM publish_jobs WHERE user_id=? AND created_at>=?')
    .get(userId, e.start).c;
  if (used >= e.user.daily_pub_quota) {
    return { ok: false, msg: `今日发布任务配额已用完（${e.user.daily_pub_quota}/天）` };
  }
  return { ok: true, used, quota: e.user.daily_pub_quota };
}

module.exports = { prices, chargeMoney, creditMoney, chargeScore, creditScore, checkWriteQuota, checkPublishQuota };
