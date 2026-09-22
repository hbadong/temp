## 相关章节

| 类型 | 文件 |
|------|------|
| 前提 | [04-develop-templates.md](04-develop-templates.md)（模板已开发完成）|
| 测试 | [07-functional-test.md](07-functional-test.md)（功能测试通过后才比对）|
| 常见陷阱 | [09-pitfalls.md](09-pitfalls.md) |
| 实战案例 | [examples/](../examples/)（hero-block / store-card / detail-info-grid / aside-by-alias）|
| 对比脚本 | [scripts/compare-with-ref.php](../scripts/compare-with-ref.php)（核心沉淀 #14-#16）|
| 模板检查 | [scripts/check-templates.php](../scripts/check-templates.php)（核心沉淀 #14-#18）|

---

## 八、效果比对（必须执行）

功能测试通过后，**逐页对照原网站**进行视觉效果比对，确保前端展示效果与原网址完全一致。这是复刻项目的最终验收标准。

### 8.1 区块级比对清单（实战验证）

逐页对比时，用脚本提取双方页面区块结构，逐项核对：

```php
// 1. 提取双方 section/div 区块
preg_match_all('/<section class="([^"]+)"/', $ours, $mo);
preg_match_all('/<section class="([^"]+)"/', $ref, $mr);
// 对比区块清单是否一致

// 2. 统计各区块内容量
foreach ($sections as $s) {
    if(preg_match('/<section class="' . $s . '">(.*?)<\/section>/s', $html, $m)) {
        echo $s . ': ' . substr_count($m[1], '<li>') . ' li, ' . substr_count($m[1], '<img') . ' img';
    }
}

// 3. 提取关键文案/链接对比
preg_match_all('/<em>([^<]+)<\/em>/', $html, $t);   // 区块标题
preg_match('/<div class="crumb">(.*?)<\/div>/s', $html, $m);  // 面包屑
```

**游戏下载门户典型区块清单（youxilibao 类网站）：**

| 页面 | 必须一致的区块 |
|------|--------------|
| 首页 | index_one~six（热门游戏tab/幻灯片/分类tabOne/tabTwo/专题轮播/资讯4栏/三大排行榜）+ friendLink |
| 列表页 | gameTags（分类标签+`<i>+</i>`分隔+更多其它分类）+ softWareList（pic/标题/大小/时间/简介/查看详情）+ pagecode |
| 游戏详情 | ga_intro(含3下载按钮) + feBaBtn(投诉反馈) + editSay(点评+截图) + ga_content + bhVersion + ga_hot(热门软件) + ga_same + ga_strategy×2(游戏攻略/软件教程) + straga_same + 右栏(分类/同类/厂商其它) |
| 软件详情 | 同游戏详情，差异：迅雷网盘/PC安装按钮、热门游戏、软件介绍 |
| 文章详情 | stradetail_left(tit/cont/next_pre/相关新闻/同类推荐) + stradetail_right(新品榜/热门推荐) |
| 单页 | nav左侧导航(active高亮) + danye-cont(h1带英文小字+content) |
| 标签/搜索 | softWareList 风格 |

**交互效果比对（参考网站原生 JS 驱动）：**
- 原生 slider 轮播（`ul + div.dot`，index.js 驱动）——**不要换 swiper**！
- tab 切换（`$(this).index()` 定位，不依赖 data-index 属性值）
- tfBox 排行榜 hover 展开（tBox/fBox 结构）
- 分类标签横向滚动（preNextBox + dotTabBox）
- 截图灯箱（screenshot.js，需引入 screenshot.css）

> **注意**：浮动侧边栏（thgotop/返回顶部/关灯）**参考网站可能没有**——实战验证 youxilibao 的 footer 后直接是 JS 引用，无浮动侧边栏。**必须先在原站源码确认**再决定是否保留，不要默认加上。

**手机端游戏下载门户典型区块（m.955yx.com 类，实战验证）：**

