const { db, prepare, now, ftsText } = require('../db');
const { buildMatchQuery } = require('../tokenizer');

const CLOUD_TYPES = ['aliyun', 'baidu', 'quark', 'xunlei', 'pan115', 'tianyi', 'mobile', 'other'];
const RES_TYPES = ['video', 'software', 'doc', 'music', 'image', 'other'];

function getSensitiveWords() {
  return prepare('SELECT word FROM sensitive_words').all().map((r) => r.word);
}

function containsSensitive(text, words) {
  const lower = String(text).toLowerCase();
  return words.find((w) => lower.includes(w.toLowerCase())) || null;
}

function insertResource(item) {
  const ts = now();
  const info = prepare(`
    INSERT OR IGNORE INTO resources
      (fingerprint, title, title_t, link, password, cloud_type, res_type, channel, size_text, status, published_at, created_at, updated_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'ok', ?, ?, ?)
  `).run(
    item.fingerprint,
    item.title,
    ftsText(item.title),
    item.link,
    item.password || null,
    item.cloud_type || 'other',
    item.res_type || 'other',
    item.channel || null,
    item.size_text || null,
    item.published_at || ts,
    ts,
    ts
  );
  return info.changes > 0;
}

function updateChannelCounter(channel) {
  if (!channel) return;
  const username = channel.replace(/^@/, '');
  prepare(`
    INSERT INTO channels (username, collected, created_at) VALUES (?, 1, ?)
    ON CONFLICT(username) DO UPDATE SET collected = collected + 1
  `).run(username, now());
}

function importMessages(messages, source) {
  const words = getSensitiveWords();
  const result = { fetched: messages.length, inserted: 0, skipped: 0, filtered: 0 };
  for (const msg of messages) {
    const parsed = typeof msg === 'string'
      ? require('../parser').parseMessage(msg, null)
      : msg;
    if (!parsed || !parsed.link || !parsed.title) {
      result.skipped += 1;
      continue;
    }
    const hit = containsSensitive(`${parsed.title} ${parsed.link}`, words);
    if (hit) {
      result.filtered += 1;
      continue;
    }
    if (insertResource(parsed)) {
      result.inserted += 1;
      updateChannelCounter(parsed.channel);
    } else {
      result.skipped += 1;
    }
  }
  prepare(`
    INSERT INTO collect_logs (channel, source, fetched, inserted, skipped, filtered, message, created_at)
    VALUES (?, ?, ?, ?, ?, ?, NULL, ?)
  `).run(result.channel || null, source, result.fetched, result.inserted, result.skipped, result.filtered, now());
  if (result.inserted > 0) flushSearchCache();
  return result;
}

const SEARCH_SELECT = `
  SELECT r.id, r.title, r.link, r.password, r.cloud_type, r.res_type,
         r.status, r.invalid_reports, r.size_text, r.channel, r.published_at, r.created_at
  FROM resources_fts f
  CROSS JOIN resources r ON r.id = f.rowid
`;

const MAX_OFFSET = 9800;

const countCache = new Map();
const COUNT_TTL_MS = 30000;
const STATS_TTL_MS = 60000;
const RESULT_TTL_MS = 60000;
const RESULT_CACHE_MAX = 500;
let statsCache = null;
const resultCache = new Map();

function cachedResult(key, fn) {
  const hit = resultCache.get(key);
  if (hit && hit.expire > Date.now()) return hit.value;
  const value = fn();
  if (resultCache.size >= RESULT_CACHE_MAX) {
    const oldest = resultCache.keys().next().value;
    resultCache.delete(oldest);
  }
  resultCache.set(key, { value, expire: Date.now() + RESULT_TTL_MS });
  return value;
}

function flushSearchCache() {
  resultCache.clear();
  countCache.clear();
  statsCache = null;
}

function cachedCount(sql, params) {
  const key = sql + '|' + params.join('\x1f');
  const hit = countCache.get(key);
  if (hit && hit.expire > Date.now()) return hit.value;
  const value = prepare(sql).get(...params).c;
  countCache.set(key, { value, expire: Date.now() + COUNT_TTL_MS });
  if (countCache.size > 500) countCache.clear();
  return value;
}

function search({ q, cloud, type, page = 1, size = 20 }) {
  const started = Date.now();
  page = Math.max(1, parseInt(page, 10) || 1);
  size = Math.min(50, Math.max(1, parseInt(size, 10) || 20));
  if ((page - 1) * size > MAX_OFFSET) {
    return emptyResult(page, size, started);
  }

  const cacheKey = JSON.stringify([q || '', cloud || '', type || '', page, size]);
  const result = cachedResult(cacheKey, () => {
    const hasQuery = Boolean(q && q.trim());
    if (hasQuery) {
      return searchByMatch({ q: q.trim(), cloud, type, page, size, started: Date.now() });
    }
    return searchRecent({ cloud, type, page, size, started: Date.now() });
  });
  return { ...result, took_ms: Date.now() - started };
}

