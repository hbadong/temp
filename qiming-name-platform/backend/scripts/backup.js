#!/usr/bin/env node

import mysql from 'mysql2/promise'
import fs from 'fs'
import path from 'path'
import { fileURLToPath } from 'url'
import dayjs from 'dayjs'

const __filename = fileURLToPath(import.meta.url)
const __dirname = path.dirname(__filename)

const config = {
  host: process.env.DB_HOST || 'localhost',
  port: process.env.DB_PORT || 3306,
  user: process.env.DB_USER || 'root',
  password: process.env.DB_PASSWORD || '',
  database: process.env.DB_NAME || 'qiming_db'
}

const tables = [
  'users',
  'names',
  'name_favorites',
  'name_records',
  'bazi_charts',
  'orders',
  'articles',
  'article_comments',
  'surnames',
  'surname_names',
  'poems',
  'poem_sentences',
  'kanxi_dict',
  'five_elements_dict',
  'admins',
  'admin_logs'
]

console.log('===== 起名平台数据库备份脚本 =====')
console.log('配置:', { host: config.host, database: config.database })

async function connect() {
  try {
    const connection = await mysql.createConnection(config)
    console.log('数据库连接成功')
    return connection
  } catch (error) {
    console.error('数据库连接失败:', error.message)
    throw error
  }
}

async function backupDatabase(connection) {
  const backupDir = path.join(__dirname, `../backups/${dayjs().format('YYYYMMDD_HHmmss')}`)
  
  if (!fs.existsSync(backupDir)) {
    fs.mkdirSync(backupDir, { recursive: true })
  }

  console.log('\n开始备份数据库...')
  
  for (const table of tables) {
    await backupTable(connection, table, backupDir)
  }
  
  await backupFullSql(connection, backupDir)
  
  console.log('\n数据库备份完成!')
  console.log('备份目录:', backupDir)
  
  return backupDir
}

async function backupTable(connection, table, backupDir) {
  try {
    const [rows] = await connection.execute(`SELECT * FROM ${table}`)
    
    const data = {
      table,
      exportTime: new Date().toISOString(),
      total: rows.length,
      data: rows
    }
    
    const filePath = path.join(backupDir, `${table}.json`)
    fs.writeFileSync(filePath, JSON.stringify(data, null, 2))
    
    console.log(`[OK] ${table}: ${rows.length} 条`)
  } catch (error) {
    console.log(`[SKIP] ${table}: ${error.message}`)
  }
}

async function backupFullSql(connection, backupDir) {
  try {
    let sql = `-- 起名平台数据库备份\n`
    sql += `-- 备份时间: ${new Date().toISOString()}\n`
    sql += `-- 数据库: ${config.database}\n\n`
    
    for (const table of tables) {
      const [rows] = await connection.execute(`SELECT * FROM ${table}`)
      
      if (rows.length > 0) {
        sql += `\n-- 表: ${table} (${rows.length} 条)\n`
        
        for (const row of rows) {
          const columns = Object.keys(row)
          const values = columns.map(col => {
            const val = row[col]
            if (val === null) return 'NULL'
            if (typeof val === 'string') return `'${val.replace(/'/g, "''")}'`
            if (typeof val === 'object' && val instanceof Date) return `'${val.toISOString()}'`
            return String(val)
          })
          
          sql += `INSERT INTO ${table} (${columns.join(', ')}) VALUES (${values.join(', ')});\n`
        }
      }
    }
    
    const sqlPath = path.join(backupDir, 'backup.sql')
    fs.writeFileSync(sqlPath, sql)
    
    console.log(`\n[OK] 完整SQL文件: ${sqlPath}`)
  } catch (error) {
    console.log(`[SKIP] SQL导出: ${error.message}`)
  }
}

async function restoreDatabase(connection, backupDir) {
  console.log('\n开始恢复数据库...')
  
  const files = fs.readdirSync(backupDir).filter(f => f.endsWith('.json'))
  
  for (const file of files) {
    const table = file.replace('.json', '')
    await restoreTable(connection, table, path.join(backupDir, file))
  }
  
  console.log('\n数据库恢复完成!')
}

async function restoreTable(connection, table, filePath) {
  try {
    const content = fs.readFileSync(filePath, 'utf8')
    const { data } = JSON.parse(content)
    
    await connection.execute(`DELETE FROM ${table}`)
    
    if (data.length > 0) {
      for (const row of data) {
        const columns = Object.keys(row)
        const values = columns.map(() => '?')
        
        await connection.execute(
          `INSERT INTO ${table} (${columns.join(', ')}) VALUES (${values.join(', ')})`,
          Object.values(row)
        )
      }
    }
    
    console.log(`[OK] ${table}: ${data.length} 条`)
  } catch (error) {
    console.log(`[FAIL] ${table}: ${error.message}`)
  }
}

async function main() {
  const action = process.argv[2] || 'backup'
  let connection
  
  try {
    connection = await connect()
    
    if (action === 'backup') {
      await backupDatabase(connection)
    } else if (action === 'restore') {
      const backupDir = process.argv[3]
      if (!backupDir) {
        console.error('请指定备份目录: node backup.js restore <backup_dir>')
        return
      }
      await restoreDatabase(connection, backupDir)
    } else {
      console.log('用法:')
      console.log('  node backup.js backup    - 备份数据库')
      console.log('  node backup.js restore <dir> - 恢复数据库')
    }
  } catch (error) {
    console.error('执行出错:', error)
  } finally {
    if (connection) {
      await connection.end()
      console.log('\n数据库连接已关闭')
    }
  }
}

main()
