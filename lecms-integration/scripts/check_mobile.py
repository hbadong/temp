import asyncio
from playwright.async_api import async_playwright

PAGES = [
    ("home", "http://127.0.0.1:8090/"),
    ("kxzd", "http://127.0.0.1:8090/view/haoiming/tool/kxzd.html"),
    ("search", "http://127.0.0.1:8090/view/haoiming/tool/search.html"),
    ("char", "http://127.0.0.1:8090/view/haoiming/tool/char.html?c=%E6%9E%97"),
    ("faq", "http://127.0.0.1:8090/view/haoiming/tool/faq.html"),
]

async def main():
    async with async_playwright() as p:
        browser = await p.chromium.launch()
        page = await browser.new_page(viewport={"width": 375, "height": 812})
        for name, url in PAGES:
            await page.goto(url, wait_until="networkidle")
            await page.wait_for_timeout(900)
            overflow = await page.evaluate("""() => {
                const de = document.documentElement;
                const bad = [];
                document.querySelectorAll('*').forEach(el => {
                    const r = el.getBoundingClientRect();
                    if (r.width > 0 && (r.right > de.clientWidth + 2 || r.left < -2)) {
                        bad.push(el.tagName + '.' + (el.className || '').toString().slice(0, 40) + ' right=' + Math.round(r.right));
                    }
                });
                return { scrollW: de.scrollWidth, clientW: de.clientWidth, bad: bad.slice(0, 5) };
            }""")
            status = "OK" if overflow["scrollW"] <= overflow["clientW"] + 2 else "OVERFLOW"
            print(f"{name}: {status} (scrollW={overflow['scrollW']} clientW={overflow['clientW']}) {overflow['bad'] if overflow['bad'] else ''}")
        await browser.close()

asyncio.run(main())
