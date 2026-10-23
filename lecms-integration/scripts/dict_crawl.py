#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
三站数据爬虫：成语大全 / 汉语字典 / 汉语词典
- 磁盘缓存（可重复运行不重复请求）
- 并发 6，失败重试 3 次
- 索引：成语/词典 23 字母页；字典 部首 285 页 + 拼音全音节页
- 输出结构化 JSON 到 dict_data/
"""
import os, re, sys, json, time, html as H
import concurrent.futures as cf
import urllib.request, urllib.error

BASE = "/root/.codebuddy/artifact/2f8a7d30-9c21-4f0b-1d51-5b9fdf21a000"
CACHE = os.path.join(BASE, "dict_cache")
OUT = os.path.join(BASE, "dict_data")
SITES = {
    "chengyu": "https://chengyu.qiming.cn",
    "zidian": "https://zidian.qiming.cn",
    "cidian": "https://cidian.qiming.cn",
}
LETTERS = list("abcdefghjklmnopqrstwxyz")  # 三站字母索引均无 i/u/v
UA = {"User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Safari/537.36"}

stats = {"fetch": 0, "cache": 0, "fail": 0}

def fetch(url, retries=3):
    """带磁盘缓存的抓取"""
    site = url.split("/")[2].split(".")[0]
    key = url.split(site + ".qiming.cn", 1)[1] or "/"
    safe = re.sub(r"[^A-Za-z0-9_.-]", "_", key)
    path = os.path.join(CACHE, site, safe)
    os.makedirs(os.path.dirname(path), exist_ok=True)
    if os.path.exists(path) and os.path.getsize(path) > 200:
        stats["cache"] += 1
        return open(path, encoding="utf-8", errors="ignore").read()
    last = None
    for i in range(retries):
        try:
            req = urllib.request.Request(url, headers=UA)
            with urllib.request.urlopen(req, timeout=25) as r:
                data = r.read().decode("utf-8", errors="ignore")
            with open(path, "w", encoding="utf-8") as f:
                f.write(data)
            stats["fetch"] += 1
            time.sleep(0.03)
            return data
        except Exception as e:
            last = e
            time.sleep(0.5 * (i + 1))
    stats["fail"] += 1
    print(f"[FAIL] {url}: {last}", file=sys.stderr)
    return ""

def fetch_many(urls, workers=6):
    out = {}
    with cf.ThreadPoolExecutor(max_workers=workers) as ex:
        futs = {ex.submit(fetch, u): u for u in urls}
        for fu in cf.as_completed(futs):
            u = futs[fu]
            try:
                out[u] = fu.result()
            except Exception as e:
                print(f"[ERR] {u}: {e}", file=sys.stderr)
    return out

# ---------------- 索引 ----------------
def index_cy_cd():
    """成语/词典字母页 -> [{id, name, pinyin}]"""
    entries = {}
    pages = []
    for site in ("chengyu", "cidian"):
        for L in LETTERS:
            sub = "cyzm" if site == "chengyu" else "cdzm"
            pages.append(f"{SITES[site]}/{sub}/{L}.html")
    results = fetch_many(pages, workers=6)
    for url, html in results.items():
        if not html:
            continue
        site = "chengyu" if "chengyu" in url else "cidian"
        for m in re.finditer(
            r'<li><a href="(?:https?://[^/]+)?/(?:chengyu|cidian)/(\d+)\.html"\s+title="([^"]+)"><span>(.*?)</span>', html):
            eid, name, py = m.group(1), H.unescape(m.group(2)), H.unescape(re.sub(r"<[^>]+>", "", m.group(3))).strip()
            entries[f"{site}:{eid}"] = {"site": site, "id": eid, "name": name, "pinyin": py}
    return entries

def index_zd():
    """字典：部首索引 + 拼音索引 -> {id: {char, pinyin}}"""
    idx_html = fetch(SITES["zidian"] + "/bushou/index.html")
    py_html = fetch(SITES["zidian"] + "/pinyin/index.html")
    radical_pages = sorted(set(re.findall(r'href="(?:https?://[^/]+)?/bushou/([a-z]+)\.html"', idx_html)))
    pinyin_pages = sorted(set(re.findall(r'href="(?:https?://[^/]+)?/pinyin/([a-z]+)\.html"', py_html)))
    print(f"部首页: {len(radical_pages)}  拼音页: {len(pinyin_pages)}")
    urls = ([f"{SITES['zidian']}/bushou/{p}.html" for p in radical_pages] +
            [f"{SITES['zidian']}/pinyin/{p}.html" for p in pinyin_pages])
    results = fetch_many(urls, workers=6)
    entries = {}
    for url, html in results.items():
        if not html:
            continue
        for m in re.finditer(
            r'<li><a href="(?:https?://[^/]+)?/zidian/(\d+)\.html"\s+title="([^"]*)"><span>(.*?)</span>', html):
            eid, ch, py = m.group(1), H.unescape(m.group(2)), H.unescape(re.sub(r"<[^>]+>", "", m.group(3))).strip()
            entries[f"zidian:{eid}"] = {"site": "zidian", "id": eid, "name": ch, "pinyin": py}
    return entries

# ---------------- 详情解析 ----------------
def clean_text(s):
    return re.sub(r"\s+", " ", H.unescape(re.sub(r"<[^>]+>", " ", s))).strip()

def clean_html(s):
    """保留段落结构，去脚本样式"""
    s = re.sub(r"<script.*?</script>|<style.*?</style>", "", s, flags=re.S)
    return s.strip()

def parse_chengyu(html):
    d = {}
    m = re.search(r'<div class="pinyin light f16"><span>(.*?)</span></div>\s*<h1>(.*?)</h1>', html, re.S)
    if not m:
        return None
    d["pinyin"] = clean_text(m.group(1)); d["name"] = clean_text(m.group(2))
    tags = {}
    tm = re.search(r'<div class="tags">(.*?)</div>', html, re.S)
    if tm:
        for t in re.finditer(r"<li><a [^>]*title=\"([^\"]+)\"><span>[^<]+</span>([^<]+)</a>", tm.group(1)):
            tags[clean_text(t.group(1))] = clean_text(t.group(2))
    d["tags"] = tags
    # detail 主区
    dm = re.search(r'<div class="detail">(.*?)<span class="gray">', html, re.S)
    body = dm.group(1) if dm else ""
    # 按 h2 切段
    secs = {}
    parts = re.split(r"<h2>(.*?)</h2>", body)
    for i in range(1, len(parts), 2):
        secs[clean_text(parts[i])] = parts[i + 1]
    # 基本解释
    basic = ""
    if "意思解释" in secs:
        bm = re.search(r'<span class="tip">基本解释</span>\s*<p>(.*?)</p>', secs["意思解释"], re.S)
        if bm:
            basic = clean_text(bm.group(1))
        else:
            bm = re.search(r'<span class="tip">基本解释</span>(.*)', secs["意思解释"], re.S)
            basic = clean_text(bm.group(1)) if bm else ""
    d["basic"] = basic
    # 基础信息 tip 对
    info = {}
    if "基础信息" in secs:
        for pm in re.finditer(r'<p><span class="tip">([^<]+)</span>(.*?)</p>', secs["基础信息"], re.S):
            label, val = clean_text(pm.group(1)), pm.group(2)
            links = re.findall(r"<a href='(?:https?://[^/]+)?/chengyu/(\d+)\.html'[^>]*>([^<]+)</a>", val)
            if links:
                info[label] = [{"id": i, "name": H.unescape(n)} for i, n in links]
            else:
                info[label] = clean_text(val)
    d["info"] = info
    # 字义分解
    chars = []
    if "字义分解" in secs:
        for fm in re.finditer(r'<a class="fontbox" href="(?:https?://[^/]+)?/zidian/(\d+)\.html"[^>]*><span>(.*?)</span>([^<]+)</a>', secs["字义分解"]):
            chars.append({"zidian_id": fm.group(1), "pinyin": clean_text(fm.group(2)), "char": H.unescape(fm.group(3))})
    d["chars"] = chars
    # 成语接龙
    jl = []
    jm = re.search(r"<h3>[^<]*成语接龙[^<]*</h3>.*?<ul[^>]*>(.*?)</ul>", html, re.S)
    if jm:
        for lm in re.finditer(r"<li><a href='(?:https?://[^/]+)?/chengyu/(\d+)\.html'[^>]*>([^<]+)</a>", jm.group(1)):
            jl.append({"id": lm.group(1), "name": H.unescape(lm.group(2))})
    d["jielong"] = jl
    return d

def parse_zidian(html):
    d = {}
    m = re.search(r'<div class="title"><h1>(.*?)</h1></div>', html)
    if not m:
        return None
    d["char"] = clean_text(m.group(1))
    dm = re.search(r'<div class="detail zdwdetail">(.*?)<div class="gclear">', html, re.S)
    attrs = {}
    yiti = []
    if dm:
        seg = dm.group(1)
        # 逐 attr_name 切
        parts = re.split(r'<span class="attr_name">(.*?)</span>', seg)
        for i in range(1, len(parts), 2):
            label = clean_text(parts[i])
            val = parts[i + 1] if i + 1 < len(parts) else ""
            val = re.split(r'<span class="attr_name">', val)[0]
            if label == "异体字":
                for lm in re.finditer(r"<a href='(?:https?://[^/]+)?/zidian/(\d+)\.html'>([^<]+)</a>", val):
                    yiti.append({"id": lm.group(1), "char": H.unescape(lm.group(2))})
                attrs[label] = " ".join(H.unescape(x) for x in re.findall(r">([^<>]+)</a>", val))
            elif label == "部首":
                bm = re.search(r"<a href=\"(?:https?://[^/]+)?/bushou/([a-z]+)\.html\"[^>]*>([^<]+)</a>", val)
                attrs[label] = H.unescape(bm.group(2)) if bm else clean_text(val)
                if bm:
                    attrs["_radical"] = bm.group(1)
            else:
                attrs[label] = clean_text(val)
    d["attrs"] = attrs
    d["yiti"] = yiti
    # 基本解释 / 详细解释
    bm = re.search(r"<h2>基本解释</h2>(.*?)(?=<div class=\"gclear\"></div><hr|<div class=\"box\">|$)", html, re.S)
    d["basic_html"] = clean_html(bm.group(1)) if bm else ""
    xm = re.search(r"<h2>详细解释</h2>(.*?)(?=<div class=\"box\">|<div class=\"sidebar|$)", html, re.S)
    d["detail_html"] = clean_html(xm.group(1)) if xm else ""
    return d

def parse_cidian(html):
    d = {}
    m = re.search(r'<div class="pinyin light f16">(.*?)</div>\s*<h1>(.*?)</h1>', html, re.S)
    if not m:
        return None
    d["pinyin"] = clean_text(m.group(1)); d["name"] = clean_text(m.group(2))
    attrs = {}
    am = re.search(r'<div class="text">(.*?)<hr\s*/?>\s*<div class="mtb">', html, re.S)
    if am:
        for at in re.finditer(r'<div class="attr"><span class="attr_name">([^<]+)</span>(.*?)</div>', am.group(1), re.S):
            label, val = clean_text(at.group(1)), at.group(2)
            links = re.findall(r"<a href='(?:https?://[^/]+)?/cidian/(\d+)\.html'[^>]*>([^<]+)</a>", val)
            if links:
                attrs[label] = [{"id": i, "name": H.unescape(n)} for i, n in links]
            else:
                attrs[label] = clean_text(val)
    d["attrs"] = attrs
    # 词语解释/引证解释/国语辞典：按 h2 切
    body_m = re.search(r'<div class="mtb">(.*?)<hr\s*/?>\s*<span class="gray">', html, re.S)
    body = body_m.group(1) if body_m else ""
    secs = {}
    parts = re.split(r"<h2>(.*?)</h2>", body)
    for i in range(1, len(parts), 2):
        secs[clean_text(parts[i])] = parts[i + 1]
    d["jieshi_html"] = clean_html(secs.get("词语解释", ""))
    d["yinzheng_html"] = clean_html(secs.get("引证解释", ""))
    d["guoyu_html"] = clean_html(secs.get("国语辞典", ""))
    # 分字解释
    chars = []
    cm = re.search(r"<h2>分字解释</h2>(.*?)</div>", body, re.S)
    if cm:
        for fm in re.finditer(r'<a class="fontbox" href="(?:https?://[^/]+)?/zidian/(\d+)\.html"[^>]*><span>(.*?)</span>([^<]+)</a>', cm.group(1)):
            chars.append({"zidian_id": fm.group(1), "pinyin": clean_text(fm.group(2)), "char": H.unescape(fm.group(3))})
    d["chars"] = chars
    # 造句
    zj = []
    zm = re.search(r"<h3>[^<]*的造句</h3>.*?<ul[^>]*>(.*?)</ul>", html, re.S)
    if zm:
        for lm in re.finditer(r"<li>(.*?)</li>", zm.group(1), re.S):
            zj.append(clean_text(lm.group(1)))
    d["zaoju"] = zj
    return d

def main():
    os.makedirs(OUT, exist_ok=True)
    # 1. 索引
    print("== 抓取字母/部首/拼音索引 ==")
    idx_cy_cd = index_cy_cd()
    idx_zd = index_zd()
    print(f"成语条目: {sum(1 for k in idx_cy_cd if k.startswith('chengyu'))}")
    print(f"词典条目: {sum(1 for k in idx_cy_cd if k.startswith('cidian'))}")
    print(f"字典条目: {len(idx_zd)}")
    json.dump(idx_cy_cd, open(f"{OUT}/index_cy_cd.json", "w", encoding="utf-8"), ensure_ascii=False)
    json.dump(idx_zd, open(f"{OUT}/index_zd.json", "w", encoding="utf-8"), ensure_ascii=False)
    print(f"统计: {stats}")

if __name__ == "__main__":
    main()
