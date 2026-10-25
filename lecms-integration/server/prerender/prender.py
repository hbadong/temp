#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
SEO 预渲染服务（方案 A）
--------------------------------
搜索引擎爬虫（Baiduspider / Googlebot 等）抓取动态参数页
  char.html?c=X  /  surname.html?c=X  /  mingzi.html?name=X
时，前端内容由 JS 按参数渲染，爬虫抓到的是空壳。
本服务用无头 Chromium 把页面真实渲染一遍，返回完整 outerHTML，
与用户端看到的页面 100% 一致（复用同一套前端代码，无需重写逻辑）。

接口： GET /render?url=<绝对地址>  ->  返回渲染后的 HTML

关键实现点：
  - Playwright 同步 API 必须在「创建浏览器的同一线程」调用，否则报
    "cannot switch to a different thread"。因此浏览器由【专用后台线程】独占，
    Flask 请求线程只负责把任务放入队列并等待结果，绝不触碰浏览器对象。
  - 起 N 个 worker 线程（各持一个浏览器），实现有限并发、互不阻塞。
  - 任何异常都返回 5xx，由前端 router 回退到静态空壳（不比现在更差）。
"""
import queue
import sys
import threading
from concurrent.futures import Future

from flask import Flask, request, Response
from playwright.sync_api import sync_playwright

app = Flask(__name__)

N_WORKERS = 2
_JOB_Q = queue.Queue()

# 动态/JS 渲染页 -> 详情容器选择器（渲染完成标志）
# 2026-10-25：新增无参数内容页（faq/shengxiao/典籍/百家姓/文库）——
# 这些页面的核心内容（FAQ 问答、生肖专题、名句库、姓氏索引）纯 JS 渲染，
# 静态外壳对爬虫为空，预渲染后内容与 JSON-LD 结构化数据才可被检索。
DETAIL_SELECTORS = {
    "char.html": "#char-detail",
    "surname.html": "#surname-detail",
    "mingzi.html": "#mingzi-detail",
    "faq.html": "#faq-list",
    "shengxiao.html": "#sx-result",
    "poems.html": "#poem-list",
    "shijing.html": "#poem-list",
    "chuci.html": "#poem-list",
    "tangshi.html": "#poem-list",
    "songci.html": "#poem-list",
    "baijiaxing.html": "#bjx-grid",
    "lib.html": "#lib-grid",
}


def worker():
    """独占一个 Chromium 浏览器，循环处理渲染任务。"""
    try:
        with sync_playwright() as pw:
            browser = pw.chromium.launch(
                args=["--no-sandbox", "--disable-dev-shm-usage", "--disable-gpu"]
            )
            ctx = browser.new_context(
                user_agent=(
                    "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 "
                    "(KHTML, like Gecko) Chrome/120.0 PrerenderBot/1.0"
                ),
                viewport={"width": 1200, "height": 900},
            )
            while True:
                item = _JOB_Q.get()
                if item is None:
                    _JOB_Q.task_done()
                    break
                fut, url = item
                try:
                    fut.set_result((200, _render(ctx, url)))
                except Exception as e:  # pragma: no cover
                    sys.stderr.write("render error for %s: %s\n" % (url, e))
                    fut.set_result((500, "render error: %s" % e))
                finally:
                    _JOB_Q.task_done()
    except Exception as e:  # pragma: no cover
        sys.stderr.write("worker init failed: %s\n" % e)


def _render(ctx, url):
    selector = "body"
    for key, sel in DETAIL_SELECTORS.items():
        if key in url:
            selector = sel
    page = ctx.new_page()
    try:
        page.goto(url, wait_until="domcontentloaded", timeout=20000)
        try:
            page.wait_for_function(
                (
                    "() => { const el = document.querySelector(%r);"
                    " return el && (el.innerText || '').trim().length > 30; }"
                    % selector
                ),
                timeout=15000,
            )
        except Exception:
            pass  # 超时也尽量返回当前 DOM
        page.wait_for_timeout(300)
        return page.content()
    finally:
        page.close()


def pick_and_submit(url):
    fut = Future()
    _JOB_Q.put((fut, url))
    status, body = fut.result(timeout=50)
    return status, body


@app.route("/render")
def render():
    url = request.args.get("url", "").strip()
    if not url or not (url.startswith("http://") or url.startswith("https://")):
        return ("missing or invalid url", 400)
    try:
        status, body = pick_and_submit(url)
    except Exception as e:
        return ("render timeout/error: %s" % e, 504)
    if status != 200:
        return (body, 500)
    return Response(body, mimetype="text/html; charset=utf-8")


@app.route("/healthz")
def healthz():
    return ("ok", 200)


if __name__ == "__main__":
    sys.stderr.write("prender 启动中（%d workers）...\n" % N_WORKERS)
    for _ in range(N_WORKERS):
        t = threading.Thread(target=worker, daemon=True)
        t.start()
    app.run(host="127.0.0.1", port=8099, threaded=True)
