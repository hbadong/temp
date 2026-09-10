<?php
/**
 * 补丁系统 Cron 定时任务
 * 可选的远程补丁检查功能
 */

if (!defined('ROOT_PATH')) {
    exit;
}

$site_id = defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0;
if (empty($site_id)) {
    return;
}

// 远程检查功能暂未启用，预留接口
// 后续可通过配置开启自动检查远程补丁更新
