const { db } = require('../db');
const { ok, fail, paginate, now } = require('../lib');

function listByUser(table, req, res, extraWhere, extraArgs) {
  const { page, pageSize, offset } = paginate(req.query);
  const where = extraWhere ? `user_id=? AND ${extraWhere}` : 'user_id=?';
  const args = extraArgs ? [req.user.id, ...extraArgs] : [req.user.id];
  const total = db.prepare(`SELECT COUNT(*) AS c FROM ${table} WHERE ${where}`).get(...args).c;
  const list = db.prepare(`SELECT * FROM ${table} WHERE ${where} ORDER BY id DESC LIMIT ? OFFSET ?`)
    .all(...args, pageSize, offset);
  ok(res, { list, total, page, page_size: pageSize });
}

function createRow(table, fields, req, res) {
  const keys = Object.keys(fields);
  const placeholders = keys.map(() => '?').join(',');
  const info = db.prepare(`INSERT INTO ${table} (${keys.join(',')}) VALUES (${placeholders})`)
    .run(...keys.map((k) => fields[k]));
  const row = db.prepare(`SELECT * FROM ${table} WHERE id=?`).get(info.lastInsertRowid);
  ok(res, row);
}

function updateOwn(table, id, userId, patch) {
  const row = db.prepare(`SELECT * FROM ${table} WHERE id=? AND user_id=?`).get(id, userId);
  if (!row) return null;
  const keys = Object.keys(patch).filter((k) => patch[k] !== undefined);
  if (!keys.length) return row;
  const sql = `UPDATE ${table} SET ${keys.map((k) => `${k}=?`).join(',')} WHERE id=? AND user_id=?`;
  db.prepare(sql).run(...keys.map((k) => patch[k]), id, userId);
  return db.prepare(`SELECT * FROM ${table} WHERE id=?`).get(id);
}

function deleteOwn(table, id, userId) {
  const info = db.prepare(`DELETE FROM ${table} WHERE id=? AND user_id=?`).run(id, userId);
  return info.changes > 0;
}

function need(res, cond, msg) {
  if (!cond) {
    fail(res, msg);
    return false;
  }
  return true;
}

module.exports = { listByUser, createRow, updateOwn, deleteOwn, need, now, ok, fail, paginate, db };
