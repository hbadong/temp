<?php
/**
 * 竞品分析 Cron 定时任务
 * 每周一凌晨自动生成周报
 */

if (!defined('ROOT_PATH')) {
    exit;
}

$site_id = defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0;
if (empty($site_id)) {
    return;
}

$tablepre = $_ENV['_config']['db']['master']['tablepre'];
$db_type = 'db_' . $_ENV['_config']['db']['type'];
$db = new $db_type($_ENV['_config']['db']);
$db = $db->db ?? $db;

$engine = new analysis_engine($site_id, $db);
$report_ids = $engine->batch_analyze();

// 记录日志
if (!empty($report_ids)) {
    $log_file = sys_get_temp_dir() . '/competitor_analysis_cron.log';
    $log_entry = date('Y-m-d H:i:s') . " - Site: {$site_id}, Reports: " . count($report_ids) . "\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}
