<?php
/**
 * Hook: cms_template_apply
 * 主题运行时切换逻辑
 *
 * apply_theme($site_id): string
 * - 读取站点 theme 配置
 * - 只接受启用主题
 * - 检查主题目录存在
 * - 设置 CURRENT_THEME 常量
 * - 缺失目录或禁用主题回退 default
 */

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__, 4) . '/');
}

/**
 * 设置当前主题（CURRENT_THEME 常量，避免重复定义告警）
 */
function tm_set_theme($theme)
{
    if (!defined('CURRENT_THEME')) {
        define('CURRENT_THEME', $theme);
    }
    return $theme;
}

/**
 * 应用站点主题
 *
 * 站点配置来源优先级：
 *   1. get_site_config($site_id) —— 若由其它插件提供（如 site_manager 扩展）
 *   2. SITE_CONFIG 常量 —— 核心 base_control 注入（site 表 config JSON）
 *
 * 修复：不再硬调用未定义的 get_site_config()（无该函数环境直接 fatal），
 * 并移除 static 缓存（站群多站点同进程会串主题）。
 *
 * @param int $site_id 站点ID
 * @return string 主题名称或 'default'
 */
function apply_theme($site_id)
{
    $site_config = null;
    if (function_exists('get_site_config')) {
        $site_config = get_site_config((int)$site_id);
    } elseif (defined('SITE_CONFIG')) {
        $site_config = constant('SITE_CONFIG');
    }

    if (!is_array($site_config) || empty($site_config['theme'])) {
        return tm_set_theme('default');
    }

    $theme = $site_config['theme'];
    $enabled_themes = isset($site_config['enabled_themes']) ? $site_config['enabled_themes'] : array();

    // 验证主题是否启用
    if (!empty($enabled_themes) && !in_array($theme, $enabled_themes, true)) {
        return tm_set_theme('default');
    }

    // 验证主题目录存在（REQ-05-AC3 回退）
    $theme_dir = ROOT_PATH . 'view/' . $theme;
    if (!is_dir($theme_dir)) {
        return tm_set_theme('default');
    }

    // 主题有效
    return tm_set_theme($theme);
}
