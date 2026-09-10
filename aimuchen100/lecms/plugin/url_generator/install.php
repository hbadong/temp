<?php
defined('ROOT_PATH') || exit;
/**
 * URL生成引擎插件安装脚本
 */

$tablepre = $_ENV["_config"]["db"]["master"]["tablepre"];

// 创建 le_cms_url_map 表
$sql = "CREATE TABLE IF NOT EXISTS `{$tablepre}cms_url_map` (
    `id` BIGINT PRIMARY KEY AUTO_INCREMENT COMMENT 'URL ID',
    `site_id` INT NOT NULL COMMENT '站点ID',
    `url` VARCHAR(500) NOT NULL COMMENT '完整URL路径',
    `url_hash` CHAR(64) NOT NULL COMMENT 'SHA256哈希(小写)',
    `type` TINYINT NOT NULL COMMENT 'URL类型: 1-8',
    `control` VARCHAR(50) NOT NULL COMMENT '控制器名',
    `action` VARCHAR(50) NOT NULL COMMENT '操作方法',
    `params` TEXT COMMENT 'JSON格式路由参数',
    `score` TINYINT DEFAULT 80 COMMENT 'SEO质量评分(70-90)',
    `status` TINYINT DEFAULT 1 COMMENT '状态: 1待生成 2已使用 3已失效',
    `content_id` INT DEFAULT 0 COMMENT '关联的内容ID',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    UNIQUE KEY `uk_site_hash` (`site_id`, `url_hash`),
    INDEX `idx_status` (`site_id`, `status`),
    INDEX `idx_score` (`score`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='URL映射表'";

$ret1 = $this->db->query($sql);

if ($ret1) {
    // 创建 le_cms_game 表
    $sql2 = "CREATE TABLE IF NOT EXISTS `{$tablepre}cms_game` (
        `id` INT PRIMARY KEY AUTO_INCREMENT COMMENT '游戏ID',
        `site_id` INT NOT NULL COMMENT '站点ID',
        `name` VARCHAR(200) NOT NULL COMMENT '游戏名称',
        `platform` VARCHAR(50) DEFAULT '' COMMENT '平台(pc/mobile/console)',
        `category_id` INT DEFAULT 0 COMMENT '分类ID',
        `description` TEXT COMMENT '游戏描述',
        `cover` VARCHAR(500) DEFAULT '' COMMENT '封面图URL',
        `tags` VARCHAR(500) DEFAULT '' COMMENT '标签(逗号分隔)',
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
        INDEX `idx_site` (`site_id`),
        INDEX `idx_category` (`site_id`, `category_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='游戏元数据表'";
    $this->db->query($sql2);
}

