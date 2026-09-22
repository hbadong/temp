# 游嘻CMS（youxi-cms）深度分析报告

- **分析对象**：https://gitee.com/bala123/youxi-cms （master 分支，commit c7c6d05，v1.1.0）
- **分析方式**：克隆完整源码逐文件通读（config.php、index.php、includes/ 全部类库、admin/ 全部后台页面、install/、cron/、templates/default、.htaccess、nginx.conf、PROMO.md）
- **代码规模**：约 16000 行（PHP 82.6% / CSS 16.8% / JS 0.6%），35 个文件
- **分析日期**：2026-09-20

---

## 一、项目定位：本质是"主站-分站"模式的分站程序

这是整个系统最关键的一点。`config.php:4-11` 用 XOR 0x5A 混淆了一个 API 地址，解码后为：

```
https://www.nnii.cn/api.php
```

这是所有内容的唯一来源：

- 本地数据库所有表都以 `subsite_` 前缀命名（subsite_games、subsite_articles……）
- 游戏、礼包码、分类、标签、文章全部通过 `ApiSync` 从中央 API 拉取
- README/PROMO 明确面向宝塔面板用户、SEO 运营者

结论：它是一个**游戏下载站分站建站程序**。站长部署后一键同步主站数据，配合 AI 伪原创 + CSS 前缀改写让每个分站"内容独特化"，靠 8 个广告位变现。它是轻量单用户 CMS：无会员、无评论、无 UGC，前台纯展示。

## 二、技术栈与规模

| 项 | 内容 |
|---|---|
| 语言 | PHP 7.4+（推荐 8.0-8.2），占代码 82.6% |
| 数据库 | MySQL 5.7+，PDO + 预处理语句（EMULATE_PREPARES=false） |
| 前端 | UIKit 3.21 + Font Awesome 6 + jQuery（打包了 "4.0.0" 版本） |
| 依赖 | 零 Composer 依赖，全部手写 |
| 规模 | ~16000 行；核心：config.php 675 行（全局函数库）、index.php 977 行（路由 + 全部前台业务） |

**架构特点**：无框架、单入口 front-controller。`index.php` 一个文件承担路由、前台全部控制器和数据查询函数；`config.php` 承担配置、DB、缓存、加密、签名、CSRF、日志等全部横切功能；`admin/` 按页面拆分 PHP 脚本。

## 三、请求流转

```
请求 → .htaccess/nginx try_files → index.php
  ├─ /dynamic-css.css           → 输出模板CSS（可加CSS前缀）
  ├─ /{admin_path}/...          → handleAdminRoute：白名单页面 include admin/*.php
  │    ├─ 未登录                → 只允许 admin/index.php（登录页）
  │    └─ /ajax_xxx             → 映射 admin/ajax_xxx.php
  └─ 其余 → handleFrontRoute 正则路由：
       /api/claim-code   礼包码领取(JSON+CSRF)
       /api/download     下载跳转(302+签名校验+计数)
       /api/random-games 随机游戏池(缓存300s)
       /  /games  /game/{id}  /category/{id}  /tag/{id}
       /articles  /article/{id}  /tags  /lucky  /search
  模板渲染：extract($data) + include pages/{page}.php
```

补充机制：

- `index.php:7-10` 每天自动跑一次 `Database::migrate()`（补索引，标记文件放 sys_get_temp_dir）
- `admin_path` 安装时可自定义（如 `/myadmin/`），是安全措施之一
- 未配置伪静态时可用 `?route=/xxx` 临时路由过渡

## 四、数据库设计（17 张表，includes/database.php）

**内容表**：

| 表 | 说明 |
|---|---|
| subsite_games | main_id 唯一键、AES 加密的 android_url/ios_url、data_signature、tags JSON、is_ai_rewritten、views/download_count |
| subsite_gift_packages / subsite_gift_codes | 礼包（unique/universal 类型）与礼包码（领取记录 IP+UA） |
| subsite_categories / subsite_tags | 分类、标签（含 SEO 字段、game_count 冗余计数） |
| subsite_articles | source=api/local 区分来源、main_id、slug、置顶 |
| subsite_game_categories / subsite_game_tags / subsite_article_tags | 三张多对多关联表 |

**配置运营表**：settings（KV）、admin、page_seo、ads、links、menus、sync_log、stats（日 PV/UV）、game_stats（游戏日浏览/下载）。

设计上 `main_id`（主站 ID）与本地 `id` 分离；索引覆盖合理（idx_enabled_signature、idx_views 等复合索引）。

## 五、核心机制详解

### 1. 数据同步引擎（includes/sync.php，1037 行，最重的模块）

