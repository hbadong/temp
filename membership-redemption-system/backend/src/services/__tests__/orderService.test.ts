import { OrderService } from '../orderService'

const mockPool = {
  query: jest.fn(),
  getConnection: jest.fn()
}

jest.mock('../../config/database', () => ({
  getPool: () => mockPool
}))

jest.mock('../productService', () => ({
  productService: {
    findById: jest.fn(),
    updateStock: jest.fn()
  }
}))

jest.mock('../cardService', () => ({
  cardService: {
    validateCard: jest.fn(),
    useCard: jest.fn()
  }
}))

jest.mock('../smsService', () => ({
  smsService: {
    sendNotification: jest.fn()
  }
}))

describe('OrderService', () => {
  let orderService: OrderService

  beforeEach(() => {
    jest.clearAllMocks()
    orderService = new OrderService()
  })

  describe('findById', () => {
    it('should return order when found', async () => {
      const mockOrder = {
        id: 1,
        order_no: 'O2024010112345678',
        user_id: 1,
        product_id: 1,
        type: 1,
        target_account: '13800138000',
        amount: 20,
        status: 0,
        platform: 'iqiyi',
        product_name: '月度会员'
      }
      mockPool.query.mockResolvedValueOnce([[mockOrder]])

      const result = await orderService.findById(1)

      expect(result).toEqual(mockOrder)
    })

    it('should return null when order not found', async () => {
      mockPool.query.mockResolvedValueOnce([[]])

      const result = await orderService.findById(999)

      expect(result).toBeNull()
    })
  })

  describe('findByUserId', () => {
    it('should return paginated orders for user', async () => {
      const mockOrders = [
        { id: 1, order_no: 'O2024010112345678', status: 0 },
        { id: 2, order_no: 'O2024010112345679', status: 2 }
      ]
      mockPool.query
        .mockResolvedValueOnce([[{ total: 2 }]])
        .mockResolvedValueOnce([mockOrders])

      const result = await orderService.findByUserId(1, 1, 20)

      expect(result.total).toBe(2)
      expect(result.orders).toHaveLength(2)
    })
  })

  describe('updateStatus', () => {
    it('should update order status', async () => {
      mockPool.query.mockResolvedValueOnce([{}])

      await orderService.updateStatus(1, 2, 'PLATFORM123')

      expect(mockPool.query).toHaveBeenCalledWith(
        expect.stringContaining('UPDATE orders SET'),
        expect.arrayContaining([2, 'PLATFORM123', 1])
      )
    })

    it('should update order with failure reason', async () => {
      mockPool.query.mockResolvedValueOnce([{}])

      await orderService.updateStatus(1, 3, undefined, 'Network error')

      expect(mockPool.query).toHaveBeenCalledWith(
        expect.stringContaining('failure_reason = ?'),
        expect.arrayContaining([3, 'Network error', 1])
      )
    })
  })
})
