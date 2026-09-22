---
name: lecms-game-template-dev
description: LeCMS 游戏主题模板开发全流程（PC+手机端双前端）。激活后先经三问门禁（AskUserQuestion 卡片式问答收集原网址/源码路径/主题名）。含 57 条核心沉淀速查（移动端 seajs/Vue 原版 JS 体系、共数据双前端自动切换、JS 交互陷阱、数据导入正文清理、滚动浮动条复刻、block cid 劫持、views 排序数据一致性、渲染中断排查、调试方法论）、全页面效果比对清单（首页/列表/详情/频道/搜索/单页 + 动态交互层 + 数据层）、模板语法限制（block参数静态化/{@expr}引号/loop键值）、分类/数据机制（type/upid/mid/alias/属性表）、自动化检查脚本（完整性/标签平衡/区块元素）、代码审查清单、Skill 自我迭代机制。详细流程见 references/ 子文件。
---

# LeCMS 游戏主题模板开发指南

基于实战项目总结。

## 输入 / 输出

| | 内容 |
|---|---|
| **输入** | 原网址地址（如 `https://www.youxilibao.com`）+ 参考网站源码地址（本地静态站点路径） |
| **输出** | 在 `view/{主题名}/` 下创建完整的主题模板目录（.htm + css/js/img） |

## 阶段〇：输入收集（三问门禁）

技能激活后**必须先完成三问**，三问全部完成才能进入核心流程。三问一律通过 **AskUserQuestion 工具**进行卡片式问答交互，禁止纯文本连珠提问、禁止 AI 擅自假设或用示例值代替：

| # | 问题 | 交互方式 |
|---|------|---------|
| 1 | **原网址地址** — 要复刻的目标网站 URL | AskUserQuestion 问题卡片，question 注明「选择 Other 并粘贴完整 URL（含 https://）」 |
| 2 | **参考网站源码地址** — 本地静态站点路径（包含 HTML/CSS/JS 源码目录） | 同一次 AskUserQuestion 的问题卡片，question 注明「选择 Other 并粘贴本地源码目录路径」 |
| 3 | **开发主题名称** — 默认 AI 按 §1.0 命名规则自动命名 | 同一次 AskUserQuestion 的问题卡片：选项 1 =「AI 自动命名（推荐）」，用户可点 Other 输入自定义名 |

### 三问交互流程（一次问答 + 确认摘要）

1. **一次性 AskUserQuestion**（三个问题放同一张卡片；用户首条消息已提供的输入视为已答，只放缺失项）：
   - 问题 1：header「原网址」— 自由输入，选项仅作格式示例占位
   - 问题 2：header「源码路径」— 自由输入，选项仅作格式示例占位
   - 问题 3：header「主题名」— 选项「AI 自动命名（推荐）」/ Other 自定义
2. **AI 命名**：用户选「AI 自动命名」时，拿到 URL 后按 §1.0 命名（域名主体优先 + Glob `view/*/info.ini` 冲突检查，重名自动调整）并在确认摘要中告知
3. **输出确认摘要**（原网址 / 源码路径 / 主题名，一行），再进入核心流程

**门禁规则**：
- 三问未全部完成前，不得 Read `references/` 进入分析阶段，不得创建任何文件或目录
- 问题 1、2 用户未给出真实值（只选了示例占位，或 URL 为明显占位域名如 example.com/test.com）时必须追问（重发 AskUserQuestion 只问缺失项），不得假设默认值、不得用示例 URL 代替
- 用户自定义主题名直接采用（仍需冲突检查，重名时告知用户并给替代名）
- 环境无 AskUserQuestion 工具时，降级为纯文本逐问：一次一问，收到回答再问下一问，三问顺序不变

## 核心流程

**输入收集（三问门禁）→ 分析原网站 → 推导模板结构 → 创建 view/ 目录 → 开发模板 → 后台配置 → 占位符替换 → 功能测试 → 效果比对 → 修复调整**

每个阶段的具体操作按需 Read `references/` 对应文件（详见文末索引）。

## 触发条件

用户提供以下输入时激活：
- 原网址地址 + 参考网站源码地址
- "复刻这个网站为 LeCMS 模板"
- "创建一个游戏主题模板"

