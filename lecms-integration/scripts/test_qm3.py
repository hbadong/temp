import asyncio, re
from playwright.async_api import async_playwright

async def main():
    async with async_playwright() as p:
        browser = await p.chromium.launch()
        page = await browser.new_page(viewport={"width": 1280, "height": 900})
        ok_all = True
        # 1) 库内字：按钮携带 fixed，结果含该字
        for ch, exp_g in [("梓", 1), ("轩", 1), ("薇", 2)]:
            await page.goto(f"http://127.0.0.1:8090/view/haoiming/tool/char.html?c={ch}", wait_until="networkidle")
            await page.wait_for_timeout(900)
            btn = await page.query_selector("text=用该字起名")
            href = await btn.get_attribute("href") if btn else ""
            print(f"[{ch}] href = {href}")
            good = f"fixed={ch}" in href and f"gender={exp_g}" in href
            print(f"    参数校验: {'✅' if good else '❌'} (期望 fixed={ch}&gender={exp_g})")
            ok_all &= good
            target = "http://127.0.0.1:8090/view/haoiming/tool/" + href
            await page.goto(target, wait_until="networkidle")
            await page.wait_for_timeout(1200)
            body = await page.inner_text("body")
            names = sorted(set(re.findall(r"李[\u4e00-\u9fff]{1,2}", body)))
            hit = [n for n in names if ch in n]
            good2 = len(hit) >= 4
            print(f"    结果含「{ch}」的名字({len(hit)}个): {hit[:8]} {'✅' if good2 else '❌'}")
            ok_all &= good2
        # 2) 库外字：降级布局 + 说明文字
        await page.goto("http://127.0.0.1:8090/view/haoiming/tool/char.html?c=%E7%BE%8E", wait_until="networkidle")
        await page.wait_for_timeout(900)
        body = await page.inner_text("body")
        has_note = "暂未收录进本站五行字库" in body
        print(f"[美] 降级布局说明: {'✅' if has_note else '❌'}")
        # 3) 爬虫预渲染路径
        await page.goto("http://127.0.0.1:8090/view/haoiming/tool/char.html?c=%E6%A2%93&__prerender=1", wait_until="networkidle")
        await page.wait_for_timeout(1500)
        html = await page.content()
        m = re.search(r'naming\.html\?[^"]*fixed[^"]*', html)
        print(f"[爬虫预渲染] fixed 参数: {'✅ ' + m.group(0)[:80] if m else '❌ 未找到'}")
        ok_all &= bool(m)
        print("=====", "全部通过 ✅" if ok_all else "存在失败项 ❌")
        await browser.close()

asyncio.run(main())
