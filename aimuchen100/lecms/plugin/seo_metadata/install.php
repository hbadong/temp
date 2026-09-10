<?php
defined('ROOT_PATH') || exit;
/**
 * SEO元数据插件安装脚本
 */

$tablepre = $_ENV["_config"]["db"]["master"]["tablepre"];

// 扩展 le_category 表，添加 SEO 字段
$sql = "ALTER TABLE `{$tablepre}category`
    ADD COLUMN `seo_title` VARCHAR(200) DEFAULT '' AFTER `name`,
    ADD COLUMN `seo_keywords` VARCHAR(200) DEFAULT '' AFTER `seo_title`,
    ADD COLUMN `seo_description` VARCHAR(500) DEFAULT '' AFTER `seo_keywords`";

$this->db->query($sql);
