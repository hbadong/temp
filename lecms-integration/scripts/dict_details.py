#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
详情页抓取 + 解析（阶段二）
读取 index_cy_cd.json / index_zd.json，并发抓取全部详情页（磁盘缓存），
解析为结构化 JSON：chengyu.json / zidian.json / cidian.json
"""
import os, re, json, time, html as H
import concurrent.futures as cf
from dict_crawl import fetch, fetch_many, parse_chengyu, parse_zidian, parse_cidian, SITES, OUT

def detail_url(site, eid):
    return f"{SITES[site]}/{site}/{eid}.html"

def run_site(site, entries, parser, workers=8):
    items = [(eid, e) for eid, e in entries.items() if e["site"] == site]
    print(f"[{site}] 待抓取 {len(items)} 页")
    urls = {eid: detail_url(site, e["id"]) for eid, e in items}
    # 抓取（缓存优先）
    htmls = fetch_many(list(urls.values()), workers=workers)
    by_url = {}
    for u, h in htmls.items():
        if h:
            by_url[u] = h
    # 解析
    out = {}
    done = 0
    for eid, e in items:
        h = by_url.get(urls[eid], "")
        if not h:
            continue
        d = parser(h)
        if d:
            d["id"] = e["id"]
            if "name" not in d:
                d["name"] = e["name"]
            if "pinyin" not in d:
                d["pinyin"] = e.get("pinyin", "")
            out[eid] = d
        done += 1
        if done % 2000 == 0:
            print(f"[{site}] 已解析 {done}/{len(items)}")
    json.dump(out, open(f"{OUT}/{site}.json", "w", encoding="utf-8"), ensure_ascii=False)
    print(f"[{site}] 完成 {len(out)}/{len(items)} -> {site}.json")
    return out

def main():
    idx = json.load(open(f"{OUT}/index_cy_cd.json", encoding="utf-8"))
    idx_zd = json.load(open(f"{OUT}/index_zd.json", encoding="utf-8"))
    all_idx = dict(idx); all_idx.update(idx_zd)
    t0 = time.time()
    run_site("chengyu", all_idx, parse_chengyu)
    run_site("cidian", all_idx, parse_cidian)
    run_site("zidian", all_idx, parse_zidian)
    print(f"总耗时 {time.time()-t0:.0f}s")

if __name__ == "__main__":
    main()
