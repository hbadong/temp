# 好名网 SEO 优化修复报告

**日期**：2026-10-25
**对象**：好名网智能起名系统（本地 127.0.0.1:8090 验证，生产域名 ad6acb5cb3aeb83e6.app.workbuddy.link）
**依据**：《好名网 SEO 布局审计报告》（seo-audit-20261025.md）问题清单 C1/H1/H2/M1/M3/M4/M5/L1

---

## 一、修复总览

| # | 审计问题 | 严重度 | 状态 | 验证方式 |
|---|---|---|---|---|
| C1 | robots.txt 声明外站 sitemap（7131 个 qiming.cn URL） | Critical | ✅ 已修复 | curl robots.txt 仅含 sitemap.xml；sitemap-dict.xml 移出 web 根 |
| H1 | 软 404：无效字符生成 200 页面 | High | ✅ 已修复 | 爬虫 UA 验证 7 组用例全部正确注入/不注入 noindex |
| H2 | 静态资源零缓存 + 无 gzip | High | ✅ 已修复 | curl -I 验证 gzip + ETag + Cache-Control + 304 |
| M3 | 安全响应头缺失 | Medium | ✅ 已修复 | PHP 页与静态资源均带三头 |
| M4 | 矩阵页 TDK 重复 + sitemap 提交非规范 URL | Medium | ✅ 已修复 | 25 组矩阵 TDK 唯一；sitemap 矩阵 URL 605→0 |
| M1 | 落地页群缺乏核心入口内链 | Medium | ✅ 已修复 | kxzd 80 + search 40 + 首页 60 条静态内链 |
| M5 | 参数顺序变体未规范化 | Medium | ✅ 已修复 | JS 规范化 + 回归通过 |
| L1 | 结构化数据缺失 | Low | ✅ 已修复 | char=DefinedTerm / faq=FAQPage(20问) / 全站 @graph |
| M2 | 渲染阻塞资源 | Medium | ⏸ 部分（gzip 已大幅缓解；JS 拆分属重构，另行排期） | Lighthouse 性能基线见下 |

---

## 二、逐项修复明细

### C1 robots.txt 外站 sitemap 污染（Critical）

- `robots.txt` 删除 `sitemap-dict.xml` 声明行，只保留 `sitemap.xml`。
- `sitemap-dict.xml`（454KB，7131 个 qiming.cn URL）移出 web 根目录，归档至 `scripts/archive/sitemap-dict-qiming-cn.xml`（仅仓库留存，不暴露公网）。
- `scripts/dict_gen.py`：sitemap 产物改写 `archive/` 目录，杜绝再生污染。
- `scripts/gen_sitemap.py`：robots.txt 的 Sitemap 声明改为**规范化重写**（清除一切非 sitemap.xml 声明后再写入），而非此前的「缺失才追加」。

### H1 软 404 防护（High）

`main.7.js` 新增 SEO 工具组（`seoNoindex` / `inCharPool` / `escHtml`），三类动态页全部覆盖：

| 页面 | noindex 条件 | 说明 |
|---|---|---|
| `char.html?c=X` | X 不在 `HM_DATA.CHARS` | 字库外字符（含无效字符串/空值）走《说文解字》降级页或未查到态，均 noindex |
| `surname.html?c=X` | X 不在 `HM_DATA.SURNAMES` | 未收录姓氏不再产生可收录空页 |
| `mingzi.html?name=X` | 非纯汉字或含字库（CHARS∪SURNAMES∪IDIOM_CHARS）外字符 | 杜绝 `?name=` 无限 URL 空间 |

同时修复了降级分支的用户输入直插 innerHTML 的 XSS 隐患（统一 `escHtml` 转义）。
预渲染管线（Chromium）执行同一套 JS，爬虫拿到的 HTML 中 noindex meta 已生效（30 天缓存）。

