const TIAN_GAN = ['甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸']
const DI_ZHI = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥']

const WU_XING_MAP = {
  '甲': '木', '乙': '木',
  '丙': '火', '丁': '火',
  '戊': '土', '己': '土',
  '庚': '金', '辛': '金',
  '壬': '水', '癸': '水',
  '子': '水', '丑': '土', '寅': '木', '卯': '木',
  '辰': '土', '巳': '火', '午': '火', '未': '土',
  '申': '金', '酉': '金', '戌': '土', '亥': '水'
}

const GAN_NAMES = ['甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸']
const ZHI_NAMES = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥']

const ZHI_SCORE = {
  '子': 1, '丑': 2, '寅': 3, '卯': 4, '辰': 5, '巳': 6,
  '午': 7, '未': 8, '申': 9, '酉': 10, '戌': 11, '亥': 12
}

const LUCKY_WUGE = [1, 3, 5, 6, 7, 8, 11, 13, 15, 16, 17, 18, 21, 23, 24, 25, 29, 31, 32, 33, 35, 37, 39, 41, 45, 47, 48, 52, 57, 61, 63, 65, 67, 68, 81]

export class BaziCalculator {
  constructor() {
    this.tianGan = TIAN_GAN
    this.diZhi = DI_ZHI
    this.wuXingMap = WU_XING_MAP
  }

  calcBaZi(year, month, day, hour) {
    const yearGanZhi = this.calcYearGanZhi(year)
    const monthGanZhi = this.calcMonthGanZhi(year, month)
    const dayGanZhi = this.calcDayGanZhi(year, month, day)
    const hourGanZhi = this.calcHourGanZhi(dayGanZhi, hour)
    
    return {
      year: yearGanZhi,
      month: monthGanZhi,
      day: dayGanZhi,
      hour: hourGanZhi
    }
  }

  calcYearGanZhi(year) {
    const baseYear = 1984
    const offset = year - baseYear
    const ganIndex = ((offset % 10) + 10) % 10
    const zhiIndex = ((offset % 12) + 12) % 12
    return {
      gan: this.tianGan[ganIndex],
      zhi: this.diZhi[zhiIndex],
      branch: this.tianGan[ganIndex] + this.diZhi[zhiIndex],
      wuXing: this.wuXingMap[this.tianGan[ganIndex]]
    }
  }

  calcMonthGanZhi(year, month) {
    const yearGan = this.calcYearGanZhi(year).gan
    const monthGanIndex = this.getMonthGanIndex(yearGan, month)
    return {
      gan: this.tianGan[monthGanIndex],
      zhi: this.diZhi[month - 1],
      branch: this.tianGan[monthGanIndex] + this.diZhi[month - 1],
      wuXing: this.wuXingMap[this.tianGan[monthGanIndex]]
    }
  }

  getMonthGanIndex(yearGan, month) {
    const startIndex = GAN_NAMES.indexOf(yearGan)
    const monthZhiIndex = month - 1
    return (startIndex * 2 + monthZhiIndex) % 10
  }

  calcDayGanZhi(year, month, day) {
    const baseDate = new Date(1984, 0, 1)
    const targetDate = new Date(year, month - 1, day)
    const diffDays = Math.floor((targetDate - baseDate) / (1000 * 60 * 60 * 24))
    const dayIndex = diffDays % 60
    return {
      gan: this.tianGan[dayIndex % 10],
      zhi: this.diZhi[dayIndex % 12],
      branch: this.tianGan[dayIndex % 10] + this.diZhi[dayIndex % 12],
      wuXing: this.wuXingMap[this.tianGan[dayIndex % 10]]
    }
  }

  calcHourGanZhi(dayGanZhi, hour) {
    const dayGan = dayGanZhi.gan
    const ganIndex = GAN_NAMES.indexOf(dayGan)
    
    let hourZhiIndex
    if (hour >= 23 || hour < 1) {
      hourZhiIndex = 0
    } else {
      hourZhiIndex = Math.floor((hour + 1) / 2) % 12
    }
    
    const hourGanIndex = (ganIndex * 2 + hourZhiIndex) % 10
    
    return {
      gan: this.tianGan[hourGanIndex],
      zhi: this.diZhi[hourZhiIndex],
      branch: this.tianGan[hourGanIndex] + this.diZhi[hourZhiIndex],
      wuXing: this.wuXingMap[this.tianGan[hourGanIndex]]
    }
  }

