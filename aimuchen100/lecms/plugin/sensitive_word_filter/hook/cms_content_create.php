<?php
/**
 * 手动发布时敏感词检测
 * Hook: cms_content_create
 */

if (!defined('ROOT_PATH')) {
    exit;
}

$site_id = defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0;
if (empty($site_id)) {
    return;
}

// 检查插件是否启用及内容类型过滤配置
$setting_file = PLUGIN_PATH . 'sensitive_word_filter/setting.php';
$settings = array();
if (file_exists($setting_file)) {
    $settings = include $setting_file;
}
if (isset($settings['enabled']) && empty($settings['enabled'])) {
    return;
}
if (!empty($settings['filter_content_types']) && !in_array('content_create', $settings['filter_content_types'])) {
    return;
}

$content = $GLOBALS['hook_content'] ?? '';
$content_id = $GLOBALS['hook_content_id'] ?? 0;

if (empty($content)) {
    return;
}

global $db;
$filter = new sensitive_filter($site_id, $db);
$found = $filter->detect($content);

if (!empty($found)) {
    $word = $found[0]['word'];
    $level = $found[0]['level'];

    if ($level == 3) {
        $GLOBALS['hook_block'] = true;
        $GLOBALS['hook_message'] = '内容包含敏感词，发布被阻止';
    } elseif ($level == 2) {
        $GLOBALS['hook_content'] = $filter->replace_words($content)['text'];
    }

    $filter->log($word, $content_id, $level == 3 ? 'reject' : 'replace');
}
