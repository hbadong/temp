#!/usr/bin/env python3
"""TG 频道采集器：通过 Telethon 拉取频道消息并回传给后端导入接口。

环境变量：
  TG_API_ID        Telegram api_id（my.telegram.org 申请）
  TG_API_HASH      Telegram api_hash
  TG_SESSION       Telethon session 字符串（StringSession）
  IMPORT_URL       后端导入接口，默认 http://localhost:3001/api/admin/import
  ADMIN_TOKEN      后端管理令牌
  COLLECT_HOURS    拉取最近 N 小时消息，默认 24
  POLL_INTERVAL    轮询间隔秒数，默认 300

频道列表从后端 GET /api/admin/channels 读取（仅取 enabled=1 且 blocked=0）。
"""

import asyncio
import os
import sys
import time
from datetime import datetime, timedelta, timezone

import httpx
from telethon import TelegramClient
from telethon.sessions import StringSession

API_ID = int(os.environ.get("TG_API_ID", "0"))
API_HASH = os.environ.get("TG_API_HASH", "")
SESSION = os.environ.get("TG_SESSION", "")
IMPORT_URL = os.environ.get("IMPORT_URL", "http://localhost:3001/api/admin/import")
CHANNELS_URL = os.environ.get("CHANNELS_URL", "http://localhost:3001/api/admin/channels")
ADMIN_TOKEN = os.environ.get("ADMIN_TOKEN", "")
COLLECT_HOURS = int(os.environ.get("COLLECT_HOURS", "24"))
POLL_INTERVAL = int(os.environ.get("POLL_INTERVAL", "300"))


async def fetch_enabled_channels(client: TelegramClient) -> list[str]:
    async with httpx.AsyncClient(timeout=15) as http:
        resp = await http.get(CHANNELS_URL, headers={"X-Admin-Token": ADMIN_TOKEN})
        resp.raise_for_status()
        items = resp.json().get("items", [])
    return [
        item["username"]
        for item in items
        if item.get("enabled") and not item.get("blocked")
    ]


async def collect_channel(client: TelegramClient, http: httpx.AsyncClient, username: str) -> None:
    since = datetime.now(timezone.utc) - timedelta(hours=COLLECT_HOURS)
    messages = []
    try:
        async for msg in client.iter_messages(username, offset_date=None, reverse=True):
            if msg.date and msg.date.replace(tzinfo=timezone.utc) < since:
                continue
            if not msg.message:
                continue
            messages.append(
                {
                    "channel": username,
                    "msg_id": msg.id,
                    "text": msg.message,
                    "date": msg.date.isoformat(),
                }
            )
    except Exception as exc:  # noqa: BLE001
        print(f"[collect] {username} 拉取失败: {exc}", file=sys.stderr)
        return

    if not messages:
        print(f"[collect] {username} 本轮无新消息")
        return

    resp = await http.post(
        IMPORT_URL,
        headers={"X-Admin-Token": ADMIN_TOKEN},
        json={"messages": messages, "source": "telethon"},
    )
    resp.raise_for_status()
    result = resp.json()
    print(
        f"[collect] {username} 拉取 {result.get('fetched', 0)} 条，"
        f"入库 {result.get('inserted', 0)} 条，"
        f"重复 {result.get('skipped', 0)} 条，过滤 {result.get('filtered', 0)} 条"
    )


async def run_round(client: TelegramClient) -> None:
    async with httpx.AsyncClient(timeout=30) as http:
        channels = await fetch_enabled_channels(client)
        print(f"[collect] 本轮采集频道：{channels or '无'}")
        for username in channels:
            await collect_channel(client, http, username)


async def main() -> None:
    if not API_ID or not API_HASH or not SESSION:
        print(
            "缺少 TG_API_ID / TG_API_HASH / TG_SESSION 环境变量，"
            "请先在 my.telegram.org 申请并在生产环境配置。",
            file=sys.stderr,
        )
        sys.exit(1)

    async with TelegramClient(StringSession(SESSION), API_ID, API_HASH) as client:
        while True:
            await run_round(client)
            time.sleep(POLL_INTERVAL)


if __name__ == "__main__":
    asyncio.run(main())