  analyzeFiveElements(bazi) {
    const elements = {
      '木': 0,
      '火': 0,
      '土': 0,
      '金': 0,
      '水': 0
    }
    
    for (const pillar of [bazi.year, bazi.month, bazi.day, bazi.hour]) {
      const wuXing = this.wuXingMap[pillar.gan] || this.wuXingMap[pillar.zhi]
      if (wuXing && elements.hasOwnProperty(wuXing)) {
        elements[wuXing]++
      }
    }
    
    return elements
  }

  calcDayMasterStrength(fiveElements, dayGan) {
    const dayWuXing = this.wuXingMap[dayGan]
    let strength = 0
    
    const selfElement = fiveElements[dayWuXing]
    strength += (selfElement - 1) * 20
    
    const supportRelations = {
      '木': '水',
      '火': '木',
      '土': '火',
      '金': '土',
      '水': '金'
    }
    const supportElement = supportRelations[dayWuXing]
    if (fiveElements[supportElement] > 0) {
      strength += fiveElements[supportElement] * 10
    }
    
    const restrainRelations = {
      '木': '金',
      '火': '水',
      '土': '木',
      '金': '火',
      '水': '土'
    }
    const restrainElement = restrainRelations[dayWuXing]
    if (fiveElements[restrainElement] > 0) {
      strength -= fiveElements[restrainElement] * 15
    }
    
    return Math.max(-100, Math.min(100, strength))
  }

  determineXiYongSheng(fiveElements, dayMasterStrength, dayGan) {
    const dayWuXing = this.wuXingMap[dayGan]
    
    if (dayMasterStrength < -30) {
      const supportRelations = {
        '木': '水',
        '火': '木',
        '土': '火',
        '金': '土',
        '水': '金'
      }
      return supportRelations[dayWuXing]
    } else if (dayMasterStrength > 30) {
      const restrainRelations = {
        '木': '金',
        '火': '水',
        '土': '木',
        '金': '火',
        '水': '土'
      }
      return restrainRelations[dayWuXing]
    }
    
    let maxElement = dayWuXing
    let maxCount = fiveElements[dayWuXing]
    
    for (const [element, count] of Object.entries(fiveElements)) {
      if (element !== dayWuXing && count > maxCount) {
        maxCount = count
        maxElement = element
      }
    }
    
    return maxElement
  }

  determineJiShen(xiYongSheng) {
    const oppositeElements = {
      '木': '金',
      '火': '水',
      '土': '木',
      '金': '火',
      '水': '土'
    }
    return oppositeElements[xiYongSheng]
  }

  getFullAnalysis(year, month, day, hour) {
    const bazi = this.calcBaZi(year, month, day, hour)
    const fiveElements = this.analyzeFiveElements(bazi)
    const dayMasterStrength = this.calcDayMasterStrength(fiveElements, bazi.day.gan)
    const xiYongSheng = this.determineXiYongSheng(fiveElements, dayMasterStrength, bazi.day.gan)
    const jiShen = this.determineJiShen(xiYongSheng)
    
    return {
      bazi,
      fiveElements,
      dayMasterStrength,
      xiYongSheng,
      jiShen,
      dayMaster: bazi.day.gan,
      dayWuXing: this.wuXingMap[bazi.day.gan],
      strengthLevel: this.getStrengthLevel(dayMasterStrength)
    }
  }

  getStrengthLevel(strength) {
    if (strength < -50) return '极弱'
    if (strength < -30) return '较弱'
    if (strength < 0) return '偏弱'
    if (strength === 0) return '平和'
    if (strength < 30) return '偏强'
    if (strength < 50) return '较强'
    return '极强'
  }
}

export default new BaziCalculator()
