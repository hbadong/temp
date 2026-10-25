#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
dict_gen.py — 成语 / 汉语字典 / 词语词典 三站静态页面生成器
输入: dict_data/{chengyu,cidian,zidian}.json（详情抓取产物）
输出: /var/www/lecms/{chengyu,zidian,cidian}/ 下全部静态页面 + 搜索索引 + sitemap
"""
import os, re, json, html as H, random, zlib, urllib.parse

BASE = "/var/www/lecms"
DATA = "/root/.codebuddy/artifact/2f8a7d30-9c21-4f0b-1d51-5b9fdf21a000/dict_data"
CSS_V = "20261023c"
JS_V = "20261023c"
SITE_NAME = "好名网"
DOMAIN = "https://www.qiming.cn"

# ---------------- 拼音 → ASCII ----------------
TONE = {}
for a, b in [
    ("ā", "a"), ("á", "a"), ("ǎ", "a"), ("à", "a"),
    ("ē", "e"), ("é", "e"), ("ě", "e"), ("è", "e"), ("ê", "e"),
    ("ī", "i"), ("í", "i"), ("ǐ", "i"), ("ì", "i"),
    ("ō", "o"), ("ó", "o"), ("ǒ", "o"), ("ò", "o"),
    ("ū", "u"), ("ú", "u"), ("ǔ", "u"), ("ù", "u"),
    ("ǖ", "v"), ("ǘ", "v"), ("ǚ", "v"), ("ǜ", "v"), ("ü", "v"),
    ("ń", "n"), ("ň", "n"), ("ǹ", "n"), ("ḿ", "m"),
]:
    TONE[a] = b

def py_ascii(p):
    return "".join(TONE.get(c, c.lower()) for c in (p or ""))

def pinyin_slug(p):
    """'āi fēng jī fèng' -> 'ai-feng-ji-feng'；多读音取第一个"""
    first = (p or "").split(",")[0]
    return "-".join(py_ascii(x) for x in first.split() if x.strip())

def all_syllables(p):
    out = set()
    for reading in (p or "").split(","):
        for x in reading.split():
            s = py_ascii(x)
            if s:
                out.add(s)
    return out

def first_letter(e):
    p = (e.get("pinyin", "") or "").split(",")[0].split()
    return py_ascii(p[0])[:1].lower() if p else "#"

def esc(s):
    return H.escape(str(s if s is not None else ""), quote=True)

def trunc(s, n=90):
    s = re.sub(r"\s+", " ", (s or "")).strip()
    return s if len(s) <= n else s[: n - 1] + "…"

def strip_tags(s):
    return re.sub(r"\s+", " ", re.sub(r"<[^>]+>", "", s or "")).strip()

# ---------------- 页面骨架 ----------------
def page(title, desc, keywords, body, extra_head=""):
    return f"""<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{esc(title)}</title>
