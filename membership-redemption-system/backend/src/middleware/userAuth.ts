import { Request, Response, NextFunction } from 'express'
import { verifyToken, TokenPayload } from '../utils/jwt'
import { error, ErrorCodes } from '../utils/response'

declare global {
  namespace Express {
    interface Request {
      user?: TokenPayload
    }
  }
}

export function userAuth(req: Request, res: Response, next: NextFunction): void {
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
    const payload = verifyToken(token)
    
    if (payload.type !== 'user') {
      res.status(401).json(error(ErrorCodes.AUTH_ERROR, '无效的用户令牌'))
      return
    }

    req.user = payload
    next()
  } catch (err) {
    res.status(401).json(error(ErrorCodes.TOKEN_EXPIRED, '令牌已过期'))
  }
}
