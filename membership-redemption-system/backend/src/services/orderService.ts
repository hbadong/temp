import { getPool } from '../config/database'
import { productService } from './productService'
import { cardService } from './cardService'
import { smsService } from './smsService'
import { integrationService } from './integrationService'
import { logger } from '../utils/logger'
import { v4 as uuidv4 } from 'uuid'
import { Order, Product } from '../models'

export type OrderStatus = 0 | 1 | 2 | 3
export type OrderType = 1 | 2

export class OrderService {
  async createMobileExchangeOrder(userId: number, productId: number, targetAccount: string): Promise<Order> {
    const product = await productService.findById(productId)
    if (!product) {
      throw new Error('套餐不存在')
    }

    if (product.stock <= 0) {
      throw new Error('套餐库存不足')
    }

    const pool = getPool()
    const connection = await pool.getConnection()

    try {
      await connection.beginTransaction()

      const orderNo = this.generateOrderNo()

      await productService.updateStock(productId, -1)

      const [result] = await connection.query(
        `INSERT INTO orders (order_no, user_id, product_id, type, target_account, amount, status)
         VALUES (?, ?, ?, 1, ?, ?, 0)`,
        [orderNo, userId, productId, targetAccount, product.price]
      )

      const orderId = (result as any).insertId

      await connection.commit()

      const order = await this.findById(orderId)

      if (!order) {
        throw new Error('订单创建失败')
      }

      logger.info(`Mobile exchange order created: ${orderNo}`)

      return order
    } catch (err) {
      await connection.rollback()
      throw err
    } finally {
      connection.release()
    }
  }

  async createCardRedeemOrder(userId: number, cardNo: string, targetAccount: string): Promise<Order> {
    const validation = await cardService.validateCard(cardNo)
    if (!validation.valid) {
      throw new Error(validation.error)
    }

    const card = validation.card!
    const product = await productService.findById(card.productId)
    if (!product) {
      throw new Error('套餐不存在')
    }

    const pool = getPool()
    const connection = await pool.getConnection()

    try {
      await connection.beginTransaction()

      const orderNo = this.generateOrderNo()

      await cardService.useCard(cardNo, userId)

      const [result] = await connection.query(
        `INSERT INTO orders (order_no, user_id, product_id, type, card_id, target_account, amount, status)
         VALUES (?, ?, ?, 2, ?, ?, ?, 0)`,
        [orderNo, userId, card.productId, card.id, targetAccount, product.price]
      )

      const orderId = (result as any).insertId

      await connection.commit()

      const order = await this.findById(orderId)

      if (!order) {
        throw new Error('订单创建失败')
      }

      logger.info(`Card redeem order created: ${orderNo}`)

      return order
    } catch (err) {
      await connection.rollback()
      throw err
    } finally {
      connection.release()
    }
  }

  async processOrder(orderId: number): Promise<void> {
    const order = await this.findById(orderId)

    if (!order) {
      throw new Error('订单不存在')
    }

    if (order.status !== 0) {
      throw new Error('订单状态不允许处理')
    }

    await this.updateStatus(orderId, 1)

    try {
      const result = await integrationService.rechargeMember({
        platform: order.product?.platform || '',
        account: order.targetAccount,
        durationDays: order.product?.durationDays || 0
      })

      if (result.success) {
        await this.updateStatus(orderId, 2, result.platformOrderNo)
        await smsService.sendNotification(order.targetAccount, `您已成功兑换${order.product?.name}，感谢您的使用！`)
      } else {
        await this.updateStatus(orderId, 3, undefined, result.message)
        await this.refundStock(order.productId)
      }
    } catch (err: any) {
      logger.error(`Order processing failed: ${orderId}`, err)
      await this.updateStatus(orderId, 3, undefined, err.message)
      await this.refundStock(order.productId)
    }
  }

