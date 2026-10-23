/**
 * 金价查询网站系统 — 后端服务
 * 复刻目标：huangjinjiage.cn 的数据管线
 *   原站：浏览器 JSONP → res.huangjinjiage.com.cn/jin.js（新浪行情中继）→ 表格渲染 → 60s 轮询
 *   本站：浏览器 fetch /api/quotes → 本服务中继 hq.sinajs.cn（带 Referer）→ 派生计算 → JSON → 60s 轮询
 */
const http = require("http");
const https = require("https");
const fs = require("fs");
const path = require("path");
const { URL } = require("url");

const PORT = process.env.PORT || 3000;
const PUBLIC_DIR = path.join(__dirname, "public");
const DATA_DIR = path.join(__dirname, "data");
const HISTORY_FILE = path.join(DATA_DIR, "recycle-history.json");

/* ---------- 配置（运维可调，无需改代码；修改后重启服务生效） ---------- */
function loadConfig() {
  const def = {
    site: { name: "金价查询网", notice: "" },
    rates: { usdcnyFallback: 6.67, usdhkdFallback: 7.84 },
    units: { ozToGram: 31.1035, taelToGram: 37.429 },
    derive: {
      touziJintiaoOffset: 16, huishouGoldOffset: -15, auSalesOffset: 3,
      huishouPtFactor: 0.871, huishouPdFactor: 0.673, huishou18kFactor: 0.7123, huishouSilverFactor: 0.694,
    },
    brands: [],
    refresh: { defaultIntervalSec: 60, options: [30, 60, 120] },
    theme: { default: "light", allowUserToggle: true },
  };
  try {
    const u = JSON.parse(fs.readFileSync(path.join(__dirname, "config.json"), "utf8"));
    return Object.assign({}, def, u, {
      rates: Object.assign({}, def.rates, u.rates),
      units: Object.assign({}, def.units, u.units),
      derive: Object.assign({}, def.derive, u.derive),
      refresh: Object.assign({}, def.refresh, u.refresh),
      theme: Object.assign({}, def.theme, u.theme),
    });
  } catch (e) { return def; }
}
const CFG = loadConfig();

/* ---------- 新浪行情中继（与原站 jin.js 同源） ---------- */
const SINA_LIST = "hf_XAU,hf_XPT,hf_XAG,hf_XPD,gds_AUTD,gds_AGTD,fx_susdcny,fx_susdhkd";
const SINA_URL = `https://hq.sinajs.cn/list=${SINA_LIST}`;

let quoteCache = { data: null, at: 0, stale: false };
const CACHE_MS = 3000;

function fetchSina() {
  return new Promise((resolve, reject) => {
    const req = https.request(
      SINA_URL,
      {
        method: "GET",
        headers: {
          Referer: "https://finance.sina.com.cn/",
          "User-Agent":
            "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36",
        },
      },
      (res) => {
        let chunks = [];
        res.on("data", (c) => chunks.push(c));
        res.on("end", () => resolve(Buffer.concat(chunks)));
      }
    );
    req.on("error", reject);
    req.setTimeout(10000, () => req.destroy(new Error("sina timeout")));
    req.end();
  });
}

/** 解析新浪 GBK 字节流中的 hq_str_* 变量；所需字段（数值/时间/代码）均为 ASCII，中文名称段忽略 */
function parseSina(buf) {
  const text = buf.toString("latin1");
  const vars = {};
  const re = /var hq_str_([A-Za-z0-9_]+)="([^"]*)"/g;
  let m;
  while ((m = re.exec(text)) !== null) {
    vars[m[1]] = m[2].split(",");
  }
  return vars;
}

function num(v) {
  const n = Number(v);
  return Number.isFinite(n) ? n : null;
}

/* ---------- 派生价格计算（公式均按原站观测值校准，详见分析报告） ---------- */
const OZ_TO_G = CFG.units.ozToGram || 31.1035; // 1 金衡盎司 = 单位换算（来自 config.units.ozToGram）

