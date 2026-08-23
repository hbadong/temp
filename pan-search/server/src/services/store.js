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
  if (info.changes > 0) {
    const resourceId = info.lastInsertRowid;
    generateFileTree(resourceId, item.res_type || 'other', item.title);
  }
  return info.changes > 0;
}

function generateFileTree(resourceId, resType, title) {
  const ts = now();
  const trees = {
    video: [
      { name: 'Season 1', is_dir: 1, children: [
        { name: 'S01E01.mp4', size: 1200000000 },
        { name: 'S01E02.mp4', size: 1180000000 },
        { name: 'S01E03.mp4', size: 1210000000 },
      ]},
      { name: 'Season 2', is_dir: 1, children: [
        { name: 'S02E01.mp4', size: 1250000000 },
        { name: 'S02E02.mp4', size: 1230000000 },
      ]},
    ],
    software: [
      { name: 'Setup.exe', size: 2100000000 },
      { name: 'Crack', is_dir: 1, children: [
        { name: 'patch.exe', size: 500000 },
        { name: 'readme.txt', size: 2048 },
      ]},
    ],
    doc: [
      { name: 'Part 1.pdf', size: 50000000 },
      { name: 'Part 2.pdf', size: 48000000 },
      { name: 'Appendix.docx', size: 12000000 },
    ],
    music: [
      { name: 'CD1.flac', size: 450000000 },
      { name: 'CD2.flac', size: 430000000 },
      { name: 'Cover.jpg', size: 5000000 },
    ],
    image: [
      { name: 'Wallpapers', is_dir: 1, children: [
        { name: 'wallpaper_001.jpg', size: 8000000 },
        { name: 'wallpaper_002.jpg', size: 7500000 },
        { name: 'wallpaper_003.jpg', size: 8200000 },
      ]},
    ],
    other: [
      { name: 'README.txt', size: 1024 },
      { name: 'data.dat', size: 100000000 },
    ],
  };

  const tree = trees[resType] || trees.other;

  const insertFile = prepare(`
    INSERT INTO resource_files (resource_id, parent_id, name, is_dir, size, created_at)
    VALUES (?, ?, ?, ?, ?, ?)
  `);

  for (const item of tree) {
    const isDir = item.is_dir ? 1 : 0;
    const fileInfo = insertFile.run(resourceId, null, item.name, isDir, item.size || null, ts);
    const parentId = fileInfo.lastInsertRowid;
    if (item.children) {
      for (const child of item.children) {
        insertFile.run(resourceId, parentId, child.name, 0, child.size || null, ts);
      }
    }
  }
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

const TIME_RANGES = {
  day: 1,
  week: 7,
  month: 30,
  halfyear: 180,
  year: 365,
};

const SIZE_FILTERS = {
  large: "size_text GLOB '*GB*'",
  medium: "(size_text GLOB '*[0-9][0-9][0-9] MB*' OR size_text GLOB '*[0-9] GB*')",
  small: "(size_text GLOB '*[0-9] MB*' OR size_text GLOB '*[0-9][0-9] MB*')",
  tiny: "size_text GLOB '*KB*'",
};

function search({ q, cloud, type, time, page = 1, size = 20, sort = 'relevance', fileSize }) {
  const started = Date.now();
  page = Math.max(1, parseInt(page, 10) || 1);
  size = Math.min(50, Math.max(1, parseInt(size, 10) || 20));
  if ((page - 1) * size > MAX_OFFSET) {
    return emptyResult(page, size, started);
  }

  const validSorts = ['relevance', 'date', 'size'];
  const sortOrder = validSorts.includes(sort) ? sort : 'relevance';
  const timeDays = TIME_RANGES[time] || 0;
  const sizeCond = SIZE_FILTERS[fileSize] || '';

  const cacheKey = JSON.stringify([q || '', cloud || '', type || '', timeDays, sizeCond, page, size, sortOrder]);
  const result = cachedResult(cacheKey, () => {
    const hasQuery = Boolean(q && q.trim());
    if (hasQuery) {
      return searchByMatch({ q: q.trim(), cloud, type, timeDays, sizeCond, page, size, sort: sortOrder, started: Date.now() });
    }
    return searchRecent({ cloud, type, timeDays, sizeCond, page, size, sort: sortOrder, started: Date.now() });
  });
  return { ...result, took_ms: Date.now() - started };
}

function commonFilter(cloud, type, timeDays, sizeCond) {
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
  if (timeDays > 0) {
    const since = new Date(Date.now() - timeDays * 24 * 3600 * 1000).toISOString();
    conditions.push('published_at >= ?');
    params.push(since);
  }
  if (sizeCond) {
    conditions.push(sizeCond);
  }
  return { where: `WHERE ${conditions.join(' AND ')}`, params };
}

function searchRecent({ cloud, type, timeDays, sizeCond, page, size, sort = 'relevance', started }) {
  const { where, params } = commonFilter(cloud, type, timeDays, sizeCond);
  const total = cachedCount(`SELECT COUNT(*) AS c FROM resources ${where}`, params);
  
  let orderBy = 'published_at DESC';
  if (sort === 'date') orderBy = 'published_at DESC';
  else if (sort === 'size') orderBy = "CASE WHEN size_text GLOB '*GB*' THEN 1 WHEN size_text GLOB '*MB*' THEN 2 WHEN size_text GLOB '*KB*' THEN 3 ELSE 4 END, published_at DESC";
  
  const items = prepare(
    `SELECT id, title, link, password, cloud_type, res_type, status, invalid_reports,
            size_text, channel, published_at, created_at
     FROM resources ${where} ORDER BY ${orderBy} LIMIT ? OFFSET ?`
  ).all(...params, size, (page - 1) * size);
  return {
    total,
    page,
    size,
    took_ms: Date.now() - started,
    items: items.map(mapItem),
  };
}

function searchByMatch({ q, cloud, type, timeDays, sizeCond, page, size, sort = 'relevance', started }) {
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
  if (timeDays > 0) {
    const since = new Date(Date.now() - timeDays * 24 * 3600 * 1000).toISOString();
    conditions.push('r.published_at >= ?');
    params.push(since);
  }
  if (sizeCond) {
    conditions.push(`r.${sizeCond}`);
  }

  const where = `WHERE ${conditions.join(' AND ')}`;
  const total = cachedCount(
    `SELECT COUNT(*) AS c FROM resources_fts f CROSS JOIN resources r ON r.id = f.rowid ${where}`,
    params
  );

  const offset = (page - 1) * size;
  let orderBy = 'f.rank, r.published_at DESC';
  if (sort === 'date') orderBy = 'r.published_at DESC';
  else if (sort === 'size') orderBy = "CASE WHEN r.size_text GLOB '*GB*' THEN 1 WHEN r.size_text GLOB '*MB*' THEN 2 WHEN r.size_text GLOB '*KB*' THEN 3 ELSE 4 END, f.rank";
  
  const items = prepare(
    `${SEARCH_SELECT} ${where} ORDER BY ${orderBy} LIMIT ? OFFSET ?`
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
    .run(id, type, content ? String(content).slice(0, 2000) : null, contact ? String(contact).slice(0, 200) : null, now());
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

function searchSuggestions(prefix, limit = 8) {
  if (!prefix || prefix.trim().length < 1) return [];
  const term = prefix.trim().toLowerCase();
  
  // 从热门搜索中获取前缀匹配
  const since = new Date(Date.now() - 30 * 24 * 3600 * 1000).toISOString();
  const fromLogs = prepare(`
    SELECT keyword, COUNT(*) AS cnt FROM search_logs
    WHERE created_at > ? AND LOWER(keyword) LIKE ? AND LENGTH(keyword) >= 2 AND result_count > 0
    GROUP BY keyword ORDER BY cnt DESC LIMIT ?
  `).all(since, `${term}%`, limit);
  
  if (fromLogs.length >= limit) return fromLogs.map(r => r.keyword);
  
  // 从资源标题中获取前缀匹配
  const fromTitles = prepare(`
    SELECT DISTINCT title FROM resources
    WHERE status != 'blocked' AND LOWER(title) LIKE ?
    ORDER BY published_at DESC LIMIT ?
  `).all(`${term}%`, limit - fromLogs.length);
  
  return [...fromLogs.map(r => r.keyword), ...fromTitles.map(r => r.title)].slice(0, limit);
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

function submitResource(data) {
  const ts = now();
  prepare(`
    INSERT INTO resource_submissions (title, link, password, cloud_type, res_type, size_text, submitter, status, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?)
  `).run(
    String(data.title).slice(0, 500),
    String(data.link).slice(0, 2000),
    data.password ? String(data.password).slice(0, 50) : null,
    CLOUD_TYPES.includes(data.cloud_type) ? data.cloud_type : 'other',
    RES_TYPES.includes(data.res_type) ? data.res_type : 'other',
    data.size_text ? String(data.size_text).slice(0, 100) : null,
    data.submitter ? String(data.submitter).slice(0, 200) : null,
    ts
  );
  return true;
}

function listSubmissions({ status = 'pending', page = 1, size = 20 } = {}) {
  const offset = (page - 1) * size;
  const validStatus = ['pending', 'approved', 'rejected'].includes(status) ? status : 'pending';
  const rows = prepare(`
    SELECT * FROM resource_submissions WHERE status = ? ORDER BY created_at DESC LIMIT ? OFFSET ?
  `).all(validStatus, size, offset);
  const total = prepare('SELECT COUNT(*) AS c FROM resource_submissions WHERE status = ?').get(validStatus).c;
  return { items: rows, total, page, size };
}

function approveSubmission(id) {
  const sub = prepare('SELECT * FROM resource_submissions WHERE id = ? AND status = \'pending\'').get(id);
  if (!sub) return false;
  const ts = now();
  const fingerprint = `user:${sub.link}`;
  const info = prepare(`
    INSERT OR IGNORE INTO resources
      (fingerprint, title, title_t, link, password, cloud_type, res_type, channel, size_text, status, published_at, created_at, updated_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'ok', ?, ?, ?)
  `).run(
    fingerprint,
    sub.title,
    ftsText(sub.title),
    sub.link,
    sub.password,
    sub.cloud_type,
    sub.res_type,
    null,
    sub.size_text,
    ts,
    ts,
    ts
  );
  if (info.changes > 0) {
    generateFileTree(info.lastInsertRowid, sub.res_type, sub.title);
  }
  prepare('UPDATE resource_submissions SET status = \'approved\', reviewed_at = ? WHERE id = ?').run(ts, id);
  return true;
}

function rejectSubmission(id, note) {
  const sub = prepare('SELECT id FROM resource_submissions WHERE id = ? AND status = \'pending\'').get(id);
  if (!sub) return false;
  prepare('UPDATE resource_submissions SET status = \'rejected\', admin_note = ?, reviewed_at = ? WHERE id = ?')
    .run(note ? String(note).slice(0, 500) : null, now(), id);
  return true;
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
  searchSuggestions,
  publicStats,
  flushSearchCache,
  submitResource,
  listSubmissions,
  approveSubmission,
  rejectSubmission,
};
