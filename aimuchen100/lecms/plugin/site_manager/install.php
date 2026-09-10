<?php
defined('ROOT_PATH') || exit;

// 创建多站点管理表
$tablepre = $_ENV['_config']['db']['master']['tablepre'];
$sql = "CREATE TABLE IF NOT EXISTS `{$tablepre}site_manager` (
    `sid` INT PRIMARY KEY AUTO_INCREMENT COMMENT '站点ID',
    `site_name` VARCHAR(100) NOT NULL COMMENT '站点名称',
    `domain` VARCHAR(100) NOT NULL COMMENT '域名，支持 *.xxx.com 泛域名',
    `theme` VARCHAR(50) NOT NULL DEFAULT 'default' COMMENT '当前主题',
    `status` TINYINT DEFAULT 1 COMMENT '状态: 1启用 0禁用 -1删除',
    `config` TEXT COMMENT '站点级JSON配置(theme_vars, override_templates等)',
    `created_at` INT UNSIGNED DEFAULT 0 COMMENT '创建时间',
    `updated_at` INT UNSIGNED DEFAULT 0 COMMENT '更新时间',
    UNIQUE KEY `uk_domain` (`domain`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='多站点管理表'";

$this->db->query($sql);

// 写入默认站点（当前站点作为默认站点）
$default_domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost'; // PHP 5.4 兼容
$now = $_ENV['_time'];
$this->db->query("INSERT INTO `{$tablepre}site_manager` (`site_name`, `domain`, `theme`, `status`, `created_at`, `updated_at`)
    VALUES ('默认站点', '{$default_domain}', 'default', 1, {$now}, {$now})
    ON DUPLICATE KEY UPDATE `site_name` = '默认站点'");
