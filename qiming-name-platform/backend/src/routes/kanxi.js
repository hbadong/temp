import express from 'express'
import { query } from '../config/database.js'

const router = express.Router()

router.get('/search', async (req, res, next) => {
  try {
    const { char, element, radical, stroke, page = 1, pageSize = 20 } = req.query
    
    let sql = 'SELECT * FROM kanxi_dict WHERE 1=1'
    const params = []
    
    if (char) {
      sql += ' AND character = ?'
      params.push(char)
    }
    
    if (element) {
      sql += ' AND five_element = ?'
      params.push(element)
    }
    
    if (radical) {
      sql += ' AND radical = ?'
      params.push(radical)
    }
    
    if (stroke) {
      sql += ' AND total_stroke = ?'
      params.push(parseInt(stroke))
    }
    
    sql += ' ORDER BY total_stroke ASC LIMIT ? OFFSET ?'
    params.push(parseInt(pageSize), (parseInt(page) - 1) * parseInt(pageSize))
    
    const results = await query(sql, params)
    
    res.json({
      code: 200,
      message: 'success',
      data: results
    })
  } catch (error) {
    next(error)
  }
})

router.get('/detail/:char', async (req, res, next) => {
  try {
    const { char } = req.params
    
    const results = await query('SELECT * FROM kanxi_dict WHERE character = ?', [char])
    
    if (results.length === 0) {
      return res.status(404).json({
        code: 404,
        message: '汉字不存在'
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

router.get('/element/:element', async (req, res, next) => {
  try {
    const { element } = req.params
    
    const results = await query(
      'SELECT * FROM kanxi_dict WHERE five_element = ? ORDER BY total_stroke ASC',
      [element]
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

router.get('/radical/:radical', async (req, res, next) => {
  try {
    const { radical } = req.params
    
    const results = await query(
      'SELECT * FROM kanxi_dict WHERE radical = ? ORDER BY total_stroke ASC',
      [radical]
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

export default router