function commonFilter(cloud, type) {
  const conditions = [`status != 'blocked'`];
  const params = [];
  if (cloud && CLOUD_TYPES.includes(cloud)) {
    conditions.push('cloud_type = ?');
    params.push(cloud);
  }
  if (type && RES_TYPES.includes(type)) {
    conditions.push('res_type = ?');
    params.push(type);
  }
  return { where: `WHERE ${conditions.join(' AND ')}`, params };
}

function searchRecent({ cloud, type, page, size, started }) {
  const { where, params } = commonFilter(cloud, type);
  const total = cachedCount(`SELECT COUNT(*) AS c FROM resources ${where}`, params);
  const items = prepare(
    `SELECT id, title, link, password, cloud_type, res_type, status, invalid_reports,
            size_text, channel, published_at, created_at
     FROM resources ${where} ORDER BY published_at DESC LIMIT ? OFFSET ?`
  ).all(...params, size, (page - 1) * size);
  return {
    total,
    page,
    size,
    took_ms: Date.now() - started,
    items: items.map(mapItem),
  };
}

function searchByMatch({ q, cloud, type, page, size, started }) {
  const match = buildMatchQuery(q);
  if (!match) return emptyResult(page, size, started);
  const conditions = [`r.status != 'blocked'`, 'resources_fts MATCH ?'];
  const params = [match];

  if (cloud && CLOUD_TYPES.includes(cloud)) {
    conditions.push('r.cloud_type = ?');
    params.push(cloud);
  }
  if (type && RES_TYPES.includes(type)) {
    conditions.push('r.res_type = ?');
    params.push(type);
  }

  const where = `WHERE ${conditions.join(' AND ')}`;
  const total = cachedCount(
    `SELECT COUNT(*) AS c FROM resources_fts f CROSS JOIN resources r ON r.id = f.rowid ${where}`,
    params
  );

  const offset = (page - 1) * size;
  const items = prepare(
    `${SEARCH_SELECT} ${where} ORDER BY f.rank, r.published_at DESC LIMIT ? OFFSET ?`
  ).all(...params, size, offset);

  return {
    total,
    page,
    size,
    took_ms: Date.now() - started,
    items: items.map(mapItem),
  };
}

function mapItem(r) {
  return {
    id: r.id,
    title: r.title,
    link: r.link,
    password: r.password,
    cloud_type: r.cloud_type,
    res_type: r.res_type,
    status: r.status === 'ok' && r.invalid_reports >= 5 ? 'expired' : r.status,
    size_text: r.size_text,
    channel: r.channel,
    published_at: r.published_at,
    created_at: r.created_at,
  };
}

function emptyResult(page, size, started) {
  return { total: 0, page, size, took_ms: Date.now() - started, items: [] };
}

function getResource(id) {
  return prepare('SELECT * FROM resources WHERE id = ?').get(id);
}

function reportResource(id, type, content, contact) {
  const resource = getResource(id);
  if (!resource) return false;
  prepare('INSERT INTO feedbacks (resource_id, type, content, contact, created_at) VALUES (?, ?, ?, ?, ?)')
    .run(id, type, content || null, contact || null, now());
  if (type === 'invalid') {
    prepare('UPDATE resources SET invalid_reports = invalid_reports + 1, updated_at = ? WHERE id = ?')
      .run(now(), id);
    flushSearchCache();
  }
  return true;
}

function logSearch(keyword, resultCount, tookMs, ip) {
  prepare('INSERT INTO search_logs (keyword, result_count, took_ms, ip, created_at) VALUES (?, ?, ?, ?, ?)')
    .run(keyword, resultCount, tookMs, ip || null, now());
}

function hotKeywords(limit = 10) {
  const since = new Date(Date.now() - 7 * 24 * 3600 * 1000).toISOString();
  return prepare(`
    SELECT keyword, COUNT(*) AS cnt FROM search_logs
    WHERE created_at > ? AND LENGTH(keyword) >= 2 AND result_count > 0
    GROUP BY keyword ORDER BY cnt DESC LIMIT ?
  `).all(since, limit);
}

function publicStats() {
  if (statsCache && statsCache.expire > Date.now()) return statsCache.value;
  const total = prepare("SELECT COUNT(*) AS c FROM resources WHERE status != 'blocked'").get().c;
  const todayStart = new Date();
  todayStart.setHours(0, 0, 0, 0);
  const todayNew = prepare('SELECT COUNT(*) AS c FROM resources WHERE created_at >= ?')
    .get(todayStart.toISOString()).c;
  const byCloud = prepare(`
    SELECT cloud_type, COUNT(*) AS c FROM resources
    WHERE status != 'blocked' GROUP BY cloud_type ORDER BY c DESC
  `).all();
  const value = { total, today_new: todayNew, by_cloud: byCloud };
  statsCache = { value, expire: Date.now() + STATS_TTL_MS };
  return value;
}

module.exports = {
  CLOUD_TYPES,
  RES_TYPES,
  getSensitiveWords,
  containsSensitive,
  insertResource,
  importMessages,
  search,
  getResource,
  reportResource,
  logSearch,
  hotKeywords,
  publicStats,
};
