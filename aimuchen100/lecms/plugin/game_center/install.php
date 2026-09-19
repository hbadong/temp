<?php
defined('ROOT_PATH') or exit;
/**
 * 游戏库插件安装脚本
 * 1. 创建 le_cms_game_category 游戏分类表
 * 2. 为 le_cms_game 补齐后台管理字段（上下架/下载/简介/更新时间）
 * 3. 预置 8 个常见游戏分类，并同步登记前台分类/详情 URL（与 url_generator 协议一致）
 */

$tablepre = $_ENV["_config"]["db"]["master"]["tablepre"];

// 1. 游戏分类表（独立于通用 le_category，结构与软件分类对齐）
$sql = "CREATE TABLE IF NOT EXISTS `{$tablepre}cms_game_category` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='游戏分类表'";

if(!$this->db->query($sql)) {
    throw new Exception('game_center 插件游戏分类表创建失败');
}

// 2. le_cms_game 补充后台管理字段（已存在则跳过）
$game_table = $tablepre . 'cms_game';
$game_cols = array(
    'status'           => "ALTER TABLE `{$game_table}` ADD COLUMN `status` TINYINT DEFAULT 1 COMMENT '状态: 1上架 0下架' AFTER `tags`",
    'downloads'        => "ALTER TABLE `{$game_table}` ADD COLUMN `downloads` INT UNSIGNED DEFAULT 0 COMMENT '下载次数' AFTER `status`",
    'download_url'     => "ALTER TABLE `{$game_table}` ADD COLUMN `download_url` VARCHAR(500) DEFAULT '' COMMENT '下载地址' AFTER `downloads`",
    'intro'            => "ALTER TABLE `{$game_table}` ADD COLUMN `intro` VARCHAR(1000) DEFAULT '' COMMENT '游戏简介' AFTER `download_url`",
    'updated_at'       => "ALTER TABLE `{$game_table}` ADD COLUMN `updated_at` DATETIME DEFAULT NULL COMMENT '更新时间' AFTER `created_at`",
    'content_id'       => "ALTER TABLE `{$game_table}` ADD COLUMN `content_id` INT DEFAULT 0 COMMENT '关联内容ID' AFTER `updated_at`",
);
$exists = array();
$rows = $this->db->fetch_all("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$game_table}'");
foreach((array)$rows as $r) $exists[$r['COLUMN_NAME']] = 1;
foreach($game_cols as $col => $sql_alter) {
    if(isset($exists[$col])) continue;
    $this->db->query($sql_alter);
}

// 3. 预置 8 个常见游戏分类（alias 与 url_generator 已生成的 /category/{slug}.html 一一对应）
$site_row = $this->db->fetch_first("SELECT sid FROM `{$tablepre}site_manager` WHERE status=1 ORDER BY sid ASC LIMIT 1");
$site_id = $site_row ? (int)$site_row['sid'] : 1;

$pre_cats = array(
    array('角色扮演', 'rpg', 1),
    array('动作游戏', 'action', 2),
    array('策略游戏', 'strategy', 3),
    array('体育游戏', 'sports', 4),
    array('益智休闲', 'puzzle', 5),
    array('街机游戏', 'arcade', 6),
    array('模拟经营', 'simulation', 7),
    array('冒险解密', 'adventure', 8),
);
foreach($pre_cats as $c) {
    $exists = $this->db->fetch_first("SELECT id FROM `{$tablepre}cms_game_category` WHERE site_id={$site_id} AND name='" . addslashes($c[0]) . "' LIMIT 1");
    $cate_alias = $c[1];
    if($exists) {
        $cate_id = (int)$exists['id'];
    }else{
        $this->db->query("INSERT INTO `{$tablepre}cms_game_category` (site_id,name,alias,orderby,enabled) VALUES ({$site_id},'" . addslashes($c[0]) . "','{$cate_alias}',{$c[2]},1)");
        $cate_id = (int)$this->db->last_insert_id();
    }

    // 登记分类前台 URL：/category/{alias}.html（type=3，与 url_generator 协议一致）
    $cate_url = '/category/' . $cate_alias . '.html';
    $cate_hash = substr(strtolower(hash('sha256', $cate_url)), 0, 40);
    $cate_map = $this->db->fetch_first("SELECT id FROM `{$tablepre}cms_url_map` WHERE site_id={$site_id} AND url_hash='{$cate_hash}' LIMIT 1");
    $params = json_encode(array('slug' => $cate_alias));
    if($cate_map) {
        $this->db->query("UPDATE `{$tablepre}cms_url_map` SET status=2, type=3, control='game', action='index', params='" . addslashes($params) . "', content_id={$cate_id} WHERE id=" . (int)$cate_map['id']);
    }else{
        $this->db->query("INSERT INTO `{$tablepre}cms_url_map` (site_id,url,url_hash,type,control,action,params,score,status,content_id) VALUES ({$site_id},'{$cate_url}','{$cate_hash}',3,'game','index','" . addslashes($params) . "',85,2,{$cate_id})");
    }
}

// 4. 已有游戏补登记详情 URL：/{id}.html（type=2），避免前台详情无路由
$games = $this->db->fetch_all("SELECT id FROM `{$tablepre}cms_game` WHERE site_id={$site_id} AND status=1");
foreach($games as $g) {
    $gid = (int)$g['id'];
    $game_url = '/' . $gid . '.html';
    $game_hash = substr(strtolower(hash('sha256', $game_url)), 0, 40);
    $game_map = $this->db->fetch_first("SELECT id FROM `{$tablepre}cms_url_map` WHERE site_id={$site_id} AND url_hash='{$game_hash}' LIMIT 1");
    $params = json_encode(array('id' => $gid));
    if($game_map) {
        $this->db->query("UPDATE `{$tablepre}cms_url_map` SET status=2, type=2, control='game', action='index', params='" . addslashes($params) . "', content_id={$gid} WHERE id=" . (int)$game_map['id']);
    }else{
        $this->db->query("INSERT INTO `{$tablepre}cms_url_map` (site_id,url,url_hash,type,control,action,params,score,status,content_id) VALUES ({$site_id},'{$game_url}','{$game_hash}',2,'game','index','" . addslashes($params) . "',85,2,{$gid})");
    }
}

return true;