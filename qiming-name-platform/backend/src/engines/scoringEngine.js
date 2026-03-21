import wuGeCalculator from './wuGeCalculator.js'
import baziCalculator from './baziCalculator.js'

export class ScoringEngine {
  constructor() {
    this.wuGe = wuGeCalculator
    this.bazi = baziCalculator
  }

  async scoreName(name, surname, givenName, gender, birthDate, birthTime) {
    const pinyin = this.getPinyin(name)
    const strokes = this.getStrokes(name)
    const fiveElement = this.getFiveElement(givenName)
    const wuGe = this.wuGe.calcWuGe(surname, givenName)
    
    const scores = {
      yin: this.scoreYin(pinyin),
      xing: this.scoreXing(name),
      yi: 80,
      shu: this.scoreShu(wuGe),
      li: 80,
      yun: 80,
      jing: this.scoreJing(wuGe),
      de: 80,
      ming: birthDate ? await this.scoreMing(surname, givenName, birthDate, birthTime) : 80
    }
    
    const total = this.calcTotalScore(scores)
    
    return {
      name,
      pinyin,
      strokes,
      fiveElement,
      wuGe,
      scores,
      total,
      level: this.getScoreLevel(total)
    }
  }

  scoreYin(pinyin) {
    let score = 100
    const tones = this.extractTones(pinyin)
    
    if (tones.length >= 2) {
      if (tones[0] === tones[1]) score -= 10
      
      for (let i = 1; i < tones.length; i++) {
        if (tones[i] === tones[i-1]) score -= 5
      }
      
      if (this.hasGoodRhythm(tones)) score += 5
    }
    
    return Math.max(0, Math.min(100, score))
  }

  extractTones(pinyin) {
    const toneMap = {
      'a': 1, 'o': 1, 'e': 1, 'i': 1, 'u': 1, 'v': 1,
      'ai': 2, 'ei': 2, 'ui': 2, 'ao': 2, 'ou': 2, 'iu': 2, 'ie': 2, 've': 2,
      'an': 3, 'en': 3, 'in': 3, 'un': 3, 'vn': 3,
      'ang': 4, 'eng': 4, 'ing': 4, 'ong': 4
    }
    
    return pinyin.split(' ').map(py => {
      const lower = py.toLowerCase()
      for (const [ending, tone] of Object.entries(toneMap)) {
        if (lower.endsWith(ending)) return tone
      }
      return 1
    })
  }

  hasGoodRhythm(tones) {
    if (tones.length < 2) return false
    let rises = 0, falls = 0
    for (let i = 1; i < tones.length; i++) {
      if (tones[i] > tones[i-1]) rises++
      else if (tones[i] < tones[i-1]) falls++
    }
    return rises > 0 && falls > 0
  }

  scoreXing(name) {
    let score = 100
    const chars = name.split('')
    
    for (const char of chars) {
      const strokes = this.wuGe.getStrokeCount(char)
      if (strokes > 25 || strokes < 1) score -= 10
      if (this.isRadicalBalanced(char)) score += 5
    }
    
    if (chars.length === 2 && this.wuGe.getStrokeCount(chars[0]) === this.wuGe.getStrokeCount(chars[1])) {
      score -= 15
    }
    
    return Math.max(0, Math.min(100, score))
  }

  isRadicalBalanced(char) {
    return true
  }

  scoreShu(wuGe) {
    let score = 60
    
    const allLucky = [wuGe.tian.lucky, wuGe.di.lucky, wuGe.ren.lucky, wuGe.wai.lucky, wuGe.zong.lucky].every(l => l === '吉')
    if (allLucky) score = 100
    else if (wuGe.ren.lucky === '凶') score -= 30
    else if (wuGe.ren.lucky === '吉') score += 20
    
    const tianDiSheng = this.isMutualGenerate(wuGe.ren.wuXing, wuGe.tian.wuXing) && 
                         this.isMutualGenerate(wuGe.ren.wuXing, wuGe.di.wuXing)
    if (tianDiSheng) score += 15
    
    return Math.max(0, Math.min(100, score))
  }

  isMutualGenerate(from, to) {
    const relations = {
      '木': '火', '火': '土', '土': '金', '金': '水', '水': '木'
    }
    return relations[from] === to
  }

  scoreJing(wuGe) {
    const renScore = wuGe.ren.value
    if (renScore >= 13 && renScore <= 31) return 90
    if (renScore >= 32 && renScore <= 45) return 70
    return 60
  }

  async scoreMing(surname, givenName, birthDate, birthTime) {
    if (!birthDate) return 80
    
    const [year, month, day] = birthDate.split('-').map(Number)
    const hour = parseInt(birthTime) || 12
    
    const analysis = this.bazi.getFullAnalysis(year, month, day, hour)
    const nameElement = this.getFiveElement(givenName)
    
    if (nameElement === analysis.xiYongSheng) return 100
    if (this.isMutualGenerate(nameElement, analysis.xiYongSheng)) return 90
    if (nameElement === analysis.jiShen) return 40
    
    return 70
  }

  getPinyin(name) {
    return name.split('').map(c => this.charToPinyin(c)).join(' ')
  }

  charToPinyin(char) {
    return char
  }

  getStrokes(name) {
    return name.split('').map(c => this.wuGe.getStrokeCount(c))
  }

  getFiveElement(givenName) {
    const char = givenName[0] || ''
    const element = this.wuGe.wuXingMap[char]
    return element || '土'
  }

  calcTotalScore(scores) {
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
    
    let total = 0
    for (const [key, weight] of Object.entries(weights)) {
      total += (scores[key] || 80) * weight
    }
    
    return Math.round(total)
  }

  getScoreLevel(score) {
    if (score >= 90) return '大吉'
    if (score >= 80) return '吉'
    if (score >= 70) return '中吉'
    if (score >= 60) return '小吉'
    if (score >= 50) return '平'
    return '凶'
  }
}

export default new ScoringEngine()
