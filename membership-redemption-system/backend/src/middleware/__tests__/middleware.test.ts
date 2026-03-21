import { Request, Response } from 'express'
import { userAuth } from '../userAuth'
import { adminAuth, requireSuperAdmin } from '../adminAuth'
import { generateToken, generateAdminToken } from '../../utils/jwt'

describe('User Auth Middleware', () => {
  let mockRequest: Partial<Request>
  let mockResponse: Partial<Response>
  let nextFunction: jest.Mock

  beforeEach(() => {
    mockRequest = {
      headers: {}
    }
    mockResponse = {
      status: jest.fn().mockReturnThis(),
      json: jest.fn()
    }
    nextFunction = jest.fn()
  })

  it('should reject request without authorization header', () => {
    userAuth(mockRequest as Request, mockResponse as Response, nextFunction)

    expect(mockResponse.status).toHaveBeenCalledWith(401)
    expect(nextFunction).not.toHaveBeenCalled()
  })

  it('should reject request with invalid token format', () => {
    mockRequest.headers = { authorization: 'InvalidToken' }

    userAuth(mockRequest as Request, mockResponse as Response, nextFunction)

    expect(mockResponse.status).toHaveBeenCalledWith(401)
    expect(nextFunction).not.toHaveBeenCalled()
  })

  it('should accept request with valid user token', () => {
    const token = generateToken({
      userId: 1,
      phone: '13800138000',
      type: 'user'
    })
    mockRequest.headers = { authorization: `Bearer ${token}` }

    userAuth(mockRequest as Request, mockResponse as Response, nextFunction)

    expect(nextFunction).toHaveBeenCalled()
    expect((mockRequest as any).user.userId).toBe(1)
  })

  it('should reject admin token in user auth', () => {
    const adminToken = generateAdminToken({
      userId: 1,
      phone: 'admin',
      type: 'admin',
      adminId: 1,
      role: 'admin'
    })
    mockRequest.headers = { authorization: `Bearer ${adminToken}` }

    userAuth(mockRequest as Request, mockResponse as Response, nextFunction)

    expect(mockResponse.status).toHaveBeenCalledWith(401)
    expect(nextFunction).not.toHaveBeenCalled()
  })
})

describe('Admin Auth Middleware', () => {
  let mockRequest: Partial<Request>
  let mockResponse: Partial<Response>
  let nextFunction: jest.Mock

  beforeEach(() => {
    mockRequest = {
      headers: {}
    }
    mockResponse = {
      status: jest.fn().mockReturnThis(),
      json: jest.fn()
    }
    nextFunction = jest.fn()
  })

  it('should reject request without authorization header', () => {
    adminAuth(mockRequest as Request, mockResponse as Response, nextFunction)

    expect(mockResponse.status).toHaveBeenCalledWith(401)
    expect(nextFunction).not.toHaveBeenCalled()
  })

  it('should accept request with valid admin token', () => {
    const token = generateAdminToken({
      userId: 1,
      phone: 'admin',
      type: 'admin',
      adminId: 1,
      role: 'admin'
    })
    mockRequest.headers = { authorization: `Bearer ${token}` }

    adminAuth(mockRequest as Request, mockResponse as Response, nextFunction)

    expect(nextFunction).toHaveBeenCalled()
    expect((mockRequest as any).admin.adminId).toBe(1)
  })
})

describe('Super Admin Middleware', () => {
  let mockRequest: Partial<Request>
  let mockResponse: Partial<Response>
  let nextFunction: jest.Mock

  beforeEach(() => {
    mockRequest = {
      headers: {},
      admin: undefined
    }
    mockResponse = {
      status: jest.fn().mockReturnThis(),
      json: jest.fn()
    }
    nextFunction = jest.fn()
  })

  it('should reject request without admin', () => {
    requireSuperAdmin(mockRequest as Request, mockResponse as Response, nextFunction)

    expect(mockResponse.status).toHaveBeenCalledWith(401)
    expect(nextFunction).not.toHaveBeenCalled()
  })

  it('should reject request from non-super-admin', () => {
    mockRequest.admin = {
      userId: 1,
      phone: 'admin',
      type: 'admin',
      adminId: 1,
      role: 'admin'
    }

    requireSuperAdmin(mockRequest as Request, mockResponse as Response, nextFunction)

    expect(mockResponse.status).toHaveBeenCalledWith(403)
    expect(nextFunction).not.toHaveBeenCalled()
  })

  it('should accept request from super-admin', () => {
    mockRequest.admin = {
      userId: 1,
      phone: 'superadmin',
      type: 'admin',
      adminId: 1,
      role: 'super_admin'
    }

    requireSuperAdmin(mockRequest as Request, mockResponse as Response, nextFunction)

    expect(nextFunction).toHaveBeenCalled()
  })
})