用户提到以下关键词时激活：
- LeCMS 模板开发 / 主题开发 / 复刻网站
- 分析参考网站 / 网站结构分析
- 游戏模板 / 游戏下载站
- LeCMS 模型 / 分类 / 导航配置

## 知识库参考（开发必读）

遇到 LeCMS 机制问题时查阅 `knowledge/` 目录：

| 问题类型 | 文档（相对 skill 目录） | 内容 |
|---------|---------------------------|------|
| 主题目录结构、info.ini、show.jpg | `knowledge/LeCSM系统完整知识库/第六篇_主题开发.md` | 主题创建流程、模板查找优先级、CSS 体系、命名规范、DIY 功能 |
| 模板标签语法、Block 列表、变量 | `knowledge/LeCSM系统完整知识库/第五篇_模板引擎.md` | 编译流程、标签语法、Block 模块完整列表、返回值结构、常用变量 |
| 综合手册 | `knowledge/模板开发完整手册.md` | 系统概述、模板文件说明、创建主题步骤、标签语法、Block 详解、最佳实践、调试方法、检查清单 |
| 模型/控制器/数据库 | `knowledge/LeCSM系统完整知识库/第四篇_MVC 架构.md` | 模型基类、控制器基类、数据库操作 |
| Hook 机制、插件覆盖 | `knowledge/LeCSM系统完整知识库/第七篇_Hook 机制.md` | Hook 系统、插件模板覆盖 |
| 后台配置 | `knowledge/LeCSM系统完整知识库/第二篇_核心架构.md` | 核心架构、后台管理 |
| 路由系统 | `knowledge/LeCSM系统完整知识库/第三篇_路由系统.md` | URL 路由、伪静态 |
| 插件系统 | `knowledge/LeCSM系统完整知识库/第八篇_插件系统.md` | 插件架构、安装卸载 |
| 系统概论 | `knowledge/LeCSM系统完整知识库/第一篇_系统概论.md` | 整体架构、目录结构 |
| 安全与性能 | `knowledge/LeCSM系统完整知识库/第十一篇_安全与性能.md` | 安全机制、性能优化 |
| 开发规范与技巧 | `knowledge/LeCSM系统完整知识库/第十三篇_开发规范与技巧.md` | 编码规范、开发技巧 |

> **使用方式**：直接用 Read 工具读取，或 Grep 搜索关键词定位。

---

## 核心沉淀（快速查阅 — 高频内容，保留在主文件）

整个实战项目的精华清单。开发中遇到问题**先查这里**，再查知识库细节与 references/ 详细流程。

### 模板语法核心（7 条）

| # | 要点 | 说明 |
|---|------|------|
| 1 | **block 参数静态化** | `{block:xxx cid="{$var}"}` 中的 `{$var}` 不编译（addslashes+var_export 字符串化）。动态值靠 block 自动识别（不传 cid/mid 时自动读 `$_GET`）或 `{php}` 直接调用函数 |
| 2 | **loop 键是字符串** | `find_fetch` 返回数组键为 `{表}-{主键}-{值}`（如 `cms_article-id-5`），`$k` 非数字。序号一律用 block 自带 **`$v[xuhao]`**（1 起） |
| 3 | **`{@expr}` 手写引号** | `{@$v['dateline']}` ✅ / `{@$v[dateline]}` ❌（dateline 当常量报错）。`{if:}` 中的键自动加引号 |
| 4 | **无管道默认值** | `{$var\|'默认'}` 原样输出。用 `{if:$var}{$var}{else}默认{/if}` |
| 5 | **`{php}` 调 block 需触发** | 模板须有 `{block:xxx}{/block}` 空标签才会收集函数，`{php}` 中才能调用 `block_xxx()` |
| 6 | **详情页必须 global_show** | `{block:global_show show_prev_next="1" dateformat="Y-m-d" field_format="1"}{/block}` 生成 `$gdata`；面包屑用 `$cfg_var[url]/[name]`（`$gdata` 无分类字段） |
| 7 | **列表页用 global_cate** | `{block:global_cate pagenum="20"}{/block}` 自动识别当前分类+分页（`$gdata[list]`/`$gdata[pages]`）；`block:list_flag` 必须显式 `mid`（默认 2=文章） |

### 分类/数据机制核心（6 条）

