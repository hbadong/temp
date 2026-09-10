<?php
/**
 * 竞品对标分析插件安装脚本
 */

defined('ROOT_PATH') || exit;
$tablepre = $_ENV['_config']['db']['master']['tablepre'];

// 创建竞品站点表
$sql1 = "CREATE TABLE IF NOT EXISTS `{$tablepre}competitor_site` (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT '竞品站点ID',
    site_id INT NOT NULL COMMENT '所属站点ID',
    url VARCHAR(500) NOT NULL COMMENT '竞品站点URL',
    name VARCHAR(200) COMMENT '站点名称',
    `group` VARCHAR(50) DEFAULT 'direct' COMMENT '分组：direct/indirect/benchmark',
    status TINYINT DEFAULT 1 COMMENT '状态：0=停用 1=启用',
    last_analyzed_at INT UNSIGNED DEFAULT 0 COMMENT '最后分析时间',
    created_at INT UNSIGNED DEFAULT 0 COMMENT '创建时间',
    updated_at INT UNSIGNED DEFAULT 0 COMMENT '更新时间',
    INDEX idx_site (site_id),
    INDEX idx_group (`group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='竞品站点配置表'";

$ret1 = $this->db->query($sql1);

if ($ret1) {
    // 创建分析报告表
    $sql2 = "CREATE TABLE IF NOT EXISTS `{$tablepre}competitor_report` (
        id INT PRIMARY KEY AUTO_INCREMENT COMMENT '报告ID',
        site_id INT NOT NULL COMMENT '所属站点ID',
        competitor_id INT NOT NULL COMMENT '竞品站点ID',
        report_type VARCHAR(20) DEFAULT 'manual' COMMENT '报告类型：manual/weekly',
        domain_authority INT COMMENT '域名权重（0-100）',
        title_data TEXT COMMENT '标题策略数据（JSON）',
        template_data TEXT COMMENT '模板结构数据（JSON）',
        server_info TEXT COMMENT '服务器信息（JSON）',
        ai_suggestions TEXT COMMENT 'AI 优化建议（JSON）',
        analyzed_at INT UNSIGNED DEFAULT 0 COMMENT '分析时间',
        created_at INT UNSIGNED DEFAULT 0 COMMENT '创建时间',
        INDEX idx_site (site_id),
        INDEX idx_competitor (competitor_id),
        INDEX idx_type (report_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='竞品分析报告表'";

    $ret2 = $this->db->query($sql2);
} else {
    $ret2 = false;
}

