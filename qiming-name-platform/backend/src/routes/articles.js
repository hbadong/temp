import express from 'express'
import { query } from '../config/database.js'

const router = express.Router()

router.get('/list', async (req, res, next) => {
  try {
    const { categoryId, status, page = 1, pageSize = 10 } = req.query
    
    let sql = 'SELECT * FROM articles WHERE 1=1'
    const params = []
    
    if (categoryId) {
      sql += ' AND category_id = ?'
      params.push(parseInt(categoryId))
    }
    
    if (status !== undefined) {
      sql += ' AND status = ?'
      params.push(parseInt(status))
    }
    
    sql += ' ORDER BY is_top DESC, published_at DESC LIMIT ? OFFSET ?'
    params.push(parseInt(pageSize), (parseInt(page) - 1) * parseInt(pageSize))
    
    const results = await query(sql, params)
    
    const countSql = 'SELECT COUNT(*) as total FROM articles' + 
      (categoryId || status !== undefined ? ' WHERE ' + [categoryId ? 'category_id = ?' : '', status !== undefined ? 'status = ?' : ''].filter(Boolean).join(' AND ') : '')
    const countResult = await query(countSql, [categoryId, status].filter(Boolean))
    
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

router.get('/categories', async (req, res, next) => {
  try {
    const results = await query('SELECT * FROM article_categories ORDER BY sort_order ASC')
    
    res.json({
      code: 200,
      message: 'success',
      data: results
    })
  } catch (error) {
    next(error)
  }
})

router.get('/:id', async (req, res, next) => {
  try {
    const { id } = req.params
    
    const results = await query('SELECT * FROM articles WHERE id = ?', [id])
    
    if (results.length === 0) {
      return res.status(404).json({
        code: 404,
        message: '文章不存在'
      })
    }
    
    await query('UPDATE articles SET view_count = view_count + 1 WHERE id = ?', [id])
    
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
    const { title, slug, categoryId, author, summary, content, coverImage, tags, isTop, isRecommend, status } = req.body
    
    const result = await query(
      `INSERT INTO articles (title, slug, category_id, author, summary, content, cover_image, tags, is_top, is_recommend, status, published_at) 
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())`,
      [title, slug || title, categoryId, author || '管理员', summary, content, coverImage, tags, isTop ? 1 : 0, isRecommend ? 1 : 0, status || 1]
    )
    
    res.status(201).json({
      code: 201,
      message: '文章创建成功',
      data: {
        articleId: result.insertId
      }
    })
  } catch (error) {
    next(error)
  }
})

router.put('/:id', async (req, res, next) => {
  try {
    const { id } = req.params
    const { title, categoryId, author, summary, content, coverImage, tags, isTop, isRecommend, status } = req.body
    
    await query(
      `UPDATE articles SET title = ?, category_id = ?, author = ?, summary = ?, content = ?, cover_image = ?, tags = ?, is_top = ?, is_recommend = ?, status = ? WHERE id = ?`,
      [title, categoryId, author, summary, content, coverImage, tags, isTop ? 1 : 0, isRecommend ? 1 : 0, status, id]
    )
    
    res.json({
      code: 200,
      message: '文章更新成功'
    })
  } catch (error) {
    next(error)
  }
})

router.delete('/:id', async (req, res, next) => {
  try {
    const { id } = req.params
    
    await query('DELETE FROM articles WHERE id = ?', [id])
    
    res.json({
      code: 200,
      message: '文章删除成功'
    })
  } catch (error) {
    next(error)
  }
})

export default router
