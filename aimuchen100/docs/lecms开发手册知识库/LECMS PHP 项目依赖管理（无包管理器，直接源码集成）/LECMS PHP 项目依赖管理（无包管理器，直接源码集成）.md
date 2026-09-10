---
kind: dependency_management
name: LECMS PHP 项目依赖管理（无包管理器，直接源码集成）
category: dependency_management
scope:
    - '**'
source_files:
    - index.php
    - admin/index.php
    - install/index.php
    - lecms/xiunophp/ext/email.class.php
---

该 LECMS 内容管理系统项目**未使用任何现代包管理器或依赖声明文件**。整个项目的第三方依赖采用传统的 PHP 源码直接集成方式：

1. **框架核心**：基于 XiunoPHP 框架，其源码直接内嵌在 `lecms/xiunophp/` 目录下，通过 `index.php` 中的 `FRAMEWORK_PATH` 常量定义和 `require FRAMEWORK_PATH.'xiunophp.php'` 加载。

2. **扩展组件**：邮件功能使用的 PHPMailer 等第三方库以源码形式存放在 `lecms/xiunophp/ext/phpmailer/` 目录中，通过 `require_once` 语句直接引入。

3. **前端资源**：jQuery、Layui 等 JavaScript 库的源码直接放置在 `static/js/`、`static/layui/` 等目录中，通过 HTML 模板中的 `<script>` 标签直接引用。

4. **插件系统**：项目内置的编辑器插件（如 editor_um、le_links）位于 `lecms/plugin/` 目录下，作为源码直接集成。

5. **运行时缓存**：编译后的控制器、模型、视图等缓存文件存储在 `runcache/` 目录中，由框架自动管理。

**关键特征**：
- 无任何 `composer.json`、`package.json`、`go.mod` 等依赖声明文件
- 无 `vendor/`、`node_modules/` 等依赖目录
- 无版本锁定文件或私有仓库配置
- 所有依赖都是直接拷贝源码到项目目录中
- 通过 PHP 的 `define()` 和 `require/require_once` 语句进行模块加载

这种依赖管理方式简单直接但缺乏版本控制和自动化更新能力，升级第三方库需要手动替换源码文件。