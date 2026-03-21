import { getPool } from '../config/database'
import { encryptCardPassword, decryptCardPassword } from '../utils/crypto'
import { productService } from './productService'
import { logger } from '../utils/logger'
import { v4 as uuidv4 } from 'uuid'
import { Card, CardBatch } from '../models'
import crypto from 'crypto'

const CARD_MASTER_KEY = process.env.CARD_MASTER_KEY || 'default-card-master-key'

export class CardService {
  async createBatch(data: {
    productId: number
    prefix: string
    count: number
    validFrom: Date
    validUntil: Date
    createdBy: number
  }): Promise<{ batch: CardBatch; cards: Card[] }> {
    const pool = getPool()
    const connection = await pool.getConnection()

    try {
      await connection.beginTransaction()

      const batchNo = this.generateBatchNo()
      
      const [batchResult] = await connection.query(
        `INSERT INTO card_batches (batch_no, product_id, prefix, total_count, used_count, valid_from, valid_until, created_by)
         VALUES (?, ?, ?, ?, 0, ?, ?, ?)`,
        [
          batchNo,
          data.productId,
          data.prefix,
          data.count,
          data.validFrom,
          data.validUntil,
          data.createdBy
        ]
      )

      const batchId = (batchResult as any).insertId
      const cards: Card[] = []

      for (let i = 0; i < data.count; i++) {
        const cardNo = this.generateCardNo(data.prefix)
        const password = this.generatePassword()
        const encryptedPassword = encryptCardPassword(password, CARD_MASTER_KEY)

        await connection.query(
          `INSERT INTO cards (card_no, batch_id, password, product_id, status)
           VALUES (?, ?, ?, ?, 0)`,
          [cardNo, batchId, encryptedPassword, data.productId]
        )

        cards.push({
          id: 0,
          cardNo,
          batchId,
          password: encryptedPassword,
          productId: data.productId,
          status: 0,
          usedBy: null,
          usedAt: null,
          createdAt: new Date(),
          updatedAt: new Date()
        })
      }

      await connection.commit()

      logger.info(`Card batch created: ${batchNo}, count: ${data.count}`)

      return { batch: await this.findBatchById(batchId), cards }
    } catch (err) {
      await connection.rollback()
      throw err
    } finally {
      connection.release()
    }
  }

  async findBatchById(id: number): Promise<CardBatch | null> {
    const pool = getPool()
    const [rows] = await pool.query(
      'SELECT * FROM card_batches WHERE id = ?',
      [id]
    )
    const batches = rows as CardBatch[]
    return batches.length > 0 ? batches[0] : null
  }

  async findCardsByBatchId(batchId: number): Promise<Card[]> {
    const pool = getPool()
    const [rows] = await pool.query(
      'SELECT * FROM cards WHERE batch_id = ?',
      [batchId]
    )
    return rows as Card[]
  }

  async findByCardNo(cardNo: string): Promise<Card | null> {
    const pool = getPool()
    const [rows] = await pool.query(
      'SELECT * FROM cards WHERE card_no = ?',
      [cardNo]
    )
    const cards = rows as Card[]
    return cards.length > 0 ? cards[0] : null
  }

  async validateCard(cardNo: string): Promise<{ valid: boolean; card?: Card; error?: string }> {
    const card = await this.findByCardNo(cardNo)

    if (!card) {
      return { valid: false, error: '卡密不存在' }
    }

    if (card.status === 1) {
      return { valid: false, error: '卡密已使用' }
    }

    if (card.status === 2) {
      return { valid: false, error: '卡密已作废' }
    }

    const batch = await this.findBatchById(card.batchId)
    if (!batch) {
      return { valid: false, error: '卡密批次不存在' }
    }

    const now = new Date()
    if (now < batch.validFrom || now > batch.validUntil) {
      return { valid: false, error: '卡密已过期' }
    }

    return { valid: true, card }
  }

