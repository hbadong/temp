import wuGeCalculator from './wuGeCalculator.js'
import baziCalculator from './baziCalculator.js'
import scoringEngine from './scoringEngine.js'

export class RecommendEngine {
  constructor() {
    this.wuGe = wuGeCalculator
    this.bazi = baziCalculator
    this.scoring = scoringEngine
  }

  async recommend(params) {
    const {
      surname,
      gender,
      birthDate,
      birthTime,
      fiveElementPreference,
      expectTags,
      poetryStyle,
      excludeNames = [],
      page = 1,
      pageSize = 20
    } = params

    let candidates = await this.getCandidates(surname, gender, fiveElementPreference)
    
    candidates = candidates.filter(c => !excludeNames.includes(c.fullName))
    
    if (birthDate && birthTime) {
      candidates = await this.filterByBazi(candidates, birthDate, birthTime)
    }
    
    if (expectTags && expectTags.length > 0) {
      candidates = this.filterByExpectTags(candidates, expectTags)
    }
    
    if (poetryStyle) {
      candidates = this.filterByPoetryStyle(candidates, poetryStyle)
    }
    
    candidates = await this.sortByScore(candidates, birthDate, birthTime)
    
    const total = candidates.length
    const start = (page - 1) * pageSize
    const items = candidates.slice(start, start + pageSize)
    
    return {
      items,
      total,
      page,
      pageSize,
      totalPages: Math.ceil(total / pageSize)
    }
  }

  async getCandidates(surname, gender, fiveElementPreference) {
    return []
  }

  async filterByBazi(candidates, birthDate, birthTime) {
    const [year, month, day] = birthDate.split('-').map(Number)
    const hour = parseInt(birthTime) || 12
    
    const analysis = this.bazi.getFullAnalysis(year, month, day, hour)
    
    return candidates.filter(c => {
      const element = this.wuGe.wuXingMap[c.givenName[0]]
      return element === analysis.xiYongSheng || 
             this.isMutualGenerate(element, analysis.xiYongSheng)
    })
  }

  filterByExpectTags(candidates, expectTags) {
    return candidates.filter(c => {
      const nameTags = c.tags || []
      return expectTags.some(tag => nameTags.includes(tag))
    })
  }

  filterByPoetryStyle(candidates, poetryStyle) {
    return candidates.filter(c => c.sourceType === poetryStyle)
  }

  async sortByScore(candidates, birthDate, birthTime) {
    const scored = await Promise.all(
      candidates.map(async c => {
        const score = await this.scoring.scoreName(
          c.fullName,
          c.surname,
          c.givenName,
          c.gender,
          birthDate,
          birthTime
        )
        return { ...c, score: score.total }
      })
    )
    
    return scored.sort((a, b) => b.score - a.score)
  }

  isMutualGenerate(from, to) {
    const relations = {
      '木': '火', '火': '土', '土': '金', '金': '水', '水': '木',
      '木': '水', '水': '金', '金': '土', '土': '火', '火': '木'
    }
    return relations[from] === to
  }

  isMutualRestrain(from, to) {
    const relations = {
      '木': '金', '金': '木',
      '火': '水', '水': '火',
      '土': '木', '木': '土',
      '金': '火', '火': '金',
      '水': '土', '土': '水'
    }
    return relations[from] === to
  }
}

export default new RecommendEngine()