| # | 要点 | 说明 |
|---|------|------|
| 8 | **叶子 type=0，顶级 type=1** | 叶子 type=1 → son_cids 空 → list/list_top/global_cate 全空 |
| 9 | **子分类 upid=父级** | upid=0 会变成顶级分类，频道页列表空 |
| 10 | **单页分类 mid=1** | mid=0 → `table_arr[0]` 越界报错 |
| 11 | **alias 需注册** | 分类 URL 解析用 `le_only_alias` 表 + `le_runtime` 缓存，SQL 改分类后要：`DELETE FROM le_only_alias` 重建 + `DELETE FROM le_runtime` |
| 12 | **属性表机制** | 推荐/热门/幻灯查 `cms_{model}_flag` 表（非主表 flags 字段），测试数据需同步填充 |
| 13 | **数据表核对** | 模型创建后检查：tag_data 联合主键 `(tagid,id)`、flag 表 `(flag,cid,id)`、views 表存在。写 SQL 前先 `SHOW TABLES` 核实表名（分类表是 `le_category` 而非 le_cms_category；站点配置存 `le_kv` 表 cfg JSON，直改后必须清 le_runtime，见 #20） |

### 对比验证核心（5 条）

| # | 要点 | 说明 |
|---|------|------|
| 14 | **模板文件静态对比** | 不依赖服务器（curl 可能被环境干扰）：对比模板 .htm 与参考网站源码的 section/元素 |
| 15 | **标签平衡检查** | `{block:}`/`{loop:}`/`{if:}`/`<section>` 开闭必须相等；**div 跳过**（`{if:}` 条件 div 静态不可见会误报） |
| 16 | **元素级逐项比对** | 关键 class/文案/按钮逐一对比 |
| 17 | **运行时逻辑验证** | 静态平衡 ≠ 运行时正确：条件 div（如 carousel xuhao 分组）需逻辑推演 + 页面实测 |
| 18 | **确认原站细节** | 注释内容（`<!-- -->` 内）、外链广告、浮动元素——原站没有的不要加，注释的不要误判 |

### 环境核心（3 条）

| # | 要点 | 说明 |
|---|------|------|
| 19 | **伪静态必须开启** | `lecms_parseurl=1`，nginx 下动态 URL `index.php?u=...` 不可用 |
| 20 | **三层缓存清理** | 模板修改清 `runcache/lecms_view/`；分类/配置修改清 `le_runtime`；alias 修改重建 `le_only_alias`。**直接 SQL 改 `le_kv` 的 cfg JSON（如 webname/seo_title 站点设置）后必须 `DELETE FROM le_runtime`**——前台 cfg 优先读 le_runtime 缓存，后台保存会自动清，直改库不会清 → 前台仍输出旧值 |
| 21 | **curl 异常 ≠ 模板问题** | 浏览器正常但 curl 返回其他网站内容 → 环境问题（CGI 端口/虚拟主机），改用模板静态对比验证 |

### 移动端主题核心（10 条）

| # | 要点 | 说明 |
|---|------|------|
| 22 | **共数据双前端** | 手机端与 PC 端共数据共模型同分类，只换前端模板。LeCMS 内置 `open_mobile_view=1` + `mobile_view={主题名}`（存于 le_kv 表 cfg JSON），移动 UA 自动切换主题（runtime_model.class.php 78-80 行），PC 继续原主题 |
| 23 | **原站 jquery 是定制版** | 955yx 类站点 jquery.min.js 内嵌 **seajs 2.2.0 + Vue 2.4.0 + TouchSlide 1.1**（页面无 seajs 标签也能 seajs.use）。**不要用官网 jQuery 替代**，否则全部交互失效 |
| 24 | **seajs base 必须配置** | `seajs.use('app/common')` 解析为 `base + 'app/common.js'`，base 默认=页面 URL 目录。页面内联 `seajs.config({ base: '{$cfg[tpl]}script/' })` 指向主题 script 目录，否则 common.js 404 |
| 25 | **common.js 实际路径** | 原站交互模块（AMD define 格式）在 `/statics/js/app/common.js`（≠ 页面模块名 'app/common'）；本地源码通常缺失，需在线抓取 |
| 26 | **loop 键值顺序** | `{loop:$arr $v $k}` 编译为 `foreach($arr as $k=>$v)`（**第一个=值、第二个=键**）。写反 → 面包屑链接/序号丢失 |
| 27 | **面包屑 place 链** | `$cfg_var[place]` = `[{顶级分类},{子分类}]` 数组（不含首页，末项=当前分类）。`{loop:$cfg_var[place] $p $k}{if:$k < count($cfg_var[place])-1}` 输出链接层，最后输出 h1 当前分类 |
| 28 | **list 不支持 views 排序** | `block:list`/`global_cate` 的 orderby 白名单仅 `id/dateline/lasttime/comments`；浏览量排行必须用 `block:list_top orderby="views"` |
| 29 | **原站 AJAX 接口不可复刻** | common.js 的 gamelist/newslist 等依赖原站 API——仅复刻前端交互，页面内联实现 |
| 30 | **图片防盗链 403** | 原站图片域名可能拒下——logo 用 GD + Windows 中文字体生成；模板图片直接输出真实 src |
| 31 | **缺失 JS 模块占位** | 原站已 404 的模块自建空占位：`define(function(require,exports,module){window.x={init:function(){}};module.exports={};})`，防 seajs 同步 require 抛错中断 |

