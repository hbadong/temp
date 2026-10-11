import { test } from 'node:test';
import assert from 'node:assert/strict';
import { setupEnv, startApp, request, login } from './helper.mjs';

setupEnv();

test('管理端改价后 /api/prices 同步生效', async (t) => {
  const { server, base } = await startApp();
  t.after(() => server.close());
  const jar = {};
  const al = await request(base, 'POST', '/api/admin/login', { jar, body: { username: 'admin', password: 'admin123' } });
  assert.equal(al.json.code, 1);
  const put = await request(base, 'PUT', '/api/admin/settings', { jar, body: { diagnose_price: '12345', write_price: '300' } });
  assert.equal(put.json.code, 1);
  const user = await login(base, 'chinaz51008', 'chinaz51008');
  const prices = await request(base, 'GET', '/api/prices', { jar: user.jar });
  assert.equal(prices.json.data.diagnose, 12345);
  assert.equal(prices.json.data.write, 300);
});

test('KYC 驳回后用户状态 rejected 且仍被门禁', async (t) => {
  const { server, base } = await startApp();
  t.after(() => server.close());
  const demo = await login(base, 'demo', 'demo123');
  const kyc = await request(base, 'POST', '/api/kyc/company', {
    jar: demo.jar,
    body: { company_name: '测试公司', license_no: 'L123', license_photo: 'http://x/1.png', admin_name: '李四', admin_id_number: '110101199001011234', admin_id_photo: 'http://x/2.png' },
  });
  assert.equal(kyc.json.code, 1);

  const jar = {};
  const al = await request(base, 'POST', '/api/admin/login', { jar, body: { username: 'admin', password: 'admin123' } });
  const list = await request(base, 'GET', '/api/admin/kyc', { jar });
  const rec = list.json.data.list.find((x) => x.user_id === demo.json.data.id && x.kind === 'company');
  const rej = await request(base, 'POST', `/api/admin/kyc/${rec.id}`, { jar, body: { action: 'reject', reason: '执照不清晰' } });
  assert.equal(rej.json.code, 1);

  const me = await request(base, 'GET', '/api/me', { jar: demo.jar });
  assert.equal(me.json.data.kyc_status, 'rejected');
  assert.equal(me.json.data.kyc_reject_reason, '执照不清晰');
  const gated = await request(base, 'POST', '/api/write-tasks', { jar: demo.jar, body: { keyword: 'x', count: 1 } });
  assert.equal(gated.json.code, 0, '驳回后仍应被门禁');
});

test('管理端任务监控与用户列表', async (t) => {
  const { server, base } = await startApp();
  t.after(() => server.close());
  const jar = {};
  await request(base, 'POST', '/api/admin/login', { jar, body: { username: 'admin', password: 'admin123' } });
  const tasks = await request(base, 'GET', '/api/admin/tasks?status=done', { jar });
  assert.equal(tasks.json.code, 1);
  assert.ok(Array.isArray(tasks.json.data.list));
  const users = await request(base, 'GET', '/api/admin/users', { jar });
  assert.equal(users.json.code, 1);
  assert.ok(users.json.data.list.length >= 2);
});

test('管理端调账写 operator 与流水', async (t) => {
  const { server, base } = await startApp();
  t.after(() => server.close());
  const jar = {};
  await request(base, 'POST', '/api/admin/login', { jar, body: { username: 'admin', password: 'admin123' } });
  const adj = await request(base, 'POST', '/api/admin/adjust', { jar, body: { username: 'demo', kind: 'score', amount: 500, remark: '测试赠点' } });
  assert.equal(adj.json.code, 1);
  assert.equal(adj.json.data.score, 700);
  const demoJar = {};
  await request(base, 'POST', '/api/auth/login', { jar: demoJar, body: { username: 'demo', password: 'demo123' } });
  const demoLed = await request(base, 'GET', '/api/ledger/score', { jar: demoJar });
  const row = demoLed.json.data.list.find((x) => x.type === 'adjust');
  assert.ok(row, '应存在调账流水');
  assert.equal(row.operator, 'admin');
  assert.equal(row.delta, 500);
});