**附加改进**：`char.html` 无参数时（导航与 sitemap 可达的工具落地页）由「未查到『』字」错误态改为**引导态落地页**（使用说明 + 示例链接 + 五行分类入口 + 三大 CTA），索引内容与用户所见一致。

### H2 静态资源 gzip + 缓存（High）

`prerender/router.php`（PHP 内置服务器路由器，全站请求入口）新增静态文本资源快路径：

- **gzip**：js/css/json/xml/txt/html/svg，>512B 且客户端声明 `Accept-Encoding: gzip` 时启用（`Vary: Accept-Encoding`）。实测 `main.7.js` 195,528B → 60,647B（**省 69%**）。
- **ETag + 304**：mtime-size 指纹，条件请求直接 304。
- **Cache-Control 分级**：
  - 版本化资源（`?v=` 查询串或 `.7.js/.7.css` 命名）→ `public, max-age=2592000, immutable`（30 天）
  - `sitemap.xml` / `robots.txt` → `public, max-age=3600`
  - 其它页面外壳 → `public, max-age=300, must-revalidate`
- 二进制资源（图片/字体）不走快路径，交由内置服务器原生处理（保留 Range 支持）。
- 目录穿越防护：realpath 必须位于 DOCROOT 内。
- 预渲染 HTML 输出同样走 gzip。

### M3 安全响应头（Medium）

router.php 顶层对**所有请求**注入：`X-Content-Type-Options: nosniff`、`X-Frame-Options: SAMEORIGIN`、`Referrer-Policy: strict-origin-when-cross-origin`。实测 PHP 动态页（index.php）与静态资源均生效（返回 false 放行时头部保留）。

### M4 矩阵页 TDK 差异化 + sitemap 精简（Medium）

- **根因**：router.php 此前只转发 `c` 参数给渲染层，`wx` 被剥离，导致 main.7.js:1831 的矩阵 TDK 分支永不执行，605 个矩阵页与主页 TDK 完全雷同。
- **修复**：surname 页转发 `wx`（限定金木水火土），`rewriteTdk` 服务端生成差异化 TDK（与 JS 公式一致）：「王姓五行属木的名字_王姓五行缺木怎么取名 - 好名网」。
- **canonical 不变**：矩阵页仍集中到 `surname.html?c=X`（权重集中设计保留）。
- **sitemap**：605 个矩阵 URL 全部撤下（canonical 指向别处的 URL 不提交），仅保留 121 个姓氏主页；矩阵页经站内互链发现（kxzd 五行专题入口、姓氏页五行 chips）。
- sitemap 总量 1477 → 886（单字 704 + 姓氏 121 + 工具 20 + 栏目文章 + 生肖 12 + faq/lib）。

### M1 核心页落地页入口内链（Medium）

新增「起名用字热度索引」静态区块（数据驱动：卜易居热度 Top ∩ 本站字库 704 字，Node 真值校验全部在库）：

| 页面 | 区块 | 链接数 |
|---|---|---|
| `kxzd.html` | 起名用字热度索引 · Top 80 | 80 条 `char.html?c=X` |
| `search.html` | 热门起名用字直达 · Top 40 | 40 条 |
| 首页 `index.htm` | 热门起名用字 · 常用字索引 | 60 条 |

静态 HTML 直链（非 JS 注入），爬虫可直接发现；每卡含拼音 + 五行 + 康熙笔画，title 属性带寓意文案。1400+ 落地页从此拥有来自核心页的权重入口。

### M5 参数顺序规范化（Medium）

`normalizeToolParams()`：char/surname/mingzi/shengxiao 页启动时将 URL 参数规范化为固定顺序（c → wx → name → z，其余参数按 key 排序保留 sharecode），`history.replaceState` 无刷新替换，消除 `?wx=木&c=王` 类重复内容入口。

### L1 结构化数据增强（Low）

排查发现审计「JSON-LD 0 处」的**真正根因**：全站多页核心内容纯 JS 渲染，爬虫（无 JS）只看到空壳。除既有的 char=DefinedTerm、surname=WebPage+Breadcrumb 外：

