<?php
/**
 * Spider Mode Plugin Install
 * Creates 4 tables: pre_spider_mode, pre_spider_icp_page, pre_access_mode_log, pre_spider_log
 */

defined('ROOT_PATH') || exit;

// LECMS 无全局 db() 函数；install.php 由 admin/control/plugin_control.class.php::install()
// 方法内 include 执行，$this 可用，$this->db 经 control::__get 注入。
$tablepre = $_ENV['_config']['db']['master']['tablepre'];
$db = $this->db;

// Table 1: Spider Mode Configuration
$sql1 = "CREATE TABLE IF NOT EXISTS `{$tablepre}spider_mode` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `site_id` int(10) unsigned NOT NULL DEFAULT '0',
    `mode` varchar(20) NOT NULL DEFAULT 'normal' COMMENT 'normal|spider_only|icp_filing',
    `spider_mode_active` tinyint(1) NOT NULL DEFAULT '0',
    `updated_at` int(10) unsigned NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_site` (`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='蜘蛛模式配置 -- pre_spider_mode'";

// Table 2: ICP Filing Page
$sql2 = "CREATE TABLE IF NOT EXISTS `{$tablepre}spider_icp_page` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `site_id` int(10) unsigned NOT NULL DEFAULT '0',
    `html_content` text NOT NULL,
    `updated_at` int(10) unsigned NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`),
    KEY `idx_site` (`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='ICP备案页内容 -- pre_spider_icp_page'";

// Table 3: Access Mode Log
$sql3 = "CREATE TABLE IF NOT EXISTS `{$tablepre}access_mode_log` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `site_id` int(10) unsigned NOT NULL DEFAULT '0',
    `old_mode` varchar(20) NOT NULL DEFAULT 'normal',
    `new_mode` varchar(20) NOT NULL DEFAULT 'normal',
    `uid` int(10) unsigned NOT NULL DEFAULT '0',
    `ip` varchar(45) NOT NULL DEFAULT '',
    `dateline` int(10) unsigned NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`),
    KEY `idx_site` (`site_id`),
    KEY `idx_dateline` (`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='访问模式切换日志 -- pre_access_mode_log'";

// Table 4: Spider Log
$sql4 = "CREATE TABLE IF NOT EXISTS `{$tablepre}spider_log` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `site_id` int(10) unsigned NOT NULL DEFAULT '0',
    `engine` varchar(30) NOT NULL DEFAULT '',
    `ip` varchar(45) NOT NULL DEFAULT '',
    `user_agent` varchar(255) NOT NULL DEFAULT '',
    `url` varchar(500) NOT NULL DEFAULT '',
    `dateline` int(10) unsigned NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`),
    KEY `idx_site` (`site_id`),
    KEY `idx_engine` (`engine`),
    KEY `idx_dateline` (`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='蜘蛛访问日志 -- pre_spider_log'";

$db->query($sql1);
$db->query($sql2);
$db->query($sql3);
$db->query($sql4);

// 修复 II-2：spider_mode 表若空，AccessMode::get_mode() 返回 null → spider_mode_active 默认 0；
// AccessMode::switch_active() 是 UPDATE，无记录时影响 0 行 → 用户后台启用后下次请求仍 inactive。
// 因此安装时插入默认行 site_id=1, mode='normal', spider_mode_active=0；
// 用 ON DUPLICATE KEY UPDATE 兜底，避免重复安装时 duplicate entry 报错。
$default_site_id = 1;
$dateline = isset($_ENV['_time']) ? $_ENV['_time'] : time();
$sql5 = "INSERT INTO `{$tablepre}spider_mode` (`site_id`, `mode`, `spider_mode_active`, `updated_at`) VALUES ("
    . intval($default_site_id) . ", 'normal', 0, " . intval($dateline) . ") "
    . "ON DUPLICATE KEY UPDATE updated_at = updated_at";
$db->query($sql5);


