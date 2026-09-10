<?php
/**
 * 多语言支持插件安装脚本
 */

defined('ROOT_PATH') || exit;
$tablepre = $_ENV['_config']['db']['master']['tablepre'];

// 创建语言配置表
$sql1 = "CREATE TABLE IF NOT EXISTS `{$tablepre}language_config` (
    `id` INT PRIMARY KEY AUTO_INCREMENT COMMENT '配置ID',
    `site_id` INT NOT NULL DEFAULT 0 COMMENT '站点ID（0=全局默认）',
    `language` VARCHAR(10) NOT NULL COMMENT '语言代码',
    `language_name` VARCHAR(50) COMMENT '语言显示名称',
    `is_default` TINYINT DEFAULT 0 COMMENT '是否默认语言',
    `is_enabled` TINYINT DEFAULT 1 COMMENT '是否启用',
    `subdomain_enabled` TINYINT DEFAULT 0 COMMENT '是否启用子域名路由',
    `prompt_template` TEXT COMMENT '该语言的翻译prompt模板（NULL=使用全局）',
    `sort` INT DEFAULT 0 COMMENT '排序',
    UNIQUE KEY `uk_site_lang` (`site_id`, `language`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='语言配置表'";

$this->db->query($sql1);

// 创建翻译关联表
    $sql2 = "CREATE TABLE IF NOT EXISTS `{$tablepre}article_translation` (
        `id` INT PRIMARY KEY AUTO_INCREMENT COMMENT '翻译记录ID',
        `source_id` INT NOT NULL COMMENT '原文文章ID（pre_cms_article.id）',
        `language` VARCHAR(10) NOT NULL COMMENT '语言代码（en/ja/ko...）',
        `title` VARCHAR(500) COMMENT '翻译标题',
        `content` MEDIUMTEXT COMMENT '翻译内容（Markdown格式）',
        `translator_status` TINYINT DEFAULT 0 COMMENT '翻译状态：0=待翻译 1=翻译中 2=已翻译 3=已验证',
        `ai_model` VARCHAR(100) COMMENT '使用的AI模型',
        `prompt_template` TEXT COMMENT '使用的prompt模板',
        `verified_by` INT DEFAULT 0 COMMENT '审核人ID（0=系统）',
        `verified_at` DATETIME COMMENT '审核时间',
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
        INDEX `idx_source` (`source_id`),
        INDEX `idx_lang` (`language`),
        UNIQUE KEY `uk_source_lang` (`source_id`, `language`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='文章翻译版本表'";

    $this->db->query($sql2);


