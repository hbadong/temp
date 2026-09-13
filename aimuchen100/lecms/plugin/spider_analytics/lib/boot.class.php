<?php
/**
 * 统一引导门面：一次性加载插件依赖并注入 PDO 连接
 * 供后台控制器（spider_*_control）与 hook 复用，消除重复 require_once/init
 */
if (!function_exists('spider_boot')) {
    function spider_boot($db = null) {
        static $loaded = false;
        $base = ROOT_PATH . 'lecms/plugin/spider_analytics/';
        require_once $base . 'lib/cidr.class.php';
        require_once $base . 'lib/runtime.class.php';
        require_once $base . 'model/runtime.class.php';
        require_once $base . 'model/blacklist.class.php';
        require_once $base . 'model/iprange.class.php';
        require_once $base . 'model/dashboard.class.php';
        require_once $base . 'model/aggregator.class.php';
        require_once $base . 'lib/csv.class.php';
        $loaded = true;

        // 注入 PDO（后台控制器传入 $this->db；hook/CLI 场景 $db 为 null 时跳过，
        // 由调用方自行注入）
        if ($db !== null && !spider_runtime::$pdo) {
            try {
                $pdo = $db->rlink;
                if ($pdo) {
                    spider_runtime::init($pdo, $_ENV['_config']['db']['master']['tablepre']);
                }
            } catch (\Throwable $e) {
                // 连接失败静默，依赖方会在无 pdo 时自动跳过
            }
        }
        return $loaded;
    }
}
