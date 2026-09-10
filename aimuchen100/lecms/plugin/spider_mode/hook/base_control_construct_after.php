<?php
/**
 * Spider Mode - base_control_construct_after Hook
 *
 * 合法运行时挂载点（base_control.class.php:113，base_control::__construct() 末尾，
 * 每次前台请求构造时执行，$this 可用，$this->db 可用）。
 *
 * 合并三块逻辑：
 * 1. 蜘蛛检测（原 cms_init 逻辑迁移：框架无 cms_init hook 触发点，IS_SPIDER 依赖本 hook 定义）
 * 2. 双面渲染（ob_start() + register_shutdown_function + ob_get_clean()，
 *    仅 spider_mode_active = 1 时启用，避免 ob_get_flush 导致的重复输出）
 * 3. spider-pool 链接注入（插件不存在时优雅跳过）
 *
 * 框架机制注意事项：
 * - process_hook()（core.class.php:544）把本文件内容原样内联进 runcache 编译的
 *   base_control.class.php 的 __construct() 方法体内，因此：
 *   - 不能定义全局函数（会触发 fatal error），蜘蛛池注入逻辑内联在闭包内
 *   - __DIR__ 指向 runcache 编译目录而非插件目录，require 必须用 ROOT_PATH 绝对路径
 * - 无全局 db() 函数，通过 $this->db（control::__get 注入 db_mysql 实例）访问数据库
 *
 * @author     沐尘100
 * @version    1.1.0
 * @cms_version 3.0.0
 */

// 防重复（构造时执行一次）
if (defined('SPIDER_MODE_RUN')) {
    return;
}
define('SPIDER_MODE_RUN', true);

// 加载模型类（ROOT_PATH 绝对路径，hook 内联到 runcache 后 __DIR__ 失效）
require_once ROOT_PATH . 'lecms/plugin/spider_mode/model/spider_detect.class.php';
require_once ROOT_PATH . 'lecms/plugin/spider_mode/model/access_mode.class.php';

// 蜘蛛检测（原 cms_init 逻辑迁移；框架无 cms_init hook 点）
if (!defined('IS_SPIDER')) {
    $spider_ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    $spider_engine = SpiderDetect::detect($spider_ua);
    define('IS_SPIDER', $spider_engine !== false);
    if ($spider_engine !== false) {
        $spider_site_id = defined('CURRENT_SITE_ID') ? (int)CURRENT_SITE_ID : 0;
        if ($spider_site_id > 0) {
            $spider_ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
            $spider_url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
            $spider_dateline = isset($_ENV['_time']) ? $_ENV['_time'] : time();
            $spider_db = $this->db;
            $spider_sql = "INSERT INTO `{$spider_db->tablepre}spider_log` (`site_id`, `engine`, `ip`, `user_agent`, `url`, `dateline`) VALUES ("
                . intval($spider_site_id) . ", '" . addslashes($spider_engine) . "', '" . addslashes($spider_ip) . "', '"
                . addslashes($spider_ua) . "', '" . addslashes($spider_url) . "', " . intval($spider_dateline) . ")";
            $spider_db->query($spider_sql);
        }
    }
}

// 双面渲染 + 蜘蛛池注入
try {
$spider_mode_site_id = defined('CURRENT_SITE_ID') ? (int)CURRENT_SITE_ID : 0;
$spider_mode_access = new AccessMode($spider_mode_site_id, $this->db);
if ($spider_mode_access->is_active()) {
    ob_start();
    register_shutdown_function(function () use ($spider_mode_access, $spider_mode_site_id) {
        $html = ob_get_clean();
        if ($html === false) {
            $html = '';
        }
        $html = $spider_mode_access->render(function () use ($html) { return $html; });
        // spider-pool 注入（插件缺失时优雅跳过）
        // 去重 guard：spider-pool 自身 hook（plugin/spider-pool/hook/base_control_construct_after.php）
        // 与本兜底逻辑共用 SPIDER_POOL_HOOK_INJECTED；两插件同时启用时谁先注入谁 define，
        // 后者跳过注入但仍原样输出 HTML，避免同一批链接被注入两次。
        if (!defined('SPIDER_POOL_HOOK_INJECTED')) {
            if (!class_exists('spider_pool', false) && file_exists(ROOT_PATH . 'lecms/plugin/spider_pool/model/spider_pool.class.php')) {
                require_once ROOT_PATH . 'lecms/plugin/spider_pool/model/spider_pool.class.php';
            }
            if (class_exists('spider_pool', false)) {
                try {
                    $pool = spider_pool::instance();
                    $domains = $pool->get_active();
                    if (!empty($domains)) {
                        $links = '';
                        foreach ($domains as $d) {
                            $links .= '<a href="http://' . htmlspecialchars($d) . '" target="_blank">' . htmlspecialchars($d) . '</a>';
                        }
                        $html = str_ireplace('</body>', '<div style="display:none">' . $links . '</div></body>', $html);
                    }
                    define('SPIDER_POOL_HOOK_INJECTED', true);
                } catch (Throwable $e) { /* 忽略，不影响页面 */ }
            }
        }
        echo $html;
    });
}
} catch (Throwable $e) { /* spider_mode 表未创建时跳过，不影响页面 */ }
