import asyncio
from playwright.async_api import async_playwright

async def main():
    async with async_playwright() as p:
        browser = await p.chromium.launch()
        page = await browser.new_page(viewport={"width": 1280, "height": 900})
        # 首页：滚动到热门用字区块
        await page.goto("http://127.0.0.1:8090/", wait_until="networkidle")
        await page.wait_for_timeout(1000)
        el = await page.query_selector("text=热门起名用字")
        if el: await el.scroll_into_view_if_needed()
        await page.wait_for_timeout(600)
        await page.screenshot(path="/workspace/seo_fixes/home_hot.png")
        # kxzd：热度索引区块
        await page.goto("http://127.0.0.1:8090/view/haoiming/tool/kxzd.html", wait_until="networkidle")
        await page.wait_for_timeout(1000)
        el = await page.query_selector("text=起名用字热度索引")
        if el: await el.scroll_into_view_if_needed()
        await page.wait_for_timeout(600)
        await page.screenshot(path="/workspace/seo_fixes/kxzd_hot.png")
        # faq 页
        await page.goto("http://127.0.0.1:8090/view/haoiming/tool/faq.html", wait_until="networkidle")
        await page.wait_for_timeout(1000)
        await page.screenshot(path="/workspace/seo_fixes/faq_top.png")
        # 百家姓（预渲染后姓氏索引）
        await page.goto("http://127.0.0.1:8090/view/haoiming/tool/baijiaxing.html", wait_until="networkidle")
        await page.wait_for_timeout(1000)
        await page.screenshot(path="/workspace/seo_fixes/baijiaxing.png")
        await browser.close()
        print("done")

asyncio.run(main())
