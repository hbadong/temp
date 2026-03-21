import express from 'express'
import cors from 'cors'
import helmet from 'helmet'
import morgan from 'morgan'
import dotenv from 'dotenv'
import { errorHandler } from './middlewares/errorHandler.js'
import authRoutes from './routes/auth.js'
import userRoutes from './routes/user.js'
import nameRoutes from './routes/names.js'
import baziRoutes from './routes/bazi.js'
import poemRoutes from './routes/poems.js'
import zhouyiRoutes from './routes/zhouyi.js'
import kanxiRoutes from './routes/kanxi.js'
import surnameRoutes from './routes/surnames.js'
import articleRoutes from './routes/articles.js'
import almanacRoutes from './routes/almanac.js'
import orderRoutes from './routes/orders.js'
import searchRoutes from './routes/search.js'

dotenv.config()

const app = express()
const PORT = process.env.PORT || 8080

app.use(helmet())
app.use(cors())
app.use(morgan('combined'))
app.use(express.json())
app.use(express.urlencoded({ extended: true }))

app.use('/api/v1/auth', authRoutes)
app.use('/api/v1/user', userRoutes)
app.use('/api/v1/names', nameRoutes)
app.use('/api/v1/bazi', baziRoutes)
app.use('/api/v1/poems', poemRoutes)
app.use('/api/v1/zhouyi', zhouyiRoutes)
app.use('/api/v1/kanxi', kanxiRoutes)
app.use('/api/v1/surnames', surnameRoutes)
app.use('/api/v1/articles', articleRoutes)
app.use('/api/v1/almanac', almanacRoutes)
app.use('/api/v1/orders', orderRoutes)
app.use('/api/v1/search', searchRoutes)

app.get('/health', (req, res) => {
  res.json({ status: 'ok', timestamp: new Date().toISOString() })
})

app.use(errorHandler)

app.listen(PORT, () => {
  console.log(`Server is running on port ${PORT}`)
})

export default app
