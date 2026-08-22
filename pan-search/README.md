# 盘搜 PanSearch - 网盘资源搜索系统

类似 feizhupan 的多网盘资源搜索引擎 MVP。支持百度 / 阿里 / 夸克 / 迅雷等网盘资源的关键词检索、类型筛选、失效标记与后台管理。

## 技术架构

```
TG频道消息 → 采集器(Telethon/模拟源) → 解析+去重+敏感词过滤 → SQLite FTS5 索引 → 搜索API → Vue3 前端
```

| 组件 | 实现 |
|------|------|
| 后端 API | Node.js 18+ / Express |
| 检索引擎 | SQLite FTS5 + bigram 中文分词（10万级索引毫秒响应，接口层预留 ES 切换） |
| 数据采集 | 内置模拟源（演示）+ Telethon 采集器（生产，`server/tools/tg_importer.py`） |
| 前端 | Vue 3 + Vite，移动端适配 |
| 部署 | 单端口预览：Vite 反向代理 `/api` → 后端 3001 |

## 快速启动

```bash
./start.sh
# 访问 http://localhost:5173
```

环境变量（可选，见 `server/src/index.js` 与 `server/tools/tg_importer.py`）：

- `ADMIN_TOKEN` 管理令牌，默认 `admin123`
- `SEED_COUNT` 种子数据条数，默认 2000
- `COLLECT_INTERVAL_MS` 模拟采集间隔，默认 30000
- `SEARCH_RATE_LIMIT` 每分钟每 IP 搜索上限，默认 30

## 功能清单

- 全文检索：中文 bigram 分词、关键词高亮、相关度排序、分页（20 条/页）
- 筛选：网盘类型（百度/阿里/夸克/迅雷/115/天翼/移动）、资源类型（视频/软件/文档/音乐/图片）
- 数据采集：频道配置、增量采集、链接指纹去重、敏感词过滤、采集日志
- 资源管理：手动增删改、屏蔽/标失效、失效举报自动标记（≥5 次标记"已失效"）
- 用户侧：热门搜索、索引统计、失效反馈按钮、侵权投诉入口
- 安全：IP 限流（全局 + 搜索接口）、管理令牌鉴权、敏感词库动态更新

## 生产接入 Telegram 采集

1. 在 my.telegram.org 申请 `api_id` / `api_hash`，生成 StringSession
2. 配置环境变量后运行：

```bash
pip install telethon httpx
export TG_API_ID=xxx TG_API_HASH=xxx TG_SESSION=xxx ADMIN_TOKEN=xxx
python3 server/tools/tg_importer.py
```

采集器会从后台读取启用的频道列表，拉取近 24 小时消息并回传导入接口。

## 目录结构

```
pan-search/
├── server/            # Node.js 后端
│   ├── src/
│   │   ├── index.js           # 入口（限流/路由/种子/调度）
│   │   ├── db.js              # SQLite schema + FTS 触发器
│   │   ├── tokenizer.js       # bigram 中文分词
│   │   ├── parser.js          # TG 消息解析（链接/提取码/标题）
│   │   ├── services/          # store(检索/入库) collector(采集)
│   │   ├── routes/            # public / admin API
│   │   └── middleware/        # IP 限流
│   ├── tools/tg_importer.py   # Telethon 生产采集器
│   └── data/                  # SQLite 数据文件
├── web/               # Vue3 前端（首页/搜索页/投诉页/管理后台）
└── start.sh           # 一键启动
```
