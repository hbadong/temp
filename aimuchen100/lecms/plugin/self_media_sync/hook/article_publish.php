<?php
/**
 * 文章发布后自动触发同步
 */

if (!defined('ROOT_PATH')) {
    exit;
}

$article_id = defined('ARTICLE_ID') ? ARTICLE_ID : 0;
if (empty($article_id)) {
    return;
}

$site_id = defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0;

// 获取启用的平台账号
$tablepre = $_ENV['_config']['db']['master']['tablepre'];
$db_type = 'db_' . $_ENV['_config']['db']['type'];
$db = new $db_type($_ENV['_config']['db']);
$db = $db->db ?? $db;
$accounts = $db->fetch_all("
    SELECT id, platform FROM `{$tablepre}media_account`
    WHERE site_id = " . (int)$site_id . " AND status = 1
");

if (empty($accounts)) {
    return;
}

$queue = new sync_queue($site_id);

foreach ($accounts as $account) {
    $queue->add_task($article_id, $account['id'], $account['platform']);
}
