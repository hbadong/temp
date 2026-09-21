<?php
defined('ROOT_PATH') or exit;
/**
 * 站群数据同步客户端 安装脚本
 * 1. 创建 le_cms_sync_log 同步日志表
 * 2. 依赖检查：主站 site_manager、目标表同步列（game main_id/source/content_hash/is_ai_rewritten、article main_id/is_ai_rewritten、category main_id/is_ai_rewritten）必须存在
 */

$tablepre = $_ENV["_config"]["db"]["master"]["tablepre"];

// 1. 同步日志表
$sql = "CREATE TABLE IF NOT EXISTS `{$tablepre}cms_sync_log` (
    `id` INT UNSIGNED PRIMARY KEY AUTO_INCREMENT COMMENT 'ID',
    `site_id` INT UNSIGNED DEFAULT 0 COMMENT '目标站点ID',
    `sync_type` VARCHAR(20) NOT NULL COMMENT '同步类型: games/articles/categories/tags',
    `status` TINYINT DEFAULT 0 COMMENT '结果: 1成功 0失败',
    `message` VARCHAR(500) DEFAULT '' COMMENT '结果描述',
    `item_count` INT UNSIGNED DEFAULT 0 COMMENT '本次处理条数',
    `created_at` DATETIME DEFAULT NULL COMMENT '执行时间',
    KEY `idx_type` (`sync_type`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='站群数据同步日志'";

if(!$this->db->query($sql)) {
    throw new Exception('sync_client 插件同步日志表创建失败');
}

// 2. 依赖检查：site_manager 站点表
$sm_table = $tablepre . 'site_manager';
$chk = $this->db->fetch_first("SELECT sid FROM `{$sm_table}` LIMIT 1");
if(!$chk) {
    throw new Exception('sync_client 插件依赖站点表 site_manager，请先启用 site_manager 插件');
}

// 3. 目标表同步列检查（T1/T2 已迁移，缺列则报错提示先升级 game_center / ai_content_factory）
$need = array(
    'cms_game' => array('main_id', 'source', 'content_hash', 'is_ai_rewritten'),
    'cms_article' => array('main_id', 'is_ai_rewritten'),
    'cms_game_category' => array('main_id', 'is_ai_rewritten'),
);
foreach($need as $tbl => $cols) {
    foreach($cols as $col) {
        $c = $this->db->fetch_first("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE table_schema=DATABASE() AND table_name='" . addslashes($tablepre . $tbl) . "' AND column_name='" . addslashes($col) . "' LIMIT 1");
        if(!$c) {
            throw new Exception("sync_client 插件依赖 {$tbl}.{$col} 列，请先升级 game_center / ai_content_factory 插件");
        }
    }
}

return true;
