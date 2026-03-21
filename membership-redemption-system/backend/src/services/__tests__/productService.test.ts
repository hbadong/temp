import { ProductService } from '../productService'

const mockPool = {
  query: jest.fn()
}

jest.mock('../../config/database', () => ({
  getPool: () => mockPool
}))

describe('ProductService', () => {
  let productService: ProductService

  beforeEach(() => {
    jest.clearAllMocks()
    productService = new ProductService()
  })

  describe('findAll', () => {
    it('should return all products when no status filter', async () => {
      const mockProducts = [
        { id: 1, platform: 'iqiyi', name: '月度会员', price: 20, stock: 100, status: 1 },
        { id: 2, platform: 'youku', name: '年度会员', price: 200, stock: 50, status: 1 }
      ]
      mockPool.query.mockResolvedValueOnce([mockProducts])

      const result = await productService.findAll()

      expect(result).toEqual(mockProducts)
    })

    it('should filter products by status', async () => {
      const mockProducts = [
        { id: 1, platform: 'iqiyi', name: '月度会员', price: 20, stock: 100, status: 1 }
      ]
      mockPool.query.mockResolvedValueOnce([mockProducts])

      const result = await productService.findAll(1)

      expect(result).toEqual(mockProducts)
      expect(mockPool.query).toHaveBeenCalledWith(
        expect.stringContaining('AND status = ?'),
        [1]
      )
    })
  })

  describe('findById', () => {
    it('should return product when found', async () => {
      const mockProduct = { id: 1, platform: 'iqiyi', name: '月度会员', price: 20, stock: 100, status: 1 }
      mockPool.query.mockResolvedValueOnce([[mockProduct]])

      const result = await productService.findById(1)

      expect(result).toEqual(mockProduct)
    })

    it('should return null when product not found', async () => {
      mockPool.query.mockResolvedValueOnce([[]])

      const result = await productService.findById(999)

      expect(result).toBeNull()
    })
  })

  describe('create', () => {
    it('should create product successfully', async () => {
      const mockInsertResult = { insertId: 1 }
      const mockProduct = { id: 1, platform: 'iqiyi', name: '月度会员', price: 20, stock: 100, status: 1 }

      mockPool.query
        .mockResolvedValueOnce([mockInsertResult])
        .mockResolvedValueOnce([[mockProduct]])

      const result = await productService.create({
        platform: 'iqiyi',
        name: '月度会员',
        durationDays: 30,
        price: 20,
        stock: 100
      })

      expect(result.id).toBe(1)
      expect(mockPool.query).toHaveBeenCalledTimes(2)
    })
  })

  describe('updateStock', () => {
    it('should increase stock correctly', async () => {
      const mockProduct = { id: 1, stock: 100 }
      mockPool.query
        .mockResolvedValueOnce([[mockProduct]])
        .mockResolvedValueOnce([{}])

      await productService.updateStock(1, 50)

      expect(mockPool.query).toHaveBeenLastCalledWith(
        'UPDATE products SET stock = ? WHERE id = ?',
        [150, 1]
      )
    })

    it('should decrease stock correctly', async () => {
      const mockProduct = { id: 1, stock: 100 }
      mockPool.query
        .mockResolvedValueOnce([[mockProduct]])
        .mockResolvedValueOnce([{}])

      await productService.updateStock(1, -30)

      expect(mockPool.query).toHaveBeenLastCalledWith(
        'UPDATE products SET stock = ? WHERE id = ?',
        [70, 1]
      )
    })

    it('should throw error when stock would be negative', async () => {
      const mockProduct = { id: 1, stock: 10 }
      mockPool.query.mockResolvedValueOnce([[mockProduct]])

      await expect(productService.updateStock(1, -20)).rejects.toThrow('库存不足')
    })
  })

  describe('hasActiveBatches', () => {
    it('should return true when active batches exist', async () => {
      mockPool.query.mockResolvedValueOnce([[{ count: 5 }]])

      const result = await productService.hasActiveBatches(1)

      expect(result).toBe(true)
    })

    it('should return false when no active batches', async () => {
      mockPool.query.mockResolvedValueOnce([[{ count: 0 }]])

      const result = await productService.hasActiveBatches(1)

      expect(result).toBe(false)
    })
  })
})
