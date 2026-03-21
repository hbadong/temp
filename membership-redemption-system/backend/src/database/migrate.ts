import fs from 'fs'
import path from 'path'
import mysql from 'mysql2/promise'
import { config } from '../config'
import { logger } from '../utils/logger'

async function runMigrations(): Promise<void> {
  let connection: mysql.Connection | null = null

  try {
    connection = await mysql.createConnection({
      host: config.db.host,
      port: config.db.port,
      user: config.db.user,
      password: config.db.password,
      multipleStatements: true
    })

    logger.info('Starting database migrations...')

    const schemaPath = path.join(__dirname, 'schema.sql')
    const schema = fs.readFileSync(schemaPath, 'utf-8')

    await connection.query(schema)
    logger.info('Database migrations completed successfully')

  } catch (error) {
    logger.error('Migration failed:', error)
    throw error
  } finally {
    if (connection) {
      await connection.end()
    }
  }
}

if (require.main === module) {
  runMigrations()
    .then(() => {
      process.exit(0)
    })
    .catch((error) => {
      logger.error('Migration script failed:', error)
      process.exit(1)
    })
}

export { runMigrations }
