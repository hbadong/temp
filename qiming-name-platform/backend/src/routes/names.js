import express from 'express'
import scoringEngine from '../engines/scoringEngine.js'
import recommendEngine from '../engines/recommendEngine.js'
import { query } from '../config/database.js'

const router = express.Router()

router.get('/search', async (req, res, next) => {
  try {
    const {
      surname,
      gender,
      birthDate,
      birthTime,
      firstElement,
      lastElement,
      page = 1,
      pageSize = 20
    } = req.query
    
    let sql = 'SELECT * FROM names WHERE status = 1'
    const params = []
    
    if (surname) {
      sql += ' AND surname = ?'
      params.push(surname)
    }
    
    if (gender) {
      sql += ' AND (gender = ? OR gender = 0)'
      params.push(parseInt(gender))
    }
    
    if (firstElement) {
      sql += ' AND five_element = ?'
      params.push(firstElement)
    }
    
    sql += ' ORDER BY total_score DESC, usage_count DESC'
    sql += ' LIMIT ? OFFSET ?'
    params.push(parseInt(pageSize), (parseInt(page) - 1) * parseInt(pageSize))
    
    const results = await query(sql, params)
    
    const countSql = 'SELECT COUNT(*) as total FROM names WHERE status = 1' + 
      (surname ? ' AND surname = ?' : '') +
      (gender ? ' AND (gender = ? OR gender = 0)' : '') +
      (firstElement ? ' AND five_element = ?' : '')
    const countParams = [surname, gender, firstElement].filter(Boolean)
    const countResult = await query(countSql, countParams)
    
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

router.get('/detail/:id', async (req, res, next) => {
  try {
    const { id } = req.params
    
    const results = await query('SELECT * FROM names WHERE id = ? AND status = 1', [id])
    
    if (results.length === 0) {
      return res.status(404).json({
        code: 404,
        message: '名字不存在'
      })
    }
    
    const name = results[0]
    
    res.json({
      code: 200,
      message: 'success',
      data: name
    })
  } catch (error) {
    next(error)
  }
})

router.get('/ranks', async (req, res, next) => {
  try {
    const { type = 'popular', gender, month } = req.query
    
    let orderBy = 'usage_count DESC'
    if (type === 'score') {
      orderBy = 'total_score DESC'
    }
    
    const sql = `SELECT * FROM names WHERE status = 1 AND is_popular = 1` +
      (gender ? ' AND gender = ?' : '') +
      ` ORDER BY ${orderBy} LIMIT 30`
    
    const results = await query(sql, gender ? [gender] : [])
    
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
    const { gender } = req.query
    
    const sql = `SELECT * FROM names WHERE status = 1` +
      (gender ? ' AND gender = ?' : '') +
      ` ORDER BY usage_count DESC LIMIT 20`
    
    const results = await query(sql, gender ? [gender] : [])
    
    res.json({
      code: 200,
      message: 'success',
      data: results
    })
  } catch (error) {
    next(error)
  }
})

router.post('/test', async (req, res, next) => {
  try {
    const { name, surname, givenName, gender, birthDate, birthTime } = req.body
    
    if (!name || !surname || !givenName) {
      return res.status(400).json({
        code: 400,
        message: '姓名信息不完整'
      })
    }
    
    const result = await scoringEngine.scoreName(name, surname, givenName, gender, birthDate, birthTime)
    
    res.json({
      code: 200,
      message: 'success',
      data: result
    })
  } catch (error) {
    next(error)
  }
})

router.post('/recommend', async (req, res, next) => {
  try {
    const {
      surname,
      gender,
      birthDate,
      birthTime,
      firstElement,
      lastElement,
      expectTags,
      poetryStyle,
      excludeNames,
      page = 1,
      pageSize = 20
    } = req.body
    
    const result = await recommendEngine.recommend({
      surname,
      gender: parseInt(gender),
      birthDate,
      birthTime,
      fiveElementPreference: { first: firstElement, last: lastElement },
      expectTags,
      poetryStyle,
      excludeNames,
      page: parseInt(page),
      pageSize: parseInt(pageSize)
    })
    
    res.json({
      code: 200,
      message: 'success',
      data: result
    })
  } catch (error) {
    next(error)
  }
})

export default router