  async updateStatus(orderId: number, status: OrderStatus, platformOrderNo?: string, failureReason?: string): Promise<void> {
    const pool = getPool()

    const updates: string[] = ['status = ?']
    const params: any[] = [status]

    if (platformOrderNo) {
      updates.push('platform_order_no = ?')
      params.push(platformOrderNo)
    }

    if (failureReason) {
      updates.push('failure_reason = ?')
      params.push(failureReason)
    }

    if (status === 2 || status === 3) {
      updates.push('processed_at = NOW()')
    }

    params.push(orderId)

    await pool.query(
      `UPDATE orders SET ${updates.join(', ')} WHERE id = ?`,
      params
    )

    logger.info(`Order ${orderId} status updated to ${status}`)
  }

  async refundStock(productId: number): Promise<void> {
    await productService.updateStock(productId, 1)
  }

  async findById(id: number): Promise<Order | null> {
    const pool = getPool()
    const [rows] = await pool.query(
      `SELECT o.*, p.platform, p.name as product_name, p.duration_days
       FROM orders o
       LEFT JOIN products p ON o.product_id = p.id
       WHERE o.id = ?`,
      [id]
    )

    const orders = rows as any[]
    return orders.length > 0 ? orders[0] : null
  }

  async findByUserId(userId: number, page: number = 1, pageSize: number = 20): Promise<{ orders: Order[]; total: number }> {
    const pool = getPool()

    const [countResult] = await pool.query(
      'SELECT COUNT(*) as total FROM orders WHERE user_id = ?',
      [userId]
    )

    const [rows] = await pool.query(
      `SELECT o.*, p.platform, p.name as product_name, p.duration_days
       FROM orders o
       LEFT JOIN products p ON o.product_id = p.id
       WHERE o.user_id = ?
       ORDER BY o.created_at DESC
       LIMIT ?, ?`,
      [userId, (page - 1) * pageSize, pageSize]
    )

    return {
      orders: rows as Order[],
      total: (countResult as any)[0].total
    }
  }

  async findAll(options: {
    orderNo?: string
    userPhone?: string
    platform?: string
    status?: number
    startDate?: Date
    endDate?: Date
    page?: number
    pageSize?: number
  }): Promise<{ orders: Order[]; total: number }> {
    const pool = getPool()
    let sql = `SELECT o.*, p.platform, p.name as product_name, p.duration_days, u.phone as user_phone
               FROM orders o
               LEFT JOIN products p ON o.product_id = p.id
               LEFT JOIN users u ON o.user_id = u.id
               WHERE 1=1`
    let countSql = 'SELECT COUNT(*) as total FROM orders o WHERE 1=1'
    const params: any[] = []

    if (options.orderNo) {
      sql += ' AND o.order_no LIKE ?'
      countSql += ' AND o.order_no LIKE ?'
      params.push(`%${options.orderNo}%`)
    }

    if (options.userPhone) {
      sql += ' AND u.phone LIKE ?'
      countSql += ' AND u.phone LIKE ?'
      params.push(`%${options.userPhone}%`)
    }

    if (options.platform) {
      sql += ' AND p.platform = ?'
      countSql += ' AND p.platform = ?'
      params.push(options.platform)
    }

    if (options.status !== undefined) {
      sql += ' AND o.status = ?'
      countSql += ' AND o.status = ?'
      params.push(options.status)
    }

    if (options.startDate) {
      sql += ' AND o.created_at >= ?'
      countSql += ' AND o.created_at >= ?'
      params.push(options.startDate)
    }

    if (options.endDate) {
      sql += ' AND o.created_at <= ?'
      countSql += ' AND o.created_at <= ?'
      params.push(options.endDate)
    }

    const page = options.page || 1
    const pageSize = options.pageSize || 20
    sql += ` ORDER BY o.created_at DESC LIMIT ${(page - 1) * pageSize}, ${pageSize}`

    const [countResult] = await pool.query(countSql, params)
    const [rows] = await pool.query(sql, params)

    return {
      orders: rows as Order[],
      total: (countResult as any)[0].total
    }
  }

  private generateOrderNo(): string {
    const date = new Date().toISOString().replace(/[-T:]/g, '').substring(0, 8)
    const random = uuidv4().replace(/-/g, '').substring(0, 8).toUpperCase()
    return `O${date}${random}`
  }
}

export const orderService = new OrderService()
