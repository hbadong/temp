# 实战案例：单页 aside 按 alias 分支渲染

**来源**：macfkm Windows 商店主题复刻，参考站 [macfk.com](https://www.macfk.com) `/windows/download-guide.html` 等单页

**相关沉淀**：核心沉淀 #6（详情页用 `$cfg_var[url]/[name]`）

## 场景

参考站每个静态单页（下载帮助/安装指南/会员说明/隐私政策/免责声明/联系我们）的右侧 aside 链接**各不相同**：

| 单页（alias） | aside 链接 |
|-------------|-----------|
| downloadguide | 安装指南 / 搜索 / 首页 |
| installguide | 下载帮助 / 专题 / 首页 |
| membership | 下载帮助 / 安装指南 / 首页 |
| privacypolicy | 免责声明 / 联系我们 / 下载帮助 / 首页 |
| disclaimer | 隐私政策 / 联系我们 / 下载帮助 |
| contact | 隐私政策 / 免责声明 / 下载帮助 / 安装指南 |

模板 `page_show.htm` 是所有单页**共用**的，需要根据 `$cfg_var[alias]` 渲染不同的 aside 链接。

## LeCMS 模板实现

[view/macfkm/page_show.htm](view/macfkm/page_show.htm) 的 aside 分支（节选）：

```html
<aside class="windows-static-page-view-module__Pn3PFG__aside">
    <div class="windows-static-page-view-module__Pn3PFG__asideCard">
        <p class="windows-static-page-view-module__Pn3PFG__asideKicker">继续查看</p>
        <h2>你可能还会用到</h2>
        <div class="windows-static-page-view-module__Pn3PFG__relatedLinks">
            {if:$cfg_var[alias] == 'downloadguide'}
            <a class="..." href="{$cfg[weburl]}installguide/"><span>Windows 安装指南</span><span>进入</span></a>
            <a class="..." href="{$cfg[weburl]}search/mid_6/"><span>Windows 搜索</span><span>进入</span></a>
            <a class="..." href="{$cfg[weburl]}"><span>Windows 首页</span><span>进入</span></a>
            {elseif:$cfg_var[alias] == 'installguide'}
            <a class="..." href="{$cfg[weburl]}downloadguide/"><span>Windows 下载帮助</span><span>进入</span></a>
            <a class="..." href="{$cfg[weburl]}winsystem/"><span>Windows 专题</span><span>进入</span></a>
            <a class="..." href="{$cfg[weburl]}"><span>Windows 首页</span><span>进入</span></a>
            {elseif:$cfg_var[alias] == 'membership'}
            <a class="..." href="{$cfg[weburl]}downloadguide/"><span>Windows 下载帮助</span><span>进入</span></a>
            <a class="..." href="{$cfg[weburl]}installguide/"><span>Windows 安装指南</span><span>进入</span></a>
            <a class="..." href="{$cfg[weburl]}"><span>Windows 首页</span><span>进入</span></a>
            {elseif:$cfg_var[alias] == 'privacypolicy'}
            <a class="..." href="{$cfg[weburl]}disclaimer/"><span>Windows 免责声明</span><span>进入</span></a>
            <a class="..." href="{$cfg[weburl]}contact/"><span>联系我们</span><span>进入</span></a>
            <a class="..." href="{$cfg[weburl]}downloadguide/"><span>Windows 下载帮助</span><span>进入</span></a>
            <a class="..." href="{$cfg[weburl]}"><span>Windows 首页</span><span>进入</span></a>
            {elseif:$cfg_var[alias] == 'disclaimer'}
            <a class="..." href="{$cfg[weburl]}privacypolicy/"><span>Windows 隐私政策</span><span>进入</span></a>
            <a class="..." href="{$cfg[weburl]}contact/"><span>联系我们</span><span>进入</span></a>
            <a class="..." href="{$cfg[weburl]}downloadguide/"><span>Windows 下载帮助</span><span>进入</span></a>
            {elseif:$cfg_var[alias] == 'contact'}
            <a class="..." href="{$cfg[weburl]}privacypolicy/"><span>Windows 隐私政策</span><span>进入</span></a>
            <a class="..." href="{$cfg[weburl]}disclaimer/"><span>Windows 免责声明</span><span>进入</span></a>
            <a class="..." href="{$cfg[weburl]}downloadguide/"><span>Windows 下载帮助</span><span>进入</span></a>
            <a class="..." href="{$cfg[weburl]}installguide/"><span>Windows 安装指南</span><span>进入</span></a>
            {else}
            <a class="..." href="{$cfg[weburl]}downloadguide/"><span>Windows 下载帮助</span><span>进入</span></a>
            <a class="..." href="{$cfg[weburl]}installguide/"><span>Windows 安装指南</span><span>进入</span></a>
            <a class="..." href="{$cfg[weburl]}privacypolicy/"><span>Windows 隐私政策</span><span>进入</span></a>
            <a class="..." href="{$cfg[weburl]}disclaimer/"><span>Windows 免责声明</span><span>进入</span></a>
            {/if}
        </div>
    </div>
</aside>
```

## 关键决策点

| 决策 | 方案 | 原因 |
|------|------|------|
| 分支依据 | `$cfg_var[alias]` | cate_control 通过 `$this->category->get_cache($cid)` + `format()` 给 `_var` 赋值，保留分类所有原始字段（alias 是 le_category 表字段） |
| `{if:}` 中的数组键 | `$cfg_var[alias]` 直接用（不加引号） | 核心沉淀 #3：`{if:}` 表达式自动给数组键加引号，手写反而报错 |
| `{else}` 分支 | 默认显示 4 个固定链接（下载帮助/安装指南/隐私政策/免责声明） | 对未配置的特殊单页（如 addsoft/about/about 等本地扩展单页），用通用兜底 |
| 链接文本格式 | `<span>标题</span><span>进入</span>` | 参考站统一格式：`[标题][进入 →]` 双 span 结构 |
| href 形式 | `{$cfg[weburl]}xxx/`（伪静态形式） | 参考站伪静态 URL；用 `{$cfg[weburl]}` 变量避免域名写死 |
| 安装指南的"专题" | 指向 `{$cfg[weburl]}winsystem/` | 本地无独立专题页（参考站的 topic.html），用 winsystem 系统工具分类作替代 |

## 复刻验证清单

- [ ] `{if:$cfg_var[alias] == 'xxx'}` 分支覆盖参考站所有单页 alias
- [ ] `{else}` 兜底分支存在（本地扩展单页兜底）
- [ ] 每个链接是双 span 结构（`<span>标题</span><span>进入</span>`）
- [ ] href 用 `{$cfg[weburl]}xxx/` 伪静态形式
- [ ] 渲染每个单页验证 aside 内容与参考站一致

## 测试方法

```bash
# 渲染各单页验证 aside 内容
php -S 127.0.0.1:8099 tmp_router.php &
for p in downloadguide installguide membership privacypolicy disclaimer contact; do
    curl -s "http://127.0.0.1:8099/$p/" | grep -o 'relatedLink[^>]*href="[^"]*"[^>]*><span>[^<]*'
done
```

每页 aside 链接数与参考站对照（downloadguide 3 条、privacypolicy 4 条等）。

## 扩展模式

如果新主题有更多单页类型，可在 `{else}` 之前加更多 `{elseif:$cfg_var[alias] == 'xxx'}` 分支。**注意顺序**：

- 优先匹配最具体的 alias
- `{else}` 放最后做兜底

```
{if:alias == 'most-specific'} ...
{elseif:alias == 'specific'} ...
{elseif:alias == 'common'} ...
{else} 默认 4 链接
{/if}
```