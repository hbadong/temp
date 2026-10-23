# 方案 A：动态参数页 SEO 预渲染 — 交付报告

> 交付日期：2026-10-22
> 适用系统：好名网起名系统（LECMS + 前端 JS 渲染）
> 关键变量：`char.html?c=` / `surname.html?c=` / `mingzi.html?name=` 三类动态参数页
> 目标：让搜索引擎（百度 / 谷歌 / 360 / 搜狗 等）抓取到的不再是 JS 空壳，而是**真实渲染后的完整 HTML**，从而可被正常收录。

---

## 1. 背景：为什么必须要预渲染

这三类页面是系统的核心流量入口（汉字寓意、姓氏起名、姓名打分），但**内容完全由前端 JS 按 URL 参数实时渲染**。搜索引擎的初级抓取器（含部分百度蜘蛛的历史快照机制）在禁用 JS 时，抓到的页面结构是：

```
<body>
  <div id="char-detail"></div>   <!-- 空容器，JS 才会填充 -->
  ...
</body>
```

空容器 = 零正文 = 无法收录。这是"工具页能否被百度收录"的决定性变量。

**方案 A（UA 识别 + 服务端预渲染）覆盖面最广**：爬虫请求时，服务端用无头 Chromium 把页面真实渲染一遍，返回完整 HTML；真实用户仍走前端 SPA（体验不变）。相比"改写前端为 SSR"或"服务端拼静态页"，方案 A 复用同一套前端代码，无需重写逻辑，且对百度 / 谷歌 / 全部主流爬虫统一生效。

---

## 2. 架构：三服务协同 + UA 分流

```
                        ┌─────────────────────────────────────────┐
   搜索引擎爬虫请求  ──▶ │  公网 8090  (PHP router.php 拦截)        │
   (Baiduspider...)     │  · UA 命中爬虫正则？                     │
                        │  · 是且为三类动态页 → 触发预渲染          │
                        │  · 其他请求 → return false 原样放行       │
                        └───────┬───────────────┬──────────────────┘
                                │ 命中缓存?     │ 未命中
                                ▼               ▼
                         本地缓存文件      内部后端 8091
                         (30天TTL,秒回)   (PHP -S 无 router)
                                                │
                                                ▼
                                          预渲染服务 8099
                                          (Playwright 无头渲染)
                                          · 2 worker 线程
                                          · 等 #*-detail 渲染完成(>30字)
                                          · 返回完整 outerHTML
                                                │
                                                ▼
                          router.php 重写 canonical+TDK → 返回爬虫
```

**死锁规避**：PHP 内置服务器是单线程的。公网 8090 若在处理爬虫请求时回连 *自身* 让 Chromium 抓取，会自锁。因此预渲染抓取指向**独立的内部后端 8091**（另一 worker），三者各占独立进程，互不阻塞。

---

## 3. 代码清单（均已落地并验证）

| 文件 | 角色 | 关键机制 |
|---|---|---|
| `/var/www/lecms/prerender/router.php` | 公网入口拦截器（PHP 内置服务器 router） | 识别 **24 种**爬虫 UA；仅对 3 类动态参数页触发；30 天文件缓存；重写 `canonical`（还原公网域名）与 TDK（参数专属）；失败回退空壳；`?__prerender=1` 调试开关 |
| `/var/www/lecms/prerender/prender.py` | 预渲染服务（Flask + Playwright） | 2 个 worker 线程各持一个 Chromium（专用线程模型，规避线程切换崩溃）；等待 `#char-detail/#surname-detail/#mingzi-detail` 渲染完成（内容 >30 字）；超时降级；`/render?url=` 接口、`/healthz` 健康检查 |
| `/var/www/lecms/prerender/cache/` | 预渲染缓存目录 | 按内部地址 md5 落地 `.html`，30 天 TTL |
| `/workspace/.cloudstudio` | 部署启动配置 | 固化「启动 MariaDB → 内部后端 8091 → 预渲染 8099 → 公网 router 8090」四级启动顺序，**沙箱重启自动恢复** |

> 三处代码副本（`/var/www/lecms/view/haoiming`、`/workspace/lecms-integration/theme/haoiming`、`/workspace/qiming-system/assets`）的 `main.7.js` 与 HTML 已同步一致。

---

## 4. 验证结果（实测）

### 4.1 三类页面预渲染内容正确性

| 页面 | 爬虫 UA | HTTP | 体积 | 专属标题 | 真实内容命中 |
|---|---|---|---|---|---|
| `char.html?c=林` | Baiduspider | 200 | 19.3 KB | `林字寓意_林字起名含义、五行属性与康熙笔画 - 好名网` | 15 处「林字」 |
| `surname.html?c=李` | Googlebot | 200 | 13.8 KB | `李姓起名_李姓男孩女孩名字大全与八字起名 - 好名网` | 10 处「李姓」 |
| `mingzi.html?name=李明轩` | Baiduspider | 200 | 15 KB | `李明轩名字寓意_李明轩姓名解析、五格数理与打分 - 好名网` | 13 处「李明轩」 |
| `surname.html?c=王` | Baiduspider | 200 | 13.8 KB | `王姓起名_...` | 正确 |
| `char.html?c=森` | Baiduspider | 200 | 18.9 KB | `森字寓意_...` | 正确 |
| `mingzi.html?name=张伟` | Baiduspider | 200 | 11.6 KB | `张伟名字寓意_...` | 正确 |

