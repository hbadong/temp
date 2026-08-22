const path = require('path');
const fs = require('fs');
const { DatabaseSync } = require('node:sqlite');
const { tokenize } = require('./tokenizer');

const DATA_DIR = process.env.DATA_DIR || path.join(__dirname, '..', 'data');
fs.mkdirSync(DATA_DIR, { recursive: true });

const db = new DatabaseSync(path.join(DATA_DIR, 'pan-search.db'));
db.exec('PRAGMA journal_mode = WAL');
db.exec('PRAGMA foreign_keys = ON');

db.exec(`
CREATE TABLE IF NOT EXISTS resources (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  fingerprint TEXT NOT NULL UNIQUE,
  title TEXT NOT NULL,
  title_t TEXT NOT NULL DEFAULT '',
  link TEXT NOT NULL,
  password TEXT,
  cloud_type TEXT NOT NULL DEFAULT 'other',
  res_type TEXT NOT NULL DEFAULT 'other',
  channel TEXT,
  size_text TEXT,
  status TEXT NOT NULL DEFAULT 'ok',
  invalid_reports INTEGER NOT NULL DEFAULT 0,
  published_at TEXT,
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_resources_cloud ON resources(cloud_type);
CREATE INDEX IF NOT EXISTS idx_resources_status ON resources(status);
CREATE INDEX IF NOT EXISTS idx_resources_created ON resources(created_at);
CREATE INDEX IF NOT EXISTS idx_resources_published ON resources(published_at DESC);

CREATE VIRTUAL TABLE IF NOT EXISTS resources_fts USING fts5(
  title_t,
  content='resources',
  content_rowid='id',
  tokenize='unicode61'
);

CREATE TRIGGER IF NOT EXISTS resources_ai AFTER INSERT ON resources BEGIN
  INSERT INTO resources_fts(rowid, title_t) VALUES (new.id, new.title_t);
END;

CREATE TRIGGER IF NOT EXISTS resources_ad AFTER DELETE ON resources BEGIN
  INSERT INTO resources_fts(resources_fts, rowid, title_t) VALUES ('delete', old.id, old.title_t);
END;

CREATE TRIGGER IF NOT EXISTS resources_au AFTER UPDATE OF title ON resources BEGIN
  INSERT INTO resources_fts(resources_fts, rowid, title_t) VALUES ('delete', old.id, old.title_t);
  INSERT INTO resources_fts(rowid, title_t) VALUES (new.id, new.title_t);
END;

CREATE TABLE IF NOT EXISTS channels (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  username TEXT NOT NULL UNIQUE,
  enabled INTEGER NOT NULL DEFAULT 1,
  blocked INTEGER NOT NULL DEFAULT 0,
  last_msg_id INTEGER NOT NULL DEFAULT 0,
  collected INTEGER NOT NULL DEFAULT 0,
  note TEXT,
  created_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS sensitive_words (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  word TEXT NOT NULL UNIQUE,
  created_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS feedbacks (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  resource_id INTEGER,
  type TEXT NOT NULL,
  content TEXT,
  contact TEXT,
  status TEXT NOT NULL DEFAULT 'pending',
  created_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS search_logs (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  keyword TEXT NOT NULL,
  result_count INTEGER NOT NULL DEFAULT 0,
  took_ms INTEGER NOT NULL DEFAULT 0,
  ip TEXT,
  created_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS collect_logs (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  channel TEXT,
  source TEXT NOT NULL DEFAULT 'simulator',
  fetched INTEGER NOT NULL DEFAULT 0,
  inserted INTEGER NOT NULL DEFAULT 0,
  skipped INTEGER NOT NULL DEFAULT 0,
  filtered INTEGER NOT NULL DEFAULT 0,
  message TEXT,
  created_at TEXT NOT NULL
);
`);

const stmtCache = new Map();
function prepare(sql) {
  if (!stmtCache.has(sql)) stmtCache.set(sql, db.prepare(sql));
  return stmtCache.get(sql);
}

function now() {
  return new Date().toISOString();
}

function ftsText(title) {
  return tokenize(title).join(' ');
}

module.exports = { db, prepare, now, ftsText };