function computeAll(vars) {
  const xau = (vars.hf_XAU || []).map(num);
  const xpt = (vars.hf_XPT || []).map(num);
  const xag = (vars.hf_XAG || []).map(num);
  const xpd = (vars.hf_XPD || []).map(num);
  const autd = (vars.gds_AUTD || []).map(num);
  const agtd = (vars.gds_AGTD || []).map(num);
  const usdcny = num((vars.fx_susdcny || [])[1]) || CFG.rates.usdcnyFallback;  // 美元兑人民币（来自 config.rates.usdcnyFallback）
  const usdhkd = num((vars.fx_susdhkd || [])[1]) || CFG.rates.usdhkdFallback;  // 美元兑港元（来自 config.rates.usdhkdFallback）

  // 国际现货（美元/盎司）：[0]最新 [4]高 [5]低 [7]今开 [8]昨收 [6]时间 [12]日期
  const intl = {
    xau: mk(xau, vars.hf_XAU, "国际金价"),
    xpt: mk(xpt, vars.hf_XPT, "国际铂金"),
    xag: mk(xag, vars.hf_XAG, "国际银价"),
    xpd: mk(xpd, vars.hf_XPD, "国际钯金"),
  };
  function mk(a, raw, name) {
    if (a[0] == null) return null;
    const open = a[7] || a[8] || a[0];
    const chg = a[0] - open;
    return {
      name,
      price: a[0],
      open,
      prevClose: a[8],
      high: a[4],
      low: a[5],
      change: chg,
      changePct: open ? (chg / open) * 100 : 0,
      time: (raw && raw[6]) || "",
      date: (raw && raw[12]) || "",
    };
  }

  // 上海黄金交易所 T+D（元/克）
  const dom = {
    autd: mkTd(autd, vars.gds_AUTD, "黄金T+D"),
    agtd: mkTd(agtd, vars.gds_AGTD, "白银T+D"),
  };
  function mkTd(a, raw, name) {
    if (a[0] == null) return null;
    const open = a[7] || a[8] || a[0];
    const chg = a[0] - open;
    return {
      name,
      price: a[0],
      open,
      prevClose: a[8],
      high: a[4],
      low: a[5],
      change: chg,
      changePct: open ? (chg / open) * 100 : 0,
      time: (raw && raw[6]) || "",
      date: (raw && raw[12]) || "",
    };
  }

  const au = dom.autd ? dom.autd.price : 0; // 国内基础金价（元/克）
  const ag = dom.agtd ? dom.agtd.price / 1000 : 0; // 元/千克 → 元/克（原站 jiage5 逻辑）
  const ptCnyPerG = intl.xpt ? (intl.xpt.price / OZ_TO_G) * usdcny : 0; // 国内铂金价（元/克）
  const pdCnyPerG = intl.xpd ? (intl.xpd.price / OZ_TO_G) * usdcny : 0; // 国内钯金价（元/克）

  // 原站首页表2 派生行（setRow offset 逻辑）
  const derived = {
    guoneiGold: au,                       // 国内金价 = AUTD
    guoneiSilver: r2(ag),                 // 国内银价 = AGTD/1000
    touziJintiao: r2(au + CFG.derive.touziJintiaoOffset),            // 投资金条 = AUTD + 配置偏移
    huishouGold: r2(au + CFG.derive.huishouGoldOffset),             // 黄金回收 = AUTD + 配置偏移
    huishouPt: Math.round(ptCnyPerG * CFG.derive.huishouPtFactor),   // 铂金回收 = 国内铂金价 × 配置系数
    huishouPd: Math.round(pdCnyPerG * CFG.derive.huishouPdFactor),   // 钯金回收 = 国内钯金价 × 配置系数
    huishou18k: Math.round(au * CFG.derive.huishou18kFactor),        // 18K金回收 = 国内金价 × 配置系数
    huishouSilver: r2(ag * CFG.derive.huishouSilverFactor),          // 白银回收 = 国内银价 × 配置系数
    auSales: r2(au + CFG.derive.auSalesOffset),                  // 国内黄金销售价 = AUTD + 配置偏移
    ptCn: r2(ptCnyPerG),                  // 国内铂金价
    pdCn: r2(pdCnyPerG),                  // 国内钯金价
    ptCnHigh: r2(intl.xpt && intl.xpt.high ? (intl.xpt.high / OZ_TO_G) * usdcny : 0),
    ptCnLow: r2(intl.xpt && intl.xpt.low ? (intl.xpt.low / OZ_TO_G) * usdcny : 0),
    pdCnHigh: r2(intl.xpd && intl.xpd.high ? (intl.xpd.high / OZ_TO_G) * usdcny : 0),
    pdCnLow: r2(intl.xpd && intl.xpd.low ? (intl.xpd.low / OZ_TO_G) * usdcny : 0),
  };

  // 品牌金店（挂牌价 = 基础金价 + 品牌加价，加价幅度来自 config.brands，运维可调）
  const brands = (CFG.brands || []).map((b) => ({
    name: b.name,
    gold: Math.round(au + b.gold),
    pt: b.ptOn ? Math.round(ptCnyPerG + b.pt) : null,
    bar: Math.round(au + b.bar),
  }));

  // 香港金价（港元计价）：现货黄金折算 + 港元汇率
  const hk = intl.xau && usdhkd
    ? {
        hkdPerGram: r2((intl.xau.price / OZ_TO_G) * usdhkd),
        hkdPerTael: Math.round((intl.xau.price / OZ_TO_G) * usdhkd * (CFG.units.taelToGram || 37.429)),
        rate: usdhkd,
      }
    : null;

  return { intl, dom, derived, brands, hk, usdcny, usdhkd };
}

