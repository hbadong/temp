import asyncio
from playwright.async_api import async_playwright

async def main():
    async with async_playwright() as p:
        browser = await p.chromium.launch()
        page = await browser.new_page(viewport={"width": 1280, "height": 900})
        # 1) 梓 详情页（按钮区）
        await page.goto("http://127.0.0.1:8090/view/haoiming/tool/char.html?c=%E6%A2%93", wait_until="networkidle")
        await page.wait_for_timeout(1200)
        await page.screenshot(path="/workspace/mobile_audit/fixes/fix_qm_char.png")
        # 2) 起名结果页（含梓的名字）
        await page.goto("http://127.0.0.1:8090/view/haoiming/tool/naming.html?surname=%E6%9D%8E&gender=1&date=2026-01-01&fixed=%E6%A2%93", wait_until="networkidle")
        await page.wait_for_timeout(1500)
        await page.screenshot(path="/workspace/mobile_audit/fixes/fix_qm_result.png", full_page=False)
        # 3) 移动端
        await page.set_viewport_size({"width": 375, "height": 812})
        await page.goto("http://127.0.0.1:8090/view/haoiming/tool/char.html?c=%E8%96%87", wait_until="networkidle")
        await page.wait_for_timeout(1200)
        await page.screenshot(path="/workspace/mobile_audit/fixes/fix_qm_mobile.png")
        await browser.close()
        print("截图完成")

asyncio.run(main())
