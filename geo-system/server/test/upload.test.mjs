import { test } from 'node:test';
import assert from 'node:assert/strict';
import { setupEnv, startApp, request, login, parseSetCookie, cookieHeader } from './helper.mjs';

setupEnv();

async function rawFetch(base, url, { method = 'GET', jar, body, headers = {} } = {}) {
  const h = { ...headers };
  if (jar && Object.keys(jar).length) h.Cookie = cookieHeader(jar);
  const res = await fetch(base + url, { method, headers: h, body });
  const set = parseSetCookie(res.headers);
  if (jar) Object.assign(jar, set);
  return { status: res.status, res, set };
}

test('图片/视频素材上传：类型校验、入库与越权下载', async (t) => {
  const { server, base } = await startApp();
  t.after(() => server.close());
  const chinaz = await login(base, 'chinaz51008', 'chinaz51008');
  const demo = await login(base, 'demo', 'demo123');

  const ok = await rawFetch(base, '/api/uploads', {
    method: 'POST',
    jar: chinaz.jar,
    headers: { 'Content-Type': 'image/jpeg', 'x-filename': 'demo.jpg', 'x-group-id': '1', 'x-kind': 'image' },
    body: Buffer.from('fake-jpeg-bytes'),
  });
  assert.equal(ok.status, 200);
  const okJson = await ok.res.json();
  assert.equal(okJson.code, 1);
  assert.match(okJson.data.path, /^\/api\/files\/uploads\/1\//);

  const bad = await rawFetch(base, '/api/uploads', {
    method: 'POST',
    jar: chinaz.jar,
    headers: { 'Content-Type': 'application/octet-stream', 'x-filename': 'evil.exe' },
    body: Buffer.from('MZxxxx'),
  });
  const badJson = await bad.res.json();
  assert.equal(badJson.code, 0);
  assert.match(badJson.msg, /不支持的文件类型/);

  const own = await rawFetch(base, okJson.data.path, { jar: chinaz.jar });
  assert.equal(own.status, 200);

  const other = await rawFetch(base, okJson.data.path, { jar: demo.jar });
  assert.equal(other.status, 403, '他人文件应 403');
});

test('视频素材上传入库 video-assets', async (t) => {
  const { server, base } = await startApp();
  t.after(() => server.close());
  const chinaz = await login(base, 'chinaz51008', 'chinaz51008');
  const up = await rawFetch(base, '/api/uploads', {
    method: 'POST',
    jar: chinaz.jar,
    headers: { 'Content-Type': 'video/mp4', 'x-filename': 'clip.mp4', 'x-group-id': '0', 'x-kind': 'video' },
    body: Buffer.from('fake-mp4'),
  });
  const j = await up.res.json();
  assert.equal(j.code, 1);
  const list = await request(base, 'GET', '/api/video-assets', { jar: chinaz.jar });
  assert.ok(list.json.data.list.some((x) => x.kind === 'video'));
});
