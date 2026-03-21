import { UserService } from '../userService'
import { SmsService } from '../smsService'

const mockPool = {
  query: jest.fn()
}

const mockRedis = {
  get: jest.fn(),
  setex: jest.fn(),
  del: jest.fn(),
  incr: jest.fn()
}

jest.mock('../../config/database', () => ({
  getPool: () => mockPool,
  getRedis: () => mockRedis
}))

describe('UserService', () => {
  let userService: UserService

  beforeEach(() => {
    jest.clearAllMocks()
    userService = new UserService()
  })

  describe('findByPhone', () => {
    it('should return user when found', async () => {
      const mockUser = {
        id: 1,
        phone: '13800138000',
        status: 1,
        createdAt: new Date(),
        updatedAt: new Date()
      }
      mockPool.query.mockResolvedValueOnce([[mockUser]])

      const result = await userService.findByPhone('13800138000')

      expect(result).toEqual(mockUser)
      expect(mockPool.query).toHaveBeenCalledWith(
        'SELECT * FROM users WHERE phone = ?',
        ['13800138000']
      )
    })

    it('should return null when user not found', async () => {
      mockPool.query.mockResolvedValueOnce([[]])

      const result = await userService.findByPhone('13800138000')

      expect(result).toBeNull()
    })
  })

  describe('findById', () => {
    it('should return user when found', async () => {
      const mockUser = {
        id: 1,
        phone: '13800138000',
        status: 1,
        createdAt: new Date(),
        updatedAt: new Date()
      }
      mockPool.query.mockResolvedValueOnce([[mockUser]])

      const result = await userService.findById(1)

      expect(result).toEqual(mockUser)
      expect(mockPool.query).toHaveBeenCalledWith(
        'SELECT * FROM users WHERE id = ?',
        [1]
      )
    })
  })

  describe('createUser', () => {
    it('should create new user successfully', async () => {
      const mockInsertResult = { insertId: 1 }
      const mockUser = {
        id: 1,
        phone: '13800138000',
        status: 1,
        createdAt: new Date(),
        updatedAt: new Date()
      }

      mockPool.query
        .mockResolvedValueOnce([mockInsertResult])
        .mockResolvedValueOnce([[mockUser]])

      const result = await userService.createUser('13800138000')

      expect(result.phone).toBe('13800138000')
      expect(mockPool.query).toHaveBeenCalledTimes(2)
    })
  })
})

describe('SmsService', () => {
  let smsService: SmsService

  beforeEach(() => {
    jest.clearAllMocks()
    smsService = new SmsService()
  })

  describe('verifyCode', () => {
    it('should return true when code is correct', async () => {
      mockRedis.get.mockResolvedValue('123456')
      mockRedis.get.mockResolvedValueOnce('123456')
      mockRedis.get.mockResolvedValueOnce(null)
      mockRedis.del.mockResolvedValueOnce(undefined)
      mockRedis.del.mockResolvedValueOnce(undefined)

      const result = await smsService.verifyCode('13800138000', '123456')

      expect(result).toBe(true)
    })

    it('should throw error when code is expired', async () => {
      mockRedis.get.mockResolvedValueOnce(null)

      await expect(smsService.verifyCode('13800138000', '123456'))
        .rejects.toThrow('验证码已过期')
    })

    it('should throw error when code is wrong and max attempts reached', async () => {
      mockRedis.get
        .mockResolvedValueOnce('123456')
        .mockResolvedValueOnce('3')

      await expect(smsService.verifyCode('13800138000', '000000'))
        .rejects.toThrow('验证码错误次数过多')
    })
  })
})
