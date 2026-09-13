<?php
/**
 * Spider Pool Plugin Install
 * Creates 1 table: pre_spider_pool
 *
 * 设计要点：
 * - site_id 多站点隔离（与 spider-mode 保持一致）
 * - status 默认 1（启用），避免新建行被默认过滤逻辑隐藏
 * - sort 字段支持后台自定义排序输出
 * - dateline int(10) unsigned 时间戳，避免 datetime 索引代价
 * - 无默认 INSERT：spider_pool 表允许空（按需添加域名）
 */

defined('ROOT_PATH') || exit;

// LECMS 无全局 db() 函数；install.php 由 admin/control/plugin_control.class.php::install()
// 方法内 include 执行，$this 可用，$this->db 经 control::__get 注入。
$tablepre = $_ENV['_config']['db']['master']['tablepre'];
$db = $this->db;

// Table: Spider Pool Domains
$sql = "CREATE TABLE IF NOT EXISTS `{$tablepre}spider_pool` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `site_id` int(10) unsigned NOT NULL DEFAULT '0',
    `domain` varchar(255) NOT NULL DEFAULT '',
    `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1=启用 0=停用',
    `sort` int(10) unsigned NOT NULL DEFAULT '0',
    `dateline` int(10) unsigned NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`),
    KEY `idx_site` (`site_id`),
    KEY `idx_status` (`status`),
    KEY `idx_sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='蜘蛛池域名表 -- pre_spider_pool'";

$db->query($sql);

// 域名去重唯一键（(site_id, domain)）：先清理历史重复行再建索引，避免 ALTER 失败
try {
    $db->query("DELETE t1 FROM `{$tablepre}spider_pool` t1 INNER JOIN `{$tablepre}spider_pool` t2 ON t1.site_id = t2.site_id AND t1.domain = t2.domain AND t1.id > t2.id");
    $db->query("ALTER TABLE `{$tablepre}spider_pool` ADD UNIQUE KEY `uk_site_domain` (`site_id`, `domain`)");
} catch (Throwable $e) {
    // 索引已存在或并发场景下忽略
}


