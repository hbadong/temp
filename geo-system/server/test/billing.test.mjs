import { test } from 'node:test';
import assert from 'node:assert/strict';
import { setupEnv, startApp, request, login, loadModule } from './helper.mjs';

setupEnv();

test('并发诊断扣费不超扣，成功数与流水一致', async (t) => {
  const { server, base } = await startApp();
  t.after(() => server.close());
  const r = await login(base, 'demo', 'demo123');
  assert.equal(r.json.code, 1);

  const { db } = loadModule('db.js');
  const before = db.prepare('SELECT balance FROM users WHERE username=?').get('demo').balance;
  assert.equal(before, 50000);

  const jobs = await Promise.all(
    Array.from({ length: 6 }, () => request(base, 'POST', '/api/analyze', {
      jar: r.jar,
      body: { brand: '并发品牌', platforms: ['doubao', 'yuanbao'], keyword: ['并发行业'], is_jianyi: 0 },
    }))
  );
  const okCount = jobs.filter((x) => x.json.code === 1).length;
  const failCount = jobs.filter((x) => x.json.code === 0).length;
  assert.equal(okCount, 5);
  assert.equal(failCount, 1);
  assert.match(jobs.filter((x) => x.json.code === 0)[0].json.msg, /余额不足/);

  const after = db.prepare('SELECT balance FROM users WHERE username=?').get('demo').balance;
  assert.equal(after, 0);

  const ledger = db.prepare("SELECT COUNT(*) AS c FROM ledger_money WHERE user_id=? AND type='diagnose'").get(r.json.data.id).c;
  assert.equal(ledger, 5);
  const tasksN = db.prepare("SELECT COUNT(*) AS c FROM tasks WHERE user_id=? AND type='diagnose'").get(r.json.data.id).c;
  assert.equal(tasksN, 5);
});

test('点数扣费：指数查询与 AI 拓词各扣 10 点', async (t) => {
  const { server, base } = await startApp();
  t.after(() => server.close());
  const r = await login(base, 'demo', 'demo123');

  const idx = await request(base, 'POST', '/api/tools/index', { jar: r.jar, body: { keyword: '测试指数' } });
  assert.equal(idx.json.code, 1);
  assert.ok(Array.isArray(idx.json.data) && idx.json.data.length > 0);

  const dis = await request(base, 'POST', '/api/tools/distill', { jar: r.jar, body: { keyword: '测试拓词' } });
  assert.equal(dis.json.code, 1);
  assert.ok(Array.isArray(dis.json.data) && dis.json.data.length >= 5);

  const me = await request(base, 'GET', '/api/me', { jar: r.jar });
  assert.equal(me.json.data.score, 200 - 20);
});
