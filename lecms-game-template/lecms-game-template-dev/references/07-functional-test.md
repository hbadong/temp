## 相关章节

| 类型 | 文件 |
|------|------|
| 前提 | [04-develop-templates.md](04-develop-templates.md)（模板已开发完成）|
| 占位符 | [06-placeholder-replace.md](06-placeholder-replace.md) |
| 测试数据 | [06-5-test-data.md](06-5-test-data.md) |
| 后台配置 | [05-backend-config.md](05-backend-config.md) |
| 后续比对 | [08-compare-effect.md](08-compare-effect.md) |
| 模板检查工具 | [scripts/check-templates.php](../scripts/check-templates.php)（核心沉淀 #14-#18）|
| 常见陷阱 | [09-pitfalls.md](09-pitfalls.md) |

---

## 七、功能测试

### 7.1 页面测试

```bash
# 测试前必须清缓存（模板修改后编译缓存不自动失效）
rm -f runcache/lecms_view/*.php

# 分类/数据修改后还需清运行时缓存
php -r "DELETE FROM le_runtime;"

curl -s -o /dev/null -w "%{http_code} %{url_effective}\n" \
  "http://localhost/" \
  "http://localhost/game/" \
  "http://localhost/soft/" \
  "http://localhost/news/"
```

### 7.2 调试方法（实战验证）

**1. 页面错误检查**：LeCMS 错误会以 HTML 错误页形式输出到页面中
```bash
curl -s URL | grep -o "Lecms 3.0.4 错误"   # 有输出=有错误
# 提取错误详情
php -r "
\$h = file_get_contents('page.html');
if(preg_match('/消息:<\/span> <font color=\"red\">(.*?)<\/font>/s', \$h, \$m)) echo \$m[1];
if(preg_match('/位置:<\/span>(.*?)<\/li>/s', \$h, \$m2)) echo \$m2[1];
"
```

**2. `{php}` 调试输出**：模板中临时输出变量
```html
{php}echo '<!--DEBUG ' . json_encode($GLOBALS['run']->category->get_cache((int)$_GET['cid'])) . '-->';{/php}
```

**3. 编译文件检查**：`runcache/lecms_view/game,xxx.htm.php` 直接看编译后的 PHP
```bash
php -l runcache/lecms_view/game,index.htm.php   # 语法检查
grep -n "block_list" runcache/lecms_view/game,index.htm.php
```

**4. 标签配平检查**：block/loop/if 的开闭数量必须相等
```php
// {block:} 数 == {/block} 数，否则未解析标签会原样输出到页面
```

### 7.3 代码审查清单

#### P1 — 必须修复

- [ ] Swiper 同时引入了 CSS 和 JS
- [ ] `{block:category}` 用 `type="child"` 而非 `showchild="1"`
- [ ] Tab 菜单 `<li>` 数量与 `.sub_box` 数量一致
- [ ] 所有 `{block:xxx}` 正确闭合
- [ ] 分页 `{$data[pages]}` 存在

#### P2 — 配置后修复

- [ ] 所有 `cid="0"` 和中文占位符已替换
- [ ] `{$gdata[cid]}` 用于"同类推荐"等区块

#### P3 — 建议优化

- [ ] 所有 `<img>` 有 `onerror` 回退
- [ ] 所有 `<a>` 有有效 `href`（无 `href="#"`）
- [ ] 详情页图片设置 `style="width:100%"`
- [ ] 列表页无冗余字段

#### P4 — 移动端专项（m.xxx.com 类）

- [ ] seajs base 已配置（`seajs.config({base:'{$cfg[tpl]}script/'})`），`app/common` 可加载
- [ ] jquery.min.js 为原站定制版（含 seajs/Vue/TouchSlide），非官网替换版
- [ ] footer 未闭合 `</div></body></html>`（各页面自行闭合 + 各自 seajs.use）
- [ ] 面包屑用 `$cfg_var[place]` 链（loop 键值顺序正确：`$p $k`）
- [ ] 列表页 `block:category type="child"` 不传 cid（自动识别，适配多模型共模板）
- [ ] dropdown 按钮恒显示"全部"（原站行为），当前项菜单内高亮
- [ ] 搜索表单含 `mid` 参数 + 搜索结果页模型切换 tabs
- [ ] 页面 `<section class="page-content">` 由 header 开、footer 闭（单文件检查不平衡属正常）
- [ ] 移动 UA / PC UA 双主题切换验证通过

