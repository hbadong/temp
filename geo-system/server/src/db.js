const path = require('path');
const fs = require('fs');
const { DatabaseSync } = require('node:sqlite');
const { hashPassword, now } = require('./lib');

const DATA_DIR = process.env.DATA_DIR || path.join(__dirname, '..', 'data');
fs.mkdirSync(DATA_DIR, { recursive: true });
fs.mkdirSync(path.join(DATA_DIR, 'uploads'), { recursive: true });

const db = new DatabaseSync(path.join(DATA_DIR, 'geo.db'));
db.exec('PRAGMA journal_mode = WAL');
db.exec('PRAGMA foreign_keys = ON');
db.exec('PRAGMA busy_timeout = 5000');

db.transaction = (fn) => (...args) => {
  db.exec('BEGIN');
  try {
    const result = fn(...args);
    db.exec('COMMIT');
    return result;
  } catch (e) {
    db.exec('ROLLBACK');
    throw e;
  }
};

db.exec(`
CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  username TEXT UNIQUE NOT NULL,
  password_hash TEXT NOT NULL,
  nickname TEXT DEFAULT '',
  balance INTEGER NOT NULL DEFAULT 0,
  score INTEGER NOT NULL DEFAULT 0,
  kyc_status TEXT NOT NULL DEFAULT 'none',
  kyc_reject_reason TEXT DEFAULT '',
  entitlement_plan TEXT DEFAULT '体验版',
  entitlement_expire INTEGER NOT NULL DEFAULT 0,
  daily_write_quota INTEGER NOT NULL DEFAULT 100,
  daily_pub_quota INTEGER NOT NULL DEFAULT 100,
  created_at INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS sessions (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  token TEXT UNIQUE NOT NULL,
  expire_at INTEGER NOT NULL,
  created_at INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS keep_tokens (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  token TEXT UNIQUE NOT NULL,
  expire_at INTEGER NOT NULL,
  created_at INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS admin_users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  username TEXT UNIQUE NOT NULL,
  password_hash TEXT NOT NULL,
  created_at INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS admin_sessions (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  admin_id INTEGER NOT NULL,
  token TEXT UNIQUE NOT NULL,
  expire_at INTEGER NOT NULL,
  created_at INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS kyc_records (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  kind TEXT NOT NULL,
  real_name TEXT DEFAULT '',
  id_number TEXT DEFAULT '',
  mobile TEXT DEFAULT '',
  company_name TEXT DEFAULT '',
  license_no TEXT DEFAULT '',
  license_photo TEXT DEFAULT '',
  admin_name TEXT DEFAULT '',
  admin_id_number TEXT DEFAULT '',
  admin_id_photo TEXT DEFAULT '',
  status TEXT NOT NULL DEFAULT 'pending',
  reject_reason TEXT DEFAULT '',
  created_at INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS ledger_money (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  delta INTEGER NOT NULL,
  balance_after INTEGER NOT NULL,
  type TEXT NOT NULL,
  remark TEXT DEFAULT '',
  ref_type TEXT DEFAULT '',
  ref_id INTEGER DEFAULT 0,
  operator TEXT DEFAULT 'self',
  created_at INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS ledger_score (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  delta INTEGER NOT NULL,
  balance_after INTEGER NOT NULL,
  type TEXT NOT NULL,
  remark TEXT DEFAULT '',
  ref_type TEXT DEFAULT '',
  ref_id INTEGER DEFAULT 0,
  operator TEXT DEFAULT 'self',
  created_at INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS settings (
  key TEXT PRIMARY KEY,
  value TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS tasks (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  type TEXT NOT NULL,
  status TEXT NOT NULL DEFAULT 'queued',
  payload_json TEXT DEFAULT '{}',
  result_json TEXT DEFAULT '',
  error_message TEXT DEFAULT '',
  billed INTEGER NOT NULL DEFAULT 0,
  created_at INTEGER NOT NULL,
  started_at INTEGER DEFAULT 0,
  finished_at INTEGER DEFAULT 0
);
CREATE INDEX IF NOT EXISTS idx_tasks_status ON tasks(status, id);

CREATE TABLE IF NOT EXISTS analyze_jobs (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  task_id INTEGER NOT NULL,
  brand TEXT NOT NULL,
  industry_json TEXT NOT NULL,
  platforms_json TEXT NOT NULL,
  kind TEXT NOT NULL DEFAULT 'standard',
  need_advice INTEGER NOT NULL DEFAULT 0,
  full_name TEXT DEFAULT '',
  region TEXT DEFAULT '',
  competitors TEXT DEFAULT '',
  price INTEGER NOT NULL DEFAULT 0,
  status TEXT NOT NULL DEFAULT 'queued',
  created_at INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS analyze_reports (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  job_id INTEGER NOT NULL,
  user_id INTEGER NOT NULL,
  entity TEXT DEFAULT '',
  platform TEXT NOT NULL,
  mentioned INTEGER NOT NULL DEFAULT 0,
  score INTEGER NOT NULL DEFAULT 0,
  snippet TEXT DEFAULT '',
  advice_json TEXT DEFAULT '[]',
  screenshot_path TEXT DEFAULT ''
);

CREATE TABLE IF NOT EXISTS keywords (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  word TEXT NOT NULL,
  created_at INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS writing_titles (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  keyword_id INTEGER DEFAULT 0,
  title TEXT NOT NULL,
  created_at INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS instructions (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  name TEXT NOT NULL,
  body TEXT NOT NULL DEFAULT '',
  status TEXT NOT NULL DEFAULT 'on',
  created_at INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS article_types (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  name TEXT NOT NULL,
  created_at INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS articles (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  task_id INTEGER DEFAULT 0,
  type_id INTEGER DEFAULT 0,
  title TEXT NOT NULL,
  content TEXT NOT NULL DEFAULT '',
  keyword TEXT DEFAULT '',
  source TEXT DEFAULT 'write',
  created_at INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS knowledge (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  title TEXT NOT NULL,
  body TEXT DEFAULT '',
  created_at INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS image_groups (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  name TEXT NOT NULL,
  kind INTEGER NOT NULL DEFAULT 1,
  created_at INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS images (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  group_id INTEGER NOT NULL,
  filename TEXT NOT NULL,
  path TEXT NOT NULL,
  created_at INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS tdk_groups (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  name TEXT NOT NULL,
  items_json TEXT NOT NULL DEFAULT '[]',
  created_at INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS video_assets (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  group_id INTEGER DEFAULT 0,
  filename TEXT NOT NULL,
  path TEXT NOT NULL,
  kind TEXT DEFAULT 'video',
  created_at INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS video_jobs (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  task_id INTEGER DEFAULT 0,
  kind TEXT NOT NULL,
  tdk_id INTEGER DEFAULT 0,
  count INTEGER DEFAULT 1,
  status TEXT NOT NULL DEFAULT 'queued',
  created_at INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS video_records (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  job_id INTEGER NOT NULL,
  platform TEXT DEFAULT '',
  title TEXT DEFAULT '',
  status TEXT NOT NULL DEFAULT 'success',
  error TEXT DEFAULT '',
  published_at INTEGER DEFAULT 0
);

CREATE TABLE IF NOT EXISTS contacts (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  name TEXT DEFAULT '',
  phone TEXT DEFAULT '',
  email TEXT DEFAULT '',
  company TEXT DEFAULT '',
  created_at INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS sites (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  name TEXT NOT NULL,
  domain TEXT NOT NULL,
  config_json TEXT DEFAULT '{}',
  created_at INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS site_pub_records (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  site_id INTEGER NOT NULL,
  article_id INTEGER NOT NULL,
  url TEXT DEFAULT '',
  status TEXT NOT NULL DEFAULT 'success',
  created_at INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS proxy_ips (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  ip TEXT NOT NULL,
  port INTEGER NOT NULL,
  protocol TEXT DEFAULT 'http',
  account TEXT DEFAULT '',
  password TEXT DEFAULT '',
  status TEXT DEFAULT 'on',
  created_at INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS media_favs (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  kind INTEGER NOT NULL,
  name TEXT NOT NULL,
  channel_ids_json TEXT DEFAULT '[]',
  created_at INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS weixin_auths (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  account_name TEXT NOT NULL,
  status TEXT NOT NULL DEFAULT 'valid',
  created_at INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS channel_catalog (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  kind TEXT NOT NULL,
  name TEXT NOT NULL,
  industry TEXT DEFAULT '',
  status TEXT DEFAULT 'on'
);

CREATE TABLE IF NOT EXISTS publish_jobs (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  task_id INTEGER DEFAULT 0,
  kind TEXT NOT NULL,
  article_ids_json TEXT DEFAULT '[]',
  channel_ids_json TEXT DEFAULT '[]',
  contact_id INTEGER DEFAULT 0,
  status TEXT NOT NULL DEFAULT 'queued',
  created_at INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS publish_records (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  job_id INTEGER NOT NULL,
  channel_id INTEGER NOT NULL,
  article_id INTEGER NOT NULL,
  url TEXT DEFAULT '',
  status TEXT NOT NULL DEFAULT 'success',
  error TEXT DEFAULT '',
  published_at INTEGER DEFAULT 0
);

CREATE TABLE IF NOT EXISTS inclusion_queries (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  task_id INTEGER DEFAULT 0,
  query TEXT NOT NULL,
  target TEXT DEFAULT '',
  included INTEGER NOT NULL DEFAULT 0,
  snippet TEXT DEFAULT '',
  screenshot_path TEXT DEFAULT '',
  created_at INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS idempotency (
  key TEXT PRIMARY KEY,
  user_id INTEGER NOT NULL,
  response_json TEXT NOT NULL,
  created_at INTEGER NOT NULL
);
`);

