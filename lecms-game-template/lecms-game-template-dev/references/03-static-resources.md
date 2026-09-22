## 相关章节

| 类型 | 文件 |
|------|------|
| 前提 | [02-create-view-directory.md](02-create-view-directory.md) |
| 后续开发 | [04-develop-templates.md](04-develop-templates.md) |

---

## 三、静态资源移植

### 3.1 提取资源

从参考网站提取：
- CSS 文件（右键查看源代码 → 找到 `<link rel="stylesheet">`）
- JS 文件（`<script src="...">`）
- 图片（logo、图标、背景图等）
- 字体文件（如有）

### 3.2 路径替换

提取后批量替换路径：

```css
/* 原参考网站路径 */
background: url(../upload/game/xxx.png);
background: url(../view/yxb/static/image/xxx.png);

/* 替换为 LeCMS 变量 */
background: url({$cfg[webdir]}upload/game/xxx.png);
background: url({$cfg[tpl]}style/image/xxx.png);
```

| 原路径模式 | 替换为 |
|-----------|--------|
| `../upload/game/` | `{$cfg[webdir]}upload/game/` |
| `../view/{theme}/static/` | `{$cfg[tpl]}` |
| `http://ref-site.com/` | `{$cfg[weburl]}` |
| `static/` | `{$cfg[tpl]}style/` |

### 3.3 第三方库

优先使用 CDN（避免遗漏文件）：

```html
<!-- Swiper -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
```

### 3.4 从源网站在线抓取缺失文件（本地源码不完整时）

小飞兔下载类工具保存的源码可能缺交互 JS（seajs / common.js / Vue / Zepto 等模块化文件）：

```bash
UA="Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.0 Mobile/15E148 Safari/604.1"
# 1. 抓取在线页面 HTML，列出实际加载的 JS/CSS 完整清单
curl -s -A "$UA" "https://m.example.com/" | grep -o '<script[^>]*src="[^"]*"'
# 2. 逐个下载缺失文件（URL 上的 ?t= 版本参数可保留或去掉）
curl -s -A "$UA" -o script/jquery.min.js "https://m.example.com/statics/js/jquery.min.js?t=20181122"
# 3. AMD 模块（seajs.use('app/common')）按 seajs base 推算实际路径，如 /statics/js/app/common.js
curl -s -A "$UA" -o script/app/common.js "https://m.example.com/statics/js/app/common.js"
```

**要点（实战验证）：**
- **原站 jquery.min.js 可能是定制版**（内嵌 seajs + Vue + TouchSlide）——必须抓原文件，勿用官网 jQuery 替换，否则 `seajs.use`/`Vue`/`TouchSlide` 全部 undefined
- 页面 `seajs.use('app/common')` 的模块名 ≠ 实际 URL：seajs 解析为 `base + 模块名.js`，base 默认是页面目录；结合页面其他资源路径（/statics/js/...）推断真实位置
- 已 404 的资源：自建空占位模块防报错（见核心沉淀第 31 条）
- 防盗链图片（403）：用 GD + Windows 中文字体（`C:/Windows/Fonts/msyhbd.ttc`）生成 logo 等占位图
- 对比在线与本地同名文件确认差异（`diff <(curl ...) local.js` 或下载后 diff）：本地文件可能是旧版