<meta property="og:type" content="website">
<meta property="og:site_name" content="{SITE_NAME}">
<meta property="og:title" content="{esc(title)}">
<meta property="og:description" content="{esc(desc)}">
<meta name="keywords" content="{esc(keywords)}">
<meta name="description" content="{esc(desc)}">
{extra_head}<link rel="stylesheet" href="/view/haoiming/css/style.7.css?v={CSS_V}">
</head>
<body>
<header id="site-header"></header>
{body}
<footer id="site-footer"></footer>
<script src="/view/haoiming/js/data.7.js"></script>
<script src="/view/haoiming/js/shuowen.7.js"></script>
<script src="/view/haoiming/js/engine.7.js"></script>
<script>window._LE={{uri:"/",tpl:"/view/haoiming/",dict:1}};</script><script src="/view/haoiming/js/main.7.js?v={JS_V}"></script>
</body>
</html>"""

def banner(seal, h1, sub):
    return f"""<div class="ink-banner"><div class="banner-inner">
    <span class="seal">{esc(seal)}</span>
    <h1 class="bh">{esc(h1)}</h1>
    <div class="sub">{esc(sub)}</div>
  </div></div>"""

# ---------------- HTML 片段链接改写 ----------------
def make_html_rewriter(slug_by_id):
    pat = re.compile(r'href="(?:https?://[^/"]+)?/(zidian|cidian|chengyu)/(\d+)\.html"')
    def rep(m):
        site, eid = m.group(1), m.group(2)
        slug = slug_by_id.get((site, eid))
        return f'href="/{site}/{slug}.html"' if slug else 'href="javascript:void(0)"'
    def rw(s):
        if not s:
            return ""
        s = pat.sub(rep, s)
        s = re.sub(r'href="(?:https?://[^/"]+)?/(?!view/)[a-z]+/[^"]*"', "", s)
        s = re.sub(r'href="https?://[^"]*"', "", s)
        s = re.sub(r"<img[^>]*>", "", s)
        s = re.sub(r'\s(onclick|onload|onerror|target|rel)="[^"]*"', "", s)
        s = re.sub(r'<div class="gclear"></div>', "", s)
        return s
    return rw

# ============================================================
#  数据加载与索引
# ============================================================
def load():
    def rd(name):
        p = f"{DATA}/{name}.json"
        return json.load(open(p, encoding="utf-8")) if os.path.exists(p) else {}
    return rd("chengyu"), rd("cidian"), rd("zidian")

def build_slugs(cy, cd, zd):
    slug_by_id = {}
    def assign(entries, site, pykey):
        used = {}
        for k in sorted(entries):
            e = entries[k]
            base = pinyin_slug(e.get(pykey, "")) or site[:2]
            used[base] = used.get(base, 0) + 1
            e["slug"] = base if used[base] == 1 else f"{base}-{used[base]}"
            slug_by_id[(site, e["id"])] = e["slug"]
    assign(cy, "chengyu", "pinyin")
    assign(cd, "cidian", "pinyin")
    # 字典拼音在 attrs 里
    for e in zd.values():
        e["py"] = (e.get("attrs") or {}).get("拼音", "") or e.get("pinyin", "")
    assign(zd, "zidian", "py")
    return slug_by_id

def build_indexes(cy, cd, zd):
    idx = {"cy_letter": {}, "cd_letter": {}, "radical": {}, "syllable": {},
           "cy_by_zid": {}, "cd_by_char": {}}
    for e in cy.values():
        idx["cy_letter"].setdefault(first_letter(e), []).append(e)
    for e in cd.values():
        idx["cd_letter"].setdefault(first_letter(e), []).append(e)
    for e in zd.values():
        rad = (e.get("attrs") or {}).get("部首", "")
        if rad:
            idx["radical"].setdefault(rad, []).append(e)
        for syl in all_syllables(e.get("py", "")):
            idx["syllable"].setdefault(syl, []).append(e)
    for e in cy.values():
        for c in e.get("chars", []):
            idx["cy_by_zid"].setdefault(c.get("zidian_id"), []).append(e)
    for e in cd.values():
        for ch in set(e.get("name", "")):
            idx["cd_by_char"].setdefault(ch, []).append(e)
    for d in idx.values():
        for k in d:
            d[k].sort(key=lambda x: x.get("slug", ""))
    return idx

# ============================================================
#  渲染组件
# ============================================================
def tag_chips(tags):
    if not tags:
        return ""
    order = ["热度", "年代", "字数", "结构", "感情"]
    items = [(k, tags[k]) for k in order if k in tags] + [(k, v) for k, v in tags.items() if k not in order]
    return '<div class="dict-tagrow">' + "".join(
        f'<span class="dict-tag"><i>{esc(k)}</i>{esc(v)}</span>' for k, v in items) + "</div>"

def attr_rows(attrs, skip=()):
    order = ["拼音", "注音", "总笔画", "结构", "五笔", "统一码", "仓颉", "郑码",
             "四角", "电码", "区位", "笔顺编号", "姓名学", "英语", "笔顺读写"]
    rows = [(k, attrs[k]) for k in order if k in attrs and k not in skip]
    rows += [(k, v) for k, v in attrs.items() if k not in order and not k.startswith("_") and k not in skip]
    if not rows:
        return ""
    return '<div class="dict-attrs">' + "".join(
        f'<div class="dict-attr"><i>{esc(k)}</i><span>{esc(v)}</span></div>' for k, v in rows) + "</div>"

def cy_card(e):
    tags = e.get("tags", {})
    t = " · ".join(x for x in [esc(tags.get("感情", "")), esc(tags.get("字数", ""))] if x)
    return f"""<a class="dict-card" href="/chengyu/{e['slug']}.html" title="{esc(e['name'])}">
      <b>{esc(e['name'])}</b><span class="py">{esc(e.get('pinyin', ''))}</span>
      <span class="tg">{t}</span><p>{esc(trunc(e.get('basic', ''), 56))}</p></a>"""

def zd_card(e):
    return f"""<a class="dict-card zd" href="/zidian/{e['slug']}.html" title="{esc(e.get('char', ''))}">
      <b>{esc(e.get('char', ''))}</b><span class="py">{esc(e.get('py', ''))}</span>
      <p>{esc(trunc(strip_tags(e.get('basic_html', '')), 40))}</p></a>"""

def cd_card(e):
    return f"""<a class="dict-card" href="/cidian/{e['slug']}.html" title="{esc(e['name'])}">
      <b>{esc(e['name'])}</b><span class="py">{esc(e.get('pinyin', ''))}</span>
      <p>{esc(trunc(strip_tags(e.get('jieshi_html', '')), 56))}</p></a>"""

def rel_links(entries, site, limit=12):
    if not entries:
        return ""
    items = []
    for e in entries[:limit]:
        label = e.get("name") or e.get("char") or ""
        items.append(f'<a href="/{site}/{e["slug"]}.html" title="{esc(label)}">{esc(label)}</a>')
    return '<div class="dict-rel">' + "".join(items) + "</div>"

def char_cards(chars, sl):
    out = []
    for c in chars:
        s = sl(c.get("zidian_id"), "zidian")
        inner = f'<b>{esc(c["char"])}</b><span>{esc(c.get("pinyin", ""))}</span>'
        if s:
            out.append(f'<a class="dict-char" href="/zidian/{s}.html" title="{esc(c["char"])}">{inner}</a>')
        else:
            out.append(f'<span class="dict-char nolink">{inner}</span>')
    return '<div class="dict-chars">' + "".join(out) + "</div>"

def search_box(zone, placeholder):
    return f"""<div class="dict-search-box">
    <input id="dict-q" type="text" placeholder="{esc(placeholder)}" autocomplete="off" data-zone="{zone}">
    <button type="button" id="dict-q-btn">搜索</button>
    <div class="dict-suggest" id="dict-suggest"></div>
  </div>"""

def crumb(parts):
    out = []
    for u, label in parts:
        out.append(f'<a href="{u}">{esc(label)}</a>' if u else f'<span>{esc(label)}</span>')
    return '<div class="dict-crumb">' + '<i>·</i>'.join(out) + "</div>"

def sec(title, inner, h2=True):
    if not inner:
        return ""
    tag = "h2" if h2 else "h3"
    return f'<div class="rel-box dict-sec"><{tag}>{esc(title)}</{tag}>{inner}</div>'

def letter_nav(letters, zone):
    return '<div class="dict-indexnav">' + "".join(
        f'<a href="/{zone}/zm-{l}.html">{l.upper()}</a>' for l in letters) + "</div>"

# ============================================================
#  页面渲染
# ============================================================
def render_cy_home(cy, idx, featured):
    letters = sorted(idx["cy_letter"])
    letter_bar = '<div class="dict-letters">' + "".join(
        f'<a href="/chengyu/zm-{l}.html">{l.upper()}</a>' for l in letters) + "</div>"
    cards = "".join(cy_card(e) for e in featured)
    body = banner("成", "成语词典", f"收录成语 {len(cy):,} 条 · 释义出处 · 近义反义 · 字义分解 · 成语接龙")
    body += f"""<div class="wrap">
  {search_box('chengyu', '输入成语或拼音，如 守株待兔 / shouzhudatu')}
  <div class="dict-stats">
    <div class="dict-stat"><b>{len(cy):,}</b><span>成语总数</span></div>
    <div class="dict-stat"><b>{len(letters)}</b><span>拼音首字母</span></div>
    <div class="dict-stat"><b>{len(idx['cy_by_zid']):,}</b><span>单字关联</span></div>
  </div>
  <div class="dict-zone-links">
    <a href="/zidian/" class="dict-zone-link"><b>汉语字典</b><span>查汉字释义与属性</span></a>
    <a href="/cidian/" class="dict-zone-link"><b>词语词典</b><span>查词语解释与造句</span></a>
  </div>
  {sec('按拼音首字母查找', letter_bar, h2=False)}
  {sec('常用成语', '<div class="dict-grid">' + cards + '</div>', h2=False)}
  <div class="rel-box dict-sec dict-intro">
    <h3>关于成语词典</h3>
    <p>成语词典收录常见成语及其拼音、感情色彩、结构用法与文献出处，支持按拼音首字母浏览。每条成语提供释义、出处原文、用法示例、近义词与反义词，并通过「字义分解」逐字链接到汉语字典，实现成语与单字的双向打通；另附成语接龙，适合语文学习与写作参考。</p>
  </div>
