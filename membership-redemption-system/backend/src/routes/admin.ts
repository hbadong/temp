import { Router, Request, Response, NextFunction } from 'express'
import Joi from 'joi'
import { adminService } from '../services/adminService'
import { adminAuth, requireSuperAdmin } from '../middleware/adminAuth'
import { operationLogger } from '../middleware/operationLogger'
import { validateBody } from '../middleware/validator'
import { success, error, ErrorCodes } from '../utils/response'

const router = Router()

const loginSchema = Joi.object({
  username: Joi.string().required(),
  password: Joi.string().required()
})

router.post('/login',
  validateBody(loginSchema),
  async (req: Request, res: Response, next: NextFunction) => {
    try {
      const { username, password } = req.body
      const { admin, token } = await adminService.login(username, password)
      
      res.json(success({
        admin: {
          id: admin.id,
          username: admin.username,
          role: admin.role,
          lastLoginAt: admin.lastLoginAt
        },
        token
      }))
    } catch (err: any) {
      if (err.message.includes('错误') || err.message.includes('禁用') || err.message.includes('锁定')) {
        res.status(401).json(error(ErrorCodes.AUTH_ERROR, err.message))
      } else {
        next(err)
      }
    }
  }
)

router.post('/logout',
  adminAuth,
  async (req: Request, res: Response) => {
    res.json(success({ message: '退出登录成功' }))
  }
)

router.get('/profile',
  adminAuth,
  async (req: Request, res: Response, next: NextFunction) => {
    try {
      const admin = await adminService.findById(req.admin!.adminId)
      if (!admin) {
        res.status(404).json(error(ErrorCodes.AUTH_ERROR, '管理员不存在'))
        return
      }
      res.json(success({
        id: admin.id,
        username: admin.username,
        role: admin.role,
        lastLoginAt: admin.lastLoginAt
      }))
    } catch (err) {
      next(err)
    }
  }
)

export default router
