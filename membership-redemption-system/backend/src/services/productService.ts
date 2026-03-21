import { getPool } from '../config/database'
import { logger } from '../utils/logger'
import { Product } from '../models'

export class ProductService {
  async findAll(status?: number): Promise<Product[]> {
    const pool = getPool()
    let sql = 'SELECT * FROM products WHERE 1=1'
    const params: any[] = []

    if (status !== undefined) {
      sql += ' AND status = ?'
      params.push(status)
    }

    sql += ' ORDER BY created_at DESC'

    const [rows] = await pool.query(sql, params)
    return rows as Product[]
  }

  async findById(id: number): Promise<Product | null> {
    const pool = getPool()
    const [rows] = await pool.query(
      'SELECT * FROM products WHERE id = ?',
      [id]
    )
    
    const products = rows as Product[]
    return products.length > 0 ? products[0] : null
  }

  async findByPlatform(platform: string, status: number = 1): Promise<Product[]> {
    const pool = getPool()
    const [rows] = await pool.query(
      'SELECT * FROM products WHERE platform = ? AND status = ? ORDER BY price ASC',
      [platform, status]
    )
    return rows as Product[]
  }

  async create(data: {
    platform: string
    name: string
    description?: string
    durationDays: number
    price: number
    stock: number
    status?: number
  }): Promise<Product> {
    const pool = getPool()
    
    const [result] = await pool.query(
      `INSERT INTO products (platform, name, description, duration_days, price, stock, status)
       VALUES (?, ?, ?, ?, ?, ?, ?)`,
      [
        data.platform,
        data.name,
        data.description || null,
        data.durationDays,
        data.price,
        data.stock,
        data.status ?? 1
      ]
    )

    const insertResult = result as any
    const product = await this.findById(insertResult.insertId)

    if (!product) {
      throw new Error('Failed to create product')
    }

    logger.info(`Product created: ${product.id}`)

    return product
  }

  async update(id: number, data: {
    platform?: string
    name?: string
    description?: string
    durationDays?: number
    price?: number
    stock?: number
    status?: number
  }): Promise<Product> {
    const pool = getPool()
    
    const updates: string[] = []
    const params: any[] = []

    if (data.platform !== undefined) {
      updates.push('platform = ?')
      params.push(data.platform)
    }
    if (data.name !== undefined) {
      updates.push('name = ?')
      params.push(data.name)
    }
    if (data.description !== undefined) {
      updates.push('description = ?')
      params.push(data.description)
    }
    if (data.durationDays !== undefined) {
      updates.push('duration_days = ?')
      params.push(data.durationDays)
    }
    if (data.price !== undefined) {
      updates.push('price = ?')
      params.push(data.price)
    }
    if (data.stock !== undefined) {
      updates.push('stock = ?')
      params.push(data.stock)
    }
    if (data.status !== undefined) {
      updates.push('status = ?')
      params.push(data.status)
    }

    if (updates.length === 0) {
      throw new Error('No fields to update')
    }

    params.push(id)

    await pool.query(
      `UPDATE products SET ${updates.join(', ')} WHERE id = ?`,
      params
    )

    const product = await this.findById(id)

    if (!product) {
      throw new Error('Product not found')
    }

    logger.info(`Product updated: ${id}`)

    return product
  }

  async delete(id: number): Promise<void> {
    const pool = getPool()

    await pool.query('DELETE FROM products WHERE id = ?', [id])

    logger.info(`Product deleted: ${id}`)
  }

  async updateStock(id: number, delta: number): Promise<void> {
    const pool = getPool()
    
    const product = await this.findById(id)
    if (!product) {
      throw new Error('Product not found')
    }

    const newStock = product.stock + delta
    if (newStock < 0) {
      throw new Error('库存不足')
    }

    await pool.query(
      'UPDATE products SET stock = ? WHERE id = ?',
      [newStock, id]
    )

    logger.info(`Product ${id} stock updated: ${product.stock} -> ${newStock}`)
  }

  async hasActiveBatches(productId: number): Promise<boolean> {
    const pool = getPool()
    const [rows] = await pool.query(
      'SELECT COUNT(*) as count FROM card_batches WHERE product_id = ? AND valid_until > NOW()',
      [productId]
    )
    
    const result = rows as any[]
    return result[0].count > 0
  }
}

export const productService = new ProductService()
