<?php
defined('ROOT_PATH') || exit;
/**
 * Template Manager 插件安装脚本
 *
 * 创建 4 张模板相关表：
 *   - cms_template（主题定义）
 *   - cms_block_config（Block 配置）
 *   - cms_template_file（模板文件版本化）
 *   - cms_template_audit_log（审计日志）
 *
 * 使用 {$tablepre} 动态表前缀，支持重复安装（幂等）
 */


// 1. cms_template — 主题定义
$sql1 = "CREATE TABLE IF NOT EXISTS `{$tablepre}cms_template` (
    `id` INT UNSIGNED PRIMARY KEY AUTO_INCREMENT COMMENT '主题ID',
    `name` VARCHAR(64) UNIQUE COMMENT '主题标识 green-tech / dark-esports / ...',
    `title` VARCHAR(128) COMMENT '主题标题',
    `description` TEXT COMMENT '主题描述',
    `version` VARCHAR(16) COMMENT '主题版本',
    `is_default` TINYINT(1) DEFAULT 0 COMMENT '是否默认主题',
    `enabled` TINYINT(1) DEFAULT 1 COMMENT '是否启用',
    `created_at` INT UNSIGNED COMMENT '创建时间戳',
    KEY `idx_enabled` (`enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='主题定义表'";

$ret = $this->db->query($sql1);

if ($ret) {
    // 2. cms_block_config — Block 配置
    $sql2 = "CREATE TABLE IF NOT EXISTS `{$tablepre}cms_block_config` (
        `id` INT UNSIGNED PRIMARY KEY AUTO_INCREMENT COMMENT '配置ID',
        `site_id` INT UNSIGNED NOT NULL COMMENT '站点ID',
        `block_name` VARCHAR(64) NOT NULL COMMENT 'Block名称 game_detail / game_download_box / ...',
        `target` VARCHAR(255) COMMENT '页面/URL pattern',
        `params` TEXT COMMENT 'JSON格式参数',
        `enabled` TINYINT(1) DEFAULT 1 COMMENT '是否启用',
        `updated_at` INT UNSIGNED COMMENT '更新时间戳',
        UNIQUE KEY `uk_site_block_target` (`site_id`, `block_name`, `target`(64))
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Block配置表'";

    $ret = $this->db->query($sql2);
}

if ($ret) {
    // 3. cms_template_file — 模板文件版本化
    $sql3 = "CREATE TABLE IF NOT EXISTS `{$tablepre}cms_template_file` (
        `id` INT UNSIGNED PRIMARY KEY AUTO_INCREMENT COMMENT '文件版本ID',
        `template_id` INT UNSIGNED NOT NULL COMMENT '主题ID',
        `path` VARCHAR(255) NOT NULL COMMENT '相对主题目录路径如 index.htm',
        `version_no` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '版本号',
        `content` MEDIUMTEXT COMMENT '文件内容',
        `sha256` CHAR(64) COMMENT '内容SHA256哈希',
        `file_size` INT UNSIGNED COMMENT '文件大小(字节)',
        `is_current` TINYINT(1) DEFAULT 0 COMMENT '是否为当前版本',
        `created_at` INT UNSIGNED COMMENT '创建时间戳',
        `created_by` INT UNSIGNED COMMENT '创建人ID',
        KEY `idx_template_path` (`template_id`, `path`(64)),
        KEY `idx_current` (`template_id`, `path`(64), `is_current`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='模板文件版本表'";

    $ret = $this->db->query($sql3);
}

if ($ret) {
    // 4. cms_template_audit_log — 审计日志
    $sql4 = "CREATE TABLE IF NOT EXISTS `{$tablepre}cms_template_audit_log` (
        `id` BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT COMMENT '日志ID',
        `operator_uid` INT UNSIGNED NOT NULL COMMENT '操作人ID',
        `template_id` INT UNSIGNED COMMENT '主题ID',
        `path` VARCHAR(255) COMMENT '操作文件路径',
        `action` VARCHAR(32) COMMENT '操作类型 edit / rollback / upload / delete',
        `old_version` INT UNSIGNED COMMENT '旧版本号',
        `new_version` INT UNSIGNED COMMENT '新版本号',
        `ip` VARCHAR(45) COMMENT '操作人IP',
        `user_agent` VARCHAR(255) COMMENT 'User Agent',
        `created_at` INT UNSIGNED COMMENT '操作时间戳',
        KEY `idx_template` (`template_id`, `created_at`),
        KEY `idx_operator` (`operator_uid`, `created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='模板审计日志表'";

    $ret = $this->db->query($sql4);
}

