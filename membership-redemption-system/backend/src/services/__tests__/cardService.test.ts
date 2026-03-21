import { CardService } from '../cardService'

const mockPool = {
  query: jest.fn(),
  getConnection: jest.fn()
}

jest.mock('../../config/database', () => ({
  getPool: () => mockPool
}))

describe('CardService', () => {
  let cardService: CardService

  beforeEach(() => {
    jest.clearAllMocks()
    cardService = new CardService()
  })

  describe('validateCard', () => {
    it('should return invalid when card not found', async () => {
      mockPool.query.mockResolvedValueOnce([[]])

      const result = await cardService.validateCard('INVALID')

      expect(result.valid).toBe(false)
      expect(result.error).toBe('卡密不存在')
    })

    it('should return invalid when card is already used', async () => {
      mockPool.query.mockResolvedValueOnce([[{ id: 1, card_no: 'CARD123', status: 1 }]])

      const result = await cardService.validateCard('CARD123')

      expect(result.valid).toBe(false)
      expect(result.error).toBe('卡密已使用')
    })

    it('should return invalid when card is disabled', async () => {
      mockPool.query.mockResolvedValueOnce([[{ id: 1, card_no: 'CARD123', status: 2 }]])

      const result = await cardService.validateCard('CARD123')

      expect(result.valid).toBe(false)
      expect(result.error).toBe('卡密已作废')
    })

    it('should return invalid when card is expired', async () => {
      mockPool.query
        .mockResolvedValueOnce([[{ id: 1, card_no: 'CARD123', status: 0, batch_id: 1 }]])
        .mockResolvedValueOnce([[{
          id: 1,
          valid_from: new Date('2020-01-01'),
          valid_until: new Date('2020-12-31')
        }]])

      const result = await cardService.validateCard('CARD123')

      expect(result.valid).toBe(false)
      expect(result.error).toBe('卡密已过期')
    })

    it('should return valid when card is valid', async () => {
      mockPool.query
        .mockResolvedValueOnce([[{ id: 1, card_no: 'CARD123', status: 0, batch_id: 1 }]])
        .mockResolvedValueOnce([[{
          id: 1,
          valid_from: new Date('2020-01-01'),
          valid_until: new Date('2099-12-31')
        }]])

      const result = await cardService.validateCard('CARD123')

      expect(result.valid).toBe(true)
      expect(result.card).toBeDefined()
    })
  })

  describe('useCard', () => {
    it('should throw error when card not found', async () => {
      mockPool.query.mockResolvedValueOnce([[]])

      await expect(cardService.useCard('INVALID', 1)).rejects.toThrow('卡密不存在')
    })

    it('should update card status to used', async () => {
      const mockCard = { id: 1, card_no: 'CARD123', status: 0, batch_id: 1 }
      mockPool.query
        .mockResolvedValueOnce([[mockCard]])
        .mockResolvedValueOnce([[{ id: 1, status: 0, batch_id: 1 }]])
        .mockResolvedValueOnce([[{ id: 1, valid_from: new Date(), valid_until: new Date('2099-12-31') }]])
        .mockResolvedValueOnce([{}])
        .mockResolvedValueOnce([{}])

      await cardService.useCard('CARD123', 1)

      expect(mockPool.query).toHaveBeenCalledWith(
        'UPDATE cards SET status = 1, used_by = ?, used_at = NOW() WHERE id = ?',
        [1, 1]
      )
    })
  })

  describe('disableCard', () => {
    it('should throw error when card not found', async () => {
      mockPool.query.mockResolvedValueOnce([[]])

      await expect(cardService.disableCard(999)).rejects.toThrow('卡密不存在')
    })

    it('should throw error when card is already used', async () => {
      mockPool.query.mockResolvedValueOnce([[{ id: 1, status: 1 }]])

      await expect(cardService.disableCard(1)).rejects.toThrow('卡密已使用，无法作废')
    })

    it('should disable unused card', async () => {
      mockPool.query
        .mockResolvedValueOnce([[{ id: 1, status: 0 }]])
        .mockResolvedValueOnce([{}])

      await cardService.disableCard(1)

      expect(mockPool.query).toHaveBeenCalledWith(
        'UPDATE cards SET status = 2 WHERE id = ?',
        [1]
      )
    })
  })

  describe('findCards', () => {
    it('should return paginated cards', async () => {
      mockPool.query
        .mockResolvedValueOnce([[{ count: 50 }]])
        .mockResolvedValueOnce([[{ id: 1 }, { id: 2 }]])

      const result = await cardService.findCards({ page: 1, pageSize: 20 })

      expect(result.total).toBe(50)
      expect(result.cards).toHaveLength(2)
    })

    it('should filter by status', async () => {
      mockPool.query
        .mockResolvedValueOnce([[{ count: 10 }]])
        .mockResolvedValueOnce([[{ id: 1, status: 0 }]])

      const result = await cardService.findCards({ status: 0, page: 1, pageSize: 20 })

      expect(result.cards[0].status).toBe(0)
    })
  })
})