- **预渲染管线扩展**：router.php + prender.py 新增 8 类内容页（faq / shengxiao / poems 系 5 页 / baijiaxing / lib）的爬虫预渲染。修复后爬虫可见：
  - `faq.html`：20 组 FAQ 全文 + **FAQPage** JSON-LD（20 问）
  - `baijiaxing.html`：**121 个姓氏卡片**（全站姓氏落地页主内链枢纽，此前对爬虫完全不可见）
  - `shengxiao.html?z=X`：12 个生肖专题（默认马年）
  - `poems/shijing/chuci/tangshi/songci`：名句库内容
  - `lib.html`：名字库
- 各页同时获得 WebApplication + BreadcrumbList + WebSite @graph。

---

## 三、验证结果

| 验证项 | 结果 |
|---|---|
| Lighthouse SEO（首页 / char?c=林 / faq） | **100 / 100 / 100** |
| 单字页 TDK 唯一性（26 样本） | title 26/26、desc 26/26 唯一，0 误伤 noindex |
| 矩阵页 TDK（25 样本） | 25/25 唯一且含「五行属X」；canonical 25/25 集中 |
| noindex 防护（7 用例） | 7/7 正确 |
| sitemap | 886 URL，矩阵 URL 0 个，抽样 25/25 返回 200 |
| robots.txt | 仅 sitemap.xml；sitemap-dict.xml 公网不可达 |
| gzip / ETag / 304 / Cache-Control | 全部生效（main.7.js 省 69% 传输） |
| 安全响应头 | PHP 页 + 静态资源全覆盖 |
| 内链区块 | 180 条静态直链，Node 真值校验全部指向字库内字符 |
| 移动端溢出（5 页） | 无横向溢出 |
| E2E 回归（test_final.py 五项） | 全部通过 |
| JS 错误 | 0 |

---

## 四、变更文件清单

**服务端**（`/var/www/lecms/`，已归档至 `lecms-integration/server/`）：
- `prerender/router.php` — gzip/缓存/安全头/wx 转发/内容页预渲染扩展
- `prerender/prender.py` — DETAIL_SELECTORS 新增 8 类内容页
- `robots.txt` — 删除外站 sitemap 声明

**主题**（`view/haoiming/` ↔ `theme/haoiming/` 已 rsync 一致）：
- `js/main.7.js` — noindex 防护组 / 参数规范化 / char 落地态 / 版本号 v=20261025d
- `tool/kxzd.html`、`tool/search.html`、`index.htm` — 热度索引内链区块
- 全部工具页 — main.7.js 版本号刷新

**脚本**（`lecms-integration/scripts/`）：
- `gen_sitemap.py` — 矩阵 URL 撤下 / faq+lib+生肖 12 纳入 / robots 规范化重写
- `dict_gen.py` — sitemap 产物改归档目录
- 新增 `verify_seo.py`（SEO 验证）、`check_mobile.py`（移动端溢出诊断）、`shot_seo.py`/`shot_seo2.py`（验证截图）
- `archive/` — 竞品 sitemap 归档（不暴露公网）

---

## 五、后续建议（未在本次范围）

1. **M2 剩余**：main.7.js（188KB）按页面拆分 + 关键 CSS 内联，需前端重构排期；gzip 已缓解传输量。
2. **数据接入**：Search Console / 百度站长平台收录与点击数据接入后复评矩阵页与生肖页表现。
3. **缓存运维**：主题文件（main.7.js / data.7.js / 工具页）变更后需清空 `prerender/cache/*.html`（30 天 TTL），建议纳入发布流程。
4. **shengxiao 矩阵**：`?z=X` 十二页已预渲染 + 入 sitemap，观察收录后再评估是否扩 `?z=X&性别` 维度。

---

*报告生成：2026-10-25 · 好名网 SEO 优化修复（验证：Lighthouse 12.8.2 + 爬虫 UA 预渲染 + Node 数据真值 + E2E 回归）*
