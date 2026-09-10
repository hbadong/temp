<?php
/**
 * Template Manager - cms_template_apply Hook
 *
 * 主题运行时切换逻辑：
 * 1. 读取站点配置中的 theme 字段（pre_site_manager 表）
 * 2. 校验主题在 pre_cms_template 表中 enabled=1
 * 3. 设置 CURRENT_THEME 常量
 * 4. 读取站点 config 字段中的 theme_vars 并直接输出 <style>:root{...}</style> 注入块
 *
 * 框架机制：
 * - cms_template_apply 在模板应用阶段执行（view.class.php 调用前）
 * - $this 为前台 control 实例，$this->db 可用
 */

if (defined('CURRENT_THEME')) {
    return;
}

/**
 * CSS 值安全转义
 *
 * 防止通过 theme_vars 注入恶意 CSS 代码。
 * addslashes() 仅转义引号和反斜杠，不符合 CSS 转义规则；
 * 此函数按 CSS 规范对结构字符进行转义。
 *
 * @param mixed $value 原始值
 * @return string 转义后的 CSS 值
 */
function css_escape_value($value) {
    $value = (string)$value;
    // 先处理反斜杠（必须最先处理，避免双重转义）
    $value = str_replace('\\', '\\\\', $value);
    // 转义引号
    $value = str_replace('"', '\\"', $value);
    $value = str_replace("'", "\\'", $value);
    // 转义结构字符，防止突破 CSS 声明边界
    $value = str_replace(';', '\\;', $value);
    $value = str_replace('}', '\\}', $value);
    $value = str_replace('{', '\\{', $value);
    // 处理换行（CSS 中用 \A 表示换行）
    $value = str_replace("\n", '\\A ', $value);
    $value = str_replace("\r", '', $value);
    return $value;
}

$site_id = defined('CURRENT_SITE_ID') ? (int)CURRENT_SITE_ID : 0;
$db = isset($this) && isset($this->db) ? $this->db : null;

$theme_name = '';
$theme_vars = array();

if ($site_id > 0 && $db) {
    // 安全获取表前缀（$_ENV 可能不存在，提供降级值）
    $tablepre = (isset($_ENV['_config']['db']['master']['tablepre']) ? $_ENV['_config']['db']['master']['tablepre'] : 'pre_');

    // 1. 读取站点主题配置（pre_site_manager 表的 theme 字段）
    $site = $db->fetch_first("
        SELECT `theme`, `config` FROM `{$tablepre}site_manager`
        WHERE `sid` = '{$site_id}' LIM 1
    ");
    if ($site && !empty($site['theme'])) {
        $theme_name = $site['theme'];
    }

    // 2. 校验主题是否启用（使用白名单校验防止 SQL 注入）
    if ($theme_name && preg_match('/^[a-zA-Z0-9_-]+$/', $theme_name)) {
        $template = $db->fetch_first("
            SELECT `enabled` FROM `{$tablepre}cms_template`
            WHERE `name` = '{$theme_name}' AND `enabled` = 1
            LIMIT 1
        ");
        if (!$template) {
            // 主题未启用，回退默认主题
            $default = $db->fetch_first("
                SELECT `name` FROM `{$tablepre}cms_template`
                WHERE `is_default` = 1 AND `enabled` = 1 LIMIT 1
            ");
            $theme_name = $default ? $default['name'] : 'default';
        }
    } else {
        // 主题名格式不合法，回退默认主题
        $default = $db->fetch_first("
            SELECT `name` FROM `{$tablepre}cms_template`
            WHERE `is_default` = 1 AND `enabled` = 1 LIMIT 1
        ");
        $theme_name = $default ? $default['name'] : 'default';
    }

    // 3. 读取站点 theme_vars（从 config JSON 字段中解析）
    if ($site && !empty($site['config'])) {
        $config = json_decode($site['config'], true);
        if (is_array($config) && !empty($config['theme_vars']) && is_array($config['theme_vars'])) {
            $theme_vars = $config['theme_vars'];
        }
    }
} else {
    // 无站点上下文，使用默认主题
    $theme_name = 'default';
}

if (empty($theme_name)) {
    $theme_name = 'default';
}

define('CURRENT_THEME', $theme_name);

// 4. 直接输出 <style>:root{...}</style> 注入块
if (!empty($theme_vars)) {
    $css_vars = array();
    foreach ($theme_vars as $key => $value) {
        // 安全过滤：只允许 CSS 变量格式
        if (preg_match('/^--[a-zA-Z0-9_-]+$/', $key)) {
            $css_vars[] = "{$key}:" . css_escape_value($value);
        }
    }
    if (!empty($css_vars)) {
        echo '<style>:root{' . implode(';', $css_vars) . '}</style>';
    }
}
