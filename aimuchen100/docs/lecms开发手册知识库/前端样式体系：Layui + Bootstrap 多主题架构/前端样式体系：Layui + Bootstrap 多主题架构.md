---
kind: frontend_style
name: 前端样式体系：Layui + Bootstrap 多主题架构
category: frontend_style
scope:
    - '**'
source_files:
    - static/layui/css/public.css
    - view/default/style/style.css
    - view/blog_Quietlee/static/css/style.css
    - view/justnews/css/style.css
    - install/css/install.css
    - static/js/umeditor/themes/default/css/umeditor.min.css
---

## 样式系统概览

LECMS 项目采用**多框架混合**的前端样式架构，以 Layui 作为后台管理界面核心 UI 框架，Bootstrap 3 作为前台主题的基础栅格与组件库，配合多个独立主题实现差异化视觉风格。

## 核心框架与工具

**后台管理界面（admin）**
- 基于 **Layui v2.8.15** 构建，使用 `static/layui/` 目录下的完整框架资源
- 自定义样式集中在 `static/layui/css/public.css`，定义了表单、表格、滚动条等通用样式
- 通过 `layuimini` 扩展包提供侧边栏、标签页、主题切换等管理界面功能
- 富文本编辑器集成 UEditor，样式位于 `static/js/umeditor/themes/default/css/umeditor.min.css`

**前台主题系统**
- **default 主题**：基于 Bootstrap 3.3.7 的响应式主题，主样式文件 `view/default/style/style.css`（5000+行）
- **blog_Quietlee 主题**：博客风格主题，使用 `view/blog_Quietlee/static/css/style.css`（2000+行），支持夜间模式、三图/单图展示模式
- **justnews 主题**：新闻类主题，直接引入 Bootstrap 源码 `view/justnews/css/style.css`（17000+行）

## 设计系统与约定

**颜色体系**
- 主色调：蓝色系 `#3297fc`、`#448EF6`、`#3ca5f6` 贯穿各主题
- 状态色：成功 `#3c763d`、警告 `#8a6d3b`、危险 `#a94442` 遵循 Bootstrap 语义化命名
- 背景色：浅灰 `#f2f2f2`、白色 `#ffffff`、深灰 `#333333` 构成基础配色

**响应式策略**
- 断点设置：xs(<768px)、sm(≥768px)、md(≥992px)、lg(≥1200px)
- 栅格系统：12列布局，支持 push/pull/offset 偏移
- 移动端优先：大量 `@media (max-width: 768px)` 查询处理移动端适配

**组件规范**
- 卡片组件：统一圆角 `border-radius: 8px`、阴影 `box-shadow: 0px 0px 20px -5px rgba(158,158,158,0.22)`
- 按钮样式：渐变背景 `linear-gradient(135deg, #59c3fb 10%, #268df7 100%)`、悬停效果
- 列表项：虚线分隔 `border-bottom: 1px dashed var(--theme-line-color)`、悬停位移 `transform: translateY(-5px)`

## 主题变量与 CSS 变量

各主题广泛使用 CSS 自定义属性实现主题化：
- `--theme-color`：主题主色
- `--theme-black-color`：深色文字
- `--theme-gray-color`：灰色文字
- `--theme-line-color`：分割线颜色
- `--btn-color`：按钮默认色

## 第三方库集成

**图标字体**
- Font Awesome 4.7.0：`static/layui/lib/font-awesome-4.7.0/`
- 自定义图标：`iconfont.css`、`font_3409728_exzixulsu4k.css`

**动画与交互**
- Swiper.js：轮播图组件 `view/default/script/swiper.min.js`
- NProgress：页面加载进度条
- jQuery 生态：拖拽排序、懒加载、无限滚动等插件

**字体资源**
- HarmonyOS Sans：`view/blog_Quietlee/static/font/HarmonyOS_Sans.subset.woff2`
- 微软雅黑：`Microsoft YaHei` 作为中文字体回退

## 安装向导样式

安装界面 `install/css/install.css` 采用极简设计，基于 Layui Card 组件，统一的浅灰背景和红色必填标记，确保部署过程的清晰易用。

## 样式组织模式

每个主题遵循统一结构：
```
view/{theme}/
├── style/ 或 css/          # 样式文件
├── script/ 或 js/          # 脚本文件  
├── image/ 或 img/          # 图片资源
├── font/                   # 字体文件
└── user/                   # 用户中心模板
```

这种分层架构使得主题开发和维护相对独立，便于在不同业务场景下快速切换视觉风格。