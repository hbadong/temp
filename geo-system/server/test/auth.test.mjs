import { test } from 'node:test';
import assert from 'node:assert/strict';
import { setupEnv, startApp, request, login, cookieHeader } from './helper.mjs';

setupEnv();

test('错误密码登录失败', async (t) => {
  const { server, base } = await startApp();
  t.after(() => server.close());
  const r = await request(base, 'POST', '/api/auth/login', { body: { username: 'chinaz51008', password: 'wrong' } });
  assert.equal(r.status, 200);
  assert.equal(r.json.code, 0);
  assert.match(r.json.msg, /账号或密码错误/);
});

test('正确密码登录并读取 /api/me', async (t) => {
  const { server, base } = await startApp();
  t.after(() => server.close());
  const r = await login(base);
  assert.equal(r.json.code, 1);
  assert.equal(r.json.data.username, 'chinaz51008');
  const me = await request(base, 'GET', '/api/me', { jar: r.jar });
  assert.equal(me.status, 200);
  assert.equal(me.json.data.username, 'chinaz51008');
});

test('未登录业务接口返回 401', async (t) => {
  const { server, base } = await startApp();
  t.after(() => server.close());
  const r = await request(base, 'GET', '/api/me');
  assert.equal(r.status, 401);
});

test('keeplogin 仅凭 geo_keep 可恢复会话', async (t) => {
  const { server, base } = await startApp();
  t.after(() => server.close());
  const r = await login(base, 'chinaz51008', 'chinaz51008', true);
  assert.ok(r.jar.geo_sid);
  assert.ok(r.jar.geo_keep);
  const keepOnly = { geo_keep: r.jar.geo_keep };
  const me = await request(base, 'GET', '/api/me', { jar: keepOnly });
  assert.equal(me.json.code, 1);
  assert.equal(me.json.data.username, 'chinaz51008');
  assert.ok(keepOnly.geo_sid);
});

test('改密后旧密码失效、新密码可登录', async (t) => {
  const { server, base } = await startApp();
  t.after(() => server.close());
  const r = await login(base, 'demo', 'demo123');
  const ch = await request(base, 'PUT', '/api/me/password', {
    jar: r.jar,
    body: { old_password: 'demo123', new_password: 'demo456' },
  });
  assert.equal(ch.json.code, 1);
  const oldLogin = await request(base, 'POST', '/api/auth/login', { body: { username: 'demo', password: 'demo123' } });
  assert.equal(oldLogin.json.code, 0);
  const neu = await request(base, 'POST', '/api/auth/login', { body: { username: 'demo', password: 'demo456' } });
  assert.equal(neu.json.code, 1);
});

void cookieHeader;
