---
kind: external_dependency
name: Layui 前端框架
slug: layui
category: external_dependency
category_hints:
    - vendor_identity
scope:
    - '**'
source_files:
    - static/layui/
    - static/layui/css/public.css
    - static/layui/layui.js
    - static/admin/admin.js
last_updated: 2026-09-06
---

# Layui 前端框架（LECMS 集成版）

## 一、版本与集成方式

LECMS 后台管理界面基于 **Layui 2.8.15**（layuimini 二开版）开发，前台部分主题辅以 **Bootstrap 3.3.7**。

| 项 | 值 | 备注 |
|---|---|---|
| Layui 主版本 | 2.8.15 | 由 `新版本说明.txt` 标注 |
| 主题 | layuimini | 提供侧边栏、多标签、菜单折叠 |
| 源码位置 | `static/layui/` | 与 PHP 同源部署，无 CDN |
| 包管理 | 无 | 源码直接复制到 `static/layui/` |
| 富文本 | UEditor | `static/js/umeditor/`，独立目录 |
| 图标字体 | Font Awesome 4.7.0 | `static/layui/lib/font-awesome-4.7.0/` |

## 二、目录结构

```
static/
├── layui/                    # Layui 框架核心
│   ├── css/
│   │   ├── layui.css          # 框架主样式
│   │   └── public.css         # ★ LECMS 自定义全局样式（覆盖框架默认）
│   ├── js/
│   │   └── layui.js           # 框架主脚本
│   ├── lib/                  # Layui 内置第三方库
│   │   ├── font-awesome-4.7.0/
│   │   ├── echarts/
│   │   ├── layui-mz-min.css
│   │   └── ...
│   ├── font/                  # Layui 图标字体
│   └── images/
├── admin/                    # 后台专用资源
│   ├── admin.js              # ★ 后台 AJAX、layer、Cookie、动态加载封装
│   ├── admin.css
│   └── jquery.dragsort*
├── js/                       # 前台通用 JS
│   ├── le.js                  # ★ 移植自 xiuno 的轻量工具库
│   ├── jquery-2.2.4.min.js
│   ├── swiper.min.js
│   ├── lazyload.min.js
│   └── umeditor/              # 富文本编辑器
└── img/
```

## 三、layuimini 主题与扩展模块

`static/layui/` 通过 `lay-config.js`（在 `layui.extend()` 调用中）注册以下扩展模块：

| 模块 | 用途 |
|---|---|
| `miniAdmin` | 整体布局、侧边栏生成 |
| `miniMenu` | 多级菜单渲染 |
| `miniTab` | 多标签页路由（基于 `miniTab.listen()`） |
| `echarts` | 图表（统计页） |
| `treetable` | 树形表格（分类、菜单） |
| `dragsort` | 行拖拽排序 |
| `tableSelect` | 表格选择器 |
| `iconPicker` | 图标选择器 |
| `layarea` | 省市区三级联动 |

> 引入方式：在后台模板 `<script>` 中先 `layui.config({ base: 'static/layui/lib/' })` 注册扩展路径，再用 `layui.use(['miniAdmin'], ...)` 加载。

## 四、`admin.js` 提供的核心 API

```js
// 统一 AJAX 调用
adminAjax.post(url, data, callback);
adminAjax.get(url, callback);
adminAjax.postd(url, data, callback);  // 带 debug

// 动态加载 CSS / JS
admin.loadCss(path);
admin.loadJs(path);

// Cookie 操作
admin.cookie.set(k, v, days);
admin.cookie.get(k);

// Layer 弹窗
admin.popup.open({ type: 2, content: url });
admin.popup.msg('提交成功');
admin.popup.confirm('确定删除？', cb);

// 文件上传（封装 layui.upload）
admin.upload.render({ elem: '#btn', url: 'attach-upload' });

// 表格行编辑辅助
admin.table.edit(field, value, callback);
```

所有后台 AJAX 接口遵循统一返回结构：

```json
{
  "code": 0,
  "msg": "ok",
  "count": 123,
  "data": [ ... ]
}
```

> 错误返回时 `code != 0`，调用 `adminAjax` 的回调会弹层报错并终止流程。

