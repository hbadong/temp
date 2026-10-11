import assert from 'node:assert/strict';
import { setupEnv, startApp, request, login, loadModule } from './helper.mjs';

const log = (...a) => console.log('[smoke]', ...a);

async function main() {
  setupEnv();
  const { server, base } = await startApp();
  const tasks = loadModule('tasks.js');

  const user = await login(base, 'chinaz51008', 'chinaz51008');
  assert.equal(user.json.code, 1, '种子用户登录失败');
  log('登录成功');

  const dash = await request(base, 'GET', '/api/dashboard', { jar: user.jar });
  assert.equal(dash.json.code, 1);
  log('仪表盘数据 ok');

  const kw = await request(base, 'POST', '/api/keywords', { jar: user.jar, body: { word: '冒烟关键词' } });
  assert.equal(kw.json.code, 1);
  const dis = await request(base, 'POST', '/api/titles/distill', { jar: user.jar, body: { keyword_ids: [kw.json.data.id] } });
  assert.equal(dis.json.code, 1);
  assert.ok(dis.json.data.count >= 1);
  log(`蒸馏标题 ${dis.json.data.count} 条`);

  const dg = await request(base, 'POST', '/api/analyze', {
    jar: user.jar,
    body: { brand: '冒烟品牌', platforms: ['doubao', 'yuanbao'], keyword: ['冒烟行业'], is_jianyi: 0 },
  });
  assert.equal(dg.json.code, 1, '诊断创建失败');
  await tasks.waitAll(15000);
  const rep = await request(base, 'GET', `/api/analyze/${dg.json.data.id}`, { jar: user.jar });
  assert.equal(rep.json.data.task_status, 'done');
  assert.ok(rep.json.data.reports.length === 2);
  log(`诊断报告 ok（2 平台，首平台得分 ${rep.json.data.reports[0].score}）`);

  const idx = await request(base, 'POST', '/api/tools/index', { jar: user.jar, body: { keyword: '指数词' } });
  assert.equal(idx.json.code, 1);
  const dis2 = await request(base, 'POST', '/api/tools/distill', { jar: user.jar, body: { keyword: '拓词' } });
  assert.equal(dis2.json.code, 1);
  log('指数 / 拓词扣点 ok');

  const artsBefore = await request(base, 'GET', '/api/articles?page=1', { jar: user.jar });
  const wt = await request(base, 'POST', '/api/write-tasks', {
    jar: user.jar,
    body: { keyword: '冒烟写作', count: 2, instruction_id: 1 },
  });
  assert.equal(wt.json.code, 1);
  await tasks.waitAll(15000);
  const arts = await request(base, 'GET', '/api/articles?page=1', { jar: user.jar });
  assert.equal(arts.json.data.total, artsBefore.json.data.total + 2);
  log(`写作任务完成，新增文章 2 篇`);

  const articleId = arts.json.data.list[0].id;
  const pub = await request(base, 'POST', '/api/publish/b2b', {
    jar: user.jar,
    body: { article_ids: [articleId], channel_ids: [1], contact_id: 1 },
  });
  assert.equal(pub.json.code, 1);
  await tasks.waitAll(15000);
  const recs = await request(base, 'GET', '/api/publish/b2b/records', { jar: user.jar });
  assert.ok(recs.json.data.total >= 1);
  log(`B2B 发布记录 ${recs.json.data.total} 条`);

  const stats = await request(base, 'GET', '/api/stats?days=7', { jar: user.jar });
  assert.equal(stats.json.code, 1);
  log('数据报表 ok');

  const demo = await login(base, 'demo', 'demo123');
  const gated = await request(base, 'POST', '/api/write-tasks', { jar: demo.jar, body: { keyword: 'x', count: 1 } });
  assert.equal(gated.json.code, 0, '未实名用户应被 KYC 门禁拦截');
  log('KYC 门禁 ok');

  const kyc = await request(base, 'POST', '/api/kyc/personal', {
    jar: demo.jar,
    body: { real_name: '张三', id_number: '110101199001011234', mobile: '13800001111' },
  });
  assert.equal(kyc.json.code, 1);

  const adminJar = {};
  const adminLogin = await request(base, 'POST', '/api/admin/login', { jar: adminJar, body: { username: 'admin', password: 'admin123' } });
  assert.equal(adminLogin.json.code, 1, '管理端登录失败');
  const kycList = await request(base, 'GET', '/api/admin/kyc', { jar: adminJar });
  const pending = kycList.json.data.list.find((x) => x.user_id === demo.json.data.id);
  assert.ok(pending, '未找到待审实名记录');
  const approve = await request(base, 'POST', `/api/admin/kyc/${pending.id}`, { jar: adminJar, body: { action: 'approve' } });
  assert.equal(approve.json.code, 1);
  const me = await request(base, 'GET', '/api/me', { jar: demo.jar });
  assert.equal(me.json.data.kyc_status, 'approved');
  log('实名审核闭环 ok');

  const adj = await request(base, 'POST', '/api/admin/adjust', {
    jar: adminJar,
    body: { username: 'demo', kind: 'money', amount: 1000, remark: '冒烟调账' },
  });
  assert.equal(adj.json.code, 1);
  assert.equal(adj.json.data.balance, 51000);
  log('管理端调账 ok');

  await request(base, 'POST', '/api/auth/logout', { jar: user.jar });
  await request(base, 'POST', '/api/admin/logout', { jar: adminJar });
  log('ALL PASS');
  server.close();
  process.exit(0);
}

main().catch((e) => {
  console.error('[smoke] FAILED:', e.message);
  process.exit(1);
});