### Windows/PHP 环境（4 条）

| # | 要点 | 说明 |
|---|------|------|
| 32 | **PHP 双引号插值坑** | PHP CLI 中 `"$v['dateline']"` 触发 `T_ENCAPSED_AND_WHITESPACE` 语法错误；批量替换用 `"\$v['dateline']"`（转义 $）或 heredoc 临时文件 |
| 33 | **bash 双引号变量吞噬** | bash 命令中 `{$v[dateline]}` 的花括号被 bash 扩展 → PHP 收到的字符串已变空、str_replace 静默失效；复杂替换一律写临时 .php 文件执行 |
| 34 | **Git Bash /tmp ≠ PHP /tmp** | curl/grep 的 /tmp（msys 路径）与 PHP `file_get_contents('/tmp/..')`（解析为 C:/Users/xxx/AppData/Local/Temp）**不是同一目录**；跨工具共享文件用项目内路径 |
| 35 | **sed -i 对 $ 模式失效** | Windows Git Bash 的 sed -i 替换含 `$` 的模板模式常不生效 → 用 PHP str_replace 批量改模板 |

### JS 交互陷阱（5 条，新实战补）

| # | 要点 | 说明 |
|---|------|------|
| 36 | **JS 双加载致命** | `<script src="script.js">` 只能在 inc-header 或 inc-footer 之一引用。**双加载导致 toggle 抵消**（移动菜单 toggle 一次 add 一次 remove → 永远不动）、addEventListener 绑定两次（排行翻页点击跳两页）、setInterval 重复（轮播加倍）。验证：渲染 HTML 后 `grep -c "script/index.js"` 必须等于 1 |
| 37 | **toggle 类名必须对照参考站** | 移动菜单展开类名是 `topnavOpen`（参考站 CSS `display:none → flex`），不是 `is-open` 等想当然名字。**先 grep 参考站 chunks CSS**（如 `.x__topnavOpen{display:flex}`）确认展开类，再用 `toggle('命名空间__topnavOpen')` 完整名 |
| 38 | **IIFE 内不能用 continue/break** | `(function(panel){ if(!track) continue; })(panels[i])` 是 `SyntaxError: Illegal continue statement`，**整个脚本挂掉，所有 JS 交互失效**。改用 `return`。**改完 JS 必跑 `node --check script/index.js`** |
| 39 | **footer 必须闭合 body/html** | inc-footer.htm 结尾应有 `</body></html>`，否则所有页面渲染 HTML 不规范。验证：每个页面渲染后 `grep -c "</body>"` 必须等于 1。**从旧主题复制的辅助模板常自带闭合**（`{inc:footer.htm}<script>seajs.use(...)</script></div></body></html>`）→ 批量移除尾部 seajs 块 + 重复闭合 |
| 40 | **node --check 是 JS 修改后必跑** | Windows Git Bash 下 `node --check script/index.js` 快速验证语法。捕获 IIFE continue、括号不配、未闭合字符串、未配 { } 等。**最致命语法错误让整个脚本不执行**（浏览器控制台无报错，因为脚本根本没运行），所有交互静默失效 |

### 数据导入与正文清理（3 条，新实战补）

