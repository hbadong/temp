import { test } from 'node:test';
import assert from 'node:assert/strict';
import { setupEnv, startApp, request, login, loadModule } from './helper.mjs';

setupEnv();

test('渠道目录每类不少于 12 条且可收藏分组', async (t) => {
  const { server, base } = await startApp();
  t.after(() => server.close());
  const r = await login(base, 'chinaz51008', 'chinaz51008');
  for (const kind of ['b2b', 'webmedia', 'zimedia']) {
    const res = await request(base, 'GET', `/api/channels/${kind}`, { jar: r.jar });
    assert.equal(res.json.code, 1);
    assert.ok(res.json.data.list.length >= 12, `${kind} 渠道应 >= 12 条，实际 ${res.json.data.list.length}`);
  }
  const fav = await request(base, 'POST', '/api/media-favs', {
    jar: r.jar,
    body: { kind: 1, name: '常用媒体', channel_ids: [1, 2, 3] },
  });
  assert.equal(fav.json.code, 1);
  assert.equal(fav.json.data.channel_ids_json, JSON.stringify([1, 2, 3]));
});

test('B2B/网站媒体/自媒体发布均产出记录', async (t) => {
  const { server, base } = await startApp();
  t.after(() => server.close());
  const r = await login(base, 'chinaz51008', 'chinaz51008');
  const tasks = loadModule('tasks.js');
  const arts = await request(base, 'GET', '/api/articles?page=1', { jar: r.jar });
  const articleId = arts.json.data.list[0].id;

  for (const kind of ['b2b', 'webmedia', 'zimedia']) {
    const chans = await request(base, 'GET', `/api/channels/${kind}`, { jar: r.jar });
    const channelIds = chans.json.data.list.slice(0, 3).map((x) => x.id);
    const pub = await request(base, 'POST', `/api/publish/${kind}`, {
      jar: r.jar,
      body: { article_ids: [articleId], channel_ids: channelIds, contact_id: 1 },
    });
    assert.equal(pub.json.code, 1, `${kind} 发布创建失败`);
    await tasks.waitAll(15000);
    const recs = await request(base, 'GET', `/api/publish/${kind}/records`, { jar: r.jar });
    assert.ok(recs.json.data.total >= 3, `${kind} 应有至少 3 条记录`);
    const jobs = await request(base, 'GET', `/api/publish/${kind}/jobs`, { jar: r.jar });
    assert.ok(jobs.json.data.list.length >= 1);
  }
});

test('个人自媒体：未授权拒绝发布，授权后成功', async (t) => {
  const { server, base } = await startApp();
  t.after(() => server.close());
  const demo = await login(base, 'demo', 'demo123');
  const tasks = loadModule('tasks.js');

  const sub = await request(base, 'POST', '/api/kyc/personal', {
    jar: demo.jar,
    body: { real_name: '演示用户', id_number: '330000000000000000', mobile: '13800000000' },
  });
  assert.equal(sub.json.code, 1);

  const jar = {};
  await request(base, 'POST', '/api/admin/login', { jar, body: { username: 'admin', password: 'admin123' } });
  const list = await request(base, 'GET', '/api/admin/kyc', { jar });
  const rec = list.json.data.list.find((x) => x.user_id === demo.json.data.id);
  assert.ok(rec, 'KYC 记录应存在');
  await request(base, 'POST', `/api/admin/kyc/${rec.id}`, { jar, body: { action: 'approve' } });

  const wt = await request(base, 'POST', '/api/write-tasks', { jar: demo.jar, body: { keyword: '演示文章', count: 1 } });
  assert.equal(wt.json.code, 1);
  await tasks.waitAll(15000);
  const dArts = await request(base, 'GET', '/api/articles?page=1', { jar: demo.jar });
  const myArticleId = dArts.json.data.list[0].id;

  const denied = await request(base, 'POST', '/api/publish/weixin', {
    jar: demo.jar,
    body: { article_ids: [myArticleId], channel_ids: [1] },
  });
  assert.equal(denied.json.code, 0);
  assert.match(denied.json.msg, /授权已失效/);

  const auth = await request(base, 'POST', '/api/weixin/auth', { jar: demo.jar, body: { account_name: '个人号' } });
  assert.equal(auth.json.code, 1);
  const pub = await request(base, 'POST', '/api/publish/weixin', {
    jar: demo.jar,
    body: { article_ids: [myArticleId], channel_ids: [auth.json.data.id] },
  });
  assert.equal(pub.json.code, 1);
  await tasks.waitAll(15000);
  const recs = await request(base, 'GET', '/api/publish/weixin/records', { jar: demo.jar });
  assert.ok(recs.json.data.total >= 1);
});

test('发布校验：他人文章与他人类目号不可用', async (t) => {
  const { server, base } = await startApp();
  t.after(() => server.close());
  const r = await login(base, 'chinaz51008', 'chinaz51008');

  const junk = await request(base, 'POST', '/api/publish/b2b', {
    jar: r.jar,
    body: { article_ids: [1, 99999], channel_ids: [1, 2] },
  });
  assert.equal(junk.json.code, 0);
  assert.match(junk.json.msg, /存在无权操作的文章/);

  const wrongCh = await request(base, 'POST', '/api/publish/webmedia', {
    jar: r.jar,
    body: { article_ids: [1], channel_ids: [1] },
  });
  assert.equal(wrongCh.json.code, 0);
  assert.match(wrongCh.json.msg, /包含无效的目标渠道/);
});

test('官网 SEO 发布写入可访问 URL', async (t) => {
  const { server, base } = await startApp();
  t.after(() => server.close());
  const r = await login(base, 'chinaz51008', 'chinaz51008');
  const tasks = loadModule('tasks.js');
  const arts = await request(base, 'GET', '/api/articles?page=1', { jar: r.jar });
  const articleId = arts.json.data.list[0].id;
  const pub = await request(base, 'POST', '/api/site-publish', {
    jar: r.jar,
    body: { article_ids: [articleId], site_ids: [1] },
  });
  assert.equal(pub.json.code, 1);
  await tasks.waitAll(15000);
  const recs = await request(base, 'GET', '/api/site-publish/records', { jar: r.jar });
  assert.ok(recs.json.data.list.length >= 1);
  assert.match(recs.json.data.list[0].url, /^https:\/\//);
});

test('视频混剪与矩阵发布任务', async (t) => {
  const { server, base } = await startApp();
  t.after(() => server.close());
  const r = await login(base, 'chinaz51008', 'chinaz51008');
  const tasks = loadModule('tasks.js');

  const mix = await request(base, 'POST', '/api/video-mix', { jar: r.jar, body: { count: 2, tdk_id: 1 } });
  assert.equal(mix.json.code, 1);
  await tasks.waitAll(15000);
  const vjobs = await request(base, 'GET', '/api/video-jobs', { jar: r.jar });
  assert.ok(vjobs.json.data.list.some((x) => x.kind === 'mix' && x.status === 'done'));

  const pub = await request(base, 'POST', '/api/video-publish', { jar: r.jar, body: { count: 3 } });
  assert.equal(pub.json.code, 1);
  await tasks.waitAll(15000);
  const vrecs = await request(base, 'GET', '/api/video-records', { jar: r.jar });
  assert.ok(vrecs.json.data.list.length >= 3);
});
