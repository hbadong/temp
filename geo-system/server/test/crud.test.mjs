import { test } from 'node:test';
import assert from 'node:assert/strict';
import { setupEnv, startApp, request, login } from './helper.mjs';

setupEnv();

test('素材域 CRUD 全流程', async (t) => {
  const { server, base } = await startApp();
  t.after(() => server.close());
  const r = await login(base, 'chinaz51008', 'chinaz51008');

  const kw = await request(base, 'POST', '/api/keywords', { jar: r.jar, body: { word: '深入测试关键词' } });
  assert.equal(kw.json.code, 1);
  const kwId = kw.json.data.id;

  const title = await request(base, 'POST', '/api/titles', { jar: r.jar, body: { keyword_id: kwId, title: '深入测试标题' } });
  assert.equal(title.json.code, 1);

  const inst = await request(base, 'POST', '/api/instructions', { jar: r.jar, body: { name: '指令A', body: '正文提示' } });
  assert.equal(inst.json.code, 1);

  const at = await request(base, 'POST', '/api/article-types', { jar: r.jar, body: { name: '分类A' } });
  assert.equal(at.json.code, 1);

  const kn = await request(base, 'POST', '/api/knowledge', { jar: r.jar, body: { title: '知识A', body: '内容' } });
  assert.equal(kn.json.code, 1);

  const arts = await request(base, 'GET', '/api/articles?page=1', { jar: r.jar });
  const artId = arts.json.data.list[0].id;

  const upd = await request(base, 'PUT', `/api/articles/${artId}`, { jar: r.jar, body: { title: '文章标题改' } });
  assert.equal(upd.json.code, 1);
  assert.equal(upd.json.data.title, '文章标题改');

  const list = await request(base, 'GET', '/api/keywords?q=' + encodeURIComponent('深入'), { jar: r.jar });
  assert.ok(list.json.data.list.some((x) => x.id === kwId));

  const del = await request(base, 'DELETE', `/api/titles/${title.json.data.id}`, { jar: r.jar });
  assert.equal(del.json.code, 1);

  const page = await request(base, 'GET', '/api/articles?page=2&page_size=100', { jar: r.jar });
  assert.equal(page.json.code, 1);
  assert.ok(Number.isInteger(page.json.data.total));
});

test('跨用户资源隔离：demo 无法改删他人资源', async (t) => {
  const { server, base } = await startApp();
  t.after(() => server.close());
  const demo = await login(base, 'demo', 'demo123');
  const put = await request(base, 'PUT', '/api/keywords/1', { jar: demo.jar, body: { word: '篡改' } });
  assert.equal(put.json.code, 0);
  const del = await request(base, 'DELETE', '/api/knowledge/1', { jar: demo.jar });
  assert.equal(del.json.code, 0);
  const own = await request(base, 'GET', '/api/keywords', { jar: demo.jar });
  assert.equal(own.json.data.list.filter((x) => x.word === '篡改').length, 0);
});

test('图片分组与删除', async (t) => {
  const { server, base } = await startApp();
  t.after(() => server.close());
  const r = await login(base, 'chinaz51008', 'chinaz51008');
  const g = await request(base, 'POST', '/api/image-groups', { jar: r.jar, body: { name: '测试组', kind: 1 } });
  assert.equal(g.json.code, 1);
  const gid = g.json.data.id;
  const list = await request(base, 'GET', '/api/image-groups?kind=1', { jar: r.jar });
  assert.ok(list.json.data.list.some((x) => x.id === gid));
  const del = await request(base, 'DELETE', `/api/image-groups/${gid}`, { jar: r.jar });
  assert.equal(del.json.code, 1);
  const after = await request(base, 'GET', '/api/image-groups?kind=1', { jar: r.jar });
  assert.equal(after.json.data.list.some((x) => x.id === gid), false);
});
