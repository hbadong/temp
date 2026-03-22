import dotenv from 'dotenv'

dotenv.config()

export const config = {
  db: {
    host: process.env.DB_HOST || 'localhost',
    port: parseInt(process.env.DB_PORT || '3306'),
    user: process.env.DB_USER || 'root',
    password: process.env.DB_PASSWORD || '',
    database: process.env.DB_NAME || 'membership_redemption',
    connectionLimit: 10,
    waitForConnections: true,
    queueLimit: 0
  },
  redis: {
    host: process.env.REDIS_HOST || 'localhost',
    port: parseInt(process.env.REDIS_PORT || '6379'),
    password: process.env.REDIS_PASSWORD || undefined
  },
  jwt: {
    secret: process.env.JWT_SECRET,
    expiresIn: process.env.JWT_EXPIRES_IN || '24h'
  },
  sms: {
    accessKeyId: process.env.ALIYUN_ACCESS_KEY_ID || '',
    accessKeySecret: process.env.ALIYUN_ACCESS_KEY_SECRET || '',
    signName: process.env.ALIYUN_SMS_SIGN_NAME || '会员兑换系统',
    templateCode: process.env.ALIYUN_SMS_TEMPLATE_CODE || ''
  },
  platform: {
    iqiyi: {
      appId: process.env.IQIYI_APP_ID || '',
      appKey: process.env.IQIYI_APP_KEY || '',
      apiUrl: process.env.IQIYI_API_URL || ''
    },
    youku: {
      appId: process.env.YOUKU_APP_ID || '',
      appKey: process.env.YOUKU_APP_KEY || '',
      apiUrl: process.env.YOUKU_API_URL || ''
    },
    tencent: {
      appId: process.env.TENCENT_APP_ID || '',
      appKey: process.env.TENCENT_APP_KEY || '',
      apiUrl: process.env.TENCENT_API_URL || ''
    }
  },
  server: {
    port: parseInt(process.env.PORT || '8000'),
    env: process.env.NODE_ENV || 'development'
  },
  log: {
    level: process.env.LOG_LEVEL || 'info',
    dir: process.env.LOG_DIR || './logs'
  }
}
