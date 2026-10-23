/* ============================================================
   金价查询网站系统 — 行情渲染脚本
   复刻原站 loadPrice() 数据流：拉取行情 → 填充表格 → 60s 轮询
   ============================================================ */
(function () {
  "use strict";

  var timer = null;
  var APP_CFG = { refresh: { defaultIntervalSec: 60, options: [30, 60, 120] }, theme: { default: "light", allowUserToggle: true } };
  var theme = "light";
  var LS_POLL = "gj_poll_sec";
  var LS_THEME = "gj_theme";
  function getPollMs() {
    var v = parseInt(localStorage.getItem(LS_POLL), 10);
    if (APP_CFG.refresh.options.indexOf(v) >= 0) return v * 1000;
    return (APP_CFG.refresh.defaultIntervalSec || 60) * 1000;
  }

  /* ---------- 工具 ---------- */
  function $(id) { return document.getElementById(id); }
  function fmt(n, d) {
    if (n === null || n === undefined || !isFinite(n)) return "--";
    return Number(n).toFixed(d === undefined ? 2 : d);
  }
  function pad(n) { return String(n).padStart(2, "0"); }
  function nowTime() {
    var d = new Date();
    return pad(d.getHours()) + ":" + pad(d.getMinutes()) + ":" + pad(d.getSeconds());
  }
  function todayCN() {
    var d = new Date();
    return d.getFullYear() + "-" + pad(d.getMonth() + 1) + "-" + pad(d.getDate());
  }
  // 涨红跌绿（A 股惯例）
  function cls(v) { return v > 0 ? "up" : v < 0 ? "down" : "flat"; }
  function arrow(v) { return v > 0 ? "↑" : v < 0 ? "↓" : ""; }
  function sign(v, d) { return (v > 0 ? "+" : "") + fmt(v, d); }

  /* ---------- 行情行填充（复刻原站 setRow 列结构：最新/涨跌/幅度/最高/最低/时间） ---------- */
  function setRow(id, q) {
    var el = $(id);
    if (!el || !q) return;
    var td = el.getElementsByTagName("td");
    if (td.length < 6) return;
    td[1].textContent = fmt(q.price);
    td[1].className = cls(q.change);
    td[2].textContent = sign(q.change) + arrow(q.change);
    td[2].className = cls(q.change);
    td[3].textContent = sign(q.changePct) + "%";
    td[3].className = cls(q.change);
    td[4].textContent = fmt(q.high);
    td[5].textContent = fmt(q.low);
    if (td[6]) td[6].textContent = q.time || nowTime();
  }

  /* ---------- 派生行（无涨跌概念的静态派生价） ---------- */
  function setCell(id, idx, text, colorCls) {
    var el = $(id);
    if (!el) return;
    // 兼容两种挂载：id 在 <tr> 上（按列索引取 td）或 id 直接在 <td> 上（直接写该单元格）
    var target = el.tagName === "TD" ? el : el.getElementsByTagName("td")[idx];
    if (!target) return;
    target.textContent = text;
    if (colorCls) target.className = colorCls;
  }

  /* ---------- 数据状态 ---------- */
  function setStatus(state, extra) {
    var el = $("dataStatus");
    if (!el) return;
    var dot = el.querySelector(".dot");
    if (dot) dot.className = "dot " + (state === "loading" ? "loading" : state === "err" ? "err" : state === "stale" ? "stale" : "");
    var txt = el.querySelector(".st-txt");
    if (!txt) return;
    if (state === "loading") txt.textContent = "刷新中…";
    else if (state === "err") txt.textContent = "行情获取失败，请稍后重试";
    else if (state === "stale") txt.textContent = "行情源限频，显示上次数据 · " + (extra || nowTime());
    else txt.textContent = "数据正常 · 更新于 " + (extra || nowTime());
  }

  /* ---------- 首页三张行情表 ---------- */
  function renderHome(d) {
    // 国际贵金属（复刻原站表1：金/铂/银/钯）
    setRow("jiage1", d.intl.xau);
    setRow("jiage2", d.intl.xpt);
    setRow("jiage3", d.intl.xag);
    setRow("bajin", d.intl.xpd);
    // 国内行情（复刻原站表2 setRow 偏移逻辑）
    setRow("jiage4", d.dom.autd);                       // 国内金价 = 黄金T+D
    var agQ = d.dom.agtd ? {
      price: d.dom.agtd.price / 1000, high: d.dom.agtd.high / 1000, low: d.dom.agtd.low / 1000,
      change: (d.dom.agtd.price - (d.dom.agtd.open || d.dom.agtd.prevClose || d.dom.agtd.price)) / 1000,
      time: d.dom.agtd.time
    } : null;
    if (agQ) agQ.changePct = d.dom.agtd.changePct;
    setRow("jiage5", agQ);                              // 国内银价 = 白银T+D/1000
    setRow("jiage7", d.dom.autd); setCell("jiage7", 1, fmt(d.derived.touziJintiao));  // 投资金条 +16
    setRow("jiage6", d.dom.autd); setCell("jiage6", 1, fmt(d.derived.huishouGold));   // 黄金回收 -15
    // 静态派生回收行（原站为服务端静态值，本站按校准公式实时推算）
    setCell("hs_pt", 1, d.derived.huishouPt + " 元/克");
    setCell("hs_18k", 1, d.derived.huishou18k + " 元/克");
    setCell("hs_pd", 1, d.derived.huishouPd + " 元/克");
    setCell("hs_ag", 1, fmt(d.derived.huishouSilver) + " 元/克");
    document.querySelectorAll(".daTime").forEach(function (e) {
      if (!e.dataset.locked) e.textContent = nowTime();
    });

    // 黄金实时行情动态（时间轴文案由实时数据生成，复刻原站三条快讯结构）
    renderNews(d);
  }

  /* ---------- 时间轴快讯（复刻原站首页"黄金实时行情动态"三条结构） ---------- */
  function renderNews(d) {
    var box = $("newsFeed");
    if (!box) return;
    var xau = d.intl.xau, xag = d.intl.xag, xpt = d.intl.xpt, xpd = d.intl.xpd;
    if (!xau) return;
    var items = [
      {
        t: todayCN() + " " + nowTime().slice(0, 5),
        c: "今日国际金价实时报价：" + fmt(xau.price) + " 美元/盎司（" + sign(xau.changePct) + "%），" +
           "现货白银 " + fmt(xag && xag.price) + " 美元/盎司，现货铂金 " + fmt(xpt && xpt.price) + " 美元/盎司，" +
           "现货钯金 " + fmt(xpd && xpd.price) + " 美元/盎司。国内方面：黄金T+D实时价 " + fmt(d.dom.autd && d.dom.autd.price) +
           " 元/克，白银T+D " + fmt(d.derived.guoneiSilver) + " 元/克，投资金条 " + fmt(d.derived.touziJintiao) + " 元/克。",
      },
      {
        t: todayCN() + " " + nowTime().slice(0, 5),
        c: "今日黄金回收行情：Au9999实时金价 " + fmt(d.dom.autd && d.dom.autd.price) + " 元/克，黄金回收价格每克 " +
           fmt(d.derived.huishouGold) + " 元，铂金回收价格每克 " + d.derived.huishouPt + " 元，钯金回收价格每克 " +
           d.derived.huishouPd + " 元，18K金回收价格每克 " + d.derived.huishou18k + " 元，白银回收价格每克 " +
           fmt(d.derived.huishouSilver) + " 元。",
      },
      {
        t: todayCN() + " " + nowTime().slice(0, 5),
        c: "今日国内黄金零售价格行情：基础金价 " + fmt(d.dom.autd && d.dom.autd.price) + " 元/克，投资金条价格每克 " +
           fmt(d.derived.touziJintiao) + " 元，周大福、周大生、潮宏基、谢瑞麟今日金价 " + (d.brands[0] ? d.brands[0].gold : "--") +
           " 元，六福珠宝今日金价 " + (d.brands[2] ? d.brands[2].gold : "--") + " 元，周生生金价今日价格 " +
           (d.brands[1] ? d.brands[1].gold : "--") + " 元，中国黄金今日金价 " + (d.brands[10] ? d.brands[10].gold : "--") +
           " 元，老凤祥今日黄金价格 " + (d.brands[6] ? d.brands[6].gold : "--") + " 元，菜百今日金价 " +
           (d.brands[8] ? d.brands[8].gold : "--") + " 元。",
      },
    ];
    box.innerHTML = items.map(function (it) {
      return '<div class="singleNew"><div class="timeIcon"></div>' +
        '<div class="times"><span>' + it.t + '</span></div>' +
        '<div class="news-con"><div class="desc">' + it.c + '</div></div></div>';
    }).join("");
  }

  /* ---------- 品牌金店表（带锚点 id，复刻原站品牌页区块） ---------- */
  var BRAND_ANCHORS = ["zdf", "zss", "lf", "xrl", "jzz", "chj", "lfx", "lm", "cb", "zlf", "zghj", "zds"];
  function renderBrands(d) {
    var tbody = $("brandBody");
    if (!tbody) return;
    tbody.innerHTML = d.brands.map(function (b, i) {
      return '<tr class="' + (i % 2 ? "bg" : "") + '" id="' + (BRAND_ANCHORS[i] || "") + '">' +
        '<td valign="middle" align="center" class="tabtitle">' + b.name + '</td>' +
        '<td valign="middle" align="center">' + b.gold + '</td>' +
        '<td valign="middle" align="center">' + (b.pt !== null ? b.pt : "-") + '</td>' +
        '<td valign="middle" align="center">' + b.bar + '</td>' +
        '<td valign="middle" align="center">元/克</td>' +
        '<td valign="middle" align="center" class="daTime">' + todayCN() + '</td></tr>';
    }).join("");
  }

  /* ---------- 黄金回收页 ---------- */
  function renderRecycle(d) {
    setCell("hs1", 1, fmt(d.derived.huishouGold) + " 元/克");
    setCell("hs2", 1, fmt(d.derived.huishouSilver) + " 元/克");
    setCell("hs3", 1, d.derived.huishou18k + " 元/克");
    setCell("hs4", 1, fmt(d.dom.autd && d.dom.autd.price) + " 元/克");
    setCell("hs5", 1, d.derived.huishouPt + " 元/克");
    setCell("hs6", 1, d.derived.huishouPd + " 元/克");
    setCell("hs7", 1, fmt(d.derived.guoneiSilver) + " 元/克");
  }

  /* ---------- 国际金价页 ---------- */
  function renderIntl(d) {
    setRow("xauRow", d.intl.xau);
    setRow("xptRow", d.intl.xpt);
    setRow("xagRow", d.intl.xag);
    setRow("xpdRow", d.intl.xpd);
    // 美元/盎司 → 元/克 换算卡
    if (d.intl.xau && d.usdcny) {
      setCell("conv_usd", 1, fmt(d.intl.xau.price));
      setCell("conv_cny_g", 1, fmt((d.intl.xau.price / 31.1035) * d.usdcny));
      setCell("conv_rate", 1, fmt(d.usdcny, 4));
    }
  }

  /* ---------- 国内金价页（复刻原站 6 列表格：品种/今日价/销售价/最高/最低/日期） ---------- */
  function renderDom(d) {
    fillDomRow("au_cn", d.dom.autd, d.derived.auSales);
    fillDomRow("ag_cn", d.dom.agtd ? {
      price: d.dom.agtd.price / 1000, high: d.dom.agtd.high / 1000,
      low: d.dom.agtd.low / 1000, time: d.dom.agtd.time
    } : null, d.derived.guoneiSilver);
    fillDomRow("pt_cn", { price: d.derived.ptCn, high: d.derived.ptCnHigh, low: d.derived.ptCnLow, time: nowTime() }, d.derived.ptCn);
    fillDomRow("pd_cn", { price: d.derived.pdCn, high: d.derived.pdCnHigh, low: d.derived.pdCnLow, time: nowTime() }, d.derived.pdCn);
    // 页面信息卡
    setCell("bar_price", 1, fmt(d.derived.touziJintiao) + " 元/克");
    setCell("recycle_price", 1, fmt(d.derived.huishouGold) + " 元/克");
    setCell("ag_price", 1, fmt(d.derived.guoneiSilver) + " 元/克");
  }
  function fillDomRow(id, q, sales) {
    var el = $(id);
    if (!el || !q) return;
    var td = el.getElementsByTagName("td");
    if (td.length < 6) return;
    td[1].textContent = fmt(q.price);
    td[2].textContent = fmt(sales);
    td[3].textContent = fmt(q.high);
    td[4].textContent = fmt(q.low);
    td[5].textContent = todayCN();
  }

  /* ---------- 香港金价页 ---------- */
  function renderHK(d) {
    if (!d.hk) return;
    setCell("hk_gram", 1, fmt(d.hk.hkdPerGram));
    setCell("hk_tael", 1, d.hk.hkdPerTael.toLocaleString());
    setCell("hk_rate", 1, fmt(d.hk.rate, 4));
    var xau = d.intl.xau;
    if (xau) {
      setCell("hk_usd", 1, fmt(xau.price));
      setCell("hk_chg", 1, sign(xau.changePct) + "%", cls(xau.change));
    }
  }

  /* ---------- 回收历史图表（ECharts，跟随主题） ---------- */
  var recycleChartInst = null;
  function renderRecycleChart() {
    var dom = $("recycleChart");
    if (!dom || typeof echarts === "undefined") return;
    var dark = document.documentElement.getAttribute("data-theme") === "dark";
    var axisColor = dark ? "#9aa4b2" : "#666666";
    var gridColor = dark ? "rgba(255,255,255,.08)" : "#eeeeee";
    var titleColor = dark ? "#d7dee7" : "#000000";
    var tipBg = dark ? "rgba(23,29,38,.95)" : "#ffffff";
    fetch("/api/recycle-history")
      .then(function (r) { return r.json(); })
      .then(function (h) {
        var days = h.days || [];
        if (recycleChartInst) { try { recycleChartInst.dispose(); } catch (e) {} }
        var chart = echarts.init(dom);
        recycleChartInst = chart;
        chart.setOption({
          title: { text: "近7日回收价格走势（元/克）", x: "10px", textStyle: { fontSize: 14, fontWeight: "bolder", color: titleColor } },
          tooltip: { trigger: "axis", axisPointer: { type: "shadow" }, backgroundColor: tipBg, borderColor: gridColor },
          legend: { left: "right", data: ["黄金回收", "铂金回收", "钯金回收", "18K金回收"], textStyle: { fontSize: 12, color: axisColor } },
          grid: { left: "3%", right: "7%", bottom: "3%", containLabel: true },
          xAxis: {
            type: "category", boundaryGap: false,
            axisLabel: { interval: 0, rotate: -40, margin: 15, color: axisColor },
            axisLine: { lineStyle: { color: gridColor } },
            data: days.map(function (d) { return d.date; })
          },
          yAxis: { type: "value", scale: true, axisLabel: { color: axisColor }, splitLine: { lineStyle: { color: gridColor } } },
          series: [
            { name: "黄金回收", type: "line", smooth: true, data: days.map(function (d) { return d.huishouGold; }) },
            { name: "铂金回收", type: "line", smooth: true, data: days.map(function (d) { return d.huishouPt; }) },
            { name: "钯金回收", type: "line", smooth: true, data: days.map(function (d) { return d.huishouPd; }) },
            { name: "18K金回收", type: "line", smooth: true, data: days.map(function (d) { return d.huishou18k; }) },
          ],
        });
        window.addEventListener("resize", function () { chart.resize(); });
      })
      .catch(function () {});
  }

  /* ---------- 主循环 ---------- */
  function load() {
    setStatus("loading");
    fetch("/api/quotes", { cache: "no-store" })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (!d.ok) throw new Error(d.error || "bad payload");
        setStatus(d.stale ? "stale" : "ok", d.serverTime ? d.serverTime.slice(11, 19) : null);
        renderHome(d); renderBrands(d); renderRecycle(d);
        renderIntl(d); renderDom(d); renderHK(d);
      })
      .catch(function () { setStatus("err"); });
  }

  /* ---------- 移动端菜单 ---------- */
  function bindMenu() {
    var btn = document.querySelector(".side_menu_btn");
    var ul = document.querySelector(".header-hd .nav ul");
    if (btn && ul) btn.addEventListener("click", function () { ul.classList.toggle("open"); });
  }

  /* ---------- 配置加载（刷新间隔选项、默认主题） ---------- */
  function initConfig() {
    return fetch("/api/config", { cache: "no-store" })
      .then(function (r) { return r.json(); })
      .then(function (c) {
        if (c && c.refresh) APP_CFG.refresh = Object.assign({}, APP_CFG.refresh, c.refresh);
        if (c && c.theme) APP_CFG.theme = Object.assign({}, APP_CFG.theme, c.theme);
      })
      .catch(function () {});
  }

  /* ---------- 主题（暗色 / 亮色） ---------- */
  function applyTheme(t) {
    theme = t;
    document.documentElement.setAttribute("data-theme", t);
    try { localStorage.setItem(LS_THEME, t); } catch (e) {}
    var btn = document.querySelector(".theme-toggle");
    if (btn) btn.textContent = t === "dark" ? "☀ 亮色" : "🌙 暗色";
    if (window.GoldCharts && typeof GoldCharts.reinit === "function") {
      try { GoldCharts.reinit(); } catch (e) {}
    }
    renderRecycleChart();
  }
  function initTheme() {
    var urlTheme = null;
    try { urlTheme = new URLSearchParams(location.search).get("theme"); } catch (e) {}
    var saved = null;
    try { saved = localStorage.getItem(LS_THEME); } catch (e) {}
    var t = (urlTheme === "dark" || urlTheme === "light") ? urlTheme : (saved || APP_CFG.theme.default || "light");
    applyTheme(t);
  }

  /* ---------- 工具条（主题切换 + 刷新间隔，注入导航栏） ---------- */
  function injectTools() {
    var nav = document.querySelector(".header-hd .nav");
    if (!nav || document.querySelector(".nav-tools")) return;
    var box = document.createElement("div");
    box.className = "nav-tools";

    var themeBtn = document.createElement("button");
    themeBtn.className = "nav-tool theme-toggle";
    themeBtn.type = "button";
    themeBtn.textContent = theme === "dark" ? "☀ 亮色" : "🌙 暗色";
    if (APP_CFG.theme.allowUserToggle !== false) {
      themeBtn.addEventListener("click", function () {
        applyTheme(document.documentElement.getAttribute("data-theme") === "dark" ? "light" : "dark");
      });
    } else { themeBtn.style.display = "none"; }
    box.appendChild(themeBtn);

    var sel = document.createElement("select");
    sel.className = "nav-tool refresh-sel";
    sel.title = "自动刷新间隔";
    (APP_CFG.refresh.options || [60]).forEach(function (s) {
      var o = document.createElement("option");
      o.value = s; o.textContent = s + " 秒";
      if (s * 1000 === getPollMs()) o.selected = true;
      sel.appendChild(o);
    });
    sel.addEventListener("change", function () {
      try { localStorage.setItem(LS_POLL, sel.value); } catch (e) {}
      restartPoll();
    });
    box.appendChild(sel);

    nav.appendChild(box);
  }

  function restartPoll() {
    if (timer) { clearInterval(timer); timer = null; }
    load();
    timer = setInterval(load, getPollMs());
  }

  document.addEventListener("DOMContentLoaded", function () {
    bindMenu();
    initConfig().then(function () { initTheme(); injectTools(); });
    load();
    renderRecycleChart();
    timer = setInterval(load, getPollMs());
    document.addEventListener("visibilitychange", function () {
      if (document.hidden) { clearInterval(timer); timer = null; }
      else if (!timer) { load(); timer = setInterval(load, getPollMs()); }
    });
  });
})();
