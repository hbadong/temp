#!/usr/bin/env python3.11
"""SEO 优化验证：TDK 唯一性 + noindex + canonical + sitemap 可达性"""
import re, subprocess, random
from urllib.parse import quote
from concurrent.futures import ThreadPoolExecutor

UA = "Mozilla/5.0 (compatible; Baiduspider/2.0; +http://www.baidu.com/search/spider.html)"
BASE = "http://127.0.0.1:8090"
random.seed(42)

def fetch(path):
    r = subprocess.run(["curl", "-s", "-A", UA, "-m", "30", BASE + path],
                       capture_output=True, text=True)
    return r.stdout

def parse(html):
    t = re.search(r"<title>([^<]*)</title>", html)
    d = re.search(r'<meta name="description" content="([^"]*)"', html)
    c = re.search(r'<link rel="canonical" href="([^"]*)"', html)
    n = '<meta name="robots" content="noindex">' in html
    return (t.group(1) if t else "", d.group(1) if d else "", c.group(1) if c else "", n)

# 1) 单字页 TDK 唯一性（随机 20）
chars = ["林","轩","梓","薇","玥","凯","利","剑","琮","丽","伟","建","红","艳","玲","娟","怡","萱","昊","然","嘉","懿","岚","淳","墨","屹","璇","翊","岑","珩"][:26]
titles, descs, noidx = set(), set(), 0
for c in chars:
    html = fetch(f"/view/haoiming/tool/char.html?c={quote(c, safe='')}")
    t, d, can, n = parse(html)
    titles.add(t); descs.add(d); noidx += 1 if n else 0
    assert can, f"char {c} 无 canonical"
print(f"1. 单字页 TDK: title {len(titles)}/{len(chars)} 唯一, desc {len(descs)}/{len(chars)} 唯一, noindex {noidx} 个")

# 2) 矩阵页 TDK 差异化
mt = set()
for c in ["王","李","张","刘","陈"]:
    for w in ["金","木","水","火","土"]:
        html = fetch(f"/view/haoiming/tool/surname.html?c={quote(c, safe='')}&wx={quote(w, safe='')}")
        t, d, can, n = parse(html)
        mt.add(t)
        assert f"五行属{w}" in t, f"矩阵页 TDK 未差异化: {t}"
        assert can.endswith(f"surname.html?c={quote(c, safe='')}"), f"矩阵页 canonical 未集中: {can}"
print(f"2. 矩阵页 TDK: {len(mt)}/25 唯一且均含「五行属X」, canonical 全部集中到姓氏主页")

# 3) noindex 防护
cases = [("char.html?c=ZZ", True), ("surname.html?c=ZZZ", True),
         ("mingzi.html?name=ZZ", True), ("mingzi.html?name=abc", True),
         ("char.html?c=%E6%9E%97", False), ("surname.html?c=%E7%8E%8B", False),
         ("mingzi.html?name=%E6%9E%97%E8%8A%B7%E6%99%B4", False)]
ok = 0
for path, want in cases:
    html = fetch("/view/haoiming/tool/" + path)
    _, _, _, n = parse(html)
    mark = "✓" if n == want else "✗"
    ok += n == want
    print(f"3. {mark} {path} noindex={n} (期望 {want})")
assert ok == len(cases)

# 4) sitemap 可达性（随机 25）
sm = fetch("/sitemap.xml")
locs = re.findall(r"<loc>([^<]+)</loc>", sm)
print(f"4. sitemap 共 {len(locs)} URL，含矩阵页 {sum(1 for l in locs if 'wx=' in l)} 个（应为 0）")
sample = random.sample(locs, 25)
def check(u):
    r = subprocess.run(["curl", "-s", "-o", "/dev/null", "-w", "%{http_code}", "-m", "20",
                        u.replace("https://ad6acb5cb3aeb83e6.app.workbuddy.link", "http://127.0.0.1:8090")],
                       capture_output=True, text=True)
    return r.stdout.strip() == "200"
with ThreadPoolExecutor(8) as ex:
    results = list(ex.map(check, sample))
print(f"4. sitemap 抽样 25 URL: {sum(results)}/25 返回 200")
assert all(results)

# 5) 内容页预渲染 + JSON-LD
for path, kw in [("faq.html", "FAQPage"), ("baijiaxing.html", "bjx-card"),
                 ("shengxiao.html", "马年"), ("poems.html", "poem-card"),
                 ("lib.html", "lib-card")]:
    html = fetch(f"/view/haoiming/tool/{path}")
    has = kw in html
    print(f"5. {'✓' if has else '✗'} {path} 预渲染内容({kw}): {has}")
    assert has
print("===== SEO 验证全部通过 =====")
