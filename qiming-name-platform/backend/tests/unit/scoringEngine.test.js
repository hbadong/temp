import { describe, test, expect, beforeEach } from '@jest/globals'
import ScoringEngine from '../src/engines/scoringEngine.js'

describe('测名打分引擎', () => {
  let scoringEngine

  beforeEach(() => {
    scoringEngine = new ScoringEngine()
  })

  describe('scoreName - 名字评分', () => {
    test('应该返回完整的评分结果', async () => {
      const result = await scoringEngine.scoreName('李俊豪', '李', '俊豪', 1, null, null)
      
      expect(result.name).toBe('李俊豪')
      expect(result.pinyin).toBeDefined()
      expect(result.strokes).toBeDefined()
      expect(result.fiveElement).toBeDefined()
      expect(result.wuGe).toBeDefined()
      expect(result.scores).toBeDefined()
      expect(result.total).toBeDefined()
      expect(result.level).toBeDefined()
    })

    test('评分应该在0-100范围内', async () => {
      const result = await scoringEngine.scoreName('李俊豪', '李', '俊豪', 1, null, null)
      
      expect(result.total).toBeGreaterThanOrEqual(0)
      expect(result.total).toBeLessThanOrEqual(100)
    })

    test('各维度评分应该在0-100范围内', async () => {
      const result = await scoringEngine.scoreName('李俊豪', '李', '俊豪', 1, null, null)
      
      for (const [key, score] of Object.entries(result.scores)) {
        expect(score).toBeGreaterThanOrEqual(0)
        expect(score).toBeLessThanOrEqual(100)
      }
    })
  })

  describe('scoreYin - 音维度评分', () => {
    test('单字拼音应返回有效评分', () => {
      const score = scoringEngine.scoreYin('li')
      expect(score).toBeGreaterThanOrEqual(0)
      expect(score).toBeLessThanOrEqual(100)
    })

    test('多字拼音应返回有效评分', () => {
      const score = scoringEngine.scoreYin('li jun hao')
      expect(score).toBeGreaterThanOrEqual(0)
      expect(score).toBeLessThanOrEqual(100)
    })
  })

  describe('extractTones - 声调提取', () => {
    test('应该正确提取声调', () => {
      const tones = scoringEngine.extractTones('li')
      expect(Array.isArray(tones)).toBe(true)
      expect(tones.length).toBeGreaterThan(0)
    })

    test('多字拼音应提取多个声调', () => {
      const tones = scoringEngine.extractTones('li jun hao')
      expect(tones.length).toBe(3)
    })
  })

  describe('scoreXing - 形维度评分', () => {
    test('单字名字应返回有效评分', () => {
      const score = scoringEngine.scoreXing('李')
      expect(score).toBeGreaterThanOrEqual(0)
      expect(score).toBeLessThanOrEqual(100)
    })

    test('双字名字应返回有效评分', () => {
      const score = scoringEngine.scoreXing('李俊')
      expect(score).toBeGreaterThanOrEqual(0)
      expect(score).toBeLessThanOrEqual(100)
    })
  })

  describe('scoreShu - 数维度评分', () => {
    test('应该基于三才五格评分', () => {
      const wuGe = {
        tian: { lucky: '吉', value: 10 },
        di: { lucky: '吉', value: 10 },
        ren: { lucky: '吉', value: 16 },
        wai: { lucky: '凶', value: 5 },
        zong: { lucky: '吉', value: 20 }
      }
      
      const score = scoringEngine.scoreShu(wuGe)
      expect(score).toBeGreaterThanOrEqual(0)
      expect(score).toBeLessThanOrEqual(100)
    })

    test('全吉应得高分', () => {
      const wuGe = {
        tian: { lucky: '吉', value: 16 },
        di: { lucky: '吉', value: 16 },
        ren: { lucky: '吉', value: 16 },
        wai: { lucky: '吉', value: 16 },
        zong: { lucky: '吉', value: 16 }
      }
      
      const score = scoringEngine.scoreShu(wuGe)
      expect(score).toBe(100)
    })
  })

  describe('getScoreLevel - 评分等级', () => {
    test('应该正确返回评分等级', () => {
      expect(scoringEngine.getScoreLevel(95)).toBe('大吉')
      expect(scoringEngine.getScoreLevel(85)).toBe('吉')
      expect(scoringEngine.getScoreLevel(75)).toBe('中吉')
      expect(scoringEngine.getScoreLevel(65)).toBe('小吉')
      expect(scoringEngine.getScoreLevel(55)).toBe('平')
      expect(scoringEngine.getScoreLevel(30)).toBe('凶')
    })
  })

  describe('getPinyin - 拼音获取', () => {
    test('应该返回姓名的拼音', () => {
      const pinyin = scoringEngine.getPinyin('李俊豪')
      expect(typeof pinyin).toBe('string')
      expect(pinyin.length).toBeGreaterThan(0)
    })
  })

  describe('getStrokes - 笔画获取', () => {
    test('应该返回姓名的笔画数', () => {
      const strokes = scoringEngine.getStrokes('李俊豪')
      expect(Array.isArray(strokes)).toBe(true)
      expect(strokes.length).toBe(3)
    })
  })

  describe('getFiveElement - 五行获取', () => {
    test('应该返回名字的五行属性', () => {
      const element = scoringEngine.getFiveElement('俊')
      expect(['金', '木', '水', '火', '土']).toContain(element)
    })
  })

  describe('calcTotalScore - 综合评分计算', () => {
    test('应该正确计算加权总分', () => {
      const scores = {
        yin: 90,
        xing: 85,
        yi: 88,
        shu: 80,
        li: 82,
        yun: 78,
        jing: 85,
        de: 80,
        ming: 75
      }
      
      const total = scoringEngine.calcTotalScore(scores)
      
      expect(total).toBeGreaterThanOrEqual(0)
      expect(total).toBeLessThanOrEqual(100)
    })

    test('权重总和应为1', () => {
      const weights = {
        yin: 0.12,
        xing: 0.10,
        yi: 0.18,
        shu: 0.15,
        li: 0.15,
        yun: 0.10,
        jing: 0.08,
        de: 0.07,
        ming: 0.05
      }
      
      const totalWeight = Object.values(weights).reduce((sum, w) => sum + w, 0)
      expect(totalWeight).toBeCloseTo(1, 2)
    })
  })
})
