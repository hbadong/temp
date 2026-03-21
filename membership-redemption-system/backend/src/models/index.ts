export interface User {
  id: number
  phone: string
  passwordHash: string | null
  status: number
  createdAt: Date
  updatedAt: Date
}

export interface Admin {
  id: number
  username: string
  passwordHash: string
  role: 'super_admin' | 'admin'
  status: number
  lastLoginAt: Date | null
  failedLoginAttempts: number
  lockedUntil: Date | null
  createdAt: Date
  updatedAt: Date
}

export interface Product {
  id: number
  platform: 'iqiyi' | 'youku' | 'tencent'
  name: string
  description: string | null
  durationDays: number
  price: number
  stock: number
  status: number
  createdAt: Date
  updatedAt: Date
}

export interface CardBatch {
  id: number
  batchNo: string
  productId: number
  prefix: string
  totalCount: number
  usedCount: number
  validFrom: Date
  validUntil: Date
  createdBy: number
  createdAt: Date
  updatedAt: Date
}

export interface Card {
  id: number
  cardNo: string
  batchId: number
  password: string
  productId: number
  status: 0 | 1 | 2
  usedBy: number | null
  usedAt: Date | null
  createdAt: Date
  updatedAt: Date
}

export interface Order {
  id: number
  orderNo: string
  userId: number
  productId: number
  type: 1 | 2
  cardId: number | null
  targetAccount: string
  amount: number
  status: 0 | 1 | 2 | 3
  platformOrderNo: string | null
  failureReason: string | null
  processedAt: Date | null
  createdAt: Date
  updatedAt: Date
}

export interface SmsLog {
  id: number
  phone: string
  type: 'verify_code' | 'notification'
  templateCode: string
  content: string | null
  status: 0 | 1 | 2
  errorCode: string | null
  errorMessage: string | null
  sendAt: Date | null
  response: string | null
  createdAt: Date
  updatedAt: Date
}

export interface OperationLog {
  id: number
  adminId: number
  action: string
  targetType: string | null
  targetId: number | null
  detail: Record<string, any> | null
  ip: string | null
  userAgent: string | null
  createdAt: Date
}

export interface SystemConfig {
  id: number
  configKey: string
  configValue: string | null
  description: string | null
  isEncrypted: number
  updatedBy: number | null
  createdAt: Date
  updatedAt: Date
}
