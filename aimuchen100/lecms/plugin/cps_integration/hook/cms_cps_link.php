<?php
defined('ROOT_PATH') || exit;

/**
 * Hook: cms_cps_link
 * 推广链接入口（扩展点，供核心/主题模板调用）
 *
 * 修复：空实现 → 真实中间页 URL（REQ-06-AC2）
 *
 * 注意：Hook 编译到 runcache 后路径会变化，
 * 使用全局 ROOT_PATH 常量加载依赖，不得用魔术目录常量。
 */

/**
 * 获取下载 URL（含中间页跳转）
 * @return string 如 index.php?download-index-id-123
 */
function hook_cms_cps_link($site_id, $game_id)
{
    // download_control 继承 base_control，仅前台运行时存在该基类；
    // 后台/CLI 上下文必须先探测，否则 `new download_control()` 触发
    // "Class 'base_control' not found" E_ERROR（try/catch 无法捕获类缺失）。
    if (!class_exists('base_control', false)) {
        return 'index.php?download-index-id-' . (int)$game_id;
    }
    if (!class_exists('download_control', false)) {
        require_once ROOT_PATH . 'lecms/plugin/cps_integration/control/download_control.class.php';
    }
    // download_control 构造依赖前台 runtime，
    // 实例化失败时仍回退到静态 URL 生成
    try {
        $ctrl = new download_control();
        return $ctrl->get_download_url((int)$site_id, (int)$game_id);
    } catch(Exception $e) {
        return 'index.php?download-index-id-' . (int)$game_id;
    }
}
