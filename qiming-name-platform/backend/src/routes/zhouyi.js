import express from 'express'

const router = express.Router()

router.get('/hexagrams', async (req, res) => {
  res.json({ code: 200, message: 'success', data: [] })
})

router.get('/hexagram/:name', async (req, res) => {
  res.json({ code: 200, message: 'success', data: {} })
})

export default router
