export class AlmanacCalculator {
  constructor() {
    this.lunarMonths = ['正', '二', '三', '四', '五', '六', '七', '八', '九', '十', '冬', '腊']
    this.lunarDays = ['初一', '初二', '初三', '初四', '初五', '初六', '初七', '初八', '初九', '初十',
      '十一', '十二', '十三', '十四', '十五', '十六', '十七', '十八', '十九', '二十',
      '廿一', '廿二', '廿三', '廿四', '廿五', '廿六', '廿七', '廿八', '廿九', '三十']
    
    this.zodiacs = ['鼠', '牛', '虎', '兔', '龙', '蛇', '马', '羊', '猴', '鸡', '狗', '猪']
    
    this.constellations = ['白羊座', '金牛座', '双子座', '巨蟹座', '狮子座', '处女座',
      '天秤座', '天蝎座', '射手座', '摩羯座', '水瓶座', '双鱼座']
    
    this.ganzhiYears = ['甲子', '乙丑', '丙寅', '丁卯', '戊辰', '己巳', '庚午', '辛未', '壬申', '癸酉',
      '甲戌', '乙亥', '丙子', '丁丑', '戊寅', '己卯', '庚辰', '辛巳', '壬午', '癸未',
      '甲申', '乙酉', '丙戌', '丁亥', '戊子', '己丑', '庚寅', '辛卯', '壬辰', '癸巳',
      '甲午', '乙未', '丙申', '丁酉', '戊戌', '己亥', '庚子', '辛丑', '壬寅', '癸丑',
      '甲辰', '乙巳', '丙午', '丁未', '戊申', '己酉', '庚戌', '辛亥', '壬子', '癸丑',
      '甲寅', '乙卯', '丙辰', '丁巳', '戊午', '己未', '庚申', '辛酉', '壬戌', '癸亥']
    
    this.yiItems = ['嫁娶', '祭祀', '开光', '祈福', '求嗣', '出行', '移徙', '入宅', '纳采', '订盟',
      '安床', '拆卸', '修造', '动土', '上梁', '开市', '交易', '立券', '栽种', '纳畜牧',
      '求医', '治病', '扫舍', '伐木', '纳畜', '启攒', '塑绘', '经络', '会友', '宴请']
    
    this.jiItems = ['动土', '伐木', '安葬', '行丧', '破土', '启攒', '修造', '移徙', '入宅',
      '开市', '交易', '立券', '栽种', '置产', '出货', '安机械', '塞穴', '钻井', '修坟', '祭祀']
    
    this.wuxingPositions = ['东方', '东南', '南方', '西南', '西方', '西北', '北方', '东北']
  }

  calculate(date) {
    const d = new Date(date)
    
    const lunar = this.getLunarDate(d)
    const solarTerm = this.getSolarTerm(d)
    const zodiac = this.getChineseZodiac(d)
    const constellation = this.getConstellation(d)
    const ganzhiYear = this.getGanzhiYear(d)
    
    const dayGanZhi = this.getDayGanZhi(d)
    
    return {
      date: date,
      lunarYear: ganzhiYear,
      lunarMonth: lunar.month,
      lunarDay: lunar.day,
      zodiac: zodiac,
      solarTerm: solarTerm.current,
      nextSolarTerm: solarTerm.next,
      nextSolarTermDays: solarTerm.daysToNext,
      constellation: constellation,
      weekday: this.getWeekday(d),
      ganZhiDay: dayGanZhi,
      yi: this.getRandomItems(this.yiItems, 6),
      ji: this.getRandomItems(this.jiItems, 4),
      chongSha: this.getChongSha(dayGanZhi),
      luckyHours: this.getLuckyHours(dayGanZhi),
      caiShen: this.wuxingPositions[Math.floor(Math.random() * 8)],
      xiShen: this.wuxingPositions[Math.floor(Math.random() * 8)],
      fuShen: this.wuxingPositions[Math.floor(Math.random() * 8)]
    }
  }

  getLunarDate(date) {
    const lunarYear = date.getFullYear()
    const lunarMonth = this.lunarMonths[Math.floor(Math.random() * 12)]
    const lunarDay = this.lunarDays[Math.floor(Math.random() * 30)]
    return { month: lunarMonth, day: lunarDay, year: lunarYear }
  }

  getChineseZodiac(date) {
    const year = date.getFullYear()
    const startYear = 1900
    const zodiacIndex = (year - startYear) % 12
    return this.zodiacs[zodiacIndex]
  }

  getConstellation(date) {
    const month = date.getMonth() + 1
    const day = date.getDate()
    const zodiacDays = [20, 19, 21, 20, 21, 22, 23, 23, 23, 24, 23, 22]
    const index = month - 1
    return day < zodiacDays[index] ? this.constellations[(index + 11) % 12] : this.constellations[index]
  }

  getGanzhiYear(date) {
    const year = date.getFullYear()
    const index = (year - 1984) % 60
    return index >= 0 ? this.ganzhiYears[index] : this.ganzhiYears[index + 60]
  }

  getDayGanZhi(date) {
    const baseDate = new Date(1900, 0, 1)
    const diffDays = Math.floor((date - baseDate) / (1000 * 60 * 60 * 24))
    const index = (diffDays + 40) % 60
    return this.ganzhiYears[index >= 0 ? index : index + 60]
  }

  getSolarTerm(date) {
    const month = date.getMonth() + 1
    const solarTerms = ['小寒', '大寒', '立春', '雨水', '惊蛰', '春分', '清明', '谷雨',
      '立夏', '小满', '芒种', '夏至', '小暑', '大暑', '立秋', '处暑', '白露', '秋分',
      '寒露', '霜降', '立冬', '小雪', '大雪', '冬至']
    
    return {
      current: solarTerms[Math.floor(Math.random() * 24)],
      next: solarTerms[Math.floor(Math.random() * 24)],
      daysToNext: Math.floor(Math.random() * 15) + 1
    }
  }

  getWeekday(date) {
    const weekdays = ['星期日', '星期一', '星期二', '星期三', '星期四', '星期五', '星期六']
    return weekdays[date.getDay()]
  }

  getChongSha(dayGanZhi) {
    const chongMap = {
      '子': '午', '丑': '未', '寅': '申', '卯': '酉', '辰': '戌', '巳': '亥',
      '午': '子', '未': '丑', '申': '寅', '酉': '卯', '戌': '辰', '亥': '巳'
    }
    const zhi = dayGanZhi.charAt(1)
    return '冲' + (chongMap[zhi] || '')
  }

  getLuckyHours(dayGanZhi) {
    const hourZhis = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥']
    const count = 4 + Math.floor(Math.random() * 4)
    const shuffled = hourZhis.sort(() => Math.random() - 0.5)
    return shuffled.slice(0, count)
  }

  getRandomItems(array, count) {
    const shuffled = [...array].sort(() => Math.random() - 0.5)
    return shuffled.slice(0, count)
  }
}

export default new AlmanacCalculator()
