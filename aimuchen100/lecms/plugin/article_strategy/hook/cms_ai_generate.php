<?php
/**
 * CMS AI 生成内容重组 Hook
 * 在 AI 生成内容后、入库前触发内容重组处理
 *
 * Hook 点位置：CMS AI 生成流程中内容生成完成后
 * 触发时机：AI 生成文章内容，准备写入数据库之前
 */

// 获取站点 ID
$site_id = (int)(defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0);

// 获取配置
$config = array();
$config['ai_reorganize'] = true; // 默认开启 AI 深度重组

// 加载插件配置（如果存在）
$setting_file = PLUGIN_PATH . 'article_strategy/setting.php';
if (file_exists($setting_file)) {
    $setting = include $setting_file;
    if (isset($setting['ai_reorganize'])) {
        $config['ai_reorganize'] = (bool)$setting['ai_reorganize'];
    }
}

// 获取 AI 生成的内容
// 支持多种输入方式：$_POST、参数传入、全局变量
$ai_content = '';
if (isset($_POST['content'])) {
    $ai_content = $_POST['content'];
} elseif (isset($_GET['content'])) {
    $ai_content = $_GET['content'];
} elseif (isset($GLOBALS['ai_generated_content'])) {
    $ai_content = $GLOBALS['ai_generated_content'];
}

if (empty($ai_content) || !is_string($ai_content)) {
    return;
}

// 加载内容重组引擎（文件名不符合 core::model() 约定，显式 require）
require_once ROOT_PATH . 'lecms/plugin/article_strategy/model/content_reorganizer.class.php';

// 加载内容重组引擎
$reorganizer = core::model('content_reorganizer');

// 执行内容重组
$reorganized = $reorganizer->reorganize($ai_content, $config);

// 将重组后的内容回写
// 优先更新 $_POST（文章尚未入库）
// 其次更新全局变量
if (isset($_POST['content'])) {
    $_POST['content'] = $reorganized;
}
if (isset($_GET['content'])) {
    $_GET['content'] = $reorganized;
}
$GLOBALS['ai_generated_content'] = $reorganized;

// 可选：记录重组日志
// core::debug('content_reorganizer', 'AI 内容重组完成');
