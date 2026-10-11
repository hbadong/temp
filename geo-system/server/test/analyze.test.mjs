import { test } from 'node:test';
import assert from 'node:assert/strict';
import { setupEnv, startApp, request, login, loadModule } from './helper.mjs';

setupEnv();

const body = { brand: '确定性品牌', platforms: ['doubao', 'yuanbao', 'tongyi'], keyword: ['确定性行业'], is_jianyi: 1 };

test('同一品牌+行业+平台两次诊断报告得分一致', async (t) => {
  const { server, base } = await startApp();
  t.after(() => server.close());
  const r = await login(base, 'demo', 'demo123');
  const tasks = loadModule('tasks.js');

  const j1 = await request(base, 'POST', '/api/analyze', { jar: r.jar, body });
  const j2 = await request(base, 'POST', '/api/analyze', { jar: r.jar, body });
  assert.equal(j1.json.code, 1);
  assert.equal(j2.json.code, 1);

  await tasks.waitAll(15000);

  const d1 = await request(base, 'GET', `/api/analyze/${j1.json.data.id}`, { jar: r.jar });
  const d2 = await request(base, 'GET', `/api/analyze/${j2.json.data.id}`, { jar: r.jar });
  assert.equal(d1.json.data.task_status, 'done');
  assert.equal(d1.json.data.reports.length, 3);
  assert.deepEqual(
    d1.json.data.reports.map((x) => [x.platform, x.score, x.snippet]),
    d2.json.data.reports.map((x) => [x.platform, x.score, x.snippet])
  );
});

test('诊断余额差额等于标价（含建议 10200）', async (t) => {
  const { server, base } = await startApp();
  t.after(() => server.close());
  const r = await login(base, 'demo', 'demo123');
  const { db } = loadModule('db.js');
  const before = db.prepare('SELECT balance FROM users WHERE username=?').get('demo').balance;
  const res = await request(base, 'POST', '/api/analyze', { jar: r.jar, body });
  assert.equal(res.json.code, 1);
  const after = db.prepare('SELECT balance FROM users WHERE username=?').get('demo').balance;
  assert.equal(before - after, 10200);
});

test('行业词超过 3 个被拒绝', async (t) => {
  const { server, base } = await startApp();
  t.after(() => server.close());
  const r = await login(base, 'demo', 'demo123');
  const res = await request(base, 'POST', '/api/analyze', {
    jar: r.jar,
    body: { ...body, keyword: ['a', 'b', 'c', 'd'] },
  });
  assert.equal(res.json.code, 0);
  assert.match(res.json.msg, /3 个/);
});

test('Pro 品牌解析返回完整品牌信息', async (t) => {
  const { server, base } = await startApp();
  t.after(() => server.close());
  const r = await login(base, 'demo', 'demo123');
  const res = await request(base, 'POST', '/api/analyze/pro/parse', { jar: r.jar, body: { brand_name: '示例公司' } });
  assert.equal(res.json.code, 1);
  assert.ok(res.json.data.fullName.includes('示例公司'));
  assert.ok(res.json.data.industry);
});

test('Pro 诊断生成品牌+竞品的多实体报告', async (t) => {
  const { server, base } = await startApp();
  t.after(() => server.close());
  const r = await login(base, 'demo', 'demo123');
  const tasks = loadModule('tasks.js');
  const body = { brand_name: '确定性品牌', full_name: '确定性品牌有限公司', industry: '确定性行业', region: '中国', competitors: '竞争对手A, 竞争对手B；对手C' };
  const res = await request(base, 'POST', '/api/analyze/pro', { jar: r.jar, body });
  assert.equal(res.json.code, 1);
  await tasks.waitAll(15000);
  const d = await request(base, 'GET', `/api/analyze/${res.json.data.id}`, { jar: r.jar });
  const data = d.json.data;
  assert.equal(data.task_status, 'done');
  assert.deepEqual(data.competitors, ['竞争对手A', '竞争对手B', '对手C']);
  const entities = [...new Set(data.reports.map((x) => x.entity))];
  assert.deepEqual(entities.sort(), ['确定性品牌', '竞争对手A', '竞争对手B', '对手C'].sort());
  assert.equal(data.reports.length, 4 * data.platforms.length);
  const brandReports = data.reports.filter((x) => x.entity === '确定性品牌');
  const compReports = data.reports.filter((x) => x.entity === '竞争对手A');
  assert.ok(brandReports.every((x) => Array.isArray(x.advice) && x.advice.length > 0), '品牌本体报告应含优化建议');
  assert.ok(compReports.every((x) => !Array.isArray(x.advice) || x.advice.length === 0), '竞品报告不应含优化建议');
});
