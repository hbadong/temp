#!/usr/bin/env python3.11
"""好名网 sitemap.xml 生成器
用法: python3.11 gen_sitemap.py [--host https://域名] [--out /var/www/lecms/sitemap.xml]
数据源: MariaDB lecms 库（文章/栏目）+ data.7.js（热门字）+ 静态工具页清单
"""
import argparse, datetime, html, re, subprocess, sys, xml.etree.ElementTree as ET
from urllib.parse import quote

NS = "http://www.sitemaps.org/schemas/sitemap/0.9"
TODAY = datetime.date.today().isoformat()

def db_rows(sql):
    r = subprocess.run(["mysql", "-uroot", "lecms", "-N", "-e", sql], capture_output=True, text=True)
    return [l.split("\t") for l in r.stdout.strip().split("\n") if l.strip()]

def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--host", default="https://ad6acb5cb3aeb83e6.app.workbuddy.link")
    ap.add_argument("--out", default="/var/www/lecms/sitemap.xml")
    ap.add_argument("--data", default="/var/www/lecms/view/haoiming/js/data.7.js")
    a = ap.parse_args()
    host = a.host.rstrip("/")

    urls = []  # (loc, priority, changefreq)
    urls.append(("/", "1.0", "daily"))
    tools = ["naming", "bazi", "poems", "shijing", "chuci", "tangshi", "songci",
             "zhouyi", "gaiming", "gongsi", "score", "match", "kxzd", "baijiaxing",
             "surname", "char", "mingzi", "search"]
    for t in tools:
        urls.append((f"/view/haoiming/tool/{t}.html", "0.9", "weekly"))

    # 栏目页
    for cid, alias, name in db_rows("SELECT cid, alias, name FROM lecms_category WHERE type=1 ORDER BY cid"):
        urls.append((f"/index.php?cate--cid-{cid}", "0.8", "daily"))
    # 文章页
    for aid, cid, title in db_rows("SELECT id, cid, title FROM lecms_cms_article ORDER BY id"):
        urls.append((f"/index.php?show--cid-{cid}-id-{aid}.html", "0.7", "monthly"))

    # 热门字落地页（data.7.js 字库前 60 个）
    # 注意：中文参数必须 percent-encode，否则违反 sitemap 协议，百度/站长平台会判为无效 URL
    try:
        src = open(a.data, encoding="utf-8").read()
        chars = re.findall(r'\{c:"([^"]+)"', src)[:60]
        for c in chars:
            urls.append((f"/view/haoiming/tool/char.html?c={quote(c, safe='')}", "0.6", "monthly"))
    except FileNotFoundError:
        print("警告: data.7.js 未找到，跳过热门字落地页", file=sys.stderr)

    root = ET.Element("urlset", xmlns=NS)
    for loc, pri, freq in urls:
        u = ET.SubElement(root, "url")
        ET.SubElement(u, "loc").text = host + loc
        ET.SubElement(u, "lastmod").text = TODAY
        ET.SubElement(u, "changefreq").text = freq
        ET.SubElement(u, "priority").text = pri
    ET.indent(root)
    xml = ['<?xml version="1.0" encoding="UTF-8"?>', ET.tostring(root, encoding="unicode")]
    open(a.out, "w", encoding="utf-8").write("\n".join(xml))
    print(f"sitemap 已生成: {a.out} | {len(urls)} 个 URL | host={host}")

    # robots.txt Sitemap 声明
    rp = a.out.rsplit("/", 1)[0] + "/robots.txt"
    line = f"Sitemap: {host}/sitemap.xml"
    try:
        content = open(rp, encoding="utf-8").read()
        if "Sitemap:" not in content:
            open(rp, "a", encoding="utf-8").write("\n" + line + "\n")
            print(f"robots.txt 已追加 Sitemap 声明")
        else:
            print("robots.txt 已含 Sitemap 声明")
    except FileNotFoundError:
        open(rp, "w", encoding="utf-8").write(f"User-agent: *\nDisallow: /lecms/\nDisallow: /admin*\n{line}\n")
        print("robots.txt 已创建")

if __name__ == "__main__":
    main()
