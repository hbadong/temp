#!/usr/bin/env python3.11
"""buyiju 采集 v2：均衡抓取全部分类的剩余字 + 补抓含义字段。
- 断点续传：buyiju_raw.json 已有且 w/k/p 完整的条目跳过（除非补含义阶段）
- meaning 正则修正（冒号后无分隔符）
- 礼貌限速 0.22s
"""
import re, json, time, subprocess

UA = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120 Safari/537.36"
BASE = "https://www.buyiju.com"
OUT = "/workspace/lecms-integration/scripts/buyiju_raw.json"

def fetch(url, timeout=12):
    r = subprocess.run(["curl", "-s", "-L", "--max-time", str(timeout), "-A", UA, url],
                       capture_output=True, text=True)
    return r.stdout if r.returncode == 0 else ""

def parse_detail(html):
    body = re.sub(r"<script.*?</script>", "", html, flags=re.S)
    body = re.sub(r"<[^>]+>", "|", body)
    body = re.sub(r"\|+", "|", body)

    def grab(pat, default=""):
        m = re.search(pat, body)
        return m.group(1).strip() if m else default

    ch = grab(r"\|(\S)字的起名属性")
    py = grab(r"字的拼音：\|([^|]+)")
    wx = grab(r"五行属什么：\|(金|木|水|火|土)")
    heat = grab(r"取名的人多吗：\|([\d,]+)人次")
    jx = grab(r"取名字好么：\|(吉|凶|中)")
    strokes = grab(r"康熙字典多少划：\|(\d+)划")
    meaning = grab(r"在名字里的含义：\|?([^|\r]{4,90})")
    return {"c": ch, "p": py, "w": wx, "heat": int(heat.replace(",", "")) if heat else 0,
            "jx": jx, "k": int(strokes) if strokes else 0, "meaning": meaning}

def main():
    # 1. 五分类字表（保持分类归属）
    by_wx = {}
    for wx, slug in [("金", "jin"), ("木", "mu"), ("水", "shui"), ("火", "huo"), ("土", "tu")]:
        html = fetch(f"{BASE}/zidian/wuxing-{slug}.html")
        items = re.findall(r'href="(/zidian/hz\d+\.html)"[^>]*>([^<]{1,2})</a>', html)
        for url, ch in items:
            ch = ch.strip()
            if ch:
                by_wx.setdefault(ch, BASE + url)
        print(f"五行属{wx}列表完成", flush=True)
        time.sleep(0.4)
    print(f"合计唯一字: {len(by_wx)}", flush=True)

    # 2. 排除现有库
    data = open("/var/www/lecms/view/haoiming/js/data.7.js", encoding="utf-8").read()
    existing = set(m[0] for m in re.findall(r'\{c:"([^"]+)",k:\d+,w:"[^"]+"', data))
    todo = {c: u for c, u in by_wx.items() if c not in existing}
    print(f"待抓: {len(todo)}", flush=True)

    # 3. 断点续传：加载已抓数据
    try:
        done = {x["c"]: x for x in json.load(open(OUT, encoding="utf-8"))}
    except Exception:
        done = {}
    results = dict(done)
    need = [(c, u) for c, u in todo.items() if c not in done or not done[c].get("meaning")]
    print(f"已有 {len(done)} 条，补抓 {len(need)} 条", flush=True)

    errors = 0
    for i, (ch, url) in enumerate(sorted(need)):
        html = fetch(url)
        d = parse_detail(html)
        if not d["c"] or not d["p"] or not d["w"] or not d["k"]:
            errors += 1
            if errors <= 5:
                print(f"  解析不全: {ch} → {d}", flush=True)
        else:
            results[ch] = d
        if (i + 1) % 100 == 0:
            print(f"  进度 {i+1}/{len(need)} 成功 {len(results)} 错误 {errors}", flush=True)
            json.dump(list(results.values()), open(OUT, "w", encoding="utf-8"), ensure_ascii=False)
        time.sleep(0.22)

    json.dump(list(results.values()), open(OUT, "w", encoding="utf-8"), ensure_ascii=False)
    print(f"完成: {len(results)} 字（错误 {errors}）", flush=True)

if __name__ == "__main__":
    main()