- 按 `updated_at` 增量拉取（`since` 参数），分页 50 条/页循环直到拉完
- 游戏 Upsert 在事务中：比较本地/远程 updated_at，本地新则只补签名
- 礼包只同步 `universal` 类型；强制同步时先删除一人一码礼包重拉
- **文章图片本地化**：正则提取 `<img src>`，curl 下载到 `/uploads/articles/article_{id}_{md5}.jpg`，失败则整段移除 img 标签（防裂图）
- 分类无关联数据时按 platform 字符串 `stripos` 自动匹配分类
- 同步完写 sync_log、清缓存；可选自动向百度/Bing 提交新 URL + 刷新 sitemap

### 2. 游戏数据签名体系（config.php:368-424）

- `signGameData`：对 `main_id|name|logo_url|platform|price` 做 HMAC-SHA256；密钥为用户配置的 `signature_key` 或 `sha256(目录|sig|主机名)`
- **所有前台查询都带 `data_signature != ''` 过滤**，详情页再 `verifyGameSignature` 复验——未同步过的游戏在前台完全不可见
- `fixGameSignatures` 每小时自愈一次签名（防服务器迁移后密钥变化导致全站失效）
- 实际效果评估：签名由本地密钥自算自验，更像"内容必须经过同步流程"的完整性闸门，防篡改意义有限

### 3. 下载链接保护

- 入库时 `encryptUrl`（AES-256-CBC，密钥 = sha256(部署目录路径)），DB 中只见密文
- 前台下载按钮指向 `/api/download?type=android&id=N`：验签名 → 解密 → 302 跳转 + download_count 自增
- 评估：属于混淆级保护（密钥源自服务器路径，本机可解），主要防爬虫直接扒库

### 4. 礼包码系统

- 通用码：复制即用
- 一人一码：领取时记 IP+UA，**每 IP 24h 限领 5 次**（index.php:138-154）

### 5. 模板系统与"模板伪原创"

- 无模板引擎，纯 PHP 模板：`renderTemplate` 把 `$data` extract 后 include `pages/*.php` + `partials/header|footer`
- 两代布局约定并存：default/uikit 用 `pages/` + `partials/` 子目录，其他模板用平铺 `header.php`（index.php:498-534 按目录名分支）
- **CSS class 前缀替换**：开启后输出缓冲整个页面，`preg_replace('/\bgm-/', $prefix.'-')` 把所有 `gm-*` class 换成自定义前缀（`/dynamic-css.css` 同样处理）——让不同分站 HTML 结构不同，规避搜索引擎对站群的相似度判定

### 6. AI 伪原创（includes/ai.php + admin/ai_rewrite.php）

- OpenAI 兼容协议，支持智谱/通义/文心/DeepSeek/Kimi/豆包/自定义（文心走特殊响应格式 `result` 字段）
- 用户自填 API Key，temperature 0.8，max_tokens 4000
- 批量改写游戏详情/文章/分类/标签，各自可配提示词，改写后打 `is_ai_rewritten` 标记

### 7. SEO 体系（做得最完整的部分）

- `subsite_page_seo` 表按页面类型配置；标题支持 `{site_name}/{separator}/{page}` 占位符；分页自动追加"第N页"并去重分隔符（seo.php:95-114）
- 未填描述时自动从 details/content/summary 截 160 字
- Canonical、nofollow 页面控制、动态 sitemap.xml（带完整性校验的文件缓存）
- `seo_submit.php`：百度普通收录 API + Bing Webmaster API 逐条提交；同步完成后自动提交最新 100 条游戏 + 100 篇文章 URL

### 8. 广告系统

8 个位置（header_banner / sidebar_top / sidebar_bottom / game_list / game_detail / article_top / article_bottom / footer），首次访问自动播种占位 SVG，内容为任意 HTML（广告联盟代码），每位独立开关。

### 9. 缓存与统计

- 文件缓存 `/cache/md5(key).cache`，**首行存 sha256 数据哈希做完整性校验**（config.php:426-442），TTL 300-600s，同步后全清
- PV/UV：文件计数器每 10 次批量 flush 进 `subsite_stats`；游戏浏览/下载按日聚合

## 六、安全设计评估

### 做对了的

- 全程 PDO 预处理 + `escapeLike` 处理 LIKE 通配符
- CSRF token（hash_equals，表单一次性或 keep 模式）、`session_regenerate_id` 登录后重建会话、cookie httponly/samesite/strict
- 登录限速：每 IP 5 次 / 15 分钟（临时文件计数）
- `password_hash/verify` 存管理员密码
- 自写 `purifyHtml`：黑名单标签剥离（script/iframe/svg/math…）+ 白名单标签属性 + javascript:/data: 协议过滤 + on* 事件正则清除 + a 标签强制 rel="noopener noreferrer"
- Web 层防护完整：.htaccess / nginx 双份规则禁 config*、includes、templates PHP、logs、cache、cron（sync.php 除外）；uploads 禁 PHP 执行；HTTP 安全头（nosniff / XFO / HSTS / Referrer-Policy）
- install.lock 锁定安装目录；`.debug` 文件存在才开 display_errors；cron HTTP 触发需 `cron_key`（hash_equals 比较）

### 弱点（代码审查视角）

