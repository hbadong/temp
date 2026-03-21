import { describe, test, expect, beforeEach } from '@jest/globals'
import AlmanacCalculator from '../src/engines/almanacCalculator.js'

describe('黄历计算引擎', () => {
  let calculator

  beforeEach(() => {
    calculator = new AlmanacCalculator()
  })

  describe('calculate - 黄历计算', () => {
    test('应该返回完整的黄历数据', () => {
      const result = calculator.calculate('2026-03-21')
      
      expect(result.date).toBe('2026-03-21')
      expect(result.lunarYear).toBeDefined()
      expect(result.lunarMonth).toBeDefined()
      expect(result.lunarDay).toBeDefined()
      expect(result.zodiac).toBeDefined()
      expect(result.solarTerm).toBeDefined()
      expect(result.constellation).toBeDefined()
      expect(result.weekday).toBeDefined()
      expect(result.yi).toBeDefined()
      expect(result.ji).toBeDefined()
      expect(result.chongSha).toBeDefined()
      expect(result.luckyHours).toBeDefined()
      expect(result.caiShen).toBeDefined()
      expect(result.xiShen).toBeDefined()
      expect(result.fuShen).toBeDefined()
    })

    test('宜忌项目数量应该在合理范围内', () => {
      const result = calculator.calculate('2026-03-21')
      
      expect(result.yi.length).toBeGreaterThanOrEqual(4)
      expect(result.yi.length).toBeLessThanOrEqual(8)
      expect(result.ji.length).toBeGreaterThanOrEqual(3)
      expect(result.ji.length).toBeLessThanOrEqual(6)
    })

    test('吉时数量应该在合理范围内', () => {
      const result = calculator.calculate('2026-03-21')
      
      expect(result.luckyHours.length).toBeGreaterThanOrEqual(4)
      expect(result.luckyHours.length).toBeLessThanOrEqual(8)
    })
  })

  describe('getLunarDate - 农历日期', () => {
    test('应该返回农历日期', () => {
      const date = new Date('2026-03-21')
      const lunar = calculator.getLunarDate(date)
      
      expect(lunar.month).toBeDefined()
      expect(lunar.day).toBeDefined()
      expect(lunar.year).toBeDefined()
    })
  })

  describe('getChineseZodiac - 生肖计算', () => {
    test('应该返回正确的生肖', () => {
      const date1 = new Date('2024-01-01')
      expect(calculator.getChineseZodiac(date1)).toBe('龙')
      
      const date2 = new Date('2025-01-01')
      expect(calculator.getChineseZodiac(date2)).toBe('蛇')
    })

    test('2026年应该是马年', () => {
      const date = new Date('2026-06-01')
      expect(calculator.getChineseZodiac(date)).toBe('马')
    })
  })

  describe('getConstellation - 星座计算', () => {
    test('应该返回正确的星座', () => {
      const aries = new Date('2026-03-21')
      expect(calculator.getConstellation(aries)).toBe('白羊座')
      
      const taurus = new Date('2026-04-20')
      expect(calculator.getConstellation(taurus)).toBe('金牛座')
    })
  })

  describe('getGanzhiYear - 年柱计算', () => {
    test('1984年应该是甲子年', () => {
      const date = new Date('1984-01-01')
      expect(calculator.getGanzhiYear(date)).toBe('甲子')
    })

    test('2024年应该是甲辰年', () => {
      const date = new Date('2024-01-01')
      expect(calculator.getGanzhiYear(date)).toBe('甲辰')
    })
  })

  describe('getDayGanZhi - 日柱计算', () => {
    test('应该返回日柱', () => {
      const date = new Date('2026-03-21')
      const ganzhi = calculator.getDayGanZhi(date)
      
      expect(ganzhi).toBeDefined()
      expect(ganzhi.length).toBe(2)
    })
  })

  describe('getWeekday - 星期计算', () => {
    test('应该返回正确的星期', () => {
      const sunday = new Date('2026-03-22')
      expect(calculator.getWeekday(sunday)).toBe('星期日')
      
      const saturday = new Date('2026-03-21')
      expect(calculator.getWeekday(saturday)).toBe('星期六')
    })
  })

  describe('getChongSha - 冲煞计算', () => {
    test('应该返回冲煞信息', () => {
      const chongSha = calculator.getChongSha('甲子')
      expect(chongSha).toContain('冲')
    })
  })

  describe('getLuckyHours - 吉时计算', () => {
    test('应该返回4-8个吉时', () => {
      const hours = calculator.getLuckyHours('甲子')
      expect(hours.length).toBeGreaterThanOrEqual(4)
      expect(hours.length).toBeLessThanOrEqual(8)
    })

    test('吉时应该在地支列表中', () => {
      const hours = calculator.getLuckyHours('甲子')
      const validZhis = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥']
      hours.forEach(hour => {
        expect(validZhis).toContain(hour)
      })
    })
  })

  describe('getRandomItems - 随机选择', () => {
    test('应该返回指定数量的随机项', () => {
      const items = ['a', 'b', 'c', 'd', 'e']
      const result = calculator.getRandomItems(items, 3)
      
      expect(result.length).toBe(3)
    })

    test('返回的项应该在原数组中', () => {
      const items = ['a', 'b', 'c', 'd', 'e']
      const result = calculator.getRandomItems(items, 3)
      
      result.forEach(item => {
        expect(items).toContain(item)
      })
    })

    test('当数量大于数组长度时返回全部', () => {
      const items = ['a', 'b', 'c']
      const result = calculator.getRandomItems(items, 5)
      
      expect(result.length).toBe(3)
    })
  })

  describe('SolarTerm - 节气计算', () => {
    test('应该返回节气信息', () => {
      const date = new Date('2026-03-21')
      const solarTerm = calculator.getSolarTerm(date)
      
      expect(solarTerm.current).toBeDefined()
      expect(solarTerm.next).toBeDefined()
      expect(solarTerm.daysToNext).toBeDefined()
      expect(solarTerm.daysToNext).toBeGreaterThanOrEqual(1)
      expect(solarTerm.daysToNext).toBeLessThanOrEqual(15)
    })
  })
})
