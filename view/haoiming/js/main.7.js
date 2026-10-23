/* ============================================================
 * 好名 · 智能起名系统 —— 站点交互层 v2（原创实现）
 * 支持：性别三态 / 时辰 / 姓氏校验 / 五行偏好 / 卦象 /
 *       扩展评分报告 / 汉字详情 / 名字详情 / 法人八字
 * ============================================================ */
(function () {
  const $ = (s, el) => (el || document).querySelector(s);
  const $$ = (s, el) => Array.from((el || document).querySelectorAll(s));
  const TPL = window._LE ? window._LE.tpl : "";
  /* 工具页链接修正：CMS 首页/文章页上相对路径需补 tool/ 前缀（修复首页表单跳 404） */
  const IN_TOOL = /\/tool\//.test(location.pathname) || !window._LE;
  const toolUrl = n => (IN_TOOL ? "" : (window._LE.tpl + "tool/")) + n;
  /* 时辰下拉选项值为时辰序号（子=0…亥=11），引擎按 24 小时制 floor((h+1)/2) 推算时柱，
     统一换算为各时辰代表钟点（子→0、丑→2、…、亥→22），保证时柱与所选时辰一致 */
  const ZHI_LABELS = ["子时", "丑时", "寅时", "卯时", "辰时", "巳时", "午时", "未时", "申时", "酉时", "戌时", "亥时"];
  const hourParam = hv => (hv == null ? "none" : hv * 2);
  const zhiOfHour = h => ZHI_LABELS[Math.floor((h + 1) / 2) % 12];

  /* ---------- 真实使用统计（后端 stats-api.php） ---------- */
  /* 站点根路径（相对）：反代/任意 origin 下统计 API 均可同源访问 */
  function statsRoot() {
    try {
      if (window._LE && typeof window._LE.tpl === "string") {
        const m = window._LE.tpl.match(/^(\/.*?)\/view\//);
        if (m) return m[1] + "/";
      }
    } catch (e) {}
    return "/";
  }
  function statsTrack(k, n) {
    try {
      fetch(withSc(statsRoot() + "stats-api.php?action=track&k=" + encodeURIComponent(k) + "&n=" + encodeURIComponent(n || "")), { keepalive: true }).catch(() => {});
    } catch (e) {}
  }
  /* 真实统计读取（失败静默，回退演示数据） */
  function statsGet(cb) {
    try {
      fetch(withSc(statsRoot() + "stats-api.php?action=get")).then(r => r.json()).then(j => cb(j)).catch(() => cb(null));
    } catch (e) { cb(null); }
  }

  /* ---------- 收藏夹（localStorage 本地持久） ---------- */
  const FAV_KEY = "hm_favs_v1";
  function favList() {
    try { const l = JSON.parse(localStorage.getItem(FAV_KEY) || "[]"); return Array.isArray(l) ? l : []; }
    catch (e) { return []; }
  }
  function favSave(name, meta) {
    const l = favList();
    if (l.some(x => x.name === name)) return false;
    l.unshift({ name, meta: meta || "", t: Date.now() });
    try { localStorage.setItem(FAV_KEY, JSON.stringify(l.slice(0, 200))); } catch (e) {}
    return true;
  }
  function favRemove(name) {
    const l = favList().filter(x => x.name !== name);
    try { localStorage.setItem(FAV_KEY, JSON.stringify(l)); } catch (e) {}
  }
  function favClear() { try { localStorage.removeItem(FAV_KEY); } catch (e) {} }
  /* 收藏悬浮按钮 + 抽屉（全站可用） */
  function favInit() {
    if (document.getElementById("fav-fab")) return;
    const fab = document.createElement("button");
    fab.id = "fav-fab"; fab.title = "我的收藏夹"; fab.innerHTML = `♥<i id="fav-count">0</i>`;
    document.body.appendChild(fab);
    const panel = document.createElement("div");
    panel.id = "fav-panel";
    panel.innerHTML = `
      <div class="fav-head"><b>我的收藏夹</b><span class="fav-close" id="fav-close">✕</span></div>
      <div class="fav-body" id="fav-body"></div>
      <div class="fav-foot">
        <button class="fav-copy" id="fav-copyall">复制全部名字</button>
        <button class="fav-clear" id="fav-clear">清空</button>
      </div>`;
    document.body.appendChild(panel);
    fab.addEventListener("click", () => { favRender(); panel.classList.add("on"); });
    $("#fav-close").addEventListener("click", () => panel.classList.remove("on"));
    $("#fav-clear").addEventListener("click", () => { if (confirm("确定清空收藏夹？")) { favClear(); favRender(); favRefresh(); } });
    $("#fav-copyall").addEventListener("click", () => {
      const names = favList().map(x => x.name).join("、");
      if (!names) return toast("收藏夹还是空的");
      copyText("好名网收藏的名字：" + names, () => toast("已复制 " + favList().length + " 个名字"));
    });
  }
  function favRender() {
    const body = $("#fav-body");
    if (!body) return;
    const l = favList();
    if (!l.length) { body.innerHTML = '<p class="fav-empty">还没有收藏的名字。<br>在起名结果页点击名字旁的 ♥ 即可收藏。</p>'; return; }
    body.innerHTML = l.map(x => `
      <div class="fav-item">
        <div class="fav-name">${x.name}</div>
        ${x.meta ? `<div class="fav-meta">${x.meta}</div>` : ""}
        <div class="fav-ops">
          <button data-copy="${x.name}">复制</button>
          <button data-del="${x.name}">删除</button>
        </div>
      </div>`).join("");
    body.querySelectorAll("[data-copy]").forEach(b => b.addEventListener("click", () => copyText(b.dataset.copy, () => toast("已复制"))));
    body.querySelectorAll("[data-del]").forEach(b => b.addEventListener("click", () => { favRemove(b.dataset.del); favRender(); favRefresh(); }));
  }
  function favRefresh() {
    const c = $("#fav-count");
    if (c) c.textContent = String(favList().length);
    $$("[data-fav]").forEach(btn => {
      const on = favList().some(x => x.name === btn.dataset.fav);
      btn.classList.toggle("on", on);
    });
  }

  /* ---------- 轻提示 toast ---------- */
  let toastTimer = 0;
  function toast(msg) {
    let el = document.getElementById("hm-toast");
    if (!el) { el = document.createElement("div"); el.id = "hm-toast"; document.body.appendChild(el); }
    el.textContent = msg; el.classList.add("on");
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => el.classList.remove("on"), 1800);
  }
  /* 复制文本（含降级方案） */
  function copyText(text, ok) {
    const done = () => { if (ok) ok(); };
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(done).catch(() => fallbackCopy(text, done));
    } else fallbackCopy(text, done);
  }
  function fallbackCopy(text, done) {
    const ta = document.createElement("textarea");
    ta.value = text; ta.style.cssText = "position:fixed;opacity:0;";
    document.body.appendChild(ta); ta.select();
    try { document.execCommand("copy"); } catch (e) {}
    document.body.removeChild(ta); done();
  }

  /* ---------- 商业闭环：方案解锁（演示支付） ---------- */
  function isUnlocked() {
    try {
      const o = JSON.parse(sessionStorage.getItem("hm_order") || "null");
      return o && o.no ? o : null;
    } catch (e) { return null; }
  }
  const UNLOCK_PLANS = [
    { id: "basic", name: "体验版", price: "¥9.9", desc: "解锁本次全部方案 + 数理解读" },
    { id: "std", name: "标准版", price: "¥29.9", desc: "全部方案 + 五行喜忌 + 同音候选" },
    { id: "pro", name: "尊享版", price: "¥69.9", desc: "全部方案 + 专家复核建议 + 起名手册" }
  ];
  let unlockPlanSel = "std";
  function openUnlockModal() {
    if (document.getElementById("pay-modal")) return;
    const qrColor = unlockPlanSel === "pro" ? "#0a7d43" : "#1677ff";
    const modal = document.createElement("div");
    modal.id = "pay-modal";
    modal.innerHTML = `
      <div class="pay-mask"></div>
      <div class="pay-panel">
        <div class="pay-head"><b>开通完整起名方案</b><span class="pay-close" id="pay-close">✕</span></div>
        <div class="pay-plans">${UNLOCK_PLANS.map(p => `
          <label class="pay-plan${p.id === unlockPlanSel ? " on" : ""}" data-plan="${p.id}">
            <input type="radio" name="payplan" value="${p.id}" ${p.id === unlockPlanSel ? "checked" : ""} style="display:none;">
            <b>${p.name}</b><i>${p.price}</i><em>${p.desc}</em>
          </label>`).join("")}</div>
        <div class="pay-methods">
          <div class="pay-tabs"><span class="on" data-pay="alipay">支付宝</span><span data-pay="wx">微信支付</span></div>
          <div class="pay-qr">
            <div class="qr-box" id="pay-qr"></div>
            <p>请使用${'<span class="pm-name">支付宝</span>'}扫码支付<span class="pm-price" id="pay-price"></span></p>
            <p class="pay-demo">* 演示环境：点击下方按钮模拟支付成功</p>
          </div>
        </div>
        <button class="pay-confirm" id="pay-confirm">我已完成支付（模拟）</button>
      </div>`;
    document.body.appendChild(modal);
    const reRender = () => {
      modal.querySelectorAll(".pay-plan").forEach(l => l.classList.toggle("on", l.dataset.plan === unlockPlanSel));
      const p = UNLOCK_PLANS.find(x => x.id === unlockPlanSel);
      modal.querySelector("#pay-price").textContent = " " + p.price;
      /* 程序化占位二维码（非真实收款码） */
      let cells = "";
      let seed = 0;
      for (let i = 0; i < 144; i++) { seed = (seed * 9301 + 49297) % 233280; const r = seed / 233280; if (r > 0.48) cells += `<i style="left:${(i % 12) * 10}%;top:${Math.floor(i / 12) * 10}%"></i>`; }
      modal.querySelector("#pay-qr").innerHTML = cells + `<span class="qr-logo">${qrColor === "#0a7d43" ? "微" : "支"}</span>`;
    };
    reRender();
    modal.querySelectorAll(".pay-plan").forEach(l => l.addEventListener("click", () => { unlockPlanSel = l.dataset.plan; reRender(); }));
    modal.querySelectorAll(".pay-tabs span").forEach(t => t.addEventListener("click", () => {
      modal.querySelectorAll(".pay-tabs span").forEach(x => x.classList.remove("on"));
      t.classList.add("on");
      modal.querySelector(".pm-name").textContent = t.dataset.pay === "wx" ? "微信" : "支付宝";
      modal.querySelector(".qr-logo").textContent = t.dataset.pay === "wx" ? "微" : "支";
    }));
    modal.querySelector("#pay-close").addEventListener("click", () => modal.remove());
    modal.querySelector(".pay-mask").addEventListener("click", () => modal.remove());
    modal.querySelector("#pay-confirm").addEventListener("click", () => {
      const p = UNLOCK_PLANS.find(x => x.id === unlockPlanSel);
      const order = { no: "QM" + new Date().toISOString().slice(0, 10).replace(/-/g, "") + "-" + String(Math.floor(1000 + Math.random() * 9000)), plan: p.name, price: p.price, ts: Date.now() };
      try { sessionStorage.setItem("hm_order", JSON.stringify(order)); } catch (e) {}
      statsTrack("order", order.no + "|" + p.name);
      modal.remove();
      const bar = document.querySelector(".order-bar");
      if (bar) { bar.querySelector("span").innerHTML = `方案编号 <b>${order.no}</b><i class="unlocked-tag">已开通 · ${order.plan}</i>`; const ob = bar.querySelector("#unlock-open"); if (ob) ob.remove(); }
      document.querySelectorAll(".locked-card").forEach(c => c.remove());
      document.querySelectorAll(".unlock-banner").forEach(b => b.remove());
      /* 解锁：直接重载结果页（URL 不变，sessionStorage 已记录解锁态） */
      setTimeout(() => location.reload(), 400);
    });
  }

  /* ---------- 在线发布凭据传播（sharecode 自动附加） ---------- */
  let HM_SC = "";
  try {
    const m = location.search.match(/[?&]sharecode=([^&]+)/);
    if (m) { HM_SC = m[1]; sessionStorage.setItem("hm_sc", HM_SC); }
    else HM_SC = sessionStorage.getItem("hm_sc") || "";
  } catch (e) {}
  function withSc(url) {
    if (!HM_SC || !url) return url;
    if (/^(https?:)?\/\//i.test(url) && !url.includes(location.host)) return url; // 外链
    if (/^(mailto|javascript|tel):/i.test(url) || url.startsWith("#")) return url;
    if (url.includes("sharecode=")) return url;
    return url + (url.includes("?") ? "&" : "?") + "sharecode=" + HM_SC;
  }
  function patchLinks(root) {
    if (!HM_SC) return;
    (root.matches && root.matches("a[href]") ? [root] : []).concat(
      Array.from(root.querySelectorAll ? root.querySelectorAll("a[href]") : [])
    ).forEach(a => {
      const h = a.getAttribute("href");
      if (h) { const fixed = withSc(h); if (fixed !== h) a.setAttribute("href", fixed); }
    });
  }
  /* ---------- SEO：JSON-LD 结构化数据 ---------- */
  function injectJsonLd() {
    try {
      const path = location.pathname;
      const isArticle = !!document.querySelector(".article-content");
      const isTool = /\/tool\//.test(path) || IN_TOOL;
      const origin = location.origin;
      const graphs = [
        { "@type": "WebSite", "name": "好名网", "url": origin + "/",
          "potentialAction": { "@type": "SearchAction",
            "target": origin + "/view/haoiming/tool/search.html?q={search_term_string}",
            "query-input": "required name=search_term_string" } },
        { "@type": "Organization", "name": "好名网", "url": origin + "/" }
      ];
      const crumb = (name) => ({ "@type": "BreadcrumbList", "itemListElement": [
        { "@type": "ListItem", "position": 1, "name": "首页", "item": origin + "/" },
        { "@type": "ListItem", "position": 2, "name": name, "item": location.href.split("?")[0] }
      ]});
      if (isArticle) {
        const h1 = ((document.querySelector("h1") || {}).innerText || document.title).trim();
        const desc = (document.querySelector('meta[name="description"]') || {}).content || "";
        graphs.push({ "@type": "Article", "headline": h1.slice(0, 60), "description": desc.slice(0, 120) });
        graphs.push(crumb(h1.slice(0, 30) || "文章"));
      } else if (isTool) {
        const h1 = ((document.querySelector(".bh") || {}).innerText || document.title.split("_")[0]).trim();
        graphs.push({ "@type": "WebApplication", "name": h1, "applicationCategory": "UtilitiesApplication", "operatingSystem": "Web" });
        graphs.push(crumb(h1.slice(0, 20) || "工具"));
      }
      const el = document.createElement("script");
      el.type = "application/ld+json";
      el.textContent = JSON.stringify({ "@context": "https://schema.org", "@graph": graphs });
      document.head.appendChild(el);
    } catch (e) {}
  }
  /* ---------- SEO：全站内容区块「词 + 字」自动内链 ---------- */
  /* 内容容器：文章正文、工具页栏目块、专题 hero、知识区块、底部延伸阅读等 */
  const ALC_ROOTS = ".article-content, .art-intro, .rel-box, .strip-hero, .page-body, " +
    ".panel, .art-col, .tool-arts, .side-box .promo-txt, .guess-sec, .intro-txt, .cate-intro";
  /* 跳过：已有链接、表单控件、导航页脚、标题按钮、诗句原文、代码片段 */
  const ALC_SKIP = "a,script,style,button,input,textarea,select,option,form,header,footer,nav," +
    "#site-header,#site-footer,#mobile-bar,h1,.bh,.crumb,blockquote,code,pre,.btn-ghost,.tab,.chip,[data-nolink]";
  /* 关键词表：长词在前，避免被短词抢先命中；不收录「起名」「名字」等过宽泛词 */
  const AL_WORDS = [
    ["生辰八字起名", "bazi.html"], ["按生辰八字取名", "bazi.html"], ["新生儿起名", "naming.html"],
    ["双胞胎起名", "naming.html"], ["免费起名", "naming.html"], ["宝宝取名", "naming.html"],
    ["宝宝起名", "naming.html"], ["婴儿起名", "naming.html"], ["小孩起名", "naming.html"],
    ["女孩名字大全", "nvhai.html"], ["男孩名字大全", "nanhai.html"], ["女孩起名", "nvhai.html"],
    ["男孩起名", "nanhai.html"], ["女孩名字", "nvhai.html"], ["男孩名字", "nanhai.html"],
    ["古诗词取名", "poems.html"], ["诗词起名", "poems.html"], ["古诗起名", "poems.html"],
    ["诗词名句", "poems.html"], ["古文名句", "poems.html"], ["唐诗宋词", "poems.html"],
    ["楚辞起名", "chuci.html"], ["楚辞取名", "chuci.html"], ["诗经起名", "shijing.html"],
    ["诗经取名", "shijing.html"], ["唐诗起名", "tangshi.html"], ["唐诗取名", "tangshi.html"],
    ["宋词起名", "songci.html"], ["宋词取名", "songci.html"], ["周易起名", "zhouyi.html"],
    ["周易取名", "zhouyi.html"], ["周易八卦", "zhouyi.html"], ["八卦起名", "zhouyi.html"],
    ["公司起名", "gongsi.html"], ["公司取名", "gongsi.html"], ["店铺起名", "gongsi.html"],
    ["成人改名", "gaiming.html"], ["改名换名", "gaiming.html"], ["姓名打分", "score.html"],
    ["姓名测试", "score.html"], ["名字打分", "score.html"], ["免费测名", "score.html"],
    ["三才五格", "score.html"], ["五格数理", "score.html"], ["姓名配对", "match.html"],
    ["名字配对", "match.html"], ["康熙字典", "kxzd.html"], ["康熙笔画", "kxzd.html"],
    ["五行字典", "kxzd.html"], ["五行属性", "kxzd.html"], ["取名用字", "kxzd.html"],
    ["百家姓", "baijiaxing.html"], ["姓氏排名", "baijiaxing.html"], ["姓氏起源", "baijiaxing.html"]
  ];
  /* SEO 收敛：正文 contextual 链接每 1000 字 3-5 个（业界共识），全站统一按正文长度动态配额 */
  const AL_MAX_WORDS = 3, AL_MAX_CHARS = 2, AL_PER_CHARS = 200, AL_MIN = 2, AL_MAX = 6;
  const alState = { wSeen: {}, cSeen: {}, targets: {}, words: 0, chars: 0, limit: AL_MIN, self: "" };
  /* URL 归一化：保留 query（?c=X / ?name=X 是不同落地页），仅剥离 sharecode 等追踪参数 */
  function alNorm(h) {
    try {
      const u = new URL((h || "").split("#")[0], location.href);
      u.searchParams.delete("sharecode");
      return (u.pathname.replace(/\/$/, "") + (u.search || "")) || "/";
    } catch (e) { return (h || "").split("#")[0].split("?")[0] || "/"; }
  }
  /* 当前页面自身地址（用于排除自链接——自己链自己不传递任何权重） */
  function alSelf() {
    if (!alState.self) {
      try {
        const u = new URL(location.href);
        u.searchParams.delete("sharecode");
        alState.self = u.pathname.replace(/\/$/, "") + (u.search || "");
      } catch (e) { alState.self = location.pathname; }
    }
    return alState.self;
  }
  /* 目标可用性：非自链 + 该目标本页未链过（同一 URL 重复链接不增加权重传递，纯稀释） */
  function alTargetOk(href) {
    const k = alNorm(href);
    if (!k || k === "/") return false;
    if (k === alSelf()) return false;
    if (alState.targets[k]) return false;
    return k;
  }
  /* 动态配额：按当前已渲染正文长度计算，单调增长避免内容异步加载时抖动 */
  function alLimit() {
    try {
      let len = 0;
      document.querySelectorAll(".article-content, .page-body, .rel-box").forEach(e => {
        const t = e.innerText || e.textContent || "";
        len += t.replace(/\s+/g, "").length;
      });
      const n = Math.max(AL_MIN, Math.min(AL_MAX, Math.round(len / AL_PER_CHARS)));
      if (n > alState.limit) alState.limit = n;
    } catch (e) {}
    return alState.limit;
  }
  function alCharSet() {
    const set = {};
    try {
      const cs = window.HM_DATA && window.HM_DATA.CHARS;
      if (cs) cs.forEach(x => { if (x && x.c) set[x.c] = 1; });
    } catch (e) {}
    return set;
  }
  /* 收集容器内可链接的文本节点（跳过导航/链接/表单/标题等） */
  function alcTextNodes(root) {
    if (!root.querySelectorAll) return [];
    const roots = (root.matches && root.matches(ALC_ROOTS) ? [root] : [])
      .concat(Array.from(root.querySelectorAll(ALC_ROOTS)));
    const out = [];
    roots.forEach(box => {
      const walker = document.createTreeWalker(box, NodeFilter.SHOW_TEXT, {
        acceptNode(n) {
          if (!n.nodeValue || !n.nodeValue.trim()) return NodeFilter.FILTER_REJECT;
          const p = n.parentNode;
          if (!p || p.nodeType !== 1 || !p.matches) return NodeFilter.FILTER_REJECT;
          if (p.matches(ALC_SKIP) || p.closest(ALC_SKIP)) return NodeFilter.FILTER_REJECT;
          return NodeFilter.FILTER_ACCEPT;
        }
      });
      while (walker.nextNode()) out.push(walker.currentNode);
    });
    return out;
  }
  /* 安全切分：把文本节点 [start, start+len) 包成链接，返回尾部分片 */
  function alCut(node, start, len, href, cls) {
    try {
      const p = node.parentNode;
      let tail = null;
      if (node.nodeValue.length > start + len) tail = node.splitText(start + len);
      const mid = start > 0 ? node.splitText(start) : node;
      const a = document.createElement("a");
      a.href = href;
      a.className = cls;
      a.textContent = mid.nodeValue;
      p.replaceChild(a, mid);
      return tail;
    } catch (e) { return null; }
  }
  /* 单个文本节点迭代处理：词优先（语义锚文本价值更高），字补位；
     每目标全页 1 次、排除自链、总量受动态配额限制 */
  function alProcessNode(node, charSet) {
    let cur = node, guard = 0;
    while (cur && cur.nodeType === 3 && guard++ < 40) {
      const val = cur.nodeValue;
      if (!val || !val.trim()) return;
      const budget = alLimit();
      const total = alState.words + alState.chars;
      if (total >= budget) return;
      let hit = null;
      /* 1) 关键词优先：未用过 + 目标未被链 + 排除自链接 */
      if (alState.words < AL_MAX_WORDS && total < budget) {
        for (const [kw, page] of AL_WORDS) {
          if (alState.wSeen[kw]) continue;
          const idx = val.indexOf(kw);
          if (idx < 0) continue;
          const href = withSc(toolUrl(page));
          if (!alTargetOk(href)) continue; // 目标重复或自链 → 换下一个候选词
          hit = { start: idx, len: kw.length, key: kw, type: "w", href: href, k: alNorm(href) };
          break;
        }
      }
      /* 2) 书名号单字补位：仅在 word 未命中且仍有配额时 */
      if (!hit && alState.chars < AL_MAX_CHARS && total < budget) {
        const re = /[「『“"]([一-龥])[」』”"]/g;
        const cands = []; let m;
        while ((m = re.exec(val)) !== null) {
          const ch = m[1];
          if (alState.cSeen[ch]) continue;
          const href = withSc(toolUrl("char.html") + "?c=" + encodeURIComponent(ch));
          const k = alTargetOk(href);
          if (!k) continue;
          cands.push({ ch: ch, start: m.index + 1, href: href, k: k, lib: charSet[ch] ? 1 : 0 });
        }
        if (cands.length) {
          const pick = cands.filter(x => x.lib)[0] || cands[0];
          hit = { start: pick.start, len: 1, key: pick.ch, type: "c", href: pick.href, k: pick.k };
        }
      }
      if (!hit) return;
      cur = alCut(cur, hit.start, hit.len, hit.href, hit.type === "c" ? "al-char" : "al-word");
      alState.targets[hit.k] = 1;
      if (hit.type === "c") { alState.cSeen[hit.key] = 1; alState.chars++; }
      else { alState.wSeen[hit.key] = 1; alState.words++; }
    }
  }
  /* 入口：对 root（默认整页）内的内容区块做自动内链 */
  function initContentAutoLinks(root) {
    try {
      const scope = root || document.body;
      if (!scope || !scope.querySelectorAll) return;
      const charSet = alCharSet();
      alcTextNodes(scope).forEach(n => {
        if (alState.words + alState.chars >= alLimit()) return;
        if (!n.parentNode) return; // 可能已被前序处理移除
        alProcessNode(n, charSet);
      });
    } catch (e) {}
  }
  /* ---------- SEO：canonical 规范化链接 ---------- */
  /* 本站工具页大量使用带参 URL（char.html?c=X / mingzi.html?name=X / surname.html?c=X），
     不同参数组合是不同落地页，必须各自声明 canonical 并剥离 sharecode 等追踪参数，
     否则会被判为重复页面导致只收录一个、其余被折叠 */
  function injectCanonical() {
    try {
      if (document.querySelector('link[rel="canonical"]')) return;
      // 用字符串方式剔除 sharecode，避免 URLSearchParams 重序列化破坏
      // "?show--cid-1-id-1.html" 这类无等号的 PHP query（会被错误补成 "...html="）
      const raw = location.href.split("#")[0];
      let url = raw;
      const qi = url.indexOf("?");
      if (qi >= 0) {
        const base = url.slice(0, qi);
        const kept = url.slice(qi + 1).split("&").filter(kv => kv.split("=")[0] !== "sharecode");
        url = kept.length ? base + "?" + kept.join("&") : base;
      }
      const link = document.createElement("link");
      link.rel = "canonical";
      link.href = url;
      document.head.appendChild(link);
    } catch (e) {}
  }
  /* 本地绝对地址链接改写（CMS 用 weburl 拼的文章链接在反代环境下不可达） */
  function fixLocalLinks(root) {
    const localRe = /^https?:\/\/(127\.0\.0\.1|localhost)(:\d+)?\//i;
    (root.matches && root.matches("a[href]") ? [root] : []).concat(
      Array.from(root.querySelectorAll ? root.querySelectorAll("a[href]") : [])
    ).forEach(a => {
      const h = a.getAttribute("href");
      if (h && localRe.test(h)) a.setAttribute("href", h.replace(localRe, "/"));
    });
  }

  /* SEO：移除指向当前页自身的链接
     自链接不传递任何权重且浪费抓取预算；推荐列表推荐"自己"也损害体验。
     列表类整条移除，其余降级为纯文本以保留 UI 与视觉 */
  function stripSelfLinks(root) {
    try {
      let cur = location.pathname;
      const proto = (h) => {
        try {
          const u = new URL(h, location.href);
          u.searchParams.delete("sharecode");
          return u.pathname.replace(/\/$/, "") + (u.search || "");
        } catch (e) { return null; }
      };
      cur = proto(location.href) || cur;
      const scope = root && root.querySelectorAll ? root : document;
      const list = [...(scope.querySelectorAll ? scope.querySelectorAll("a[href]") : [])];
      if (scope.matches && scope.matches("a[href]")) list.unshift(scope);
      list.forEach(a => {
        const k = proto(a.getAttribute("href"));
        if (!k || k !== cur) return;
        // 「猜你喜欢」推荐到当前页自身 → 整项移除（推荐自己无意义）
        if (a.closest(".guess-links")) { a.remove(); return; }
        // 其余推荐列表：整条移除
        const li = a.closest("li");
        if (li && li.closest(".art-more-list, .side-list, .footer-inner ul")) li.remove();
        // 注：主导航/抽屉/移动底栏的当前页高亮项（a.on）属于全站导航，保留以维护 UI
      });
    } catch (e) {}
  }

  /* ---------- 公共：性别三态 chips ---------- */
  function bindGenderChips(root) {
    $$(".gender-chips", root).forEach(box => {
      box.addEventListener("click", e => {
        const chip = e.target.closest(".chip");
        if (!chip) return;
        $$(".chip", box).forEach(x => x.classList.remove("on"));
        chip.classList.add("on");
      });
    });
  }
  function genderOf(boxSel, root) {
    const on = $(".gender-chips .chip.on", $(boxSel, root));
    return on ? on.dataset.g : "1";
  }
  function hourVal(sel) { const v = $(sel) ? $(sel).value : "none"; return v === "none" ? null : parseInt(v, 10); }

  /* ---------- 姓氏校验 ---------- */
  function checkSurnameInput(input) {
    const s = (input.value || "").trim();
    const r = window.HM_ENGINE.checkSurname(s);
    if (!r.ok) { alert(r.msg); input.focus(); }
    return r.ok ? s : null;
  }

  /* ---------- 静态页头部/页脚（工具页渲染） ---------- */
  function renderToolChrome() {
    const base = TPL || "";
    const home = window._LE ? window._LE.uri : "index.html";
    const searchUrl = window._LE ? window._LE.uri + "index.php?search-index" : "search.html";
    const header = $("#site-header");
    if (header && !header.children.length) {
      const DICT = !!(window._LE && window._LE.dict);
      const toolItems = [
        ["naming.html", "宝宝起名"], ["bazi.html", "八字起名"], ["paipan.html", "八字排盘"], ["poems.html", "诗词起名"],
        ["gaiming.html", "成人改名"], ["score.html", "姓名测试"], ["gongsi.html", "公司起名"],
        ["zhouyi.html", "周易起名"], ["kxzd.html", "康熙字典"], ["baijiaxing.html", "百家姓"]
      ];
      const zoneItems = [["/chengyu/", "成语词典"], ["/zidian/", "汉语字典"], ["/cidian/", "词语词典"]];
      const navItems = DICT
        ? toolItems.map(([h, t]) => [TPL + "tool/" + h, t]).concat(zoneItems)
        : toolItems;
      const hotChars = (window.HM_DATA.CHARS || []).filter(x => x.g !== 0).slice(0, 6);
      const hotNames = ["梓晴", "宇轩", "浩然"];
      header.innerHTML = `
        <div class="wrap header-inner">
          <a class="logo" href="${home}">
            <span class="logo-mark">名</span>
            <span class="logo-text"><b>好名网</b><i>智能起名 · 内容平台</i></span>
          </a>
          <nav class="nav" style="margin-left:18px;">
            ${navItems.map(([h, t]) => `<a href="${h}">${t}</a>`).join("")}
          </nav>
          <div style="margin-left:auto;display:flex;align-items:center;gap:8px;">
            <span style="font-size:12px;color:var(--ink-2);" class="hot-quick">
              ${hotChars.map(x => `<a style="color:inherit;margin:0 3px;" href="${toolUrl("char.html")}?c=${encodeURIComponent(x.c)}" title="${x.c}字的寓意">${x.c}</a>`).join("")}
              ${hotNames.map(n => `<a style="color:inherit;margin:0 3px;" href="${toolUrl("mingzi.html")}?name=${encodeURIComponent(n)}" title="${n}名字解析">${n}</a>`).join("")}
            </span>
            <button class="nav-toggle" id="nav-toggle" title="打开导航"><span data-ic="menu"></span></button>
            <a class="header-search" href="${searchUrl}" title="站内搜索"><span data-ic="search"></span></a>
          </div>
        </div>
        <div class="nav-drawer" id="nav-drawer"><div class="wrap">
          ${navItems.map(([h, t]) => `<a href="${h}">${t}</a>`).join("")}
          <a href="${toolUrl("match.html")}">姓名配对</a><a href="${toolUrl("char.html")}">汉字详情</a><a href="${toolUrl("mingzi.html")}">名字详情</a>
        </div></div>`;
      const tg = document.getElementById("nav-toggle");
      const dr = document.getElementById("nav-drawer");
      if (tg && dr) tg.addEventListener("click", () => dr.classList.toggle("on"));
      fillIcons(header);
    }
    const footer = $("#site-footer");
    if (footer && !footer.children.length) {
      footer.innerHTML = `
        <div class="wrap footer-inner">
          <div class="footer-col">
            <h4>快捷导航</h4>
            <div class="footer-links">
              <a href="${toolUrl("naming.html")}">宝宝起名</a><a href="${toolUrl("bazi.html")}">八字起名</a><a href="${toolUrl("poems.html")}">诗词起名</a>
              <a href="${toolUrl("shijing.html")}">诗经起名</a><a href="${toolUrl("chuci.html")}">楚辞起名</a><a href="${toolUrl("tangshi.html")}">唐诗起名</a><a href="${toolUrl("songci.html")}">宋词起名</a>
              <a href="${toolUrl("zhouyi.html")}">周易起名</a><a href="${toolUrl("score.html")}">姓名测试</a><a href="${toolUrl("gongsi.html")}">公司起名</a>
              <a href="${toolUrl("kxzd.html")}">康熙字典</a><a href="${toolUrl("baijiaxing.html")}">百家姓</a><a href="${toolUrl("match.html")}">姓名配对</a>
            </div>
          </div>
          <div class="footer-col">
            <h4>词典专区</h4>
            <div class="footer-links">
              <a href="/chengyu/">成语词典</a><a href="/zidian/">汉语字典</a><a href="/cidian/">词语词典</a>
              <a href="/zidian/">按部首查字</a><a href="/zidian/">按拼音查字</a>
            </div>
          </div>
          <div class="footer-col">
            <h4>关于我们</h4>
            <p class="footer-about">好名网是原创的起名、测名打分演示平台：传统数理模型的现代实现 + 原创知识内容。命理输出仅供娱乐参考。</p>
          </div>
          <div class="footer-col">
            <h4>热门工具</h4>
            <div class="footer-links footer-art">
              <a href="${toolUrl("char.html")}?c=轩">汉字详情示例：轩</a>
              <a href="${toolUrl("mingzi.html")}?name=李明轩">名字详情示例：李明轩</a>
              <a href="${toolUrl("bazi.html")}">五行偏好起名</a>
              <a href="${toolUrl("zhouyi.html")}">按卦象起名</a>
            </div>
          </div>
        </div>
        <div class="copyright">© 好名网 · 原创功能演示系统</div>`;
    }
  }

  /* ---------- 面包屑（内页顶部） ---------- */
  function crumb(trail) {
    const home = window._LE ? window._LE.uri : "index.html";
    const items = trail.filter(Boolean);
    return `<div class="crumb"><div class="wrap"><a href="${home}">首页</a>${items.map(i =>
      i.url ? `<span>&gt;</span><a href="${i.url}">${i.name}</a>` : `<span>&gt;</span>${i.name}`).join("")}</div></div>`;
  }
  function insertCrumb(trail) {
    const anchor = document.querySelector(".ink-banner") || document.querySelector(".page-hero") || document.querySelector(".page-body") || document.querySelector(".hero");
    if (!anchor || document.querySelector(".crumb")) return;
    anchor.insertAdjacentHTML("beforebegin", crumb(trail));
  }

  /* ---------- 侧栏（内容页右栏） ---------- */
  function sideHtml(activeChar) {
    // 阅读排行榜：取本站原创文章数据（静态版）／CMS 页由模板标签提供
    const arts = [];
    const cats = window.HM_DATA && window.HM_DATA.ARTICLES ? window.HM_DATA.ARTICLES : {};
    Object.keys(cats).forEach(k => {
      (cats[k] || []).forEach(a => arts.push({ t: a.t, cat: k, u: a.u || "" }));
    });
    // 阅读排行榜：链接到真实文章页（CMS 环境由 data.7.js 的 u 字段提供；静态站降级为打分页）
    // SEO 收敛：侧栏为样板链接，条目过多会挤占正文链接的权重传递配额
    const rank = arts.slice(0, 5).map((a, i) =>
      `<li><i>${i + 1}</i><a href="${a.u || toolUrl("score.html")}">${a.t}</a></li>`).join("");
    const hotChars = (window.HM_DATA ? window.HM_DATA.CHARS.slice(0, 8) : []).map(x =>
      `<a href="${toolUrl("char.html")}?c=${encodeURIComponent(x.c)}" title="${x.m}">${x.c}</a>`).join("");
    const tools = [["naming.html", "宝宝起名"], ["score.html", "姓名打分"], ["bazi.html", "八字起名"],
      ["paipan.html", "八字排盘"], ["poems.html", "诗词起名"], ["zhouyi.html", "周易起名"], ["gongsi.html", "公司起名"]]
      .map(([h, t]) => `<a href="${h}">${t}</a>`).join("");
    const promo = `<div class="side-box side-promo">
      <div class="promo-art" aria-hidden="true">
        <svg viewBox="0 0 120 96" width="100%" height="72">
          <circle cx="60" cy="48" r="40" fill="none" stroke="#e6bdb2" stroke-width="1.5"/>
          <circle cx="60" cy="48" r="27" fill="none" stroke="#e6bdb2" stroke-width="1"/>
          <path d="M60 8v80M20 48h80M32 20l56 56M88 20L32 76" stroke="#f0d0c6" stroke-width="1"/>
          <path d="M60 21l9 19 21 3-15 15 3 21-18-9-18 9 3-21-15-15 21-3z" fill="#c92009" opacity="0.82"/>
          <circle cx="60" cy="48" r="7" fill="#fff"/>
        </svg>
      </div>
      <h4>宝宝起名定制</h4>
      <p class="promo-txt">输入姓氏与生辰，按五行喜用生成候选名，并给出数理评分与出处。</p>
      <a class="btn-ghost" href="${toolUrl("naming.html")}">立即起名</a>
    </div>`;
    return `<aside class="col-side">
      <div class="side-box"><h4>阅读排行榜</h4><ul class="side-list">${rank || "<li>暂无内容</li>"}</ul></div>
      ${promo}
      <div class="side-box"><h4>热门起名用字</h4><div class="side-chars">${hotChars}</div></div>
      <div class="side-box"><h4>快捷工具</h4><div class="side-chars" style="justify-content:flex-start;">${tools}</div></div>
    </aside>`;
  }

  /* ---------- 内页统一布局：主内容 + 侧栏 ---------- */
  function withLayout(inner, activeChar) {
    return `<div class="layout"><div class="col-main">${inner}</div>${sideHtml(activeChar)}</div>`;
  }

  /* ---------- 首页 ---------- */
  function homePage() {
    const tabs = $$("#home-tabs .tab");
    const panels = $$("#home-panels .tab-panel");
    tabs.forEach((t, i) => t.addEventListener("click", () => {
      tabs.forEach(x => x.classList.remove("on")); t.classList.add("on");
      panels.forEach(x => x.classList.remove("on")); panels[i].classList.add("on");
    }));
    // 宝宝起名（首页）：六种起名模式
    const MODE_META = {
      bazi: { btn: "立即宝宝起名", tip: "按生辰八字五行喜用神生成候选名字" },
      poem: { btn: "立即诗词起名", tip: "从古诗词名句中提取意境，生成文雅名字" },
      zhouyi: { btn: "立即周易起名", tip: "依周易卦象取意，配合字库生成候选名字" },
      idiom: { btn: "立即成语起名", tip: "从 338 条精选成语取字成名，每个名字标注成语出处" },
      nickname: { btn: "立即生成小名", tip: "叠字、节气风物与生肖意象乳名，亲昵顺口" },
      twins: { btn: "立即配对双胞胎名", tip: "成语对半、同源词组、对仗字对与同部首四种配对话术" }
    };
    const modeChips = $$("#qmh-mode .chip");
    let qmhMode = "bazi";
    modeChips.forEach(ch => ch.addEventListener("click", () => {
      modeChips.forEach(x => x.classList.remove("on")); ch.classList.add("on");
      qmhMode = ch.dataset.mode;
      const meta = MODE_META[qmhMode] || MODE_META.bazi;
      const btn = $("#qmh-submit"); if (btn) btn.textContent = meta.btn;
      const tip = $("#qmh-mode-tip"); if (tip) tip.textContent = meta.tip;
      /* 双胞胎模式：性别 chips 语义切换为 男双/女双/龙凤胎 */
      const gchips = $$("#form-qmh .gender-chips .chip");
      if (qmhMode === "twins") {
        gchips[0].textContent = "男双"; gchips[1].textContent = "女双"; gchips[2].textContent = "龙凤胎";
      } else {
        gchips[0].textContent = "男"; gchips[1].textContent = "女"; gchips[2].textContent = "未知";
      }
    }));
    const bf = $("#form-qmh");
    if (bf) bf.addEventListener("submit", e => {
      e.preventDefault();
      const s = checkSurnameInput($("#xingh")); if (!s) return;
      const g = genderOf("#form-qmh");
      const dt = $("#dateh").value;
      if (!dt) return alert("请选择出生日期");
      const h = hourVal("#hourh");
      const place = $("#placeh").value.trim() || "未填写";
      location.href = withSc(`${toolUrl("naming.html")}?surname=${encodeURIComponent(s)}&gender=${g}&date=${dt}&hour=${hourParam(h)}&place=${encodeURIComponent(place)}&mode=${qmhMode}`);
    });
    const tf = $("#form-test");
    if (tf) tf.addEventListener("submit", e => {
      e.preventDefault();
      const n = $("#tt-name").value.trim();
      if (!n) return alert("请输入完整姓名");
      const g = genderOf("#form-test");
      location.href = withSc(`${toolUrl("score.html")}?name=${encodeURIComponent(n)}&gender=${g}`);
    });
    const sf = $("#form-score");
    if (sf) sf.addEventListener("submit", e => {
      e.preventDefault();
      const n = $("#sc-name2").value.trim();
      if (!n) return alert("请输入完整姓名");
      location.href = withSc(`${toolUrl("score.html")}?name=${encodeURIComponent(n)}`);
    });
    // 排行（男/女/热门）
    function renderRank(id, gender) {
      const el = document.getElementById(id);
      if (!el) return;
      let pool = window.HM_DATA.CHARS.filter(x => gender === 0 ? true : (x.g === gender || x.g === 0));
      pool.sort((a, b) => (b.k * 7 + b.c.charCodeAt(0)) % 97 - (a.k * 7 + a.c.charCodeAt(0)) % 97);
      el.innerHTML = pool.slice(0, 9).map((x, i) => `
        <a class="rank-item" href="${toolUrl("char.html")}?c=${encodeURIComponent(x.c)}">
          <span class="rank-no r${i < 3 ? i + 1 : "n"}">${i + 1}</span>
          <span class="rank-char">${x.c}</span>
          <span class="rank-sub">${x.w} · ${x.k}画</span>
        </a>`).join("");
    }
    renderRank("rank-boy", 1);
    renderRank("rank-girl", 2);
    renderRank("rank-hot", 0);
    /* 别人正在查的姓名（滚动流） */
    const liveBox = document.getElementById("live-names");
    if (liveBox) {
      const surnames = window.HM_DATA.SURNAMES.slice(0, 40);
      const gChars = window.HM_DATA.CHARS.filter(x => x.g === 1).slice(0, 30);
      const gChars2 = window.HM_DATA.CHARS.filter(x => x.g === 2).slice(0, 30);
      const items = [];
      const rnd = (n) => Math.floor(Math.random() * n);
      for (let i = 0; i < 16; i++) {
        const sn = surnames[rnd(surnames.length)].c;
        const male = i % 2 === 0;
        const pool = male ? gChars : gChars2;
        const n1 = pool[rnd(pool.length)].c, n2 = pool[rnd(pool.length)].c;
        const nm = rnd(4) === 0 ? n1 : n1 + (n2 !== n1 ? n2 : pool[(pool.indexOf(n1) + 3) % pool.length].c);
        items.push(`<a href="${toolUrl("mingzi.html")}?name=${encodeURIComponent(sn + nm)}"><b>${sn}姓</b> 正在为${male ? "男宝" : "女宝"}起名「${nm}」</a>`);
      }
      liveBox.innerHTML = items.join("");
    }
    /* 真实使用统计：累计条 + 真实最近查询覆盖演示流 */
    statsGet(j => {
      if (!j || !j.totals) return;
      const strip = document.getElementById("home-stats");
      if (strip) {
        const t = j.totals, sum = (t.naming || 0) + (t.score || 0) + (t.match || 0);
        if (sum > 0) strip.innerHTML = `
          <div><b>${sum}</b><span>累计服务查询</span></div>
          <div><b>${t.naming || 0}</b><span>起名方案生成</span></div>
          <div><b>${t.score || 0}</b><span>姓名评测</span></div>
          <div><b>${t.order || 0}</b><span>已开通方案</span></div>`;
      }
      if (j.recent && j.recent.length && liveBox) {
        const esc = x => String(x).replace(/[<>&"]/g, ch => ({ "<": "&lt;", ">": "&gt;", "&": "&amp;", '"': "&quot;" }[ch]));
        const label = { naming: "正在为宝宝起名", score: "正在测试姓名", match: "正在测算配对", search: "正在搜索", order: "已开通起名方案" };
        liveBox.innerHTML = j.recent.slice(0, 16).map(r => `<a><b>${esc(r.n)}</b> ${label[r.k] || "查询"}</a>`).join("");
      }
    });
    const hot = document.getElementById("rank-hot");
    if (hot) { // 热门名字：两字组合示例
      const pool = window.HM_DATA.CHARS.filter(x => x.g !== 0).slice(0, 40);
      const names = [];
      for (let i = 0; i + 1 < pool.length && names.length < 9; i += 4) {
        names.push([pool[i].c, pool[i + 3] ? pool[i + 3].c : pool[i + 1].c]);
      }
      hot.innerHTML = names.slice(0, 9).map((p, i) => `
        <a class="rank-item" href="${toolUrl("mingzi.html")}?name=${encodeURIComponent("李" + p[0] + p[1])}">
          <span class="rank-no r${i < 3 ? i + 1 : "n"}">${i + 1}</span>
          <span class="rank-char">${p[0]}${p[1]}</span>
          <span class="rank-sub">热门组合</span>
        </a>`).join("");
    }
    const wx = $("#wuxing-grid");
    if (wx) {
      wx.innerHTML = window.HM_DATA.WUXING.map(w => {
        const n = window.HM_DATA.CHARS.filter(x => x.w === w).length;
        return `<a class="wx-card wx-${w}" href="${toolUrl("kxzd.html")}?wx=${w}"><b>${w}</b><span>${n} 字</span><i>${window.HM_DATA.WUXING_DESC[w].slice(0, 14)}…</i></a>`;
      }).join("");
    }
  }
  window._HM_HOME = homePage;

  /* ---------- 起名结果页 ---------- */
  function initNaming() {
    const box = $("#naming-result");
    if (!box) return;
    const q = new URLSearchParams(location.search);
    const surname = q.get("surname") || "李";
    const gender = parseInt(q.get("gender") ?? "1", 10);
    const date = q.get("date") || "2026-01-01";
    const hourRaw = q.get("hour");
    const hour = hourRaw && hourRaw !== "none" ? parseInt(hourRaw, 10) : new Date().getHours();
    const place = q.get("place") || "未填写";
    const [y, m, d] = date.split("-").map(Number);
    const birth = { y, m, d, h: hour, place: place !== "未填写" ? place : "" };
    const mode = q.get("mode") || "bazi";
    let data;
    if (mode === "poem") data = { names: window.HM_ENGINE.generateByPoem(surname, gender, q.get("cat") || "all", 12) };
    else if (mode === "zhouyi") data = { names: window.HM_ENGINE.generateByZhouyi(surname, gender, 12, q.get("gua") || "none") };
    else if (mode === "idiom") data = window.HM_ENGINE.genIdiom(surname, gender, birth, 12, { avoid: q.get("avoid") || "" });
    else if (mode === "nickname") data = window.HM_ENGINE.genNickname(surname, gender, birth, 12);
    else if (mode === "twins") {
      /* gender: 1=男双 2=女双 0=龙凤胎 */
      const g1 = gender === 2 ? 2 : 1, g2 = gender === 2 ? 2 : (gender === 0 ? 2 : 1);
      data = window.HM_ENGINE.genTwins(surname, g1, g2, birth, 10);
      data.twins = true;
    }
    else data = window.HM_ENGINE.generateByBazi(surname, gender, birth, 12, { wx1: q.get("wx1"), wx2: q.get("wx2"), avoid: q.get("avoid") || "", fixed: q.get("fixed") || "", wish: q.get("wish") || "" });

    const modeName = mode === "poem" ? "诗词" : mode === "zhouyi" ? "周易" : mode === "idiom" ? "成语" : mode === "nickname" ? "小名" : mode === "twins" ? "双胞胎" : "八字";
    const MODE_NOTE = {
      idiom: "成语起名：每个名字均取自褒义成语，附寓意与典籍出处，可直接溯源。",
      nickname: "小名/乳名：叠字亲昵、节气风物与生肖意象三类，供家人日常呼唤。",
      twins: "双胞胎配对名：成语对半、同源词组、对仗字对、同部首四种策略，两名意脉相连。"
    };
    $("#nr-title").textContent = data.twins ? `${surname}姓双胞胎 · 配对起名结果` : `${surname}姓宝宝 · ${modeName}起名结果`;
    $("#nr-meta").textContent = `出生：${date}${hourRaw && hourRaw !== "none" ? " · 时辰已计入" : ""} · 地区：${place} · 生肖：${data.bazi ? data.bazi.zodiac : "—"} · 共 ${data.names.length} 个推荐`;
    document.title = `${surname}姓${modeName}${data.twins ? "配对" : ""}起名结果 - 好名网`;
    const _ng = data.names[0];
    statsTrack("naming", surname + (_ng ? (data.twins ? _ng.name1 : _ng.name.replace(surname, "")) : ""));

    if (data.bazi) {
      const b = data.bazi;
      $("#nr-bazi").innerHTML = `
        <div class="bazi-pills">${b.gz.map((g, i) => `<span class="pill"><i>${["年柱", "月柱", "日柱", "时柱"][i]}</i><b>${g}</b></span>`).join("")}</div>
        <div class="bazi-wx">五行分布：${window.HM_DATA.WUXING.map(w => `${w} ${b.wxCount[w]}`).join(" · ")}
        ${b.missing.length ? `<em>缺 ${b.missing.join("、")}</em>` : "<em>五行不缺</em>"}
        · 喜用神：<b class="favor">${b.favorable}</b></div>
        ${b.solarNote ? `<p class="bazi-note">【真太阳时】${b.solarNote}，时柱已按当地太阳时核定。</p>` : ""}
        ${b.cangGan ? `<p class="bazi-note">【地支藏干】${b.cangGan.map(c => `${c.gz}藏「${c.cq}」`).join(" · ")}（五行统计已按藏干加权）。</p>` : ""}
        ${b.dayMaster ? `<p class="bazi-note" style="color:var(--red-deep);">【日主分析】${b.dayMaster.desc}，取名以<b>${b.favorable}</b>为喜用神${b.tabooWx ? `，<b>忌${b.tabooWx}</b>（已自动避开）` : ""}。</p>` : ""}
        ${mode === "bazi" ? `<p class="bazi-note">【五格笔画推荐】按传统五格剖象，${surname}姓宜搭配 <b>${(() => { const pairs = window.HM_ENGINE.bestStrokePairs(surname); return pairs.slice(0, 4).map(p2 => `${p2.n1}+${p2.n2} 画`).join("、"); })()}</b> 的名字笔画组合（三才配置全吉）。</p>` : ""}
        ${MODE_NOTE[mode] ? `<p class="bazi-note" style="color:var(--red-deep);">【模式说明】${MODE_NOTE[mode]}</p>` : ""}
        <p class="bazi-note">* 命理模型为简化演示算法，仅供娱乐参考。</p>`;
    }
    /* 付费闭环：未解锁时前 3 个免费完整展示，其余锁定模糊 */
    const unlocked = isUnlocked();
    const orderNo = unlocked ? unlocked.no : "QM" + new Date().toISOString().slice(0, 10).replace(/-/g, "") + "-" + String(Math.floor(1000 + Math.random() * 9000));
    const orderBar = `
      <div class="order-bar">
        <span>方案编号 <b>${orderNo}</b>${unlocked ? `<i class="unlocked-tag">已开通 · ${unlocked.plan}</i>` : '<i class="free-tag">免费预览前 3 个</i>'}</span>
        ${unlocked ? "" : `<button class="unlock-btn" id="unlock-open">解锁全部 ${data.names.length} 个方案</button>`}
      </div>`;
    const favOf = (n) => favList().some(x => x.name === n);
    const favBtn = (n, meta) => `<button class="fav-btn${favOf(n) ? " on" : ""}" data-fav="${n}" data-meta="${encodeURIComponent(meta)}" title="收藏此名">♥</button>`;
    const cardMeta = (n) => data.twins ? (n.name1 + " / " + n.name2) : n.name;
    const shareBar = `<div class="share-row"><button class="share-btn" id="share-plan">📋 复制方案分享文案</button><span class="share-hint">复制后可发给家人一起挑</span></div>`;
    box.innerHTML = orderBar + (data.twins ? data.names.map((n, i) => {
      const locked = !unlocked && i >= 3;
      if (locked) {
        return `
      <div class="name-card twin-card locked-card" data-idx="${i}">
        <div class="name-main">
          <span class="name-rank">${i + 1}</span>
          <span class="name-text locked-text">${n.name1}<span class="blur-name"></span></span>
          <span class="twin-plus">＋</span>
          <span class="name-text locked-text">${n.name2}<span class="blur-name"></span></span>
          <span class="name-score s${n.score >= 90 ? "hi" : n.score >= 75 ? "mid" : "low"} blur-score">${n.score}分</span>
        </div>
        <div class="name-sub lock-hint">🔒 开通方案后查看配对解读与取名策略</div>
        <button class="unlock-mini" data-unlock>立即解锁</button>
      </div>`;
      }
      return `
      <div class="name-card twin-card">
        <div class="name-main">
          <span class="name-rank">${i + 1}</span>
          <a class="name-text" href="${toolUrl("mingzi.html")}?name=${encodeURIComponent(n.name1)}">${n.name1}</a>
          <span class="twin-plus">＋</span>
          <a class="name-text" href="${toolUrl("mingzi.html")}?name=${encodeURIComponent(n.name2)}">${n.name2}</a>
          ${favBtn(n.name1 + " / " + n.name2, n.reason)}
          <span class="name-score s${n.score >= 90 ? "hi" : n.score >= 75 ? "mid" : "low"}" title="配对综合分">${n.score}分</span>
        </div>
        <div class="name-sub">${n.reason} · 五格 ${n.wg1}/${n.wg2}</div>
      </div>`;
    }).join("") : data.names.map((n, i) => {
      const locked = !unlocked && i >= 3;
      if (locked) {
        return `
      <div class="name-card locked-card" data-idx="${i}">
        <div class="name-main">
          <span class="name-rank">${i + 1}</span>
          <span class="name-text locked-text">${n.name.charAt(0)}<span class="blur-name">${n.name.slice(1)}</span></span>
          <span class="name-score s${n.score >= 90 ? "hi" : n.score >= 75 ? "mid" : "low"} blur-score">${n.score}分</span>
        </div>
        <div class="name-sub lock-hint">🔒 开通方案后查看完整解读、五行分析与同音候选</div>
        <button class="unlock-mini" data-unlock>立即解锁</button>
      </div>`;
      }
      return `
      <div class="name-card">
        <div class="name-main">
          <span class="name-rank">${i + 1}</span>
          <a class="name-text" href="${toolUrl("mingzi.html")}?name=${encodeURIComponent(n.name)}">${n.name}</a>
          <a class="name-score s${n.score >= 90 ? "hi" : n.score >= 75 ? "mid" : "low"}" href="${toolUrl("score.html")}?name=${encodeURIComponent(n.name)}">${n.score}分</a>
          ${favBtn(n.name, (n.reason || "").replace(/"/g, "'"))}
        </div>
        <div class="name-sub">${n.reason}${n.wx ? ` · 五行：${n.wx}` : ""}${n.line ? `<blockquote>「${n.line}」— ${n.src}</blockquote>` : ""}</div>
      </div>`;
    }).join("")) + shareBar + (unlocked ? "" : `
      <div class="unlock-banner">
        <b>已展示 ${Math.min(3, data.names.length)} / ${data.names.length} 个方案</b>
        <p>开通后解锁全部名字的数理解读、五行喜忌、同音候选与收藏清单，一次付费永久有效。</p>
        <button class="unlock-btn big" data-unlock>立即解锁完整方案</button>
      </div>`);
    const openBtn = document.getElementById("unlock-open");
    if (openBtn) openBtn.addEventListener("click", openUnlockModal);
    box.querySelectorAll("[data-unlock]").forEach(b => b.addEventListener("click", openUnlockModal));

    /* 收藏按钮 */
    box.querySelectorAll("[data-fav]").forEach(btn => btn.addEventListener("click", () => {
      const name = btn.dataset.fav;
      const meta = decodeURIComponent(btn.dataset.meta || "");
      if (favOf(name)) { favRemove(name); btn.classList.remove("on"); toast("已取消收藏"); }
      else { favSave(name, meta); btn.classList.add("on"); toast("已收藏 ♥"); }
      favRefresh();
    }));
    /* 分享文案 */
    const shareBtn = document.getElementById("share-plan");
    if (shareBtn) shareBtn.addEventListener("click", () => {
      const b = data.bazi;
      const lines = [`【好名网 · ${modeName}${data.twins ? "配对" : ""}起名方案】`,
        `姓氏：${surname} · 出生：${date}${hourRaw && hourRaw !== "none" ? " " + zhiOfHour(parseInt(hourRaw, 10)) : ""} · 生肖：${b ? b.zodiac : "—"}`];
      if (b) lines.push(`八字：${b.gz.join(" ")} · 喜用神：${b.favorable}`);
      const top = data.names.slice(0, unlocked ? 6 : 3);
      top.forEach((n, i) => {
        lines.push(`${i + 1}. ${data.twins ? n.name1 + " ＋ " + n.name2 : n.name}（${n.score}分）${n.reason ? "——" + n.reason.replace(/<[^>]+>/g, "") : ""}`);
      });
      lines.push("—— 由好名网智能起名生成，命理模型为简化演示算法，仅供娱乐参考");
      copyText(lines.join("\n"), () => toast("方案文案已复制，快去分享给家人"));
    });
    [["switch-poem", "poem"], ["switch-zhouyi", "zhouyi"], ["switch-bazi", "bazi"], ["switch-idiom", "idiom"], ["switch-nickname", "nickname"], ["switch-twins", "twins"]].forEach(([id, m]) => {
      const el = document.getElementById(id);
      if (!el) return;
      const p = new URLSearchParams(location.search);
      p.set("mode", m);
      el.href = `${toolUrl("naming.html")}?${p.toString()}`;
      if (mode === m) el.style.display = "none";
    });
  }

  /* ---------- 独立八字排盘页（四柱 / 藏干 / 十神 / 旺衰 / 喜用神） ---------- */
  function initPaipan() {
    const form = $("#form-pp"), box = $("#pp-result");
    if (!form || !box) return;
    const STEMS = "甲乙丙丁戊己庚辛壬癸", BRANCHES = "子丑寅卯辰巳午未申酉戌亥";
    const STEM_WX = { 甲:"木",乙:"木",丙:"火",丁:"火",戊:"土",己:"土",庚:"金",辛:"金",壬:"水",癸:"水" };
    const BRANCH_WX = { 子:"水",丑:"土",寅:"木",卯:"木",辰:"土",巳:"火",午:"火",未:"土",申:"金",酉:"金",戌:"土",亥:"水" };
    const KONG = ["戌亥", "申酉", "午未", "辰巳", "寅卯", "子丑"];   // 六甲旬空
    const WX_COLOR = { "金": "#b98d2f", "木": "#3f7d4e", "水": "#3f6f9f", "火": "#b5372a", "土": "#8a6b3d" };
    const SHENG = { "木": "火", "火": "土", "土": "金", "金": "水", "水": "木" };      // 我生（食伤）
    const KE = { "木": "土", "土": "水", "水": "火", "火": "金", "金": "木" };        // 我克（财）
    const BEI_KE = { "木": "金", "金": "火", "火": "水", "水": "土", "土": "木" };    // 克我（官杀）
    const SHENG_ME = { "木": "水", "水": "金", "金": "土", "土": "火", "火": "木" };  // 生我（印）
    /* 十神：以日主为「我」，按生克与阴阳同异取算 */
    const tenGod = (dm, o) => {
      if (o === dm) return "比肩";
      const dmw = STEM_WX[dm], ow = STEM_WX[o];
      const sameYY = (STEMS.indexOf(o) % 2) === (STEMS.indexOf(dm) % 2);
      if (ow === dmw) return sameYY ? "比肩" : "劫财";
      if (SHENG[dmw] === ow) return sameYY ? "食神" : "伤官";
      if (KE[dmw] === ow) return sameYY ? "偏财" : "正财";
      if (BEI_KE[dmw] === ow) return sameYY ? "七杀" : "正官";
      return sameYY ? "偏印" : "正印";
    };
    const esc = s => String(s).replace(/[&<>"]/g, c => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" }[c]));

    form.addEventListener("submit", e => {
      e.preventDefault();
      const dt = $("#pp-date").value;
      if (!dt) return alert("请选择出生日期");
      const hi = $("#pp-hour").value;
      const place = ($("#pp-place").value || "").trim();
      const [y, m, d] = dt.split("-").map(Number);
      const h = hi === "none" ? 12 : parseInt(hi, 10) * 2;   /* 时辰序号 → 代表钟点 */
      const b = window.HM_ENGINE.bazi({ y, m, d, h, place });
      render(b, { dt, hi, place });
      box.scrollIntoView({ behavior: "smooth", block: "start" });
    });

    function render(b, ctx) {
      /* 旬空：由日柱在六十甲子中的位次定旬 */
      let dayIdx = -1;
      for (let n = 0; n < 60; n++) if (n % 10 === b.pillars[2].stem && n % 12 === b.pillars[2].branch) { dayIdx = n; break; }
      const kong = KONG[Math.floor(dayIdx / 10)];
      const xunShou = "甲" + BRANCHES[(dayIdx - dayIdx % 10) % 12];
      const hiLabel = ctx.hi === "none" ? "时辰未填（时柱按午时默认）" : ZHI_LABELS[parseInt(ctx.hi, 10)];
      const maxW = Math.max.apply(null, window.HM_DATA.WUXING.map(w => b.wxWeight[w]));
      const dm = b.dayStemName;
      const samePct = Math.round(b.dayMaster.sameParty / (b.dayMaster.sameParty + b.dayMaster.rivalParty) * 100);
      const pool = (window.HM_DATA.CHARS || []).filter(x => x.w === b.favorable)
        .sort((a, c) => (a.g === 0 ? 1 : 0) - (c.g === 0 ? 1 : 0) || c.k - a.k).slice(0, 12);
      box.innerHTML = `
      <div class="ppz-wrap">
        <div class="rel-box ppz-hero">
          <div class="ppz-hero-main">
            <div class="ppz-hero-label">生 辰 八 字</div>
            <div class="ppz-hero-gz">${b.gz.join("　")}</div>
            <div class="ppz-hero-meta">${esc(ctx.dt)} · ${hiLabel} · 生肖属${b.zodiac} · 日主 ${dm}${b.dayStemWx} · ${b.dayMaster.tag}</div>
            ${b.solarNote ? `<div class="ppz-hero-solar">☀ ${esc(b.solarNote)}</div>` : ""}
          </div>
          <div class="ppz-hero-fav">
            <div class="ppz-hero-label">喜 用 神</div>
            <div class="ppz-fav-big" style="color:${WX_COLOR[b.favorable]}">${b.favorable}</div>
            <div class="ppz-taboo">${b.tabooWx ? "忌 " + b.tabooWx : "无忌"}</div>
          </div>
        </div>

        <div class="rel-box ppz-box">
          <h3>四柱八字<span class="ppz-sub">年 · 月 · 日 · 时，日柱为命主本人</span></h3>
          <div class="ppz-scroll"><table class="ppz-table">
            <thead><tr><th></th><th>年柱</th><th>月柱</th><th>日柱</th><th>时柱</th></tr></thead>
            <tbody>
              <tr><td class="ppz-rl">干支</td>${b.gz.map((g, i) => `<td class="ppz-gz${i === 2 ? " is-day" : ""}">${g}</td>`).join("")}</tr>
              <tr><td class="ppz-rl">天干</td>${b.pillars.map(p => { const s = STEMS[p.stem]; return `<td>${s}<i style="color:${WX_COLOR[STEM_WX[s]]}">${STEM_WX[s]}</i></td>`; }).join("")}</tr>
              <tr><td class="ppz-rl">地支</td>${b.pillars.map(p => { const z = BRANCHES[p.branch]; return `<td>${z}<i style="color:${WX_COLOR[BRANCH_WX[z]]}">${BRANCH_WX[z]}</i></td>`; }).join("")}</tr>
              <tr><td class="ppz-rl">藏干</td>${b.cangGan.map(c => `<td class="ppz-cq">${c.cq.split("").join(" ")}</td>`).join("")}</tr>
              <tr><td class="ppz-rl">十神</td>${b.pillars.map(p => `<td class="ppz-ss">${tenGod(dm, STEMS[p.stem])}</td>`).join("")}</tr>
            </tbody>
          </table></div>
          <p class="ppz-tip">十神以日主「${dm}」为我推算：比劫为同党，印星生我，食伤我生，财我克，官杀克我。月柱地支当权，藏干按本气 0.7 / 中气 0.3 / 余气 0.1 加权，月令再乘 1.8。</p>
        </div>

        <div class="rel-box ppz-box">
          <h3>五行旺衰<span class="ppz-sub">藏干加权 + 月令当权</span></h3>
          <div class="ppz-bars">
            ${window.HM_DATA.WUXING.map(w => {
              const v = b.wxWeight[w], pct = Math.max(3, Math.round(v / maxW * 100));
              return `<div class="ppz-bar-row">
                <span class="ppz-bar-l" style="color:${WX_COLOR[w]}">${w}</span>
                <div class="ppz-bar-track"><div class="ppz-bar-fill" style="width:${pct}%;background:${WX_COLOR[w]}"></div></div>
                <span class="ppz-bar-v">${v.toFixed(1)}</span></div>`;
            }).join("")}
          </div>
          <p class="ppz-wx-count">天干地支直观计数：${window.HM_DATA.WUXING.map(w => `${w} ${b.wxCount[w]}`).join(" · ")}；${b.missing.length ? "八字缺 " + b.missing.join("、") : "五行俱全"}。旺衰值与喜用神推算以左图加权分为准。</p>
        </div>

        <div class="rel-box ppz-box">
          <h3>日主强弱<span class="ppz-sub">扶抑理论的起点</span></h3>
          <div class="ppz-dm-bar">
            <div class="ppz-dm-same" style="width:${samePct}%">同党 ${b.dayMaster.sameParty}</div>
            <div class="ppz-dm-rival" style="width:${100 - samePct}%">异党 ${b.dayMaster.rivalParty}</div>
          </div>
          <p class="ppz-dm-desc">${esc(b.dayMaster.desc)}。日主${b.dayMaster.tag}时，${b.dayMaster.tag === "偏强" ? "宜克泄耗" : b.dayMaster.tag === "偏弱" ? "宜生扶" : "取命局最少之五行调和"}，故以 <b style="color:${WX_COLOR[b.favorable]}">${b.favorable}</b> 为喜用神${b.tabooWx ? `，忌 <b>${b.tabooWx}</b>（起名引擎已自动避开）` : ""}。</p>
          <div class="ppz-tags">
            <span class="ppz-tag${b.deling ? " good" : ""}">${b.deling ? "得令" : "不得令"}</span>
            ${b.tiaohou ? `<span class="ppz-tag warn">${esc(b.tiaohou)}</span>` : ""}
            <span class="ppz-tag">${xunShou}旬 · 旬空 ${kong}</span>
            <span class="ppz-tag">${b.missing.length ? "缺 " + b.missing.join("、") : "五行不缺"}</span>
          </div>
        </div>

        <div class="rel-box ppz-box">
          <h3>取名用字建议<span class="ppz-sub">喜用神属 ${b.favorable} 的宜用字</span></h3>
          <div class="ppz-chars">
            ${pool.map(c => `<a class="ppz-char" href="${toolUrl("char.html")}?c=${encodeURIComponent(c.c)}" title="${esc(c.m)}">
              <b>${c.c}</b><span>${esc(c.p)}</span><i>${c.w} · ${c.k}画</i></a>`).join("") || '<p class="empty">字库中暂无该五行用字。</p>'}
          </div>
          <p class="ppz-tip">以上按喜用神「${b.favorable}」从康熙字典五行字库筛选，点击单字可查看《说文解字》释义、笔画数与起名宜忌；配合姓氏生成完整方案请使用下方入口。</p>
        </div>

        <div class="ppz-actions">
          <a class="btn-primary" href="${toolUrl("bazi.html")}?date=${encodeURIComponent(ctx.dt)}&hour=${hourParam(ctx.hi)}&place=${encodeURIComponent(ctx.place || "")}">按此八字起名 →</a>
          <a class="btn-ghost" href="${toolUrl("naming.html")}?surname=${encodeURIComponent("李")}&gender=1&date=${encodeURIComponent(ctx.dt)}&hour=${hourParam(ctx.hi)}&place=${encodeURIComponent(ctx.place || "")}&mode=bazi">直接看名字方案</a>
          <button class="btn-ghost" id="ppz-copy" type="button">复制八字摘要</button>
        </div>
        <p class="ppz-disclaimer">排盘规则：年柱以立春为界、月柱按节气换月（内置 1900-2100 天文年历精确核定）、日柱按儒略日连续推算、时柱由日干五鼠遁起。命理模型为简化演示算法，结果仅供娱乐参考。</p>
      </div>`;
      const cp = $("#ppz-copy");
      if (cp) cp.addEventListener("click", () => {
        const lines = [
          "【八字排盘 · 好名网】",
          `出生：${ctx.dt}${ctx.hi === "none" ? "" : " " + ZHI_LABELS[parseInt(ctx.hi, 10)]}${ctx.place ? " · " + ctx.place : ""}`,
          `四柱：${b.gz.join(" ")}`,
          `日主：${dm}（${b.dayStemWx}）· ${b.dayMaster.tag}${b.deling ? " · 得令" : ""}${b.tiaohou ? " · " + b.tiaohou : ""}`,
          `五行旺衰：${window.HM_DATA.WUXING.map(w => `${w}${b.wxWeight[w].toFixed(1)}`).join(" ")}`,
          `喜用神：${b.favorable}${b.tabooWx ? " · 忌 " + b.tabooWx : ""} · 生肖：${b.zodiac} · ${xunShou}旬空${kong}`,
          "—— 命理模型为简化演示算法，仅供娱乐参考"
        ];
        copyText(lines.join("\n"), () => toast("八字摘要已复制"));
      });
    }
  }

  /* ---------- 评分报告页（扩展版） ---------- */
  function initScore() {
    ensureStrokes(() => {
    const box = $("#score-result");
    if (!box) return;
    const q = new URLSearchParams(location.search);
    const name = (q.get("name") || "").trim();
    const gender = parseInt(q.get("gender") ?? "1", 10);
    const date = q.get("date");
    const hourRaw = q.get("hour");
    const place = q.get("place") || "";
    const inp = $("#sc-name"); if (inp) inp.value = name;
    if (!name) { box.innerHTML = '<p class="empty">请输入完整姓名后查询。</p>'; return; }
    const wg = window.HM_ENGINE.wuge(name);
    if (!wg) { box.innerHTML = '<p class="empty">姓名至少需要两个字。</p>'; return; }
    document.title = `${name}的姓名测试打分 - 好名网`;
    statsTrack("score", name);
    const rows = [["天格", wg.tian], ["人格", wg.ren], ["地格", wg.di], ["外格", wg.wai], ["总格", wg.zong]];
    let extra = "";
    let bz = null;
    // 生肖板块
    if (date) {
      const [y, m, d] = date.split("-").map(Number);
      const bh = hourRaw && hourRaw !== "none" ? parseInt(hourRaw, 10) : 12;
      const b = window.HM_ENGINE.bazi({ y, m, d: d, h: bh, place });
      bz = b;
      const zx = window.HM_DATA.SHENGXIAO[b.zodiac] || [];
      const roots = zx.map(r => `<a class="tagline" style="padding:4px 12px;border-radius:999px;background:#f6ead9;color:var(--red);font-size:12px;" href="${toolUrl("kxzd.html")}">${r} 字根</a>`).join("");
      extra += `<div class="report-sec"><h3>生肖分析</h3>
        <p>生肖：<b>${b.zodiac}</b>。传统民俗认为不同生肖有宜用字根，以下为「${b.zodiac}」的常见宜用字根（民俗说法，仅供参考）：</p>
        <div class="tagline">${roots || "<span>暂无</span>"}</div></div>`;
      // 星座板块
      if (m && d) {
        const c = window.HM_ENGINE.constellation(m, d);
        if (c) extra += `<div class="report-sec"><h3>星座分析</h3><p>星座：<b>${c.n}</b>（${c.m}）。星座维度仅供娱乐，与姓名数理无关联。</p></div>`;
      }
      // 八字五行板块
      const wx = window.HM_DATA.WUXING.map(w => `${w} ${b.wxCount[w]}`).join(" · ");
      extra += `<div class="report-sec"><h3>生辰五行分析</h3>
        <p>四柱：${b.gz.join(" ")}；五行分布：${wx}；${b.missing.length ? "缺 " + b.missing.join("、") : "五行不缺"}；简化喜用神：<b>${b.favorable}</b>。</p>
        <p style="margin-top:6px;">名字用字五行：<b>${Array.from(name).map(c => { const i = window.HM_DATA.CHARS.find(x => x.c === c); return i ? i.w : "?"; }).join(" ")}</b></p></div>`;
    }
    if (place) extra += `<div class="report-sec"><h3>出生地</h3><p>${place}${bz && bz.solarNote ? `（${bz.solarNote}，时柱已按当地太阳时核定）` : "（当前版本仅作记录展示，未参与计算）"}。</p></div>`;
    // 大数据分析
    const bd = window.HM_ENGINE.bigData(name);
    extra += `<div class="report-sec"><h3>大数据分析</h3>
      <p>用字热度：<b>${bd.heat}</b>/100 · 重名指数：<b>${bd.dupIndex}</b>（${bd.tag}） · 辨识度：<b>${bd.unique}</b> · 姓名总笔画：<b>${bd.strokeSum}</b></p>
      <p style="margin-top:6px;color:#a0a0a0;font-size:12px;">* ${bd.note}</p></div>`;
    // 音形义详解
    const ph = window.HM_ENGINE.phonetic(name);
    const phRows = ph.info.map(i => `<tr><td style="font-size:20px;">${i.c}</td><td>${i.py}</td><td>${i.sm || "—"} / ${i.ym || "—"}</td><td>${i.pingze}</td><td>${i.radical || "—"}</td><td>${i.strokes || "—"}画</td><td style="text-align:left;">${i.meaning || "—"}</td></tr>`).join("");
    extra += `<div class="report-sec"><h3>音形义详解</h3>
      <table class="wuge-table">
        <tr><th>字</th><th>拼音</th><th>声母/韵母</th><th>平仄</th><th>部首</th><th>笔画</th><th>字义</th></tr>
        ${phRows}
      </table>
      <p style="margin-top:8px;">声调组合：<b>${ph.pingze}</b>（平仄交替为佳）</p>
      ${ph.risks.length ? `<p style="margin-top:6px;color:var(--red);">谐音提示：与「${ph.risks.map(r => r.with).join("、")}」读音相近，${ph.risks[0].note}，建议斟酌。</p>` : `<p style="margin-top:6px;">谐音检查：<b>未发现常见不雅谐音</b>。</p>`}
    </div>`;
    // 姓名学分析
    const na = window.HM_ENGINE.nameAnalysis(name);
    if (na) extra += `<div class="report-sec"><h3>姓名学分析</h3>
      <p>三才配置：<b>${na.sancai}</b> —— ${na.sancaiText}</p>
      <table class="wuge-table" style="margin-top:8px;">
        <tr><th>五格</th><th>数理</th><th>吉凶</th><th>解读</th></tr>
        ${na.rows.map(r => `<tr><td>${r.n}</td><td>${r.g.n}</td><td class="luck-${r.g.luck === "吉" ? "j" : r.g.luck === "凶" ? "x" : "b"}">${r.g.luck}</td><td style="text-align:left;">${r.d}：${r.text}</td></tr>`).join("")}
      </table>
      <p style="margin-top:8px;"><b>综合结论：</b>${na.summary}</p></div>`;
    /* 综合评分（五格+音律+字义+生肖+热度） */
    const so = window.HM_ENGINE.scoreOf(name, date ? (() => { const [yy, mm, dd] = date.split("-").map(Number); return { y: yy, m: mm, d: dd, h: 12 }; })() : null);
    const final = so ? so.final : wg.score;
    box.innerHTML = `
      <div class="score-hero">
        <h2>${name}</h2>
        <div class="score-big s${final >= 90 ? "hi" : final >= 75 ? "mid" : "low"}">${final}<i>分</i></div>
        <p>综合评分（五格数理 + 音律读感 + 字义内涵${so && so.breakdown.some(b => b.k === "生肖契合") ? " + 生肖契合" : ""}）</p>
        ${so ? `<div class="six-grid" style="margin-top:10px;">${so.breakdown.map(b => `<div class="six-score"><span>${b.k}</span><div class="bar"><i style="width:${b.v}%"></i></div><b>${b.v}</b></div>`).join("")}</div>` : ""}
        <p style="margin-top:8px;">康熙笔画：${wg.strokes.join(" + ")} · 三才：${wg.sancai}（${wg.sancaiLuck}）</p>
      </div>
      <table class="wuge-table">
        <tr><th>五格</th><th>笔画</th><th>五行</th><th>吉凶</th></tr>
        ${rows.map(([t, g]) => `<tr><td>${t}</td><td>${g.n}</td><td>${g.wx}</td><td class="luck-${g.luck === "吉" ? "j" : g.luck === "凶" ? "x" : "b"}">${g.luck}</td></tr>`).join("")}
      </table>
      ${extra}
      <p class="score-note">* 三才五格与命理模型为传统公共领域算法的简化实现，评分仅供娱乐参考。</p>`;
  
    });
  }  /* ---------- 配对 ---------- */
  function initMatch() {
    const form = $("#form-match");
    if (!form) return;
    const box = $("#match-result");
    form.addEventListener("submit", e => {
      e.preventDefault();
      const a = $("#mt-a").value.trim(), b = $("#mt-b").value.trim();
      if (!a || !b) return alert("请输入两个姓名");
      const r = window.HM_ENGINE.matchNames(a, b);
      box.innerHTML = `
        <div class="match-hero">
          <div class="match-name">${a}<i>${r.s1}画</i></div>
          <div class="match-heart"><span data-ic="heart"></span></div>
          <div class="match-name">${b}<i>${r.s2}画</i></div>
        </div>
        <div class="score-big s${r.score >= 90 ? "hi" : r.score >= 75 ? "mid" : "low"}">${r.score}<i>分</i></div>
        <p class="match-tag">${r.tag}</p>
        ${r.detail ? `<p style="font-size:13px;color:var(--ink-2);">和合分析：${r.detail.wxTxt} · ${r.detail.phTxt}</p>` : ""}
        <p class="score-note">* 配对结果为娱乐化数理游戏，仅供活跃气氛。</p>`;
      box.classList.add("on");
      statsTrack("match", a + "/" + b);
    });
    // 生日配对（生肖六合/三合/六冲，民俗娱乐）
    const zf2 = $("#form-match-birth");
    if (zf2) zf2.addEventListener("submit", e => {
      e.preventDefault();
      const b1 = $("#mb-a").value, b2 = $("#mb-b").value;
      if (!b1 || !b2) return alert("请选择两个出生日期");
      const [y1, m1, d1] = b1.split("-").map(Number);
      const [y2, m2, d2] = b2.split("-").map(Number);
      const r = window.HM_ENGINE.zodiacMatch({ y: y1, m: m1, d: d1, h: 12 }, { y: y2, m: m2, d: d2, h: 12 });
      const box2 = $("#match-birth-result");
      box2.innerHTML = `
        <div class="match-hero">
          <div class="match-name">${r.z1}<i>一方生肖</i></div>
          <div class="match-heart"><span data-ic="heart"></span></div>
          <div class="match-name">${r.z2}<i>另一方生肖</i></div>
        </div>
        <div class="score-big s${r.score >= 90 ? "hi" : r.score >= 75 ? "mid" : "low"}">${r.score}<i>分</i></div>
        <p class="match-tag">${r.tag}</p>
        <p class="score-note">* ${r.note}</p>`;
      box2.classList.add("on");
    });
  }

  /* ---------- 康熙字典 ---------- */
  function initKxzd() {
    const grid = $("#kxzd-grid");
    if (!grid) return;
    const q = new URLSearchParams(location.search);
    let wx = q.get("wx") || "all";
    let st = "all";
    /* 月度用字排行 */
    const now = new Date();
    const mLabel = `${now.getFullYear()}年${now.getMonth() + 1}月`;
    const rankRender = (id, gender) => {
      const el = document.getElementById(id);
      if (!el) return;
      const pool = window.HM_DATA.CHARS.filter(x => gender === 0 ? true : (x.g === gender || x.g === 0));
      pool.sort((a, b) => (b.k * 7 + b.c.charCodeAt(0)) % 97 - (a.k * 7 + a.c.charCodeAt(0)) % 97);
      el.innerHTML = pool.slice(0, 8).map((x, i) => `
        <a class="rank-item" href="${toolUrl("char.html")}?c=${encodeURIComponent(x.c)}">
          <span class="rank-no r${i < 3 ? i + 1 : "n"}">${i + 1}</span>
          <span class="rank-char">${x.c}</span>
          <span class="rank-sub">${x.w} · ${x.k}画</span>
        </a>`).join("");
    };
    const hb = document.getElementById("rank-month-boy"), hg = document.getElementById("rank-month-girl");
    if (hb) hb.textContent = `${mLabel}男孩起名用字排行`;
    if (hg) hg.textContent = `${mLabel}女孩起名用字排行`;
    rankRender("kxzd-rank-boy", 1);
    rankRender("kxzd-rank-girl", 2);
    const render = () => {
      const kw = ($("#kxzd-search").value || "").trim();
      const chars = window.HM_DATA.CHARS.filter(x => {
        if (wx !== "all" && x.w !== wx) return false;
        if (st !== "all" && (window.HM_ENGINE.structOf || (() => ""))(x.c) !== st) return false;
        return !kw || x.c.includes(kw) || x.p.includes(kw.toLowerCase());
      });
      /* 任意字查询：输入单个汉字且不在五行字库 → 显示说文全量查询卡 */
      const extra = (/^[\u4e00-\u9fff]$/.test(kw) && !window.HM_DATA.CHARS.some(x => x.c === kw))
        ? `<a class="char-card" href="${toolUrl("char.html")}?c=${encodeURIComponent(kw)}" style="border-color:var(--red);grid-column:1/-1;">
            <b>${kw}</b><span>查《说文解字》全库释义</span><i>共 9833 字头</i><em>点击查看该字的《说文解字》原文与段玉裁注 →</em></a>` : "";
      grid.innerHTML = extra + chars.map(x => `
        <a class="char-card" href="${toolUrl("char.html")}?c=${encodeURIComponent(x.c)}">
          <b>${x.c}</b><span>${x.p}</span><i>${x.w} · ${x.k}画 · ${(window.HM_ENGINE.structOf || (() => ""))(x.c)}</i><em>${x.m}</em>
        </a>`).join("") || (extra ? "" : '<p class="empty">没有找到匹配的汉字。</p>');
    };
    $$("#kxzd-tabs .tab").forEach(t => t.addEventListener("click", () => {
      $$("#kxzd-tabs .tab").forEach(x => x.classList.remove("on")); t.classList.add("on");
      wx = t.dataset.wx; render();
    }));
    $$("#kxzd-struct .tab").forEach(t => t.addEventListener("click", () => {
      $$("#kxzd-struct .tab").forEach(x => x.classList.remove("on")); t.classList.add("on");
      st = t.dataset.st; render();
    }));
    $("#kxzd-search").addEventListener("input", render);
    if (wx !== "all") { const t = $(`#kxzd-tabs .tab[data-wx="${wx}"]`); if (t) { $$("#kxzd-tabs .tab").forEach(x => x.classList.remove("on")); t.classList.add("on"); } }
    render();
  }

  /* ---------- 康熙笔画全量库懒加载（25100 字） ---------- */
  let _strokesLoading = false;
  function ensureStrokes(cb) {
    if (window.HM_DATA.STROKES_FULL) return cb();
    const done = () => { _strokesLoading = false; cb(); };
    if (_strokesLoading) {
      const t = setInterval(() => { if (window.HM_DATA.STROKES_FULL) { clearInterval(t); cb(); } }, 120);
      setTimeout(() => clearInterval(t), 6000);
      return;
    }
    _strokesLoading = true;
    const s = document.createElement("script");
    s.src = (window._LE && window._LE.tpl ? window._LE.tpl : "assets/") + "js/strokes-full.js";
    s.onload = done; s.onerror = done;
    document.head.appendChild(s);
  }

  /* ---------- 说文解字：懒加载全量库（9833 字头）与通用查询 ---------- */
  function loadShuowenFull(cb) {
    if (window.HM_DATA.SHUOWEN_FULL) return cb();
    const s = document.createElement("script");
    s.src = (window._LE && window._LE.tpl ? window._LE.tpl : "assets/") + "js/shuowen-full.js";
    s.onload = cb; s.onerror = cb;
    document.head.appendChild(s);
  }
  function shuowenLookup(c) {
    const core = (window.HM_DATA.SHUOWEN || {})[c];
    if (core) return { expl: core.expl, rad: core.rad, fanqie: core.fanqie, vol: core.vol, duan: core.duan, py: "", src: "core" };
    const F = window.HM_DATA.SHUOWEN_FULL, SIM = window.HM_DATA.SHUOWEN_SIM;
    if (F) {
      const key = (SIM && SIM[c]) || c;
      const e = F[key];
      if (e) return { expl: e.expl, rad: e.rad, fanqie: e.fq, vol: e.vol, duan: e.duan, py: e.py || "", src: "full" };
    }
    return null;
  }

  /* ---------- 汉字详情 ---------- */
  function initChar() {
    const box = $("#char-detail");
    if (!box) return;
    const c = (new URLSearchParams(location.search).get("c") || "").trim();
    const d = window.HM_ENGINE.charDetail(c);
    if (!d) {
      /* 字库外任意字：懒加载全量说文库，渲染通用释义 */
      loadShuowenFull(() => {
        const sw = shuowenLookup(c);
        if (!sw) { box.innerHTML = '<p class="empty">《说文解字》中未查到「' + c + '」字，请尝试其他汉字。</p>'; return; }
        document.title = `${c}字的说文解字释义 - 好名网`;
        insertCrumb([{ name: "康熙字典", url: "kxzd.html" }, { name: `${c}字` }]);
    statsGet(j => {
      const el = document.getElementById("char-real-usage");
      if (el && j && j.charUsage && j.charUsage[c]) el.textContent = `📊 本站真实查询统计：${j.charUsage[c]} 次（实时累计）`;
    });
        const swBlocks = `
          <div class="rel-box" style="margin-top:14px;">
            <h3>《説文解字》清 · 陈昌治刻本</h3>
            <p style="font-size:15px;color:var(--ink);line-height:2;font-family:var(--serif);">【卷${sw.vol || "—"}】【${sw.rad || ""}部】${c}<br>${sw.expl || ""}</p>
            ${sw.fanqie ? `<p style="font-size:13px;color:var(--ink-2);margin-top:6px;">【反切】${sw.fanqie}</p>` : ""}
            ${sw.py ? `<p style="font-size:13px;color:var(--ink-2);margin-top:4px;">【拼音】${sw.py}</p>` : ""}
          </div>
          ${(sw.duan || []).length && sw.duan[0].n ? `<div class="rel-box" style="margin-top:14px;">
            <h3>《説文解字注》清 · 段玉裁</h3>
            ${sw.duan[0].e ? `<p style="font-size:14px;color:var(--ink);line-height:2;">${sw.duan[0].e}</p>` : ""}
            <p style="font-size:13px;color:var(--ink-2);line-height:1.9;">【注】${sw.duan[0].n}${sw.duan[0].n.length >= 300 ? "……" : ""}</p>
          </div>` : ""}
          <div class="rel-box" style="margin-top:14px;">
            <h3>本字与五行起名</h3>
            <p style="font-size:14px;color:var(--ink-2);line-height:1.9;">「${c}」字暂未收录进本站五行字库（含康熙笔画、五行属性与取名寓意评估），以上为《说文解字》原始释义。可从 <a href="${toolUrl("kxzd.html")}" style="color:var(--red);">五行字库</a> 选择已评估用字起名。</p>
          </div>`;
        box.innerHTML = withLayout(`
          <div class="strip-hero">
            <div>
              <h1>${c}字的说文解字释义</h1>
              <div class="meta">${sw.py ? "拼音 " + sw.py : ""}${sw.rad ? " · " + sw.rad + "部" : ""}${sw.fanqie ? " · 反切 " + sw.fanqie : ""}</div>
              <div class="cta-row"><a class="btn-ghost" href="${toolUrl("kxzd.html")}">返回字库</a></div>
            </div>
            <div class="bigchar">${glyphSvg(c, 150)}</div>
          </div>
          ${swBlocks}`, c);
      });
      return;
    }
    document.title = `${c}字的起名寓意 - 好名网`;
    const rel = (title, arr) => `<div class="rel-box"><h3>${title}</h3><div class="rel-chars">${
      arr.length ? arr.map(x => `<a href="${toolUrl("char.html")}?c=${encodeURIComponent(x.c)}" title="${x.m}">${x.c}</a>`).join("") : "<span style='font-size:13px;color:var(--ink-2);'>暂无关联字</span>"
    }</div></div>`;
    const ex = d.extra || {};
    const zg = ex.zixing || 0;
    const zxPos = ((zg + 10) / 20 * 100).toFixed(0);
    const tabooHtml = (ex.taboo || []).map((t, i) => `<li style="margin-bottom:6px;">${i + 1}、${t}</li>`).join("");
    const cyHtml = (d.chengyu || []).map(x => `<li style="margin-bottom:6px;"><b>${x[0]}</b> —— ${x[1]}</li>`).join("");
    const sw = (window.HM_DATA.SHUOWEN || {})[c];
    const swHtml = sw ? `
      <div class="rel-box" style="margin-top:14px;">
        <h3>《説文解字》清 · 陈昌治刻本</h3>
        <p style="font-size:15px;color:var(--ink);line-height:2;font-family:var(--serif);">【卷${sw.vol || "—"}】【${sw.rad || ""}部】${c}<br>${sw.expl || ""}</p>
        ${sw.fanqie ? `<p style="font-size:13px;color:var(--ink-2);margin-top:6px;">【反切】${sw.fanqie}</p>` : ""}
      </div>
      ${(sw.duan || []).length ? `<div class="rel-box" style="margin-top:14px;">
        <h3>《説文解字注》清 · 段玉裁</h3>
        ${sw.duan.map(n => `<div style="margin-bottom:10px;">${n.e ? `<p style="font-size:14px;color:var(--ink);line-height:2;">${n.e}</p>` : ""}${n.n ? `<p style="font-size:13px;color:var(--ink-2);line-height:1.9;">【注】${n.n}${(n.n || "").length >= 400 ? "……" : ""}</p>` : ""}</div>`).join("")}
      </div>` : ""}` : `
      <div class="rel-box" style="margin-top:14px;">
        <h3>《説文解字》</h3>
        <p style="font-size:14px;color:var(--ink-2);line-height:1.9;">${c}字为后起字，《説文解字》（东汉 · 许慎）未收录此字头，可参考其部首归属与五行属性了解字义源流。</p>
      </div>`;
    box.innerHTML = withLayout(`
      <div class="strip-hero">
        <div>
          <h1>${c}字的起名寓意含义，${c}字取名有什么意义</h1>
          <div class="meta">${d.self.m}</div>
          <div class="cta-row">
            <a class="btn-ghost" href="${toolUrl("score.html")}?name=李${encodeURIComponent(c)}">测「李${c}」评分</a>
            <a class="btn-ghost" href="${toolUrl("naming.html")}?surname=李&gender=${d.self.g === 2 ? 2 : 1}&date=2026-01-01&fixed=${encodeURIComponent(c)}">用该字起名</a>
          </div>
        </div>
        <div class="bigchar">${glyphSvg(c, 150)}</div>
      </div>
      <div class="rel-box" style="margin-top:14px;">
        <h3>${c}字的信息属性</h3>
        <div class="attr-table">
          <div><b>拼音</b><span>${d.self.p}</span></div>
          <div><b>注音</b><span>${ex.zhuyin || "—"}</span></div>
          <div><b>五行属性</b><span>${d.self.w}</span></div>
          <div><b>部首</b><span>${d.radical || "—"}</span></div>
          <div><b>总笔画</b><span>${d.self.k}画</span></div>
          <div><b>康熙字典笔画</b><span>（${c}:${d.self.k}）画</span></div>
          <div><b>统一码</b><span>${ex.unicode || "—"}</span></div>
          <div><b>结构</b><span>${ex.struct || "—"}</span></div>
          <div><b>吉凶寓意</b><span>${ex.jixiong || "吉"}</span></div>
          <div><b>是否为常用字</b><span>${ex.common ? "是" : "否"}</span></div>
          <div><b>姓名学</b><span>${ex.nameXue || "—"}</span></div>
          <div><b>取名寓意</b><span>${d.self.m}</span></div>
        </div>
      </div>
      <div class="rel-box" style="margin-top:14px;">
        <h3>${c}字的起名参考</h3>
        <div class="six-score"><span>推荐度</span><div class="bar"><i style="width:${ex.recScore}%"></i></div><b>${ex.recScore}%</b></div>
        <div class="six-score"><span>文化印象</span><div class="bar"><i style="width:${ex.cultureScore}%"></i></div><b>${ex.cultureScore}%</b></div>
        <div class="six-score"><span>字性</span><div class="bar"><i style="width:${zxPos}%;background:linear-gradient(90deg,#5b7fa6,#b03a2e);"></i></div><b>${zg < 0 ? Math.abs(zg) + "偏男" : zg > 0 ? zg + "偏女" : "中性"}</b></div>
      </div>
      <div class="rel-box" style="margin-top:14px;">
        <h3>${c}字的起名含义</h3>
        <p style="font-size:14px;color:var(--ink-2);line-height:1.9;">${ex.meaning || ""}</p>
        <p style="font-size:14px;color:var(--ink-2);line-height:1.9;margin-top:8px;">起名寓意：${c}字用着起名寓指${d.self.m}之义，寓意吉祥又有内涵。</p>
        <p style="font-size:14px;color:var(--ink-2);line-height:1.9;margin-top:8px;">${ex.usage || ""}</p>
        <p id="char-real-usage" style="font-size:13px;color:var(--red);margin-top:6px;"></p>
      </div>
      ${tabooHtml ? `<div class="rel-box" style="margin-top:14px;"><h3>${c}字的起名忌讳</h3><ul style="font-size:14px;color:var(--ink-2);line-height:1.9;padding-left:4px;">${tabooHtml}</ul></div>` : ""}
      ${swHtml}
      ${cyHtml ? `<div class="rel-box" style="margin-top:14px;"><h3>含${c}字的成语</h3><ul style="font-size:14px;color:var(--ink-2);line-height:1.9;padding-left:4px;">${cyHtml}</ul></div>` : ""}
      <div class="rel-grid" style="margin-top:14px;">
        ${rel("与" + c + "字同音的字", d.sameSound)}
        ${rel("与" + c + "字同部首的字", d.sameRadical)}
        ${rel("与" + c + "字同笔画的字", d.sameStrokes)}
        ${rel("与" + c + "字同五行的字", d.sameWuxing)}
      </div>`, c);
    insertCrumb([{ name: "康熙字典", url: "kxzd.html" }, { name: `${c}字` }]);
  }

  /* ---------- 名字详情 ---------- */
  function initMingzi() {
    ensureStrokes(() => {
    const box = $("#mingzi-detail");
    if (!box) return;
    const name = (new URLSearchParams(location.search).get("name") || "").trim();
    if (!name || name.length < 2) { box.innerHTML = '<p class="empty">请通过起名结果或排行页进入。</p>'; return; }
    insertCrumb([{ name: "名字解析", url: "mingzi.html" }, { name: name }]);
    const d = window.HM_ENGINE.nameDetail(name);
    document.title = `${name}的寓意解析 - 好名网`;
    const charsHtml = d.chars.map(c => `<div class="rel-box" style="text-align:center;"><b style="font-size:34px;font-family:var(--serif);color:var(--red-deep);">${c.c}</b><p style="font-size:13px;color:var(--ink-2);">${c.p || ""} ${c.w ? "· " + c.w : ""} · ${c.k}画<br>${c.m || ""}</p></div>`).join("");
    const sixHtml = (d.six || []).map(s => `
      <div class="six-score"><span>${s.k}</span><div class="bar"><i style="width:${s.v}%"></i></div><b>${s.v}%</b></div>`).join("");
    const charAnHtml = (d.charAnalysis || []).map(a => `
      <div class="rel-box"><h3>${a.c}：${a.jixiong}，${a.k}画，${a.struct}，是否常用：${a.common ? "是" : "否"}</h3>
      <p style="font-size:14px;color:var(--ink-2);line-height:1.9;">解析：${a.analysis}</p></div>`).join("");
    const cyHtml = (d.chengyu || []).map(x => `<li><b>${x.word}</b> —— 取自《成语大全》解释：${x.note}</li>`).join("");
    const recHtml = (d.recSurnames || []).map(s => `<a href="surname.html?c=${encodeURIComponent(s)}">${s}</a>`).join("");
    /* 名字出处来源：诗词句命中 + 说文溯源 */
    const srcItems = [];
    (d.chars || []).forEach(ci => {
      (window.HM_DATA.POEMS || []).forEach(p => {
        if ((p.line || "").includes(ci.c) && srcItems.length < 5) {
          srcItems.push(`<li style="margin-bottom:8px;"><b>${ci.c}</b> 字见於《${p.src}》：「${p.line}」</li>`);
        }
      });
    });
    const swItems = (d.chars || []).map(ci => {
      const sw = (window.HM_DATA.SHUOWEN || {})[ci.c];
      return sw ? `<li style="margin-bottom:8px;"><b>${ci.c}</b>，《説文解字》：「${sw.expl}」</li>` : "";
    }).filter(Boolean).join("");
    box.innerHTML = `
      <div class="detail-hero">
        <h1 style="font-size:30px;letter-spacing:10px;font-family:var(--serif);color:var(--red-deep);margin:0 0 8px;">${name}</h1>
        <div class="meta">数理评分 <b>${d.wg ? d.wg.score : "—"}分</b> · 三才 ${d.wg ? d.wg.sancai : "—"}（${d.wg ? d.wg.sancaiLuck : "—"}）</div>
      </div>
      <div class="rel-box" style="margin-top:14px;">
        <h3>${name}名字测试打分</h3>
        <div class="six-grid">${sixHtml}</div>
      </div>
      <div class="rel-box" style="margin-top:14px;">
        <h3>${name}名字的推荐姓氏</h3>
        <div class="poem-names">${recHtml || "<span style='font-size:13px;color:var(--ink-2);'>暂无推荐</span>"}</div>
      </div>
      <div class="rel-grid" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));margin-top:14px;">${charsHtml}</div>
      ${d.wxText ? `<div class="rel-box" style="margin-top:14px;"><h3>${name}名字五行</h3><p style="font-size:14px;color:var(--ink-2);line-height:1.9;">${d.wxText}</p></div>` : ""}
      ${charAnHtml ? `<div style="margin-top:14px;"><h3 style="font-size:17px;color:var(--red-deep);margin-bottom:10px;">${name}名字字型及字义解析</h3>${charAnHtml}</div>` : ""}
      ${cyHtml ? `<div class="rel-box" style="margin-top:14px;"><h3>${name}成语用字</h3><ul style="font-size:14px;color:var(--ink-2);line-height:2;padding-left:18px;">${cyHtml}</ul></div>` : ""}
      ${swItems ? `<div class="rel-box" style="margin-top:14px;"><h3>${name}名字说文溯源</h3><ul style="font-size:14px;color:var(--ink-2);line-height:2;padding-left:4px;">${swItems}</ul></div>` : ""}
      ${srcItems.length ? `<div class="rel-box" style="margin-top:14px;"><h3>${name}名字出处来源</h3><ul style="font-size:14px;color:var(--ink-2);line-height:2;padding-left:4px;">${srcItems.join("")}</ul></div>` : ""}
      <div class="rel-box" style="margin-top:14px;">
        <h3>名字变换（同音 / 同部首 / 同笔画 / 同五行）</h3>
        ${(() => {
          const v = window.HM_ENGINE.nameVariants(name);
          const row = (t, arr) => arr.length ? `<p style="font-size:13px;color:var(--ink-2);margin:6px 0 2px;"><b>${t}</b></p><div class="poem-names">${arr.map(x => `<a href="${toolUrl("mingzi.html")}?name=${encodeURIComponent(x)}">${x}</a>`).join("")}</div>` : "";
          return row("同音变换", v.sound) + row("同部首变换", v.radical) + row("同笔画变换", v.strokes) + row("同五行变换", v.wuxing) || "<span style='font-size:13px;color:var(--ink-2);'>暂无变换候选</span>";
        })()}
      </div>
      <div class="cta-row">
        <a class="btn-ghost" href="${toolUrl("score.html")}?name=${encodeURIComponent(name)}">查看完整评分报告</a>
        <a class="btn-ghost" href="${toolUrl("naming.html")}">重新起名</a>
      </div>`;
  
    });
  }  /* ---------- 百家姓 ---------- */
  function initBaijiaxing() {
    const grid = $("#bjx-grid");
    if (!grid) return;
    const letters = {};
    window.HM_DATA.SURNAMES.forEach(s => {
      const L = (s.p || "").replace(/[^a-zA-Z]/g, "").charAt(0).toUpperCase() || "#";
      (letters[L] = letters[L] || []).push(s);
    });
    const keys = Object.keys(letters).sort();
    const rankOf = (c) => window.HM_DATA.SURNAMES.findIndex(s => s.c === c) + 1;
    grid.innerHTML = `
      <div class="kxzd-tabs" id="bjx-index" style="grid-column:1/-1;">
        ${keys.map(k => `<a class="tab" href="#L${k}">${k}</a>`).join("")}
      </div>` + keys.map(k => `
      <div class="bjx-group" id="L${k}" style="grid-column:1/-1;">
        <h4 style="margin:10px 0 6px;color:var(--red);font-size:15px;">${k}</h4>
        <div class="bjx-grid">
          ${letters[k].map(s => `<a class="bjx-card" href="surname.html?c=${encodeURIComponent(s.c)}">
            <b>${s.c}</b><span>${s.p || ""} · ${s.k}画</span><i style="font-style:normal;font-size:12px;color:var(--red);">排名:${rankOf(s.c)}</i></a>`).join("")}
        </div>
      </div>`).join("");
  }

  /* ---------- 姓氏详情页 ---------- */
  function initSurname() {
    const box = $("#surname-detail");
    if (!box) return;
    const c = (new URLSearchParams(location.search).get("c") || "").trim();
    const s = window.HM_DATA.SURNAMES.find(x => x.c === c);
    if (!s) { box.innerHTML = '<p class="empty">未收录该姓氏。</p>'; return; }
    document.title = `${c}姓起名 - ${c}姓的男孩女孩名字大全 - 好名网`;
    // 该姓氏的推荐名（按性别生成两组）
    const boys = window.HM_ENGINE.generateByBazi(c, 1, { y: 2026, m: 6, d: 15, h: 10 }, 6);
    const girls = window.HM_ENGINE.generateByBazi(c, 2, { y: 2026, m: 6, d: 15, h: 10 }, 6);
    const card = n => `<a class="name-text" style="font-size:20px;" href="${toolUrl("mingzi.html")}?name=${encodeURIComponent(n.name)}">${n.name}</a>`;
    box.innerHTML = withLayout(`
      <div class="strip-hero">
        <div>
          <h1>${c}姓起名，${c}姓男孩女孩名字大全</h1>
          <div class="meta">拼音：<b>${s.p || "—"}</b> · 康熙笔画：<b>${s.k} 画</b> · 收录于百家姓</div>
          <div class="cta-row">
            <a class="btn-ghost" href="${toolUrl("naming.html")}?surname=${encodeURIComponent(c)}&gender=1&date=2026-01-01">给${c}姓宝宝起名</a>
            <a class="btn-ghost" href="${toolUrl("naming.html")}?surname=${encodeURIComponent(c)}&gender=2&date=2026-01-01">${c}姓女孩起名</a>
          </div>
        </div>
        <div class="bigchar">${glyphSvg(c, 150)}</div>
      </div>
      <div class="rel-box" style="margin-top:14px;">
        <h3>${c}姓男孩名字推荐</h3>
        <div class="poem-names">${boys.names.map(n => card(n) + `<span class="name-score s${n.score >= 90 ? "hi" : n.score >= 75 ? "mid" : "low"}">${n.score}分</span>`).join("")}</div>
      </div>
      <div class="rel-box" style="margin-top:14px;">
        <h3>${c}姓女孩名字推荐</h3>
        <div class="poem-names">${girls.names.map(n => card(n) + `<span class="name-score s${n.score >= 90 ? "hi" : n.score >= 75 ? "mid" : "low"}">${n.score}分</span>`).join("")}</div>
      </div>`, c);
    insertCrumb([{ name: "百家姓", url: "baijiaxing.html" }, { name: `${c}姓` }]);
  }

  /* ---------- 工具页底部「起名知识」文章内链区块 ---------- */
  const TOOL_ART_MAP = {
    naming: ["bazi", "zhishi"], bazi: ["bazi", "zhishi"], score: ["bazi", "zhishi"],
    gongsi: ["zhishi", "bazi"], gaiming: ["zhishi", "bazi"], match: ["zhishi", "bazi"],
    search: ["bazi", "shici", "zhouyi", "zhishi"],
    poems: ["shici"], shijing: ["shici"], chuci: ["shici"], tangshi: ["shici"], songci: ["shici"],
    zhouyi: ["zhouyi"],
    kxzd: ["zhishi", "bazi"], baijiaxing: ["zhishi", "bazi"],
    mingzi: ["bazi", "shici", "zhouyi", "zhishi"]
    // char / surname 已由侧栏「阅读排行榜」提供文章入口，不重复注入
  };
  function injectToolArticles() {
    const m = (location.pathname.match(/\/?tool\/([a-z0-9]+)\.html/) || [])[1];
    const cats = m && TOOL_ART_MAP[m];
    if (!cats || !window.HM_DATA || !window.HM_DATA.ARTICLES) return;
    if (document.getElementById("tool-arts")) return;
    // 静态内容层已含「相关推荐」文章内链：不再重复注入，避免内链冗余与布局冲突
    if (document.querySelector(".tool-static")) return;
    const pool = [];
    cats.forEach(k => (window.HM_DATA.ARTICLES[k] || []).forEach(a => { if (a.u) pool.push(a); }));
    if (!pool.length) return;
    // 仅在正文容器追加，严格排除页头/页脚/抽屉，防止把区块插进 header 撑坏布局
    const host = document.querySelector(".page-body") ||
      [...document.querySelectorAll(".wrap")].find(w =>
        !w.classList.contains("form-card") &&
        !w.classList.contains("header-inner") &&
        !w.classList.contains("footer-inner") &&
        !w.closest("#site-header") && !w.closest("#site-footer") && !w.closest(".nav-drawer")
      );
    if (!host) return;
    host.insertAdjacentHTML("beforeend", `<div class="rel-box tool-arts" id="tool-arts" style="margin-top:14px;">
      <h3>起名知识 · 延伸阅读</h3>
      <ul class="art-more-list">${pool.slice(0, 4).map(a =>
        `<li><a href="${a.u}">${a.t}</a></li>`).join("")}</ul>
    </div>`);
  }

  /* ---------- 移动端底部工具栏 ---------- */
  function initMobileBar() {
    if (document.getElementById("mobile-bar")) return;
    const bar = document.createElement("div");
    bar.id = "mobile-bar";
    bar.innerHTML = `
      <a href="${toolUrl("naming.html")}">起名</a>
      <a href="${toolUrl("score.html")}">打分</a>
      <a href="${toolUrl("poems.html")}">诗词</a>
      <a href="${toolUrl("kxzd.html")}">字典</a>
      <a href="${toolUrl("search.html")}">搜索</a>`;
    document.body.appendChild(bar);
  }

  /* ---------- 诗词库 ---------- */
  function initPoems() {
    const list = $("#poem-list");
    if (!list) return;
    const onTab = $("#poem-tabs .tab.on");
    let cat = onTab ? onTab.dataset.cat : "all";
    const render = () => {
      const poems = window.HM_DATA.POEMS.filter(p => cat === "all" || p.cat === cat);
      list.innerHTML = poems.map(p => `
        <div class="poem-card">
          <blockquote>「${p.line}」</blockquote>
          <p class="poem-src">— ${p.src}</p>
          <div class="poem-names">${p.names.map(n => `<a href="${toolUrl("mingzi.html")}?name=${encodeURIComponent(n.n)}">${n.n}</a>`).join("")}</div>
        </div>`).join("");
    };
    $$("#poem-tabs .tab").forEach(t => t.addEventListener("click", () => {
      $$("#poem-tabs .tab").forEach(x => x.classList.remove("on")); t.classList.add("on");
      cat = t.dataset.cat; render();
    }));
    render();
  }

  /* ---------- 通用起名表单（form-qm*） ---------- */
  function initNamingForms() {
    /* URL 深度链接预填：从排盘页等入口带 date/hour/place 进入时自动回填表单 */
    const pre = new URLSearchParams(location.search);
    if (pre.get("date") || pre.get("hour") || pre.get("place")) {
      $$("form[id^=form-qm]").forEach(f => {
        const sfx = f.id.replace("form-qm", "");
        if (pre.get("date")) { const el = $("#date" + sfx); if (el) el.value = pre.get("date"); }
        if (pre.get("hour")) {
          const el = $("#hour" + sfx);
          if (el) { const hv = pre.get("hour"); el.value = (hv === "none" || hv == null) ? "none" : String(Math.floor((parseInt(hv, 10) + 1) / 2) % 12); }
        }
        if (pre.get("place")) { const el = $("#place" + sfx); if (el) el.value = pre.get("place"); }
      });
    }
    $$("form[id^=form-qm]").forEach(form => {
      form.addEventListener("submit", e => {
        e.preventDefault();
        const sfx = form.id.replace("form-qm", "");
        const s = checkSurnameInput($("#xing" + sfx)); if (!s) return;
        const g = genderOf("#" + form.id);
        const dt = $("#date" + sfx).value;
        if (!dt) return alert("请选择出生日期");
        const h = hourVal("#hour" + sfx);
        const place = ($("#place" + sfx) || {}).value || "";
        const wx = form.dataset.wx === "1";
        let url = `${toolUrl("naming.html")}?surname=${encodeURIComponent(s)}&gender=${g}&date=${dt}&hour=${hourParam(h)}&place=${encodeURIComponent(place || "未填写")}`;
        if (wx) {
          url += `&wx1=${$("#wx1" + sfx).value}&wx2=${$("#wx2" + sfx).value}`;
          if ($("#avoid" + sfx) && $("#avoid" + sfx).value.trim()) url += `&avoid=${encodeURIComponent($("#avoid" + sfx).value.trim())}`;
          if ($("#fixed" + sfx) && $("#fixed" + sfx).value.trim()) url += `&fixed=${encodeURIComponent($("#fixed" + sfx).value.trim())}`;
          if ($("#wish" + sfx) && $("#wish" + sfx).value.trim()) url += `&wish=${encodeURIComponent($("#wish" + sfx).value.trim())}`;
          url = url.replace("naming.html", "naming.html") + "&mode=bazi";
        }
        location.href = withSc(url);
      });
    });
    // 周易表单
    const zf = $("#form-zhouyi");
    if (zf) zf.addEventListener("submit", e => {
      e.preventDefault();
      const s = checkSurnameInput($("#xingzy")); if (!s) return;
      const g = genderOf("#form-zhouyi");
      const gua = $("#gua-sel").value;
      location.href = withSc(`${toolUrl("naming.html")}?mode=zhouyi&surname=${encodeURIComponent(s)}&gender=${g}&date=2026-01-01&gua=${encodeURIComponent(gua)}`);
    });
    // 打分页表单
    const sf = $("#form-score-page");
    if (sf) sf.addEventListener("submit", e => {
      e.preventDefault();
      const n = $("#sc-name").value.trim();
      if (!n) return alert("请输入完整姓名");
      const g = genderOf("#form-score-page");
      const dt = $("#sc-date").value;
      const h = hourVal("#sc-hour");
      const place = $("#sc-place").value.trim();
      let url = `${toolUrl("score.html")}?name=${encodeURIComponent(n)}&gender=${g}`;
      if (dt) url += `&date=${dt}&hour=${hourParam(h)}`;
      if (place) url += `&place=${encodeURIComponent(place)}`;
      location.href = withSc(url);
    });
    // 诗词落地页表单 → 跳诗词库
    const scf = $("#form-qmsc");
    if (scf) scf.addEventListener("submit", e => { e.preventDefault(); location.href = withSc("poems.html"); });
    // 改名/男孩女孩表单默认跳八字模式
    const gm = $("#form-qmgm");
    if (gm) gm.addEventListener("submit", e => {
      e.preventDefault();
      const s = checkSurnameInput($("#xinggm")); if (!s) return;
      location.href = withSc(`${toolUrl("naming.html")}?surname=${encodeURIComponent(s)}&gender=${genderOf("#form-qmgm")}&date=${$("#dategm").value || "2026-01-01"}&hour=${hourParam(hourVal("#hourgm"))}&mode=poem`);
    });
    const nan = $("#form-qmnan");
    if (nan) nan.addEventListener("submit", e => {
      e.preventDefault();
      const s = checkSurnameInput($("#xingnan")); if (!s) return;
      location.href = withSc(`${toolUrl("naming.html")}?surname=${encodeURIComponent(s)}&gender=1&date=${$("#datenan").value || "2026-01-01"}&hour=${hourParam(hourVal("#hournan"))}`);
    });
    const nv = $("#form-qmnv");
    if (nv) nv.addEventListener("submit", e => {
      e.preventDefault();
      const s = checkSurnameInput($("#xingnv")); if (!s) return;
      location.href = withSc(`${toolUrl("naming.html")}?surname=${encodeURIComponent(s)}&gender=2&date=${$("#datenv").value || "2026-01-01"}&hour=${hourParam(hourVal("#hournv"))}`);
    });
  }

  /* ---------- 统一搜索页（本地数据 + CMS 内容入口） ---------- */
  function initUnifiedSearch() {
    const input = $("#us-input");
    const list = $("#us-result");
    if (!input || !list) return;
    const cmsBase = window._LE ? window._LE.uri + "index.php?search-index&mid=2&keyword=" : "search.html";
    const run = () => {
      const kw = (input.value || "").trim();
      if (!kw) { list.innerHTML = '<p class="empty">输入关键字，同时搜索汉字、诗词与站内文章。</p>'; return; }
      statsTrack("search", kw);
      const chars = window.HM_DATA.CHARS.filter(x => x.c.includes(kw) || x.p.includes(kw.toLowerCase()) || x.m.includes(kw))
        .sort((x, y) => {
          const sc = z => z.c === kw ? 3 : z.c.startsWith(kw) ? 2 : z.p.startsWith(kw.toLowerCase()) ? 1 : 0;
          return sc(y) - sc(x);
        });
      const poems = window.HM_DATA.POEMS.filter(p => p.line.includes(kw) || p.src.includes(kw));
      const surnames = window.HM_DATA.SURNAMES.filter(x => x.c === kw);
      list.innerHTML = `
        ${surnames.length ? `<h3>姓氏（${surnames.length}）</h3>` + surnames.map(x => `<a class="sr-item" href="${toolUrl("naming.html")}?surname=${encodeURIComponent(x.c)}&gender=1&date=2026-01-01"><b>${x.c}</b> 去给${x.c}姓宝宝起名（${x.k}画）</a>`).join("") : ""}
        ${chars.length ? `<h3>汉字（${chars.length}）</h3>` + chars.slice(0, 8).map(x => `<a class="sr-item" href="${toolUrl("char.html")}?c=${encodeURIComponent(x.c)}"><b>${x.c}</b> ${x.w} · ${x.k}画 · ${x.m}</a>`).join("") : ""}
        ${poems.length ? `<h3>诗词（${poems.length}）</h3>` + poems.slice(0, 6).map(p => `<a class="sr-item" href="${toolUrl("poems.html")}">「${p.line}」— ${p.src}</a>`).join("") : ""}
        <div class="report-sec"><h3>站内文章</h3>
          <p>在内容库中搜索「${kw}」：<a style="color:var(--red);" href="${cmsBase}${encodeURIComponent(kw)}" rel="nofollow">点此搜索文章 »</a></p>
        </div>
        ${!chars.length && !poems.length && !surnames.length ? "" : ""}`;
    };
    input.addEventListener("input", run);
    const kwParam = new URLSearchParams(location.search).get("kw");
    if (kwParam) { input.value = kwParam; run(); }
  }

  /* ---------- 公司起名 ---------- */
  function initCompany() {
    const form = $("#form-company");
    if (!form) return;
    const box = $("#company-result");
    form.addEventListener("submit", e => {
      e.preventDefault();
      const ind = $("#cp-industry").value;
      const core = $("#cp-core").value.trim();
      const legalName = $("#cp-legal").value.trim();
      const birthStr = $("#cp-birth").value;
      let legal = null;
      if (birthStr) {
        const [y, m, d] = birthStr.split("-").map(Number);
        legal = { name: legalName, birth: { y, m, d, h: 12 } };
      }
      const res = window.HM_ENGINE.generateCompany(ind, core, 9, legal);
      box.classList.add("on");
      box.innerHTML = `<h3>推荐公司名称（${window.HM_DATA.INDUSTRY[ind].name}${legalFavorText(legal)}）</h3>` + res.map((r, i) => `
        <div class="name-card">
          <div class="name-main"><span class="name-rank">${i + 1}</span><b class="name-text">${r.name}</b>
          <span class="name-score s${r.score >= 90 ? "hi" : r.score >= 75 ? "mid" : "low"}">${r.score}分</span></div>
          <div class="name-sub">${r.reason}</div>
        </div>`).join("");
    });
  }
  function legalFavorText(legal) {
    if (!legal || !legal.birth) return "";
    try {
      const b = window.HM_ENGINE.bazi(legal.birth);
      return ` · 法人八字喜${b.favorable}`;
    } catch (e) { return ""; }
  }

  /* ---------- 字形块：米字格 SVG（模拟字形图，程序化生成） ---------- */
  function glyphSvg(ch, size) {
    size = size || 200;
    const c = size / 2, pad = size * 0.06;
    return `<svg viewBox="0 0 ${size} ${size}" width="${size}" height="${size}" role="img" aria-label="${ch}字形">
      <rect x="2" y="2" width="${size-4}" height="${size-4}" rx="6" fill="#fffdf8" stroke="#f0d9d0" stroke-width="2"/>
      <g stroke="#e9c9c0" stroke-width="1" stroke-dasharray="4 4">
        <line x1="${pad}" y1="${c}" x2="${size-pad}" y2="${c}"/>
        <line x1="${c}" y1="${pad}" x2="${c}" y2="${size-pad}"/>
        <line x1="${pad}" y1="${pad}" x2="${size-pad}" y2="${size-pad}"/>
        <line x1="${size-pad}" y1="${pad}" x2="${pad}" y2="${size-pad}"/>
      </g>
      <text x="${c}" y="${c + size*0.06}" text-anchor="middle" dominant-baseline="middle"
        font-family="STKaiti,KaiTi,STSong,SimSun,serif" font-size="${size*0.62}" fill="#2f2f2f">${ch}</text>
      <rect x="${size-46}" y="${size-46}" width="30" height="30" rx="5" fill="#c92009" opacity="0.9"/>
      <text x="${size-31}" y="${size-24}" text-anchor="middle" font-family="STKaiti,KaiTi,serif" font-size="16" fill="#fff">名</text>
    </svg>`;
  }

  /* ---------- 统一 SVG 图标集（替换 emoji） ---------- */
  const ICONS = {
    baby: '<svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="9" r="4.5" stroke="currentColor" stroke-width="1.8"/><path d="M4.5 20c1.5-3.4 4.2-5 7.5-5s6 1.6 7.5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
    bagua: '<svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.8"/><path d="M12 3.5v17M3.5 12h17" stroke="currentColor" stroke-width="1.3"/><path d="M7.5 7.5l9 9M16.5 7.5l-9 9" stroke="currentColor" stroke-width="1.3"/></svg>',
    poem: '<svg viewBox="0 0 24 24" fill="none"><path d="M5 4h9a3 3 0 013 3v13H8a3 3 0 01-3-3z" stroke="currentColor" stroke-width="1.8"/><path d="M8.5 8.5h5M8.5 12h5" stroke="currentColor" stroke-width="1.6"/></svg>',
    person: '<svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="3.6" stroke="currentColor" stroke-width="1.8"/><path d="M5 20c1.4-3.2 3.9-4.8 7-4.8s5.6 1.6 7 4.8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
    company: '<svg viewBox="0 0 24 24" fill="none"><rect x="4" y="3" width="16" height="18" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M8 7h3M8 11h3M8 15h3M14 7h2M14 11h2M14 15h2" stroke="currentColor" stroke-width="1.6"/></svg>',
    zhouyi: '<svg viewBox="0 0 24 24" fill="none"><path d="M12 3l8 5-3 13H7L4 8z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M9.5 12h5" stroke="currentColor" stroke-width="1.6"/></svg>',
    star: '<svg viewBox="0 0 24 24" fill="none"><path d="M12 4l2.5 5.2 5.5.8-4 3.9 1 5.6L12 16.8 7 19.5l1-5.6-4-3.9 5.5-.8z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>',
    heart: '<svg viewBox="0 0 24 24" fill="none"><path d="M12 20s-7-4.6-7-9.5A4 4 0 0112 8a4 4 0 017 2.5C19 15.4 12 20 12 20z" stroke="currentColor" stroke-width="1.8"/></svg>',
    search: '<svg viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="6.5" stroke="currentColor" stroke-width="2"/><path d="M20 20l-4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
    menu: '<svg viewBox="0 0 24 24" fill="none"><path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
    dict: '<svg viewBox="0 0 24 24" fill="none"><path d="M4 5h6a2 2 0 012 2v12a2 2 0 00-2-2H4z" stroke="currentColor" stroke-width="1.8"/><path d="M20 5h-6a2 2 0 00-2 2v12a2 2 0 012-2h6z" stroke="currentColor" stroke-width="1.8"/></svg>',
    surname: '<svg viewBox="0 0 24 24" fill="none"><path d="M4 6h16M4 12h10M4 18h13" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/></svg>',
    pair: '<svg viewBox="0 0 24 24" fill="none"><circle cx="8" cy="10" r="3.2" stroke="currentColor" stroke-width="1.8"/><circle cx="16" cy="10" r="3.2" stroke="currentColor" stroke-width="1.8"/><path d="M3 19c1.1-2.6 2.9-4 5-4s3.9 1.4 5 4M11 19c1.1-2.6 2.9-4 5-4s3.9 1.4 5 4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>'
  };
  function iconHtml(key) { return ICONS[key] || ICONS.star; }
  function fillIcons(root) {
    (root || document).querySelectorAll("[data-ic]").forEach(el => {
      if (!el.innerHTML.trim()) el.innerHTML = iconHtml(el.dataset.ic);
    });
  }

  /* ---------- 水墨横幅按页切换 + 维度卡图标 ---------- */
  const DIM_ICONS = {
    "国学": '<svg viewBox="0 0 24 24" fill="none"><path d="M4 5h7a2 2 0 012 2v12a2 2 0 00-2-2H4z" stroke="currentColor" stroke-width="1.8"/><path d="M20 5h-7a2 2 0 00-2 2v12a2 2 0 012-2h7z" stroke="currentColor" stroke-width="1.8"/></svg>',
    "音形义": '<svg viewBox="0 0 24 24" fill="none"><path d="M9 18V6l10-2v12" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><circle cx="6" cy="18" r="3" stroke="currentColor" stroke-width="1.8"/><circle cx="16" cy="16" r="3" stroke="currentColor" stroke-width="1.8"/></svg>',
    "期望": '<svg viewBox="0 0 24 24" fill="none"><path d="M12 20s-7-4.6-7-9.5A4 4 0 0112 8a4 4 0 017 2.5C19 15.4 12 20 12 20z" stroke="currentColor" stroke-width="1.8"/></svg>',
    "大数据": '<svg viewBox="0 0 24 24" fill="none"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
    "诗词": '<svg viewBox="0 0 24 24" fill="none"><path d="M5 4h9a3 3 0 013 3v13H8a3 3 0 01-3-3z" stroke="currentColor" stroke-width="1.8"/><path d="M8 8h6M8 12h6" stroke="currentColor" stroke-width="1.6"/></svg>',
    "生肖": '<svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.8"/><path d="M8 10h.01M16 10h.01M9 15c1.8 1.2 4.2 1.2 6 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
    "五行": '<svg viewBox="0 0 24 24" fill="none"><path d="M12 3l8 5-3 9H7L4 8z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>',
    "数理": '<svg viewBox="0 0 24 24" fill="none"><rect x="4" y="4" width="16" height="16" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M8 12h8M12 8v8" stroke="currentColor" stroke-width="1.6"/></svg>',
    "声调": '<svg viewBox="0 0 24 24" fill="none"><path d="M3 12h4l3-7 4 14 3-7h4" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" stroke-linecap="round"/></svg>',
    "出处": '<svg viewBox="0 0 24 24" fill="none"><path d="M6 3h9l4 4v14H6z" stroke="currentColor" stroke-width="1.8"/><path d="M15 3v4h4" stroke="currentColor" stroke-width="1.8"/></svg>',
    "八字": '<svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="7" stroke="currentColor" stroke-width="1.8"/><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.4"/></svg>',
    "校验": '<svg viewBox="0 0 24 24" fill="none"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>'
  };
  function pickIcon(title) {
    for (const k in DIM_ICONS) if (title.indexOf(k) >= 0) return DIM_ICONS[k];
    return DIM_ICONS["五行"];
  }
  function initBanner() {
    const f = (location.pathname.split("/").pop() || "index.html").replace(/\.html$/, "");
    const map = {
      index: "default", baobao: "baobao", bazi: "bazi", shici: "shici",
      zhouyi: "zhouyi", gongsi: "gongsi", score: "ceshi", xingmingceshi: "ceshi",
      nanhai: "boy", nvhai: "girl", char: "name", mingzi: "name",
      surname: "surname", search: "search", poems: "shici", match: "ceshi", gaiming: "baobao"
    };
    const key = map[f] || "default";
    const base = TPL ? TPL + "img/" : "assets/img/";
    $$(".ink-banner").forEach(el => {
      el.style.backgroundImage = `url(${base}banner-${key}.svg)`;
    });
    // 维度卡图标
    $$(".dim-item").forEach(item => {
      const ic = item.querySelector(".dim-ic");
      const b = item.querySelector("b");
      if (ic && b && !ic.innerHTML.trim()) ic.innerHTML = pickIcon(b.textContent || "");
    });
  }

  function initMonthLabels() {
    const now = new Date();
    const label = `${now.getFullYear()}年${now.getMonth() + 1}月`;
    $$(".js-month").forEach(el => { el.textContent = label; });
  }

  function initToolCrumb() {
    const f = (location.pathname.split("/").pop() || "index.html").replace(/\.html$/, "");
    const map = {
      naming: "宝宝起名", score: "姓名测试打分", bazi: "八字起名", shici: "诗词起名",
      zhouyi: "周易起名", gongsi: "公司起名", gaiming: "成人改名", match: "姓名配对",
      kxzd: "康熙字典", baijiaxing: "百家姓", poems: "诗词起名库", search: "统一搜索",
      nanhai: "男孩起名", nvhai: "女孩起名", char: "汉字详情", mingzi: "名字详情"
    };
    const nm = map[f];
    if (!nm || document.querySelector(".crumb")) return;
    insertCrumb([{ name: nm }]);
  }

  document.addEventListener("DOMContentLoaded", () => {
    patchLinks(document);
    fixLocalLinks(document);
    stripSelfLinks(document);
    injectCanonical();
    injectJsonLd();
    initContentAutoLinks();
    fillIcons(document);
    document.addEventListener("click", e => {
      const a = e.target.closest && e.target.closest("a[href]");
      if (!a) return;
      const h = a.getAttribute("href");
      if (h) { const fixed = withSc(h); if (fixed !== h) a.setAttribute("href", fixed); }
    }, true);
    document.addEventListener("submit", e => {
      if (!HM_SC) return;
      const form = e.target;
      if ((form.method || "get").toLowerCase() === "get" && !form.querySelector('input[name="sharecode"]')) {
        const i = document.createElement("input");
        i.type = "hidden"; i.name = "sharecode"; i.value = HM_SC;
        form.appendChild(i);
      }
    }, true);
    new MutationObserver(muts => muts.forEach(mu =>
      mu.addedNodes.forEach(n => { if (n.nodeType === 1) { patchLinks(n); fixLocalLinks(n); stripSelfLinks(n); initContentAutoLinks(n); } })
    )).observe(document.documentElement, { childList: true, subtree: true });
    bindGenderChips(document);
    renderToolChrome();
    favInit();
    favRefresh();
    if (window._HM_HOME) window._HM_HOME();
    initNaming();
    initPaipan();
    initScore();
    initMatch();
    initKxzd();
    initChar();
    initMingzi();
    initBaijiaxing();
    initPoems();
    initCompany();
    initNamingForms();
    initUnifiedSearch();
    initMonthLabels();
    initToolCrumb();
    initSurname();
    initMobileBar();
    injectToolArticles();
    initBanner();
    injectToolArticles();
    initContentAutoLinks(); // 工具内容渲染完成后补一次全页扫描（JS 动态区块）
    fillIcons(document);
    const path = location.pathname.split("/").pop() || "index.html";
    $$("#site-header .nav a").forEach(a => { if (a.getAttribute("href") === path) a.classList.add("on"); });
  });
})();

/* ================= 出生地选择弹窗（省 / 市两级联动） =================
   将「出生地 / 出生城市」文本框改为只读触发 + 弹窗选择：
   - 手机端（≤640px）底部抽屉，省份 → 城市逐级下钻，面包屑可返回
   - 桌面端居中弹窗，左省右市双栏
   - 选中后写回原 input 的 value，表单提交逻辑无需改动
=================================================================== */
(function () {
  if (window.__PLACE_PICKER__) return;
  window.__PLACE_PICKER__ = 1;

  var PLACES = {
    "北京市": ["北京市"],
    "天津市": ["天津市"],
    "上海市": ["上海市"],
    "重庆市": ["重庆市"],
    "河北省": ["石家庄", "唐山", "秦皇岛", "邯郸", "邢台", "保定", "张家口", "承德", "沧州", "廊坊", "衡水"],
    "山西省": ["太原", "大同", "阳泉", "长治", "晋城", "朔州", "晋中", "运城", "忻州", "临汾", "吕梁"],
    "内蒙古自治区": ["呼和浩特", "包头", "乌海", "赤峰", "通辽", "鄂尔多斯", "呼伦贝尔", "巴彦淖尔", "乌兰察布", "兴安盟", "锡林郭勒盟", "阿拉善盟"],
    "辽宁省": ["沈阳", "大连", "鞍山", "抚顺", "本溪", "丹东", "锦州", "营口", "阜新", "辽阳", "盘锦", "铁岭", "朝阳", "葫芦岛"],
    "吉林省": ["长春", "吉林", "四平", "辽源", "通化", "白山", "松原", "白城", "延边"],
    "黑龙江省": ["哈尔滨", "齐齐哈尔", "鸡西", "鹤岗", "双鸭山", "大庆", "伊春", "佳木斯", "七台河", "牡丹江", "黑河", "绥化", "大兴安岭"],
    "江苏省": ["南京", "无锡", "徐州", "常州", "苏州", "南通", "连云港", "淮安", "盐城", "扬州", "镇江", "泰州", "宿迁"],
    "浙江省": ["杭州", "宁波", "温州", "嘉兴", "湖州", "绍兴", "金华", "衢州", "舟山", "台州", "丽水"],
    "安徽省": ["合肥", "芜湖", "蚌埠", "淮南", "马鞍山", "淮北", "铜陵", "安庆", "黄山", "滁州", "阜阳", "宿州", "六安", "亳州", "池州", "宣城"],
    "福建省": ["福州", "厦门", "莆田", "三明", "泉州", "漳州", "南平", "龙岩", "宁德"],
    "江西省": ["南昌", "景德镇", "萍乡", "九江", "新余", "鹰潭", "赣州", "吉安", "宜春", "抚州", "上饶"],
    "山东省": ["济南", "青岛", "淄博", "枣庄", "东营", "烟台", "潍坊", "济宁", "泰安", "威海", "日照", "临沂", "德州", "聊城", "滨州", "菏泽"],
    "河南省": ["郑州", "开封", "洛阳", "平顶山", "安阳", "鹤壁", "新乡", "焦作", "濮阳", "许昌", "漯河", "三门峡", "南阳", "商丘", "信阳", "周口", "驻马店"],
    "湖北省": ["武汉", "黄石", "十堰", "宜昌", "襄阳", "鄂州", "荆门", "孝感", "荆州", "黄冈", "咸宁", "随州", "恩施"],
    "湖南省": ["长沙", "株洲", "湘潭", "衡阳", "邵阳", "岳阳", "常德", "张家界", "益阳", "郴州", "永州", "怀化", "娄底", "湘西"],
    "广东省": ["广州", "韶关", "深圳", "珠海", "汕头", "佛山", "江门", "湛江", "茂名", "肇庆", "惠州", "梅州", "汕尾", "河源", "阳江", "清远", "东莞", "中山", "潮州", "揭阳", "云浮"],
    "广西壮族自治区": ["南宁", "柳州", "桂林", "梧州", "北海", "防城港", "钦州", "贵港", "玉林", "百色", "贺州", "河池", "来宾", "崇左"],
    "海南省": ["海口", "三亚", "三沙", "儋州", "五指山", "琼海", "文昌", "万宁", "东方"],
    "四川省": ["成都", "自贡", "攀枝花", "泸州", "德阳", "绵阳", "广元", "遂宁", "内江", "乐山", "南充", "眉山", "宜宾", "广安", "达州", "雅安", "巴中", "资阳", "阿坝", "甘孜", "凉山"],
    "贵州省": ["贵阳", "六盘水", "遵义", "安顺", "毕节", "铜仁", "黔西南", "黔东南", "黔南"],
    "云南省": ["昆明", "曲靖", "玉溪", "保山", "昭通", "丽江", "普洱", "临沧", "楚雄", "红河", "文山", "西双版纳", "大理", "德宏", "怒江", "迪庆"],
    "西藏自治区": ["拉萨", "日喀则", "昌都", "林芝", "山南", "那曲", "阿里"],
    "陕西省": ["西安", "铜川", "宝鸡", "咸阳", "渭南", "延安", "汉中", "榆林", "安康", "商洛"],
    "甘肃省": ["兰州", "嘉峪关", "金昌", "白银", "天水", "武威", "张掖", "酒泉", "庆阳", "平凉", "定西", "陇南", "临夏", "甘南"],
    "青海省": ["西宁", "海东", "海北", "黄南", "海南", "果洛", "玉树", "海西"],
    "宁夏回族自治区": ["银川", "石嘴山", "吴忠", "固原", "中卫"],
    "新疆维吾尔自治区": ["乌鲁木齐", "克拉玛依", "吐鲁番", "哈密", "昌吉", "博尔塔拉", "巴音郭楞", "阿克苏", "克孜勒苏", "喀什", "和田", "伊犁", "塔城", "阿勒泰"],
    "中国香港": ["中国香港"],
    "中国澳门": ["中国澳门"],
    "中国台湾": ["台北市", "新北市", "桃园市", "台中市", "台南市", "高雄市", "基隆市", "新竹市", "嘉义市"]
  };
  /* 直接以省级名称作为结果（无下级市列表） */
  var DIRECT = { "北京市": 1, "天津市": 1, "上海市": 1, "重庆市": 1, "中国香港": 1, "中国澳门": 1 };

  var cur = null, curProv = null, mask = null, colP = null, colC = null, crumb = null;

  function h(tag, cls, html) {
    var e = document.createElement(tag);
    if (cls) e.className = cls;
    if (html != null) e.innerHTML = html;
    return e;
  }
  function isMobile() { return window.matchMedia("(max-width:640px)").matches; }

  function build() {
    mask = h("div", "pp-mask");
    var panel = h("div", "pp-panel");
    var head = h("div", "pp-head");
    var btnClose = h("button", "pp-x", "✕");
    var title = h("div", "pp-title", "选择出生地");
    var btnClear = h("button", "pp-clear", "清除");
    head.appendChild(btnClose); head.appendChild(title); head.appendChild(btnClear);
    crumb = h("div", "pp-crumb");
    var body = h("div", "pp-body");
    colP = h("div", "pp-col");
    colC = h("div", "pp-col");
    body.appendChild(colP); body.appendChild(colC);
    panel.appendChild(head); panel.appendChild(crumb); panel.appendChild(body);
    mask.appendChild(panel);
    document.body.appendChild(mask);

    mask.addEventListener("click", function (e) { if (e.target === mask) close(); });
    btnClose.onclick = close;
    btnClear.onclick = function () {
      if (cur) { cur.value = ""; fire(cur); }
      close();
    };
    window.addEventListener("resize", function () {
      if (mask && mask.classList.contains("on")) open(cur || null);
    });
  }

  function renderCrumb() {
    crumb.innerHTML = "";
    if (curProv && isMobile()) {
      var back = h("span", "pp-back", "‹ 返回");
      back.onclick = function () { curProv = null; renderProv(); syncCols(); renderCrumb(); };
      crumb.appendChild(back);
      crumb.appendChild(h("span", "pp-crumb-cur", curProv));
    } else {
      crumb.appendChild(h("span", null, "请选择省份" + (isMobile() ? "" : " / 城市")));
    }
  }

  function renderProv() {
    colP.innerHTML = "";
    var names = Object.keys(PLACES);
    for (var i = 0; i < names.length; i++) {
      (function (name) {
        var it = h("div", "pp-item" + (curProv === name ? " on" : ""), name +
          (DIRECT[name] ? "" : '<i class="pp-arrow">›</i>'));
        it.onclick = function () { pickProv(name); };
        colP.appendChild(it);
      })(names[i]);
    }
  }

  function renderCities(prov) {
    colC.innerHTML = "";
    var list = PLACES[prov] || [];
    for (var i = 0; i < list.length; i++) {
      (function (city) {
        var it = h("div", "pp-item", city);
        it.onclick = function () { setVal(prov + " " + city); };
        colC.appendChild(it);
      })(list[i]);
    }
  }

  function pickProv(name) {
    if (DIRECT[name]) { setVal(name); return; }
    curProv = name;
    renderProv();
    renderCities(name);
    syncCols();
    renderCrumb();
  }

  function syncCols() {
    if (isMobile()) {
      colP.classList.toggle("on", !curProv);
      colC.classList.toggle("on", !!curProv);
    } else {
      colP.classList.add("on");
      colC.classList.add("on");
    }
  }

  function setVal(v) {
    if (cur) { cur.value = v; fire(cur); }
    close();
  }

  function fire(inp) {
    try {
      inp.dispatchEvent(new Event("input", { bubbles: true }));
      inp.dispatchEvent(new Event("change", { bubbles: true }));
    } catch (e) {}
  }

  function open(inp) {
    if (!mask) build();
    cur = inp; curProv = null;
    renderProv();
    colC.innerHTML = '<div class="pp-hint">' + (isMobile() ? "请先选择省份" : "← 请先选择省份") + "</div>";
    syncCols();
    renderCrumb();
    document.body.classList.add("pp-lock");
    mask.classList.add("on");
  }

  function close() {
    if (mask) mask.classList.remove("on");
    document.body.classList.remove("pp-lock");
    cur = null;
  }

  function bind() {
    var inputs = document.querySelectorAll('input[type="text"], input:not([type])');
    for (var i = 0; i < inputs.length; i++) {
      (function (inp) {
        if (inp.__ppBound) return;
        var ph = inp.getAttribute("placeholder") || "";
        if (ph.indexOf("出生地") < 0 && ph.indexOf("出生城市") < 0) return;
        inp.__ppBound = 1;
        inp.readOnly = true;
        inp.classList.add("pp-input");
        inp.addEventListener("click", function () { open(inp); });
        inp.addEventListener("keydown", function (e) { e.preventDefault(); });
      })(inputs[i]);
    }
  }

  function init() { if (!mask) build(); bind(); }
  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", init);
  else init();
})();