</div>"""
    return page("成语词典_成语大全_成语意思及出处 - 好名网",
                "收录数千条常用成语，提供拼音、释义、出处、近反义词、字义分解与成语接龙，支持拼音首字母检索。",
                "成语词典,成语大全,成语解释,成语意思,成语出处,近义词反义词", body,
                '<script src="/view/haoiming/js/dict.7.js"></script>\n')

def render_letter_page(kind, letter, entries, all_letters):
    if kind == "chengyu":
        zone, zname, zseo, cards = "chengyu", "成语词典", "成语", "".join(cy_card(e) for e in entries)
    else:
        zone, zname, zseo, cards = "cidian", "词语词典", "词语", "".join(cd_card(e) for e in entries)
    label = f"首字母 {letter.upper()}"
    body = banner(zseo[:1], f"{zname} · {label}", f"共 {len(entries):,} 条{zseo}")
    body += f"""<div class="wrap">
  {crumb([(f'/{zone}/', zname), ('', label)])}
  {search_box(zone, f'输入{zseo}或拼音')}
  <div class="dict-list">{cards}</div>
  {letter_nav(all_letters, zone)}
</div>"""
    return page(f"{zname}_{label}_{zseo}大全 - 好名网",
                f"{zname}首字母 {letter.upper()} 的{zseo}共 {len(entries):,} 条，含拼音与释义，点击查看详情。",
                f"{zseo},{zname},{label}", body,
                '<script src="/view/haoiming/js/dict.7.js"></script>\n')

def render_cy_detail(e, idx, sl, rh, related):
    tags = e.get("tags", {})
    info = e.get("info", {})
    rows = [f'<div class="dict-attr"><i>{esc(k)}</i><span>{esc(info[k])}</span></div>'
            for k in ["出处", "例子", "用法", "感情", "辨形", "正音"] if info.get(k)]
    info_html = '<div class="dict-attrs">' + "".join(rows) + "</div>" if rows else ""
    jy_html = ""
    for k in ["近义词", "反义词"]:
        lst = info.get(k) or []
        if not lst:
            continue
        links = []
        for x in lst:
            s = sl(x.get("id"), "chengyu")
            links.append(f'<a href="/chengyu/{s}.html">{esc(x["name"])}</a>' if s else esc(x["name"]))
        jy_html += f'<div class="dict-jy-row"><i>{esc(k)}</i><div class="dict-rel">' + "".join(links) + "</div></div>"
    if jy_html:
        jy_html = f'<div class="dict-jy">{jy_html}</div>'
    chars = e.get("chars", [])
    ch_html = char_cards(chars, sl) if chars else ""
    jl = e.get("jielong", [])
    jl_html = ""
    if jl:
        parts = []
        for x in jl:
            s = sl(x["id"], "chengyu")
            parts.append(f'<a href="/chengyu/{s}.html">{esc(x["name"])}</a>' if s else esc(x["name"]))
        jl_html = '<div class="dict-chain">' + '<i>→</i>'.join(parts) + "</div>"
    char_cy = []
    for c in chars:
        lst = [x for x in idx["cy_by_zid"].get(c.get("zidian_id"), []) if x["id"] != e["id"]]
        if lst:
            char_cy.append((c["char"], lst[:8]))
    ccy_html = ""
    if char_cy:
        ccy_html = '<div class="dict-charcy-wrap">' + "".join(
            f'<div class="dict-charcy"><i>{esc(ch)}</i><div class="dict-rel">' +
            "".join(f'<a href="/chengyu/{x["slug"]}.html">{esc(x["name"])}</a>' for x in lst) + "</div></div>"
            for ch, lst in char_cy) + "</div>"
    letter = first_letter(e)
    body = banner("成", e["name"], e.get("pinyin", ""))
    body += f"""<div class="wrap">
  {crumb([('/chengyu/', '成语词典'), (f'/chengyu/zm-{letter}.html', letter.upper()), ('', e['name'])])}
  {tag_chips(tags)}
  {sec('释义', f'<p class="dict-basic">{esc(e.get("basic", ""))}</p>')}
  {sec('详细资料', info_html + jy_html)}
  {sec('字义分解', ch_html + ('<p class="dict-tip">点击单字可查看汉字详解</p>' if ch_html else ''))}
  {sec('成语接龙', jl_html)}
  {sec('含以上单字的成语', ccy_html)}
  {sec('相关成语', rel_links(related, 'chengyu', 12))}
  <div class="rel-box dict-sec dict-intro">
    <h3>{esc(e['name'])}</h3>
    <p>{esc(e['name'])}（拼音：{esc(e.get('pinyin', ''))}）{esc(trunc(e.get('basic', ''), 120))}。本页整理其感情色彩、语法结构、文献出处与用法示例，并给出近义词、反义词及成语接龙，供语文学习与写作查阅。</p>
  </div>
