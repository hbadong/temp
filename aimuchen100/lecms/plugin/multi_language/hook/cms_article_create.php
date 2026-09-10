<?php
/**
 * 文章创建后自动触发多语言翻译
 * Hook 点：admin/control/content_control.class.php 的 add_post() 中 $id 赋值后
 * 或 lecms/model/cms_content_model.class.php 的 xadd() 方法中
 *
 * 逻辑：获取站点启用的非默认语言列表，为每种语言创建翻译记录（pending）
 * 实际翻译由 Cron 任务执行，避免文章创建时阻塞响应
 */

// 需要站点上下文
$site_id = (int)(defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0);
if (empty($site_id)) {
    return;
}

// 需要文章 ID（Hook 执行时文章已入库）
$article_id = 0;
if (isset($id) && $id > 0) {
    $article_id = (int)$id;
}
if (empty($article_id)) {
    return;
}

// 加载语言配置模型（插件模型，不在核心模型搜索路径中）

// 获取站点启用的非默认语言
$lang_model = core::model('language_config');
try {
    $enabled_langs = $lang_model->get_enabled($site_id);
    $default_lang = $lang_model->get_default($site_id);
} catch (Exception $e) {
    return;
}

if (empty($enabled_langs)) {
    return;
}

$trans_model = core::model('article_translation');

foreach ($enabled_langs as $lang) {
    // 跳过默认语言
    if ($lang['language'] == $default_lang) {
        continue;
    }

    // 幂等创建（已存在则跳过）
    $existing = $trans_model->get_by_source_lang($article_id, $lang['language']);
    if (!$existing) {
        $trans_model->insert(array(
            'source_id' => $article_id,
            'language' => $lang['language'],
            'translator_status' => 0, // pending
        ));
    }
}
