const express = require('express');
const { db } = require('../db');
const { ok, fail, now, paginate, readJson } = require('../lib');
const { prices, chargeMoney } = require('../billing');
const { enqueue } = require('../tasks');
const llm = require('../providers/llm');
const { requireUser } = require('../auth');

const router = express.Router();
router.use(requireUser);

const PLATFORM_IDS = Object.keys(llm.PLATFORMS);

router.post('/', (req, res) => {
  const { brand, platforms, keyword, is_jianyi } = req.body || {};
  const brandStr = String(brand || '').trim();
  const plats = Array.isArray(platforms) ? platforms.filter((p) => PLATFORM_IDS.includes(p)) : [];
  let industries = keyword;
  if (typeof industries === 'string') industries = industries.split(/[,，\n]/).map((s) => s.trim()).filter(Boolean);
  if (!Array.isArray(industries)) industries = [];
  industries = industries.map((s) => String(s).trim()).filter(Boolean).slice(0, 10);

  if (!plats.length) return fail(res, '请至少选择一个AI大模型平台');
  if (!brandStr) return fail(res, '请输入品牌');
  if (!industries.length) return fail(res, '请输入行业');
  if (industries.length > 3) return fail(res, '一次性最多支持 3 个词');

  const needAdvice = String(is_jianyi) === '1' || is_jianyi === 1 || is_jianyi === true;
  const price = needAdvice ? prices().diagnoseAdvice : prices().diagnose;

  let jobId;
  let taskId;
  const tx = db.transaction(() => {
    const billed = chargeMoney(req.user.id, price, { type: 'diagnose', remark: 'AI可见度诊断', refType: 'analyze' });
    if (!billed.ok) throw Object.assign(new Error(billed.msg), { biz: true });
    taskId = enqueue(req.user.id, 'diagnose', { brand: brandStr }, 1);
    jobId = db.prepare(`INSERT INTO analyze_jobs
      (user_id, task_id, brand, industry_json, platforms_json, kind, need_advice, price, status, created_at)
      VALUES (?,?,?,?,?,?,?,?,?,?)`)
      .run(req.user.id, taskId, brandStr, JSON.stringify(industries), JSON.stringify(plats),
        'standard', needAdvice ? 1 : 0, price, 'queued', now()).lastInsertRowid;
    db.prepare('UPDATE ledger_money SET ref_id=? WHERE user_id=? AND ref_type=? AND ref_id=0')
      .run(jobId, req.user.id, 'analyze');
  });
  try {
    tx();
  } catch (e) {
    return fail(res, e.biz ? e.message : '创建失败');
  }
  ok(res, { id: jobId, task_id: taskId }, '创建成功');
});

router.post('/pro/parse', (req, res) => {
  const brand_name = String((req.body || {}).brand_name || '').trim();
  if (!brand_name) return fail(res, '请输入品牌');
  const price = prices().proParse;
  try {
    db.transaction(() => {
      const billed = chargeMoney(req.user.id, price, { type: 'pro_parse', remark: 'Pro品牌解析', refType: 'analyze_pro_parse' });
      if (!billed.ok) throw Object.assign(new Error(billed.msg), { biz: true });
    })();
  } catch (e) {
    return fail(res, e.biz ? e.message : '解析失败');
  }
  ok(res, llm.mockBrandParse(brand_name), '分析成功');
});

router.post('/pro', (req, res) => {
  const b = req.body || {};
  const brand_name = String(b.brand_name || '').trim();
  const full_name = String(b.full_name || '').trim();
  const industry = String(b.industry || '').trim();
  const region = String(b.region || '').trim();
  const competitors = String(b.competitors || '').trim();
  if (!brand_name || !full_name || !industry || !region) return fail(res, '请完整填写品牌信息');
  const price = prices().proDiagnose;
  const plats = PLATFORM_IDS.slice();
  let jobId;
  let taskId;
  try {
    db.transaction(() => {
      const billed = chargeMoney(req.user.id, price, { type: 'diagnose_pro', remark: 'AI诊断Pro版', refType: 'analyze' });
      if (!billed.ok) throw Object.assign(new Error(billed.msg), { biz: true });
      taskId = enqueue(req.user.id, 'diagnose_pro', { brand: brand_name }, 1);
      jobId = db.prepare(`INSERT INTO analyze_jobs
        (user_id, task_id, brand, industry_json, platforms_json, kind, need_advice, full_name, region, competitors, price, status, created_at)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)`)
        .run(req.user.id, taskId, brand_name, JSON.stringify([industry]), JSON.stringify(plats),
          'pro', 1, full_name, region, competitors, price, 'queued', now()).lastInsertRowid;
    })();
  } catch (e) {
    return fail(res, e.biz ? e.message : '创建失败');
  }
  ok(res, { id: jobId, task_id: taskId }, '创建成功');
});

router.get('/', (req, res) => {
  const { page, pageSize, offset } = paginate(req.query);
  const total = db.prepare('SELECT COUNT(*) AS c FROM analyze_jobs WHERE user_id=?').get(req.user.id).c;
  const list = db.prepare('SELECT * FROM analyze_jobs WHERE user_id=? ORDER BY id DESC LIMIT ? OFFSET ?')
    .all(req.user.id, pageSize, offset)
    .map((j) => {
      const task = db.prepare('SELECT status, error_message FROM tasks WHERE id=?').get(j.task_id);
      return {
        ...j,
        industry: readJson(j.industry_json, []),
        platforms: readJson(j.platforms_json, []),
        task_status: task ? task.status : j.status,
      };
    });
  ok(res, { list, total, page, page_size: pageSize });
});

router.get('/:id', (req, res) => {
  const job = db.prepare('SELECT * FROM analyze_jobs WHERE id=? AND user_id=?').get(req.params.id, req.user.id);
  if (!job) return fail(res, '报告不存在');
  const reports = db.prepare('SELECT * FROM analyze_reports WHERE job_id=? ORDER BY id ASC').all(job.id)
    .map((r) => ({ ...r, advice: readJson(r.advice_json, []) }));
  const task = db.prepare('SELECT * FROM tasks WHERE id=?').get(job.task_id);
  ok(res, {
    ...job,
    industry: readJson(job.industry_json, []),
    platforms: readJson(job.platforms_json, []),
    competitors: String(job.competitors || '').split(/[,，;；]/).map((s) => s.trim()).filter(Boolean),
    reports,
    task_status: task ? task.status : job.status,
  });
});

module.exports = router;
