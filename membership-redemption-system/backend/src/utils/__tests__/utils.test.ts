import { hashPassword, comparePassword } from '../password'
import { encryptCardPassword, decryptCardPassword } from '../crypto'
import { generateToken, verifyToken, TokenPayload } from '../jwt'
import { success, error, AppError, ErrorCodes } from '../response'

describe('Password Utils', () => {
  it('should hash and verify password correctly', async () => {
    const password = 'testPassword123'
    const hash = await hashPassword(password)
    
    expect(hash).toBeDefined()
    expect(hash).not.toBe(password)
    
    const isValid = await comparePassword(password, hash)
    expect(isValid).toBe(true)
    
    const isInvalid = await comparePassword('wrongPassword', hash)
    expect(isInvalid).toBe(false)
  })
})

describe('Crypto Utils', () => {
  const masterKey = 'test-master-key-32-characters!!'
  
  it('should encrypt and decrypt card password', () => {
    const plaintext = 'CARD123456789'
    const encrypted = encryptCardPassword(plaintext, masterKey)
    
    expect(encrypted).toBeDefined()
    expect(encrypted).not.toBe(plaintext)
    
    const decrypted = decryptCardPassword(encrypted, masterKey)
    expect(decrypted).toBe(plaintext)
  })
  
  it('should produce different ciphertext for same plaintext', () => {
    const plaintext = 'CARD123456789'
    const encrypted1 = encryptCardPassword(plaintext, masterKey)
    const encrypted2 = encryptCardPassword(plaintext, masterKey)
    
    expect(encrypted1).not.toBe(encrypted2)
  })
})

describe('JWT Utils', () => {
  it('should generate and verify token', () => {
    const payload: TokenPayload = {
      userId: 1,
      phone: '13800138000',
      type: 'user'
    }
    
    const token = generateToken(payload)
    expect(token).toBeDefined()
    
    const decoded = verifyToken(token)
    expect(decoded.userId).toBe(payload.userId)
    expect(decoded.phone).toBe(payload.phone)
    expect(decoded.type).toBe(payload.type)
  })
  
  it('should throw error for invalid token', () => {
    expect(() => verifyToken('invalid-token')).toThrow()
  })
})

describe('Response Utils', () => {
  it('should create success response', () => {
    const response = success({ id: 1, name: 'test' })
    
    expect(response.code).toBe(ErrorCodes.SUCCESS)
    expect(response.message).toBe('操作成功')
    expect(response.data).toEqual({ id: 1, name: 'test' })
    expect(response.timestamp).toBeDefined()
  })
  
  it('should create error response', () => {
    const response = error(ErrorCodes.VALIDATION_ERROR)
    
    expect(response.code).toBe(ErrorCodes.VALIDATION_ERROR)
    expect(response.message).toBe('参数验证失败')
  })
  
  it('should create AppError with custom message', () => {
    const appError = new AppError(ErrorCodes.CARD_INVALID, '卡密不存在')
    
    expect(appError.code).toBe(ErrorCodes.CARD_INVALID)
    expect(appError.message).toBe('卡密不存在')
    expect(appError.name).toBe('AppError')
  })
})
