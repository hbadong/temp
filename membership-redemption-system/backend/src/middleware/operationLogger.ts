import { Request, Response, NextFunction } from 'express'
import { getPool } from '../config/database'
import { logger } from '../utils/logger'

interface LogData {
  adminId: number
  action: string
  targetType?: string
  targetId?: number
  detail?: Record<string, any>
  ip?: string
  userAgent?: string
}

export async function logOperation(data: LogData): Promise<void> {
  try {
    const pool = getPool()
    await pool.query(
      `INSERT INTO operation_logs (admin_id, action, target_type, target_id, detail, ip, user_agent)
       VALUES (?, ?, ?, ?, ?, ?, ?)`,
      [
        data.adminId,
        data.action,
        data.targetType || null,
        data.targetId || null,
        data.detail ? JSON.stringify(data.detail) : null,
        data.ip || null,
        data.userAgent || null
      ]
    )
  } catch (err) {
    logger.error('Failed to log operation:', err)
  }
}

export function operationLogger(action: string, targetType?: string) {
  return async (req: Request, res: Response, next: NextFunction): Promise<void> => {
    const originalJson = res.json

    res.json = function(data: any) {
      if (req.admin) {
        const logData: LogData = {
          adminId: req.admin.adminId,
          action,
          targetType,
          targetId: data?.data?.id || req.params?.id ? parseInt(req.params?.id || data?.data?.id) : undefined,
          detail: {
            method: req.method,
            path: req.path,
            body: req.body,
            responseCode: res.statusCode
          },
          ip: req.ip,
          userAgent: req.headers['user-agent']
        }

        logOperation(logData).catch(err => {
          logger.error('Operation logging failed:', err)
        })
      }

      return originalJson.call(this, data)
    }

    next()
  }
}
