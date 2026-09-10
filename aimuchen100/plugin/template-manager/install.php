<?php
/**
 * Template Manager Plugin Install
 * Creates 4 tables: pre_cms_template, pre_cms_block_config,
 * pre_cms_template_file, pre_cms_template_audit_log
 */

if (!defined('IN_ADMIN')) {
    exit('Access Denied');
}

// LECMS 无全局数据库函数；install.php 由 admin/control/plugin_control.class.php::install()
// 方法内 include 执行，$this 可用，$this->db 经 control::__get 注入。
$tablepre = $_ENV['_config']['db']['master']['tablepre'];
$db = $this->db;

// Table 1: 主题定义
$sql1 = "CREATE TABLE IF NOT EXISTS `{$tablepre}cms_template` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(64) NOT NULL DEFAULT '' COMMENT '主题标识: green-tech/dark-esports/cartoon-cute/minimal-white/retro-pixel',
    `title` varchar(128) NOT NULL DEFAULT '',
    `description` text NOT NULL,
    `version` varchar(16) NOT NULL DEFAULT '1.0.0',
    `is_default` tinyint(1) NOT NULL DEFAULT '0',
    `enabled` tinyint(1) NOT NULL DEFAULT '1',
    `created_at` int(10) unsigned NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_name` (`name`),
    KEY `idx_enabled` (`enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='主题定义 -- pre_cms_template'";

// Table 2: Block 配置
$sql2 = "CREATE TABLE IF NOT EXISTS `{$tablepre}cms_block_config` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `site_id` int(10) unsigned NOT NULL DEFAULT '0',
    `block_name` varchar(64) NOT NULL DEFAULT '',
    `target` varchar(255) NOT NULL DEFAULT '',
    `params` text NOT NULL,
    `enabled` tinyint(1) NOT NULL DEFAULT '1',
    `updated_at` int(10) unsigned NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_site_block_target` (`site_id`, `block_name`(64), `target`(64)),
    KEY `idx_site` (`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Block 配置 -- pre_cms_block_config'";

// Table 3: 模板文件（版本化）
$sql3 = "CREATE TABLE IF NOT EXISTS `{$tablepre}cms_template_file` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `template_id` int(10) unsigned NOT NULL DEFAULT '0',
    `path` varchar(255) NOT NULL DEFAULT '' COMMENT '相对主题目录路径，如 index.htm',
    `version_no` int(10) unsigned NOT NULL DEFAULT '1',
    `content` mediumtext NOT NULL,
    `sha256` char(64) NOT NULL DEFAULT '',
    `file_size` int(10) unsigned NOT NULL DEFAULT '0',
    `is_current` tinyint(1) NOT NULL DEFAULT '0',
    `created_at` int(10) unsigned NOT NULL DEFAULT '0',
    `created_by` int(10) unsigned NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`),
    KEY `idx_template_path` (`template_id`, `path`(64)),
    KEY `idx_current` (`template_id`, `path`(64), `is_current`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='模板文件版本化存储 -- pre_cms_template_file'";

// Table 4: 审计日志
$sql4 = "CREATE TABLE IF NOT EXISTS `{$tablepre}cms_template_audit_log` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `operator_uid` int(10) unsigned NOT NULL DEFAULT '0',
    `template_id` int(10) unsigned DEFAULT NULL,
    `path` varchar(255) NOT NULL DEFAULT '',
    `action` varchar(32) NOT NULL DEFAULT '' COMMENT 'edit/rollback/upload/delete',
    `old_version` int(10) unsigned DEFAULT NULL,
    `new_version` int(10) unsigned DEFAULT NULL,
    `ip` varchar(45) NOT NULL DEFAULT '',
    `user_agent` varchar(255) NOT NULL DEFAULT '',
    `created_at` int(10) unsigned NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`),
    KEY `idx_template` (`template_id`, `created_at`),
    KEY `idx_operator` (`operator_uid`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='模板编辑审计日志 -- pre_cms_template_audit_log'";

$db->query($sql1);
$db->query($sql2);
$db->query($sql3);
$db->query($sql4);

return true;
