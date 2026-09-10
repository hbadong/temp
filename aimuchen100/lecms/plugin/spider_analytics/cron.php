<?php
/**
 * CLI 入口：OS crontab 建议 30 0 * * * 调用
 * 行为：汇总昨日 + 归档 >90 天 + 压缩 >1 年
 * 文件锁防重入（与 admin 首访兜底互斥）
 */
define('ROOT_PATH', true);
define('APP_PATH', dirname(__FILE__) . '/../../');
chdir(APP_PATH);
require_once APP_PATH . 'lecms/xiunophp/xiunophp.php';

require_once __DIR__ . '/lib/cidr.class.php';
require_once __DIR__ . '/lib/runtime.class.php';
require_once __DIR__ . '/model/runtime.class.php';
require_once __DIR__ . '/model/aggregator.class.php';

// 初始化 db
$db_type = 'db_' . $_ENV['_config']['db']['type'];
$db = new $db_type($_ENV['_config']['db']);

// 初始化 model/runtime 的 PDO 连接（模型使用 spider_runtime::$pdo 静态属性，
// 不 init 则聚合器/归档器对 null 调用 prepare() 会致命错误）
spider_runtime::init($db->wlink, $_ENV['_config']['db']['master']['tablepre']);

$lock = spider_lock::acquire('spider_analytics_cron');
if (!$lock) {
    echo "locked, another cron running\n";
    exit(0);
}

try {
    $ag = new spider_aggregator();
    $ag->aggregate_yesterday();
    if (spider_runtime_get('archive_enabled', '1') === '1') {
        $keep = (int)spider_runtime_get('archive_keep_days', 90);
        $n = $ag->archive_old($keep);
        echo "archived: $n\n";
    }
    $years = (int)spider_runtime_get('archive_compress_years', 1);
    $n = $ag->compress_old_hist($years);
    echo "compressed: $n\n";
    echo "OK\n";
} catch (Exception $e) {
    if (class_exists('log')) log::le_log('spider_analytics_cron:' . $e->getMessage());
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
} finally {
    spider_lock::release($lock);
}
