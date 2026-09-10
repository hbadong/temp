<?php
/**
 * Spider Pool - base_control_construct_after Hook
 *
 * 在页面 HTML 输出后，向 </body> 之前注入蜘蛛池隐藏链接。
 *
 * 合法运行时挂载点（base_control.class.php:113，base_control::__construct() 末尾）。
 * 禁止使用类体级挂载点 hook/base_control_after.php —— 内联进类体会导致整站 parse error
 * （spider-mode 已踩过此坑）。
 *
 * 框架机制注意事项：
 * - process_hook()（core.class.php:544）把本文件内容原样内联进 runcache 编译的
 *   base_control.class.php 的 __construct() 方法体，因此：
 *   - 不能定义全局函数，render_links()/inject_links() 实现为 spider_pool 静态方法
 *   - __DIR__ 指向 runcache 编译目录，require 必须用 ROOT_PATH 绝对路径
 * - LECMS 无全局 db() 函数，构造期把 $this->db 注入 spider_pool 单例，
 *   shutdown 回调里即可直接复用当前请求的数据库连接
 *
 * 与 spider-mode 的去重协作（关键）：
 * - spider-mode（rank 40）与本插件（rank 41）的 hook 会被内联进同一个 __construct()，
 *   两者各自 ob_start() + register_shutdown_function()，回调按注册顺序执行，
 *   形成"内层缓冲先被取走、处理后 echo 进外层缓冲"的链式管道。
 * - 两个 hook 共用 SPIDER_POOL_HOOK_INJECTED 常量作为注入 guard：
 *   谁先完成蜘蛛池注入谁 define，后者检测到即跳过注入（但仍需原样输出 HTML）。
 *
 * @author     沐尘100
 * @version    1.0.0
 * @cms_version 3.0.0
 */

// 防重复：同一请求多次构造 base_control 时只注册一次；
// 若蜘蛛池链接已被 spider-mode 注入（guard 已定义），本 hook 无需再介入。
if (defined('SPIDER_POOL_HOOK_RUN') || defined('SPIDER_POOL_HOOK_INJECTED')) {
    return;
}
define('SPIDER_POOL_HOOK_RUN', true);

// 加载模型类（ROOT_PATH 绝对路径，hook 内联到 runcache 后 __DIR__ 失效）
if (!class_exists('spider_pool', false) && file_exists(ROOT_PATH . 'lecms/plugin/spider_pool/model/spider_pool.class.php')) {
    require_once ROOT_PATH . 'lecms/plugin/spider_pool/model/spider_pool.class.php';
}

if (class_exists('spider_pool', false)) {
    $spider_pool_site_id = defined('CURRENT_SITE_ID') ? (int)CURRENT_SITE_ID : 0;

    // 构造期注入 db：shutdown 阶段 $GLOBALS['run'] 未必可用
    try {
        spider_pool::instance($this->db, $spider_pool_site_id);
    } catch (Throwable $spider_pool_e) {
        // db 不可用时交给 shutdown 回调统一降级，不影响页面构造
    }

    ob_start();
    register_shutdown_function(function () use ($spider_pool_site_id) {
        $html = ob_get_clean();
        if ($html === false) {
            $html = '';
        }

        // spider-mode 已注入过则跳过，但必须原样输出 HTML（不得吞掉页面）
        if (!defined('SPIDER_POOL_HOOK_INJECTED')) {
            try {
                $pool = spider_pool::instance();
                // site_id > 0 时按站点严格隔离；无站点上下文时取全部活跃域名
                $domains = $spider_pool_site_id > 0
                    ? $pool->get_active($spider_pool_site_id)
                    : $pool->get_active();
                if (!empty($domains)) {
                    $html = spider_pool::inject_links($html, $domains);
                }
                define('SPIDER_POOL_HOOK_INJECTED', true);
            } catch (Throwable $e) {
                // 数据库/模型异常时跳过注入，不影响页面输出
            }
        }

        echo $html;
    });
}
