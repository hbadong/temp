<?php
/**
 * Cron 定时翻译任务
 * 扫描 le_article_translation 中 translator_status = 0 的记录，
 * 批量调用 AI 翻译引擎执行翻译
 *
 * 通过 article-strategy 插件的 cms_cron Hook 触发，
 * 或独立在 LECMS Cron 系统中注册
 */

$site_id = defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0;
if (empty($site_id)) {
    return;
}

// 加载翻译记录模型（插件模型，不在核心模型搜索路径中）

$queue = new translation_queue($site_id, $this->db);
$pending = $queue->get_pending(50); // 每次最多处理50篇

if (empty($pending)) {
    return;
}

foreach ($pending as $task) {
    // 标记为 translating（防止重复执行）
    $trans_model = core::model('article_translation');
    $trans_model->update_status($task['id'], 1);

    $result = $queue->execute($task['id']);

    // 失败已在 engine 中回滚为 pending，此处无需额外处理
}
