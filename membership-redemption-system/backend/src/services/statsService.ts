import { getPool } from '../config/database'
import dayjs from 'dayjs'

export class StatsService {
  async getDashboardStats(): Promise<{
    todayOrders: number
    todaySales: number
    totalUsers: number
    totalCards: number
    orderSuccessRate: number
    recentOrders: any[]
  }> {
    const pool = getPool()
    const today = dayjs().format('YYYY-MM-DD')

    const [todayStats] = await pool.query(
      `SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as sales
       FROM orders
       WHERE DATE(created_at) = ?`,
      [today]
    )

    const [totalUsers] = await pool.query('SELECT COUNT(*) as count FROM users')

    const [totalCards] = await pool.query(
      'SELECT COUNT(*) as count FROM cards WHERE status = 0'
    )

    const [successRate] = await pool.query(
      `SELECT 
        COUNT(CASE WHEN status = 2 THEN 1 END) as success,
        COUNT(*) as total
       FROM orders
       WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)`
    )

    const [recentOrders] = await pool.query(
      `SELECT o.*, p.name as product_name, u.phone as user_phone
       FROM orders o
       LEFT JOIN products p ON o.product_id = p.id
       LEFT JOIN users u ON o.user_id = u.id
       ORDER BY o.created_at DESC
       LIMIT 10`
    )

    const todayStatsData = (todayStats as any)[0]
    const successRateData = (successRate as any)[0]

    return {
      todayOrders: todayStatsData.count || 0,
      todaySales: parseFloat(todayStatsData.sales) || 0,
      totalUsers: (totalUsers as any)[0].count || 0,
      totalCards: (totalCards as any)[0].count || 0,
      orderSuccessRate: successRateData.total > 0 
        ? ((successRateData.success / successRateData.total) * 100).toFixed(2)
        : '0.00',
      recentOrders: recentOrders as any[]
    }
  }

  async getSalesStats(options: {
    startDate: string
    endDate: string
    platform?: string
    groupBy?: 'day' | 'week' | 'month'
  }): Promise<any[]> {
    const pool = getPool()
    const { startDate, endDate, platform, groupBy = 'day' } = options

    let dateFormat: string
    switch (groupBy) {
      case 'week':
        dateFormat = '%Y-%u'
        break
      case 'month':
        dateFormat = '%Y-%m'
        break
      default:
        dateFormat = '%Y-%m-%d'
    }

    let sql = `SELECT 
        DATE_FORMAT(o.created_at, '${dateFormat}') as date,
        p.platform,
        COUNT(*) as order_count,
        SUM(o.amount) as total_amount,
        COUNT(CASE WHEN o.status = 2 THEN 1 END) as success_count
       FROM orders o
       LEFT JOIN products p ON o.product_id = p.id
       WHERE o.created_at BETWEEN ? AND ?`

    const params: any[] = [startDate, endDate]

    if (platform) {
      sql += ' AND p.platform = ?'
      params.push(platform)
    }

    sql += ` GROUP BY DATE_FORMAT(o.created_at, '${dateFormat}'), p.platform
             ORDER BY date DESC`

    const [rows] = await pool.query(sql, params)

    return rows as any[]
  }

  async getFinancialStats(options: {
    startDate: string
    endDate: string
  }): Promise<{
    totalRevenue: number
    cardRevenue: number
    mobileRevenue: number
    totalCost: number
    profit: number
  }> {
    const pool = getPool()
    const { startDate, endDate } = options

    const [revenueStats] = await pool.query(
      `SELECT 
        SUM(CASE WHEN type = 1 THEN amount ELSE 0 END) as mobile_revenue,
        SUM(CASE WHEN type = 2 THEN amount ELSE 0 END) as card_revenue,
        SUM(CASE WHEN status = 2 THEN amount ELSE 0 END) as total_revenue
       FROM orders
       WHERE created_at BETWEEN ? AND ? AND status = 2`,
      [startDate, endDate]
    )

    const [costStats] = await pool.query(
      `SELECT COUNT(*) * 15 as total_cost
       FROM orders
       WHERE created_at BETWEEN ? AND ? AND status = 2`,
      [startDate, endDate]
    )

    const revenueData = (revenueStats as any)[0]
    const costData = (costStats as any)[0]

    const totalRevenue = parseFloat(revenueData.total_revenue) || 0
    const totalCost = parseFloat(costData.total_cost) || 0

    return {
      totalRevenue,
      cardRevenue: parseFloat(revenueData.card_revenue) || 0,
      mobileRevenue: parseFloat(revenueData.mobile_revenue) || 0,
      totalCost,
      profit: totalRevenue - totalCost
    }
  }
}

export const statsService = new StatsService()
