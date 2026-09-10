---
kind: error_handling
name: LECMS错误处理体系
category: error_handling
scope:
    - '**'
source_files:
    - lecms/xiunophp/lib/debug.class.php
    - lecms/xiunophp/lib/base.func.php
    - lecms/control/error404_control.class.php
    - lecms/xiunophp/xiunophp.php
---

LECMS基于XiunoPHP框架实现了完整的错误处理体系，涵盖运行时错误捕获、异常处理、404页面和调试模式。核心机制如下：

**1. 全局错误处理器（debug.class.php）**
- `debug::error_handler()`：通过`set_error_handler()`注册，将PHP错误转换为Exception对象
- `debug::exception_handler()`：统一异常处理，根据DEBUG模式决定输出详细堆栈或友好错误页
- `debug::shutdown_handler()`：程序关闭时的致命错误捕获
- 支持E_ERROR、E_WARNING、E_NOTICE等所有PHP错误级别

**2. 调试模式控制**
- DEBUG=0：生产环境，隐藏错误详情，仅记录日志
- DEBUG=1：开发环境，显示完整错误信息
- DEBUG=2：调试模式，输出性能统计和SQL追踪

**3. AJAX错误响应**
- `E($err, $msg)`函数返回统一JSON格式：`{'err':状态码,'msg':'消息','name':'名称'}`
- AJAX请求的异常会返回`{'error':'错误信息'}`格式

**4. 404错误处理**
- `error404_control.class.php`专门处理未找到页面的情况
- 设置HTTP状态码为404，渲染404.htm模板
- 控制器不存在时自动跳转到error404控制器的index方法

**5. 错误日志记录**
- `log::write()`记录到`log/`目录下的php_error*.php文件
- 区分普通通知（E_NOTICE）和严重错误的不同处理策略

**6. 安全考虑**
- 生产环境禁止显示错误详情，防止敏感信息泄露
- 数据库连接错误时提示查看配置文件路径而非直接显示配置
- 使用`defined('ROOT_PATH') or exit;`防止直接访问类文件