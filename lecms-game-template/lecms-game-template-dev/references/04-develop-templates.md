## 相关章节

| 类型 | 文件 |
|------|------|
| 前提 | [02-create-view-directory.md](02-create-view-directory.md) |
| 资源 | [03-static-resources.md](03-static-resources.md) |
| 后台配置 | [05-backend-config.md](05-backend-config.md) |
| 测试 | [07-functional-test.md](07-functional-test.md) |
| 常见陷阱 | [09-pitfalls.md](09-pitfalls.md) |
| 实战案例 | [examples/](../examples/)（hero-block / store-card / detail-info-grid / aside-by-alias） |

---

## 四、模板文件开发

### 4.1 开发顺序

按依赖关系从高到低：

| 顺序 | 文件 | 理由 |
|------|------|------|
| 1 | `inc-header.htm` | 所有页面引入，包含导航+搜索 |
| 2 | `inc-footer.htm` | 所有页面引入 |
| 3 | `index.htm` | 首页，验证整体样式 |
| 4 | `{model}_list.htm` | 列表页 |
| 5 | `{model}_show.htm` | 详情页（结构最复杂） |
| 6 | `{model}_index.htm` | 频道首页 |
| 7 | 其他列表/详情页 | 复用已有模式 |
| 8 | 辅助模板 | search/tag/flags/404/comment |

### 4.2 开发步骤

对每个模板文件：

1. 复制参考网站的 HTML 结构
2. 替换静态内容为 LeCMS 模板标签
3. 添加 `{inc:header.htm}` / `{inc:footer.htm}`
4. 自检标签闭合、变量引用

### 4.3 模板语法要点

```html
<!-- 变量输出 -->
{$cfg[weburl]}              <!-- 网站 URL -->
{$cfg[tpl]}                 <!-- 模板路径（末尾带 /） -->
{$cfg[webname]}             <!-- 网站名称 -->
{$cfg[beian]}               <!-- ICP 备案号 -->
{$gdata[title]}             <!-- 详情页完整标题（用于 <title>、alt 属性） -->
{$gdata[subject]}           <!-- 详情页截断标题（用于显示） -->
{$gdata[content]}           <!-- 详情页正文 HTML -->
{$gdata[pic]}               <!-- 详情页缩略图 -->
{$gdata[size]}              <!-- 自定义字段：大小 -->
{$gdata[update_time]}       <!-- 自定义字段：更新时间 -->
{$gdata[tag_arr]}           <!-- 标签数组 -->
{$gdata[cate_name]}         <!-- 分类名称 -->
{$gdata[cate_url]}          <!-- 分类 URL -->
{$gdata[id]}                <!-- 内容 ID -->
{$gdata[cid]}               <!-- 当前分类 ID -->
{$gdata[download_url_android]}  <!-- 自定义字段 -->
{$gdata[download_url_ios]}      <!-- 自定义字段 -->
{$gdata[download_url_pc]}       <!-- 自定义字段 -->
{$gdata[platform]}          <!-- 自定义字段：平台 -->
{$v[title]}                 <!-- 列表项完整标题（用于 alt/title 属性） -->
{$v[subject]}               <!-- 列表项截断标题（用于显示） -->
{$v[pic]}                   <!-- 列表项缩略图 -->
{$v[cate_name]}             <!-- 列表项分类名 -->
{$v[date]}                  <!-- 列表项日期 -->
{$v[size]}                  <!-- 列表项大小 -->
{$v[intro]}                 <!-- 列表项摘要 -->
{$v[url]}                   <!-- 列表项链接 -->
{$v[views]}                 <!-- 浏览量 -->
{$v[dateline]}              <!-- 时间戳 -->
{$v[tag_arr]}               <!-- 标签数组 -->
{$cid}                      <!-- 当前分类 ID（列表页） -->
{$mid}                      <!-- 当前模型 ID -->
{$keyword}                  <!-- 搜索关键词 -->
{$data[pages]}              <!-- 分页 HTML -->
{@date('Y-m-d', $v[dateline])}      <!-- 日期格式化 -->
{@mb_substr($v[title], 0, 20)}      <!-- 字符串截取 -->
```

> **`{$v[subject]}` vs `{$v[title]}`**：`subject` 是 LeCMS 自动截断的标题（长度由 intronum 控制），用于页面显示；`title` 是完整标题，用于 `<img alt>` 和 `<a title>` 等属性。

