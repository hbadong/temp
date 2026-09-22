## 相关章节

| 类型 | 文件 |
|------|------|
| 应用场景 | 全部流程（开发/配置/测试/比对阶段都可查阅）|
| 关联沉淀 | SKILL.md 主文件 57 条核心沉淀速查表（高频查阅入口）|
| 比对方法 | [08-compare-effect.md](08-compare-effect.md)（陷阱常在比对阶段发现）|
| 自我迭代 | [10-iterate.md](10-iterate.md)（新发现陷阱应沉淀回核心沉淀或本陷阱清单）|

---

## 九、常见陷阱（实战验证全清单）

### 模板语法类

| 陷阱 | 症状 | 修复 |
|------|------|------|
| block 参数用 `{$var}` | 传字面字符串/空数据 | block 参数静态化，动态值用自动识别或 `{php}` |
| `{loop:$data $k $v}` 取索引 | `$k` 是 `{表}-{主键}-{值}` 字符串，`$k+1` 报 non-numeric | 用 `$v[xuhao]`（block 自带 1 起序号） |
| `{@$v[dateline]}` 缺引号 | Use of undefined constant dateline | 手写 `{@$v['dateline']}` |
| `{$var\|'默认'}` 管道默认值 | 原样输出模板代码 | `{if:$var}{$var}{else}默认{/if}` |
| `{php}` 调用未收集的 block 函数 | Call to undefined function | 模板中放空标签 `{block:xxx}{/block}` 触发收集 |
| 详情页缺 `{block:global_show}` | `{$gdata[...]}` 全部为空 | 详情页必须加 global_show |
| 面包屑用 `$gdata[cate_url]` | 分类链接空 | 用 `$cfg_var[url]` / `$cfg_var[name]` |
| `{block:list_flag}` 不传 mid | 查的是文章表（mid 默认 2） | 显式传 mid（游戏3/软件6） |
| `{block:list cid="39"}` 父分类查内容 | 列表空（内容在子分类） | 用 `cids="40,41,42,43"` 或依赖 son_cids |
| Swiper 缺 CSS | 幻灯片无样式 | 引入 `swiper-bundle.min.css`；或按参考用原生 slider + index.js |
| 残留 `{/block}` | 页面显示 `{/block}` 原文 | block 配平检查（开闭数量相等） |
| `showchild` 属性 | 子分类不显示 | 改为 `type="child"` |
| Tab 数量不匹配 | 切换空白 | `<li>` 与 `.sub_box` 一一对应 |

### 分类/数据类

| 陷阱 | 症状 | 修复 |
|------|------|------|
| 叶子分类 type=1 | son_cids 空 → list/list_top/global_cate 空 | 叶子 type=0，顶级 type=1 |
| 子分类 upid=0 | 变成顶级分类，频道页空 | upid=父级 cid |
| 单页分类 mid=0 | `table_arr[0]` Undefined offset | mid=1（单页模型） |
| 分类模板带目录前缀 | 模板找不到 | 只写文件名（`game_show.htm`） |
| 分类 alias 没注册 | 新 alias URL 404 | 写入 `le_only_alias` 表 |
| 修改分类不清 le_runtime | 旧 cate_arr/son_cids 生效 | `DELETE FROM le_runtime` |
| 直接 SQL 改 le_kv cfg JSON（webname/seo_title）后前台不生效 | 站点名/SEO 标题仍是旧值 | `DELETE FROM le_runtime`（前台 cfg 优先读 runtime 缓存；后台保存会自动清，直改库不会）+ 清 runcache |
| 模板修改不清编译缓存 | 页面还是旧模板 | `rm -f runcache/lecms_view/*.php` |
| `cms_{model}_flag` 表空 | 属性区块（推荐/热门/幻灯）空 | 从主表 flags 填充属性表 |
| tag_data 主键错误 | 同标签多内容报 Duplicate entry | 联合主键 `(tagid, id)` |
| views 表缺失 | 排行榜报 Table doesn't exist | 补建 `cms_{model}_views` |
| 分类 count 为 0 | 标签显示 0 款 | SQL 导入后更新 `le_category.count` |
| 图片无 onerror | 破损图标 | 添加 `onerror="this.src='...nopic.png'"` |
| 路径写死 | 换域名后资源丢失 | 用 `{$cfg[tpl]}` / `{$cfg[weburl]}` 变量 |

### 数据一致性与排序类（新实战）

