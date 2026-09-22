# 实战案例：详情页 6 项信息网格

**来源**：macfkm Windows 商店主题复刻，参考站 [macfk.com](https://www.macfk.com) `/windows/software/adobe-photoshop-windows.html`

**相关沉淀**：核心沉淀 #6（详情页必须 global_show）+ #41（正文不能嵌页面结构）

## 参考站结构

参考站详情页"其他信息"区块（detailInfoGrid）含 6 项：

| label | value |
|-------|-------|
| 开发者 | Adobe Systems Incorporated |
| 类别 | 办公 / 媒体 / 设计 |
| 发布时间 | 2026-06-28 |
| 版本号 | 2026 27.8.0 |
| 系统要求 | Windows 10 以上 |
| 文件大小 | 6.11 GB |

每个 item 是一对 `detailInfoLabel` + `detailInfoValue`。

## LeCMS 模板实现

[view/macfkm/software_show.htm](view/macfkm/software_show.htm) 的 detailInfoGrid 区块（节选）：

```html
<section class="page-module__CBPw8q__card">
    <div class="page-module__CBPw8q__cardHeader">
        <h2 class="page-module__CBPw8q__sectionTitle">其他信息</h2>
    </div>
    <div class="page-module__CBPw8q__detailInfoGrid">
        <div class="page-module__CBPw8q__detailInfoItem">
            <div class="page-module__CBPw8q__detailInfoLabel">开发者</div>
            <div class="page-module__CBPw8q__detailInfoValue">{if:$gdata[developer]}{$gdata[developer]}{/if}</div>
        </div>
        <div class="page-module__CBPw8q__detailInfoItem">
            <div class="page-module__CBPw8q__detailInfoLabel">类别</div>
            <div class="page-module__CBPw8q__detailInfoValue">{@implode(' / ', (array)$gdata['tags'])}</div>
        </div>
        <div class="page-module__CBPw8q__detailInfoItem">
            <div class="page-module__CBPw8q__detailInfoLabel">发布时间</div>
            <div class="page-module__CBPw8q__detailInfoValue">{if:$gdata[update_time]}{$gdata[update_time]}{else}{$gdata[date]}{/if}</div>
        </div>
        <div class="page-module__CBPw8q__detailInfoItem">
            <div class="page-module__CBPw8q__detailInfoLabel">版本号</div>
            <div class="page-module__CBPw8q__detailInfoValue">{if:$gdata[version]}{$gdata[version]}{/if}</div>
        </div>
        <div class="page-module__CBPw8q__detailInfoItem">
            <div class="page-module__CBPw8q__detailInfoLabel">系统要求</div>
            <div class="page-module__CBPw8q__detailInfoValue">{if:$gdata[system_req]}{$gdata[system_req]}{else}Windows 10 以上{/if}</div>
        </div>
        <div class="page-module__CBPw8q__detailInfoItem">
            <div class="page-module__CBPw8q__detailInfoLabel">文件大小</div>
            <div class="page-module__CBPw8q__detailInfoValue">{if:$gdata[size]}{$gdata[size]}{/if}</div>
        </div>
    </div>
</section>
```

## 关键决策点

| 决策 | 方案 | 原因 |
|------|------|------|
| 6 项数据来源 | developer + tags + update_time/date + version + system_req + size | LeCMS 软件表字段（参考站 value 与本地完全一致） |
| 发布时间回退 | `update_time` 优先，无则用 `date` | update_time 是软件更新时间，date 是入库时间；优先用更新时间更准确 |
| 系统要求回退 | `system_req` 字段，无则静态"Windows 10 以上" | macfkm 是 Windows 商店主题，所有软件都是 Windows，无系统要求字段时给默认值 |
| 类别（tags）显示 | `@implode(' / ', $gdata['tags'])` | tags 是数组，用 ` / ` 分隔拼接（与参考站"办公 / 媒体 / 设计"格式一致） |
| `{if:...}...{/if}` 包裹 | 字段为空时不输出 value | 避免显示空字符串（视觉上是空项） |

## 字段映射表（LeCMS 软件表 vs 参考站）

| 参考站 value | LeCMS 字段 | 字段类型 |
|-------------|-----------|---------|
| Adobe Systems Incorporated | `developer` | varchar(100) |
| 办公 / 媒体 / 设计 | `tags`（数组，`/` 分隔） | varchar(500) |
| 2026-06-28 | `update_time` 优先，无则 `date` | varchar(255) / int(10) |
| 2026 27.8.0 | `version` | varchar(100) |
| Windows 10 以上 | `system_req`，默认 "Windows 10 以上" | varchar(100) |
| 6.11 GB | `size` | varchar(255) |

## 复刻验证清单

- [ ] detailInfoGrid 内 6 个 detailInfoItem
- [ ] 每项含 detailInfoLabel（中文标签）+ detailInfoValue（实际值）
- [ ] 时间字段优先 update_time，回退 date
- [ ] 系统要求有默认值回退
- [ ] tags 数组用 ` / ` 拼接
- [ ] 值字段缺失时用 `{if:...}...{/if}` 包裹不输出

## 常见陷阱

**核心沉淀 #41**：正文 content 不能嵌页面结构。如果导入数据时正文里包含完整的 `<section class="card">...detailInfoGrid...</section>`，模板渲染时会重复输出。**必须先清理数据库**：

```php
$pos = strpos($c, '<section class="page-module__CBPw8q__card">');
if ($pos !== false) $c = substr($c, 0, $pos);
while (preg_match('#</(?:div|section|main)>$#', $c)) {
    $c = preg_replace('#</(?:div|section|main)>$#', '', $c);
}
```