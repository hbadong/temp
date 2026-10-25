#!/usr/bin/env python3.11
"""从 buyiju.com（卜易居）采集起名字库数据。
站点结构：/zidian/wuxing-{jin,mu,shui,huo,tu}.html 五行分类页 → /zidian/hz{id}.html 单字页
单字页字段：繁体/拼音/部首/五行/取名热度/吉凶/是否姓氏/康熙笔画/含义
礼貌策略：单线程 + 0.25s 限速 + 正常 UA；只抓候选详情页。
输出: buyiju_raw.json（全量解析）+ 校准报告
"""
import re, json, time, subprocess, sys

UA = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120 Safari/537.36"
BASE = "https://www.buyiju.com"
OUT = "/workspace/lecms-integration/scripts/buyiju_raw.json"

def fetch(url, timeout=12):
    r = subprocess.run(["curl", "-s", "-L", "--max-time", str(timeout), "-A", UA, url],
                       capture_output=True, text=True)
    return r.stdout if r.returncode == 0 else ""

def clean_text(s):
    s = re.sub(r"&nbsp;?", " ", s)
    return re.sub(r"\s+", "", s)

def parse_list_page(html, wx):
    """分类页 → [(字, hz链接)]"""
    out = []
    for m in re.finditer(r'href="(/zidian/hz\d+\.html)"[^>]*>([^<]{1,2})</a>', html):
        url, ch = m.group(1), m.group(2).strip()
        if ch:
            out.append((ch, BASE + url, wx))
    return out

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
    surname = grab(r"是否为姓氏：\|(是|否)")
    strokes = grab(r"康熙字典多少划：\|(\d+)划")
    meaning = grab(r"在名字里的含义：\|([^|]{4,80})")
    return {"c": ch, "p": py, "w": wx, "heat": int(heat.replace(",", "")) if heat else 0,
            "jx": jx, "is_sur": surname == "是", "k": int(strokes) if strokes else 0,
            "meaning": meaning}

def main():
    # 1. 五个分类页
    chars = {}
    for wx, slug in [("金", "jin"), ("木", "mu"), ("水", "shui"), ("火", "huo"), ("土", "tu")]:
        html = fetch(f"{BASE}/zidian/wuxing-{slug}.html")
        items = parse_list_page(html, wx)
        print(f"五行属{wx}: {len(items)} 字链接")
        for ch, url, w in items:
            chars.setdefault(ch, {"url": url, "w": w})
        time.sleep(0.4)
    print(f"分类页合计唯一字: {len(chars)}")

    # 2. 与现有库对比，只抓新字
    data = open("/var/www/lecms/view/haoiming/js/data.7.js", encoding="utf-8").read()
    existing = set(re.findall(r'\{c:"([^"]+)",k:\d+,w:"([^"]+)"', data) and
                   [m[0] for m in re.findall(r'\{c:"([^"]+)",k:\d+,w:"([^"]+)"', data)])
    todo = [c for c in chars if c not in existing]
    print(f"排除现有 {len(existing)} 字后待抓: {len(todo)}")

    # 3. 抓详情（上限 700，礼貌限速）
    results, errors = [], 0
    for i, ch in enumerate(sorted(todo, key=lambda c: -len(c))[:700] if False else todo[:700]):
        html = fetch(chars[ch]["url"])
        d = parse_detail(html)
        if not d["c"] or not d["p"] or not d["w"] or not d["k"]:
            errors += 1
            if errors <= 5:
                print(f"  解析不全: {ch} → {d}")
        else:
            results.append(d)
        if (i + 1) % 50 == 0:
            print(f"  进度 {i+1}/{min(len(todo),700)} 成功 {len(results)} 错误 {errors}")
        time.sleep(0.25)

    json.dump(results, open(OUT, "w", encoding="utf-8"), ensure_ascii=False)
    print(f"完成: {len(results)} 字已存 {OUT}（解析失败 {errors}）")

    # 4. 校准：与现有 232 字比对五行/笔画（对现有字重抓无必要——用分类页 w 与现库比）
    existing_map = {m[0]: m[1] for m in re.findall(r'\{c:"([^"]+)",k:\d+,w:"([^"]+)"', data)}
    agree = disagree = 0
    for ch, info in chars.items():
        if ch in existing_map:
            if existing_map[ch] == info["w"]:
                agree += 1
            else:
                disagree += 1
                if disagree <= 10:
                    print(f"  五行分歧: {ch} 现库{existing_map[ch]} vs buyiju{info['w']}")
    if agree + disagree:
        print(f"五行标注校准: 一致 {agree} / 分歧 {disagree}（一致率 {agree/(agree+disagree)*100:.1f}%）")

if __name__ == "__main__":
    main()