function r2(n) {
  return Math.round(n * 100) / 100;
}

/* ---------- 回收价 7 日历史（滚动存储，ECharts 走势图数据源） ---------- */
function todayStr(offsetDays = 0) {
  const d = new Date();
  d.setDate(d.getDate() - offsetDays);
  return d.toISOString().slice(0, 10);
}

function loadHistory() {
  try {
    return JSON.parse(fs.readFileSync(HISTORY_FILE, "utf8"));
  } catch {
    return null;
  }
}

function saveHistory(h) {
  fs.mkdirSync(DATA_DIR, { recursive: true });
  fs.writeFileSync(HISTORY_FILE, JSON.stringify(h, null, 2));
}

function updateHistory(derived) {
  let h = loadHistory();
  if (!h || !Array.isArray(h.days)) {
    // 首次运行：以当前值填充近 7 天作为种子（明确说明：非真实历史）
    h = { days: [], seeded: true };
    for (let i = 6; i >= 1; i--) {
      h.days.push({ date: todayStr(i), ...derived });
    }
  }
  const today = todayStr(0);
  const idx = h.days.findIndex((d) => d.date === today);
  const entry = { date: today, ...derived };
  if (idx >= 0) h.days[idx] = entry;
  else {
    h.days.push(entry);
    if (h.days.length > 7) h.days = h.days.slice(-7);
  }
  saveHistory(h);
  return h;
}

/* ---------- 行情获取（带缓存与降级） ---------- */
async function getQuotes() {
  const now = Date.now();
  if (quoteCache.data && now - quoteCache.at < CACHE_MS) return quoteCache;
  try {
    const buf = await fetchSina();
    const vars = parseSina(buf);
    if (!vars.hf_XAU || !vars.gds_AUTD) throw new Error("sina payload missing symbols");
    const data = computeAll(vars);
    quoteCache = { data, at: now, stale: false };
  } catch (err) {
    if (quoteCache.data) {
      quoteCache.stale = true;
    } else {
      throw err;
    }
  }
  return quoteCache;
}

/* ---------- 新浪走势图 GIF 代理（白名单路径，防开放代理） ---------- */
const CHART_RE = /^\/newchart\/v5\/(futures|global)\/(min|daily|weekly)\/([A-Za-z0-9]+)\.gif$/;

