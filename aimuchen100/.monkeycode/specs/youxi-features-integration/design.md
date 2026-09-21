# 游嘻CMS 五项能力集成 LECMS — 技术设计

日期：2026-09-20
范围：数据同步引擎、游戏数据签名体系（内容指纹版）、下载链接保护、模板伪原创、AI 伪原创扩展

## 1. 总体架构

```
主站 LECMS                                分站 LECMS
┌─────────────────────┐                  ┌──────────────────────────┐
│ game_center(升级)    │                  │ game_center(升级)         │
│  - 加密下载列        │                  │  - main_id/source/指纹列  │
│ master_api(新)       │  HTTPS+Token     │ sync_client(新)           │
│  - JSON 增量输出     │ ───────────────> │  - 拉取/验签/upsert       │
│  - HMAC 出口签名     │  since+page      │  - url_map 登记           │
│  - Token 鉴权+限频   │                  │  - 图片本地化(SSRF白名单) │
└─────────────────────┘                  │  - 跳过 is_ai_rewritten=1 │
                                          │ template_rewrite(新)      │
ai_content_factory(扩展)                  │  - CSS 前缀输出替换       │
  - 4类伪原创任务                         │ url_generator(小改)       │
  - is_ai_rewritten 标记                 │  - /download-{id}.html    │
└─────────────────────┘                  └──────────────────────────┘
```

## 2. 数据库变更

### le_cms_game（game_center install.php 迁移，try-catch ALTER）
- `main_id` INT NULL — 源站游戏ID（同步映射键）
- `source` VARCHAR(10) DEFAULT 'local' — local/api
- `content_hash` VARCHAR(64) DEFAULT '' — HMAC-SHA256 内容指纹
- `is_ai_rewritten` TINYINT DEFAULT 0

### le_cms_article（ai_content_factory install.php 迁移）
- `main_id` INT NULL（sync_client 需要）
- `is_ai_rewritten` TINYINT DEFAULT 0

### le_cms_game_category（ai_content_factory install.php 迁移）
- `main_id` INT NULL
- `is_ai_rewritten` TINYINT DEFAULT 0

### le_cms_sync_log（sync_client install.php 新建）
- id/site_id/sync_type/status/message/item_count/created_at

### le_cms_api_token（master_api install.php 新建）
- id/token/remark/last_request_at/request_count/created_at

## 3. 关键设计决策

| 决策 | 结论 |
|---|---|
| 签名用途 | 内容指纹：master_api 出口签名、sync_client 入口验签 + 增量去重；**前台查询零过滤** |
| 签名算法 | HMAC-SHA256(载荷JSON, key)，key = hash_hmac('sha256','game_sync',auth_key) |
| 下载保护 | download_url 列存密文 AES-256-CBC，key=hash('sha256',auth_key.'|dl',true)；前台 /download-{id}.html 302 中转+计数 |
| 模板伪原创 | 输出层替换（ob），per-site 前缀存 le_site_manager.config；替换规则 `\bgm-` → `{prefix}-`；/dynamic-css.css 动态出 CSS |
| 同步协议 | GET ?action=sync&type=games|articles|categories|tags&page=N&page_size=50&since=ts&token=T；响应 {status,data,pagination}（兼容游嘻CMS 协议形态） |
| AI 改写保护 | sync upsert 跳过 is_ai_rewritten=1 行（force 除外）；admin 手工编辑自动清零标记 |
| 提示词 | 每任务类型独立模板存 le_ai_config，settings 页可编辑 |
| 图片本地化 | 仅从主站 host 白名单下载；扩展名白名单 jpg/jpeg/png/gif/webp；失败移除 img 标签 |
| 调度 | cron 端点 /api/sync-cron?key=（sync_client 设置页生成），CLI 直跑 |
| site_id | 所有新表/查询带 site_id；sync_client 设置"目标站点" |

## 4. 风险与规避
1. runcache：新插件后台模板首次访问前清对应缓存目录
2. url_map：同步游戏必须登记（status=2, type=2），复用 game_center 注册函数
3. {@ } 模板陷阱：模板内禁写 echo；复杂逻辑放 control
4. FORM_HASH：所有后台表单携带
5. master_api 暴露面：Token + 每 Token 每分钟 30 次限频 + 仅输出已启用内容