| 页面 | 必须一致的区块 |
|------|--------------|
| 首页 | index-focus（焦点图轮播，TouchSlide 驱动，`.bd`+`.hd` 结构）+ 火爆游戏(8图标宫格)+必备游戏(8)+游戏资讯(6图文)+最新手游(8列表项)+精选分类(13个分类链接)+friend-link（友情链接上下轮播）+ totop（返回顶部） |
| 手游列表 | url-here 面包屑（首页>顶级>子分类）+ list-head（dropdown 分类下拉 + btn-group 最热/最新）+ soft-list2 list-item（图+标题+星级 star star{N}+分类/大小+查看按钮）+ soft-list-loader |
| 游戏详情 | soft-info（图标+版本/大小/类别/时间 两列+下载按钮 btn-gs高速/btn-pt普通/btn-none暂不提供）+ soft-remark（soft-focus 截图轮播+text-inner 正文+textShow/textHide+game_bt_rem 特别说明*）+ 精品推荐(5)+相关下载(8,btn-link)+相关文章(5,tit+date)+热门搜索(16热词)+mobileGamesList（3tab：最新/最热/评分最高，各12项前4显示+btn-more） |
| 资讯列表 | list-tabs 分类 tab（圆角按钮组）+ article-list（图+标题+时间） |
| 资讯详情 | news-detail（标题+时间/来源/作者+正文）+ 精品推荐+相关下载+热门搜索+手游排行榜 |
| 搜索 | list-tabs 模型切换（游戏/资讯/软件）+ list-item 结果 + 分页 |
| 单页 | 分类名标题 + 正文 |

\* game_bt_rem（特别说明）是**运营内容非固定结构**（仅 18/314 篇有），无对应字段时可不复刻。

**移动端交互结构要求（原版 common.js 驱动，HTML 必须匹配选择器）：**
- 焦点图：`#iFocus` + `.bd`(ul>li) + `.hd`(ul)（TouchSlide autoPage 自动生成圆点）
- 搜索面板：`#taptapSearch`（Vue `el` 绑定）+ `#searchTap` 触发 + `#searchBack/#searchCancel/#searchClear`
- 排行榜：`#mobileGamesList` + `.tab-cell li` ↔ `.tab-content`（index 配对）+ `.btn-more` 每批展开 4 个 `.hide` 项，全展开后隐藏 `.section-ft`
- 截图轮播：`#softFocus` + `.bd ul li` + `.hd ul`（TouchSlide）
- 友链轮播：`#friendLink .section-bd .list` + `.section-bd li`（JS 重构按行分组 ul 后 TouchSlide topLoop）
- 返回顶部：`#totop` + `.fadein`（scroll>500 显示，点击回顶）
- 展开收起：`.soft-remark.show` 类（text-inner height:auto + textHide 显示）；textShow/textHide 按钮 CSS 默认隐藏、common.js 无逻辑——**原站此功能本就失效，保持原样即可**

### 8.2 自动化深度检查脚本（实战验证）

不依赖服务器（curl 可能被环境干扰），直接对比**模板文件**与**参考网站源码**：

**1. 模板文件完整性：**
```php
\$required = ['index.htm','game_list.htm','game_show.htm','software_show.htm',
    'article_index.htm','article_list.htm','article_show.htm','page_show.htm',
    'search.htm','tag_list.htm','tag_top.htm','tag_all.htm','flags.htm',
    '404.htm','comment.htm','inc-header.htm','inc-footer.htm','info.ini','show.jpg'];
foreach(\$required as \$f) echo (file_exists(\$ours . \$f) ? '✓' : '❌') . ' ' . \$f . PHP_EOL;
```

**2. 标签平衡检查（block/loop/if/section 开闭数量必须相等）：**
```php
foreach(['block','loop','if'] as \$tag) {
    \$o = preg_match_all('/\{' . \$tag . ':[^}]*\}/', \$s);
    \$c = preg_match_all('/\{\/' . \$tag . '\}/', \$s);
    if(\$o != \$c) \$issues[] = \$tag . '(' . \$o . '/' . \$c . ')';
}
// {inc:xxx.htm} 映射文件 inc-xxx.htm 必须存在
```

