import { test } from 'node:test';
import assert from 'node:assert/strict';
import { setupEnv, startApp, request, login, loadModule } from './helper.mjs';

setupEnv();

test('仪表盘与流水与价格接口', async (t) => {
  const { server, base } = await startApp();
  t.after(() => server.close());
  const r = await login(base, 'chinaz51008', 'chinaz51008');
  const dash = await request(base, 'GET', '/api/dashboard', { jar: r.jar });
  assert.equal(dash.json.code, 1);
  assert.ok(dash.json.data.days.length === 5);
  assert.ok(Array.isArray(dash.json.data.write_series));
  assert.ok(Array.isArray(dash.json.data.pub_series));

  const lm = await request(base, 'GET', '/api/ledger/money', { jar: r.jar });
  assert.equal(lm.json.code, 1);
  const ls = await request(base, 'GET', '/api/ledger/score', { jar: r.jar });
  assert.equal(ls.json.code, 1);

  const prices = await request(base, 'GET', '/api/prices', { jar: r.jar });
  assert.equal(prices.json.data.diagnose, 10000);
  assert.equal(prices.json.data.write, 200);
});

test('数据报表 7/30 天与收录查询', async (t) => {
  const { server, base } = await startApp();
  t.after(() => server.close());
  const r = await login(base, 'chinaz51008', 'chinaz51008');
  const tasks = loadModule('tasks.js');

  const s7 = await request(base, 'GET', '/api/stats?days=7', { jar: r.jar });
  assert.equal(s7.json.code, 1);
  assert.equal(s7.json.data.series.length, 7);
  const s30 = await request(base, 'GET', '/api/stats?days=30', { jar: r.jar });
  assert.equal(s30.json.data.series.length, 30);

  const inc = await request(base, 'POST', '/api/inclusion', { jar: r.jar, body: { query: '测试收录词', target: 'www.example.com' } });
  assert.equal(inc.json.code, 1);
  await tasks.waitAll(15000);
  const recs = await request(base, 'GET', '/api/inclusion', { jar: r.jar });
  assert.ok(recs.json.data.list.some((x) => x.query === '测试收录词'));
});

test('账号权益（需实名）', async (t) => {
  const { server, base } = await startApp();
  t.after(() => server.close());
  const r = await login(base, 'chinaz51008', 'chinaz51008');
  const ent = await request(base, 'GET', '/api/entitlements', { jar: r.jar });
  assert.equal(ent.json.code, 1);
  assert.equal(ent.json.data.plan, '旗舰版');
  assert.ok(ent.json.data.platforms.length >= 6);
});

test('重试 billed=1 失败任务不二次扣费', async (t) => {
  const { server, base } = await startApp();
  t.after(() => server.close());
  const r = await login(base, 'chinaz51008', 'chinaz51008');
  const { db } = loadModule('db.js');
  const tasks = loadModule('tasks.js');
  const now = Math.floor(Date.now() / 1000);

  db.prepare(`INSERT INTO tasks (user_id, type, status, payload_json, billed, created_at)
    VALUES (?, 'write', 'failed', ?, 1, ?)`)
    .run(1, JSON.stringify({ keyword: '重试词', count: 1 }), now);
  const taskId = db.prepare('SELECT id FROM tasks WHERE type=? ORDER BY id DESC LIMIT 1').get('write').id;
  const before = db.prepare('SELECT balance FROM users WHERE id=?').get(1).balance;

  const retry = await request(base, 'POST', `/api/tasks/${taskId}/retry`, { jar: r.jar });
  assert.equal(retry.json.code, 1);
  await tasks.waitAll(15000);
  const after = db.prepare('SELECT balance FROM users WHERE id=?').get(1).balance;
  assert.equal(before, after, '重试不应二次扣费');
  const task = db.prepare('SELECT * FROM tasks WHERE id=?').get(taskId);
  assert.equal(task.status, 'done');
  assert.equal(task.billed, 1);
});

test('套餐到期后写作/发布配额视为 0', async (t) => {
  const { server, base } = await startApp();
  t.after(() => server.close());
  const r = await login(base, 'chinaz51008', 'chinaz51008');
  const { db } = loadModule('db.js');
  const past = Math.floor(Date.now() / 1000) - 100;
  db.prepare('UPDATE users SET entitlement_expire=? WHERE id=1').run(past);

  const wt = await request(base, 'POST', '/api/write-tasks', { jar: r.jar, body: { keyword: '到期测试', count: 1 } });
  assert.equal(wt.json.code, 0);
  assert.match(wt.json.msg, /套餐已到期/);

  const arts = await request(base, 'GET', '/api/articles?page=1', { jar: r.jar });
  const pub = await request(base, 'POST', '/api/publish/b2b', {
    jar: r.jar,
    body: { article_ids: [arts.json.data.list[0].id], channel_ids: [1] },
  });
  assert.equal(pub.json.code, 0);
  assert.match(pub.json.msg, /套餐已到期/);
});

test('写作日配额用尽后拒绝创建', async (t) => {
  const { server, base } = await startApp();
  t.after(() => server.close());
  const r = await login(base, 'chinaz51008', 'chinaz51008');
  const { db } = loadModule('db.js');
  const future = Math.floor(Date.now() / 1000) + 86400;
  db.prepare('UPDATE users SET entitlement_expire=? WHERE id=1').run(future);
  const used = db.prepare("SELECT COUNT(*) AS c FROM tasks WHERE user_id=1 AND type='write'").get().c;
  db.prepare('UPDATE users SET daily_write_quota=? WHERE id=1').run(used + 1);

  const ok1 = await request(base, 'POST', '/api/write-tasks', { jar: r.jar, body: { keyword: '配额词1', count: 1 } });
  assert.equal(ok1.json.code, 1);
  const over = await request(base, 'POST', '/api/write-tasks', { jar: r.jar, body: { keyword: '配额词2', count: 1 } });
  assert.equal(over.json.code, 0);
  assert.match(over.json.msg, /配额已用完/);
});
