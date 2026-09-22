# 实战案例：列表项 storeCard 区块

**来源**：macfkm Windows 商店主题复刻，参考站 [macfk.com](https://www.macfk.com) `/windows/search-win-office.html`

**相关沉淀**：核心沉淀 #7（列表页用 global_cate）

## 参考站结构

参考站列表项 storeCard 网格：

```
resultsGrid
└── storeCard（每项一个）
    ├── storeCardCover
    │   └── storeCardImage（图标）
    └── storeCardMeta
        ├── storeCardIcon（小图标）
        ├── storeCardCopy
        │   ├── storeCardTitle
        │   └── storeCardSubtitle
        └── storeCardAction（"获取"按钮）
```

## LeCMS 模板实现

[view/macfkm/search.htm](view/macfkm/search.htm) 的 storeCard 区块（节选）：

```html
<div class="page-module__CBPw8q__resultsGrid">
    {block:global_search pagenum="20" dateformat="Y-m-d" field_format="1"}
    {loop:$gdata[list] $v}
    <a class="page-module__CBPw8q__storeCard" href="{$v[url]}">
        <div class="page-module__CBPw8q__storeCardCover">
            <img class="page-module__CBPw8q__storeCardImage" draggable="false" src="{$v[pic]}"/>
        </div>
        <div class="page-module__CBPw8q__storeCardMeta">
            <img class="page-module__CBPw8q__storeCardIcon" draggable="false" src="{$v[pic]}"/>
            <div class="page-module__CBPw8q__storeCardCopy">
                <div class="page-module__CBPw8q__storeCardTitle">{$v[subject]}</div>
                <div class="page-module__CBPw8q__storeCardSubtitle">{@implode(' / ', (array)$v['tags'])} · {@$v['developer']}</div>
            </div>
            <span class="page-module__CBPw8q__storeCardAction">获取</span>
        </div>
    </a>
    {/loop}
    {/block}
</div>
```

[view/macfkm/software_list.htm](view/macfkm/software_list.htm) 复用同结构（chips 区不同）：

```html
<div class="page-module__CBPw8q__resultsGrid">
    {block:global_cate pagenum="20" dateformat="Y-m-d" field_format="1"}
    {loop:$gdata[list] $v}
    <!-- 同 storeCard 结构 -->
    {/loop}
    {/block}
</div>
```

## 关键决策点

| 决策 | 方案 | 原因 |
|------|------|------|
| 列表项数据来源 | 列表页 `{block:global_cate pagenum="20"}` / 搜索页 `{block:global_search pagenum="20"}` | 核心沉淀 #7：自动识别当前分类+分页 |
| `storeCardImage` 与 `storeCardIcon` 同 src | 两者都用 `{$v[pic]}` | 参考站详情页同一图标既做大封面图也做小图标（`storeCardCover` + `storeCardIcon`） |
| `storeCardSubtitle` 拼接 | `tags / developer` 用 ` · ` 分隔 | 参考站显示 "办公 / 媒体 / 设计 · Adobe Systems Incorporated" |
| `storeCardAction` 文字 | 静态"获取" | 参考站统一"获取"，无字段绑定 |
| `draggable="false"` | img 加 `draggable="false"` | 参考站规范，防止图片被拖拽触发下载 |
| 整卡片 `<a>` 包裹 | 整个 storeCard 是 `<a>` 标签 | 参考站整卡片可点击跳转详情页 |

## 复刻验证清单

- [ ] storeCard 用 `<a>` 包裹（整卡片可点击）
- [ ] storeCardImage 在 storeCardCover 内
- [ ] storeCardIcon 与 storeCardCover image 同 src
- [ ] storeCardSubtitle 是 tags / developer
- [ ] storeCardAction 静态"获取"
- [ ] 全卡片有 href 指向 `{$v[url]}`（`global_cate`/`global_search` 自动生成）

## 复用到其他主题

storeCard 是列表/搜索页通用结构，可直接复制到任何主题的列表/搜索模板。只需修改：

- `storeCardTitle` 用 `{$v[subject]}`（截断标题）
- `storeCardSubtitle` 根据主题字段拼接
- `storeCardAction` 文字按主题调整（如"下载"/"阅读"/"观看"）

参考站 CSS classes 命名风格统一（`x__storeCard` / `x__storeCardImage` / `x__storeCardTitle`），跨主题复用只需保留尾部命名空间后的部分。