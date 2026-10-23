# 修复报告：首页宝宝起名 404 + 统计 API CORS

## 问题 1：在线域名失效（整站 403/404）

- **现象**：`workbuddy.host` / `workbuddy.link` DNS 均解析失效，curl 返回 404
- **修复**：重新执行发布（publish.js），平台恢复实例并分配域名
- **新链接**：https://ad6acb5cb3aeb83e6.app.workbuddy.link/?sharecode=NWEUXAJJEF

## 问题 2：首页"立即宝宝起名"跳 /naming.html 404（核心 bug）

### 根因
`main.7.js` 中 `#form-qmh` 被**绑定了两个 submit handler**：

| 位置 | 函数 | 路径写法 | 绑定时机 |
|---|---|---|---|
| 296 行 | `homePage()`（toolUrl 修复版） | `/view/haoiming/tool/naming.html` ✓ | DOMContentLoaded 先 |
| 995 行 | `initNamingForms()` | `naming.html`（裸相对路径）✗ | DOMContentLoaded 后 |

`initNamingForms()` 用 `$$("form[id^=form-qm]")` 通配匹配，**前缀 `form-qm` 误中 `form-qmh`**。后绑定的 handler 后执行，`location.href` 被覆盖为裸相对路径，在首页 `/` 下解析为 `/naming.html` → 404。

### 修复
- 995 行：`` let url = `naming.html?...` `` → `` let url = `${toolUrl("naming.html")}?...` ``
- 1025 行（同类漏网）：`score.html?name=` → `${toolUrl("score.html")}?name=`
- 同步全部副本：lecms 主题 / 静态站 / main.js 旧版（各 2 处）

## 问题 3：统计 API 线上 CORS 失败

- **现象**：`window._LE.uri` = `http://127.0.0.1:8090/`（PHP 本地配置），线上浏览器 fetch 该绝对地址被 CORS 拦截，统计 track/get 全部失败
- **修复**：新增 `statsRoot()`，从 `window._LE.tpl`（`/view/haoiming/`）提取站点根（`/`），`statsTrack`/`statsGet` 改走**同源根相对路径** `/stats-api.php`

## 回归测试结果（在线）

| 项目 | 结果 |
|---|---|
| 桌面端 16 页（首页 + 14 工具页 + 栏目页） | 16/16 HTTP 200 |
| 首页三 Tab 表单（起名/测试/打分） | 3/3 跳转正确 |
| 命名页输出（四柱/五行/藏干/12 候选/锁定） | 完整 |
| 移动端 375px 5 页 | 0 溢出 |
| 统计链路（get/track + 首页统计条 63/41/14/0） | 真实数据 |
| 付费闭环（弹窗→选标准版→模拟支付→刷新持久） | 通过 |

## 涉及文件
- `/var/www/lecms/view/haoiming/js/main.7.js`（3 处修复 + statsRoot 注入）
- `/workspace/lecms-integration/theme/haoiming/js/main.7.js`（同步）
- `/workspace/qiming-system/assets/js/main.7.js`（同步）
- 三个位置的 `main.js` 旧版（各 2 处补丁）

---

# 第二轮：SEO 结构升级 + 文章链接修复（巡检续作）

## 巡检发现
1. **文章详情页链接 27 处指向 `http://127.0.0.1:8090`**（CMS 用 `$v[url]` 绝对地址拼接，反代环境下点击跳到不可达的本地地址）
2. **首页无 h1/h2**——banner 标题与栏目标题全是 div/h3，搜索引擎无法识别页面主题结构
3. **无 OG 标签**——社交平台分享无标题/摘要预览

## 修复
- **main.7.js 新增 `fixLocalLinks()`**：正则匹配 `127.0.0.1|localhost` 绝对链接，改写为同源根相对路径；在 DOMContentLoaded + MutationObserver 双钩子调用（服务端渲染与 JS 动态渲染均覆盖）
- **index.htm**：`<div class="bh">` → `<h1 class="bh">`（CSS 已有 h1.bh 同款规则，视觉零变化）；4 个文章栏目标题 h3 → h2
- **inc-header.htm**：logo 链接 `{$cfg[weburl]}` → `/`；head 增加 OG 四件套（og:type / og:site_name / og:title / og:description）
- **style.7.css**：`.panel h2` / `.art-col h2` 样式别名，保证层级升级后视觉不变
- 清理 LECMS runcache 编译缓存使模板生效；模板副本同步主题包

## 最终回归（在线，19/19 通过）

