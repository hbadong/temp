import { test } from 'node:test';
import assert from 'node:assert/strict';
import { setupEnv, startApp, request, login } from './helper.mjs';

setupEnv();

test('前端全部列表页依赖的 API 端点可访问', async (t) => {
  const { server, base } = await startApp();
  t.after(() => server.close());
  const r = await login(base, 'chinaz51008', 'chinaz51008');

  const endpoints = [
    '/api/dashboard',
    '/api/prices',
    '/api/keywords', '/api/titles', '/api/image-groups?kind=1', '/api/knowledge',
    '/api/instructions', '/api/article-types', '/api/write-tasks', '/api/articles',
    '/api/tdk', '/api/video-jobs', '/api/video-records', '/api/video-assets',
    '/api/channels/b2b', '/api/channels/webmedia', '/api/channels/zimedia',
    '/api/publish/b2b/jobs', '/api/publish/b2b/records', '/api/contacts',
    '/api/publish/webmedia/jobs', '/api/publish/webmedia/records', '/api/media-favs',
    '/api/publish/zimedia/jobs', '/api/publish/zimedia/records',
    '/api/weixin/auth', '/api/publish/weixin/jobs', '/api/publish/weixin/records',
    '/api/sites', '/api/site-publish/records',
    '/api/stats?days=7', '/api/inclusion', '/api/ledger/score', '/api/ledger/money',
    '/api/entitlements', '/api/proxy', '/api/kyc', '/api/analyze',
  ];

  const failed = [];
  for (const ep of endpoints) {
    const res = await request(base, 'GET', ep, { jar: r.jar });
    if (res.json.code !== 1) failed.push(`${ep} -> ${res.json.msg}`);
  }
  assert.deepEqual(failed, [], `以下端点不可用: ${failed.join('; ')}`);
});

test('前端全部列表页依赖的写接口端点存在', async (t) => {
  const { server, base } = await startApp();
  t.after(() => server.close());
  const r = await login(base, 'chinaz51008', 'chinaz51008');

  const creates = [
    ['/api/keywords', { word: '映射' }],
    ['/api/titles', { title: '映射标题' }],
    ['/api/image-groups', { name: '映射组' }],
    ['/api/knowledge', { title: '映射知识' }],
    ['/api/instructions', { name: '映射指令' }],
    ['/api/article-types', { name: '映射分类' }],
    ['/api/tdk', { name: '映射TDK' }],
    ['/api/contacts', { name: '映射联系人' }],
    ['/api/sites', { name: '映射站点', domain: 'map.example.com' }],
    ['/api/proxy', { ip: '1.2.3.4', port: 8080 }],
    ['/api/media-favs', { name: '映射分组', channel_ids: [1] }],
  ];
  const failed = [];
  for (const [ep, body] of creates) {
    const res = await request(base, 'POST', ep, { jar: r.jar, body });
    if (res.json.code !== 1) failed.push(`${ep} -> ${res.json.msg}`);
  }
  assert.deepEqual(failed, [], `以下写接口不可用: ${failed.join('; ')}`);

  const formOnly = [
    ['/api/reprint', { source: 'https://ok.example/x' }],
    ['/api/reprint/batch', { text: 'https://ok.example/y' }],
  ];
  for (const [ep, body] of formOnly) {
    const res = await request(base, 'POST', ep, { jar: r.jar, body });
    assert.equal(res.json.code, 1, `${ep} 应可提交`);
  }
});
