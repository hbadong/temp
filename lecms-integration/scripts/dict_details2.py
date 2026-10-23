#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
dict_details2.py — 详情抓取（自适应限速版）
- 令牌桶限速（默认 3 req/s），403 时全局退避并降速
- 磁盘缓存复用；多轮补抓失败项
- 抓取完成后解析输出 chengyu.json / cidian.json / zidian.json
"""
import os, re, sys, json, time, threading, random
import concurrent.futures as cf
import requests
from dict_crawl import parse_chengyu, parse_zidian, parse_cidian, SITES, OUT

UA = {"User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Safari/537.36",
      "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
      "Accept-Language": "zh-CN,zh;q=0.9"}
CACHE = "/root/.codebuddy/artifact/2f8a7d30-9c21-4f0b-1d51-5b9fdf21a000/dict_cache"

# ---------- 令牌桶 ----------
class RateLimiter:
    def __init__(self, rate):
        self.rate = rate
        self.lock = threading.Lock()
        self.next_t = 0.0
    def acquire(self):
        with self.lock:
            now = time.time()
            wait = self.next_t - now
            if wait > 0:
                self.next_t += 1.0 / self.rate
            else:
                self.next_t = now + 1.0 / self.rate
        if wait > 0:
            time.sleep(wait)
    def slow_down(self, factor=0.6):
        with self.lock:
            self.rate = max(0.8, self.rate * factor)
    def speed_up(self):
        with self.lock:
            self.rate = min(5.0, self.rate * 1.15)

RL = RateLimiter(3.0)
BAN_EVENT = threading.Event()   # set = 全体暂停
local = threading.local()

def session():
    if not hasattr(local, "s"):
        local.s = requests.Session()
        local.s.headers.update(UA)
    return local.s

def cache_path(url):
    site = url.split("/")[2].split(".")[0]
    key = url.split(site + ".qiming.cn", 1)[1] or "/"
    safe = re.sub(r"[^A-Za-z0-9_.-]", "_", key)
    return os.path.join(CACHE, site, safe)

def fetch(url, timeout=25):
    path = cache_path(url)
    if os.path.exists(path) and os.path.getsize(path) > 200:
        return True
    while True:
        if BAN_EVENT.is_set():
            time.sleep(2)
            continue
        RL.acquire()
        time.sleep(random.uniform(0, 0.05))
        try:
            r = session().get(url, timeout=timeout)
            if r.status_code == 200 and len(r.content) > 200:
                os.makedirs(os.path.dirname(path), exist_ok=True)
                open(path, "w", encoding="utf-8").write(r.text)
                return True
            if r.status_code == 403:
                return False   # 触发退避
            if r.status_code == 404:
                open(path, "w", encoding="utf-8").write("<!-- 404 -->")
                return True
            return False
        except Exception:
            return False

def detail_url(site, eid):
    return f"{SITES[site]}/{site}/{eid}.html"

def wait_unban(max_wait=7200):
    """启动前/轮次前探测：遇 403 则每 60s 重试，直至放行"""
    t0 = time.time()
    while time.time() - t0 < max_wait:
        try:
            r = session().get("https://zidian.qiming.cn/zidian/1.html", timeout=15)
            if r.status_code == 200:
                print(f"[probe] 封禁已解除（等待 {time.time()-t0:.0f}s）", flush=True)
                return True
        except Exception:
            pass
        print(f"[probe] 仍被封禁，60s 后重探（已等待 {time.time()-t0:.0f}s）", flush=True)
        time.sleep(60)
    return False

def crawl(urls, workers=4):
    """多轮抓取：返回仍失败的 URL 列表"""
    total = len(urls)
    pending = list(urls)
    for rnd in range(8):
        if not pending:
            break
        if rnd > 0:
            wait_unban(600)
        ok = 0
        fails = []
        t0 = time.time()
        lock = threading.Lock()
        def work(u):
            nonlocal ok
            if fetch(u):
                with lock:
                    ok += 1
                if ok % 300 == 0:
                    el = time.time() - t0
                    print(f"  进度 {ok}/{total} 轮次{rnd+1} 速率{RL.rate:.1f}/s 用时{el:.0f}s", flush=True)
            else:
                with lock:
                    fails.append(u)
        with cf.ThreadPoolExecutor(max_workers=workers) as ex:
            list(ex.map(work, pending))
        # 403 退避
        if fails and rnd < 7:
            print(f"[轮次{rnd+1}] 失败 {len(fails)} 条，退避 45s 后降速重试", flush=True)
            BAN_EVENT.set()
            time.sleep(45)
            BAN_EVENT.clear()
            RL.slow_down(0.55)
        pending = fails
    return pending

def parse_site(site, entries):
    items = [(eid, e) for eid, e in entries.items() if e["site"] == site]
    parser = {"chengyu": parse_chengyu, "cidian": parse_cidian, "zidian": parse_zidian}[site]
    out = {}
    miss = 0
    for i, (eid, e) in enumerate(items):
        p = cache_path(detail_url(site, e["id"]))
        if not os.path.exists(p):
            miss += 1
            continue
        h = open(p, encoding="utf-8", errors="ignore").read()
        if h.startswith("<!-- 404"):
            continue
        d = parser(h)
        if d:
            d["id"] = e["id"]
            d.setdefault("name", e.get("name", ""))
            d.setdefault("pinyin", e.get("pinyin", ""))
            out[eid] = d
        if (i + 1) % 5000 == 0:
            print(f"  [parse:{site}] {i+1}/{len(items)}", flush=True)
    json.dump(out, open(f"{OUT}/{site}.json", "w", encoding="utf-8"), ensure_ascii=False)
    print(f"[{site}] 解析完成 {len(out)}/{len(items)}（缺页 {miss}）", flush=True)
    return out

def main():
    if not wait_unban():
        print("等待超时，退出", flush=True)
        return
    idx = json.load(open(f"{OUT}/index_cy_cd.json", encoding="utf-8"))
    idx_zd = json.load(open(f"{OUT}/index_zd.json", encoding="utf-8"))
    all_idx = dict(idx); all_idx.update(idx_zd)
    for site in ("chengyu", "cidian", "zidian"):
        items = [(eid, e) for eid, e in all_idx.items() if e["site"] == site]
        urls = [detail_url(site, e["id"]) for eid, e in items]
        need = [u for u in urls if not (os.path.exists(cache_path(u)) and os.path.getsize(cache_path(u)) > 200)]
        print(f"[{site}] 共 {len(urls):,} 页，待抓 {len(need):,}", flush=True)
        if need:
            left = crawl(need)
            if left:
                print(f"[{site}] 仍有 {len(left)} 页未抓到（跳过）", flush=True)
        parse_site(site, all_idx)
    print("全部完成", flush=True)

if __name__ == "__main__":
    main()