**3. 区块内部元素深度对比（逐元素检查，比 section 级更细）：**
```php
// 提取双方 section 清单比对（区块级）
preg_match_all('/<section class="([^"]+)"/', \$ours, \$mo);
preg_match_all('/<section class="([^"]+)"/', \$ref, \$mr);
// 差异：array_diff(\$mr[1], \$mo[1]) 缺失 / array_diff(\$mo[1], \$mr[1]) 多余

// 区块内部关键元素逐一对比（元素级）
\$checks = [
    ['hotGame', 'tab_menu', '热门游戏 Tab 菜单'],
    ['indexRecL', '<div class="dot">', '轮播圆点'],
    ['ga_intro', '安卓下载', '安卓按钮'],
    ['ga_intro', 'downbtn', '下载JS钩子'],
    ['editSay', 'showImg', '截图区'],
    ['ga_content', 'contHidden', '内容收起'],
    ['ga_strategy', 'circleList', '攻略列表'],
    ['popRank', 'tfBox', '排行榜'],
    ['popRank', 'star3', '星级图标'],
];
foreach(\$checks as \$c) {
    \$o = strpos(\$ours, \$c[1]) !== false;
    \$r = strpos(\$ref, \$c[1]) !== false;
    echo (\$o == \$r ? '✓' : '⚠️') . ' ' . \$c[2] . PHP_EOL;
}
```

**4. 导航配置对比：**
```php
// 后台 le_navigate_link 表顺序 vs 原站 header 导航顺序
// 原站导航可能是 9 项（含外链广告"游戏盒子"→ yun.6686.cn 推广链接）
// 外链广告属于运营内容，复刻时可不加
```

**5. 文案/按钮文字对比：**
```php
// 软件详情差异点：迅雷网盘/PC安装（vs 游戏详情 安卓下载/App store下载）
// 单页：<h1>{标题}<i>about</i></h1>（英文小字）
// 文章详情：文章来源/上一篇/下一篇/相关新闻/同类推荐
```

### 8.3 比对方式

| 方式 | 适用场景 | 操作 |
|------|---------|------|
| **并列窗口** | 逐区块比对 | 浏览器分屏，左侧原网站，右侧本地模板 |
| **截图对比** | 整体布局比对 | 两个网站分别截图，用图片对比工具叠加 |
| **元素检查** | 细节精度比对 | 浏览器 DevTools 检查元素的 CSS（间距/字体/颜色） |

### 8.4 比对清单

逐页检查以下维度：

#### 布局结构

- [ ] 页面整体宽度是否一致（如 1200px 居中）
- [ ] 区块划分是否一致（头部/内容区/侧边栏/底部）
- [ ] 列表页的分类标签栏位置、样式、数量是否一致
- [ ] 详情页的左-右两栏比例是否一致
- [ ] 各区块的内边距、外边距是否一致

#### 内容展示

- [ ] 列表项图片大小、圆角、间距是否一致
- [ ] 列表项标题字号、颜色、行高是否一致
- [ ] 列表项摘要截取长度是否一致
- [ ] 详情页游戏/软件介绍区（图+标题+标签+下载按钮）布局是否一致
- [ ] 详情页截图区域是否一致
- [ ] 详情页推荐区块的网格排列是否一致
- [ ] 侧边栏模块的标题样式、列表样式是否一致

#### 交互效果

- [ ] 幻灯片是否自动播放、切换效果是否一致
- [ ] Tab 切换是否正常（无空白/错位）
- [ ] 分类标签横向滚动是否正常
- [ ] 展开/收起功能是否正常
- [ ] 返回顶部、关灯/开灯是否正常
- [ ] 悬停效果（颜色变化、下划线等）是否一致

#### 响应式

- [ ] 常见分辨率下是否正常（1366x768、1920x1080）
- [ ] 移动端（如 375px 宽）是否适配

#### 8.4.5 全页面覆盖清单（必须执行）

用户原始诉求："深入全面详细检查和测试...包括各个模板页面，首页、列表页、内容页、标签页、频道页、搜索页以及其他页面等等"。**对照原网站，逐个模板页面做完整比对**：

