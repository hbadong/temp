import express from 'express'
import almanacCalculator from '../engines/almanacCalculator.js'

const router = express.Router()

router.get('/today', async (req, res) => {
  try {
    const today = new Date()
    const dateStr = today.toISOString().split('T')[0]
    const data = almanacCalculator.calculate(dateStr)
    
    res.json({
      code: 200,
      message: 'success',
      data
    })
  } catch (error) {
    res.status(500).json({
      code: 500,
      message: '计算黄历数据失败'
    })
  }
})

router.get('/date/:date', async (req, res) => {
  try {
    const { date } = req.params
    
    if (!/^\d{4}-\d{2}-\d{2}$/.test(date)) {
      return res.status(400).json({
        code: 400,
        message: '日期格式不正确，应为 YYYY-MM-DD'
      })
    }
    
    const data = almanacCalculator.calculate(date)
    
    res.json({
      code: 200,
      message: 'success',
      data
    })
  } catch (error) {
    res.status(500).json({
      code: 500,
      message: '计算黄历数据失败'
    })
  }
})

export default router
