export const CLOUD_NAMES = {
  aliyun: '阿里云盘',
  baidu: '百度网盘',
  quark: '夸克网盘',
  xunlei: '迅雷云盘',
  pan115: '115网盘',
  tianyi: '天翼云盘',
  mobile: '移动云盘',
  other: '其他',
}

export const RES_TYPE_NAMES = {
  video: '视频',
  software: '软件',
  doc: '文档',
  music: '音乐',
  image: '图片',
  other: '其他',
}

export const CLOUD_ICONS = {
  aliyun: '🚀',
  baidu: '☁️',
  quark: '⚡',
  xunlei: '🌊',
  pan115: '🔢',
  tianyi: '🌤️',
  mobile: '📱',
  other: '📦',
}

export const RES_TYPE_TAGS = {
  video: 'danger',
  software: 'success',
  doc: 'primary',
  music: 'warning',
  image: 'info',
  other: 'info',
}

export function formatTime(iso) {
  if (!iso) return ''
  const d = new Date(iso)
  const diff = Date.now() - d.getTime()
  const min = Math.floor(diff / 60000)
  if (min < 1) return '刚刚'
  if (min < 60) return `${min} 分钟前`
  const hour = Math.floor(min / 60)
  if (hour < 24) return `${hour} 小时前`
  const day = Math.floor(hour / 24)
  if (day < 30) return `${day} 天前`
  return d.toLocaleDateString('zh-CN')
}

export function highlightTitle(title, keyword) {
  if (!keyword || !keyword.trim()) return [{ text: title, hit: false }]
  const tokens = keyword
    .trim()
    .split(/\s+/)
    .filter(Boolean)
    .flatMap((w) => {
      const parts = []
      let buf = ''
      let latin = ''
      for (const ch of w) {
        if (/[\u4e00-\u9fa5]/.test(ch)) {
          if (latin) {
            parts.push(latin)
            latin = ''
          }
          buf += ch
          if (buf.length === 2) {
            parts.push(buf)
            buf = ''
          }
        } else {
          buf = ''
          latin += ch
        }
      }
      if (latin) parts.push(latin)
      return parts
    })
    .filter((t) => t.length >= 2)
  if (tokens.length === 0) return [{ text: title, hit: false }]

  const marks = new Array(title.length).fill(false)
  const lowerTitle = title.toLowerCase()
  for (const token of tokens) {
    const lowerToken = token.toLowerCase()
    let idx = lowerTitle.indexOf(lowerToken)
    while (idx !== -1) {
      for (let i = idx; i < idx + token.length; i++) marks[i] = true
      idx = lowerTitle.indexOf(lowerToken, idx + 1)
    }
  }

  const segments = []
  let current = ''
  let currentState = marks[0]
  for (let i = 0; i < title.length; i++) {
    if (marks[i] === currentState) {
      current += title[i]
    } else {
      segments.push({ text: current, hit: currentState })
      current = title[i]
      currentState = marks[i]
    }
  }
  if (current) segments.push({ text: current, hit: currentState })
  return segments
}
