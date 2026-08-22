#!/usr/bin/env node
const MODE = process.argv[2] || 'help';
const COUNT = parseInt(process.argv[3], 10) || 100000;

const { db, ftsText } = require('../src/db');
const parser = require('../src/parser');
const { insertResource, search } = require('../src/services/store');

const NAMES = [
  '三体', '流浪地球2', '繁花', '庆余年第二季', '狂飙', '漫长的季节', '甄嬛传',
  '哈利波特全集', '指环王三部曲', '星际穿越', '盗梦空间', '奥本海默', '沙丘2',
  'Photoshop 2024', 'Premiere Pro 2024', 'AutoCAD 2025', 'Office 2021', 'MATLAB R2024',
  'Python 数据分析', 'Java 核心技术', '考研数学复习全书', '小学奥数教程',
  '周杰伦专辑合集', '无损音乐合集', '经典老歌500首', '国风插画素材', '4K风景壁纸',
];
const TPL = [
  '{n} 4K 全集 国语中字', '{n} 1080P 高码率合集', '{n} 破解直装版', '{n} PDF 合集',
  '{n} FLAC 无损专辑', '{n} 全季 HDR 中英双字', '{n} 便携绿色版 免安装', '{n} 视频教程全集',
];
const CLOUDS = ['aliyun', 'baidu', 'quark', 'xunlei', 'pan115'];
const CHANS = ['@ch1', '@ch2', '@ch3', '@ch4', '@ch5'];
const CHARS = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';

function randStr(len) {
  let s = '';
  for (let i = 0; i < len; i++) s += CHARS[Math.floor(Math.random() * CHARS.length)];
  return s;
}

function buildOne(i) {
  const title = TPL[i % TPL.length].replace('{n}', NAMES[Math.floor(Math.random() * NAMES.length)]);
  const c = CLOUDS[Math.floor(Math.random() * CLOUDS.length)];
  const linkMap = {
    aliyun: `https://www.alipan.com/s/${randStr(11)}`,
    baidu: `https://pan.baidu.com/s/${randStr(23)}?pwd=${randStr(4).toLowerCase()}`,
    quark: `https://pan.quark.cn/s/${randStr(12)}`,
    xunlei: `https://pan.xunlei.com/s/${randStr(14)}`,
    pan115: `https://115.com/s/${randStr(8)}`,
  };
  return parser.parseMessage(
    `${title}\n${linkMap[c]}`,
    CHANS[Math.floor(Math.random() * CHANS.length)],
    new Date(Date.now() - Math.random() * 90 * 24 * 3600 * 1000).toISOString()
  );
}

function seed(count) {
  const existing = db.prepare('SELECT COUNT(*) AS c FROM resources').get().c;
  if (existing > 0) {
    console.log(`库中已有 ${existing} 条，跳过灌入`);
    return;
  }
  const BATCH = 500;
  const begin = db.prepare('BEGIN');
  const commit = db.prepare('COMMIT');
  const started = Date.now();
  let done = 0;
  while (done < count) {
    begin.run();
    for (let i = 0; i < Math.min(BATCH, count - done); i++) {
      const item = buildOne(done + i);
      if (item) insertResource(item);
    }
    commit.run();
    done += BATCH;
    if (done % 10000 === 0) {
      console.log(`已灌入 ${done}/${count}，耗时 ${((Date.now() - started) / 1000).toFixed(1)}s`);
    }
  }
  console.log(`完成：${count} 条，总耗时 ${((Date.now() - started) / 1000).toFixed(1)}s`);
}

function percentile(sorted, p) {
  const idx = Math.min(sorted.length - 1, Math.ceil((p / 100) * sorted.length) - 1);
  return sorted[Math.max(0, idx)];
}

function bench(fn, label, n = 300) {
  const times = [];
  for (let i = 0; i < n; i++) {
    const t0 = performance.now();
    fn(i);
    times.push(performance.now() - t0);
  }
  times.sort((a, b) => a - b);
  console.log(
    `${label.padEnd(16)} P50=${percentile(times, 50).toFixed(2)}ms  P95=${percentile(times, 95).toFixed(2)}ms  P99=${percentile(times, 99).toFixed(2)}ms  max=${times[times.length - 1].toFixed(2)}ms`
  );
}

function query() {
  bench(() => search({ q: '三体', page: 1, size: 20 }), '短词-中文2字');
  bench(() => search({ q: 'photoshop', page: 1, size: 20 }), '单词-英文');
  bench(() => search({ q: '庆余年第二季 全集', page: 1, size: 20 }), '长尾多词');
  bench(() => search({ q: '不存在的词xyz', page: 1, size: 20 }), '零结果词');
  bench((i) => search({ page: 1 + (i % 5), size: 20 }), '无词翻页');
  bench(() => search({ page: 500, size: 20 }), '深分页page500');
  bench(
    (i) => search({ q: '4k', cloud: CLOUDS[i % CLOUDS.length], type: 'video', page: 1, size: 20 }),
    '筛选组合'
  );
}

async function http() {
  const concurrency = parseInt(process.argv[3], 10) || 20;
  const durationSec = parseInt(process.argv[4], 10) || 10;
  const base =
    process.env.BENCH_URL || 'http://localhost:3101/api/search?q=%E4%B8%89%E4%BD%93&size=20';
  let sent = 0, ok = 0, fail = 0;
  const latencies = [];
  const deadline = Date.now() + durationSec * 1000;

  async function worker() {
    while (Date.now() < deadline) {
      const t0 = performance.now();
      try {
        const r = await fetch(base);
        await r.arrayBuffer();
        if (r.status === 200) ok++;
        else fail++;
      } catch {
        fail++;
      }
      latencies.push(performance.now() - t0);
      sent++;
    }
  }

  await Promise.all(Array.from({ length: concurrency }, worker));
  latencies.sort((a, b) => a - b);
  console.log(`并发=${concurrency} 时长=${durationSec}s 请求=${sent} 成功=${ok} 失败=${fail}`);
  console.log(
    `TPS=${Math.round(ok / durationSec)}  P50=${percentile(latencies, 50).toFixed(1)}ms  P95=${percentile(latencies, 95).toFixed(1)}ms  P99=${percentile(latencies, 99).toFixed(1)}ms`
  );
}

(async () => {
  if (MODE === 'prepare') seed(COUNT);
  else if (MODE === 'query') query();
  else if (MODE === 'http') http();
  else console.log('用法: node tools/perf.js prepare|query|http [args]');
})();