- `getClientIp` 信任 X-Forwarded-For 等一串可伪造头，登录限速和礼包 IP 限领可被绕过
- `ORDER BY RAND()`（random-games）、`LIKE '%kw%'` 全表扫描（无全文索引）、`updateCategoryGameCounts` 逐分类 N+1 子查询——数据量大后同步和标签页会慢
- `trackVisit` 文件读改写有并发丢计数可能（LOCK_EX 只锁写，读改写间隙有竞态）
- 加密/签名密钥派生自部署路径与主机名，迁移服务器需注意
- 前台搜索关键词经 `sanitize`（htmlspecialchars）后进 LIKE 参数：SQL 层安全，但输出层依赖模板再次转义，存在双重转义显示问题

## 七、代码质量总评

**优点**：

- 结构直白、注释充分（中文）、防御式编程密度高（几乎每个 DB 操作包 try-catch）
- 错误日志完善（按日分文件，带 IP/URI 上下文）
- 安装向导成熟：环境检测、测试连接、自动生成 signature_key + cron_key、初始化菜单/广告/页面 SEO
- 文档（README 约 1.7 万字宝塔教程 + PROMO.md）在同类项目中罕见地完整

**缺点**：

- `config.php` + `index.php` 两个巨型文件承载了 80% 逻辑，全局函数无命名空间，前台数据查询函数与路由混在一起，可测试性差
- 模板层 `extract($data)` 直接展开
- 两套模板布局约定并存，增加扩展心智负担

## 八、适用场景与局限

**适用**：

- 个人站长快速架设游戏下载/礼包码分享站
- 宝塔环境部署
- 靠百度/必应 SEO 流量挂广告变现
- 需要挂靠某个主站数据源运营多个分站（站群）

**局限**：

- 内容命脉完全依赖中央 API（`nnii.cn`，地址被混淆，`setDbSetting('main_api_url')` 显式禁止修改）
- 自身无内容生产闭环（文章只能后台手写或同步）
- 无多用户体系
- 签名机制绑定"必须先同步"的使用方式；想当独立 CMS 用需要改造（去掉 data_signature 过滤即可，签名是本地可自算的）

## 九、对 LECMS 游戏站项目的借鉴点

结合当前 LECMS（game_center 插件 + url_generator）工作，值得参考的点：

| # | 借鉴点 | 说明 | 改造成本 |
|---|--------|------|---------|
| 1 | URL 结构 | 它用 `/game/{id}.html`、`/category/{id}.html`；我们用 `/{id}.html` + `/category/{alias}.html`（alias 对 SEO 更好，保持现状） | - |
| 2 | 模板伪原创 CSS 前缀方案 | 输出缓冲 + 正则替换 class 前缀，实现成本低，可移植到 LECMS 模板层 | 低 |
| 3 | 图片本地化 | 同步内容时把远程图片下载到本地并重写 src、失败移除标签，比留外链稳定 | 中 |
| 4 | 页面级 SEO 表 | page_seo 按 page_key 配标题占位符（{site_name}/{separator}/{page}）+ 分页标题去重，比 LECMS 当前逐站配置更细 | 中 |
| 5 | 下载链接中转 | `/api/download?type=&id=` 统计+跳转端点，比直接暴露 download_url 干净，可给 game_center 加类似端点 | 低 |

---

## 附录：关键源码索引

| 文件 | 行数 | 职责 |
|------|------|------|
| config.php | 675 | 版本/API 常量（混淆解码）、错误处理、DB 连接、sanitize/purifyHtml、AES 加解密、HMAC 签名、文件缓存、CSRF、登录限速、CDN 源映射 |
| index.php | 977 | 单入口路由、前台全部路由分支、数据查询函数、模板渲染、广告初始化、访问统计 |
| includes/database.php | 269 | 17 张表建表 SQL + 每日索引迁移 |
| includes/sync.php | 1037 | ApiSync 类：分类/标签/游戏/礼包/文章增量同步、图片本地化、SEO 自动提交 |
| includes/seo.php | 136 | 页面 SEO 读取保存、占位符替换、自动描述 |
| includes/seo_submit.php | 261 | 百度/Bing URL 提交、sitemap 刷新 |
| includes/ai.php | 58 | 多平台 AI 调用（OpenAI 兼容 + 文心特殊处理） |
| admin/ | 17 个文件 | 登录、dashboard、settings(744行)、sync、ai_rewrite(562行)、taxonomy、seo、ads、links、menus、articles、clear、update 及 5 个 ajax 端点 |
| install/index.php | 603 | 安装向导：环境检测、建库、写 config_user.php、生成密钥、初始化数据 |
| cron/sync.php | 28 | CLI 直跑；HTTP 需 cron_key，执行增量同步 |
| templates/default/ | 9 个页面 | UIKit 主题：index/games/game/category/tags/posts/post/search/lucky/404 |
| .htaccess / nginx.conf | 80/行 | 伪静态 + 敏感路径封锁 + 安全头 + 浏览器缓存策略 |
