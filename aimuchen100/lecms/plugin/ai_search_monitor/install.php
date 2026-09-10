<?php
/**
 * AI搜索收录检测插件安装脚本
 */

defined('ROOT_PATH') || exit;
$tablepre = $_ENV['_config']['db']['master']['tablepre'];

$sql = "CREATE TABLE IF NOT EXISTS `{$tablepre}ai_search_log` (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT '日志ID',
    site_id INT NOT NULL COMMENT '站点ID',
    platform VARCHAR(50) NOT NULL COMMENT '平台：baidu_wenxin/doubao/wechat_ai',
    keyword VARCHAR(200) NOT NULL COMMENT '品牌关键词',
    keyword_variant VARCHAR(200) COMMENT '匹配到的词变体',
    found TINYINT DEFAULT 0 COMMENT '是否在AI回答中展现',
    position INT DEFAULT 0 COMMENT '在回答中的位置（0=未找到）',
    reference_url VARCHAR(500) COMMENT '引用来源URL',
    response_snippet TEXT COMMENT 'AI回答片段',
    confidence TINYINT DEFAULT 0 COMMENT '置信度（0-100）',
    checked_at INT UNSIGNED DEFAULT 0 COMMENT '检查时间',
    created_at INT UNSIGNED DEFAULT 0 COMMENT '创建时间',
    INDEX idx_site_platform (site_id, platform),
    INDEX idx_keyword (keyword),
    INDEX idx_checked_at (checked_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='AI搜索监测日志表'";

$this->db->query($sql);
