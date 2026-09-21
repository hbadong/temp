<?php defined('ROOT_PATH') or exit;
/**
 * 手工编辑文章清零 AI 改写标记（内联于 admin_content_control::edit() 保存前）
 * 设计决策：admin 手工编辑后视为人工修正，is_ai_rewritten 清零，
 * 使后续 sync 可重新拉取（配合 T5 sync_client 的 is_ai_rewritten 保护语义）。
 */

$art_edit_id = (int)R('id', 'P');
if($art_edit_id > 0) {
    $tablepre = $_ENV['_config']['db']['master']['tablepre'];
    $this->db->exec("UPDATE `{$tablepre}cms_article` SET is_ai_rewritten=0 WHERE id={$art_edit_id}");
}
