<?php
/**
 * 自媒体同步 Cron 定时任务
 * 每10分钟执行一次同步队列
 */

if (!defined('ROOT_PATH')) {
    exit;
}

$site_id = defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0;
if (empty($site_id)) {
    return;
}

$queue = new sync_queue($site_id);
$queue->process_queue(10);
