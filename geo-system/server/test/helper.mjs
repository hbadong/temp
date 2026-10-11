import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { createRequire } from 'node:module';
import { fileURLToPath } from 'node:url';

const here = path.dirname(fileURLToPath(import.meta.url));

export function setupEnv() {
  const dir = fs.mkdtempSync(path.join(os.tmpdir(), 'geo-test-'));
  process.env.DATA_DIR = dir;
  process.env.LLM_PROVIDER = 'mock';
  return dir;
}

const require = createRequire(import.meta.url);

export function loadServer() {
  return require(path.join(here, '..', 'src', 'index.js'));
}

export function loadModule(name) {
  return require(path.join(here, '..', 'src', name));
}

export function parseSetCookie(headers) {
  const raw = headers.getSetCookie ? headers.getSetCookie() : [];
  const jar = {};
  raw.forEach((line) => {
    const part = String(line).split(';')[0];
    const i = part.indexOf('=');
    if (i > 0) jar[part.slice(0, i).trim()] = decodeURIComponent(part.slice(i + 1));
  });
  return jar;
}

export function cookieHeader(jar) {
  return Object.entries(jar).map(([k, v]) => `${k}=${encodeURIComponent(v)}`).join('; ');
}

export async function startApp() {
  const { app } = loadServer();
  const server = await new Promise((resolve) => {
    const s = app.listen(0, '127.0.0.1', () => resolve(s));
  });
  const { port } = server.address();
  const base = `http://127.0.0.1:${port}`;
  return { server, base };
}

export async function request(base, method, url, { body, jar } = {}) {
  const headers = {};
  if (jar) headers.Cookie = cookieHeader(jar);
  if (body !== undefined) headers['Content-Type'] = 'application/json';
  const res = await fetch(base + url, {
    method,
    headers,
    body: body !== undefined ? JSON.stringify(body) : undefined,
  });
  const set = parseSetCookie(res.headers);
  if (jar) Object.assign(jar, set);
  const json = await res.json().catch(() => ({ code: 0, msg: 'non-json', data: {} }));
  return { status: res.status, json, jar: jar || set };
}

export async function login(base, username = 'chinaz51008', password = 'chinaz51008', keeplogin = false) {
  const jar = {};
  const r = await request(base, 'POST', '/api/auth/login', { body: { username, password, keeplogin }, jar });
  return { ...r, jar };
}

export function wait(ms) {
  return new Promise((r) => setTimeout(r, ms));
}
