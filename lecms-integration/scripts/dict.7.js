/* ============================================================
 * 好名 · 词典专区搜索（成语 / 汉语字典 / 词语词典）
 * 依赖页面中的 #dict-q 输入框（data-zone 指定专区）
 * ============================================================ */
(function () {
  const $ = (s, el) => (el || document).querySelector(s);

  function norm(s) {
    return (s || "").toLowerCase().replace(/[\s\-']/g, "");
  }

  function init() {
    const input = document.getElementById("dict-q");
    if (!input) return;
    const zone = input.getAttribute("data-zone") || "chengyu";
    const box = document.getElementById("dict-suggest");
    const btn = document.getElementById("dict-q-btn");
    if (!box) return;
    let data = null, loading = false, timer = null;

    function load() {
      if (data || loading) return;
      loading = true;
      fetch("/" + zone + "/search-index.json")
        .then(r => r.json())
        .then(j => { data = j; loading = false; })
        .catch(() => { loading = false; });
    }

    function render(q, res) {
      if (!res.length) {
        box.innerHTML = '<div class="dict-sug-empty">未找到与「' + q + "」相关的条目，可换个拼音或汉字试试</div>";
      } else {
        box.innerHTML = res.map(it =>
          '<a href="/' + zone + "/" + it[2] + '.html"><b>' + it[0] + "</b><span>" + (it[1] || "") + "</span></a>"
        ).join("");
      }
      box.classList.add("on");
    }

    function search(q) {
      q = (q || "").trim();
      if (!q) { box.classList.remove("on"); box.innerHTML = ""; return; }
      if (!data) { load(); box.innerHTML = '<div class="dict-sug-empty">索引加载中…</div>'; box.classList.add("on"); return; }
      const nq = norm(q);
      const isAscii = /^[a-z\s\-']+$/i.test(q);
      const res = [];
      for (let i = 0; i < data.length && res.length < 20; i++) {
        const it = data[i];
        if (it[0].indexOf(q) >= 0 || (isAscii && it[1] && it[1].indexOf(nq) === 0)) {
          res.push(it);
        }
      }
      render(q, res);
    }

    input.addEventListener("input", () => {
      clearTimeout(timer);
      timer = setTimeout(() => search(input.value), 150);
    });
    input.addEventListener("focus", () => { if (input.value.trim()) search(input.value); });
    input.addEventListener("keydown", ev => {
      if (ev.key === "Enter") {
        ev.preventDefault();
        search(input.value);
        const first = box.querySelector("a");
        if (first && norm(input.value)) first.click();
      }
    });
    if (btn) btn.addEventListener("click", () => {
      search(input.value);
      const first = box.querySelector("a");
      if (first) first.click();
    });
    document.addEventListener("click", ev => {
      if (!box.contains(ev.target) && ev.target !== input) box.classList.remove("on");
    });

    // 支持 ?q= 预填
    try {
      const q = new URLSearchParams(location.search).get("q");
      if (q) { input.value = q; load(); setTimeout(() => search(q), 300); }
    } catch (e) {}
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
