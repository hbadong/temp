## 相关章节

| 类型 | 文件 |
|------|------|
| 前提 | [01-receive-and-analyze.md](01-receive-and-analyze.md) |
| 资源移植 | [03-static-resources.md](03-static-resources.md) |
| 常见陷阱 | [09-pitfalls.md](09-pitfalls.md) |

---

## 二、确定模板目录结构

根据第一步的分析结果，确定模板目录。目录名称 = 主题名称（如 `game`）。

### 2.1 前置步骤：创建 info.ini + show.jpg（必须先做）

**只有创建了这两个文件，主题才会出现在后台「主题管理」中。**

```ini
; view/{theme_name}/info.ini
[name]
name = 主题名称
brief = 主题的功能特点和简介描述
version = 1.0.0
update = YYYY-MM-DD
author = 作者名
authorurl = https://example.com
```

> 注意：section 名是 `[name]`（不是 `[模板信息]`），键和值之间要有空格：`key = value`（不是 `key=value`）。

```bash
# show.jpg 要求
尺寸：235 × 160 px
格式：JPG 或 PNG
大小：< 500KB
内容：主题效果截图
```

### 2.2 模板文件命名规则

| 文件类型 | 命名规则 | 示例 | 必需 |
|---------|---------|------|------|
| 公共头部 | `inc-header.htm` | 所有页面引入 | ✓ |
| 公共底部 | `inc-footer.htm` | 所有页面引入 | ✓ |
| 首页 | `index.htm` | 模型默认首页 | ✓ |
| 列表页 | `{model}_list.htm` | `game_list.htm` | ✓ |
| 详情页 | `{model}_show.htm` | `game_show.htm` | ✓ |
| 频道首页 | `{model}_index.htm` | `article_index.htm` | 可选 |
| 模型页 | `article_model.htm` | 模型展示页 | 可选 |
| 单页 | `page_show.htm` | 所有静态页面共用 | ✓ |
| 搜索入口 | `so.htm` | 搜索表单 | 可选 |
| 搜索结果 | `search.htm` | 搜索结果 | ✓ |
| 标签列表 | `tag_list.htm` | 按标签筛选 | ✓ |
| 标签排行 | `tag_top.htm` | 标签热度排行 | ✓ |
| 全部标签 | `tag_all.htm` | 标签云分页 | ✓ |
| 属性内容 | `flags.htm` | 推荐/热门/头条 | ✓ |
| 404 | `404.htm` | 页面未找到 | ✓ |
| 站点地图 | `sitemap.htm` | 站点地图 | 可选 |
| 关闭站点 | `close_website.htm` | 站点关闭提示 | 可选 |
| 用户空间 | `space.htm` | 用户主页 | 可选 |
| 评论 | `comment.htm` | 评论分页 | ✓ |
| 随机内容 | `inc-rand.htm` | 友情链接/随机 | 可选 |

### 2.3 静态资源目录

开发前先在模板根目录下创建子目录：

```
view/{theme_name}/
├── style/
│   ├── css/          # 样式文件（从参考网站提取）
│   │   ├── css.css   # 主样式
│   │   ├── css1.css  # 辅助样式
│   │   └── xxx.css   # 特定页面样式
│   ├── image/        # 图标精灵图
│   └── img/          # logo、二维码、装饰图
├── script/
│   ├── jquery.min.js # jQuery 库
│   ├── index.js      # 通用交互
│   ├── xxx.js        # 特定页面交互
│   └── swiper.min.js # 轮播库（或使用 CDN）
└── user/             # 用户中心模板（可选，不需要可省略）
    ├── login.htm
    ├── register.htm
    ├── my_index.htm
    └── ...
```

### 2.4 命名限制

```html
<!-- {inc:} 标签的文件名不能包含连字符 - -->
{inc:header.htm}        <!-- ✅ 正确 -->
{inc:inc-header.htm}    <!-- ❌ 错误，会被解析为 inc-inc-header -->
```

