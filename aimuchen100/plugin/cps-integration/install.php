<?php
/**
 * CPS Integration Plugin Install
 * Creates 3 tables: pre_cms_cps_config, pre_cms_cps_click_log, pre_cms_plugin_site
 */
if (!defined('IN_ADMIN')) {
    exit('Access Denied');
}

$tablepre = $_ENV['_config']['db']['master']['tablepre'];
$db = $this->db;

// Table 1: CPS 推广链接配置
$db->query("CREATE TABLE IF NOT EXISTS `{$tablepre}cps_config` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `site_id` int(10) unsigned NOT NULL DEFAULT '0',
    `game_id` int(10) unsigned NOT NULL DEFAULT '0',
    `name` varchar(128) NOT NULL DEFAULT '',
    `url` varchar(1024) NOT NULL DEFAULT '' COMMENT '主推广链接',
    `backup_url` varchar(1024) NOT NULL DEFAULT '' COMMENT '备用直链',
    `weight` int(10) unsigned NOT NULL DEFAULT '50' COMMENT '0-100 权重',
    `enabled` tinyint(1) NOT NULL DEFAULT '1',
    `click_count` int(10) unsigned NOT NULL DEFAULT '0',
    `unique_ip_count` int(10) unsigned NOT NULL DEFAULT '0',
    `created_at` int(10) unsigned NOT NULL DEFAULT '0',
    `updated_at` int(10) unsigned NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`),
    KEY `idx_site_game` (`site_id`, `game_id`),
    KEY `idx_enabled_weight` (`enabled`, `weight` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='CPS 推广链接 -- pre_cms_cps_config'");

// Table 2: 点击日志
$db->query("CREATE TABLE IF NOT EXISTS `{$tablepre}cps_click_log` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `site_id` int(10) unsigned NOT NULL DEFAULT '0',
    `game_id` int(10) unsigned NOT NULL DEFAULT '0',
    `cps_id` int(10) unsigned NOT NULL DEFAULT '0',
    `ip` varbinary(16) DEFAULT NULL COMMENT 'IPv4/IPv6 二进制',
    `ip_text` varchar(45) NOT NULL DEFAULT '' COMMENT 'IP 文本（降级兼容）',
    `user_agent` varchar(512) NOT NULL DEFAULT '',
    `referer` varchar(512) NOT NULL DEFAULT '',
    `is_unique` tinyint(1) NOT NULL DEFAULT '0' COMMENT '24h 内首次点击',
    `is_degraded` tinyint(1) NOT NULL DEFAULT '0' COMMENT '降级到备用链接',
    `click_at` int(10) unsigned NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`),
    KEY `idx_site_time` (`site_id`, `click_at`),
    KEY `idx_cps_time` (`cps_id`, `click_at`),
    KEY `idx_unique` (`cps_id`, `ip_text`(64), `click_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='CPS 点击日志 -- pre_cms_cps_click_log'");

// Table 3: 插件站点绑定
$db->query("CREATE TABLE IF NOT EXISTS `{$tablepre}plugin_site` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `site_id` int(10) unsigned NOT NULL DEFAULT '0',
    `plugin_name` varchar(64) NOT NULL DEFAULT '',
    `config` text NOT NULL COMMENT 'JSON 配置',
    `enabled` tinyint(1) NOT NULL DEFAULT '1',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_site_plugin` (`site_id`, `plugin_name`),
    KEY `idx_site` (`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='插件站点绑定 -- pre_cms_plugin_site'");

return true;
