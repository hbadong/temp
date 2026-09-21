<?php
defined('ROOT_PATH') or exit;
/**
 * 同步主站 API 插件安装脚本
 * 1. 创建 le_cms_api_token Token 表（Token 鉴权 + 限频计数器）
 * 2. 依赖检查：le_site_manager 站点表必须存在（site_id 来源于站点系统）
 */

$tablepre = $_ENV["_config"]["db"]["master"]["tablepre"];

// 1. Token 表
$sql = "CREATE TABLE IF NOT EXISTS `{$tablepre}cms_api_token` (
    `id` INT UNSIGNED PRIMARY KEY AUTO_INCREMENT COMMENT 'ID',
    `token` VARCHAR(64) NOT NULL COMMENT 'API Token',
    `remark` VARCHAR(255) DEFAULT '' COMMENT '备注（标识同步客户端）',
    `enabled` TINYINT DEFAULT 1 COMMENT '状态: 1启用 0停用',
    `last_request_at` INT UNSIGNED DEFAULT 0 COMMENT '最近请求时间戳',
    `request_count` INT UNSIGNED DEFAULT 0 COMMENT '最近一分钟窗口内请求计数',
    `created_at` DATETIME DEFAULT NULL COMMENT '创建时间',
    UNIQUE KEY `uk_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='同步主站 API Token 表'";

if(!$this->db->query($sql)) {
    throw new Exception('master_api 插件 API Token 表创建失败');
}

// 2. 依赖检查：site_manager 站点表
$sm_table = $tablepre . 'site_manager';
$chk = $this->db->fetch_first("SELECT sid FROM `{$sm_table}` LIMIT 1");
if(!$chk) {
    throw new Exception('master_api 插件依赖站点表 site_manager，请先启用 site_manager 插件');
}

return true;