### 4.2 性能

| 场景 | 耗时 |
|---|---|
| 首屏渲染（Chromium 无头渲染一次） | **0.38–0.48 秒** |
| 缓存命中（已渲染过） | **0.00046 秒**（近瞬时） |

首屏本就 <0.5s，而二次爬虫（同一参数）直接命中本地缓存，对抓取频率极高的搜索引擎几乎零成本。

### 4.3 UA 分流正确性

| 访问方 | UA | 结果 |
|---|---|---|
| 搜索引擎爬虫 | Baiduspider / Googlebot | 拿到完整预渲染 HTML（带 `X-Prerendered: 1` 头） |
| 真实用户 | 普通浏览器 Chrome | 拿 SPA 空壳（1.7 KB），前端 JS 正常渲染，**体验不变** |
| 调试 | 任意 UA + `?__prerender=1` | 强制预渲染（便于人工验证，不影响正常用户） |

### 4.4 容错

- 预渲染服务不可用 / 超时 → router 回退到 SPA 空壳（与改造前一致，**不会比现状更差**）。
- 缓存 TTL 30 天，内容更新后旧缓存自然过期；也可手动 `rm /var/www/lecms/prerender/cache/*.html` 立即刷新。

---

## 5. ⚠️ 当前预览环境的限制（务必阅读）

**方案 A 在应用层已 100% 完备，但当前 `*.app.workbuddy.host` 预览环境存在一个平台层限制，会挡住真实爬虫：**

- workbuddy 预览域要求访问携带 `?sharecode=<令牌>`，否则代理层直接返回 `403 链接不完整`。
- 真实搜索引擎爬虫（Baiduspider 等）**不会携带这个分享参数**，因此在**当前预览环境**下，爬虫请求会被代理拦截在应用层之前，预渲染逻辑根本触发不到。

**这并非代码缺陷，而是 workbuddy 平台的"分享链接"鉴权机制。**

> 已实测：
> - 带 `?sharecode=` + 爬虫 UA → 预渲染完美生效（200 / 19KB / 专属内容）。
> - 不带 `?sharecode=` + 爬虫 UA → 代理返回 403（平台限制，非应用问题）。

### 用户需要做什么

把这套代码部署到**自有域名服务器**（如阿里云 / 腾讯云的 ECS + Nginx/Apache，或任意支持 PHP + Python 的环境）后：
1. 没有 sharecode 代理层 → 爬虫可直接到达应用 → 方案 A 立即生效。
2. 搜索引擎即可抓到三类动态页的真实渲染内容。

---

## 6. 自有域名部署落地步骤

1. **环境准备**
   - PHP ≥ 7.4（内置 `php -S` 或改用 Nginx + php-fpm）
   - Python 3.11 + `pip install flask playwright` + `playwright install chromium`
   - MariaDB / MySQL（LECMS 数据库）

2. **三服务启动**（参考已固化的 `/workspace/.cloudstudio` 启动逻辑）
   ```
   service mariadb start
   php -S 127.0.0.1:8091 -t /var/www/lecms &          # 内部后端
   python3 /var/www/lecms/prerender/prender.py &       # 预渲染服务(8099)
   php -S 0.0.0.0:8090 -t /var/www/lecms /var/www/lecms/prerender/router.php  # 公网入口
   ```
   > 生产可用 Nginx 反向代理 8090，并处理静态资源（CSS/JS/图片）直接由 Nginx 返回，仅把 PHP 请求转给 8090。

3. **改 `router.php` 一行**（当前硬编码了 https，自有域名若无 SSL 需调整）
   - 第 79 行 `$scheme = 'https';` 在 http 站点改为动态判断，或直接按你的协议设定。

4. **验证**
   - `curl -A "Baiduspider/2.0" https://你的域名/view/haoiming/tool/char.html?c=林` 应返回含「林字寓意」的完整 HTML。
   - `curl -A "Mozilla/5.0" https://你的域名/...` 应返回 SPA 空壳（用户端正常）。

---

## 7. 运维要点

| 项 | 说明 |
|---|---|
| 缓存位置 | `/var/www/lecms/prerender/cache/*.html` |
| 缓存 TTL | 30 天，到期自动重渲染 |
| 紧急刷新 | 删除 `cache/*.html` 即可强制下次重新渲染 |
| 健康检查 | `curl http://127.0.0.1:8099/healthz` 返回 `ok` |
| 日志 | `prender.log`（渲染）、`backend.log`（内部后端）、`/var/log/php*` 或 router 错误经 `error_log` 进 syslog |
| 资源占用 | 每个 worker 持 1 个 Chromium，2 worker 约 300–500 MB 内存；可按服务器配置调整 `N_WORKERS` |

---

## 8. 结论

方案 A 已完整实现、充分验证、并固化为自动化启动。在**真实自有域名部署**场景下，三类动态参数页对搜索引擎将呈现完整渲染内容，从根本上解决"工具页无法收录"的瓶颈。

**下一步（见《SEO 收录落地清单》）：**
1. 部署到自有域名（去 sharecode 限制）；
2. 提交 `sitemap.xml` 到百度搜索资源平台 + Google Search Console；
3. 配置主动推送 API（已附脚本模板），新页面上线即通知搜索引擎。
