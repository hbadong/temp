#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
SEO 主动推送脚本（部署到自有域名后使用）
================================================
将新上线 / 更新的 URL 实时通知搜索引擎，加速收录：
  · 百度搜索资源平台 主动推送 API（data.zz.baidu.com/urls）
  · 谷歌 Indexing API（indexing.googleapis.com/v3/urlNotifications:publish）

用法：
  # 从 sitemap 推送全部 URL
  python3 seo_push.py --sitemap https://你的域名/sitemap.xml

  # 只推送指定 URL 列表（每行一个）
  python3 seo_push.py --urls urls.txt

  # 只推百度 / 只推谷歌
  python3 seo_push.py --sitemap ... --only baidu
  python3 seo_push.py --sitemap ... --only google

配置（三选一：修改下方 CONFIG / 环境变量 / 命令行参数）：
  BAIDU_SITE   例如 https://www.yourdomain.com
  BAIDU_TOKEN  百度搜索资源平台「普通收录 → 主动推送」里分配的 token
  GOOGLE_CREDENTIALS  谷歌 Service Account 的 JSON 密钥文件路径
"""

import os
import sys
import argparse
import xml.etree.ElementTree as ET
import requests

# ───────────────────────────────────────────────
# CONFIG（改成你自己的值，或用环境变量 / 命令行覆盖）
# ───────────────────────────────────────────────
BAIDU_SITE = os.environ.get("BAIDU_SITE", "https://www.你的域名.com")
BAIDU_TOKEN = os.environ.get("BAIDU_TOKEN", "你的百度推送TOKEN")
GOOGLE_CREDENTIALS = os.environ.get(
    "GOOGLE_CREDENTIALS", "/path/to/google-service-account.json"
)

BAIDU_API = "http://data.zz.baidu.com/urls?site={site}&token={token}"
GOOGLE_INDEXING_API = "https://indexing.googleapis.com/v3/urlNotifications:publish"
GOOGLE_SCOPE = "https://www.googleapis.com/auth/indexing"

REQUEST_TIMEOUT = 30


# ───────────────────────────────────────────────
# URL 来源
# ───────────────────────────────────────────────
def load_urls_from_sitemap(sitemap_url):
    """拉取 sitemap.xml 并解析出所有 <loc>。"""
    try:
        resp = requests.get(sitemap_url, timeout=REQUEST_TIMEOUT)
        resp.raise_for_status()
    except Exception as e:
        print(f"[错误] 拉取 sitemap 失败: {e}")
        return []
    try:
        root = ET.fromstring(resp.content)
    except ET.ParseError as e:
        print(f"[错误] 解析 sitemap XML 失败: {e}")
        return []
    # 兼容带命名空间与不带命名空间两种
    urls = []
    for el in root.iter():
        if el.tag.endswith("loc") and el.text:
            urls.append(el.text.strip())
    print(f"[信息] 从 sitemap 解析到 {len(urls)} 个 URL")
    return urls


def load_urls_from_file(path):
    with open(path, "r", encoding="utf-8") as f:
        return [line.strip() for line in f if line.strip()]


# ───────────────────────────────────────────────
# 百度推送
# ───────────────────────────────────────────────
def push_baidu(site, token, urls, batch=2000):
    """百度每次最多 2000 条，按批推送。返回成功条数。"""
    if not site or not token or "你的" in site or "你的" in token:
        print("[跳过] 百度未配置（请在脚本 CONFIG 或环境变量中填入 BAIDU_SITE / BAIDU_TOKEN）")
        return 0
    api = BAIDU_API.format(site=site, token=token)
    ok_total = 0
    for i in range(0, len(urls), batch):
        chunk = urls[i : i + batch]
        body = "\n".join(chunk)
        try:
            r = requests.post(
                api,
                data=body.encode("utf-8"),
                headers={"Content-Type": "text/plain"},
                timeout=REQUEST_TIMEOUT,
            )
            print(f"[百度] HTTP {r.status_code} 推送 {len(chunk)} 条 → {r.text.strip()}")
            try:
                import json

                j = r.json()
                ok_total += int(j.get("success", 0))
            except Exception:
                if r.status_code == 200:
                    ok_total += len(chunk)
        except Exception as e:
            print(f"[百度] 请求失败: {e}")
    return ok_total


# ───────────────────────────────────────────────
# 谷歌推送
# ───────────────────────────────────────────────
def push_google(urls, credentials):
    if not credentials or "path/to" in credentials or not os.path.exists(credentials):
        print("[跳过] 谷歌未配置（准备 service-account.json 后设置 GOOGLE_CREDENTIALS）")
        return 0
    try:
        from google.oauth2 import service_account
        from google.auth.transport.requests import AuthorizedSession
    except ImportError:
        print("[跳过] 缺少 google-auth 库，请执行: pip install google-auth")
        return 0

    try:
        creds = service_account.Credentials.from_service_account_file(
            credentials, scopes=[GOOGLE_SCOPE]
        )
        session = AuthorizedSession(creds)
    except Exception as e:
        print(f"[谷歌] 鉴权失败: {e}")
        return 0

    ok = 0
    for u in urls:
        try:
            r = session.post(
                GOOGLE_INDEXING_API,
                json={"url": u, "type": "URL_UPDATED"},
                timeout=REQUEST_TIMEOUT,
            )
            ok += 1 if r.status_code in (200, 429) else 0
            if r.status_code != 200:
                print(f"[谷歌] {u} → HTTP {r.status_code} {r.text[:120]}")
        except Exception as e:
            print(f"[谷歌] {u} 请求失败: {e}")
    print(f"[谷歌] 已提交 {ok}/{len(urls)} 条")
    return ok


# ───────────────────────────────────────────────
# 主流程
# ───────────────────────────────────────────────
def main():
    ap = argparse.ArgumentParser(description="SEO 主动推送（百度 + 谷歌）")
    ap.add_argument("--sitemap", help="sitemap.xml 地址")
    ap.add_argument("--urls", help="URL 列表文件（每行一个）")
    ap.add_argument("--only", choices=["baidu", "google"], help="只推某个引擎")
    ap.add_argument("--baidu-site", default=BAIDU_SITE)
    ap.add_argument("--baidu-token", default=BAIDU_TOKEN)
    ap.add_argument("--google-creds", default=GOOGLE_CREDENTIALS)
    args = ap.parse_args()

    if args.sitemap:
        urls = load_urls_from_sitemap(args.sitemap)
    elif args.urls:
        urls = load_urls_from_file(args.urls)
    else:
        ap.error("必须提供 --sitemap 或 --urls")
        return

    if not urls:
        print("[结束] 无 URL 可推送")
        return

    if args.only in (None, "baidu"):
        push_baidu(args.baidu_site, args.baidu_token, urls)
    if args.only in (None, "google"):
        push_google(urls, args.google_creds)


if __name__ == "__main__":
    main()
