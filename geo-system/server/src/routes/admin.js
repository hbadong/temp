const express = require('express');
const { db, getSetting, setSetting } = require('../db');
const { ok, fail, paginate, now } = require('../lib');
const { requireAdmin, adminLogin, adminLogout } = require('../auth');
const { creditMoney, chargeMoney, creditScore, chargeScore } = require('../billing');

const router = express.Router();

router.post('/login', adminLogin);
router.post('/logout', adminLogout);

router.use(requireAdmin);

router.get('/me', (req, res) => {
  ok(res, { username: req.admin.username });
});

router.get('/kyc', (req, res) => {
  const status = req.query.status;
  let sql = 'SELECT k.*, u.username FROM kyc_records k JOIN users u ON u.id=k.user_id';
  const args = [];
  if (status) {
    sql += ' WHERE k.status=?';
    args.push(status);
  }
  sql += ' ORDER BY k.id DESC';
  ok(res, { list: db.prepare(sql).all(...args) });
});

router.post('/kyc/:id', (req, res) => {
  const rec = db.prepare('SELECT * FROM kyc_records WHERE id=?').get(req.params.id);
  if (!rec) return fail(res, '记录不存在');
  const action = (req.body || {}).action;
  if (action === 'approve') {
    db.prepare("UPDATE kyc_records SET status='approved' WHERE id=?").run(rec.id);
    db.prepare("UPDATE users SET kyc_status='approved', kyc_reject_reason='' WHERE id=?").run(rec.user_id);
  } else if (action === 'reject') {
    const reason = String((req.body || {}).reason || '资料不完整');
    db.prepare("UPDATE kyc_records SET status='rejected', reject_reason=? WHERE id=?").run(reason, rec.id);
    db.prepare("UPDATE users SET kyc_status='rejected', kyc_reject_reason=? WHERE id=?").run(reason, rec.user_id);
  } else {
    return fail(res, '未知操作');
  }
  ok(res, {});
});

router.post('/adjust', (req, res) => {
  const b = req.body || {};
  const username = String(b.username || '').trim();
  const user = db.prepare('SELECT * FROM users WHERE username=?').get(username);
  if (!user) return fail(res, '用户不存在');
  const amount = parseInt(b.amount, 10);
  if (!amount) return fail(res, '调整金额不能为 0');
  const kind = b.kind === 'score' ? 'score' : 'money';
  const remark = String(b.remark || '管理员调账');
  const meta = { type: 'adjust', remark, operator: req.admin.username, refType: 'admin' };
  if (kind === 'score') {
    if (amount > 0) creditScore(user.id, amount, meta);
    else {
      const r = chargeScore(user.id, -amount, meta);
      if (!r.ok) return fail(res, r.msg);
    }
  } else if (amount > 0) creditMoney(user.id, amount, meta);
  else {
    const r = chargeMoney(user.id, -amount, meta);
    if (!r.ok) return fail(res, r.msg);
  }
  const fresh = db.prepare('SELECT * FROM users WHERE id=?').get(user.id);
  ok(res, { balance: fresh.balance, score: fresh.score });
});

router.get('/tasks', (req, res) => {
  const { page, pageSize, offset } = paginate(req.query);
  const type = req.query.type;
  const status = req.query.status;
  let where = '1=1';
  const args = [];
  if (type) { where += ' AND type=?'; args.push(type); }
  if (status) { where += ' AND status=?'; args.push(status); }
  const total = db.prepare(`SELECT COUNT(*) AS c FROM tasks WHERE ${where}`).get(...args).c;
  const list = db.prepare(`SELECT t.*, u.username FROM tasks t JOIN users u ON u.id=t.user_id WHERE ${where} ORDER BY t.id DESC LIMIT ? OFFSET ?`)
    .all(...args, pageSize, offset);
  ok(res, { list, total, page, page_size: pageSize });
});

router.get('/settings', (req, res) => {
  const rows = db.prepare('SELECT key, value FROM settings').all();
  const obj = {};
  rows.forEach((r) => { obj[r.key] = r.value; });
  ok(res, obj);
});

router.put('/settings', (req, res) => {
  const b = req.body || {};
  Object.keys(b).forEach((k) => setSetting(k, b[k]));
  const rows = db.prepare('SELECT key, value FROM settings').all();
  const obj = {};
  rows.forEach((r) => { obj[r.key] = r.value; });
  ok(res, obj);
});

router.get('/users', (req, res) => {
  const list = db.prepare('SELECT id, username, nickname, balance, score, kyc_status, entitlement_plan FROM users').all();
  ok(res, { list });
});

module.exports = router;
