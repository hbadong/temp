<?php
/**
 * Hook: admin_template_editor
 * 编辑器权限检查
 */
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__, 4) . '/');
}

// 最小安全 Hook
function hook_admin_template_editor($uid)
{
    // 待实现：权限检查逻辑
    return true;
}
