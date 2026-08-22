const CLOUD_PATTERNS = [
  { type: 'aliyun', name: '阿里云盘', re: /(?:https?:\/\/)?(?:www\.)?(alipan\.com|aliyundrive\.com)\/[A-Za-z0-9]+/i },
  { type: 'baidu', name: '百度网盘', re: /(?:https?:\/\/)?(?:www\.)?pan\.baidu\.com\/(?:s\/[A-Za-z0-9_-]+|share\/init\?surl=[A-Za-z0-9_-]+)/i },
  { type: 'quark', name: '夸克网盘', re: /(?:https?:\/\/)?(?:www\.)?pan\.quark\.cn\/s\/[A-Za-z0-9]+/i },
  { type: 'xunlei', name: '迅雷云盘', re: /(?:https?:\/\/)?(?:www\.)?pan\.xunlei\.com\/s\/[A-Za-z0-9_-]+/i },
  { type: 'pan115', name: '115网盘', re: /(?:https?:\/\/)?(?:www\.)?(?:115\.com|115cdn\.com)\/s\/[A-Za-z0-9]+/i },
  { type: 'tianyi', name: '天翼云盘', re: /(?:https?:\/\/)?(?:www\.)?cloud\.189\.cn\/[A-Za-z0-9/]+/i },
  { type: 'mobile', name: '移动云盘', re: /(?:https?:\/\/)?(?:www\.)?caiyun\.139\.com\/[A-Za-z0-9/]+/i },
];

const CLOUD_NAMES = {
  aliyun: '阿里云盘',
  baidu: '百度网盘',
  quark: '夸克网盘',
  xunlei: '迅雷云盘',
  pan115: '115网盘',
  tianyi: '天翼云盘',
  mobile: '移动云盘',
  other: '其他',
};

const PWD_PATTERNS = [
  /提取码\s*[:：=]?\s*([A-Za-z0-9]{4})/,
  /访问码\s*[:：=]?\s*([A-Za-z0-9]{4})/,
  /密码\s*[:：=]?\s*([A-Za-z0-9]{4})/,
  /\?pwd=([A-Za-z0-9]{4})/i,
];

const RES_TYPE_RULES = [
  { type: 'video', re: /(4k|8k|1080p|720p|高清|蓝光|全集|全季|电影|剧集|电视剧|纪录片|动漫| anime |影片|预告片|国语|粤语|中字)/i },
  { type: 'software', re: /(软件|破解|直装|激活|绿色版|便携版|安装包|apk|exe|dmg|客户端|app|插件|ps |pr |ae |office|cad)/i },
  { type: 'doc', re: /(pdf|文档|电子书|教程|笔记|题库|试卷|课件|epub|mobi|资料合集)/i },
  { type: 'music', re: /(无损|flac|ape|mp3|音乐|专辑|歌曲|音源)/i },
  { type: 'image', re: /(壁纸|图集|插画|素材|psd|字体|摄影图)/i },
];

function detectCloud(text) {
  for (const p of CLOUD_PATTERNS) {
    const m = text.match(p.re);
    if (m) return { type: p.type, name: p.name, link: m[0].replace(/[)）\]]+$/, '') };
  }
  return null;
}

function detectResType(title) {
  for (const r of RES_TYPE_RULES) {
    if (r.re.test(title)) return r.type;
  }
  return 'other';
}

function extractPwd(text, link) {
  if (link) {
    const m = link.match(/\?pwd=([A-Za-z0-9]{4})/i);
    if (m) return m[1];
  }
  for (const p of PWD_PATTERNS) {
    const m = text.match(p);
    if (m) return m[1];
  }
  return null;
}

function buildTitle(text, link) {
  let title = text
    .replace(/https?:\/\/\S+/g, '')
    .replace(/(?:提取码|访问码|密码)\s*[:：=]?\s*[A-Za-z0-9]{4}/g, '')
    .replace(/[#@]\S+/g, '')
    .replace(/[【】\[\]()（）]/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();
  if (!title) title = link;
  return title.slice(0, 120).trim();
}

function parseMessage(text, channel, date) {
  if (!text || typeof text !== 'string') return null;
  const cloud = detectCloud(text);
  if (!cloud) return null;
  const password = extractPwd(text, cloud.link);
  const title = buildTitle(text, cloud.link);
  if (title.length < 2) return null;
  return {
    fingerprint: `tg:${cloud.link}`,
    title,
    link: cloud.link,
    password,
    cloud_type: cloud.type,
    res_type: detectResType(title),
    channel: channel || null,
    published_at: date || new Date().toISOString(),
  };
}

module.exports = { parseMessage, detectCloud, CLOUD_NAMES, CLOUD_PATTERNS };
