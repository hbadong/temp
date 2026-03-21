import mysql from 'mysql2/promise'
import { config } from './index.js'

let pool = null

export async function initDatabase() {
  pool = mysql.createPool({
    host: config.database.host,
    port: config.database.port,
    user: config.database.user,
    password: config.database.password,
    database: config.database.database,
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0,
    enableKeepAlive: true,
    keepAliveInitialDelay: 0
  })
  
  try {
    const connection = await pool.getConnection()
    console.log('Database connected successfully')
    connection.release()
  } catch (error) {
    console.error('Database connection failed:', error)
    throw error
  }
}

export function getPool() {
  if (!pool) {
    throw new Error('Database pool not initialized')
  }
  return pool
}

export async function query(sql, params) {
  const [rows] = await getPool().execute(sql, params)
  return rows
}

export async function closeDatabase() {
  if (pool) {
    await pool.end()
    pool = null
  }
}
