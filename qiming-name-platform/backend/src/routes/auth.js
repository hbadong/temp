import express from 'express'
import bcrypt from 'bcryptjs'
import jwt from 'jsonwebtoken'
import { v4 as uuidv4 } from 'uuid'
import { config } from '../config/index.js'
import { query } from '../config/database.js'
import { BadRequestError, UnauthorizedError, ConflictError } from '../middlewares/errorHandler.js'

const router = express.Router()

router.post('/register', async (req, res, next) => {
  try {
    const { username, password, phone, email } = req.body
    
    if (!username || !password) {
      throw new BadRequestError('用户名和密码不能为空')
    }
    
    if (password.length < 6) {
      throw new BadRequestError('密码长度不能少于6位')
    }
    
    const existingUser = await query(
      'SELECT id FROM users WHERE username = ? OR phone = ? OR email = ?',
      [username, phone || '', email || '']
    )
    
    if (existingUser.length > 0) {
      throw new ConflictError('用户名、手机号或邮箱已被注册')
    }
    
    const passwordHash = await bcrypt.hash(password, config.bcrypt.saltRounds)
    
    const result = await query(
      'INSERT INTO users (username, password_hash, phone, email, status) VALUES (?, ?, ?, ?, 1)',
      [username, passwordHash, phone || null, email || null]
    )
    
    const token = jwt.sign(
      { userId: result.insertId, username },
      config.jwt.secret,
      { expiresIn: config.jwt.expiresIn }
    )
    
    res.status(201).json({
      code: 201,
      message: '注册成功',
      data: {
        userId: result.insertId,
        username,
        token
      }
    })
  } catch (error) {
    next(error)
  }
})

router.post('/login', async (req, res, next) => {
  try {
    const { username, password } = req.body
    
    if (!username || !password) {
      throw new BadRequestError('用户名和密码不能为空')
    }
    
    const users = await query(
      'SELECT * FROM users WHERE username = ? OR phone = ? OR email = ?',
      [username, username, username]
    )
    
    if (users.length === 0) {
      await query(
        'INSERT INTO login_logs (user_id, ip, login_status, fail_reason) VALUES (0, ?, 0, ?)',
        [req.ip, '用户不存在']
      )
      throw new UnauthorizedError('用户名或密码错误')
    }
    
    const user = users[0]
    
    if (user.status === 0) {
      throw new UnauthorizedError('账户已被禁用')
    }
    
    const isPasswordValid = await bcrypt.compare(password, user.password_hash)
    
    if (!isPasswordValid) {
      await query(
        'INSERT INTO login_logs (user_id, ip, login_status, fail_reason) VALUES (?, ?, 0, ?)',
        [user.id, req.ip, '密码错误']
      )
      throw new UnauthorizedError('用户名或密码错误')
    }
    
    await query(
      'UPDATE users SET last_login_time = NOW(), last_login_ip = ? WHERE id = ?',
      [req.ip, user.id]
    )
    
    await query(
      'INSERT INTO login_logs (user_id, ip, login_status) VALUES (?, ?, 1)',
      [user.id, req.ip]
    )
    
    const token = jwt.sign(
      { userId: user.id, username: user.username },
      config.jwt.secret,
      { expiresIn: config.jwt.expiresIn }
    )
    
    res.json({
      code: 200,
      message: '登录成功',
      data: {
        userId: user.id,
        username: user.username,
        nickname: user.nickname,
        avatar: user.avatar,
        token
      }
    })
  } catch (error) {
    next(error)
  }
})

router.post('/logout', async (req, res) => {
  res.json({
    code: 200,
    message: '登出成功'
  })
})

router.get('/userinfo', async (req, res, next) => {
  try {
    const authHeader = req.headers.authorization
    if (!authHeader || !authHeader.startsWith('Bearer ')) {
      throw new UnauthorizedError('未登录')
    }
    
    const token = authHeader.split(' ')[1]
    const decoded = jwt.verify(token, config.jwt.secret)
    
    const users = await query('SELECT * FROM users WHERE id = ?', [decoded.userId])
    
    if (users.length === 0) {
      throw new UnauthorizedError('用户不存在')
    }
    
    const user = users[0]
    
    res.json({
      code: 200,
      message: 'success',
      data: {
        userId: user.id,
        username: user.username,
        nickname: user.nickname,
        avatar: user.avatar,
        phone: user.phone,
        email: user.email,
        gender: user.gender,
        birthDate: user.birth_date,
        birthTime: user.birth_time,
        status: user.status
      }
    })
  } catch (error) {
    if (error.name === 'JsonWebTokenError' || error.name === 'TokenExpiredError') {
      next(new UnauthorizedError('Token无效或已过期'))
    } else {
      next(error)
    }
  }
})

router.put('/userinfo', async (req, res, next) => {
  try {
    const authHeader = req.headers.authorization
    if (!authHeader || !authHeader.startsWith('Bearer ')) {
      throw new UnauthorizedError('未登录')
    }
    
    const token = authHeader.split(' ')[1]
    const decoded = jwt.verify(token, config.jwt.secret)
    
    const { nickname, avatar, gender, birthDate, birthTime } = req.body
    
    await query(
      'UPDATE users SET nickname = ?, avatar = ?, gender = ?, birth_date = ?, birth_time = ? WHERE id = ?',
      [nickname, avatar, gender, birthDate, birthTime, decoded.userId]
    )
    
    res.json({
      code: 200,
      message: '更新成功'
    })
  } catch (error) {
    if (error.name === 'JsonWebTokenError' || error.name === 'TokenExpiredError') {
      next(new UnauthorizedError('Token无效或已过期'))
    } else {
      next(error)
    }
  }
})

export default router
