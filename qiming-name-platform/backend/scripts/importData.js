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
  database: process.env.DB_NAME || 'qiming_db',
  multipleStatements: true
}

console.log('===== 起名平台数据导入脚本 =====')
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

async function importData(connection) {
  console.log('\n开始导入数据...')

  const jsonDir = path.join(__dirname, '../data')
  
  if (!fs.existsSync(jsonDir)) {
    fs.mkdirSync(jsonDir, { recursive: true })
  }

  await importNames(connection)
  await importSurnames(connection)
  await importKanxiDict(connection)
  await importPoems(connection)
  await importArticles(connection)
  
  console.log('\n数据导入完成!')
}

async function importNames(connection) {
  console.log('\n[1/5] 导入名字库...')
  
  const names = generateSampleNames()
  
  let imported = 0
  for (const name of names) {
    try {
      await connection.execute(
        `INSERT INTO names (surname, given_name, full_name, gender, pinyin, pinyin_initial, stroke_count, five_element, 
         wu_ge_tian, wu_ge_di, wu_ge_ren, wu_ge_wai, wu_ge_zong, wu_ge_lucky,
         shape_score, sound_score, meaning_score, wu_xing_score, total_score, meaning, source_type, is_popular, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE usage_count = VALUES(usage_count)`,
        [
          name.surname, name.given_name, name.full_name, name.gender, name.pinyin, name.pinyin_initial,
          name.stroke_count, name.five_element, name.wu_ge_tian, name.wu_ge_di, name.wu_ge_ren,
          name.wu_ge_wai, name.wu_ge_zong, name.wu_ge_lucky, name.shape_score, name.sound_score,
          name.meaning_score, name.wu_xing_score, name.total_score, name.meaning, name.source_type,
          name.is_popular || 0, name.status || 1
        ]
      )
      imported++
    } catch (error) {
      console.error(`导入失败: ${name.full_name}`, error.message)
    }
  }
  
  console.log(`名字库导入完成: ${imported}/${names.length} 条`)
}

