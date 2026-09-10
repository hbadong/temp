<?php
defined('ROOT_PATH') || exit;

/**
 * Hook: cms_cps_log
 * 点击日志入口（扩展点，供核心/主题模板调用）
 *
 * 修复：桩实现 → 完整点击记录 + 24h 去重（REQ-06-AC4/AC5）
 *
 * 注意：Hook 编译到 runcache 后路径会变化，
 * 使用全局 ROOT_PATH 常量加载依赖，不得用魔术目录常量。
 */

/**
 * 记录点击日志
 * @return bool
 */
function hook_cms_cps_log($site_id, $game_id, $cps_id)
{
    if (!class_exists('CpsLog')) {
        require_once ROOT_PATH . 'lecms/plugin/cps_integration/model/cps_log.class.php';
    }
    if (!class_exists('CpsConfig')) {
        require_once ROOT_PATH . 'lecms/plugin/cps_integration/model/cps_config.class.php';
    }

    $ip_text = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    $ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';

    $log = new CpsLog();
    $is_unique = $log->is_unique_click((int)$cps_id, $ip_text) ? 1 : 0;
    $log->record_click((int)$site_id, (int)$game_id, (int)$cps_id, $is_unique, 0, $ip_text, $ua, $referer);

    // 累加配置表计数
    $config = new CpsConfig();
    $config->increment_click((int)$cps_id, (bool)$is_unique);

    return true;
}
