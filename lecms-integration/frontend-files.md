# 部署后前端可见文件清单

> 实测环境：线上运行实例（2026-10-25，SEO 第二十四轮后）
> 「前端可见」= 访客浏览器能请求/渲染到的文件；「不可见」= 服务器存在但被 Nginx 拦截或无 URL 映射

---

## 一、首页实测：浏览器真实加载的文件（33 个唯一请求）

用无头浏览器访问首页抓取，去重后：

| # | 类型 | 文件 | 说明 |
|---|---|---|---|
| 1 | 文档 | `/` | PHP 动态生成的 HTML（含内联关键 CSS 8.8KB） |
| 2-5 | JS | `/view/haoiming/js/data.7.js`（134KB）<br>`/view/haoiming/js/shuowen.7.js`（147KB）<br>`/view/haoiming/js/engine.7.js`（111KB）<br>`/view/haoiming/js/main.7.js`（198KB） | 字库/说文数据/起名引擎/全站交互；defer 加载、gzip 后约 200KB 合计 |
| 6 | CSS | `/view/haoiming/css/style.7.css`（86KB） | 异步加载（print→all 技巧），gzip 后约 15KB |
| 7 | 图片 | `/view/haoiming/img/banner-default.svg` | 首页水墨横幅背景 |
| 8-31 | 图片×24 | `thumb.php?src=//upload/article/art-*.jpg&w=68&h=68` | 文章封面缩图（PHP 动态生成，源图在 `upload/article/`） |
| 32 | 接口 | `stats-api.php?action=get` | 全站统计数字（热门字/名计数） |
| 33 | JS | ~~`/view/haoiming/js/strokes-full.js`~~ | **已下线**（见下方优化记录） |

> 首屏总传输量：**292 KiB**（Lighthouse 实测）；Performance 96 / SEO 100

### 本轮优化记录（采集过程中发现并修复）

首页原本还会多加载 `strokes-full.js`（康熙笔画全量库 25100 字，222KB）——原因是 `initScore()`/`initMingzi()` 把整个函数体包在「懒加载笔画库」回调里，导致所有页面（含没有评分容器的首页）都预热该库。已改为「先检查容器、无容器不加载」：

- 首页：**0 个全量库请求**（-222KB）
- score.html / mingzi.html / char.html：照常按需加载，功能零影响（E2E 五项回归通过）

---

## 二、页面级：前端可访问的 URL 与文件

### 2.1 sitemap 提交的 886 个 URL（规范收录面）

| 分类 | 数量 | URL 形态 | 实际文件 |
|---|---|---|---|
| 首页 | 1 | `/` | PHP 动态生成（index.php → view/haoiming/index.htm） |
| 单字页 | 704 | `/view/haoiming/tool/char.html?c=林` | 同一个静态文件 `tool/char.html`，JS 按参数渲染 704 种内容 |
| 姓氏矩阵页 | 121 | `/view/haoiming/tool/surname.html?c=王` | 同一个 `tool/surname.html`，JS 渲染 121 个姓氏 |
| 工具页（无参数） | 20 | `/view/haoiming/tool/faq.html` 等 | `tool/` 下 20 个静态 HTML（faq/百家姓/生肖/典籍/文库/kxzd/search…） |
| 生肖页 | 12 | `/view/haoiming/tool/shengxiao.html?z=马` | 同一个 `tool/shengxiao.html` |
| 文章/分类 | 24 | `/index.php?cate--cid-N`、`/index.php?show--cid-N-id-N.html` | PHP 动态生成（20 篇文章 + 4 个分类） |
| 其他 | 4 | — | 服务页等 |

> 注意：704+121+12 是「一个文件 × N 种参数渲染」，物理文件只有 28 个；矩阵页 canonical 集中到姓氏主页，sitemap 只提交规范 URL。

### 2.2 词典静态页：7131 个 HTML（可访问，未进 sitemap）

| 目录 | 文件数 | URL 形态 | 说明 |
|---|---|---|---|
| `/chengyu/` | 1366 | `/chengyu/xxx.html` | 成语词典页 |
| `/cidian/` | 1404 | `/cidian/xxx.html` | 词语词典页 |
| `/zidian/` | 4361 | `/zidian/xxx.html` | 汉语字典页 |

这三个目录是历史数据沉淀，全部静态直访；当前 sitemap 策略未包含它们（如需收录可扩展 `gen_sitemap.py`）。

### 2.3 管理后台

| URL | 可见性 |
|---|---|
| `/admin` | 需登录；生产 Nginx 再加 IP 白名单（`haoiming.conf` 已配） |

---

## 三、静态资源文件明细（按目录）

### 主题目录 `view/haoiming/`（44 个 HTML + 资源）

```
js/     data.7.js(134K)  engine.7.js(111K)  main.7.js(198K)  shuowen.7.js(147K)  dict.7.js(3K)
        strokes-full.js(222K) ← 仅 score/mingzi/char 页按需加载
css/    style.7.css(86K)  style.css(旧版)
img/    12 个 SVG（banner 系列 + 图标）
tool/   28 个静态工具页 HTML
*.htm   inc-header / inc-footer / index / 文章模板等 16 个 LECMS 模板
```

### 上传资产 `upload/`

| 内容 | 数量 |
|---|---|
| `upload/article/` 文章封面图 | 26 张（首页经 thumb.php 输出 68×68 缩图） |
| 其他上传图片 | 共 139 张（jpg/png/webp/gif） |

### 根目录直访文件

| 文件 | 说明 |
|---|---|
| `favicon.ico` | 站点图标 |
| `robots.txt` | 爬虫协议（只声明本站 sitemap.xml） |
| `sitemap.xml` | 886 URL 站点地图 |
| `thumb.php` | 动态缩图接口（后台/前台调图） |
| `stats-api.php` | 统计接口 |

---

## 四、服务器存在但前端**不可见**的文件

| 路径 | 为什么不可见 |
|---|---|
| `lecms/config/config.inc.php` | 含数据库密码/加密 KEY，无 URL 映射 |
| `lecms/`（框架内核） | Nginx `deny all` |
| `install/`（安装器） | Nginx `deny all`（建议部署后直接删除） |
| `log/`（运行日志） | Nginx `deny all` |
| `runcache/`（模板编译缓存） | Nginx `deny all`（可随时清空重建） |
| `data/stats.json` | Nginx `deny all`；统计数据只经 `stats-api.php` 输出 |
| `prerender/`（网关/渲染服务/缓存） | `gateway.php` 是 Nginx `internal` location，外网直访 404；`prender.py` 只监听 127.0.0.1:8099；`cache/` 无 URL 映射 |
| `.git/` | Nginx `deny all`（且不建议部署 .git 到生产） |
| `admin/` 内的 PHP 源码 | 后台需登录后才执行，未授权不可见 |

---

## 五、一句话总结

前端看得见的是「**28 个工具页外壳（JS 渲染出 886 个收录页面）+ 7131 个词典静态页 + 20 篇文章 + 6 个 JS/CSS + 151 张图片 + 4 个根目录直访文件**」；框架内核、配置、日志、预Renderer 缓存全部沉在 Nginx 防护层之后。