function generateSampleNames() {
  const surnames = ['李', '王', '张', '刘', '陈', '杨', '赵', '黄', '周', '吴', '徐', '孙', '胡', '朱', '高']
  const boyNames = [
    { name: '俊豪', pinyin: 'jun hao', element: '火' },
    { name: '煜晨', pinyin: 'yu chen', element: '火' },
    { name: '铭轩', pinyin: 'ming xuan', element: '金' },
    { name: '梓翔', pinyin: 'zi xiang', element: '木' },
    { name: '昊然', pinyin: 'hao ran', element: '火' },
    { name: '思远', pinyin: 'si yuan', element: '土' },
    { name: '文博', pinyin: 'wen bo', element: '水' },
    { name: '家瑞', pinyin: 'jia rui', element: '金' },
    { name: '宇航', pinyin: 'yu hang', element: '土' },
    { name: '志豪', pinyin: 'zhi hao', element: '火' }
  ]
  const girlNames = [
    { name: '欣怡', pinyin: 'xin yi', element: '金' },
    { name: '梓涵', pinyin: 'zi han', element: '木' },
    { name: '雨桐', pinyin: 'yu tong', element: '木' },
    { name: '诗涵', pinyin: 'shi han', element: '金' },
    { name: '思琪', pinyin: 'si qi', element: '金' },
    { name: '雅婷', pinyin: 'ya ting', element: '火' },
    { name: '欣悦', pinyin: 'xin yue', element: '金' },
    { name: '梦瑶', pinyin: 'meng yao', element: '火' },
    { name: '佳怡', pinyin: 'jia yi', element: '木' },
    { name: '雪丽', pinyin: 'xue li', element: '水' }
  ]

  const names = []
  let id = 1

  for (const surname of surnames) {
    for (const boy of boyNames) {
      const fullName = surname + boy.name
      names.push({
        surname,
        given_name: boy.name,
        full_name: fullName,
        gender: 1,
        pinyin: surname.toLowerCase() + ' ' + boy.pinyin,
        pinyin_initial: boy.pinyin.split(' ').map(p => p[0].toUpperCase()).join(''),
        stroke_count: getStrokeCount(surname) + getStrokeCount(boy.name),
        five_element: boy.element,
        wu_ge_tian: getStrokeCount(surname) + 1,
        wu_ge_di: getStrokeCount(boy.name),
        wu_ge_ren: getStrokeCount(surname) + getStrokeCount(boy.name[0]),
        wu_ge_wai: getStrokeCount(surname) + getStrokeCount(boy.name) - getStrokeCount(boy.name[0]) + 1,
        wu_ge_zong: getStrokeCount(surname) + getStrokeCount(boy.name),
        wu_ge_lucky: '吉',
        shape_score: 85 + Math.floor(Math.random() * 15),
        sound_score: 80 + Math.floor(Math.random() * 15),
        meaning_score: 85 + Math.floor(Math.random() * 15),
        wu_xing_score: 80 + Math.floor(Math.random() * 15),
        total_score: 85 + Math.floor(Math.random() * 15),
        meaning: `寓意美好，吉祥如意`,
        source_type: ['bazi', 'poetry', 'zhouyi', 'custom'][Math.floor(Math.random() * 4)],
        is_popular: Math.random() > 0.7 ? 1 : 0,
        status: 1
      })
    }

    for (const girl of girlNames) {
      const fullName = surname + girl.name
      names.push({
        surname,
        given_name: girl.name,
        full_name: fullName,
        gender: 2,
        pinyin: surname.toLowerCase() + ' ' + girl.pinyin,
        pinyin_initial: girl.pinyin.split(' ').map(p => p[0].toUpperCase()).join(''),
        stroke_count: getStrokeCount(surname) + getStrokeCount(girl.name),
        five_element: girl.element,
        wu_ge_tian: getStrokeCount(surname) + 1,
        wu_ge_di: getStrokeCount(girl.name),
        wu_ge_ren: getStrokeCount(surname) + getStrokeCount(girl.name[0]),
        wu_ge_wai: getStrokeCount(surname) + getStrokeCount(girl.name) - getStrokeCount(girl.name[0]) + 1,
        wu_ge_zong: getStrokeCount(surname) + getStrokeCount(girl.name),
        wu_ge_lucky: '吉',
        shape_score: 85 + Math.floor(Math.random() * 15),
        sound_score: 80 + Math.floor(Math.random() * 15),
        meaning_score: 85 + Math.floor(Math.random() * 15),
        wu_xing_score: 80 + Math.floor(Math.random() * 15),
        total_score: 85 + Math.floor(Math.random() * 15),
        meaning: `寓意美好，温柔贤淑`,
        source_type: ['bazi', 'poetry', 'zhouyi', 'custom'][Math.floor(Math.random() * 4)],
        is_popular: Math.random() > 0.7 ? 1 : 0,
        status: 1
      })
    }
  }

  return names
}

function getStrokeCount(name) {
  let count = 0
  for (const char of name) {
    count += getCharStroke(char)
  }
  return count
}

function getCharStroke(char) {
  const strokeMap = {
    '一': 1, '二': 2, '三': 3, '四': 4, '五': 5,
    '六': 4, '七': 2, '八': 2, '九': 2, '十': 2,
    '口': 3, '日': 4, '月': 4, '木': 4, '水': 4,
    '火': 4, '土': 3, '金': 8, '人': 2, '女': 3,
    '子': 3, '宀': 3, '扌': 4, '氵': 4, '忄': 4,
    '王': 4, '玉': 5, '贝': 4, '目': 5, '石': 5,
    '田': 5, '目': 5, '白': 5, '血': 6, '矢': 5,
    '禾': 5, '穴': 5, '立': 5, '龙': 16, '言': 7,
    '走': 7, '足': 7, '车': 7, '辛': 7, '辰': 7,
    '辶': 3, '阝': 8, '酉': 7, '釒': 8, '門': 8,
    '阝': 8, '革': 9, '韦': 9, '音': 9, '頁': 9,
    '风': 9, '飛': 9, '食': 9, '首': 9, '香': 9,
    '馬': 10, '骨': 10, '高': 10, '髟': 10, '鬥': 10,
    '魚': 11, '鳥': 11, '卤': 12, '鹿': 11, '麦': 11,
    '麻': 11, '黃': 12, '黍': 12, '黑': 12, '黹': 12,
    '龍': 16, '龠': 17
  }
  return strokeMap[char] || 7
}

