const express = require('express');
const { db, getSetting } = require('../db');
const { ok, fail, now, paginate } = require('../lib');
const { requireUser, requireKyc } = require('../auth');
const { prices, chargeScore, chargeMoney } = require('../billing');
const { enqueue } = require('../tasks');
const llm = require('../providers/llm');
const channel = require('../providers/channel');

const router = express.Router();
router.use(requireUser);

router.get('/dashboard', (req, res) => {
  const u = db.prepare('SELECT * FROM users WHERE id=?').get(req.user.id);
  const count = (sql) => db.prepare(sql).get(req.user.id).c;
  const days = [];
  const writeSeries = [];
  const pubSeries = [];
  for (let i = 4; i >= 0; i--) {
    const d = new Date();
    d.setHours(0, 0, 0, 0);
    d.setDate(d.getDate() - i);
    const start = Math.floor(d.getTime() / 1000);
    const end = start + 86400;
    const label = `${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    days.push(label);
    writeSeries.push(db.prepare('SELECT COUNT(*) AS c FROM articles WHERE user_id=? AND created_at>=? AND created_at<?').get(req.user.id, start, end).c);
    pubSeries.push(db.prepare('SELECT COUNT(*) AS c FROM publish_records WHERE user_id=? AND published_at>=? AND published_at<?').get(req.user.id, start, end).c);
  }
  ok(res, {
    balance: u.balance,
    score: u.score,
    kyc_status: u.kyc_status,
    keywords: count('SELECT COUNT(*) AS c FROM keywords WHERE user_id=?'),
    titles: count('SELECT COUNT(*) AS c FROM writing_titles WHERE user_id=?'),
    articles: count('SELECT COUNT(*) AS c FROM articles WHERE user_id=?'),
    publishes: count('SELECT COUNT(*) AS c FROM publish_records WHERE user_id=?'),
    auths: count('SELECT COUNT(*) AS c FROM weixin_auths WHERE user_id=?'),
    inclusions: count('SELECT COUNT(*) AS c FROM inclusion_queries WHERE user_id=?'),
    days,
    write_series: writeSeries,
    pub_series: pubSeries,
  });
});

router.get('/ledger/money', (req, res) => {
  const { page, pageSize, offset } = paginate(req.query);
  const total = db.prepare('SELECT COUNT(*) AS c FROM ledger_money WHERE user_id=?').get(req.user.id).c;
  const list = db.prepare('SELECT * FROM ledger_money WHERE user_id=? ORDER BY id DESC LIMIT ? OFFSET ?')
    .all(req.user.id, pageSize, offset);
  ok(res, { list, total, page, page_size: pageSize });
});

router.get('/ledger/score', (req, res) => {
  const { page, pageSize, offset } = paginate(req.query);
  const total = db.prepare('SELECT COUNT(*) AS c FROM ledger_score WHERE user_id=?').get(req.user.id).c;
  const list = db.prepare('SELECT * FROM ledger_score WHERE user_id=? ORDER BY id DESC LIMIT ? OFFSET ?')
    .all(req.user.id, pageSize, offset);
  ok(res, { list, total, page, page_size: pageSize });
});

router.get('/entitlements', requireKyc, (req, res) => {
  const u = db.prepare('SELECT * FROM users WHERE id=?').get(req.user.id);
  ok(res, {
    plan: u.entitlement_plan,
    expire: u.entitlement_expire,
    platforms: Object.values(llm.PLATFORMS),
    daily_write_quota: u.daily_write_quota,
    daily_pub_quota: u.daily_pub_quota,
  });
});

router.post('/kyc/personal', (req, res) => {
  const b = req.body || {};
  if (!b.real_name || !b.id_number || !b.mobile) return fail(res, '请完整填写个人实名信息');
  const cur = req.user.kyc_status;
  if (cur === 'pending' || cur === 'approved') return fail(res, `当前实名状态为「${cur === 'approved' ? '已认证' : '审核中'}」，无需重复提交`);
  db.prepare(`INSERT INTO kyc_records (user_id, kind, real_name, id_number, mobile, status, created_at)
    VALUES (?,?,?,?,?,?,?)`).run(req.user.id, 'personal', b.real_name, b.id_number, b.mobile, 'pending', now());
  db.prepare("UPDATE users SET kyc_status='pending', kyc_reject_reason='' WHERE id=?").run(req.user.id);
  ok(res, { status: 'pending' });
});

router.post('/kyc/company', (req, res) => {
  const b = req.body || {};
  if (!b.company_name || !b.license_no || !b.license_photo || !b.admin_name || !b.admin_id_number || !b.admin_id_photo) {
    return fail(res, '请完整填写企业实名信息');
  }
  const cur = req.user.kyc_status;
  if (cur === 'pending' || cur === 'approved') return fail(res, `当前实名状态为「${cur === 'approved' ? '已认证' : '审核中'}」，无需重复提交`);
  db.prepare(`INSERT INTO kyc_records (user_id, kind, company_name, license_no, license_photo, admin_name, admin_id_number, admin_id_photo, status, created_at)
    VALUES (?,?,?,?,?,?,?,?,?,?)`).run(
    req.user.id, 'company', b.company_name, b.license_no, b.license_photo, b.admin_name, b.admin_id_number, b.admin_id_photo, 'pending', now()
  );
  db.prepare("UPDATE users SET kyc_status='pending', kyc_reject_reason='' WHERE id=?").run(req.user.id);
  ok(res, { status: 'pending' });
});

router.get('/kyc', (req, res) => {
  const last = db.prepare('SELECT * FROM kyc_records WHERE user_id=? ORDER BY id DESC LIMIT 1').get(req.user.id);
  ok(res, { current: last || null, kyc_status: req.user.kyc_status });
});

router.post('/tools/index', (req, res) => {
  const keyword = String((req.body || {}).keyword || '').trim();
  if (!keyword) return fail(res, '请输入关键词');
  const cost = prices().indexScore;
  const billed = chargeScore(req.user.id, cost, { type: 'index', remark: '关键词指数', refType: 'tool' });
  if (!billed.ok) return fail(res, billed.msg);
  ok(res, channel.keywordIndex(keyword));
});

router.post('/tools/distill', (req, res) => {
  const keyword = String((req.body || {}).keyword || '').trim();
  if (!keyword) return fail(res, '请输入核心词');
  const cost = prices().distillScore;
  const billed = chargeScore(req.user.id, cost, { type: 'distill', remark: 'AI拓词', refType: 'tool' });
  if (!billed.ok) return fail(res, billed.msg);
  ok(res, llm.mockDistill(keyword));
});

router.post('/inclusion', requireKyc, (req, res) => {
  const b = req.body || {};
  const query = String(b.query || '').trim();
  if (!query) return fail(res, '请填写查询词或 URL');
  const cost = prices().indexScore;
  const billed = chargeScore(req.user.id, cost, { type: 'inclusion', remark: '收录查询', refType: 'inclusion' });
  if (!billed.ok) return fail(res, billed.msg);
  const taskId = enqueue(req.user.id, 'inclusion', { query, target: b.target || '' }, 1);
  ok(res, { task_id: taskId });
});

router.get('/inclusion', requireKyc, (req, res) => {
  const { page, pageSize, offset } = paginate(req.query);
  const total = db.prepare('SELECT COUNT(*) AS c FROM inclusion_queries WHERE user_id=?').get(req.user.id).c;
  const list = db.prepare('SELECT * FROM inclusion_queries WHERE user_id=? ORDER BY id DESC LIMIT ? OFFSET ?')
    .all(req.user.id, pageSize, offset);
  ok(res, { list, total, page, page_size: pageSize });
});

router.get('/stats', requireKyc, (req, res) => {
  const days = parseInt(req.query.days, 10) === 30 ? 30 : 7;
  const series = [];
  for (let i = days - 1; i >= 0; i--) {
    const d = new Date();
    d.setHours(0, 0, 0, 0);
    d.setDate(d.getDate() - i);
    const start = Math.floor(d.getTime() / 1000);
    const end = start + 86400;
    const label = `${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    const created = db.prepare('SELECT COUNT(*) AS c FROM articles WHERE user_id=? AND created_at>=? AND created_at<?').get(req.user.id, start, end).c;
    const pub = db.prepare('SELECT COUNT(*) AS c FROM publish_records WHERE user_id=? AND published_at>=? AND published_at<?').get(req.user.id, start, end).c;
    const okN = db.prepare("SELECT COUNT(*) AS c FROM publish_records WHERE user_id=? AND published_at>=? AND published_at<? AND status='success'").get(req.user.id, start, end).c;
    const failN = db.prepare(`SELECT COUNT(*) AS c FROM publish_records r
      JOIN publish_jobs j ON j.id=r.job_id
      WHERE r.user_id=? AND j.created_at>=? AND j.created_at<? AND r.status='failed'`).get(req.user.id, start, end).c;
    series.push({ day: label, created, published: pub, success: okN, failed: failN });
  }
  ok(res, { days, series });
});

router.post('/tasks/:id/retry', (req, res) => {
  const { retryTask } = require('../tasks');
  const r = retryTask(req.user.id, parseInt(req.params.id, 10));
  if (!r.ok) return fail(res, r.msg);
  ok(res, {});
});

router.get('/prices', (req, res) => {
  ok(res, prices());
});

module.exports = router;
