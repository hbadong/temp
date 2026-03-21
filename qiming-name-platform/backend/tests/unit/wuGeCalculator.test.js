import { describe, test, expect } from '@jest/globals'
import WuGeCalculator from '../src/engines/wuGeCalculator.js'

describe('三才五格计算器', () => {
  const calculator = new WuGeCalculator()

  describe('getStrokeCount - 笔画计算', () => {
    test('常用汉字笔画应该正确', () => {
      expect(calculator.getStrokeCount('一')).toBe(1)
      expect(calculator.getStrokeCount('二')).toBe(2)
      expect(calculator.getStrokeCount('三')).toBe(3)
      expect(calculator.getStrokeCount('十')).toBe(2)
      expect(calculator.getStrokeCount('口')).toBe(3)
      expect(calculator.getStrokeCount('人')).toBe(2)
    })

    test('多字姓名应该返回总笔画', () => {
      const strokes = calculator.getStrokeCount('李')
      expect(typeof strokes).toBe('number')
      expect(strokes).toBeGreaterThan(0)
    })
  })

  describe('calcTianGe - 天格计算', () => {
    test('单姓天格 = 姓笔画 + 1', () => {
      const tianGe = calculator.calcTianGe('李', false)
      const liStrokes = calculator.getStrokeCount('李')
      expect(tianGe).toBe(liStrokes + 1)
    })

    test('复姓天格 = 两个姓笔画相加', () => {
      const tianGe = calculator.calcTianGe('欧阳', true)
      expect(tianGe).toBeGreaterThan(10)
    })
  })

  describe('calcDiGe - 地格计算', () => {
    test('地格 = 名字笔画总和', () => {
      const diGe = calculator.calcDiGe('俊豪')
      const expectedStrokes = calculator.getStrokeCount('俊') + calculator.getStrokeCount('豪')
      expect(diGe).toBe(expectedStrokes)
    })

    test('单字名地格 = 名笔画', () => {
      const diGe = calculator.calcDiGe('豪')
      expect(diGe).toBe(calculator.getStrokeCount('豪'))
    })
  })

  describe('calcRenGe - 人格计算', () => {
    test('人格 = 姓笔画 + 名第一字笔画', () => {
      const renGe = calculator.calcRenGe('李', '俊')
      const expected = calculator.getStrokeCount('李') + calculator.getStrokeCount('俊')
      expect(renGe).toBe(expected)
    })
  })

  describe('calcZongGe - 总格计算', () => {
    test('总格 = 姓名所有笔画总和', () => {
      const zongGe = calculator.calcZongGe('李', '俊豪')
      const expected = calculator.getStrokeCount('李') + calculator.getStrokeCount('俊') + calculator.getStrokeCount('豪')
      expect(zongGe).toBe(expected)
    })
  })

  describe('isLuckyNum - 吉凶判定', () => {
    test('吉数判定', () => {
      expect(calculator.isLuckyNum(1)).toBe(true)
      expect(calculator.isLuckyNum(3)).toBe(true)
      expect(calculator.isLuckyNum(13)).toBe(true)
      expect(calculator.isLuckyNum(15)).toBe(true)
    })

    test('凶数判定', () => {
      expect(calculator.isLuckyNum(2)).toBe(false)
      expect(calculator.isLuckyNum(4)).toBe(false)
      expect(calculator.isLuckyNum(9)).toBe(false)
      expect(calculator.isLuckyNum(10)).toBe(false)
    })
  })

  describe('calcWuGe - 完整三才五格计算', () => {
    test('应该返回完整的五格数据', () => {
      const wuGe = calculator.calcWuGe('李', '俊豪')
      
      expect(wuGe.tian).toBeDefined()
      expect(wuGe.di).toBeDefined()
      expect(wuGe.ren).toBeDefined()
      expect(wuGe.wai).toBeDefined()
      expect(wuGe.zong).toBeDefined()
    })

    test('五格应该包含value、lucky、meaning', () => {
      const wuGe = calculator.calcWuGe('李', '俊豪')
      
      for (const [key, data] of Object.entries(wuGe)) {
        expect(data).toHaveProperty('value')
        expect(data).toHaveProperty('lucky')
        expect(data).toHaveProperty('meaning')
        expect(['吉', '凶']).toContain(data.lucky)
      }
    })
  })

  describe('getNumMeaning - 数理含义', () => {
    test('应该返回正确的数理含义', () => {
      const meaning1 = calculator.getNumMeaning(1)
      expect(typeof meaning1).toBe('string')
      expect(meaning1.length).toBeGreaterThan(0)
    })

    test('未知数理应返回默认值', () => {
      const meaning = calculator.getNumMeaning(999)
      expect(meaning).toBe('数理不明')
    })
  })

  describe('analyzeSanCai - 三才分析', () => {
    test('应该返回三才配置分析', () => {
      const result = calculator.analyzeSanCai('木', '火', '木')
      
      expect(result).toBeDefined()
      expect(result.config).toBeDefined()
      expect(result.meaning).toBeDefined()
    })
  })
})
