# 实战案例：详情页 hero 区块

**来源**：macfkm Windows 商店主题复刻，参考站 [macfk.com](https://www.macfk.com) `/windows/software/adobe-photoshop-windows.html`

**相关沉淀**：核心沉淀 #45（浮动条）+ #43（heroSummary 提取）

## 参考站结构

参考站 hero 区块含 6 个子区块：

```
heroShell
└── heroInner
    └── heroContent（左侧主区）
    │   ├── titleRow（appIcon + titleCopy[appTitle/appDeveloper]）
    │   ├── providerCard（providerWindowsIcon + "仅支持 Windows 系统"）
    │   ├── heroSummary（一段简介，参考站是独立字段）
    │   ├── quickFactRow × 4 chips（分类/版本/日期/VIP价格）
    │   ├── supportList × 3（资源与安装说明已整理等）
    │   └── shareButton（"下载"按钮 → 弹下载对话框）
    └── heroArtwork（右侧装饰图）
```

## LeCMS 模板实现

[view/macfkm/software_show.htm](view/macfkm/software_show.htm) 的 hero 区块（节选）：

```html
<section class="page-module__CBPw8q__hero">
    <div class="page-module__CBPw8q__heroBackdrop"></div>
    <div class="page-module__CBPw8q__heroShell">
        <div class="page-module__CBPw8q__heroInner">
            <div class="page-module__CBPw8q__heroContent">
                <div class="page-module__CBPw8q__titleRow">
                    <img class="page-module__CBPw8q__appIcon" src="{$gdata[pic]}"/>
                    <div class="page-module__CBPw8q__titleCopy">
                        <h1 class="page-module__CBPw8q__appTitle">{$gdata[title]}</h1>
                        <p class="page-module__CBPw8q__appDeveloper">{if:$gdata[intro]}{$gdata[intro]}{/if}</p>
                    </div>
                </div>
                <div class="page-module__CBPw8q__providerCard">
                    <span class="page-module__CBPw8q__providerWindowsIcon">
                        <span></span><span></span><span></span><span></span>
                    </span>
                    <span>仅支持 Windows 系统</span>
                </div>
                <p class="page-module__CBPw8q__heroSummary">{php}
                    $desc = preg_replace('#<p[^>]*>\s*<img[^>]+>\s*</p>#i', '', (string)$gdata['content']);
                    $desc = preg_replace('#<[^>]+>#', ' ', $desc);
                    $desc = trim(preg_replace('/\s+/u', ' ', $desc));
                    $out = '';
                    foreach(preg_split('/(?<=。|！|？)/u', $desc) as $sent){
                        if(mb_strlen($out . $sent) > 90) break;
                        $out .= $sent;
                    }
                    echo $out ? $out : mb_substr($desc, 0, 90);
                {/php}</p>
                <div class="page-module__CBPw8q__quickFactRow">
                    <span class="page-module__CBPw8q__quickFactChip">{@implode(' / ', (array)$gdata['tags'])}</span>
                    <span class="page-module__CBPw8q__quickFactChip">{if:$gdata[version]}{$gdata[version]}{/if}</span>
                    <span class="page-module__CBPw8q__quickFactChip">{if:$gdata[update_time]}{$gdata[update_time]}{/if}</span>
                    <span class="page-module__CBPw8q__quickFactChip">VIP 免费 / 单买 5</span>
                </div>
                <div class="page-module__CBPw8q__supportList">
                    <div class="page-module__CBPw8q__supportItem">... × 3 项</div>
                </div>
                {if:$gdata[download_url_pc]}
                <a class="page-module__CBPw8q__shareButton" href="{$gdata[download_url_pc]}" target="_blank" rel="nofollow">下载</a>
                {else}
                <button class="page-module__CBPw8q__shareButton" type="button">下载</button>
                {/if}
            </div>
            <div class="page-module__CBPw8q__heroArtwork">...</div>
        </div>
    </div>
</section>
```

## 关键决策点

| 决策 | 方案 | 原因 |
|------|------|------|
| `appDeveloper` 用哪个字段 | `{$gdata[intro]}` | 参考站显示"专业图像设计处理软件"（软件分类短简介），用 intro 字段匹配 |
| `heroSummary` 内容来源 | `{php}` 从 content 提取首段前 1-2 句（按句号截断，最长 90 字）| 参考站该字段是独立编辑简介，参考站 24 款中 3 款数据有错（Photoshop 误用 Illustrator 简介）。本地无对应字段，从 content 提取更可靠 |
| quickFactChip 第 4 项 | 静态 "VIP 免费 / 单买 5" | 参考站 24 个详情页价格统一，静态写死即可（无需新字段） |
| `shareButton` 行为 | a 标签直跳网盘（if 有 download_url_pc），否则 button | 参考站 button 弹下载对话框含 VIP/单买选项，本地务实替代——直接跳转网盘链接（与 hero 现有 shareButton 行为一致） |
| providerCard 文本 | 静态"仅支持 Windows 系统" | macfkm 是 Windows 商店主题，全部软件都是 Windows，无须字段 |
| heroArtwork 装饰图 | 引用 `{$gdata[pic]}` | 参考站装饰图与软件图标同源（Next.js 用同一图片） |

## 复刻验证清单

- [ ] heroShell + heroInner + heroContent 三层结构
- [ ] titleRow 含 appIcon + titleCopy
- [ ] providerCard 4 个方块 + 文字
- [ ] heroSummary 一段文字（非 tags）
- [ ] quickFactChip × 4（含 VIP 价格）
- [ ] supportList × 3
- [ ] shareButton（a 标签直跳 / button 兜底）
- [ ] heroArtwork 右侧装饰图（与 appIcon 同 src）