async function importSurnames(connection) {
  console.log('\n[2/5] 导入姓氏数据...')
  
  const surnames = [
    { surname: '王', pinyin: 'wang', origin: '出自姬姓，为王子比干之后', rank: 1, count: '1.02亿' },
    { surname: '李', pinyin: 'li', origin: '出自颛顼帝孙理利贞之后', rank: 2, count: '1.01亿' },
    { surname: '张', pinyin: 'zhang', origin: '出自黄帝孙张挥之后', rank: 3, count: '0.95亿' },
    { surname: '刘', pinyin: 'liu', origin: '出自黄帝孙刘累之后', rank: 4, count: '0.72亿' },
    { surname: '陈', pinyin: 'chen', origin: '出自妫满陈国之后', rank: 5, count: '0.63亿' },
    { surname: '杨', pinyin: 'yang', origin: '出自姬姓，晋国羊舌氏之后', rank: 6, count: '0.47亿' },
    { surname: '黄', pinyin: 'huang', origin: '出自赢姓，陆终之后', rank: 7, count: '0.32亿' },
    { surname: '赵', pinyin: 'zhao', origin: '出自赢姓，造父之后', rank: 8, count: '0.29亿' },
    { surname: '吴', pinyin: 'wu', origin: '出自姬姓，周文王之后', rank: 9, count: '0.27亿' },
    { surname: '周', pinyin: 'zhou', origin: '出自姬姓，周文王之后', rank: 10, count: '0.26亿' },
    { surname: '徐', pinyin: 'xu', origin: '出自赢姓，徐偃王之后', rank: 11, count: '0.23亿' },
    { surname: '孙', pinyin: 'sun', origin: '出自姬姓，卫康叔之后', rank: 12, count: '0.21亿' }
  ]

  let imported = 0
  for (const s of surnames) {
    try {
      await connection.execute(
        `INSERT INTO surnames (surname, pinyin, origin, population_rank, population_count)
         VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE population_rank = VALUES(population_rank)`,
        [s.surname, s.pinyin, s.origin, s.rank, s.count]
      )
      imported++
    } catch (error) {
      console.error(`导入失败: ${s.surname}`, error.message)
    }
  }
  
  console.log(`姓氏数据导入完成: ${imported}/${surnames.length} 条`)
}

async function importKanxiDict(connection) {
  console.log('\n[3/5] 导入康熙字典(示例数据)...')
  
  const chars = [
    { char: '金', radical: '金', stroke: 8, pinyin: 'jīn', element: '金', meaning: '金属' },
    { char: '木', radical: '木', stroke: 4, pinyin: 'mù', element: '木', meaning: '树木' },
    { char: '水', radical: '水', stroke: 4, pinyin: 'shuǐ', element: '水', meaning: '水流' },
    { char: '火', radical: '火', stroke: 4, pinyin: 'huǒ', element: '火', meaning: '火焰' },
    { char: '土', radical: '土', stroke: 3, pinyin: 'tǔ', element: '土', meaning: '土地' },
    { char: '李', radical: '木', stroke: 7, pinyin: 'lǐ', element: '木', meaning: '李树，姓氏' },
    { char: '王', radical: '玉', stroke: 4, pinyin: 'wáng', element: '土', meaning: '君王，姓氏' },
    { char: '张', radical: '弓', stroke: 11, pinyin: 'zhāng', element: '火', meaning: '弓弦，姓氏' },
    { char: '俊', radical: '亻', stroke: 9, pinyin: 'jùn', element: '火', meaning: '才智超群' },
    { char: '豪', radical: '豕', stroke: 14, pinyin: 'háo', element: '金', meaning: '豪迈，大气' }
  ]

  let imported = 0
  for (const c of chars) {
    try {
      await connection.execute(
        `INSERT INTO kanxi_dict (character, radical, total_stroke, pinyin, five_element, basic_meaning)
         VALUES (?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE basic_meaning = VALUES(basic_meaning)`,
        [c.char, c.radical, c.stroke, c.pinyin, c.element, c.meaning]
      )
      imported++
    } catch (error) {
      console.error(`导入失败: ${c.char}`, error.message)
    }
  }
  
  console.log(`康熙字典导入完成: ${imported}/${chars.length} 条 (示例数据)`)
}

