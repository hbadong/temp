<?php
defined('ROOT_PATH') or exit;
/**
 * 软件中心插件安装脚本
 * 创建 le_cms_software / le_cms_software_category / le_cms_software_import_log 三表
 * 并为当前站点预置 8 个常见软件分类与软件首页 URL 登记
 */

$tablepre = $_ENV["_config"]["db"]["master"]["tablepre"];

$sql1 = "CREATE TABLE IF NOT EXISTS `{$tablepre}cms_software` (
    `id` INT PRIMARY KEY AUTO_INCREMENT COMMENT '软件ID',
    `site_id` INT NOT NULL DEFAULT 0 COMMENT '站点ID',
    `cat_id` INT NOT NULL DEFAULT 0 COMMENT '软件分类ID',
    `name` VARCHAR(200) NOT NULL COMMENT '软件名称',
    `version` VARCHAR(50) DEFAULT '' COMMENT '版本号',
    `size` VARCHAR(50) DEFAULT '' COMMENT '软件大小',
    `platform` VARCHAR(100) DEFAULT '' COMMENT '系统平台: Windows/macOS/Linux/Android/iOS',
    `download_url` VARCHAR(500) DEFAULT '' COMMENT '下载地址',
    `cover` VARCHAR(500) DEFAULT '' COMMENT '封面图URL',
    `intro` VARCHAR(1000) DEFAULT '' COMMENT '软件简介',
    `content` TEXT COMMENT '详细介绍',
    `tags` VARCHAR(500) DEFAULT '' COMMENT '标签',
    `downloads` INT UNSIGNED DEFAULT 0 COMMENT '下载次数',
    `status` TINYINT DEFAULT 1 COMMENT '状态: 1上架 0下架',
    `dateline` INT UNSIGNED DEFAULT 0 COMMENT '录入时间',
    `updated_at` INT UNSIGNED DEFAULT 0 COMMENT '更新时间',
    KEY `idx_site_cat` (`site_id`, `cat_id`),
    KEY `idx_site_status` (`site_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='软件表'";

$sql2 = "CREATE TABLE IF NOT EXISTS `{$tablepre}cms_software_category` (
    `id` INT PRIMARY KEY AUTO_INCREMENT COMMENT '分类ID',
    `site_id` INT NOT NULL DEFAULT 0 COMMENT '站点ID',
    `parent_id` INT NOT NULL DEFAULT 0 COMMENT '上级分类ID: 0为一级',
    `name` VARCHAR(100) NOT NULL COMMENT '分类名称',
    `alias` VARCHAR(100) DEFAULT '' COMMENT 'URL别名',
    `intro` VARCHAR(1000) DEFAULT '' COMMENT '分类简介',
    `orderby` INT DEFAULT 0 COMMENT '排序值',
    `seo_title` VARCHAR(255) DEFAULT '' COMMENT 'SEO标题',
    `seo_keywords` VARCHAR(255) DEFAULT '' COMMENT 'SEO关键词',
    `seo_description` VARCHAR(500) DEFAULT '' COMMENT 'SEO描述',
    `enabled` TINYINT DEFAULT 1 COMMENT '状态: 1启用 0停用',
    KEY `idx_site` (`site_id`),
    KEY `idx_parent` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='软件分类表'";

$sql3 = "CREATE TABLE IF NOT EXISTS `{$tablepre}cms_software_import_log` (
    `id` INT PRIMARY KEY AUTO_INCREMENT COMMENT '日志ID',
    `site_id` INT NOT NULL DEFAULT 0 COMMENT '站点ID',
    `filename` VARCHAR(200) DEFAULT '' COMMENT '导入文件名',
    `total` INT DEFAULT 0 COMMENT '总行数',
    `success` INT DEFAULT 0 COMMENT '成功数',
    `failed` INT DEFAULT 0 COMMENT '失败数',
    `detail` TEXT COMMENT '失败明细',
    `dateline` INT UNSIGNED DEFAULT 0 COMMENT '导入时间',
    KEY `idx_site` (`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='软件导入日志表'";

$ret1 = $this->db->query($sql1);
$ret2 = $this->db->query($sql2);
$ret3 = $this->db->query($sql3);

if(!$ret1 || !$ret2 || !$ret3) {
    throw new Exception('software_center 插件数据表创建失败');
}

// 兼容已存在的旧表结构：补充 parent_id / intro 列
$alter1 = "ALTER TABLE `{$tablepre}cms_software_category` ADD COLUMN `parent_id` INT NOT NULL DEFAULT 0 COMMENT '上级分类ID: 0为一级' AFTER `site_id`, ADD COLUMN `intro` VARCHAR(1000) DEFAULT '' COMMENT '分类简介' AFTER `alias`";
$this->db->query($alter1);

// 预置 8 个常见软件分类（当前站点；site_id 取后台站点管理的第一个站点，缺省 1）
$site_row = $this->db->fetch_first("SELECT sid FROM `{$tablepre}site_manager` WHERE status=1 ORDER BY sid ASC LIMIT 1");
$site_id = $site_row ? (int)$site_row['sid'] : 1;

$pre_cats = array(
    array('办公软件', 'bangong', 1),
    array('影音娱乐', 'yinpin', 2),
    array('系统工具', 'xitong', 3),
    array('网络工具', 'wangluo', 4),
    array('安全软件', 'anquan', 5),
    array('图像处理', 'tuxiang', 6),
    array('编程开发', 'biancheng', 7),
    array('其它工具', 'qita', 8),
);
foreach($pre_cats as $c) {
    $exists = $this->db->fetch_first("SELECT id FROM `{$tablepre}cms_software_category` WHERE site_id={$site_id} AND name='" . addslashes($c[0]) . "' LIMIT 1");
    if($exists) continue;
    $this->db->query("INSERT INTO `{$tablepre}cms_software_category` (site_id,name,alias,orderby,enabled) VALUES ({$site_id},'" . addslashes($c[0]) . "','" . addslashes($c[1]) . "',{$c[2]},1)");
    // 预置分类同步登记前台 URL（type=10）
    $cate_url = '/soft/cate-' . $c[1] . '.html';
    $cate_hash = substr(strtolower(hash('sha256', $cate_url)), 0, 40);
    $cate_map = $this->db->fetch_first("SELECT id FROM `{$tablepre}cms_url_map` WHERE site_id={$site_id} AND url_hash='{$cate_hash}' LIMIT 1");
    if(!$cate_map) {
        $this->db->query("INSERT INTO `{$tablepre}cms_url_map` (site_id,url,url_hash,type,control,action,params,score,status,content_id) VALUES ({$site_id},'{$cate_url}','{$cate_hash}',10,'soft','cate','" . addslashes(json_encode(array('alias' => $c[1]))) . "',85,2,0)");
    }
}

// 软件首页 URL 登记进 url_map（type=11，复用 url_generator rewrite hook 路由）
$url = '/soft/';
$url_hash = substr(strtolower(hash('sha256', $url)), 0, 40);
$exists_map = $this->db->fetch_first("SELECT id FROM `{$tablepre}cms_url_map` WHERE site_id={$site_id} AND url_hash='{$url_hash}' LIMIT 1");
if(!$exists_map) {
    $this->db->query("INSERT INTO `{$tablepre}cms_url_map` (site_id,url,url_hash,type,control,action,params,score,status,content_id) VALUES ({$site_id},'{$url}','{$url_hash}',11,'soft','index','{}',80,2,0)");
}

return true;
