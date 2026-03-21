import mysql from 'mysql2/promise'
import Redis from 'ioredis'
import { config } from './index'
import { logger } from '../utils/logger'

let pool: mysql.Pool | null = null
let redis: Redis | null = null

export async function initDatabase(): Promise<void> {
  try {
    pool = mysql.createPool({
      host: config.db.host,
      port: config.db.port,
      user: config.db.user,
      password: config.db.password,
      database: config.db.database,
      connectionLimit: config.db.connectionLimit,
      waitForConnections: config.db.waitForConnections,
      queueLimit: config.db.queueLimit
    })

    const connection = await pool.getConnection()
    logger.info('Database connection established')
    connection.release()
  } catch (error) {
    logger.error('Database connection failed:', error)
    throw error
  }
}

export async function initRedis(): Promise<void> {
  try {
    redis = new Redis({
      host: config.redis.host,
      port: config.redis.port,
      password: config.redis.password,
      retryStrategy: (times) => {
        const delay = Math.min(times * 50, 2000)
        return delay
      }
    })

    redis.on('connect', () => {
      logger.info('Redis connection established')
    })

    redis.on('error', (error) => {
      logger.error('Redis connection error:', error)
    })
  } catch (error) {
    logger.error('Redis initialization failed:', error)
    throw error
  }
}

export function getPool(): mysql.Pool {
  if (!pool) {
    throw new Error('Database pool not initialized')
  }
  return pool
}

export function getRedis(): Redis {
  if (!redis) {
    throw new Error('Redis not initialized')
  }
  return redis
}

export async function closeDatabase(): Promise<void> {
  if (pool) {
    await pool.end()
    pool = null
    logger.info('Database connection closed')
  }
}

export async function closeRedis(): Promise<void> {
  if (redis) {
    await redis.quit()
    redis = null
    logger.info('Redis connection closed')
  }
}