| 测试项 | 结果 |
|---|---|
| 16 个页面 HTTP 200（含文章详情页、栏目页） | 16/16 |
| 首页三 Tab 表单跳转 | 3/3 |
| 首页统计条真实数据 | 70 查询 / 47 方案 / 15 评测 / 1 已开通 |
| 文章页结构（h1/插图/正文/无本地残留链接） | 通过 |
| SEO 结构（1×h1 + 4×h2 + OG 四件套 + 本地链接 0 残留） | 通过 |
| 付费闭环（刷新后解锁持久） | 通过（上一轮） |
| 移动端 5 页零溢出 | 通过（上一轮） |

---

# 第三轮：REA 对标原站 · 典籍专题页补齐

## 对标方法
用 REA（CDP）抓取原站 qiming.cn 首页/唐诗起名专题页，与本站做结构对比。

## 发现的差异
原站诗词起名拆分为 **4 个独立典籍落地页**（诗经/楚辞/唐诗/宋词，各有独立 URL 与 SEO title），本站仅有 poems.html 合并页（站内已有四分类 Tab，但无独立 URL）。

## 修复
1. **新增 4 个典籍专题页**（tool/shijing|chuci|tangshi|songci.html）：
   - 独立 title/description/keywords（对齐原站关键词策略）
   - 独立 h1 与典籍介绍文案（女诗经/男楚辞传统、名家风格等）
   - 默认选中对应典籍 Tab，打开即见该典籍诗句卡片
2. **修复 initPoems 硬编码 bug**：原 `let cat = "all"` 改为读取 `.tab.on` 的 data-cat，否则专题页默认渲染「全部」
3. **footer 快捷导航**加入四典籍入口（main.7.js 渲染）
4. **副本同步**：静态站 4 页（修正资源路径为 assets/ 模式、与 poems.html 一致的 _LE 省略写法）、主题包 JS/CSS 全量对齐
5. 静态站 CSS 差异（h2 别名）同步

## 最终回归（在线）

| 测试项 | 结果 |
|---|---|
| 22 个页面 HTTP 200（新增 4 典籍页 + 成人改名页） | **22/22** |
| 专题页默认分类渲染 | 诗经12/楚辞8/唐诗13/宋词11 张卡片，出处标注正确 |
| poems.html Tab 切换 | 44 张 → 楚辞 8 张 |
| 静态站专题页资源 | 页面/CSS/JS 全 200 |
| 副本一致性（主题包 7 项 + 静态站 5 项） | 全部一致 |

---

# 第四轮：内链体系完善 + SEO 收录增强

## 诊断发现
1. **工具群与内容群完全断裂**：14 个工具页正文区 0 个文章入口，爬虫从工具页进入无法发现 24 篇文章
2. **侧栏「阅读排行榜」链接错误**：`sideHtml()` 把 10 个文章标题全部链到 `score.html` 工具页（锚文本与目标不符）
3. **mingzi 名字详情页零文章出口**：唯一未用 withLayout 的详情页，无侧栏无正文文章区
4. 无 sitemap / robots 声明 / JSON-LD 结构化数据

## 实施
1. **sitemap.xml**：gen_sitemap.py 生成 107 URL（1 首页 + 18 工具 + 4 栏目 + 24 文章 + 60 单字落地页）
2. **robots.txt** 追加 Sitemap 声明
3. **JSON-LD**：injectJsonLd() 三类页面注入 @graph（WebSite+Organization / Article+BreadcrumbList / WebApplication+BreadcrumbList）
4. **正文自动内链**：initArticleAutoLinks() TreeWalker 关键词替换，15 词表、每词 1 次、每篇上限 8
5. **延伸阅读**：article_show.htm 加 `{block:list}` 同栏目 6 篇文章互链 + .art-more-list 样式
6. **文章扩充**：24 篇从 ~150-360 字符扩至 527-811 字符（按栏目 4 套模板）
7. **侧栏链接修复（本轮）**：data.7.js ARTICLES 注入 u 字段（24 处，标题与数据库精确匹配）；sideHtml 改 `${a.u || toolUrl("score.html")}`，静态站副本无 u 字段自动降级
8. **工具页底部区块（本轮）**：新增 injectToolArticles()，按页面主题注入 6 篇对应栏目文章；char/surname 靠侧栏 10 条不重复注入；mingzi 补入映射

## 最终回归（本地全量）

| 测试项 | 结果 |
|---|---|
| 文章群 24 篇（正文长/自动内链/延伸阅读/JSON-LD/本地残留链） | **24/24**（正文 500-686 字，自动内链共 84，延伸阅读共 144，残留 0） |
| 工具群 14 页（底部区块/侧栏真实文章/文章可达性） | **14/14**（底部 12 页 × 6 条，char/surname 侧栏 10 条，文章全部 200） |
| 移动端 375px 5 页 | 0 溢出，区块正常 |
| 副本一致性（静态站 + 集成主题） | diff 一致 |
| sitemap.xml / robots.txt | 200 |

详见《SEO内链优化专项报告.md》。