> **`{inc:}` 限制**：文件名不能包含连字符 `-`，只能用下划线 `_` 或字母数字。

```html
<!-- 数据查询 -->
{block:list cid="15" mid="3" limit="20" orderby="dateline" orderway="-1" showcate="1" intronum="50"}
    {loop:$data[list] $v}
        <a href="{$v[url]}">{$v[subject]}</a>
    {/loop}
{/block}

{block:list_top cid="15" orderby="views" limit="8"}        <!-- 排行榜（cid 不传则自动识别当前分类） -->
{block:list_rand limit="8"}                                  <!-- 随机（cid 无实质作用） -->
{block:list_flag flag="2" mid="3" limit="10" showcate="1"}   <!-- 属性标记（必须显式传 mid！默认 2=文章） -->
{block:category type="child"}                                <!-- 当前分类子分类（cid 不传自动识别） -->
{block:links}                                                <!-- 友情链接 -->
{block:global_cate pagenum="20" dateformat="Y-m-d"}{/block}  <!-- 列表页（自动识别分类+分页） -->
{block:global_flags pagenum="20"}{/block}                    <!-- 属性内容列表页 -->
{block:global_taglist pagenum="20"}{/block}                  <!-- 标签内容列表页 -->
{block:global_show show_prev_next="1" dateformat="Y-m-d" field_format="1"}{/block}  <!-- 详情页 -->
```

<!-- 控制结构 -->
{if:$_GET[cid] == $v[cid]} class="current"{/if}             <!-- 当前分类高亮（无 $cid 变量！） -->
{loop:$gdata[tag_arr] $tag}...{/loop}
{inc:header.htm}
```

> **`{block:xxx}` 参数是静态字符串**（addslashes + var_export 编译），**不支持 `{$var}` 变量**！需要动态值的场景：
> - 依赖 block 的自动识别（cid/mid 不传时自动从 `$_GET` 读取）
> - 用 `{php}` 代码块直接调用 block 函数（需先放空 `{block:xxx}{/block}` 触发函数收集）

> **详情页**：必须包含 `{block:global_show show_prev_next="1" dateformat="Y-m-d" field_format="1"}{/block}` 来生成 `$gdata`（内容详情数组），否则 `{$gdata[title]}` 等全部为空。`show_prev_next="1"` 生成 `$gdata[prev]` / `$gdata[next]`。

> **详情页变量**：面包屑/分类链接用 `{$cfg_var[url]}` / `{$cfg_var[name]}`（控制器赋值的分类信息，含 `place` 位置链）；`$gdata` 只有内容字段（title/date/views/intro/content/自定义字段/prev/next），**没有** cate_url/cate_name。

> **变量限制**：LeCMS 的 `{$var}` 标签**不支持管道默认值**（`{$var|'默认'}` 会原样输出），需要默认值时用 `{if:$var}{$var}{else}默认{/if}`。

> **`{@expr}` 引号**：`{@...}` 表达式标签**不会**给数组键加引号，必须手写 `{@$v['dateline']}`（`{@$v[dateline]}` 会把 dateline 当常量报错）；`{if:}` 中的数组键会自动加引号。

> **`xuhao` 字段**：`find_fetch` 返回的数组键是 `{表}-{主键}-{值}` 字符串（如 `cms_article-id-5`），`{loop $data[list] $v $k}` 的 `$k` 是这种字符串而非数字！需要序号时用 block 数据自带的 `$v[xuhao]`（1 起递增）。

> **`{php}` 调用 block**：`{php}` 代码块中可直接调用 block 函数（如 `block_list(...)`），但**函数只有模板中存在对应 `{block:xxx}` 标签时才被收集**——`{php}` 中调用某 block 前，需在模板任意位置放一个空标签 `{block:xxx}{/block}` 触发函数加载。

### 4.4 常用 block 属性

| 属性 | 说明 | 示例 |
|------|------|------|
| `cid` | 分类 ID | `cid="15"` |
| `mid` | 模型 ID | `mid="3"` |
| `limit` | 条数 | `limit="20"` |
| `orderby` | 排序字段 | `dateline` / `views` / `id` |
| `orderway` | 方向 | `-1`（降序） |
| `showcate` | 显示分类名 | `showcate="1"` |
| `intronum` | 摘要截取 | `intronum="50"` |
| `start` | 偏移量（用于分页） | `start="20"` |
| `flag` | 属性 ID | `flag="1"`（推荐） |
| `type="child"` | 子分类 | 用于 category block |

### 4.5 移动端模板要点（m.xxx.com 类站点）

**JS 加载体系（原站 footer 顺序）**：

```html
<script type="text/javascript" src="{$cfg[tpl]}script/jquery.min.js"></script>  <!-- 原站定制版：含 seajs+Vue+TouchSlide -->
<script type="text/javascript" src="{$cfg[tpl]}script/base.js"></script>
<script type="text/javascript" src="{$cfg[tpl]}script/lazyload-min.js"></script>
<script type="text/javascript">seajs.config({ base: '{$cfg[tpl]}script/' });</script>  <!-- 必须！否则 app/common 404 -->
<div class="totop" id="totop"><i class="icon icon-top"></i></div>
```

页面底部（footer 引用之后、`</div></body></html>` 之前）调用原版交互模块：

```html
<script type="text/javascript">
    seajs.use('app/common', function(fn){
        fn.init();      // 搜索面板(Vue) + 返回顶部
        fn.index();     // 首页：iFocus 焦点图 + friendLink 轮播（TouchSlide）
        fn.softDetail();// 详情页：softFocus 截图轮播 + 排行榜 + 懒加载
        fn.newsDetail();// 资讯详情
        fn.mobileGamesList(); // 排行榜 tab 切换 + btn-more 查看更多
    });
