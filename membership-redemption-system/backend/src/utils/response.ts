export interface ApiResponse<T = any> {
  code: number
  message: string
  data?: T
  timestamp: number
}

export interface PaginatedResponse<T = any> {
  code: number
  message: string
  data: {
    list: T[]
    total: number
    page: number
    pageSize: number
  }
  timestamp: number
}

export const ErrorCodes = {
  SUCCESS: 0,
  VALIDATION_ERROR: 10001,
  VERIFY_CODE_ERROR: 10002,
  VERIFY_CODE_EXPIRED: 10003,
  VERIFY_CODE_LOCKED: 10004,
  AUTH_ERROR: 10005,
  TOKEN_EXPIRED: 10006,
  ORDER_CREATE_ERROR: 20001,
  ORDER_PAYMENT_ERROR: 20002,
  THIRD_PARTY_ERROR: 20003,
  ORDER_TIMEOUT: 20004,
  CARD_INVALID: 30001,
  CARD_USED: 30002,
  CARD_EXPIRED: 30003,
  CARD_DISABLED: 30004,
  PRODUCT_OUT_OF_STOCK: 40001,
  PRODUCT_NOT_FOUND: 40002,
  SYSTEM_ERROR: 50001,
  RATE_LIMIT_ERROR: 50002
} as const

export type ErrorCode = typeof ErrorCodes[keyof typeof ErrorCodes]

export function success<T>(data?: T): ApiResponse<T> {
  return {
    code: ErrorCodes.SUCCESS,
    message: '操作成功',
    data,
    timestamp: Date.now()
  }
}

export function paginated<T>(list: T[], total: number, page: number, pageSize: number): PaginatedResponse<T> {
  return {
    code: ErrorCodes.SUCCESS,
    message: '查询成功',
    data: {
      list,
      total,
      page,
      pageSize
    },
    timestamp: Date.now()
  }
}

export function error(code: ErrorCode, message?: string): ApiResponse {
  return {
    code,
    message: message || getErrorMessage(code),
    timestamp: Date.now()
  }
}

function getErrorMessage(code: ErrorCode): string {
  const messages: Record<number, string> = {
    [ErrorCodes.SUCCESS]: '操作成功',
    [ErrorCodes.VALIDATION_ERROR]: '参数验证失败',
    [ErrorCodes.VERIFY_CODE_ERROR]: '验证码错误',
    [ErrorCodes.VERIFY_CODE_EXPIRED]: '验证码已过期',
    [ErrorCodes.VERIFY_CODE_LOCKED]: '验证码验证已锁定',
    [ErrorCodes.AUTH_ERROR]: '认证失败',
    [ErrorCodes.TOKEN_EXPIRED]: '令牌已过期',
    [ErrorCodes.ORDER_CREATE_ERROR]: '订单创建失败',
    [ErrorCodes.ORDER_PAYMENT_ERROR]: '订单支付失败',
    [ErrorCodes.THIRD_PARTY_ERROR]: '第三方服务错误',
    [ErrorCodes.ORDER_TIMEOUT]: '订单处理超时',
    [ErrorCodes.CARD_INVALID]: '卡密无效',
    [ErrorCodes.CARD_USED]: '卡密已使用',
    [ErrorCodes.CARD_EXPIRED]: '卡密已过期',
    [ErrorCodes.CARD_DISABLED]: '卡密已作废',
    [ErrorCodes.PRODUCT_OUT_OF_STOCK]: '套餐库存不足',
    [ErrorCodes.PRODUCT_NOT_FOUND]: '套餐不存在',
    [ErrorCodes.SYSTEM_ERROR]: '系统繁忙',
    [ErrorCodes.RATE_LIMIT_ERROR]: '请求频率超限'
  }

  return messages[code] || '未知错误'
}

export class AppError extends Error {
  constructor(
    public code: ErrorCode,
    message?: string
  ) {
    super(message || getErrorMessage(code))
    this.name = 'AppError'
  }
}
