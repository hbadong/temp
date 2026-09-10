<?php
defined('ROOT_PATH') || exit;

define('ROOT_PATH', true);
define('APP_PATH', dirname(__FILE__) . '/../../');
chdir(APP_PATH);
require_once APP_PATH . 'lecms/xiunophp/xiunophp.php';
require_once __DIR__ . '/model/export_log.class.php';

$pre = $_ENV['_config']['db']['master']['tablepre'];
$db_type = 'db_' . $_ENV['_config']['db']['type'];
$pdo = new $db_type($_ENV['_config']['db']);
$pdo = isset($pdo->pdo) ? $pdo->pdo : null; // PHP 5.4 兼容
spider_export_log::init($pdo, $pre);
$log = new spider_export_log();
$rows = $log->list_expired(7);
$n = 0;
foreach ($rows as $r) {
    if (!empty($r['file_path']) && file_exists(APP_PATH . $r['file_path'])) {
        @unlink(APP_PATH . $r['file_path']);
    }
    $log->mark_expired($r['id']);
    $n++;
}
echo "expired: $n\n";
