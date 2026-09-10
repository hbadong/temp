<?php
/**
 * CPS Integration 插件安装脚本
 * 创建 3 张 CPS 相关表
 */

defined('ROOT_PATH') || exit;
$tablepre = $_ENV['_config']['db']['master']['tablepre'];

// 1. cms_cps_config — CPS 推广链接
$sql1 = "CREATE TABLE IF NOT EXISTS `{$tablepre}cms_cps_config` (
    `id` INT UNSIGNED PRIMARY KEY AUTO_INCREMENT COMMENT '配置ID',
    `site_id` INT UNSIGNED NOT NULL COMMENT '站点ID',
    `game_id` INT UNSIGNED NOT NULL COMMENT '游戏ID',
    `name` VARCHAR(128) COMMENT '链接名称',
    `url` VARCHAR(1024) NOT NULL COMMENT '主链接',
    `backup_url` VARCHAR(1024) COMMENT '备用链接',
    `weight` INT UNSIGNED NOT NULL DEFAULT 50 COMMENT '权重 0-100',
    `enabled` TINYINT(1) DEFAULT 1 COMMENT '是否启用',
    `click_count` INT UNSIGNED DEFAULT 0 COMMENT '总点击次数',
    `unique_ip_count` INT UNSIGNED DEFAULT 0 COMMENT '唯一点击次数',
    `created_at` INT UNSIGNED COMMENT '创建时间戳',
    `updated_at` INT UNSIGNED COMMENT '更新时间戳',
    KEY `idx_site_game` (`site_id`, `game_id`),
    KEY `idx_enabled_weight` (`enabled`, `weight` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='CPS推广链接配置表'";

$ret1 = $this->db->query($sql1);

// 2. cms_cps_click_log — 点击日志
$sql2 = "CREATE TABLE IF NOT EXISTS `{$tablepre}cms_cps_click_log` (
    `id` BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT COMMENT '日志ID',
    `site_id` INT UNSIGNED NOT NULL COMMENT '站点ID',
    `game_id` INT UNSIGNED NOT NULL COMMENT '游戏ID',
    `cps_id` INT UNSIGNED NOT NULL COMMENT 'CPS配置ID',
    `ip` VARBINARY(16) COMMENT 'IP二进制(IPv4/IPv6)',
    `ip_text` VARCHAR(45) COMMENT 'IP文本表示',
    `user_agent` VARCHAR(512) COMMENT 'User Agent',
    `referer` VARCHAR(512) COMMENT 'Referer',
    `is_unique` TINYINT(1) DEFAULT 0 COMMENT '是否24h内首次点击',
    `is_degraded` TINYINT(1) DEFAULT 0 COMMENT '是否降级到备用链接',
    `click_at` INT UNSIGNED COMMENT '点击时间戳',
    KEY `idx_site_time` (`site_id`, `click_at`),
    KEY `idx_cps_time` (`cps_id`, `click_at`),
    KEY `idx_unique` (`cps_id`, `ip`, `click_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='CPS点击日志表'";

$ret2 = $this->db->query($sql2);

// 3. cms_plugin_site — 插件站点绑定
$sql3 = "CREATE TABLE IF NOT EXISTS `{$tablepre}cms_plugin_site` (
    `id` INT UNSIGNED PRIMARY KEY AUTO_INCREMENT COMMENT '绑定ID',
    `site_id` INT UNSIGNED NOT NULL COMMENT '站点ID',
    `plugin_name` VARCHAR(64) NOT NULL COMMENT '插件名称',
    `config` TEXT COMMENT 'JSON格式配置',
    `enabled` TINYINT(1) DEFAULT 1 COMMENT '是否启用',
    UNIQUE KEY `uk_site_plugin` (`site_id`, `plugin_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='插件站点绑定表'";

$this->db->query($sql3);
