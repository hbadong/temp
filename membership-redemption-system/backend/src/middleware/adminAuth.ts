import { Request, Response, NextFunction } from 'express'
import { verifyAdminToken, AdminTokenPayload } from '../utils/jwt'
import { error, ErrorCodes } from '../utils/response'

declare global {
  namespace Express {
    interface Request {
      admin?: AdminTokenPayload
    }
  }
}

export function adminAuth(req: Request, res: Response, next: NextFunction): void {
  const authHeader = req.headers.authorization

  if (!authHeader) {
    res.status(401).json(error(ErrorCodes.AUTH_ERROR, '请先登录'))
    return
  }

  const parts = authHeader.split(' ')
  if (parts.length !== 2 || parts[0] !== 'Bearer') {
    res.status(401).json(error(ErrorCodes.AUTH_ERROR, '令牌格式错误'))
    return
  }

  const token = parts[1]

  try {
    const payload = verifyAdminToken(token)
    
    if (payload.type !== 'admin') {
      res.status(401).json(error(ErrorCodes.AUTH_ERROR, '无效的管理员令牌'))
      return
    }

    req.admin = payload
    next()
  } catch (err) {
    res.status(401).json(error(ErrorCodes.TOKEN_EXPIRED, '令牌已过期'))
  }
}

export function requireSuperAdmin(req: Request, res: Response, next: NextFunction): void {
  if (!req.admin) {
    res.status(401).json(error(ErrorCodes.AUTH_ERROR, '请先登录'))
    return
  }

  if (req.admin.role !== 'super_admin') {
    res.status(403).json(error(ErrorCodes.AUTH_ERROR, '需要超级管理员权限'))
    return
  }

  next()
}
