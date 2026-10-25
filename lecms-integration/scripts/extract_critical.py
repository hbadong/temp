#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
M2 关键 CSS 提取器：从 style.7.css 抽取首屏关键规则，生成可内联的 critical.css。
用法：python3 extract_critical.py <src.css> <out.css>
原则：只提取「首屏必然渲染」的选择器集合（头部/横幅/首页表单/面包屑/页脚），
      其余规则留由异步样式表承担；宁可少提（回落系统排版）也不误提（体积失控）。
"""
import re
import sys

# 首屏关键选择器前缀（token 级匹配：选择器片段以这些前缀开头）
CRITICAL_PREFIXES = [
    ":root", "*", "body", "a ", "a,", "a:", "a{", "ul ", "ul,", "ul{", "ol ", "ol,",
    "html", "img", "h1", "h2", "h3", ".wrap", ".serif",
    "#site-header", "#tool-header",
    ".header-inner", ".logo", ".nav ", ".nav.", ".nav,", ".nav{", ".nav:", ".nav[",
    ".hot-quick", ".nav-toggle", ".nav-drawer",
    ".ink-banner", ".banner-inner", ".seal",
    ".hero", ".form-card", "#home-tabs", "#home-panels",
    ".field", ".frow", ".chip", ".gender-chips", ".mode-chips",
    ".btn-primary", ".form-tip", ".empty",
    ".guess-sec", ".guess-links",
    ".crumb",
    "#site-footer", "#tool-footer",
]

def selector_is_critical(sel: str) -> bool:
    """选择器（可能逗号分隔多选）任一片段命中关键前缀即保留"""
    for part in sel.split(","):
        p = part.strip()
        if not p:
            continue
        for pre in CRITICAL_PREFIXES:
            if p == pre.rstrip(" {,:[") or p.startswith(pre):
                return True
    return False

def strip_comments(css: str) -> str:
    return re.sub(r"/\*.*?\*/", "", css, flags=re.S)

def split_rules(css: str):
    """顶层切分为 (prelude, body) 列表，prelude 为 '@media...' 或选择器"""
    css = strip_comments(css)
    rules = []
    i, n = 0, len(css)
    buf = []
    while i < n:
        ch = css[i]
        if ch == "{":
            prelude = "".join(buf).strip()
            # 找匹配右括号（支持嵌套）
            j = i + 1
            d = 1
            while j < n and d > 0:
                if css[j] == "{":
                    d += 1
                elif css[j] == "}":
                    d -= 1
                j += 1
            body = css[i + 1:j - 1]
            rules.append((prelude, body))
            buf = []
            i = j
            continue
        buf.append(ch)
        i += 1
    return rules

def filter_css(css: str, out_indent: str = "") -> str:
    """递归过滤：普通规则按选择器；@media 块递归过滤，空媒体查询丢弃"""
    out = []
    for prelude, body in split_rules(css):
        if prelude.startswith("@media"):
            inner = filter_css(body, out_indent + "  ")
            if inner.strip():
                out.append(f"{prelude}{{\n{inner}\n{out_indent}}}")
        elif prelude.startswith("@"):
            # @font-face/@keyframes/@supports 等：非首屏关键，跳过
            continue
        else:
            if selector_is_critical(prelude):
                # 压缩单规则内部换行为单行（内联体积优先）
                body_flat = re.sub(r"\s*\n\s*", " ", body.strip())
                out.append(f"{prelude}{{{body_flat}}}")
    return "\n".join(out)

def main():
    src, dst = sys.argv[1], sys.argv[2]
    css = open(src, encoding="utf-8").read()
    critical = filter_css(css)
    header = "/* 首屏关键 CSS（M2 内联，由 scripts/extract_critical.py 生成，勿手改） */\n"
    open(dst, "w", encoding="utf-8").write(header + critical + "\n")
    print(f"rules kept: {critical.count('}')}")
    print(f"size: {len(header + critical)} bytes (src {len(css)} bytes)")

if __name__ == "__main__":
    main()
