export const config = {
  port: process.env.PORT || 8080,
  nodeEnv: process.env.NODE_ENV || 'development',
  
  jwt: {
    secret: process.env.JWT_SECRET || 'qiming-secret-key-2024',
    expiresIn: '24h',
    refreshExpiresIn: '7d'
  },
  
  bcrypt: {
    saltRounds: 12
  },
  
  database: {
    host: process.env.DB_HOST || 'localhost',
    port: process.env.DB_PORT || 3306,
    user: process.env.DB_USER || 'root',
    password: process.env.DB_PASSWORD || '',
    database: process.env.DB_NAME || 'qiming_db'
  },
  
  redis: {
    host: process.env.REDIS_HOST || 'localhost',
    port: process.env.REDIS_PORT || 6379,
    password: process.env.REDIS_PASSWORD || ''
  },
  
  cache: {
    userSession: 86400,
    nameDetail: 604800,
    baziChart: 2592000,
    almanac: 86400,
    hotNames: 3600,
    searchSuggest: 300,
    articleList: 600
  },
  
  pagination: {
    defaultPageSize: 20,
    maxPageSize: 100
  }
}
