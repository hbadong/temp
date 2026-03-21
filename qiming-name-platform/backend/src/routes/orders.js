import express from 'express'
import { query } from '../config/database.js'
import { v4 as uuidv4 } from 'uuid'

const router = express.Router()

router.get('/list', async (req, res, next) => {
  try {
    const { status, page = 1, pageSize = 20 } = req.query
    
    let sql = 'SELECT * FROM orders WHERE 1=1'
    const params = []
    
    if (status) {
      sql += ' AND status = ?'
      params.push(parseInt(status))
    }
    
    sql += ' ORDER BY created_at DESC LIMIT ? OFFSET ?'
    params.push(parseInt(pageSize), (parseInt(page) - 1) * parseInt(pageSize))
    
    const results = await query(sql, params)
    
    const countSql = 'SELECT COUNT(*) as total FROM orders' + (status ? ' WHERE status = ?' : '')
    const countResult = await query(countSql, status ? [parseInt(status)] : [])
    
    res.json({
      code: 200,
      message: 'success',
      data: {
        items: results,
        total: countResult[0].total,
        page: parseInt(page),
        pageSize: parseInt(pageSize),
        totalPages: Math.ceil(countResult[0].total / parseInt(pageSize))
      }
    })
  } catch (error) {
    next(error)
  }
})

router.get('/:id', async (req, res, next) => {
  try {
    const { id } = req.params
    
    const results = await query('SELECT * FROM orders WHERE id = ?', [id])
    
    if (results.length === 0) {
      return res.status(404).json({
        code: 404,
        message: '订单不存在'
      })
    }
    
    res.json({
      code: 200,
      message: 'success',
      data: results[0]
    })
  } catch (error) {
    next(error)
  }
})

router.post('/create', async (req, res, next) => {
  try {
    const { serviceType, serviceName, userName, userGender, userBirthDate, userBirthTime, requirements } = req.body
    
    const orderNo = 'ORD' + Date.now() + uuidv4().slice(0, 8).toUpperCase()
    
    const priceMap = {
      'bazi': 199,
      'shici': 129,
      'zhouyi': 299,
      'company': 999,
      'normal': 99
    }
    
    const price = priceMap[serviceType] || 99
    
    const result = await query(
      `INSERT INTO orders (order_no, user_id, service_type, service_name, price, actual_price, user_name, user_gender, user_birth_date, user_birth_time, requirements, status) 
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)`,
      [orderNo, 0, serviceType, serviceName, price, price, userName, userGender, userBirthDate, userBirthTime, requirements]
    )
    
    res.status(201).json({
      code: 201,
      message: '订单创建成功',
      data: {
        orderId: result.insertId,
        orderNo
      }
    })
  } catch (error) {
    next(error)
  }
})

router.put('/:id/status', async (req, res, next) => {
  try {
    const { id } = req.params
    const { status } = req.body
    
    if (![1, 2, 3, 4, 5, 6].includes(status)) {
      return res.status(400).json({
        code: 400,
        message: '无效的订单状态'
      })
    }
    
    await query('UPDATE orders SET status = ? WHERE id = ?', [status, id])
    
    res.json({
      code: 200,
      message: '状态更新成功'
    })
  } catch (error) {
    next(error)
  }
})

router.post('/:id/pay', async (req, res, next) => {
  try {
    const { id } = req.params
    
    await query(
      'UPDATE orders SET status = 2, payment_time = NOW(), payment_method = ? WHERE id = ?',
      ['wechat', id]
    )
    
    res.json({
      code: 200,
      message: '支付成功'
    })
  } catch (error) {
    next(error)
  }
})

export default router
