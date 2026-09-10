<?php
/**
 * 管理员首访兜底触发器
 * 编译期内联到 admin_control::__construct() 末尾
 * 行为：检测上次汇总时间，如 > 24h 则补跑 aggregator
 */
if (!function_exists('spider_analytics_admin_fallback')) {
function spider_analytics_admin_fallback() {
    static $ran = false;
    if ($ran) return;
    $ran = true;
    try {
        $base = PLUGIN_PATH . 'spider_analytics/';
        require_once $base . 'lib/cidr.class.php';
        require_once $base . '/lib/runtime.class.php';
        require_once $base . '/model/runtime.class.php';
        require_once $base . '/model/aggregator.class.php';

        // 注入 LECMS 数据库 PDO 连接（模型使用 spider_runtime::$pdo 静态属性）
        // 注意：本 hook 被编译内联到普通函数 spider_analytics_admin_fallback() 中，
        // 函数作用域内 $this 不存在（isset($this->db) 会抛
        // "Using $this when not in object context"），必须经 $GLOBALS['run']
        // （admin_control::__construct() 中已注入）获取控制器实例。
        $controller = isset($GLOBALS['run']) ? $GLOBALS['run'] : null;
        // 不能用 isset($controller->db) 判断：control 基类的 db 是 __get 魔术属性，
        // 构造期间未访问过时 isset 恒 false，会导致 init 被跳过、self::$pdo 为 null，
        // 随后 aggregator 对 null 调 prepare() 抛异常。直接访问 $controller->db->rlink
        // 会触发懒创建并建立连接。
        if (is_object($controller) && isset($_ENV['_config']['db']['master']['tablepre'])) {
            try {
                $pdo = $controller->db->rlink;
                if ($pdo) {
                    spider_runtime::init($pdo, $_ENV['_config']['db']['master']['tablepre']);
                }
            } catch (\Throwable $e2) {
                // 连接失败时静默，try_run() 内 pdo 为空会自动跳过
            }
        }

        $last = spider_runtime_get('last_aggregate_date', '0');
        $today = date('Y-m-d');
        if ($last !== $today) {
            $ag = new spider_aggregator();
            $ag->try_run();
        }
    } catch (\Throwable $e) {
        if (class_exists('log', false)) log::le_log('spider_analytics_admin_fallback:' . $e->getMessage());
    }
}
}

spider_analytics_admin_fallback();
