<?php
defined('ROOT_PATH') || exit;
$tablepre = $_ENV['_config']['db']['master']['tablepre'];

$sql1 = "CREATE TABLE IF NOT EXISTS `{$tablepre}ai_config` (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT '配置ID',
    site_id INT NOT NULL DEFAULT 0 COMMENT '站点ID(0=全局配置)',
    api_base_url VARCHAR(500) DEFAULT 'https://api.deepseek.com/v1' COMMENT 'API Base URL',
    api_key VARCHAR(500) NOT NULL DEFAULT '' COMMENT 'API密钥',
    model VARCHAR(100) DEFAULT 'deepseek-chat' COMMENT '模型名称',
    max_tokens INT DEFAULT 2048 COMMENT '最大生成token数',
    temperature DECIMAL(2,1) DEFAULT 0.7 COMMENT '温度参数',
    timeout INT DEFAULT 60 COMMENT '请求超时(秒)',
    created_at INT UNSIGNED DEFAULT 0 COMMENT '创建时间',
    updated_at INT UNSIGNED DEFAULT 0 COMMENT '更新时间',
    UNIQUE KEY uk_site (site_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='AI API配置表'";

$this->db->query($sql1);

// 创建 AI 任务表
$sql2 = "CREATE TABLE IF NOT EXISTS `{$tablepre}ai_task` (
    `id` INT PRIMARY KEY AUTO_INCREMENT COMMENT '任务ID',
    `site_id` INT NOT NULL COMMENT '站点ID',
    `category_id` INT DEFAULT 0 COMMENT '分类ID',
    `prompt_template` TEXT COMMENT '提示词模板',
    `batch_size` INT DEFAULT 50 COMMENT '每批数量',
    `total` INT DEFAULT 0 COMMENT '总数量',
    `success` INT DEFAULT 0 COMMENT '成功数',
    `fail` INT DEFAULT 0 COMMENT '失败数',
    `status` TINYINT DEFAULT 0 COMMENT '0=待处理 1=处理中 2=完成 3=失败',
    `created_at` INT UNSIGNED DEFAULT 0 COMMENT '创建时间',
    `updated_at` INT UNSIGNED DEFAULT 0 COMMENT '更新时间',
    INDEX `idx_site_status` (`site_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='AI内容生成任务表'";

$this->db->query($sql2);