| # | 模板页面 | 必查项 | 实操 |
|---|---------|-------|------|
| 1 | **首页** index.htm | 顶部导航项数与参考站一致、hero 区块（卡数/布局/轮播）、各模块顺序与标题、专题卡内容、"查看更多"链接、editSay/编辑精选 | `grep -c class` 计数 + curl 渲染统计 + grep 区块标题 |
| 2 | **频道页**（顶级分类首页）| 与参考站首页结构对比；频道入口与 hero 是否复用 index.htm | curl 渲染 + class 清单对比 |
| 3 | **列表页** software_list.htm | chips 热门搜索、resultsSection 标题、storeCard 网格项数、resultsPagination 样式、分页 | curl 5 分类 + storeCard 计数 |
| 4 | **详情页** software_show.htm | hero（4-5 个 chip）、主区（screenshotGallery / richText / detailInfoGrid 三 card）、sidebar（discoverList）、浮动条 compactActionCard | curl 24 详情页 + cardHeader/detailInfoGrid 计数 + JS 行为 |
| 5 | **搜索页** search.htm | hero（关键词/结果数）、chips、resultsGrid 项数、pagination | curl 测试关键词搜索 |
| 6 | **单页** page_show.htm | hero（面包屑/eyebrow/title/summary）、正文、**aside 按 alias 分支渲染**（每个单页 aside 链接不同）| curl 各单页对比 aside 内容 |
| 7 | **标签页** tag_list / tag_top / tag_all.htm | 标签列表/分页/标签云 | curl 标签页 |
| 8 | **属性页** flags.htm | 属性项列表/分页 | curl 属性页 |
| 9 | **404 页** 404.htm | 提示文案、返回首页链接 | curl 不存在路径 → 应返回 404 |
| 10 | **inc-header.htm** | 导航项数（与参考站一致）、搜索表单 action、supportDock、brand 结构 | grep 计数导航 + topbarEnd |
| 11 | **inc-footer.htm** | 版权行、链接数（与参考站一致）| grep 计数 + 闭合标签验证 |
| 12 | **辅助页** comment / so / flags | 入口可达性 + 页面闭合（防重复 `</body>`）| curl + `grep -c "</body>"` |

**每个页面比对完成后记录状态**：✅ 通过 / 🔧 待修 / ⚠️ 数据差异（合理替代）。

#### 8.4.6 动态交互层比对（JS 行为）

JS 交互最容易隐藏失效（语法错误让整个脚本挂掉，所有交互静默失效）：

| 交互 | 检查方法 | 典型陷阱（实战案例） |
|------|---------|-------------------|
| **滚动浮动条** | JS 监听 scrollY > 阈值 → 切换 visible 类；对齐 anchor | compactActionCard（scrollY>360），需 scroll+resize+ResizeObserver 三件套 |
| **移动菜单** | JS toggle 类；CSS 配合 display | toggle 类名必须是参考站用的（如 `topnavOpen`），**不能是 is-open 等想当然名字**——先 grep 参考站 CSS 确认展开类 |
| **hero 轮播** | dots 数量决定是否轮播；setInterval 周期 | 参考站只有 1 dot 时本地不轮播（dots.length>1 才启动）|
| **排行翻页** | prev/next 按钮 click 改 translateX | JS 双加载会导致翻两页（一次 click 触发两次）|
| **下载按钮** | 直跳 vs 弹对话框 | 务实替代可接受（与 hero shareButton 行为一致）|
| **JS 加载次数** | `grep -c "script/index.js"` 应为 1 | header+footer 都引用 = 双加载（toggle 抵消）|
| **JS 语法** | `node --check script/index.js` | IIFE 内 `continue` 是 SyntaxError，整个脚本挂掉 |
| **seajs.use 顺序** | 渲染页 `grep -n "script/base.js\|seajs.use"`：base.js 行号必须 < 第一个 seajs.use 行号 | 页面内联 `seajs.use(...)` 写在 `{inc:footer.htm}` 之前 → `seajs is not defined` 静默崩溃，页面专属交互（详情页 tabs/侧栏浮动/轮播）全失效而 init 正常 → "看着正常交互全坏"。修复：页面模板 footer 前定义 `{php}$page_fn='xxx';{/php}` + inc-footer 回调 `{if:!empty($page_fn)}fn.{$page_fn}();{/if}` 统一分发（SKILL.md #57）|

#### 8.4.7 数据层比对（隐藏的差异，模板层看不到）

页面渲染正常不等于数据一致——以下差异必须在数据库层发现：