| # | 要点 | 说明 |
|---|------|------|
| 41 | **正文 content 不能嵌页面结构** | 用爬虫把参考站详情页 HTML 整段存进 content 时，会把 `<section class="page-module__CBPw8q__card">...其他信息...</section>` 一并存入——模板渲染时与自己的"其他信息" card 重复。**清理策略**：PHP 脚本找 `<section class="...card">` 位置截断，再递归清理尾部 `</div></section>` 残留。先 dry-run 单条验证再批量 UPDATE，**必先备份数据库**（mysqldump 或 PHP var_export） |
| 42 | **richText 不能直接输出含 img 的 content** | content 开头若用 `<p><img shot1.jpg></p><p><img shot2.jpg></p>` 存截图，模板 richText 输出时截图会与 screenshotGallery **重复显示**。参考站 screenshotGallery 用独立字段取图，richText 是纯文字。**模板处理**：richText 用 `{php}` 块过滤 `<p><img></p>` 整段 + 孤立 `<img>` + 空 `<p></p>`（preg_replace 三次） |
| 43 | **heroSummary 单字段可能不存在或数据错** | 参考站每款软件有独立 heroSummary 简介，但本地可能无对应字段。**务实替代**：用 `{php}` 从 content 提取首段前 1-2 句（按句号截断，最长 90 字）。注意：**参考站该字段数据本身可能有错**（Photoshop 页面误用 Illustrator 简介"贝塞尔曲线/矢量无损缩放"——3 款软件错用同一段），本地用 content 提取更可靠 |

### 模板与组件一致性（1 条，新实战补）

| # | 要点 | 说明 |
|---|------|------|
| 44 | **复制旧主题时检查残留模板** | 复制 game955 等旧主题模板目录创建新主题（如 macfkm）时，article_list / article_show / game_list / game_show / comment / so / flags / tag_all / tag_list / tag_top 常被一并带入。这些模板带：① **seajs 引用**（新主题无 seajs 库 → 404）；② **旧版样式**（url-here / soft-list2 / list-tabs）；③ **自带 `</body></html>` 重复闭合**。新主题可能用不到。**批量处理**：PHP 正则移除尾部 seajs 块与重复闭合。新主题只保留自己风格的 5-8 个核心模板 |

### 动态组件复刻（1 条，新实战补）

| # | 要点 | 说明 |
|---|------|------|
| 45 | **滚动浮动条（Sticky Detail Bar）复刻** | 参考站详情页 `compactActionCard` 在 `window.scrollY > 360` 时浮现（CSS `position:fixed; top:110px; opacity:0 → 1; transition .22s; backdrop-filter:blur(12px); z-index:39`），对齐到带 `data-sticky-anchor="true"` 的元素（如"屏幕截图" card）位置。**JS 三件套**：scroll 监听切换 `compactActionCardVisible` 类 + scroll/resize/ResizeObserver 监听 anchor 的 `getBoundingClientRect()` 实时更新 style.left/width（内联覆盖 CSS）。**模板要点**：① 第一个 card 加 `data-sticky-anchor="true"`；② compactActionCard 放 `{inc:header.htm}` 之后；③ CSS 已有 `topnavOpen{display:flex}`、`compactActionCardVisible{opacity:1; visibility:visible; pointer-events:auto; transform:translateY(0)}` 等类；④ JS 中 scroll/resize/ResizeObserver 三处都要更新位置 |

### 数据一致性与排序核心（5 条，新实战补）

