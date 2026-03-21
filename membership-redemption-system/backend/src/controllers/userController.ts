import { Router, Request, Response, NextFunction } from 'express'
import Joi from 'joi'
import { userService } from '../services/userService'
import { userAuth } from '../middleware/userAuth'
import { validateBody } from '../middleware/validator'
import { success, error, ErrorCodes } from '../utils/response'

const router = Router()

const sendCodeSchema = Joi.object({
  phone: Joi.string().pattern(/^1[3-9]\d{9}$/).required().messages({
    'string.pattern.base': '手机号格式不正确'
  })
})

const loginSchema = Joi.object({
  phone: Joi.string().pattern(/^1[3-9]\d{9}$/).required(),
  code: Joi.string().length(6).required()
})

router.post('/send-code',
  validateBody(sendCodeSchema),
  async (req: Request, res: Response, next: NextFunction) => {
    try {
      const { phone } = req.body
      await userService.sendVerifyCode(phone)
      res.json(success({ message: '验证码已发送' }))
    } catch (err: any) {
      if (err.message.includes('频繁') || err.message.includes('已发送')) {
        res.status(400).json(error(ErrorCodes.SYSTEM_ERROR, err.message))
      } else {
        next(err)
      }
    }
  }
)

router.post('/login',
  validateBody(loginSchema),
  async (req: Request, res: Response, next: NextFunction) => {
    try {
      const { phone, code } = req.body
      const { user, token } = await userService.loginWithVerifyCode(phone, code)
      res.json(success({
        user: {
          id: user.id,
          phone: user.phone,
          status: user.status
        },
        token
      }))
    } catch (err: any) {
      if (err.message.includes('验证码')) {
        res.status(400).json(error(ErrorCodes.VERIFY_CODE_ERROR, err.message))
      } else {
        next(err)
      }
    }
  }
)

router.get('/info',
  userAuth,
  async (req: Request, res: Response, next: NextFunction) => {
    try {
      const user = await userService.getUserInfo(req.user!.userId)
      res.json(success({
        id: user.id,
        phone: user.phone,
        status: user.status,
        createdAt: user.createdAt
      }))
    } catch (err: any) {
      res.status(404).json(error(ErrorCodes.AUTH_ERROR, err.message))
    }
  }
)

router.post('/logout',
  userAuth,
  async (req: Request, res: Response) => {
    res.json(success({ message: '退出登录成功' }))
  }
)

export default router