</div>"""
    t = tags.get("感情", "")
    return page(f"{e['name']}_{e.get('pinyin','')}_成语{t and '_' + t}意思 - 好名网",
                f"{e['name']}什么意思？{trunc(e.get('basic',''), 70)}含拼音、出处、近反义词与字义分解。",
                f"{e['name']},{e['name']}的意思,{e['name']}什么意思,成语,{e.get('pinyin','')}", body)

def render_zd_home(zd, idx, featured):
    radicals = sorted(idx["radical"].items(), key=lambda kv: -len(kv[1]))
    rad_grid = '<div class="dict-radicals">' + "".join(
        f'<a class="dict-radical" href="/zidian/bs-{urllib.parse.quote(r)}.html"><b>{esc(r)}</b><span>{len(v):,}</span></a>'
        for r, v in radicals) + "</div>"
    syls = sorted(idx["syllable"])
    syl_grid = '<div class="dict-letters dense">' + "".join(
        f'<a href="/zidian/py-{s}.html">{s}</a>' for s in syls) + "</div>"
    cards = "".join(zd_card(e) for e in featured)
    body = banner("字", "汉语字典", f"收录汉字 {len(zd):,} 个 · 拼音笔画 · 部首结构 · 五笔编码 · 释义详解")
    body += f"""<div class="wrap">
  {search_box('zidian', '输入汉字或拼音，如 好 / hao')}
  <div class="dict-stats">
    <div class="dict-stat"><b>{len(zd):,}</b><span>汉字总数</span></div>
    <div class="dict-stat"><b>{len(idx['radical'])}</b><span>部首</span></div>
    <div class="dict-stat"><b>{len(syls)}</b><span>拼音音节</span></div>
  </div>
  <div class="dict-zone-links">
    <a href="/chengyu/" class="dict-zone-link"><b>成语词典</b><span>查成语释义与出处</span></a>
    <a href="/cidian/" class="dict-zone-link"><b>词语词典</b><span>查词语解释与造句</span></a>
  </div>
  {sec('按部首浏览', rad_grid, h2=False)}
  {sec('按拼音浏览', syl_grid, h2=False)}
  {sec('常用汉字', '<div class="dict-grid">' + cards + '</div>', h2=False)}
  <div class="rel-box dict-sec dict-intro">
    <h3>关于汉语字典</h3>
    <p>汉语字典收录常用与生僻汉字的完整属性：拼音、注音、部首、总笔画、间架结构、五笔、统一码、笔顺编号等，并附基本解释与详细解释。每个汉字均可查看「含该字的成语」与「含该字的词语」，与成语词典、词语词典互为补充，适合语文学习、写作与起名用字参考。</p>
  </div>
