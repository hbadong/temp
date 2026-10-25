#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
M2 渲染优化验证：JS 错误 / 异步 CSS 生效 / 截图（首屏 + 工具页）
用法：python3 verify_m2.py
"""
import asyncio
from playwright.async_api import async_playwright

BASE = "http://127.0.0.1:8090"
OUT = "/workspace/lecms-integration/seo_fixes"

async def check_page(page, url, name, shot=True):
    errors = []
    page.on("pageerror", lambda e: errors.append(f"pageerror: {e}"))
    page.on("console", lambda m: errors.append(f"console.error: {m.text}") if m.type == "error" else None)
    await page.goto(url, wait_until="networkidle")
    await page.wait_for_timeout(800)
    # 异步 CSS 是否已切换回 all
    css_ok = await page.evaluate(
        "Array.from(document.styleSheets).some(s => (s.href||'').includes('style.7.css') && !s.media || s.media && s.media.mediaText !== 'print')"
    )
    css_href_all = await page.evaluate(
        "Array.from(document.querySelectorAll('link[rel=stylesheet]')).map(l => l.href + ' media=' + l.media).join(' | ')"
    )
    # 首屏关键元素是否存在（渲染成功的标志）
    has_header = await page.evaluate("!!document.querySelector('#site-header .header-inner')")
    if shot:
        await page.screenshot(path=f"{OUT}/m2_{name}.png")
    print(f"[{name}] url={url}")
    print(f"  JS/console errors: {len(errors)}")
    for e in errors[:5]:
        print(f"    - {e[:150]}")
    print(f"  style.7.css 最终 media: {css_href_all[:200]}")
    print(f"  #site-header 渲染: {has_header}")
    return len(errors) == 0 and has_header

async def main():
    async with async_playwright() as p:
        browser = await p.chromium.launch()
        page = await browser.new_page(viewport={"width": 1280, "height": 900})
        results = []
        results.append(await check_page(page, f"{BASE}/", "home_top"))
        # 首页滚动到底（懒加载区块与 footer）
        await page.evaluate("window.scrollTo(0, document.body.scrollHeight)")
        await page.wait_for_timeout(600)
        await page.screenshot(path=f"{OUT}/m2_home_bottom.png")
        results.append(await check_page(page, f"{BASE}/view/haoiming/tool/char.html?c=%E6%9E%97", "char"))
        results.append(await check_page(page, f"{BASE}/view/haoiming/tool/kxzd.html", "kxzd"))
        # 移动端
        mp = await browser.new_page(viewport={"width": 375, "height": 812})
        results.append(await check_page(mp, f"{BASE}/", "home_mobile"))
        await mp.screenshot(path=f"{OUT}/m2_home_mobile.png")
        await browser.close()
        print()
        print("ALL PASS" if all(results) else "FAIL: " + str(results))

asyncio.run(main())
