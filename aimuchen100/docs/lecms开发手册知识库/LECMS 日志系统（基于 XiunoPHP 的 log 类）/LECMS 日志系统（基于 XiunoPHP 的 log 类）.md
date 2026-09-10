---
kind: logging_system
name: LECMS 日志系统（基于 XiunoPHP 的 log 类）
category: logging_system
scope:
    - '**'
source_files:
    - lecms/xiunophp/lib/log.class.php
    - lecms/xiunophp/lib/debug.class.php
    - lecms/xiunophp/lib/core.class.php
    - admin/control/admin_control.class.php
    - admin/control/index_control.class.php
    - lecms/control/user_control.class.php
    - lecms/model/cms_content_model.class.php
---

## 1. 使用的系统与框架
LECMS 基于 XiunoPHP 框架内置的 `log` 类实现日志记录，位于 `lecms/xiunophp/lib/log.class.php`。该日志系统提供两种写入方式：
- `log::write($message, $file)` — 通用日志写入，按配置开关控制是否输出
- `log::le_log($data, $file)` — 调试专用日志，仅在 DEBUG=1 时输出

此外，`debug.class.php` 负责错误与异常捕获，并通过 `log::write` 统一写入日志文件。

## 2. 核心文件与位置
- `lecms/xiunophp/lib/log.class.php` — 日志核心类，提供 write、trace、trace_save、le_log 等方法
- `lecms/xiunophp/lib/debug.class.php` — 错误/异常处理器，集成日志记录
- `lecms/xiunophp/lib/core.class.php` — 404 错误时调用 `log::write` 记录
- `admin/control/admin_control.class.php` — 后台登录失败记录到 `login_log.php`
- `admin/control/index_control.class.php` — 密码错误记录到 `login_log.php`
- `lecms/control/user_control.class.php` — 用户登录失败记录到 `user_login_log.php`
- `lecms/model/cms_content_model.class.php` — 内容模型调试日志记录到 `cms_content_xadd_error`
- `log/` 目录 — 日志文件存储根目录

## 3. 架构与约定
### 日志格式
所有日志行采用固定格式：`时间 IP URL 消息`，每行以 `<?php exit;?>` 开头防止直接访问。

### 日志级别策略
- **E_NOTICE/E_USER_NOTICE/E_DEPRECATED** 级别的 PHP 错误在线上模式仅记录不中断执行
- **其他错误** 在线上模式抛出异常并显示友好错误页面
- **DEBUG=1** 时显示详细堆栈跟踪，否则隐藏敏感信息

### 日志文件组织
- 默认日志文件：`php_error.php`（无后缀名）
- 业务日志：`login_log.php`、`user_login_log.php`、`php_error404.php` 等
- 调试日志：按月分目录 `Ym/文件名.php`，如 `2024/error.php`
- 追踪日志：`php_trace.php` 包含 POST 数据、SQL 语句和执行轨迹

### 配置开关
通过 `$_ENV['_config'][文件名前缀]` 配置项控制特定日志是否启用，例如 `login_log`、`user_login_log` 等键值对。

## 4. 约定与约束
- 所有日志文件必须以 `<?php exit;?>` 开头，防止被直接访问
- 日志内容中的换行符会被替换为空格，避免多行记录
- 线上环境（DEBUG=0）下 E_NOTICE 类错误仅记录不中断程序
- 调试模式（DEBUG=1）下所有错误都会抛出异常并显示详细信息
- 日志写入失败时静默忽略异常，不影响主程序执行
- 404 错误会自动记录到 `php_error404.php` 文件
- 用户登录失败会记录用户名和来源 IP 到相应日志文件