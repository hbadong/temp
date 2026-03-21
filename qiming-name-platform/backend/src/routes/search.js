import express from 'express'

const router = express.Router()

router.get('/suggest', async (req, res) => {
  const { q } = req.query
  res.json({
    code: 200,
    message: 'success',
    data: []
  })
})

export default router
