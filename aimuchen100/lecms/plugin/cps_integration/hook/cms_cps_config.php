<?php
defined('ROOT_PATH') || exit;

/**
 * Hook: cms_cps_config
 * CPS 配置入口（扩展点，供核心/主题模板调用）
 *
 * 修复：桩委托 → 真实模型调用（CpsConfig::get_best_link，REQ-06-AC2）
 *
 * 注意：Hook 编译到 runcache 后路径会变化，
 * 使用全局 ROOT_PATH 常量加载依赖，不得用魔术目录常量。
 */

/**
 * 获取 CPS 配置（最佳推广链接）
 * @return array|false 配置行
 */
function hook_cms_cps_config($site_id, $game_id)
{
    if (!class_exists('CpsConfig')) {
        require_once ROOT_PATH . 'lecms/plugin/cps_integration/model/cps_config.class.php';
    }
    $config = new CpsConfig();
    return $config->get_best_link((int)$site_id, (int)$game_id);
}
