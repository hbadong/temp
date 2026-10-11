import { test } from 'node:test';
import assert from 'node:assert/strict';
import { setupEnv, startApp, request, login, loadModule } from './helper.mjs';

setupEnv();

test('关键词导入与蒸馏生成标题', async (t) => {
  const { server, base } = await startApp();
  t.after(() => server.close());
  const r = await login(base, 'chinaz51008', 'chinaz51008');
  const imp = await request(base, 'POST', '/api/keywords/import', { jar: r.jar, body: { text: '导入词A\n导入词B\n导入词C' } });
  assert.equal(imp.json.code, 1);
  assert.equal(imp.json.data.count, 3);
  const kws = await request(base, 'GET', '/api/keywords', { jar: r.jar });
  const imported = kws.json.data.list.filter((x) => x.word.startsWith('导入词'));
  assert.equal(imported.length, 3);
  const dis = await request(base, 'POST', '/api/titles/distill', {
    jar: r.jar,
    body: { keyword_ids: imported.map((x) => x.id) },
  });
  assert.equal(dis.json.code, 1);
  assert.ok(dis.json.data.count >= 3);
});

test('爆文复刻：正常源出文，坏源标记 failed，重试不扣费', async (t) => {
  const { server, base } = await startApp();
  t.after(() => server.close());
  const r = await login(base, 'chinaz51008', 'chinaz51008');
  const tasks = loadModule('tasks.js');
  const { db } = loadModule('db.js');
  const balBefore = db.prepare('SELECT balance FROM users WHERE username=?').get('chinaz51008').balance;

  const batch = await request(base, 'POST', '/api/reprint/batch', {
    jar: r.jar,
    body: { text: 'https://ok.example/a\nhttps://fail.invalid/b' },
  });
  assert.equal(batch.json.code, 1);
  await tasks.waitAll(15000);

  const arts = await request(base, 'GET', '/api/articles?page=1', { jar: r.jar });
  const reprintArts = arts.json.data.list.filter((x) => x.source === 'reprint');
  assert.equal(reprintArts.length, 3);

  const batchTask = db.prepare("SELECT * FROM tasks WHERE user_id=? AND type='reprint' AND status='failed' ORDER BY id DESC LIMIT 1").get(1);
  assert.ok(batchTask, '坏源任务应 failed');

  const retry = await request(base, 'POST', `/api/tasks/${batchTask.id}/retry`, { jar: r.jar });
  assert.equal(retry.json.code, 1);
  await tasks.waitAll(15000);
  const balAfter = db.prepare('SELECT balance FROM users WHERE username=?').get('chinaz51008').balance;
  assert.equal(balBefore, balAfter, '复刻任务不应扣费');
  const again = db.prepare('SELECT status FROM tasks WHERE id=?').get(batchTask.id).status;
  assert.equal(again, 'failed', '坏源重试后仍失败');
});

test('写作任务扣费与出文数量', async (t) => {
  const { server, base } = await startApp();
  t.after(() => server.close());
  const r = await login(base, 'chinaz51008', 'chinaz51008');
  const { db } = loadModule('db.js');
  const tasks = loadModule('tasks.js');
  const before = db.prepare('SELECT balance FROM users WHERE username=?').get('chinaz51008').balance;
  const wt = await request(base, 'POST', '/api/write-tasks', {
    jar: r.jar,
    body: { keyword: '扣费验证词', count: 3, instruction_id: 1, type_id: 1 },
  });
  assert.equal(wt.json.code, 1);
  await tasks.waitAll(15000);
  const after = db.prepare('SELECT balance FROM users WHERE username=?').get('chinaz51008').balance;
  assert.equal(before - after, 200 * 3);
});
