import express from 'express'
import { query } from '../config/database.js'

const router = express.Router()

router.get('/list', async (req, res, next) => {
  try {
    const { page = 1, pageSize = 20 } = req.query
    
    const results = await query(
      'SELECT * FROM surnames ORDER BY population_rank ASC LIMIT ? OFFSET ?',
      [parseInt(pageSize), (parseInt(page) - 1) * parseInt(pageSize)]
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

router.get('/popular', async (req, res, next) => {
  try {
    const { gender, page = 1, pageSize = 20 } = req.query
    
    const sql = `SELECT sn.* FROM surname_names sn
                 JOIN surnames s ON sn.surname_id = s.id
                 WHERE sn.surname = s.surname
                 ${gender ? ' AND sn.gender = ?' : ''}
                 ORDER BY sn.popularity_rank ASC
                 LIMIT ? OFFSET ?`
    
    const results = await query(sql, gender ? [gender, parseInt(pageSize), (parseInt(page) - 1) * parseInt(pageSize)] : [parseInt(pageSize), (parseInt(page) - 1) * parseInt(pageSize)])
    
    res.json({
      code: 200,
      message: 'success',
      data: results
    })
  } catch (error) {
    next(error)
  }
})

router.get('/:surname', async (req, res, next) => {
  try {
    const { surname } = req.params
    
    const results = await query('SELECT * FROM surnames WHERE surname = ?', [surname])
    
    if (results.length === 0) {
      return res.status(404).json({
        code: 404,
        message: '姓氏不存在'
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

router.get('/:surname/names', async (req, res, next) => {
  try {
    const { surname } = req.params
    const { gender, page = 1, pageSize = 20 } = req.query
    
    let sql = 'SELECT * FROM surname_names WHERE surname = ?'
    const params = [surname]
    
    if (gender) {
      sql += ' AND gender = ?'
      params.push(parseInt(gender))
    }
    
    sql += ' ORDER BY popularity_rank ASC LIMIT ? OFFSET ?'
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

export default router