| 数据项 | 检查方法 | 典型陷阱 |
|--------|---------|---------|
| **正文 content** | 抓渲染 HTML 看是否含参考站嵌入 HTML | 正文末尾嵌入 `<section class="card">...其他信息...</section>` → card 区块重复显示 |
| **正文 img** | 抓渲染 HTML 看 richText 是否含 `<img>` | 与 screenshotGallery 截图画廊重复显示 |
| **heroSummary** | 查数据库字段；与参考站对比 | 参考站每款有独立简介，但**数据本身可能有错**（Photoshop 误用 Illustrator 简介"贝塞尔曲线"——3 款软件错用同一段）|
| **分类字段** | `le_category` 表字段值 | alias 缺失或值不对导致 404 |
| **正文结构** | 清理参考站嵌入 HTML 后保留纯 `<p>...</p>` 段落 | 用 PHP 脚本截断到 `<section class="card">` 前 + 递归清理尾部 `</div></section>` |
| **渲染完整性** | `grep -c '<!DOCTYPE' 渲染文件` 必须为 1 | >1 = 页面渲染中途被错误页截断（debug=2 下 notice 也中断）；HTTP 200 不代表页面完整。中断点按 views/排序定位脏数据行 |
| **views↔主表一致性** | 两表 id 逐一比对（`SELECT id FROM views WHERE id NOT IN (SELECT id FROM 主表)` 应为空） | views 表垃圾行（如 id=0）→ 排行区块渲染中断，报错"缺字段"实为整行缺失 |
| **分类数据充足性** | 详情页所属分类的数据量 ≥ 2（排除自身后区块非空） | 分类仅 1 条时"相关游戏/软件"区块空白；一次性盘点统一补齐（见 06-5 缺口盘点） |
| **时间格式** | 渲染 HTML 中各区块时间格式与原站逐一比对（m/d vs Y-m-d vs 中文前缀） | block 默认 'Y-m-d H:i:s'，需逐区块传 dateformat 参数 |

> **核心原则**：静态比对（看模板源码）≠ 运行时正确。**必须 curl 渲染每个页面 + `node --check` 校验 JS + 数据库字段对比**三件套完成后才能判定"已对齐"。

### 8.5 比对流程

```
1. 打开原网站页面 A（如 /game/）
2. 打开本地模板页面 B（如 /game/）
3. 并列对比，按上方清单逐项检查
4. 发现差异 → 记录 → 修复 → 重新比对
5. 确认一致 → 标记通过 → 进入下一页
```

### 8.6 比对记录

使用以下格式记录比对结果：

| 页面 | 原网站截图 | 本地截图 | 差异 | 状态 |
|------|-----------|---------|------|------|
| 首页 `/` | ✅ | ✅ | 无 | ✅ 通过 |
| 游戏列表 `/game/` | ✅ | ⚠️ | 分类标签栏间距偏大 | 🔧 待修 |
| 游戏详情 `/youxi/2335.html` | ✅ | ❌ | 下载按钮位置偏移 | 🔧 待修 |

### 8.7 常见差异类型及修复

| 差异类型 | 原因 | 修复方法 |
|---------|------|---------|
| 图片大小不一致 | CSS 的 width/height 值不同 | 对照 DevTools 修正 CSS |
| 间距/对齐不一致 | padding/margin 值不同 | 对照 DevTools 修正 CSS |
| 字体大小/颜色不一致 | font-size/color 值不同 | 对照 DevTools 修正 CSS |
| 区块显示/隐藏不一致 | CSS class 不同（如 `hide`） | 检查模板中 class 条件判断 |
| 交互效果失效 | JS 选择器与 HTML 不匹配 | 检查 JS 选择器是否正确 |
| 内容为空 | block 标签参数错误 | 检查 cid/mid/limit 等参数 |

### 8.8 最终验收标准

所有页面比对通过后，方可认为项目完成：

- [ ] 所有页面布局结构与原网站一致
- [ ] 所有视觉元素（颜色/字体/间距/大小）与原网站一致
- [ ] 所有交互效果正常工作
- [ ] 所有页面在常见分辨率下正常显示
- [ ] 所有页面返回正确 HTTP 状态码（200）

