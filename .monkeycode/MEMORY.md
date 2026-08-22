# User Instruction Memory

This file records user instructions, preferences, and teachings for reference in future interactions.

## Format

### User Instruction Entry
User instruction entries should follow this format:

[User Instruction Summary]
- Date: [YYYY-MM-DD]
- Context: [Mentioned scenario or time]
- Instructions:
  - [Content of user teaching or instruction, described line by line]

### Project Knowledge Entry
Entries discovered by the Agent during task execution should follow this format:

[Project Knowledge Summary]
- Date: [YYYY-MM-DD]
- Context: Discovered by Agent while performing [specific task description]
- Category: [Operations & Deployment|Build Methods|Testing Methods|Troubleshooting & Debugging|Workflow & Collaboration|Environment Configuration]
- Instructions:
  - [Specific knowledge points, described line by line]

## Deduplication Strategy
- Before adding a new entry, check for similar or identical instructions.
- If a duplicate is found, skip the new entry or merge it with the existing one.
- When merging, update the context or date information.
- This helps avoid redundant entries and keeps the memory file tidy.

## Entries

[盘搜 PanSearch 项目启动与运维]
- Date: 2026-08-21
- Context: Agent 开发网盘搜索系统（/workspace/pan-search）时确认
- Category: Operations & Deployment
- Instructions:
  - 一键启动：`/workspace/pan-search/start.sh`，后端 API 监听 3001，前端 Vite 监听 5173（对外预览入口，已配置 /api 反向代理到 3001）
  - 后端必须以 `node --experimental-sqlite --no-warnings src/index.js` 启动（node:sqlite 在 Node 22 需要实验标志）
  - 管理后台令牌由环境变量 ADMIN_TOKEN 控制，默认 admin123；请求头 X-Admin-Token
  - 数据文件为 SQLite：`server/data/pan-search.db`（WAL 模式），删除该文件重启可重新生成种子数据
  - 种子数据量由 SEED_COUNT 控制（默认 2000）；模拟采集间隔 COLLECT_INTERVAL_MS（默认 30 秒）
  - 生产 TG 采集使用 `server/tools/tg_importer.py`（Telethon），需用户自行配置 TG_API_ID/TG_API_HASH/TG_SESSION

[环境限制与检索选型]
- Date: 2026-08-21
- Context: Agent 在沙箱中选型时验证
- Category: Environment Configuration
- Instructions:
  - 本环境无 Docker、无 MySQL 服务，内存约 8GB 但常驻占用高，跑不动 Elasticsearch；检索层用 SQLite FTS5 + 自实现 bigram 中文分词（server/src/tokenizer.js）替代，FTS 同步通过 resources.title_t 列 + SQL 触发器实现
  - SQLite FTS5 trigram 分词器无法命中 2 字中文词，勿改用 trigram
