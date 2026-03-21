import express from 'express'

const router = express.Router()

router.get('/today', async (req, res) => {
  const today = new Date()
  res.json({
    code: 200,
    message: 'success',
    data: {
      date: today.toISOString().split('T')[0],
      lunarYear: '乙巳',
      lunarMonth: '二月',
      lunarDay: '初四',
      zodiac: '蛇',
      solarTerm: '春分',
      constellation: '白羊座',
      weekday: '星期日',
      yi: ['嫁娶', '祭祀', '开光', '祈福', '求嗣', '出行'],
      ji: ['动土', '伐木', '安葬', '行丧'],
      chongSha: '丁酉',
      chongZodiac: '兔',
      luckyHours: ['子', '丑', '卯', '午'],
      caiShen: '东北',
      xiShen: '西北',
      fuShen: '西南'
    }
  })
})

export default router
