# YzmCMS 文档

YzmCMS V7.5 是一款基于 YZMPHP 框架开发的轻量级开源内容管理系统，采用 PHP+Mysql 架构。系统具有体积轻巧、功能强大、源码简洁、系统安全等特点，适用于构建企业网站、新闻网站、个人博客、门户网站等各种类型的网站。

**快速链接**: [架构](./ARCHITECTURE.md) | [接口](./INTERFACES.md) | [开发者指南](./DEVELOPER_GUIDE.md)

---

## 核心文档

### [架构](./ARCHITECTURE.md)
系统设计、技术栈、组件结构和数据流程。从这里开始了解系统如何运作。

### [接口](./INTERFACES.md)
公开 API、模板标签、控制器方法。集成或扩展此系统的参考。

### [开发者指南](./DEVELOPER_GUIDE.md)
环境搭建、开发规范、控制器/模型/视图开发、常见任务。贡献者必读。

---

## 核心概念

理解这些领域概念有助于导航代码库：

| 概念 | 描述 |
|------|------|
| [栏目](./专有概念/Category.md) | 网站内容分类结构，支持树形层级和多种栏目类型 |
| [内容模型](./专有概念/Model.md) | 定义不同内容类型的数据结构，支持自定义字段 |

---

## 模块

| 模块 | 描述 |
|------|------|
| `application/admin/` | 后台管理模块，包含内容、栏目、会员、权限等管理功能 |
| `application/index/` | 前台展示模块，处理网站页面的展示 |
| `application/member/` | 会员中心模块，用户注册、登录、个人中心等 |
| `application/wechat/` | 微信集成模块，与微信公众平台对接 |
| `yzmphp/core/` | YZMPHP 框架核心类库 |

---

## 入门指南

### 首次接触此系统？

按此路径学习：
1. **[架构](./ARCHITECTURE.md)** - 了解系统全局和技术栈
2. **[核心概念](#核心概念)** - 学习栏目和模型的概念
3. **[开发者指南](./DEVELOPER_GUIDE.md)** - 搭建开发环境

### 需要二次开发？

1. **[开发者指南](./DEVELOPER_GUIDE.md)** - 开发规范和代码示例
2. **[接口](./INTERFACES.md)** - API 和模板标签参考
3. **[架构](./ARCHITECTURE.md)** - 系统组件和依赖关系

### 想了解数据模型？

1. **[架构文档 - 数据库设计](./ARCHITECTURE.md)** - 核心表结构和关系
2. **[内容模型](./专有概念/Model.md)** - 模型和字段的详细说明
3. **[栏目](./专有概念/Category.md)** - 栏目与内容的关系

---

## 快速参考

### 命令

```bash
# 访问后台管理
http://domain/admin.php

# 访问前台首页
http://domain/

# 安装向导
http://domain/install/
```

### 重要文件

| 文件 | 目的 |
|------|------|
| `index.php` | 单一入口，配置 URL 模式 |
| `common/config/config.php` | 主配置文件 |
| `yzmphp/yzmphp.php` | 框架入口 |
| `yzmphp/core/class/application.class.php` | 应用创建类 |
| `application/admin/controller/content.class.php` | 内容管理控制器 |

### URL 路由模式

| 模式 | 值 | 示例 |
|------|-----|------|
| MCA兼容 | 0 | `?m=index&c=index&a=init` |
| S简化 | 1 | `?s=index/index/init` |
| SEO优化 | 3 | `/index/init.html`（推荐） |

---

## 系统特性

- **自主研发框架**: YZMPHP 2.9，轻量高效
- **MVC架构**: 模块式开发，易于维护和扩展
- **多模型支持**: 支持创建自定义内容模型
- **模板引擎**: 支持模板标签和模板继承
- **会员系统**: 完整的会员注册、登录、积分体系
- **微信集成**: 支持微信菜单、消息、素材管理
- **SEO优化**: 多种 URL 模式、伪静态、 sitemap
- **安全防护**: CSRF、XSS、SQL 注入防护