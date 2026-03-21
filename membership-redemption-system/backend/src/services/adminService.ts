import { getPool } from '../config/database'
import { hashPassword, comparePassword } from '../utils/password'
import { generateAdminToken } from '../utils/jwt'
import { logger } from '../utils/logger'
import { Admin } from '../models'

const LOGIN_MAX_ATTEMPTS = 5
const LOCK_DURATION_MINUTES = 30

export class AdminService {
  async login(username: string, password: string): Promise<{ admin: Admin; token: string }> {
    const admin = await this.findByUsername(username)

    if (!admin) {
      throw new Error('用户名或密码错误')
    }

    if (admin.status === 0) {
      throw new Error('账户已被禁用')
    }

    if (admin.lockedUntil && new Date(admin.lockedUntil) > new Date()) {
      throw new Error(`账户已被锁定，请${admin.lockedUntil}后再试`)
    }

    const isValid = await comparePassword(password, admin.passwordHash)
    if (!isValid) {
      await this.recordFailedLogin(admin.id)
      throw new Error('用户名或密码错误')
    }

    await this.recordSuccessfulLogin(admin.id)

    const token = generateAdminToken({
      userId: admin.id,
      phone: username,
      type: 'admin',
      adminId: admin.id,
      role: admin.role as 'super_admin' | 'admin'
    })

    logger.info(`Admin ${username} logged in successfully`)

    return { admin, token }
  }

  async findByUsername(username: string): Promise<Admin | null> {
    const pool = getPool()
    const [rows] = await pool.query(
      'SELECT id, username, password_hash as passwordHash, role, status, last_login_at as lastLoginAt, failed_login_attempts as failedLoginAttempts, locked_until as lockedUntil, created_at as createdAt, updated_at as updatedAt FROM admins WHERE username = ?',
      [username]
    )
    const admins = rows as Admin[]
    return admins.length > 0 ? admins[0] : null
  }

  async findById(id: number): Promise<Admin | null> {
    const pool = getPool()
    const [rows] = await pool.query(
      'SELECT id, username, password_hash as passwordHash, role, status, last_login_at as lastLoginAt, failed_login_attempts as failedLoginAttempts, locked_until as lockedUntil, created_at as createdAt, updated_at as updatedAt FROM admins WHERE id = ?',
      [id]
    )
    const admins = rows as Admin[]
    return admins.length > 0 ? admins[0] : null
  }

  async create(data: {
    username: string
    password: string
    role?: 'super_admin' | 'admin'
  }): Promise<Admin> {
    const pool = getPool()
    const passwordHash = await hashPassword(data.password)

    const [result] = await pool.query(
      `INSERT INTO admins (username, password_hash, role, status)
       VALUES (?, ?, ?, 1)`,
      [data.username, passwordHash, data.role || 'admin']
    )

    const insertResult = result as any
    const admin = await this.findById(insertResult.insertId)

    if (!admin) {
      throw new Error('Failed to create admin')
    }

    logger.info(`Admin created: ${data.username}`)

    return admin
  }

  private async recordFailedLogin(adminId: number): Promise<void> {
    const pool = getPool()

    await pool.query(
      'UPDATE admins SET failed_login_attempts = failed_login_attempts + 1 WHERE id = ?',
      [adminId]
    )

    const admin = await this.findById(adminId)
    if (admin && admin.failedLoginAttempts >= LOGIN_MAX_ATTEMPTS - 1) {
      const lockUntil = new Date(Date.now() + LOCK_DURATION_MINUTES * 60 * 1000)
      await pool.query(
        'UPDATE admins SET locked_until = ? WHERE id = ?',
        [lockUntil, adminId]
      )
      logger.warn(`Admin ${adminId} account locked until ${lockUntil}`)
    }
  }

  private async recordSuccessfulLogin(adminId: number): Promise<void> {
    const pool = getPool()

    await pool.query(
      'UPDATE admins SET failed_login_attempts = 0, locked_until = NULL, last_login_at = NOW() WHERE id = ?',
      [adminId]
    )
  }
}

export const adminService = new AdminService()