| # | 要点 | 说明 |
|---|------|------|
| 46 | **block cid 劫持** | `block_list_top`/`block_list` 不传 cid 时自动读 `$_GET['cid']`（block_list_top.lib.php:25-26）。在**详情页**调用会被劫持到当前内容分类；在**单页**（page 模型）调用被劫持到单页分类 → page 表直接 return 空 → 区块全空。**详情页/单页中调列表 block 必须显式传 cid**：`cid="0"`=全站（mid 定表），或传目标 cid。全站 views 排行写法：`{block:list_top cid="0" mid="6" orderby="views" limit="16"}` 或 `{php}` 中 `block_list_top(array('cid' => 0, 'mid' => 6, ...))` |
| 47 | **views 表垃圾行 → 渲染中断链** | views 表有记录但主表无对应 id（如 id=0 垃圾行）→ `block_list_top` views 排序取回该 id → `mget` 主表查无 → 该 key 返回**空占位**（mget 内部 NULL 占位，cache 关闭时经 db 层预赋空数组覆盖，最终为 array()）→ `format()` 对空值直接 return → foreach 里 `$v['xuhao'] = $xuhao` 把空占位转成**仅含 xuhao 一个键的数组** → 模板 `$v[size]` 报 `Undefined index: size`。**报错显示"缺字段"，实际是整行缺失**——不要顺着 size 排查字段，要查 views 表与主表的数据一致性 |
| 48 | **debug=2 渲染中断特征** | 调试模式下 notice 级错误也**中断渲染**，错误页 HTML（`<!DOCTYPE html>` + "Lecms 3.0.4 错误"）**嵌入在区块中间**（中断点前内容正常、后面是错误页）。识别：`grep -c '<!DOCTYPE' 渲染文件` >1 = 渲染中断；此时 HTTP 仍 200，**不能只看状态码判断页面正常**。生产部署把 config.inc.php 的 `debug` 改 0 |
| 49 | **批量插入脚本规范** | ① 主表 + `_data`（content）+ `_views`（排序）**三表同步**插入，缺 views 则排行区块空；② **必须校验 `lastInsertId()`** 成功才插关联表，失败 `continue`——失败后继续插会让 views 表留下 `id=0` 垃圾行（→ 触发 #47）；③ bash 内联 `php -r "..."` 写长 SQL/数组极易引号错（HY093 参数不匹配、parse error）→ 一律用 Write 工具写临时脚本文件执行后删除 |
| 50 | **数据缺口一次性盘点** | 详情页"相关游戏/相关软件"区块取**当前分类**数据，分类仅 1 条时排除自身后为空（区块空白）。修完模板后**一次性盘点**所有详情页所属分类的数据量（同分类 + 兄弟分类），识别缺口统一补齐测试数据——不要挤牙膏式逐个页面补。盘点 SQL：`SELECT cid, COUNT(*) FROM le_cms_game GROUP BY cid` + JOIN le_category 查兄弟分类 |

### 调试与验证核心（6 条，新实战补）

| # | 要点 | 说明 |
|---|------|------|
| 51 | **修复后验证必须清 runcache** | 改模板后不 `rm -rf runcache/lecms_view/*.php` 就渲染 → 渲染的还是**旧编译缓存** → 误判"修复无效"（实战：hot-words 修复其实已生效，误判导致多耗一轮调试）。验证"输出为空/无效"前先清编译缓存；清了缓存仍空才进入真排查 |
| 52 | **grep -c 统计的是行数** | 同一行里 16 个 `<a>` 标签 `grep -c '<a class'` 只返回 1。精确计数用 `grep -o '<a class="c[0-9]"' \| wc -l`。区块定位用 `awk '/锚点/{f=1} f{print}'` 比 `grep -A N` 稳（-A 行数不够会漏/跨界） |
| 53 | **{php} 块调试写法** | 禁止：`var_dump('k' => v, ...)`（命名参数式写法 → Fatal: Unknown named parameter，页面直接崩）、三元+闭包嵌套长表达式（括号错乱 → syntax error）。正确：`file_put_contents(__DIR__.'/../../dbg.txt', print_r($data, true))`；插桩时**保持 if/loop 结构完整**（只插 echo/file_put_contents 行，不替换包裹行），否则修复调试残留时易破坏语法 |
| 54 | **模板插桩拿不到 → 源码层插桩** | 渲染在断点**前**中断时，模板层调试代码根本不执行。直接在 block 源码（如 block_list_top.lib.php 的 mget 之后）插 `if(defined('DBG')) file_put_contents(__DIR__.'/../../dbg.txt', ...)`，index.php 临时 `define('DBG', 1)`，dump 中间数据（keys 数组、mget 返回的键与字段数），**定位后立即还原**。本例靠 keys 里的 `"0"` 定位垃圾行 |
| 55 | **dateformat 逐区块比对** | 原站不同区块时间格式不同：focus-tabs 的 p.time 是 **m/d**（如 07/31）、详情页更新时间 **Y-m-d**、软件列表带中文前缀"更新时间：""大小："。`block_list`/`block_list_top` 默认 dateformat='Y-m-d H:i:s'（block_list.lib.php:31），需逐区块比对并传 `dateformat` 参数（如 `'dateformat' => 'm/d'`） |
| 56 | **同站不同频道可能用不同列表模板** | 原站 /ziti/（游戏）与 /zitiinfo/（手机应用）同为 game-list 三栏结构，但 /down/（电脑软件）是独立 **soft-list6 两段式**结构（item-hd：标题+[分类]+星级 / item-bd：图+info(更新时间/大小带中文前缀)+简介+查看详情按钮）。**逐频道比对列表结构**，不要假设一个列表模板通吃全站；差异大时新建独立模板（如 software_list.htm）并给对应分类 cate_tpl 赋值 |