  async useCard(cardNo: string, userId: number): Promise<Card> {
    const card = await this.findByCardNo(cardNo)

    if (!card) {
      throw new Error('卡密不存在')
    }

    const validation = await this.validateCard(cardNo)
    if (!validation.valid) {
      throw new Error(validation.error)
    }

    const pool = getPool()
    await pool.query(
      'UPDATE cards SET status = 1, used_by = ?, used_at = NOW() WHERE id = ?',
      [userId, card.id]
    )

    await pool.query(
      'UPDATE card_batches SET used_count = used_count + 1 WHERE id = ?',
      [card.batchId]
    )

    logger.info(`Card used: ${cardNo} by user ${userId}`)

    return { ...card, status: 1, usedBy: userId, usedAt: new Date() }
  }

  async disableCard(cardId: number): Promise<void> {
    const pool = getPool()
    const [cards] = await pool.query('SELECT * FROM cards WHERE id = ?', [cardId])
    const cardArray = cards as Card[]

    if (cardArray.length === 0) {
      throw new Error('卡密不存在')
    }

    const card = cardArray[0]

    if (card.status === 1) {
      throw new Error('卡密已使用，无法作废')
    }

    await pool.query('UPDATE cards SET status = 2 WHERE id = ?', [cardId])

    logger.info(`Card disabled: ${card.cardNo}`)
  }

  async findCards(options: {
    batchId?: number
    status?: number
    cardNo?: string
    startDate?: Date
    endDate?: Date
    page?: number
    pageSize?: number
  }): Promise<{ cards: Card[]; total: number }> {
    const pool = getPool()
    let sql = 'SELECT * FROM cards WHERE 1=1'
    let countSql = 'SELECT COUNT(*) as total FROM cards WHERE 1=1'
    const params: any[] = []

    if (options.batchId) {
      sql += ' AND batch_id = ?'
      countSql += ' AND batch_id = ?'
      params.push(options.batchId)
    }

    if (options.status !== undefined) {
      sql += ' AND status = ?'
      countSql += ' AND status = ?'
      params.push(options.status)
    }

    if (options.cardNo) {
      sql += ' AND card_no LIKE ?'
      countSql += ' AND card_no LIKE ?'
      params.push(`%${options.cardNo}%`)
    }

    if (options.startDate) {
      sql += ' AND created_at >= ?'
      countSql += ' AND created_at >= ?'
      params.push(options.startDate)
    }

    if (options.endDate) {
      sql += ' AND created_at <= ?'
      countSql += ' AND created_at <= ?'
      params.push(options.endDate)
    }

    const page = options.page || 1
    const pageSize = options.pageSize || 20
    sql += ` ORDER BY created_at DESC LIMIT ${(page - 1) * pageSize}, ${pageSize}`

    const [countResult] = await pool.query(countSql, params)
    const [rows] = await pool.query(sql, params)

    return {
      cards: rows as Card[],
      total: (countResult as any)[0].total
    }
  }

  async exportCards(batchId: number): Promise<any[]> {
    const pool = getPool()
    const [rows] = await pool.query(
      `SELECT c.card_no, c.status, c.used_at, b.batch_no, b.valid_from, b.valid_until, p.name as product_name
       FROM cards c
       JOIN card_batches b ON c.batch_id = b.id
       JOIN products p ON c.product_id = p.id
       WHERE c.batch_id = ?`,
      [batchId]
    )
    return rows as any[]
  }

  private generateBatchNo(): string {
    const date = new Date()
    const dateStr = date.toISOString().replace(/[-T:]/g, '').substring(0, 8)
    const random = Math.random().toString(36).substring(2, 8).toUpperCase()
    return `B${dateStr}${random}`
  }

  private generateCardNo(prefix: string): string {
    const timestamp = Date.now().toString(36).toUpperCase()
    const random = Math.random().toString(36).substring(2, 6).toUpperCase()
    return `${prefix}${timestamp}${random}`.substring(0, 16)
  }

  private generatePassword(): string {
    return crypto.randomBytes(8).toString('hex').toUpperCase()
  }
}

export const cardService = new CardService()