function fetchChart(pathname) {
  return new Promise((resolve, reject) => {
    const req = https.request(
      "https://image.sinajs.cn" + pathname,
      {
        method: "GET",
        headers: {
          Referer: "https://finance.sina.com.cn/",
          "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0",
        },
      },
      (res) => {
        if (res.statusCode !== 200) return reject(new Error("chart status " + res.statusCode));
        const chunks = [];
        res.on("data", (c) => chunks.push(c));
        res.on("end", () => resolve(Buffer.concat(chunks)));
      }
    );
    req.on("error", reject);
    req.setTimeout(10000, () => req.destroy(new Error("chart timeout")));
    req.end();
  });
}

/* ---------- 国际品种分时 / 日K（新浪 GlobalFuturesService JSONP） ----------
   说明：新浪图片图表接口（image.sinajs.cn/newchart）仅支持国内期货合约（AU0/AG0），
   国际现货（XAU/XPT/XPD/XAG）需改用 JSON 接口，由前端 ECharts 自绘。 */
const GLOBAL_SYMBOL_RE = /^(XAU|XPT|XPD|XAG)$/;
const G_CACHE_MS = 60000;
const minlineCache = new Map();
const dailyCache = new Map();

function httpsGetText(url) {
  return new Promise((resolve, reject) => {
    const req = https.request(
      url,
      {
        method: "GET",
        headers: {
          Referer: "https://finance.sina.com.cn/",
          "User-Agent":
            "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36",
        },
      },
      (res) => {
        const chunks = [];
        res.on("data", (c) => chunks.push(c));
        res.on("end", () => resolve(Buffer.concat(chunks).toString("utf8")));
      }
    );
    req.on("error", reject);
    req.setTimeout(12000, () => req.destroy(new Error("upstream timeout")));
    req.end();
  });
}

/** 解析新浪 JSONP：/*<script>…*\/ var t=({...}); */
function parseJsonp(text) {
  const i = text.indexOf("var t=");
  if (i < 0) return null;
  let b = text.slice(i + 6).trim().replace(/;\s*$/, "");
  if (b.startsWith("(")) b = b.slice(1);
  if (b.endsWith(")")) b = b.slice(0, -1);
  try { return JSON.parse(b); } catch { return null; }
}

async function getMinLine(symbol) {
  const hit = minlineCache.get(symbol);
  if (hit && Date.now() - hit.at < G_CACHE_MS) return hit.data;
  const url = `https://stock2.finance.sina.com.cn/futures/api/jsonp.php/var%20t=/GlobalFuturesService.getGlobalFuturesMinLine?symbol=${symbol}`;
  const j = parseJsonp(await httpsGetText(url));
  let out = { points: [], prevClose: 0, date: "" };
  if (j) {
    const key = Object.keys(j).find((k) => k.startsWith("minLine")) || "minLine_1d";
    const arr = j[key] || [];
    if (arr.length) {
      const head = arr[0];
      const prevClose = Number(head[1]) || 0;
      const points = [{ t: head[4], p: Number(head[5]), avg: Number(head[5]) }];
      for (let i = 1; i < arr.length; i++) {
        const r = arr[i];
        points.push({ t: r[0], p: Number(r[1]), avg: Number(r[4]) });
      }
      out = { points, prevClose, date: head[0] };
    }
  }
  minlineCache.set(symbol, { data: out, at: Date.now() });
  return out;
}

async function getDailyK(symbol, limit) {
  const n = Math.max(20, Math.min(Number(limit) || 120, 400));
  const hit = dailyCache.get(symbol);
  if (hit && Date.now() - hit.at < G_CACHE_MS) return hit.data.slice(-n);
  const url = `https://stock2.finance.sina.com.cn/futures/api/jsonp.php/var%20t=/GlobalFuturesService.getGlobalFuturesDailyKLine?symbol=${symbol}`;
  const j = parseJsonp(await httpsGetText(url));
  const arr = Array.isArray(j) ? j : [];
  const data = arr.map((r) => ({
    date: r.date,
    o: Number(r.open), h: Number(r.high), l: Number(r.low), c: Number(r.close),
  }));
  dailyCache.set(symbol, { data, at: Date.now() });
  return data.slice(-n);
}

