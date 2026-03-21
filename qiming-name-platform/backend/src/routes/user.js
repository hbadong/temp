import express from 'express'
import jwt from 'jsonwebtoken'
import { config } from '../config/index.js'
import { query } from '../config/database.js'
import { UnauthorizedError } from '../middlewares/errorHandler.js'

const router = express.Router()

function authMiddleware(req, res, next) {
  try {
    const authHeader = req.headers.authorization
    if (!authHeader || !authHeader.startsWith('Bearer ')) {
      throw new UnauthorizedError('未登录')
    }
    
    const token = authHeader.split(' ')[1]
    const decoded = jwt.verify(token, config.jwt.secret)
    req.user = decoded
    next()
  } catch (error) {
    if (error.name === 'JsonWebTokenError' || error.name === 'TokenExpiredError') {
      next(new UnauthorizedError('Token无效或已过期'))
    } else {
      next(error)
    }
  }
}

router.get('/favorites', authMiddleware, async (req, res, next) => {
  try {
    const results = await query(
      `SELECT n.* FROM name_favorites f 
       JOIN names n ON f.name_id = n.id 
       WHERE f.user_id = ?`,
      [req.user.userId]
    )
    
    res.json({
      code: 200,
      message: 'success',
      data: results
    })
  } catch (error) {
    next(error)
  }
})

router.post('/favorites', authMiddleware, async (req, res, next) => {
  try {
    const { nameId } = req.body
    
    await query(
      'INSERT INTO name_favorites (user_id, name_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE created_at = NOW()',
      [req.user.userId, nameId]
    )
    
    res.json({
      code: 200,
      message: '收藏成功'
    })
  } catch (error) {
    next(error)
  }
})

router.delete('/favorites/:id', authMiddleware, async (req, res, next) => {
  try {
    const { id } = req.params
    
    await query(
      'DELETE FROM name_favorites WHERE user_id = ? AND name_id = ?',
      [req.user.userId, id]
    )
    
    res.json({
      code: 200,
      message: '取消收藏成功'
    })
  } catch (error) {
    next(error)
  }
})

export default router