## 五、常见组件示例

### 5.1 数据表格（后台 90% 页面）

```html
<table id="list" lay-filter="list"></table>
<script>
layui.use(['table', 'adminAjax'], function() {
  var table = layui.table,
      adminAjax = layui.adminAjax;
  table.render({
    elem: '#list',
    url: 'index.php?control=xxx&action=get_list',
    cols: [[
      {type: 'checkbox'},
      {field: 'id', title: 'ID', sort: true, width: 80},
      {field: 'title', title: '标题', edit: 'text'},
      {toolbar: '#toolbar', title: '操作'}
    ]],
    page: true,
    response: { statusCode: 0 }
  });
});
</script>
```

### 5.2 表单（基于 `form.class.php` 后端渲染 + layui 渲染）

```html
<form class="layui-form">
  <div class="layui-form-item">
    <label class="layui-form-label">标题</label>
    <div class="layui-input-block">
      <input type="text" name="title" required lay-verify="required" class="layui-input">
    </div>
  </div>
  <button lay-submit lay-filter="save">提交</button>
</form>
```

### 5.3 弹层表单

```js
admin.popup.open({
  type: 2,
  title: '编辑内容',
  content: 'index.php?control=content&action=set&id=' + id,
  area: ['720px', '480px'],
  end: function() { obj.reload(); }
});
```

## 六、与 LECMS 的集成点

| 集成点 | 说明 |
|---|---|
| `static/layui/css/public.css` | 覆盖 Layui 默认外观（按钮、表格、滚动条） |
| `admin/control/*_control.class.php` | 所有列表接口都返回 `code/msg/count/data`，与 layui.table.response 对齐 |
| `static/admin/admin.js` | 后台专属扩展，依赖 `layui.use(['layer','table','form'])` |
| `static/js/le.js` | 前台工具库，`{php}` 模板引擎未启用时的 JS 替代品 |
| 富文本 | UEditor 替代 Layui 自带的 layedit，与 Layui 不冲突 |

## 七、升级注意事项

1. **不要轻易升级 Layui 主版本**：layuimini 与 2.8.x 兼容，升 2.9+ 可能破坏 miniTab、miniMenu 的内部 API
2. **`public.css` 不要直接覆盖**：建议新建 `public.local.css` 增量修改
3. **图标字体不要替换**：layuimini 大量使用 `layui-icon` 与 FA 4.7.0 的混合图标
4. **layui.use 顺序**：先 `miniAdmin` 后 `miniTab`，否则多标签页初始化失败
5. **移动端兼容**：layuimini 已自适应，但表格在 768px 以下建议切换到 `page: { layout: ['prev', 'page', 'next'] }`

## 八、调试技巧

```js
// 打开 Layui 模块调试（dev 模式）
layui.config({ debug: true });

// 查看已加载模块
console.log(layui.cache);

// 重新渲染表格
layui.table.reloadData('list', { where: { cid: 5 } });
```

> ⚠️ `static/layui/` 目录一旦修改，前台访问会立即生效（不走 runcache），注意文件权限不要给 777。

## 九、相关源码位置

- `static/layui/` — Layui 静态资源
- `static/admin/admin.js` — 后台封装
- `admin/view/default/*.htm` — 后台模板
- `admin/control/*_control.class.php` — 后台控制器
- 主知识库"第五篇_模板引擎.md"、"第十三篇_开发规范与技巧.md" §80-82 — Layui 后台开发规范

## 十、FAQ

**Q：layui 报错 `layer.js: not found`？**
A：检查 `static/layui/lib/` 下 layer 是否完整；LECMS 把它放在 `layui/lib/layer/`。

**Q：表格列太多出现横向滚动条？**
A：在 `.layui-table-cell` 上加 `overflow: visible; white-space: normal;` 或缩小字号。

**Q：miniTab 重复打开同 URL？**
A：使用 `miniTab.checkUnique` 选项，或在 `end` 回调中刷新。

**Q：如何把后台主题从蓝色切深色？**
A：layuimini 提供多主题（参考 `static/layui/css/`），通过 `miniAdmin.render({ theme: 'dark' })` 切换。