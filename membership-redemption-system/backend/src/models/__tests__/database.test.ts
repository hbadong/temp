import { getPool, initDatabase, closeDatabase } from '../config/database'
import { expect } from '@jest/globals'

describe('Database Connection', () => {
  beforeAll(async () => {
    await initDatabase()
  })

  afterAll(async () => {
    await closeDatabase()
  })

  it('should establish database connection', async () => {
    const pool = getPool()
    expect(pool).toBeDefined()
  })

  it('should execute simple query', async () => {
    const pool = getPool()
    const [rows] = await pool.query('SELECT 1 as result')
    expect(rows).toBeDefined()
    expect(Array.isArray(rows)).toBe(true)
  })
})

describe('Database Schema', () => {
  let pool: any

  beforeAll(async () => {
    await initDatabase()
    pool = getPool()
  })

  afterAll(async () => {
    await closeDatabase()
  })

  it('should have users table', async () => {
    const [rows] = await pool.query('DESCRIBE users')
    const columns = (rows as any[]).map((row: any) => row.Field)
    expect(columns).toContain('id')
    expect(columns).toContain('phone')
    expect(columns).toContain('status')
  })

  it('should have admins table', async () => {
    const [rows] = await pool.query('DESCRIBE admins')
    const columns = (rows as any[]).map((row: any) => row.Field)
    expect(columns).toContain('id')
    expect(columns).toContain('username')
    expect(columns).toContain('password_hash')
  })

  it('should have products table', async () => {
    const [rows] = await pool.query('DESCRIBE products')
    const columns = (rows as any[]).map((row: any) => row.Field)
    expect(columns).toContain('id')
    expect(columns).toContain('platform')
    expect(columns).toContain('price')
    expect(columns).toContain('stock')
  })

  it('should have cards table', async () => {
    const [rows] = await pool.query('DESCRIBE cards')
    const columns = (rows as any[]).map((row: any) => row.Field)
    expect(columns).toContain('id')
    expect(columns).toContain('card_no')
    expect(columns).toContain('password')
    expect(columns).toContain('status')
  })

  it('should have orders table', async () => {
    const [rows] = await pool.query('DESCRIBE orders')
    const columns = (rows as any[]).map((row: any) => row.Field)
    expect(columns).toContain('id')
    expect(columns).toContain('order_no')
    expect(columns).toContain('status')
    expect(columns).toContain('type')
  })

  it('should have sms_logs table', async () => {
    const [rows] = await pool.query('DESCRIBE sms_logs')
    const columns = (rows as any[]).map((row: any) => row.Field)
    expect(columns).toContain('id')
    expect(columns).toContain('phone')
    expect(columns).toContain('type')
    expect(columns).toContain('status')
  })

  it('should have operation_logs table', async () => {
    const [rows] = await pool.query('DESCRIBE operation_logs')
    const columns = (rows as any[]).map((row: any) => row.Field)
    expect(columns).toContain('id')
    expect(columns).toContain('admin_id')
    expect(columns).toContain('action')
  })
})
