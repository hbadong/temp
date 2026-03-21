import { Request, Response, NextFunction } from 'express'
import rateLimit from 'express-rate-limit'
import { getRedis } from '../config/database'
import { error, ErrorCodes } from '../utils/response'

export function createRateLimiter(options: {
  windowMs: number
  max: number
  keyGenerator?: (req: Request) => string
  message?: string
}) {
  const limiter = rateLimit({
    windowMs: options.windowMs,
    max: options.max,
    standardHeaders: true,
    legacyHeaders: false,
    keyGenerator: options.keyGenerator || ((req: Request) => {
      return req.ip || req.headers['x-forwarded-for'] as string || 'unknown'
    }),
    handler: (req, res) => {
      res.status(429).json(error(ErrorCodes.RATE_LIMIT_ERROR, options.message || '请求频率超限，请稍后再试'))
    }
  })

  return limiter
}

export const userApiLimiter = createRateLimiter({
  windowMs: 60 * 1000,
  max: 100,
  message: '用户端API请求频率超限，每分钟最多100次'
})

export const adminApiLimiter = createRateLimiter({
  windowMs: 60 * 1000,
  max: 60,
  message: '管理端API请求频率超限，每分钟最多60次'
})

export const smsLimiter = createRateLimiter({
  windowMs: 60 * 1000,
  max: 5,
  message: '短信发送频率超限，每分钟最多发送5次'
})

export async function checkSmsLock(phone: string): Promise<boolean> {
  const redis = getRedis()
  const lockKey = `sms:lock:${phone}`
  const locked = await redis.get(lockKey)
  return !!locked
}

export async function setSmsLock(phone: string, ttlSeconds: number = 300): Promise<void> {
  const redis = getRedis()
  const lockKey = `sms:lock:${phone}`
  await redis.setex(lockKey, ttlSeconds, '1')
}

export async function clearSmsLock(phone: string): Promise<void> {
  const redis = getRedis()
  const lockKey = `sms:lock:${phone}`
  await redis.del(lockKey)
}
