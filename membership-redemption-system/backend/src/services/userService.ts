import { getPool } from '../config/database'
import { generateToken } from '../utils/jwt'
import { hashPassword } from '../utils/password'
import { smsService } from './smsService'
import { logger } from '../utils/logger'
import { User } from '../models'

export class UserService {
  async sendVerifyCode(phone: string): Promise<void> {
    await smsService.sendVerifyCode(phone)
  }

  async loginWithVerifyCode(phone: string, code: string): Promise<{ user: User; token: string }> {
    await smsService.verifyCode(phone, code)

    let user = await this.findByPhone(phone)
    
    if (!user) {
      user = await this.createUser(phone)
    }

    const token = generateToken({
      userId: user.id,
      phone: user.phone,
      type: 'user'
    })

    logger.info(`User ${phone} logged in successfully`)

    return { user, token }
  }

  async findByPhone(phone: string): Promise<User | null> {
    const pool = getPool()
    const [rows] = await pool.query(
      'SELECT * FROM users WHERE phone = ?',
      [phone]
    )
    
    const users = rows as User[]
    return users.length > 0 ? users[0] : null
  }

  async findById(id: number): Promise<User | null> {
    const pool = getPool()
    const [rows] = await pool.query(
      'SELECT * FROM users WHERE id = ?',
      [id]
    )
    
    const users = rows as User[]
    return users.length > 0 ? users[0] : null
  }

  async createUser(phone: string): Promise<User> {
    const pool = getPool()
    
    const [result] = await pool.query(
      'INSERT INTO users (phone, status) VALUES (?, 1)',
      [phone]
    )
    
    const insertResult = result as any
    const user = await this.findById(insertResult.insertId)
    
    if (!user) {
      throw new Error('Failed to create user')
    }

    logger.info(`New user created: ${phone}`)

    return user
  }

  async getUserInfo(userId: number): Promise<User> {
    const user = await this.findById(userId)
    
    if (!user) {
      throw new Error('用户不存在')
    }

    return user
  }
}

export const userService = new UserService()
