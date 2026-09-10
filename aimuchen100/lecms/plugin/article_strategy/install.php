<?php
/**
 * Article Strategy 插件安装脚本
 * 创建文章策略相关数据表
 */

defined('ROOT_PATH') || exit;
$tablepre = $_ENV['_config']['db']['master']['tablepre'];

// 创建文章标题规则表
$sql1 = "CREATE TABLE IF NOT EXISTS `{$tablepre}article_title_rule` (
    sid INT PRIMARY KEY AUTO_INCREMENT COMMENT '规则ID',
    site_id INT NOT NULL COMMENT '站点ID',
    name VARCHAR(100) NOT NULL COMMENT '规则名称',
    templates TEXT COMMENT '标题模板数组(JSON)',
    regex_rules TEXT COMMENT '正则替换规则数组(JSON)',
    is_active TINYINT DEFAULT 1 COMMENT '状态: 1启用 0禁用',
    created_at INT UNSIGNED DEFAULT 0 COMMENT '创建时间',
    updated_at INT UNSIGNED DEFAULT 0 COMMENT '更新时间',
    INDEX idx_site (site_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='文章标题规则表'";

$this->db->query($sql1);

// 创建文章导入日志表
$sql2 = "CREATE TABLE IF NOT EXISTS `{$tablepre}article_import_log` (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT '日志ID',
    site_id INT NOT NULL COMMENT '站点ID',
    filename VARCHAR(255) COMMENT '导入文件名',
    total_rows INT DEFAULT 0 COMMENT '总行数',
    imported INT DEFAULT 0 COMMENT '成功导入数',
    skipped INT DEFAULT 0 COMMENT '跳过行数',
    status TINYINT DEFAULT 0 COMMENT '状态: 0处理中 1完成 2失败',
    report TEXT COMMENT '导入报告(JSON)',
    created_at INT UNSIGNED DEFAULT 0 COMMENT '创建时间',
    INDEX idx_site (site_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='文章导入日志表'";

$this->db->query($sql2);

// 创建文章更新任务表
$sql3 = "CREATE TABLE IF NOT EXISTS `{$tablepre}article_update_task` (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT '任务ID',
    site_id INT NOT NULL COMMENT '站点ID',
    category_id INT DEFAULT 0 COMMENT '分类ID',
    frequency VARCHAR(20) DEFAULT 'weekly' COMMENT '更新频率: monthly/weekly/daily',
    next_run INT UNSIGNED DEFAULT 0 COMMENT '下次执行时间戳',
    status TINYINT DEFAULT 1 COMMENT '状态: 1启用 0暂停',
    success_count INT DEFAULT 0 COMMENT '上次成功数',
    fail_count INT DEFAULT 0 COMMENT '上次失败数',
    created_at INT UNSIGNED DEFAULT 0 COMMENT '创建时间',
    updated_at INT UNSIGNED DEFAULT 0 COMMENT '更新时间',
    INDEX idx_next_run (next_run, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='文章更新任务表'";

$this->db->query($sql3);

// 创建游戏分类表
$sql4 = "CREATE TABLE IF NOT EXISTS `{$tablepre}article_category` (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT '分类ID',
    site_id INT NOT NULL COMMENT '站点ID',
    name VARCHAR(100) NOT NULL COMMENT '分类名称',
    slug VARCHAR(100) DEFAULT '' COMMENT 'URL别名',
    parent_id INT DEFAULT 0 COMMENT '父分类ID',
    sort INT DEFAULT 0 COMMENT '排序',
    is_active TINYINT DEFAULT 1 COMMENT '状态: 1启用 0禁用',
    created_at INT UNSIGNED DEFAULT 0 COMMENT '创建时间',
    INDEX idx_site (site_id),
    UNIQUE KEY uk_site_name (site_id, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='文章分类表'";

$this->db->query($sql4);