</div>"""
    return page("汉语字典_汉字大全_汉字拼音笔画查询 - 好名网",
                "在线汉语字典：收录数万汉字，提供拼音、部首、笔画、五笔、释义等完整信息，支持部首与拼音检索。",
                "汉语字典,汉字大全,汉字查询,拼音查字,部首查字,笔画查询", body,
                '<script src="/view/haoiming/js/dict.7.js"></script>\n')

def render_radical_page(radical, entries, all_radicals):
    cards = "".join(zd_card(e) for e in entries)
    rad_nav = '<div class="dict-indexnav rad">' + "".join(
        f'<a href="/zidian/bs-{urllib.parse.quote(r)}.html">{esc(r)}</a>' for r in all_radicals) + "</div>"
    body = banner("部", f"{radical}部", f"{radical}部汉字共 {len(entries):,} 个 · 按拼音排序")
    body += f"""<div class="wrap">
  {crumb([('/zidian/', '汉语字典'), ('', f'{radical}部')])}
  {search_box('zidian', '输入汉字或拼音，如 好 / hao')}
  <div class="dict-list">{cards}</div>
  {rad_nav}
</div>"""
    return page(f"{radical}部的字_汉字部首查询 - 好名网",
                f"{radical}部汉字大全，共 {len(entries):,} 个汉字，含拼音、笔画与释义。",
                f"{radical}部的字,{radical}部,部首查字,汉字大全", body,
                '<script src="/view/haoiming/js/dict.7.js"></script>\n')

def render_pinyin_page(syl, entries):
    cards = "".join(zd_card(e) for e in entries)
    body = banner("音", f"拼音 {syl}", f"读音为 {syl} 的汉字共 {len(entries):,} 个")
    body += f"""<div class="wrap">
  {crumb([('/zidian/', '汉语字典'), ('', f'拼音 {syl}')])}
  {search_box('zidian', '输入汉字或拼音，如 好 / hao')}
  <div class="dict-list">{cards}</div>
</div>"""
    return page(f"拼音{syl}的汉字_同音字大全 - 好名网",
                f"读音为 {syl} 的汉字共 {len(entries):,} 个，含拼音、笔画与释义。",
                f"拼音{syl}的汉字,{syl}同音字,同音字查询", body,
                '<script src="/view/haoiming/js/dict.7.js"></script>\n')

def render_zd_detail(e, idx, rh):
    attrs = e.get("attrs", {})
    char = e.get("char", "")
    rad = attrs.get("部首", "")
    basic = rh(e.get("basic_html", ""))
    detail = rh(e.get("detail_html", ""))
    yiti = e.get("yiti", [])
    yt_html = ""
    if yiti:
        yt_html = '<div class="dict-rel">' + "".join(
            (f'<a href="/zidian/{s}.html">{esc(x["char"])}</a>' if (s := (idx["zd_slug"].get(("zidian", x["id"])))) else f'<span>{esc(x["char"])}</span>')
            for x in yiti) + "</div>"
    cy_list = idx["cy_by_zid"].get(e["id"], [])
    cd_list = idx["cd_by_char"].get(char, [])[:16]
    crumb_parts = [("/zidian/", "汉语字典")]
    if rad:
        crumb_parts.append((f"/zidian/bs-{urllib.parse.quote(rad)}.html", f"{rad}部"))
    crumb_parts.append(("", char))
    body = banner("字", char, (attrs.get("拼音", "") or "") + (f" · {rad}部" if rad else ""))
    body += f"""<div class="wrap">
  {crumb(crumb_parts)}
  {attr_rows(attrs, skip=('部首', '异体字'))}
  {sec('基本解释', f'<div class="dict-explain">{basic}</div>')}
  {sec('详细解释', f'<div class="dict-explain">{detail}</div>')}
  {sec('异体字', yt_html)}
  {sec(f'含「{char}」的成语', rel_links(cy_list, 'chengyu', 30))}
  {sec(f'含「{char}」的词语', rel_links(cd_list, 'cidian', 16))}
  <div class="rel-box dict-sec dict-intro">
    <h3>{esc(char)}字的意思</h3>
    <p>{esc(char)}（拼音：{esc(attrs.get('拼音', ''))}）{esc(trunc(strip_tags(basic), 100))}。本页提供该字的部首、笔画、结构、五笔编码及详细释义，并列出包含该字的成语与词语，可与<a href="/chengyu/">成语词典</a>、<a href="/cidian/">词语词典</a>互相参照。</p>
  </div>
