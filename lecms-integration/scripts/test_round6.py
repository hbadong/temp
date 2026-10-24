import asyncio, re
from playwright.async_api import async_playwright

async def main():
    async with async_playwright() as p:
        browser = await p.chromium.launch()
        page = await browser.new_page(viewport={"width": 1280, "height": 900})
        ok_all = True

        # 1) 八字起名：结果卡片应带典籍出处 blockquote（「...」— 出处）
        await page.goto("http://127.0.0.1:8090/view/haoiming/tool/bazi.html", wait_until="networkidle")
        await page.wait_for_timeout(800)
        await page.fill("#xingbz", "李")
        await page.fill("#datebz", "2024-06-15")
        try:
            await page.select_option("#hourbz", index=6)  # 午时
        except Exception as e:
            print("时辰选择跳过:", e)
        await page.click("button[type=submit]")
        await page.wait_for_timeout(2500)
        body = await page.inner_text("body")
        quotes = re.findall(r"「[^」]{4,}」[—\-–]\s*\S", body)
        names = re.findall(r"李[\u4e00-\u9fff]{1,2}", body)
        good = len(quotes) >= 2
        print(f"[八字典籍出处] 引用条数: {len(quotes)} (期望>=2) {'✅' if good else '❌'}")
        for q in quotes[:4]:
            print("    ", q[:60])
        ok_all &= good

        # 2) 出处挂载无侵入：未命中典籍的名字不应出现空 blockquote
        html = await page.content()
        empty_bq = html.count("<blockquote></blockquote>") + html.count("<blockquote>「」")
        print(f"[空出处] 数量: {empty_bq} {'✅' if empty_bq == 0 else '❌'}")
        ok_all &= (empty_bq == 0)

        # 3) 姓氏×五行矩阵页渲染
        for url, kw in [
            ("http://127.0.0.1:8090/view/haoiming/tool/surname.html?c=%E7%8E%8B&wx=%E6%9C%A8", "王"),
            ("http://127.0.0.1:8090/view/haoiming/tool/surname.html?c=%E6%9D%8E&wx=%E9%87%91", "李"),
        ]:
            await page.goto(url, wait_until="networkidle")
            await page.wait_for_timeout(900)
            txt = await page.inner_text("body")
            good = kw in txt and "404" not in txt[:200] and len(txt) > 800
            print(f"[矩阵页 {url.split('?')[1]}] 内容长度 {len(txt)} {'✅' if good else '❌'}")
            ok_all &= good

        # 4) 真太阳时专题页
        await page.goto("http://127.0.0.1:8090/view/haoiming/tool/taiyang.html", wait_until="networkidle")
        await page.wait_for_timeout(600)
        txt = await page.inner_text("body")
        good = "真太阳时" in txt and "均时差" in txt
        print(f"[真太阳时专题] {'✅' if good else '❌'} (长度 {len(txt)})")
        ok_all &= good

        # 5) 页脚免责声明（双路渲染）
        await page.goto("http://127.0.0.1:8090/view/haoiming/tool/bazi.html", wait_until="networkidle")
        await page.wait_for_timeout(600)
        txt = await page.inner_text("body")
        good = "仅供娱乐参考" in txt
        print(f"[页脚免责声明-工具页] {'✅' if good else '❌'}")
        ok_all &= good

        print("=====", "全部通过 ✅" if ok_all else "存在失败项 ❌")
        await browser.close()

asyncio.run(main())
