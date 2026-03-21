import express from 'express'
import baziCalculator from '../engines/baziCalculator.js'

const router = express.Router()

router.post('/calculate', async (req, res, next) => {
  try {
    const { year, month, day, hour, gender } = req.body
    
    if (!year || !month || !day) {
      return res.status(400).json({
        code: 400,
        message: '出生日期信息不完整'
      })
    }
    
    const analysis = baziCalculator.getFullAnalysis(
      parseInt(year),
      parseInt(month),
      parseInt(day),
      parseInt(hour) || 12
    )
    
    res.json({
      code: 200,
      message: 'success',
      data: {
        ...analysis,
        gender: gender || 0
      }
    })
  } catch (error) {
    next(error)
  }
})

export default router
