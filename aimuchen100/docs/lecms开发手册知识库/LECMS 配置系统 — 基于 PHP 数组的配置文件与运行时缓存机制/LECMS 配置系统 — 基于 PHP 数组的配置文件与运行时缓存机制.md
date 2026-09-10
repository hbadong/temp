---
kind: configuration_system
name: LECMS 配置系统 — 基于 PHP 数组的配置文件与运行时缓存机制
category: configuration_system
scope:
    - '**'
source_files:
    - index.php
    - lecms/xiunophp/xiunophp.php
    - lecms/config/config.inc.php
    - lecms/config/plugin.inc.php
    - lecms/config/route.inc.php
    - install/config.sample.php
    - install/index.php
    - admin/control/setting_control.class.php
---

## 1. 系统与架构概述

LECMS 的配置系统基于 XiunoPHP 框架，采用 **PHP 数组配置文件 + 运行时缓存** 的双层结构。核心配置通过 `$_ENV['_config']` 全局数组暴露，由框架在启动时加载并注入到运行环境中。

### 配置加载流程
1. `index.php` 入口检查 `lecms/config/config.inc.php` 是否存在，不存在则重定向到安装向导
2. `xiunophp.php` 框架入口引入 `CONFIG_PATH.'config.inc.php'`，将配置载入 `$_ENV['_config']`
3. 可选加载自定义路由配置 `route.inc.php`（需启用 `route_open`）
4. 根据 `DEBUG` 模式决定直接加载源码或加载编译后的 `_lecms.php` 缓存文件

## 2. 核心配置文件

### 主配置文件：`lecms/config/config.inc.php`
- **数据库配置**：支持 mysql/mysqli/pdo_mysql 三种驱动，支持主从分离（master/slaves）
- **缓存配置**：支持 memcache，可开启两级缓存（enable/l2_cache）
- **安全配置**：auth_key、cookie_pre、cookie_domain 等
- **功能开关**：gzip、plugin_disable、lecms_parseurl、debug/debug_admin
- **语言与时区**：zone、lang、admin_lang
- **路径配置**：front_static、admin_static、url_suffix

### 插件配置：`lecms/config/plugin.inc.php`
- 返回关联数组，键为插件名，值为 enable 状态
- 安装向导会自动复制 `plugin.sample.php` 生成此文件

### 路由配置：`lecms/config/route.inc.php`
- 定义伪静态规则，通过 `$_ENV['_route']` 数组配置
- 默认空数组，需手动启用 `route_open` 才加载

## 3. 安装与配置生成机制

### 安装向导：`install/index.php`
- 提供 Web 界面完成环境检测、数据库初始化、配置文件生成
- 自动从 `config.sample.php` 模板生成 `config.inc.php`，替换占位符
- 自动生成 `plugin.inc.php` 和可选的 `route.inc.php`
- 安装完成后提示删除 install 目录

### 配置模板：`install/config.sample.php`
- 标准配置模板，包含所有必需字段
- 安装时通过正则替换生成实际配置文件

## 4. 运行时配置管理

### KV 存储系统
- 动态配置通过 `kv_model` 存储在数据库中，命名空间为 'cfg'
- 使用 `$this->kv->xset()` 和 `$this->kv->xget()` 读写
- 修改后调用 `$this->runtime->delete('cfg')` 清除缓存

### 后台设置接口：`admin/control/setting_control.class.php`
- 提供 Web 界面修改 debug、php_error、语言、spider_user_agent 等配置
- 直接通过正则替换更新 `config.inc.php` 文件内容
- 支持重置 admin 安全密钥、生成安全登录 URL

## 5. 配置分层与优先级

| 层级 | 来源 | 说明 |
|------|------|------|
| 基础配置 | `config.inc.php` | 数据库、缓存、路径等核心配置 |
| 运行时配置 | 数据库 kv_store | 用户可修改的业务配置 |
| 环境变量 | `$_ENV['_config']` | 框架统一访问接口 |
| 常量定义 | `define()` | 框架内部常量（ROOT_PATH、CONFIG_PATH 等） |

## 6. 安全约束与约定

- **配置文件权限**：`config.inc.php` 必须存在且可读，否则进入安装流程
- **安装目录保护**：安装完成后应删除 `install/` 目录，否则会显示 404
- **调试模式限制**：DEBUG > 1 时会暴露绝对路径和表前缀信息
- **Cookie 安全**：建议设置独立的 cookie_pre 和 cookie_domain
- **数据库前缀**：tablepre 用于隔离多站点数据

## 7. 扩展点与钩子

- 插件通过 `plugin.inc.php` 启用/禁用
- 路由通过 `route.inc.php` 自定义 URL 规则
- 后台设置页面支持 hook 扩展点（如 `admin_setting_control_after.php`）
- 运行时缓存文件位于 `runcache/` 目录，可手动清理

## 8. 关键文件清单

- `index.php` - 应用入口，检查配置文件存在性
- `lecms/xiunophp/xiunophp.php` - 框架核心，加载配置和初始化
- `lecms/config/config.inc.php` - 主配置文件
- `lecms/config/plugin.inc.php` - 插件配置
- `lecms/config/route.inc.php` - 路由配置
- `install/config.sample.php` - 配置模板
- `install/index.php` - 安装向导
- `admin/control/setting_control.class.php` - 后台配置管理
- `runcache/_lecms.php` - 运行时编译缓存

该系统采用传统的 PHP 配置文件模式，结构简单直观，适合中小型 CMS 项目。配置变更需要重启或清理缓存才能生效，不支持热重载。