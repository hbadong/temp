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

  /* ---------- 真实使用统计（后端 stats-api.php） ---------- */
  function statsTrack(k, n) {
    try {
      const base = window._LE ? window._LE.uri : "";
      fetch(withSc(base + "stats-api.php?action=track&k=" + encodeURIComponent(k) + "&n=" + encodeURIComponent(n || "")), { keepalive: true }).catch(() => {});
    } catch (e) {}
  }
  /* 真实统计读取（失败静默，回退演示数据） */
  function statsGet(cb) {
    try {
      const base = window._LE ? window._LE.uri : "";
      fetch(withSc(base + "stats-api.php?action=get")).then(r => r.json()).then(j => cb(j)).catch(() => cb(null));
    } catch (e) { cb(null); }
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
      const navItems = [
        ["naming.html", "宝宝起名"], ["bazi.html", "八字起名"], ["poems.html", "诗词起名"],
        ["gaiming.html", "成人改名"], ["score.html", "姓名测试"], ["gongsi.html", "公司起名"],
        ["zhouyi.html", "周易起名"], ["kxzd.html", "康熙字典"], ["baijiaxing.html", "百家姓"]
      ];
      const hotChars = (window.HM_DATA.CHARS || []).filter(x => x.g !== 0).slice(0, 2);
      const hotNames = ["梓晴", "宇轩"];
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
              <a href="${toolUrl("zhouyi.html")}">周易起名</a><a href="${toolUrl("score.html")}">姓名测试</a><a href="${toolUrl("gongsi.html")}">公司起名</a>
              <a href="${toolUrl("kxzd.html")}">康熙字典</a><a href="${toolUrl("baijiaxing.html")}">百家姓</a><a href="${toolUrl("match.html")}">姓名配对</a>
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
      (cats[k] || []).forEach(a => arts.push({ t: a.t, cat: k }));
    });
    const rank = arts.slice(0, 10).map((a, i) =>
      `<li><i>${i + 1}</i><a href="${toolUrl("score.html")}">${a.t}</a></li>`).join("");
    const hotChars = (window.HM_DATA ? window.HM_DATA.CHARS.slice(0, 12) : []).map(x =>
      `<a href="${toolUrl("char.html")}?c=${encodeURIComponent(x.c)}" title="${x.m}">${x.c}</a>`).join("");
    const tools = [["naming.html", "宝宝起名"], ["score.html", "姓名打分"], ["bazi.html", "八字起名"],
      ["poems.html", "诗词起名"], ["zhouyi.html", "周易起名"], ["gongsi.html", "公司起名"],
      ["kxzd.html", "康熙字典"], ["baijiaxing.html", "百家姓"], ["match.html", "姓名配对"]]
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
    // 宝宝起名（首页）
    const bf = $("#form-qmh");
    if (bf) bf.addEventListener("submit", e => {
      e.preventDefault();
      const s = checkSurnameInput($("#xingh")); if (!s) return;
      const g = genderOf("#form-qmh");
      const dt = $("#dateh").value;
      if (!dt) return alert("请选择出生日期");
      const h = hourVal("#hourh");
      const place = $("#placeh").value.trim() || "未填写";
      location.href = withSc(`${toolUrl("naming.html")}?surname=${encodeURIComponent(s)}&gender=${g}&date=${dt}&hour=${h ?? "none"}&place=${encodeURIComponent(place)}`);
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
    else data = window.HM_ENGINE.generateByBazi(surname, gender, birth, 12, { wx1: q.get("wx1"), wx2: q.get("wx2"), avoid: q.get("avoid") || "", fixed: q.get("fixed") || "", wish: q.get("wish") || "" });

    const modeName = mode === "poem" ? "诗词" : mode === "zhouyi" ? "周易" : "八字";
    $("#nr-title").textContent = `${surname}姓宝宝 · ${modeName}起名结果`;
    $("#nr-meta").textContent = `出生：${date}${hourRaw && hourRaw !== "none" ? " · 时辰已计入" : ""} · 地区：${place} · 生肖：${data.bazi ? data.bazi.zodiac : "—"} · 共 ${data.names.length} 个推荐`;
    document.title = `${surname}姓${modeName}起名结果 - 好名网`;
    statsTrack("naming", surname + (data.names[0] ? data.names[0].name.replace(surname, "") : ""));

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
    box.innerHTML = orderBar + data.names.map((n, i) => {
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
        </div>
        <div class="name-sub">${n.reason}${n.wx ? ` · 五行：${n.wx}` : ""}${n.line ? `<blockquote>「${n.line}」— ${n.src}</blockquote>` : ""}</div>
      </div>`;
    }).join("") + (unlocked ? "" : `
      <div class="unlock-banner">
        <b>已展示 ${Math.min(3, data.names.length)} / ${data.names.length} 个方案</b>
        <p>开通后解锁全部名字的数理解读、五行喜忌、同音候选与收藏清单，一次付费永久有效。</p>
        <button class="unlock-btn big" data-unlock>立即解锁完整方案</button>
      </div>`);
    const openBtn = document.getElementById("unlock-open");
    if (openBtn) openBtn.addEventListener("click", openUnlockModal);
    box.querySelectorAll("[data-unlock]").forEach(b => b.addEventListener("click", openUnlockModal));

    [["switch-poem", "poem"], ["switch-zhouyi", "zhouyi"], ["switch-bazi", "bazi"]].forEach(([id, m]) => {
      const el = document.getElementById(id);
      if (!el) return;
      const p = new URLSearchParams(location.search);
      p.set("mode", m);
      el.href = `${toolUrl("naming.html")}?${p.toString()}`;
      if (mode === m) el.style.display = "none";
    });
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
    const place = q.get("place") || "";
    const inp = $("#sc-name"); if (inp) inp.value = name;
    if (!name) { box.innerHTML = '<p class="empty">请输入完整姓名后查询。</p>'; return; }
    const wg = window.HM_ENGINE.wuge(name);
    if (!wg) { box.innerHTML = '<p class="empty">姓名至少需要两个字。</p>'; return; }
    document.title = `${name}的姓名测试打分 - 好名网`;
    statsTrack("score", name);
    const rows = [["天格", wg.tian], ["人格", wg.ren], ["地格", wg.di], ["外格", wg.wai], ["总格", wg.zong]];
    let extra = "";
    // 生肖板块
    if (date) {
      const [y, m, d] = date.split("-").map(Number);
      const b = window.HM_ENGINE.bazi({ y, m, d: d, h: 12 });
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
    if (place) extra += `<div class="report-sec"><h3>出生地</h3><p>${place}（当前版本仅作记录展示，未参与计算）。</p></div>`;
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
            <a class="btn-ghost" href="${toolUrl("naming.html")}?surname=李&gender=1&date=2026-01-01">用该字起名</a>
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
        <div class="bigchar" style="font-size:44px;letter-spacing:10px;">${name}</div>
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
    let cat = "all";
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
        let url = `${toolUrl("naming.html")}?surname=${encodeURIComponent(s)}&gender=${g}&date=${dt}&hour=${h ?? "none"}&place=${encodeURIComponent(place || "未填写")}`;
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
      if (dt) url += `&date=${dt}&hour=${h ?? "none"}`;
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
      location.href = withSc(`${toolUrl("naming.html")}?surname=${encodeURIComponent(s)}&gender=${genderOf("#form-qmgm")}&date=${$("#dategm").value || "2026-01-01"}&hour=${hourVal("#hourgm") ?? "none"}&mode=poem`);
    });
    const nan = $("#form-qmnan");
    if (nan) nan.addEventListener("submit", e => {
      e.preventDefault();
      const s = checkSurnameInput($("#xingnan")); if (!s) return;
      location.href = withSc(`${toolUrl("naming.html")}?surname=${encodeURIComponent(s)}&gender=1&date=${$("#datenan").value || "2026-01-01"}&hour=${hourVal("#hournan") ?? "none"}`);
    });
    const nv = $("#form-qmnv");
    if (nv) nv.addEventListener("submit", e => {
      e.preventDefault();
      const s = checkSurnameInput($("#xingnv")); if (!s) return;
      location.href = withSc(`${toolUrl("naming.html")}?surname=${encodeURIComponent(s)}&gender=2&date=${$("#datenv").value || "2026-01-01"}&hour=${hourVal("#hournv") ?? "none"}`);
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
      mu.addedNodes.forEach(n => { if (n.nodeType === 1) patchLinks(n); })
    )).observe(document.documentElement, { childList: true, subtree: true });
    bindGenderChips(document);
    renderToolChrome();
    if (window._HM_HOME) window._HM_HOME();
    initNaming();
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
    initBanner();
    fillIcons(document);
    const path = location.pathname.split("/").pop() || "index.html";
    $$("#site-header .nav a").forEach(a => { if (a.getAttribute("href") === path) a.classList.add("on"); });
  });
})();
