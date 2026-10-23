/* ============================================================
   国际品种走势图 — ECharts 自绘
   背景：新浪图片图表接口 image.sinajs.cn/newchart 仅支持国内期货合约
        （AU0/AG0 可正常返回 GIF），国际现货 XAU/XPT/XPD/XAG 返回空。
        因此国际品种改用 /api/minline 与 /api/daily（新浪 GlobalFuturesService）
        的 JSON 数据，由 ECharts 自绘，支持悬停、缩放。
   用法：容器 <div class="chart-canvas" data-symbol="XAU" data-kind="min|day">
   主题：自动跟随 <html data-theme="dark"> 调整配色，reinit() 可重绘
   ============================================================ */
(function () {
  "use strict";
  if (typeof echarts === "undefined") return;

  var UP = "#c0392b";   // 涨：红
  var DOWN = "#1e8e3e"; // 跌：绿
  var GOLD = "#c9a227";
  var charts = [];

  function isDark() { return document.documentElement.getAttribute("data-theme") === "dark"; }
  function AXIS() { return isDark() ? "#9aa4b2" : "#999"; }
  function GRID() { return isDark() ? "rgba(255,255,255,.08)" : "#f2f2f2"; }
  function AXLINE() { return isDark() ? "rgba(255,255,255,.2)" : "#ddd"; }
  function TIPBG() { return isDark() ? "rgba(23,29,38,.95)" : "#ffffff"; }
  function TIPBORDER() { return isDark() ? "rgba(255,255,255,.15)" : "#eee"; }

  function fail(el, msg) {
    el.innerHTML = '<div class="chart-fail">' + msg + "</div>";
  }

  /* ---------- 分时图 ---------- */
  function drawMin(el, symbol) {
    var chart = echarts.init(el);
    charts.push(chart);
    chart.showLoading({ text: "加载中…", color: GOLD, textColor: AXIS() });

    fetch("/api/minline?symbol=" + symbol)
      .then(function (r) { return r.json(); })
      .then(function (d) {
        chart.hideLoading();
        if (!d.ok || !d.points || !d.points.length) { fail(el, "暂无分时数据"); return; }
        var times = d.points.map(function (p) { return p.t; });
        var prices = d.points.map(function (p) { return p.p; });
        var avgs = d.points.map(function (p) { return p.avg; });
        var base = d.prevClose || prices[0];
        var color = prices[prices.length - 1] >= base ? UP : DOWN;
        chart.setOption({
          animation: false,
          grid: { left: 50, right: 14, top: 16, bottom: 24 },
          tooltip: {
            trigger: "axis",
            backgroundColor: TIPBG(), borderColor: TIPBORDER(),
            formatter: function (ps) {
              var p = ps[0];
              return p.axisValue + "<br/>价格：<b>" + Number(p.value).toFixed(2) + "</b>";
            }
          },
          xAxis: {
            type: "category", data: times, boundaryGap: false,
            axisLabel: { color: AXIS(), fontSize: 10, interval: Math.floor(times.length / 5) },
            axisLine: { lineStyle: { color: AXLINE() } }, axisTick: { show: false }
          },
          yAxis: {
            type: "value", scale: true,
            axisLabel: { color: AXIS(), fontSize: 10 },
            splitLine: { lineStyle: { color: GRID() } }
          },
          series: [
            {
              name: "价格", type: "line", data: prices, showSymbol: false, smooth: true,
              lineStyle: { width: 1.5, color: color },
              areaStyle: {
                color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                  { offset: 0, color: color + "55" },
                  { offset: 1, color: color + "05" }
                ])
              },
              markLine: {
                silent: true, symbol: "none",
                lineStyle: { color: AXIS(), type: "dashed", width: 1 },
                data: [{ yAxis: base }],
                label: {
                  formatter: "基准 " + base.toFixed(2),
                  color: AXIS(), fontSize: 10, position: "insideEndTop"
                }
              }
            },
            {
              name: "均价", type: "line", data: avgs, showSymbol: false, smooth: true,
              lineStyle: { width: 1, color: "#e0b64a", opacity: 0.85 }
            }
          ]
        });
      })
      .catch(function () { chart.hideLoading(); fail(el, "分时数据加载失败"); });
  }

  /* ---------- 日K图 ---------- */
  function drawDay(el, symbol) {
    var chart = echarts.init(el);
    charts.push(chart);
    chart.showLoading({ text: "加载中…", color: GOLD, textColor: AXIS() });

    fetch("/api/daily?symbol=" + symbol + "&days=120")
      .then(function (r) { return r.json(); })
      .then(function (d) {
        chart.hideLoading();
        if (!d.ok || !d.bars || !d.bars.length) { fail(el, "暂无日线数据"); return; }
        var dates = d.bars.map(function (b) { return b.date; });
        var k = d.bars.map(function (b) { return [b.o, b.c, b.l, b.h]; }); // [开, 收, 低, 高]
        chart.setOption({
          animation: false,
          grid: { left: 50, right: 14, top: 16, bottom: 34 },
          tooltip: { trigger: "axis", axisPointer: { type: "cross" }, backgroundColor: TIPBG(), borderColor: TIPBORDER() },
          xAxis: {
            type: "category", data: dates,
            axisLabel: { color: AXIS(), fontSize: 10, interval: Math.floor(dates.length / 5) },
            axisLine: { lineStyle: { color: AXLINE() } }, axisTick: { show: false }
          },
          yAxis: {
            type: "value", scale: true,
            axisLabel: { color: AXIS(), fontSize: 10 },
            splitLine: { lineStyle: { color: GRID() } }
          },
          dataZoom: [
            { type: "inside" },
            {
              type: "slider", height: 12, bottom: 4, borderColor: "transparent",
              fillerColor: "rgba(201,162,39,0.15)", handleStyle: { color: GOLD }
            }
          ],
          series: [{
            type: "candlestick", data: k,
            itemStyle: { color: UP, color0: DOWN, borderColor: UP, borderColor0: DOWN }
          }]
        });
      })
      .catch(function () { chart.hideLoading(); fail(el, "日线数据加载失败"); });
  }

  /* ---------- 初始化所有容器 ---------- */
  function init(root) {
    (root || document).querySelectorAll(".chart-canvas").forEach(function (el) {
      if (el.getAttribute("data-inited")) return;
      el.setAttribute("data-inited", "1");
      var sym = el.getAttribute("data-symbol");
      var kind = el.getAttribute("data-kind");
      if (kind === "day") drawDay(el, sym); else drawMin(el, sym);
    });
  }

  /* ---------- 重绘（主题切换时调用） ---------- */
  function reinit() {
    charts.forEach(function (c) { try { c.dispose(); } catch (e) {} });
    charts = [];
    document.querySelectorAll(".chart-canvas").forEach(function (el) { el.removeAttribute("data-inited"); });
    init();
  }

  window.GoldCharts = { init: init, reinit: reinit };
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", function () { init(); });
  } else {
    init();
  }

  var rt;
  window.addEventListener("resize", function () {
    clearTimeout(rt);
    rt = setTimeout(function () { charts.forEach(function (c) { c.resize(); }); }, 150);
  });
})();