</div>"""
    py = attrs.get("拼音", "")
    return page(f"{char}_{py}_汉字意思笔画查询 - 好名网",
                f"{char}字什么意思？{trunc(strip_tags(basic), 60)}含拼音、部首、笔画、五笔与详细释义。",
                f"{char}字的意思,{char}怎么读,{char}的笔画,{char}字,汉字查询", body)

def render_cd_home(cd, idx, featured):
    letters = sorted(idx["cd_letter"])
    letter_bar = '<div class="dict-letters">' + "".join(
        f'<a href="/cidian/zm-{l}.html">{l.upper()}</a>' for l in letters) + "</div>"
    cards = "".join(cd_card(e) for e in featured)
    body = banner("词", "词语词典", f"收录词语 {len(cd):,} 条 · 释义引证 · 分字解释 · 造句示例")
    body += f"""<div class="wrap">
  {search_box('cidian', '输入词语或拼音，如 美好 / meihao')}
  <div class="dict-stats">
    <div class="dict-stat"><b>{len(cd):,}</b><span>词语总数</span></div>
    <div class="dict-stat"><b>{len(letters)}</b><span>拼音首字母</span></div>
  </div>
  <div class="dict-zone-links">
    <a href="/chengyu/" class="dict-zone-link"><b>成语词典</b><span>查成语释义与出处</span></a>
    <a href="/zidian/" class="dict-zone-link"><b>汉语字典</b><span>查汉字释义与属性</span></a>
  </div>
  {sec('按拼音首字母查找', letter_bar, h2=False)}
  {sec('常用词语', '<div class="dict-grid">' + cards + '</div>', h2=False)}
  <div class="rel-box dict-sec dict-intro">
    <h3>关于词语词典</h3>
    <p>词语词典收录现代汉语常用词语，提供拼音、词性、释义、引证解释与国语辞典释义，并附分字解释与造句示例。每个单字均可链接到汉语字典查看详细属性，适合语文学习、写作与词汇积累。</p>
  </div>
</div>"""
    return page("词语词典_词语大全_词语解释与造句 - 好名网",
                "收录数万条汉语词语，提供拼音、词性、释义、引证与造句示例，支持按拼音首字母检索。",
                "词语词典,词语大全,词语解释,造句,汉语词汇", body,
                '<script src="/view/haoiming/js/dict.7.js"></script>\n')

def render_cd_detail(e, idx, rh, related):
    attrs = e.get("attrs", {})
    rows = []
    for k, v in attrs.items():
        if isinstance(v, list):
            links = []
            for x in v:
                s = idx["cd_slug"].get(("cidian", x["id"]))
                links.append(f'<a href="/cidian/{s}.html">{esc(x["name"])}</a>' if s else esc(x["name"]))
            rows.append(f'<div class="dict-attr"><i>{esc(k)}</i><span class="dict-rel">' + "".join(links) + "</span></div>")
        elif v:
            rows.append(f'<div class="dict-attr"><i>{esc(k)}</i><span>{esc(v)}</span></div>')
    attrs_html = '<div class="dict-attrs">' + "".join(rows) + "</div>" if rows else ""
    ch_html = char_cards(e.get("chars", []), lambda eid, site: idx["zd_slug"].get((site, eid)))
    zj = e.get("zaoju", [])
    zj_html = '<ul class="dict-zaoju">' + "".join(f"<li>{esc(x)}</li>" for x in zj[:8]) + "</ul>" if zj else ""
    letter = first_letter(e)
    body = banner("词", e["name"], e.get("pinyin", ""))
    body += f"""<div class="wrap">
  {crumb([('/cidian/', '词语词典'), (f'/cidian/zm-{letter}.html', letter.upper()), ('', e['name'])])}
  {sec('词语属性', attrs_html)}
  {sec('词语解释', f'<div class="dict-explain">{rh(e.get("jieshi_html", ""))}</div>')}
  {sec('引证解释', f'<div class="dict-explain">{rh(e.get("yinzheng_html", ""))}</div>')}
  {sec('国语辞典', f'<div class="dict-explain">{rh(e.get("guoyu_html", ""))}</div>')}
  {sec('分字解释', ch_html)}
  {sec('造句示例', zj_html)}
  {sec('相关词语', rel_links(related, 'cidian', 12))}
  <div class="rel-box dict-sec dict-intro">
    <h3>{esc(e['name'])}</h3>
    <p>{esc(e['name'])}（拼音：{esc(e.get('pinyin', ''))}）{esc(trunc(strip_tags(e.get('jieshi_html', '')), 100))}。本页整理该词的释义、引证解释、分字解释与造句示例，单字可链接至<a href="/zidian/">汉语字典</a>查看详细属性。</p>
  </div>
