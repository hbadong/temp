<?php
defined('ROOT_PATH') || exit;
/**
 * 自媒体矩阵同步插件安装脚本
 */

$tablepre = $_ENV['_config']['db']['master']['tablepre'];

// 创建平台账号表
$sql1 = "CREATE TABLE IF NOT EXISTS `{$tablepre}media_account` (
    `id` INT PRIMARY KEY AUTO_INCREMENT COMMENT '账号ID',
    `site_id` INT NOT NULL COMMENT '站点ID',
    `platform` VARCHAR(50) NOT NULL COMMENT '平台：wechat/zhihu/csdn/sohu/bilibili',
    `account_name` VARCHAR(200) COMMENT '账号名称',
    `access_token` TEXT COMMENT 'OAuth Access Token（加密存储）',
    `refresh_token` TEXT COMMENT 'OAuth Refresh Token',
    `expires_at` DATETIME COMMENT 'Token 过期时间',
    `status` TINYINT DEFAULT 1 COMMENT '状态：0=停用 1=启用',
    `last_sync_at` DATETIME COMMENT '最后同步时间',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    INDEX `idx_site_platform` (`site_id`, `platform`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='平台账号配置表'";

$this->db->query($sql1);

if ($this->db->query($sql1)) {
    // 创建同步日志表
    $sql2 = "CREATE TABLE IF NOT EXISTS `{$tablepre}media_sync_log` (
        `id` INT PRIMARY KEY AUTO_INCREMENT COMMENT '日志ID',
        `site_id` INT NOT NULL COMMENT '站点ID',
        `account_id` INT NOT NULL COMMENT '账号ID',
        `article_id` INT NOT NULL COMMENT '文章ID',
        `platform` VARCHAR(50) NOT NULL COMMENT '平台',
        `status` TINYINT DEFAULT 0 COMMENT '状态：0=待同步 1=同步中 2=成功 3=失败',
        `retry_count` TINYINT DEFAULT 0 COMMENT '重试次数',
        `error_message` TEXT COMMENT '错误信息',
        `platform_url` VARCHAR(500) COMMENT '同步后的文章URL',
        `synced_at` DATETIME COMMENT '同步时间',
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
        INDEX `idx_site_platform` (`site_id`, `platform`),
        INDEX `idx_article` (`article_id`),
        INDEX `idx_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='同步日志表'";

    $this->db->query($sql2);
}

