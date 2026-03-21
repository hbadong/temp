import jwt from 'jsonwebtoken'
import { config } from '../config'

export interface TokenPayload {
  userId: number
  phone: string
  type: 'user' | 'admin'
}

export interface AdminTokenPayload extends TokenPayload {
  adminId: number
  role: 'super_admin' | 'admin'
}

export function generateToken(payload: TokenPayload): string {
  return jwt.sign(payload, config.jwt.secret, {
    expiresIn: config.jwt.expiresIn
  })
}

export function verifyToken(token: string): TokenPayload {
  return jwt.verify(token, config.jwt.secret) as TokenPayload
}

export function generateAdminToken(payload: AdminTokenPayload): string {
  return jwt.sign(payload, config.jwt.secret, {
    expiresIn: config.jwt.expiresIn
  })
}

export function verifyAdminToken(token: string): AdminTokenPayload {
  return jwt.verify(token, config.jwt.secret) as AdminTokenPayload
}
