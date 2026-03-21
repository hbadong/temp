import fetch from 'node-fetch'
import { config } from '../config'
import { logger } from '../utils/logger'

export interface RechargeParams {
  platform: string
  account: string
  durationDays: number
}

export interface RechargeResult {
  success: boolean
  platformOrderNo?: string
  message?: string
}

export class IntegrationService {
  private async callPlatformApi(platform: string, method: string, params: any): Promise<any> {
    const platforms: Record<string, { apiUrl: string; appId: string; appKey: string }> = {
      iqiyi: config.platform.iqiyi,
      youku: config.platform.youku,
      tencent: config.platform.tencent
    }

    const platformConfig = platforms[platform]
    if (!platformConfig) {
      throw new Error(`Unsupported platform: ${platform}`)
    }

    const url = `${platformConfig.apiUrl}/${method}`

    try {
      const response = await fetch(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-App-Id': platformConfig.appId,
          'X-App-Key': platformConfig.appKey
        },
        body: JSON.stringify(params)
      })

      if (!response.ok) {
        throw new Error(`API returned ${response.status}`)
      }

      return await response.json()
    } catch (error) {
      logger.error(`Platform API call failed: ${platform}`, error)
      throw error
    }
  }

  async rechargeMember(params: RechargeParams): Promise<RechargeResult> {
    const { platform, account, durationDays } = params

    try {
      const result = await this.callPlatformApi(platform, 'recharge', {
        account,
        duration: durationDays,
        timestamp: Date.now()
      })

      if (result.success || result.code === 0) {
        return {
          success: true,
          platformOrderNo: result.orderNo || result.order_id
        }
      } else {
        return {
          success: false,
          message: result.message || '充值失败'
        }
      }
    } catch (error: any) {
      logger.error(`Recharge failed for ${platform}:`, error)
      return {
        success: false,
        message: error.message || '充值请求失败'
      }
    }
  }

  async queryOrder(platform: string, platformOrderNo: string): Promise<{ status: string; message?: string }> {
    try {
      const result = await this.callPlatformApi(platform, 'query', {
        orderNo: platformOrderNo
      })

      return {
        status: result.status,
        message: result.message
      }
    } catch (error: any) {
      logger.error(`Query order failed for ${platform}:`, error)
      return {
        status: 'unknown',
        message: error.message
      }
    }
  }

  async testConnection(platform: string): Promise<boolean> {
    try {
      await this.callPlatformApi(platform, 'ping', {})
      return true
    } catch (error) {
      return false
    }
  }
}

export const integrationService = new IntegrationService()
