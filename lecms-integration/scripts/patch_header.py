#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
M2 inc-header.htm 改造：关键 CSS 内联 + 样式表异步化 + JS defer 化 + 内联脚本时序修复
用法：python3 patch_header.py <inc-header.htm> <critical.css>
"""
import re
import sys

def main():
    header_path, critical_path = sys.argv[1], sys.argv[2]
    html = open(header_path, encoding="utf-8").read()
    critical = open(critical_path, encoding="utf-8").read().strip()

    # ---- 1. 样式表：内联 critical + 全量异步加载（print→all 技巧）+ noscript 回退 ----
    css_link_re = re.compile(
        r'\t<link rel="stylesheet" href="\{\$cfg\[tpl\]\}css/style\.7\.css\?v=[^"]*" type="text/css" media="all"/>\n'
    )
    m = css_link_re.search(html)
    if not m:
        print("ERROR: CSS link 未匹配"); sys.exit(1)
    css_href = re.search(r'href="([^"]*style\.7\.css[^"]*)"', m.group(0)).group(1)
    replacement = (
        "\t<style>\n" + critical + "\n\t</style>\n"
        '\t<link rel="stylesheet" href="' + css_href + '" type="text/css" media="print" onload="this.media=\'all\'"/>\n'
        '\t<noscript><link rel="stylesheet" href="' + css_href + '" type="text/css" media="all"/></noscript>\n'
    )
    html = html[:m.start()] + replacement + html[m.end():]

    # ---- 2. JS defer 化（data/shuowen 补版本号；engine/main 已有）----
    html = html.replace(
        '<script src="{$cfg[tpl]}js/data.7.js"></script>',
        '<script src="{$cfg[tpl]}js/data.7.js?v=20261025b" defer></script>'
    )
    html = html.replace(
        '<script src="{$cfg[tpl]}js/shuowen.7.js"></script>',
        '<script src="{$cfg[tpl]}js/shuowen.7.js?v=20261022c" defer></script>'
    )
    html = html.replace(
        '<script src="{$cfg[tpl]}js/engine.7.js?v=20261024f"></script>',
        '<script src="{$cfg[tpl]}js/engine.7.js?v=20261024f" defer></script>'
    )
    html = html.replace(
        '<script src="{$cfg[tpl]}js/main.7.js?v=20261025d"></script>',
        '<script src="{$cfg[tpl]}js/main.7.js?v=20261025d" defer></script>'
    )

    # ---- 3. hot-quick 内联脚本：依赖 window.HM_DATA（defer 后未就绪），包 DOMContentLoaded ----
    hq_old = re.search(
        r'<script>\n\(function\(\)\{\n\tvar tg = document\.getElementById.*?\}\)\(\);\n</script>',
        html, re.S
    )
    if not hq_old:
        print("ERROR: hot-quick 脚本块未匹配"); sys.exit(1)
    block = hq_old.group(0)
    inner = block[len("<script>\n"):-len("\n</script>")]
    new_block = (
        "<script>\n"
        "document.addEventListener('DOMContentLoaded', function(){\n"
        + inner
        + "\n});\n</script>"
    )
    html = html[:hq_old.start()] + new_block + html[hq_old.end():]

    open(header_path, "w", encoding="utf-8").write(html)
    print("OK: inc-header.htm 改造完成")
    print("  - critical CSS 内联:", len(critical), "bytes")
    print("  - 4 个 JS defer + data/shuowen 版本号")
    print("  - hot-quick 脚本 DOMContentLoaded 包裹")

if __name__ == "__main__":
    main()
