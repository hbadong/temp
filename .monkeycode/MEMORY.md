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

[LECMS 3.0.4 后台 API 架构与关键约束]
- Date: 2026-09-04
- Context: Discovered by Agent while fixing API controller and testing all endpoints
- Category: Operations & Deployment
- Instructions:
  - 入口文件 `/workspace/lecms/api.php`（APP_NAME='api'），所有前端 API 请求经此路由；框架路由 `$_GET['u']='api-{action}'` → `api_control` 类的 `{action}` 方法（如 `content/list` → `content_list()`）
  - 编译缓存：源文件 `lecms/control/api_control.class.php` 改动后必须删除 `runcache/lecms_control/api_control.class.php`，否则改动不生效；同理 `runcache/_lecms.php` 是框架核心编译缓存
  - 鉴权：LECMS 默认 cookie 方式登录（`_login_method='cookie'`），登录写入 `le26UfR_admauth` cookie（前缀见 config.inc.php `cookie_pre`），鉴权用 `$user_model->user_token_check(1)`，不要检查 `$_SESSION['uid']`
  - `_json()` 方法在 api_control 中自定义（control 基类的 `__call` 在 DEBUG 关闭时直接返回 404 页面，不会抛异常，极易误判）
  - 模型 table 属性懒初始化：`cms_content->table` 必须设为 `'cms_article'`，`cms_content_tag->table='cms_article_tag'`，`cms_content_attach->table='cms_article_attach'`，`cms_content_comment->table='cms_comment'`（这些模型默认 `table=''`，不设会查到空表名报错）
  - 表名带 `pre_` 前缀（见 config `tablepre`），但模型层自动加前缀，代码中用 `cms_article` 不带 `pre_`
  - nginx 配置 `/etc/nginx/sites-available/lecms`：端口 8001，`location = /api.php` 优先匹配直连 FPM，避免被 `location /` 的 `try_files` 拦截到 index.php 的 404
  - 设置存储：`$this->kv->xset($k,$v,'cfg')` + `$this->kv->save_changed()` + `$this->runtime->delete('cfg')` 清缓存；`$cfg=$this->kv->xget('cfg')` 读取
  - 前端 `admin-ui`（Vite 5173）通过 `vite.config.ts` 代理 `/api/*` → `8001/api.php?u=*`；后台预览 URL 形如 `https://5173-<token>.monkeycode-ai.online`
  - 后端构建：`cd /workspace/lecms/admin-ui && npm run build`（vue-tsc + vite build，约 8 秒）
  - 旧 Layui 后台保留在 `/admin/` 路径，与新 Arco 后台共存