| 陷阱 | 症状 | 修复 |
|------|------|------|
| 详情页/单页调 block_list_top 不传 cid | 单页右栏"热门搜索/热门文章"全空（被 `$_GET['cid']` 劫持到单页分类，page 模型直接 return 空）| 显式传 `cid="0"`（全站）或目标 cid（block_list_top.lib.php:25-26） |
| views 表 id=0 垃圾行（插入脚本失败后 `lastInsertId()=0` 继续插关联表） | 排行区块渲染中断：`Undefined index: size`（debug=2 下错误页嵌入区块中间）| `DELETE FROM le_cms_{model}_views WHERE id=0`；脚本加 lastInsertId 校验（见 SKILL.md #47/#49） |
| mget 对主表不存在的 id 返回 NULL | 报错显示"缺某字段"，实际**整行缺失**（foreach 的 `$v['xuhao']=` 把 NULL 转成仅含 xuhao 的数组）| 查 views↔主表数据一致性，不要顺着报错字段排查 |
| 排行区块部分项渲染、中间嵌错误页 | `grep -c '<!DOCTYPE' 渲染文件` >1；HTTP 仍 200 | 定位中断项（按 views 顺序数），删除/修复对应数据行 |
| 详情页"相关游戏/相关软件"区块空 | 分类仅 1 条数据，排除自身后为 0 条 | 补同分类测试数据（主表+_data+_views 三表同步） |
| 表名想当然（如 `le_cms_category`） | SQL 报 table not found | 先 `SHOW TABLES` 核实（分类表实际是 `le_category`，配置存 `le_kv` 表 cfg JSON） |

### 环境类

| 陷阱 | 症状 | 修复 |
|------|------|------|
| 动态 URL `index.php?u=...` 404 | nginx 下动态 URL 不可用 | 开启伪静态 `lecms_parseurl=1`，用 `/game/` 风格 URL |
| 80 端口多进程监听 | 请求被错误进程拦截，状态码异常 | 重启 phpstudy Nginx 服务 |
| curl 访问返回**其他网站**内容 | 浏览器正常但 curl 异常 | 环境问题（CGI 端口/虚拟主机映射），与模板无关；改用模板文件静态对比验证 |

### 对比验证类（最新实战）

| 陷阱 | 说明 | 处理 |
|------|------|------|
| 静态 div 平衡误报 | `{if:}` 条件内的 div（如 carousel 分组 `<div><ul>`）静态不可见，静态检查报 div 不平衡 | 用 `{if}` 计数替代 div 检查（if/loop/block/section 必须平衡）；条件 div 需**运行时验证**（xuhao=1 开组、中间闭组+开组、循环后闭末组） |
| 标签用 div 而原站用 section | friendLink 原站是 `<section class="friendLink">`，我们写了 div | 逐标签对比：`<section class="...">` vs `<div class="...">` 都要一致 |
| 多加了原站没有的元素 | thgotop 浮动侧边栏（返回顶部/关灯/客服）——原站 footer 后直接是 JS，**没有**浮动侧边栏 | 必须先在原站源码确认（grep thgotop/ditop 计数为 0），原站没有就移除 |
| 导航多出的外链项 | 原站导航有"游戏盒子"→ yun.6686.cn 广告外链 | 外链广告是**运营内容**非网站结构，复刻时不加 |
| 注释内容误判 | 原站 tags 页 gameTags 是 `<!-- 注释 -->`（不显示），正则能匹配到注释里的 section | 对比时检查目标是否在注释内：`strpos($seg, '<!--')` |
| 静态检查 vs 运行时 | 静态不平衡 ≠ 运行时错误（条件 div）；静态平衡 ≠ 运行时正确 | 静态检查 + 逻辑推演（xuhao 分组）+ 页面实测三者结合 |
| 修复后不清 runcache 就验证 | 渲染的还是旧编译缓存，误判"修复无效"（实战：hot-words 修复已生效，误判多耗一轮调试） | 每次改模板后先 `rm -rf runcache/lecms_view/*.php` 再渲染；清了仍异常才真排查 |
| `grep -c` 统计行数误当条数 | 同行 16 个 `<a>` 标签 grep -c 返回 1，误判区块空 | 计数用 `grep -o '<a class="c[0-9]"' \| wc -l`；区块提取用 awk 锚点而非 grep -A N（行数不够会漏） |
| 时间格式逐区块不同 | focus-tabs 是 m/d、详情页 Y-m-d、列表带"更新时间："前缀——一个全局格式通吃必错 | block_list/list_top 逐区块传 `dateformat`（默认 'Y-m-d H:i:s'），与原站逐一比对 |
| 同站频道列表结构不同 | /ziti/（游戏）与 /zitiinfo/（手机应用）同为 game-list，/down/（电脑软件）却是 soft-list6 两段式 | 逐频道比对列表结构；差异大时新建独立模板（software_list.htm）+ 分类 cate_tpl 指向它 |

