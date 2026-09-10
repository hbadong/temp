<?php
/**
 * 企业 SEO 插件安装脚本
 */

defined('ROOT_PATH') || exit;
$tablepre = $_ENV['_config']['db']['master']['tablepre'];

// 创建企业站点表
$sql1 = "CREATE TABLE IF NOT EXISTS `{$tablepre}enterprise_site` (
    `id` INT PRIMARY KEY AUTO_INCREMENT COMMENT '企业站点ID',
    `site_id` INT NOT NULL COMMENT '所属站点ID（cms_site.sid）',
    `enterprise_name` VARCHAR(200) NOT NULL COMMENT '企业名称',
    `industry` VARCHAR(100) COMMENT '所属行业',
    `address` VARCHAR(500) COMMENT '企业地址',
    `phone` VARCHAR(50) COMMENT '联系电话',
    `email` VARCHAR(100) COMMENT '邮箱',
    `description` TEXT COMMENT '企业简介',
    `products` TEXT COMMENT '产品列表（JSON格式）',
    `cases` TEXT COMMENT '案例列表（JSON格式）',
    `news` TEXT COMMENT '新闻动态（JSON格式）',
    `template_id` INT DEFAULT 0 COMMENT '使用的模板ID',
    `status` TINYINT DEFAULT 0 COMMENT '状态：0=草稿 1=已发布 2=已下线',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    INDEX `idx_site` (`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='企业站点信息表'";

$ret = $this->db->query($sql1);

if ($ret) {
    // 创建外链记录表
    $sql2 = "CREATE TABLE IF NOT EXISTS `{$tablepre}external_link` (
        `id` INT PRIMARY KEY AUTO_INCREMENT COMMENT '外链记录ID',
        `enterprise_id` INT NOT NULL COMMENT '企业站点ID',
        `platform` VARCHAR(50) NOT NULL COMMENT '平台名称：aiqicha/qcc/tianyancha',
        `platform_url` VARCHAR(500) COMMENT '平台上的企业URL',
        `status` TINYINT DEFAULT 0 COMMENT '状态：0=待提交 1=已提交 2=已收录 3=失败',
        `submit_response` TEXT COMMENT '平台返回信息',
        `last_check_at` DATETIME COMMENT '最后检查收录时间',
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
        `check_count` INT DEFAULT 0 COMMENT '检查次数',
        INDEX `idx_enterprise` (`enterprise_id`),
        INDEX `idx_platform` (`platform`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='外链提交记录表'";

    $ret = $this->db->query($sql2);

    // 为已有安装添加 check_count 列
    $check_col = $this->db->fetch_first("SHOW COLUMNS FROM `{$tablepre}external_link` LIKE 'check_count'");
    if (!$check_col) {
        $this->db->query("ALTER TABLE `{$tablepre}external_link` ADD COLUMN `check_count` INT DEFAULT 0 COMMENT '检查次数' AFTER `status`");
    }
}

