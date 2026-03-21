#!/usr/bin/env node

import mysql from 'mysql2/promise'
import fs from 'fs'
import path from 'path'
import { fileURLToPath } from 'url'

const __filename = fileURLToPath(import.meta.url)
const __dirname = path.dirname(__filename)

const config = {
  host: process.env.DB_HOST || 'localhost',
  port: process.env.DB_PORT || 3306,
  user: process.env.DB_USER || 'root',
  password: process.env.DB_PASSWORD || '',
  database: process.env.DB_NAME || 'qiming_db'
}

console.log('===== 起名平台数据导出脚本 =====')

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

async function exportData(connection) {
  const exportDir = path.join(__dirname, '../data/exports')
  
  if (!fs.existsSync(exportDir)) {
    fs.mkdirSync(exportDir, { recursive: true })
  }

  await exportNames(connection, exportDir)
  await exportSurnames(connection, exportDir)
  await exportKanxiDict(connection, exportDir)
  await exportArticles(connection, exportDir)
  
  console.log('\n数据导出完成!')
  console.log('导出目录:', exportDir)
}

async function exportNames(connection, exportDir) {
  console.log('\n[1/4] 导出名字库...')
  
  const [rows] = await connection.execute('SELECT * FROM names ORDER BY id LIMIT 10000')
  
  const data = {
    exportTime: new Date().toISOString(),
    total: rows.length,
    data: rows
  }
  
  const filePath = path.join(exportDir, `names_${Date.now()}.json`)
  fs.writeFileSync(filePath, JSON.stringify(data, null, 2))
  
  console.log(`名字库导出完成: ${rows.length} 条 -> ${filePath}`)
}

async function exportSurnames(connection, exportDir) {
  console.log('\n[2/4] 导出姓氏数据...')
  
  const [rows] = await connection.execute('SELECT * FROM surnames ORDER BY population_rank')
  
  const data = {
    exportTime: new Date().toISOString(),
    total: rows.length,
    data: rows
  }
  
  const filePath = path.join(exportDir, `surnames_${Date.now()}.json`)
  fs.writeFileSync(filePath, JSON.stringify(data, null, 2))
  
  console.log(`姓氏数据导出完成: ${rows.length} 条 -> ${filePath}`)
}

async function exportKanxiDict(connection, exportDir) {
  console.log('\n[3/4] 导出康熙字典...')
  
  const [rows] = await connection.execute('SELECT * FROM kanxi_dict LIMIT 10000')
  
  const data = {
    exportTime: new Date().toISOString(),
    total: rows.length,
    data: rows
  }
  
  const filePath = path.join(exportDir, `kanxi_${Date.now()}.json`)
  fs.writeFileSync(filePath, JSON.stringify(data, null, 2))
  
  console.log(`康熙字典导出完成: ${rows.length} 条 -> ${filePath}`)
}

async function exportArticles(connection, exportDir) {
  console.log('\n[4/4] 导出文章数据...')
  
  const [rows] = await connection.execute('SELECT * FROM articles ORDER BY id LIMIT 1000')
  
  const data = {
    exportTime: new Date().toISOString(),
    total: rows.length,
    data: rows
  }
  
  const filePath = path.join(exportDir, `articles_${Date.now()}.json`)
  fs.writeFileSync(filePath, JSON.stringify(data, null, 2))
  
  console.log(`文章数据导出完成: ${rows.length} 条 -> ${filePath}`)
}

async function main() {
  let connection
  
  try {
    connection = await connect()
    await exportData(connection)
  } catch (error) {
    console.error('导出过程出错:', error)
  } finally {
    if (connection) {
      await connection.end()
      console.log('\n数据库连接已关闭')
    }
  }
}

main()