</div>"""
    return page(f"{e['name']}_{e.get('pinyin','')}_词语解释造句 - 好名网",
                f"{e['name']}是什么意思？{trunc(strip_tags(e.get('jieshi_html', '')), 60)}含拼音、引证解释与造句。",
                f"{e['name']},{e['name']}的意思,{e['name']}造句,词语解释", body)

# ============================================================
#  主流程
# ============================================================
HOT_CY = ["一举两得", "守株待兔", "亡羊补牢", "掩耳盗铃", "画蛇添足", "杯弓蛇影",
          "刻舟求剑", "滥竽充数", "自相矛盾", "买椟还珠", "拔苗助长", "叶公好龙",
          "邯郸学步", "卧薪尝胆", "破釜沉舟", "四面楚歌", "指鹿为马", "望梅止渴",
          "三顾茅庐", "鞠躬尽瘁", "闻鸡起舞", "凿壁偷光", "悬梁刺股", "囫囵吞枣",
          "对牛弹琴", "狐假虎威", "井底之蛙", "黔驴技穷", "塞翁失马", "愚公移山",
          "精卫填海", "夸父追日", "天衣无缝", "水落石出", "百发百中", "熟能生巧"]
HOT_ZD = list("的一是不了人我在有他这为之大来以个中上们到说国和地也子时道出而要于就下得可你年生自会那后能对着事其里所去行过家十用发天如然作方成者多日都三小军二无同么经法当起与好看学进种将还分此心前面")
HOT_CD = ["美好", "幸福", "快乐", "温暖", "善良", "智慧", "勇敢", "诚信", "勤奋",
          "自由", "和平", "希望", "梦想", "成功", "失败", "经验", "教训", "友谊",
          "爱情", "亲情", "恩情", "风采", "才华", "英俊", "漂亮", "可爱", "温柔",
          "优雅", "高尚", "纯洁", "真诚", "热情", "冷静", "坚强", "聪明"]

def featured_pick(hot_names, entries, name_key, n=24):
    by_name = {}
    for e in entries:
        nm = e.get(name_key, "")
        if nm and nm not in by_name:
            by_name[nm] = e
    picked, seen = [], set()
    for nm in hot_names:
        if nm in by_name and by_name[nm]["slug"] not in seen:
            picked.append(by_name[nm]); seen.add(by_name[nm]["slug"])
    rng = random.Random(42)
    rest = [e for e in entries if e["slug"] not in seen]
    rng.shuffle(rest)
    for e in rest:
        if len(picked) >= n:
            break
        picked.append(e); seen.add(e["slug"])
    return picked

def related_pick(e, pool, n=12):
    rng = random.Random(zlib.crc32(e["slug"].encode()))
    cand = [x for x in pool if x["slug"] != e["slug"]]
    rng.shuffle(cand)
    return cand[:n]

def main():
    print("加载数据 ...")
    cy, cd, zd = load()
    print(f"成语 {len(cy):,} / 词典 {len(cd):,} / 字典 {len(zd):,}")
    slug_by_id = build_slugs(cy, cd, zd)
    idx = build_indexes(cy, cd, zd)
    idx["zd_slug"] = slug_by_id
    idx["cd_slug"] = slug_by_id
    rh = make_html_rewriter(slug_by_id)
    sl = lambda eid, site: slug_by_id.get((site, eid))

    for d in ("chengyu", "zidian", "cidian"):
        os.makedirs(f"{BASE}/{d}", exist_ok=True)
    n = 0

    # 专区首页
    open(f"{BASE}/chengyu/index.html", "w", encoding="utf-8").write(
        render_cy_home(cy, idx, featured_pick(HOT_CY, list(cy.values()), "name", 24)))
    open(f"{BASE}/zidian/index.html", "w", encoding="utf-8").write(
        render_zd_home(zd, idx, featured_pick(HOT_ZD, list(zd.values()), "char", 32)))
    open(f"{BASE}/cidian/index.html", "w", encoding="utf-8").write(
        render_cd_home(cd, idx, featured_pick(HOT_CD, list(cd.values()), "name", 24)))
    n += 3
    print("[home] 3 页")

    # 字母索引
    for letter, entries in sorted(idx["cy_letter"].items()):
        open(f"{BASE}/chengyu/zm-{letter}.html", "w", encoding="utf-8").write(
            render_letter_page("chengyu", letter, entries, sorted(idx["cy_letter"])))
    for letter, entries in sorted(idx["cd_letter"].items()):
        open(f"{BASE}/cidian/zm-{letter}.html", "w", encoding="utf-8").write(
            render_letter_page("cidian", letter, entries, sorted(idx["cd_letter"])))
    n += len(idx["cy_letter"]) + len(idx["cd_letter"])
    print(f"[letter] {len(idx['cy_letter']) + len(idx['cd_letter'])} 页")

    # 部首页 / 拼音页
    all_radicals = sorted(idx["radical"])
    for rad, entries in idx["radical"].items():
        open(f"{BASE}/zidian/bs-{rad}.html", "w", encoding="utf-8").write(
            render_radical_page(rad, entries, all_radicals))
    for syl, entries in idx["syllable"].items():
        open(f"{BASE}/zidian/py-{syl}.html", "w", encoding="utf-8").write(
            render_pinyin_page(syl, entries))
    n += len(idx["radical"]) + len(idx["syllable"])
    print(f"[radical/pinyin] {len(idx['radical']) + len(idx['syllable'])} 页")

    # 详情页
    pool_cy = list(cy.values()); pool_cd = list(cd.values())
    for e in pool_cy:
        open(f"{BASE}/chengyu/{e['slug']}.html", "w", encoding="utf-8").write(
            render_cy_detail(e, idx, sl, rh, related_pick(e, pool_cy)))
    print(f"[chengyu detail] {len(pool_cy):,} 页")
    for e in pool_cd:
        open(f"{BASE}/cidian/{e['slug']}.html", "w", encoding="utf-8").write(
            render_cd_detail(e, idx, rh, related_pick(e, pool_cd)))
    print(f"[cidian detail] {len(pool_cd):,} 页")
    for e in zd.values():
        open(f"{BASE}/zidian/{e['slug']}.html", "w", encoding="utf-8").write(
            render_zd_detail(e, idx, rh))
    print(f"[zidian detail] {len(zd):,} 页")
    n += len(pool_cy) + len(pool_cd) + len(zd)

    # 搜索索引
    si_cy = [[e["name"], py_ascii(e.get("pinyin", "")).replace("-", ""), e["slug"]] for e in pool_cy]
    si_cd = [[e["name"], py_ascii(e.get("pinyin", "")).replace("-", ""), e["slug"]] for e in pool_cd]
    si_zd = [[e.get("char", ""), py_ascii(e.get("py", "")).replace("-", ""), e["slug"]] for e in zd.values()]
    json.dump(si_cy, open(f"{BASE}/chengyu/search-index.json", "w", encoding="utf-8"), ensure_ascii=False, separators=(",", ":"))
    json.dump(si_cd, open(f"{BASE}/cidian/search-index.json", "w", encoding="utf-8"), ensure_ascii=False, separators=(",", ":"))
    json.dump(si_zd, open(f"{BASE}/zidian/search-index.json", "w", encoding="utf-8"), ensure_ascii=False, separators=(",", ":"))
    print("[search-index] 3 个 JSON")

    # sitemap
    urls = [f"{DOMAIN}/chengyu/", f"{DOMAIN}/zidian/", f"{DOMAIN}/cidian/"]
    urls += [f"{DOMAIN}/chengyu/zm-{l}.html" for l in idx["cy_letter"]]
    urls += [f"{DOMAIN}/cidian/zm-{l}.html" for l in idx["cd_letter"]]
    urls += [f"{DOMAIN}/zidian/bs-{urllib.parse.quote(r)}.html" for r in idx["radical"]]
    urls += [f"{DOMAIN}/zidian/py-{s}.html" for s in idx["syllable"]]
    urls += [f"{DOMAIN}/chengyu/{e['slug']}.html" for e in pool_cy]
    urls += [f"{DOMAIN}/cidian/{e['slug']}.html" for e in pool_cd]
    urls += [f"{DOMAIN}/zidian/{e['slug']}.html" for e in zd.values()]
    sm = ['<?xml version="1.0" encoding="UTF-8"?>',
          '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">']
    sm += [f"<url><loc>{u}</loc></url>" for u in urls]
    sm.append("</urlset>")
    # 2026-10-25：dict 子站 sitemap 不再写入 web 根目录（历史版本声明了外站 URL，
    # 污染 robots.txt 的 sitemap 信任度）；产物归档到 scripts/archive/ 备查
    out_dir = os.path.join(os.path.dirname(os.path.abspath(__file__)), "archive")
    os.makedirs(out_dir, exist_ok=True)
    open(os.path.join(out_dir, "sitemap-dict.xml"), "w", encoding="utf-8").write("\n".join(sm))
    print(f"[sitemap] {len(urls):,} URL -> archive/sitemap-dict.xml（不暴露公网）")
    print(f"完成：共生成 {n + 4:,} 个页面文件")

if __name__ == "__main__":
    main()
