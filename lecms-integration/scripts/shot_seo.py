import asyncio
from playwright.async_api import async_playwright

PAGES = [
    ("home",      "http://127.0.0.1:8090/", 1280, 900),
    ("kxzd",      "http://127.0.0.1:8090/view/haoiming/tool/kxzd.html", 1280, 900),
    ("char_lin",  "http://127.0.0.1:8090/view/haoiming/tool/char.html?c=%E6%9E%97", 1280, 900),
    ("faq",       "http://127.0.0.1:8090/view/haoiming/tool/faq.html", 1280, 900),
    ("surname_wx","http://127.0.0.1:8090/view/haoiming/tool/surname.html?c=%E7%8E%8B&wx=%E6%9C%A8", 1280, 900),
    ("mobile_home","http://127.0.0.1:8090/", 375, 812),
]

async def main():
    async with async_playwright() as p:
        browser = await p.chromium.launch()
        for name, url, w, h in PAGES:
            page = await browser.new_page(viewport={"width": w, "height": h})
            await page.goto(url, wait_until="networkidle")
            await page.wait_for_timeout(1200)
            await page.screenshot(path=f"/workspace/seo_fixes/{name}.png", full_page=False)
            await page.close()
            print(f"{name} OK")
        await browser.close()

asyncio.run(main())
