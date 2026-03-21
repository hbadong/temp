import { describe, test, expect } from '@jest/globals'
import BaziCalculator from '../src/engines/baziCalculator.js'

describe('八字命盘计算器', () => {
  const calculator = new BaziCalculator()

  describe('calcYearGanZhi - 年柱计算', () => {
    test('1984年应该是甲子年', () => {
      const result = calculator.calcYearGanZhi(1984)
      expect(result.gan).toBe('甲')
      expect(result.zhi).toBe('子')
      expect(result.branch).toBe('甲子')
    })

    test('2024年应该是甲辰年', () => {
      const result = calculator.calcYearGanZhi(2024)
      expect(result.gan).toBe('甲')
      expect(result.zhi).toBe('辰')
      expect(result.branch).toBe('甲辰')
    })

    test('2026年应该是丙午年', () => {
      const result = calculator.calcYearGanZhi(2026)
      expect(result.gan).toBe('丙')
      expect(result.zhi).toBe('午')
      expect(result.branch).toBe('丙午')
    })
  })

  describe('calcBaZi - 八字计算', () => {
    test('应该正确计算完整八字', () => {
      const result = calculator.calcBaZi(2024, 1, 15, 12)
      
      expect(result.year).toBeDefined()
      expect(result.year.branch).toBeDefined()
      expect(result.month).toBeDefined()
      expect(result.month.branch).toBeDefined()
      expect(result.day).toBeDefined()
      expect(result.day.branch).toBeDefined()
      expect(result.hour).toBeDefined()
      expect(result.hour.branch).toBeDefined()
    })
  })

  describe('analyzeFiveElements - 五行分析', () => {
    test('应该返回五行分布统计', () => {
      const bazi = calculator.calcBaZi(2024, 1, 15, 12)
      const elements = calculator.analyzeFiveElements(bazi)
      
      expect(elements).toHaveProperty('木')
      expect(elements).toHaveProperty('火')
      expect(elements).toHaveProperty('土')
      expect(elements).toHaveProperty('金')
      expect(elements).toHaveProperty('水')
      
      const total = Object.values(elements).reduce((sum, count) => sum + count, 0)
      expect(total).toBe(8)
    })
  })

  describe('getFullAnalysis - 完整命盘分析', () => {
    test('应该返回完整的命盘分析', () => {
      const result = calculator.getFullAnalysis(2024, 1, 15, 12)
      
      expect(result.bazi).toBeDefined()
      expect(result.fiveElements).toBeDefined()
      expect(result.dayMasterStrength).toBeDefined()
      expect(result.xiYongSheng).toBeDefined()
      expect(result.jiShen).toBeDefined()
      expect(result.dayMaster).toBeDefined()
      expect(result.strengthLevel).toBeDefined()
    })

    test('strengthLevel应该在有效范围内', () => {
      const result = calculator.getFullAnalysis(2024, 1, 15, 12)
      
      const validLevels = ['极弱', '较弱', '偏弱', '平和', '偏强', '较强', '极强']
      expect(validLevels).toContain(result.strengthLevel)
    })
  })

  describe('getStrengthLevel - 旺衰等级判定', () => {
    test('应该正确返回旺衰等级', () => {
      expect(calculator.getStrengthLevel(-100)).toBe('极弱')
      expect(calculator.getStrengthLevel(-40)).toBe('较弱')
      expect(calculator.getStrengthLevel(-10)).toBe('偏弱')
      expect(calculator.getStrengthLevel(0)).toBe('平和')
      expect(calculator.getStrengthLevel(20)).toBe('偏强')
      expect(calculator.getStrengthLevel(60)).toBe('极强')
    })
  })
})
