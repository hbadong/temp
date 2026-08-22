const { prepare, now } = require('../db');
const { importMessages } = require('./store');

const TITLE_TEMPLATES = [
  '《{name}》{quality} 全集 国语中字',
  '{name} {quality} 高码率合集',
  '{name} 最新版 破解直装版',
  '{name} 官方原版 + 激活工具',
  '{name} 从入门到精通 视频教程',
  '{name} 电子书 PDF 合集',
  '{name} 无损音乐 FLAC 专辑合集',
  '{name} 高清壁纸素材包 {count}张',
  '{name} 全季 4K HDR 中英双字',
  '{name} 便携绿色版 免安装',
];

const NAMES = [
  '三体', '流浪地球2', '繁花', '庆余年第二季', '狂飙', '漫长的季节', '甄嬛传',
  '哈利波特全集', '指环王三部曲', '星际穿越', '盗梦空间', '奥本海默', '沙丘2',
  'Photoshop 2024', 'Premiere Pro 2024', 'AutoCAD 2025', 'Office 2021', 'MATLAB R2024',
  'Python 数据分析', 'Java 核心技术', '考研数学复习全书', '小学奥数教程', '新华字典电子版',
  '周杰伦专辑合集', 'Taylor Swift 无损合集', '经典老歌500首', '久石让钢琴曲集',
  '国风插画素材', '4K风景壁纸', 'PPT模板精选', '摄影后期预设',
];

const QUALITIES = ['4K', '1080P', 'HDR', '60帧'];
const COUNTS = [100, 300, 500, 1000, 2000];

const CLOUD_LINK_BUILDERS = {
  aliyun: () => `https://www.alipan.com/s/${randStr(11)}`,
  baidu: () => `https://pan.baidu.com/s/${randStr(23)}?pwd=${randStr(4).toLowerCase()}`,
  quark: () => `https://pan.quark.cn/s/${randStr(12)}`,
  xunlei: () => `https://pan.xunlei.com/s/${randStr(14)}`,
  pan115: () => `https://115.com/s/${randStr(8)}`,
};

const CHANNEL_POOL = ['@aliyunpanshare', '@kuakeziyuan', '@bdypandian', '@xunleipan', '@panSharePro'];

function randStr(len) {
  const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
  let s = '';
  for (let i = 0; i < len; i++) s += chars[Math.floor(Math.random() * chars.length)];
  return s;
}

function pick(arr) {
  return arr[Math.floor(Math.random() * arr.length)];
}

function buildMessage(index, ts) {
  const name = NAMES[index % NAMES.length];
  let title = pick(TITLE_TEMPLATES)
    .replace('{name}', name)
    .replace('{quality}', pick(QUALITIES))
    .replace('{count}', String(pick(COUNTS)));
  if (index % 3 === 0) title = `${title} #网盘资源`;
  const cloudType = pick(Object.keys(CLOUD_LINK_BUILDERS));
  const link = CLOUD_LINK_BUILDERS[cloudType]();
  const pwdSuffix = cloudType === 'baidu' ? ` 提取码：${link.match(/pwd=([a-z0-9]{4})/i)[1]}` : '';
  return {
    text: `${title}\n${link}${pwdSuffix}`,
    channel: pick(CHANNEL_POOL),
    date: ts,
    _cloudType: cloudType,
    _title: title,
    _link: link,
  };
}

function generateBatch(count, baseTime) {
  const messages = [];
  for (let i = 0; i < count; i++) {
    const m = buildMessage(i, new Date(baseTime - i * 60000).toISOString());
    const parsed = require('../parser').parseMessage(m.text, m.channel, m.date);
    if (parsed) messages.push(parsed);
  }
  return messages;
}

function seedDatabase(count) {
  const total = count || parseInt(process.env.SEED_COUNT, 10) || 2000;
  const existing = prepare('SELECT COUNT(*) AS c FROM resources').get().c;
  if (existing > 0) return { seeded: false, existing };
  const base = Date.now();
  const batchSize = 500;
  let inserted = 0;
  for (let offset = 0; offset < total; offset += batchSize) {
    const batch = [];
    for (let i = 0; i < Math.min(batchSize, total - offset); i++) {
      const m = buildMessage(offset + i, new Date(base - (offset + i) * 3600000).toISOString());
      const parsed = require('../parser').parseMessage(m.text, m.channel, m.date);
      if (parsed) batch.push(parsed);
    }
    inserted += importMessages(batch, 'seed').inserted;
  }
  for (const ch of CHANNEL_POOL) {
    prepare('INSERT OR IGNORE INTO channels (username, note, created_at) VALUES (?, ?, ?)')
      .run(ch, '内置演示频道', now());
  }
  return { seeded: true, inserted };
}

const FRESH_TITLES = [
  '最新上映院线电影合集 4K 中字',
  '热门剧集更新至大结局 全集打包',
  'Adobe 全家桶 2025 直装破解版',
  '公务员考试真题库 2026 版 PDF',
  '儿童英语启蒙动画 全季',
  '健身教练一对一教学视频',
  'AI 绘画提示词宝典 v3',
  '经典粤语金曲无损合集',
];

function collectFreshRound() {
  const count = 3 + Math.floor(Math.random() * 5);
  const messages = [];
  for (let i = 0; i < count; i++) {
    const title = `${pick(FRESH_TITLES)} ${pick(QUALITIES)}`;
    const cloudType = pick(Object.keys(CLOUD_LINK_BUILDERS));
    const link = CLOUD_LINK_BUILDERS[cloudType]();
    const text = `${title}\n${link}`;
    const parsed = require('../parser').parseMessage(text, pick(CHANNEL_POOL), new Date().toISOString());
    if (parsed) messages.push(parsed);
  }
  const result = importMessages(messages, 'simulator');
  return result;
}

function startScheduler(intervalMs) {
  const ms = intervalMs || parseInt(process.env.COLLECT_INTERVAL_MS, 10) || 30000;
  const timer = setInterval(() => {
    try {
      collectFreshRound();
    } catch (err) {
      console.error('[collector] round failed:', err.message);
    }
  }, ms);
  timer.unref();
  return timer;
}

module.exports = { seedDatabase, collectFreshRound, startScheduler, generateBatch, CHANNEL_POOL };