async function importPoems(connection) {
  console.log('\n[4/5] 导入诗词数据(示例数据)...')
  
  const poems = [
    {
      title: '静夜思',
      author: '李白',
      dynasty: '唐',
      content: '床前明月光，疑是地上霜。举头望明月，低头思故乡。',
      tags: '思乡,月亮,夜晚',
      theme: '思乡'
    },
    {
      title: '春晓',
      author: '孟浩然',
      dynasty: '唐',
      content: '春眠不觉晓，处处闻啼鸟。夜来风雨声，花落知多少。',
      tags: '春天,自然',
      theme: '春天'
    },
    {
      title: '登鹳雀楼',
      author: '王之涣',
      dynasty: '唐',
      content: '白日依山尽，黄河入海流。欲穷千里目，更上一层楼。',
      tags: '登高,望远,励志',
      theme: '励志'
    }
  ]

  let imported = 0
  for (const p of poems) {
    try {
      const [result] = await connection.execute(
        `INSERT INTO poems (title, author, dynasty, content, tags, theme)
         VALUES (?, ?, ?, ?, ?, ?)`,
        [p.title, p.author, p.dynasty, p.content, p.tags, p.theme]
      )
      
      for (const sentence of p.content.split('，').filter(s => s.trim())) {
        await connection.execute(
          `INSERT INTO poem_sentences (poem_id, sentence, suitable_for_naming)
           VALUES (?, ?, 1)`,
          [result.insertId, sentence.replace(/[。]/g, '')]
        )
      }
      imported++
    } catch (error) {
      console.error(`导入失败: ${p.title}`, error.message)
    }
  }
  
  console.log(`诗词数据导入完成: ${imported}/${poems.length} 首`)
}

async function importArticles(connection) {
  console.log('\n[5/5] 导入文章数据...')
  
  const articles = [
    {
      title: '宝宝起名别跟风！8个独特技巧',
      category: 1,
      author: '清飞扬',
      summary: '大家在给宝宝起名时，往往会陷入重名误区。本文分享8个独特技巧，帮助家长给宝宝起一个独特好听的名字。',
      content: '<p>给宝宝起名是每个家庭迎接新生命的重要环节...</p>',
      isTop: 1
    },
    {
      title: '八字五行缺火的人应该怎么起名',
      category: 2,
      author: '清飞扬',
      summary: '理论上八字缺啥五行就补什么五行是错误的，但八字缺少某个五行，相对来说这个五行在整个命盘上面的力量会非常的弱。',
      content: '<p>五行学说认为...</p>',
      isTop: 0
    },
    {
      title: '如何起一个富含诗意的好名字',
      category: 3,
      author: '清飞扬',
      summary: '名字是父母送给孩子的第一份礼物，一个有诗意的名字能让孩子在人群中脱颖而出。',
      content: '<p>诗意的名字...</p>',
      isTop: 0
    }
  ]

  let imported = 0
  for (const a of articles) {
    try {
      await connection.execute(
        `INSERT INTO articles (title, category_id, author, summary, content, status, is_top, view_count, published_at)
         VALUES (?, ?, ?, ?, ?, 1, ?, 100, NOW())`,
        [a.title, a.category, a.author, a.summary, a.content, a.isTop]
      )
      imported++
    } catch (error) {
      console.error(`导入失败: ${a.title}`, error.message)
    }
  }
  
  console.log(`文章数据导入完成: ${imported}/${articles.length} 篇`)
}

async function main() {
  let connection
  
  try {
    connection = await connect()
    await importData(connection)
  } catch (error) {
    console.error('导入过程出错:', error)
  } finally {
    if (connection) {
      await connection.end()
      console.log('\n数据库连接已关闭')
    }
  }
}

main()
