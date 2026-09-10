<?php
/**
 * SEO Tools Plugin Install
 * Creates 2 tables: pre_seo_verification, pre_seo_push_log
 *
 * 设计要点：
 * - pre_seo_verification 存站长验证文件元数据（百度 / Google / 360 / Bing / 搜狗 / 头条 / 神马）
 *   - engine 字段标识搜索引擎（baidu/google/sogou/360/bing/toutiao/shenma）
 *   - content + file_path 兼容"文件已写入磁盘"与"仅注册到 CMS"两种部署模式
 * - pre_seo_push_log 记录每次主动推送的 API 调用结果
 *   - status 0=失败/未推送 1=成功（默认 0，写入前先记失败再异步推送）
 *   - response 存 API 返回 JSON 或错误信息（text 字段，承载百度的详细错误描述）
 * - 两表均含 site_id 多站点隔离字段，与 spider-mode / spider-pool 保持一致
 * - 无默认 INSERT：站长验证与推送日志均按需生成
 */

defined('ROOT_PATH') || exit;

// LECMS 无全局 db() 函数；install.php 由 admin/control/plugin_control.class.php::install()
// 方法内 include 执行，$this 可用，$this->db 经 control::__get 注入。
$tablepre = $_ENV['_config']['db']['master']['tablepre'];
$db = $this->db;

// Table 1: SEO 站长验证文件元数据
$sql1 = "CREATE TABLE IF NOT EXISTS `{$tablepre}seo_verification` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `site_id` int(10) unsigned NOT NULL DEFAULT '0',
    `engine` varchar(30) NOT NULL DEFAULT '' COMMENT 'baidu/google/sogou/360/bing/toutiao/shenma',
    `filename` varchar(255) NOT NULL DEFAULT '',
    `content` text NOT NULL,
    `file_path` varchar(500) NOT NULL DEFAULT '',
    `dateline` int(10) unsigned NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`),
    KEY `idx_site` (`site_id`),
    KEY `idx_engine` (`engine`),
    KEY `idx_filename` (`filename`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='SEO 站长验证文件元数据 -- pre_seo_verification'";

// Table 2: SEO URL 推送日志
$sql2 = "CREATE TABLE IF NOT EXISTS `{$tablepre}seo_push_log` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `site_id` int(10) unsigned NOT NULL DEFAULT '0',
    `engine` varchar(30) NOT NULL DEFAULT '' COMMENT 'baidu/google/sogou/360/bing/toutiao/shenma',
    `url` varchar(500) NOT NULL DEFAULT '',
    `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0=失败/未推送 1=成功',
    `response` text NOT NULL COMMENT 'API 返回 JSON 或错误信息',
    `dateline` int(10) unsigned NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`),
    KEY `idx_site` (`site_id`),
    KEY `idx_engine` (`engine`),
    KEY `idx_dateline` (`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='SEO URL 推送日志 -- pre_seo_push_log'";

$db->query($sql1);
$db->query($sql2);


