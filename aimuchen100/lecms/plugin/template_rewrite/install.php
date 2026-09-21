<?php
defined('ROOT_PATH') or exit;
/**
 * 模板伪原创插件安装脚本
 * 无新增数据表：per-site 前缀配置存放于 le_site_manager.config（JSON KV，key: template_rewrite_enabled / template_rewrite_prefix）
 * 仅校验依赖表存在，并避免重复安装报错。
 */

$tablepre = $_ENV["_config"]["db"]["master"]["tablepre"];

// 依赖检查：le_site_manager 表必须存在（site_manager 插件）
$check = $this->db->fetch_first("SHOW TABLES LIKE '{$tablepre}site_manager'");
if(empty($check)) {
    throw new Exception('template_rewrite 插件依赖 site_manager 插件的 le_site_manager 表，请先启用 site_manager 插件');
}
