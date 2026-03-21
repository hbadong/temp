import { getPool } from '../config/database'
import { getRedis } from '../config/database'
import { logger } from '../utils/logger'
import { v4 as uuidv4 } from 'uuid'
import { error, ErrorCodes, success } from '../utils/response'

const VERIFY_CODE_LENGTH = 6
const VERIFY_CODE_EXPIRE_SECONDS = 300
const VERIFY_CODE_MAX_ATTEMPTS = 3
const VERIFY_CODE_LOCK_SECONDS = 300

export class SmsService {
  async sendVerifyCode(phone: string): Promise<void> {
    const redis = getRedis()
    
    const lockKey = `sms:lock:${phone}`
    const locked = await redis.get(lockKey)
    if (locked) {
      throw new Error('短信发送过于频繁，请稍后再试')
    }

    const code = this.generateVerifyCode()
    const codeKey = `verify:code:${phone}`
    
    const existingCode = await redis.get(codeKey)
    if (existingCode) {
      throw new Error('验证码已发送，请稍后再试')
    }

    await redis.setex(codeKey, VERIFY_CODE_EXPIRE_SECONDS, code)
    await redis.setex(lockKey, 60, '1')

    logger.info(`Verify code for ${phone}: ${code}`)

    await this.saveSmsLog(phone, 'verify_code', `您的验证码为：${code}，5分钟内有效`)
  }

  async verifyCode(phone: string, code: string): Promise<boolean> {
    const redis = getRedis()
    const codeKey = `verify:code:${phone}`
    const attemptKey = `verify:attempt:${phone}`
    const lockKey = `verify:lock:${phone}`

    const locked = await redis.get(lockKey)
    if (locked) {
      throw new Error('验证码验证已锁定，请5分钟后再试')
    }

    const storedCode = await redis.get(codeKey)
    if (!storedCode) {
      throw new Error('验证码已过期，请重新获取')
    }

    const attempts = parseInt(await redis.get(attemptKey) || '0')
    if (attempts >= VERIFY_CODE_MAX_ATTEMPTS) {
      await redis.setex(lockKey, VERIFY_CODE_LOCK_SECONDS, '1')
      throw new Error('验证码错误次数过多，请5分钟后再试')
    }

    if (storedCode !== code) {
      await redis.incr(attemptKey)
      if (attempts + 1 >= VERIFY_CODE_MAX_ATTEMPTS) {
        await redis.setex(lockKey, VERIFY_CODE_LOCK_SECONDS, '1')
      }
      throw new Error('验证码错误')
    }

    await redis.del(codeKey)
    await redis.del(attemptKey)

    return true
  }

  async sendNotification(phone: string, message: string): Promise<void> {
    await this.saveSmsLog(phone, 'notification', message)
  }

  private generateVerifyCode(): string {
    let code = ''
    for (let i = 0; i < VERIFY_CODE_LENGTH; i++) {
      code += Math.floor(Math.random() * 10)
    }
    return code
  }

  private async saveSmsLog(phone: string, type: string, content: string): Promise<void> {
    try {
      const pool = getPool()
      await pool.query(
        `INSERT INTO sms_logs (phone, type, template_code, content, status, send_at)
         VALUES (?, ?, ?, ?, 1, NOW())`,
        [phone, type, 'TEMPLATE_VERIFY_CODE', content]
      )
    } catch (err) {
      logger.error('Failed to save SMS log:', err)
    }
  }
}

export const smsService = new SmsService()