/* ---------- 静态文件 ---------- */
const MIME = {
  ".html": "text/html; charset=utf-8",
  ".css": "text/css; charset=utf-8",
  ".js": "application/javascript; charset=utf-8",
  ".json": "application/json; charset=utf-8",
  ".png": "image/png",
  ".jpg": "image/jpeg",
  ".gif": "image/gif",
  ".svg": "image/svg+xml",
  ".ico": "image/x-icon",
};

function serveStatic(req, res, pathname) {
  let file = pathname === "/" ? "/index.html" : pathname;
  const abs = path.normalize(path.join(PUBLIC_DIR, file));
  if (!abs.startsWith(PUBLIC_DIR)) {
    res.writeHead(403); res.end("Forbidden"); return;
  }
  fs.readFile(abs, (err, data) => {
    if (err) {
      res.writeHead(404, { "Content-Type": "text/html; charset=utf-8" });
      res.end("<h1>404 Not Found</h1>");
      return;
    }
    res.writeHead(200, { "Content-Type": MIME[path.extname(abs)] || "application/octet-stream" });
    res.end(data);
  });
}

/* ---------- HTTP 服务 ---------- */
const server = http.createServer(async (req, res) => {
  const u = new URL(req.url, `http://localhost:${PORT}`);
  const p = u.pathname;

  try {
    if (p === "/api/quotes") {
      const cache = await getQuotes();
      const payload = {
        ok: true,
        stale: cache.stale,
        serverTime: new Date().toISOString(),
        ...cache.data,
      };
      // 顺手滚动更新今日回收历史
      try { updateHistory(cache.data.derived); } catch {}
      res.writeHead(200, { "Content-Type": "application/json; charset=utf-8", "Cache-Control": "no-store" });
      res.end(JSON.stringify(payload));
      return;
    }
    if (p === "/api/recycle-history") {
      let h = loadHistory();
      if (!h) {
        const cache = await getQuotes();
        h = updateHistory(cache.data.derived);
      }
      res.writeHead(200, { "Content-Type": "application/json; charset=utf-8" });
      res.end(JSON.stringify(h));
      return;
    }
    if (p === "/api/minline" || p === "/api/daily") {
      const sym = (u.searchParams.get("symbol") || "").toUpperCase();
      if (!GLOBAL_SYMBOL_RE.test(sym)) {
        res.writeHead(400, { "Content-Type": "application/json; charset=utf-8" });
        res.end(JSON.stringify({ ok: false, error: "unsupported symbol" }));
        return;
      }
      const body = p === "/api/minline"
        ? { ok: true, symbol: sym, ...(await getMinLine(sym)) }
        : { ok: true, symbol: sym, bars: await getDailyK(sym, u.searchParams.get("days")) };
      res.writeHead(200, { "Content-Type": "application/json; charset=utf-8", "Cache-Control": "max-age=60" });
      res.end(JSON.stringify(body));
      return;
    }
    if (p.startsWith("/api/chart/")) {
      const sub = p.replace("/api/chart", "");
      if (!CHART_RE.test(sub)) {
        res.writeHead(400); res.end("Bad chart path"); return;
      }
      const gif = await fetchChart(sub);
      res.writeHead(200, { "Content-Type": "image/gif", "Cache-Control": "max-age=60" });
      res.end(gif);
      return;
    }
    if (p === "/api/config") {
      res.writeHead(200, { "Content-Type": "application/json; charset=utf-8", "Cache-Control": "no-store" });
      res.end(JSON.stringify({
        refresh: { defaultIntervalSec: CFG.refresh.defaultIntervalSec, options: CFG.refresh.options },
        theme: { default: CFG.theme.default, allowUserToggle: CFG.theme.allowUserToggle },
        site: { name: CFG.site.name, notice: CFG.site.notice },
      }));
      return;
    }
    serveStatic(req, res, p);
  } catch (err) {
    res.writeHead(502, { "Content-Type": "application/json; charset=utf-8" });
    res.end(JSON.stringify({ ok: false, error: String(err.message || err) }));
  }
});

server.listen(PORT, "0.0.0.0", () => {
  console.log(`金价查询网站系统已启动: http://0.0.0.0:${PORT}`);
});