### 调试类（新实战）

| 陷阱 | 症状 | 修复 |
|------|------|------|
| {php} 内 var_dump('k'=>v) 命名参数式写法 | Fatal: Unknown named parameter，页面直接崩 | 用 `file_put_contents(__DIR__.'/../../dbg.txt', print_r($data, true))`；插桩保持 if/loop 结构完整（只插输出行，不替换包裹行），否则清调试残留时易破坏语法（对应 SKILL.md #53） |
| 渲染在断点前中断，模板插桩不执行 | {php} 调试代码拿不到数据/文件不生成 | 源码层插桩：block 源码（*.lib.php）内 `if(defined('DBG')) file_put_contents(...)` + index.php 临时 `define('DBG', 1)`，dump 中间数据（keys 数组/mget 返回）后**立即还原**（对应 SKILL.md #54） |

### 移动端/双前端类（最新实战）

| 陷阱 | 症状 | 修复 |
|------|------|------|
| 用官网 jQuery 替换原站 jquery.min.js | `seajs`/`Vue`/`TouchSlide` 全部 undefined，页面交互全失效 | 原站 jquery 是**定制版**（内嵌三大库），从源网站在线抓取原文件 |
| 缺 `seajs.config({base:...})` | 控制台 `app/common.js 404`，交互无 | 页面内联 `seajs.config({ base: '{$cfg[tpl]}script/' })`（footer 库引用之后） |
| 页面内联 `seajs.use(...)` 写在 `{inc:footer.htm}` 之前 | 页面"看着正常、交互全坏"（详情页 tabs/侧栏浮动/轮播静默失效），控制台 `seajs is not defined` | base.js（内嵌 seajs）在 footer，内联块必须在其后；LeCMS 统一 footer 下用 `{php}$page_fn='xxx';{/php}`（footer 前定义）+ footer 回调 `{if:!empty($page_fn)}fn.{$page_fn}();{/if}` 分发（见 SKILL.md #57） |
| `{loop:$arr $k $p}` 键值写反 | 面包屑链接/序号丢失或错乱（编译为 `foreach($arr as $p=>$k)`） | 格式必须 `{loop:$arr $v $k}`（值在前、键在后） |
| `{if:$k < count($cfg_var[place])-1}` 中 place 理解错 | 子分类页面包屑缺中间层级 | place 数组 = `[{顶级},{子分类}]`，末项=当前分类；用 $k 判断是否末项决定输出链接或 h1 |
| `block:list orderby="views"` | 排序静默失效（按 id 排） | views 排行必须用 `block:list_top orderby="views"`（list/global_cate 白名单仅 id/dateline/lasttime/comments） |
| 复刻原站 gamelist/newslist 等 AJAX 函数 | 页面请求 `/api/sj_game_ajax` 404 | 原站 API 不可复刻；仅复刻前端交互（dropdown `toggleClass('open')`、btn-group 面板切换），页面内联实现 |
| 图片防盗链 403（img.955yx.com） | 图片下载失败 | GD + Windows 中文字体（`C:/Windows/Fonts/msyhbd.ttc`）生成 logo 等占位图 |
| 搜索按模型分区 | 默认只搜文章（mid=2），搜游戏名无结果 | 搜索表单加 `<input type="hidden" name="mid" value="3">`；search.htm 顶部加模型切换 tabs（游戏3/资讯2/软件6） |
| PHP CLI `"$v['dateline']"` 语法错 | `T_ENCAPSED_AND_WHITESPACE` parse error | 用 `"\$v['dateline']"`（转义 $）或 heredoc 临时文件 |
| bash 双引号中 `{$v[dateline]}` | 花括号被 bash 扩展成空，str_replace 静默失效 | 复杂字符串替换一律写临时 .php 文件执行 |
| Git Bash /tmp 与 PHP /tmp 不一致 | PHP `file_get_contents('/tmp/x')` 失败（路径不同） | 跨工具共享文件用项目内路径或 Windows 绝对路径 |
| sed -i 替换含 $ 模板模式 | 文件未修改 | 用 PHP str_replace 批量改模板 |
| inc-header 开 `<section>` / inc-footer 闭 | 单文件标签平衡检查报 section(1/0) 不平衡 | 设计如此（header 开 page-content、footer 关），与参考网站一致，非错误 |
| 子分类页 dropdown 显示当前分类名 | 与原站不一致（原站恒显示"全部"） | btn-dropdown 恒输出"全部"，菜单内当前项 `class="btn on"` 高亮 |