const repCols = db.prepare('PRAGMA table_info(analyze_reports)').all();
if (!repCols.some((c) => c.name === 'entity')) {
  db.exec('ALTER TABLE analyze_reports ADD COLUMN entity TEXT DEFAULT \'\'');
}

function seedIfEmpty() {
  const n = db.prepare('SELECT COUNT(*) AS c FROM users').get().c;
  if (n > 0) return false;

  const t = now();
  const expire = t + 365 * 86400;
  const insUser = db.prepare(`INSERT INTO users
    (username, password_hash, nickname, balance, score, kyc_status, entitlement_plan, entitlement_expire, daily_write_quota, daily_pub_quota, created_at)
    VALUES (?,?,?,?,?,?,?,?,?,?,?)`);
  insUser.run('chinaz51008', hashPassword('chinaz51008'), '站长GEO演示', 1000000, 10000, 'approved', '旗舰版', expire, 500, 500, t);
  insUser.run('demo', hashPassword('demo123'), '未实名演示', 50000, 200, 'none', '体验版', expire, 10, 10, t);
  db.prepare('INSERT INTO admin_users (username, password_hash, created_at) VALUES (?,?,?)')
    .run('admin', hashPassword('admin123'), t);

  const settings = {
    diagnose_price: '10000',
    diagnose_advice_price: '10200',
    pro_parse_price: '0',
    pro_diagnose_price: '15000',
    index_score_cost: '10',
    distill_score_cost: '10',
    write_price: '200',
    publish_price: '100',
  };
  const insSet = db.prepare('INSERT INTO settings (key, value) VALUES (?,?)');
  Object.entries(settings).forEach(([k, v]) => insSet.run(k, v));

  const uid = 1;
  const kws = ['GEO优化', '生成式引擎优化', 'AI搜索可见度', '品牌大模型收录', '企业知识库'];
  const insKw = db.prepare('INSERT INTO keywords (user_id, word, created_at) VALUES (?,?,?)');
  kws.forEach((w) => insKw.run(uid, w, t));

  const insTitle = db.prepare('INSERT INTO writing_titles (user_id, keyword_id, title, created_at) VALUES (?,?,?,?)');
  insTitle.run(uid, 1, '什么是GEO生成式引擎优化？企业如何提升AI搜索可见度', t);
  insTitle.run(uid, 1, '2026年品牌如何出现在豆包、元宝、通义的回答里', t);
  insTitle.run(uid, 2, '生成式引擎优化和传统SEO有何不同', t);

  db.prepare('INSERT INTO instructions (user_id, name, body, status, created_at) VALUES (?,?,?,?,?)')
    .run(uid, '专业科普', '请以专业、可信的口吻撰写GEO相关文章，结构含引言、要点、案例、结语，字数800-1200。', 'on', t);
  db.prepare('INSERT INTO article_types (user_id, name, created_at) VALUES (?,?,?)').run(uid, '品牌科普', t);
  db.prepare('INSERT INTO article_types (user_id, name, created_at) VALUES (?,?,?)').run(uid, '行业观察', t);

  db.prepare('INSERT INTO articles (user_id, task_id, type_id, title, content, keyword, source, created_at) VALUES (?,?,?,?,?,?,?,?)')
    .run(uid, 0, 1, '企业如何做生成式引擎优化（GEO）',
      '生成式引擎优化（GEO）关注品牌在大模型回答中的提及率与引用质量。企业需要沉淀可被模型学习的权威内容，覆盖用户真实提问，并持续监测豆包、元宝、通义等平台的可见度变化。',
      'GEO优化', 'write', t - 86400);

  db.prepare('INSERT INTO knowledge (user_id, title, body, created_at) VALUES (?,?,?,?)')
    .run(uid, '公司简介', '站长之家GEO致力于帮助企业提升在生成式AI搜索中的品牌可见度，覆盖诊断、内容生产与多渠道分发。', t);
  db.prepare('INSERT INTO image_groups (user_id, name, kind, created_at) VALUES (?,?,?,?)').run(uid, '企业形象', 1, t);
  db.prepare('INSERT INTO image_groups (user_id, name, kind, created_at) VALUES (?,?,?,?)').run(uid, '视频封面', 2, t);
  db.prepare('INSERT INTO tdk_groups (user_id, name, items_json, created_at) VALUES (?,?,?,?)')
    .run(uid, '默认TDK', JSON.stringify([{ title: 'GEO优化服务', description: '提升AI搜索可见度', keywords: 'GEO,AI搜索' }]), t);
  db.prepare('INSERT INTO contacts (user_id, name, phone, email, company, created_at) VALUES (?,?,?,?,?,?)')
    .run(uid, '王经理', '13800001111', 'geo@example.com', '站长GEO科技', t);
  db.prepare('INSERT INTO sites (user_id, name, domain, config_json, created_at) VALUES (?,?,?,?,?)')
    .run(uid, '品牌官网', 'www.example-geo.com', '{}', t);
  db.prepare('INSERT INTO weixin_auths (user_id, account_name, status, created_at) VALUES (?,?,?,?)')
    .run(uid, 'GEO官微', 'valid', t);

  const insCh = db.prepare('INSERT INTO channel_catalog (kind, name, industry, status) VALUES (?,?,?,?)');
  const b2b = ['慧聪网', '中国供应商', '全球机械网', '化工网', '食品商务网', '建材网', '电力英才网行业站', '汽车之家B2B', '医疗器械创新网', '环保在线', '仪器信息网', '中国制造网'];
  const web = ['北青网', '中华网', '搜狐号媒体', '网易新闻稿', '凤凰资讯', '人民资讯', '新浪看点', '腾讯新闻稿', '一点资讯', '今日头条号', '百家号', '企鹅号'];
  const zi = ['知乎机构号', '小红书品牌号', '抖音企业号', '视频号', '微博蓝V', 'B站机构号', '快手企业号', '头条号', '百家号大V', '搜狐号大V', '网易号大V', '企鹅号大V'];
  b2b.forEach((n) => insCh.run('b2b', n, '综合', 'on'));
  web.forEach((n) => insCh.run('webmedia', n, '资讯', 'on'));
  zi.forEach((n) => insCh.run('zimedia', n, '自媒体', 'on'));

  const jobId = db.prepare(`INSERT INTO analyze_jobs
    (user_id, task_id, brand, industry_json, platforms_json, kind, need_advice, full_name, region, competitors, price, status, created_at)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)`)
    .run(uid, 0, '站长之家GEO', JSON.stringify(['生成式引擎优化']), JSON.stringify(['doubao', 'yuanbao', 'tongyi']),
      'standard', 1, '站长之家GEO科技有限公司', '全国', '传统SEO服务商', 10200, 'done', t - 3600).lastInsertRowid;

  const insR = db.prepare(`INSERT INTO analyze_reports (job_id, user_id, platform, mentioned, score, snippet, advice_json, screenshot_path)
    VALUES (?,?,?,?,?,?,?,?)`);
  const reports = [
    ['doubao', 1, 78, '豆包在回答「GEO优化」时提及了站长之家GEO作为可参考服务商。', ['补充权威案例页', '增加问答对']],
    ['yuanbao', 1, 65, '元宝部分回答引用了相关行业词，品牌全称出现一次。', ['补全企业百科资料']],
    ['tongyi', 0, 32, '通义当前回答未稳定提及该品牌。', ['投放行业白皮书内容']],
  ];
  reports.forEach((r) => insR.run(jobId, uid, r[0], r[1], r[2], r[3], JSON.stringify(r[4]), ''));

  return true;
}

db.exec('BEGIN');
try {
  const seeded = seedIfEmpty();
  db.exec('COMMIT');
  if (seeded) console.log('[seed] GEO 演示数据已写入');
} catch (e) {
  db.exec('ROLLBACK');
  throw e;
}

function getSetting(key, fallback) {
  const row = db.prepare('SELECT value FROM settings WHERE key=?').get(key);
  return row ? row.value : fallback;
}

function setSetting(key, value) {
  db.prepare('INSERT INTO settings (key, value) VALUES (?,?) ON CONFLICT(key) DO UPDATE SET value=excluded.value')
    .run(key, String(value));
}

module.exports = { db, DATA_DIR, getSetting, setSetting };
