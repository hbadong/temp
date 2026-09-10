<?php
defined('ROOT_PATH') || exit;
/**
 * 远程热更新补丁系统插件安装脚本
 */

$tablepre = $_ENV['_config']['db']['master']['tablepre'];

// 创建补丁版本记录表
$sql1 = "CREATE TABLE IF NOT EXISTS `{$tablepre}patch_version` (
    `id` INT PRIMARY KEY AUTO_INCREMENT COMMENT '版本ID',
    `site_id` INT NOT NULL COMMENT '站点ID',
    `version` VARCHAR(50) NOT NULL COMMENT '补丁版本号',
    `name` VARCHAR(200) COMMENT '补丁名称',
    `changelog` TEXT COMMENT '更新说明',
    `files_count` INT DEFAULT 0 COMMENT '修改文件数',
    `status` TINYINT DEFAULT 0 COMMENT '0=已应用 1=已回滚',
    `backup_path` VARCHAR(500) COMMENT '备份目录路径',
    `applied_at` DATETIME COMMENT '应用时间',
    `rolled_back_at` DATETIME COMMENT '回滚时间',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    INDEX `idx_site_version` (`site_id`, `version`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='补丁版本记录表'";

$ret = $this->db->query($sql1);

if ($ret) {
    // 创建补丁操作日志表
    $sql2 = "CREATE TABLE IF NOT EXISTS `{$tablepre}patch_log` (
        `id` INT PRIMARY KEY AUTO_INCREMENT COMMENT '日志ID',
        `site_id` INT NOT NULL COMMENT '站点ID',
        `version_id` INT COMMENT '关联版本ID',
        `action` VARCHAR(50) NOT NULL COMMENT '操作：check/apply/rollback/upload',
        `status` TINYINT DEFAULT 0 COMMENT '0=成功 1=失败',
        `error_message` TEXT COMMENT '错误信息',
        `detail` JSON COMMENT '操作详情（文件列表、耗时等）',
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
        INDEX `idx_site_action` (`site_id`, `action`, `created_at`),
        INDEX `idx_version` (`version_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='补丁操作日志表'";

    $ret = $this->db->query($sql2);
}