</script>
```

> **footer 不要闭合 `</div></body></html>`**——各页面自行闭合，因为每页底部有各自的 `seajs.use` 调用（参考网站即此结构）。

**面包屑（列表页，参考网站层级）**：

```html
<ul class="url-here">
    <li><a href="{$cfg[weburl]}">首页</a></li>
    {loop:$cfg_var[place] $p $k}
    {if:$k < count($cfg_var[place]) - 1}<li><a href="{$p[url]}">{$p[name]}</a></li>{/if}
    {/loop}
    <li><h1 class="dis_online">{$cfg_var[name]}</h1></li>
</ul>
```

- 详情页面包屑：游戏详情只显示**顶级分类**（`{loop:$cfg_var[place] $p $k}{if:$k == 0}`）+ 标题；资讯详情显示**直接分类**（`$cfg_var[url]/[name]` 包 h1 链接）+ 标题
- 分类下拉按钮：参考网站**始终显示"全部"**（子分类页也如此），菜单内当前项 `class="btn on"` 高亮
- 下拉展开是 `.open` 类控制（CSS：`.open .dropdown-menu { visibility: visible }`），交互 `$(this).parent().toggleClass('open')`

**共模板注意**：软件模型 cate_tpl 常共用 game_list.htm → 分类下拉用 `{block:category type="child"}` **不传 cid**（自动识别当前分类），软件页自动显示软件子分类、手游页显示手游子分类

**详情页下载按钮（静态渲染替代原站 JS 动态）**：

```html
{if:$gdata[download_url_android]}
<a class="btn btn-gs" href="{$gdata[download_url_android]}" rel="nofollow"><i class="icon-down-gs"></i>高速下载</a>
{/if}
{if:$gdata[download_url_ios]}
<a class="btn btn-pt" href="{$gdata[download_url_ios]}" rel="nofollow"><i class="icon-down-pt"></i>普通下载</a>
{/if}
{if:!$gdata[download_url_android] && !$gdata[download_url_ios]}
<span class="btn btn-none"><i class="icon-down-none"></i>暂不提供</span>
{/if}
```

**正文截图轮播**（block 参数静态化无法传变量，用 `{php}` 从正文提取图片生成 soft-focus）：

```html
{php}
if (!empty($gdata['content']) && preg_match_all('/<img[^>]+src="([^"]+)"/', $gdata['content'], $m) && !empty($m[1])) {
    echo '<div class="soft-focus" id="softFocus"><div class="bd"><ul>';
    foreach ($m[1] as $img) { echo '<li><img src="' . $img . '"></li>'; }
    echo '</ul></div><div class="hd"><ul></ul></div></div>';
}
{/php}
```