### seajs 脚本加载顺序（1 条，新实战补）

| # | 要点 | 说明 |
|---|------|------|
| 57 | **seajs.use 必须在 base.js 之后（页面级交互分发的标准做法）** | 页面模板内联 `<script>seajs.use('common', fn.xxx())</script>` 若写在 `{inc:footer.htm}`（含 base.js）**之前** → seajs 未定义，内联脚本**静默崩溃**，页面专属交互（详情页 tabs 定位/侧栏浮动/轮播）全部失效，而 footer 的 fn.init 正常执行 → 页面"看起来正常、交互全坏"，极隐蔽。验证：渲染页 `grep -n "script/base.js\|seajs.use"`，base.js 行号必须 < 第一个 seajs.use 行号。**修复**：LeCMS `{inc:}` 是编译期文本替换（view.class.php process_inc 用 file_get_contents），页面模板在 footer **之前**定义 `{php}$page_fn='softDetail';{/php}`，inc-footer.htm 回调内 `{if:!empty($page_fn)}fn.{$page_fn}();{/if}` 统一分发（语义与原站"同一回调调 fn.init + 页面函数"一致，7 个页面模板一处入口） |

---

## 详细流程索引（按需 Read references/）

| 阶段 | 章节标题 | 文件 |
|------|---------|------|
| 〇、输入收集（三问门禁） | 三问门禁（本文件）+ 主题命名规则（§1.0） | SKILL.md 主文件 + `references/01-receive-and-analyze.md` |
| 一、接收输入，分析原网站 | 源码分析 + 在线分析 + 模板推导 + 设计文档 | `references/01-receive-and-analyze.md` |
| 二、确定模板目录结构 | info.ini + show.jpg + 命名规范 + 静态资源目录 | `references/02-create-view-directory.md` |
| 三、静态资源移植 | CSS/JS/图片路径替换 + 第三方库 CDN + 在线抓取缺失文件 | `references/03-static-resources.md` |
| 四、模板文件开发 | 开发顺序 + 步骤 + 语法要点 + 常用 block + 移动端模板要点 | `references/04-develop-templates.md` |
| 五、后台配置 | 模型创建 + 自定义字段 + 分类 + 数据表核对 + 属性表 + 导航 + 共数据双前端 | `references/05-backend-config.md` |
| 六、占位符替换 | 占位符清单与替换策略 | `references/06-placeholder-replace.md` |
| 六.五、测试数据生成 | 可选阶段，测试数据准备 | `references/06-5-test-data.md` |
| 七、功能测试 | 页面测试 + 调试方法 + 代码审查清单 | `references/07-functional-test.md` |
| 八、效果比对（必须执行） | 区块清单 + 自动化脚本 + 比对方式/清单/流程 + 记录 + 常见差异 + **8.4.5 全页面覆盖清单** + **8.4.6 动态交互层** + **8.4.7 数据层** + 验收标准 | `references/08-compare-effect.md` |
| 九、常见陷阱 | 模板语法类 + 分类/数据类 + **数据一致性与排序类** + 环境类 + 对比验证类 + 移动端/双前端类 | `references/09-pitfalls.md` |
| 十、迭代规范 | 提交规范 + 审查流程 + **Skill 自我迭代（实战沉淀机制）** | `references/10-iterate.md` |

**使用方法**：
- 主文件核心沉淀速查表是高频查阅内容，开发时遇到问题**先查这里**
- 详细流程和具体操作步骤按需 Read `references/` 对应文件
- 知识库细节（14 篇）按需 Read `knowledge/LeCSM系统完整知识库/` 对应文档