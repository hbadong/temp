import express from 'express'
import cors from 'cors'
import helmet from 'helmet'
import { config } from './config'
import { initDatabase, initRedis } from './config/database'
import { logger } from './utils/logger'
import userRoutes from './routes/user'
import adminRoutes from './routes/admin'
import { userApiLimiter, adminApiLimiter } from './middleware/rateLimiter'

const app = express()

app.use(helmet())
app.use(cors())
app.use(express.json())
app.use(express.urlencoded({ extended: true }))

app.use('/api/v1/user', userApiLimiter, userRoutes)
app.use('/api/v1/products', userApiLimiter, userRoutes)
app.use('/api/v1/admin', adminApiLimiter, adminRoutes)

app.get('/health', (req, res) => {
  res.json({ status: 'ok', timestamp: new Date().toISOString() })
})

app.use((err: any, req: express.Request, res: express.Response, next: express.NextFunction) => {
  logger.error('Unhandled error:', err)
  res.status(500).json({
    code: 50001,
    message: '系统繁忙，请稍后再试',
    timestamp: Date.now()
  })
})

async function startServer() {
  try {
    if (!config.jwt.secret) {
      throw new Error('JWT_SECRET environment variable is required')
    }
    
    await initDatabase()
    await initRedis()
    
    app.listen(config.server.port, '0.0.0.0', () => {
      logger.info(`Server is running on port ${config.server.port}`)
    })
  } catch (error) {
    logger.error('Failed to start server:', error)
    process.exit(1)
  }
}

startServer()

export default app
