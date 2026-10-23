import asyncio, re
from playwright.async_api import async_playwright

async def main():
    async with async_playwright() as p:
        browser = await p.chromium.launch()
        page = await browser.new_page(viewport={"width": 1280, "height": 900})
        errs = []
        page.on("pageerror", lambda e: errs.append(str(e)))
        page.on("console", lambda m: errs.append(m.text) if m.type == "error" else None)
        ok = True
        # A. 梓：完整链路
        await page.goto("http://127.0.0.1:8090/view/haoiming/tool/char.html?c=%E6%A2%93", wait_until="networkidle")
        await page.wait_for_timeout(1000)
        btn = await page.query_selector("text=用该字起名")
        href = await btn.get_attribute("href")
        a1 = "fixed=%E6%A2%93" in href and "gender=1" in href
        print(f"A1 按钮参数(fixed=梓&gender=1): {'✅' if a1 else '❌'}  {href}")
        ok &= a1
        await page.goto("http://127.0.0.1:8090/view/haoiming/tool/" + href, wait_until="networkidle")
        await page.wait_for_timeout(1400)
        body = await page.inner_text("body")
        names = sorted(set(re.findall(r"李[\u4e00-\u9fff]{1,2}", body)))
        hit = [n for n in names if "梓" in n]
        a2 = len(hit) >= 4
        print(f"A2 结果含梓({len(hit)}个): {'✅' if a2 else '❌'}  {hit[:6]}")
        ok &= a2
        a3 = "含指定用字「梓」" in body
        print(f"A3 理由措辞(含指定用字): {'✅' if a3 else '❌'}")
        ok &= a3
        # B. 薇：女字默认 gender=2
        await page.goto("http://127.0.0.1:8090/view/haoiming/tool/char.html?c=%E8%96%87", wait_until="networkidle")
        await page.wait_for_timeout(1000)
        href2 = await (await page.query_selector("text=用该字起名")).get_attribute("href")
        b1 = "gender=2" in href2 and "fixed=%E8%96%87" in href2
        print(f"B1 女字默认(gender=2&fixed=薇): {'✅' if b1 else '❌'}  {href2}")
        ok &= b1
        # C. 库外字降级说明仍在
        await page.goto("http://127.0.0.1:8090/view/haoiming/tool/char.html?c=%E7%BE%8E", wait_until="networkidle")
        await page.wait_for_timeout(1000)
        body3 = await page.inner_text("body")
        c1 = "暂未收录进本站五行字库" in body3
        print(f"C1 库外字说明: {'✅' if c1 else '❌'}")
        ok &= c1
        # D. 控制台无错误
        print(f"D1 JS 错误: {'✅ 无' if not errs else '❌ ' + '; '.join(errs[:3])}")
        ok &= not errs
        print("=====", "全部通过 ✅" if ok else "存在失败 ❌")
        await browser.close()

asyncio.run(main())
