<?php
/**
 * SEO Metadata - base_control_construct_after Hook
 *
 * 在 base_control 构造末尾注入三级 SEO 元数据变量（seo_title / seo_keywords / seo_description）。
 *
 * 触发点说明（重要）：
 * - 旧 hook/cms_template_render.php 依赖 `// hook cms_template_render.php` 标记，
 *   但核心源码（lecms/control/base_control.class.php、xiunophp/lib/view.class.php 等）
 *   无此触发点，主题模板也无 `{hook:cms_template_render.php}` 引用，导致三级 SEO
 *   继承逻辑成为死代码、REQ-07-AC2/AC3/AC4 不生效。
 * - 本文件挂载到 base_control::__construct() 末尾的合法钩子
 *   `// hook base_control_construct_after.php`（base_control.class.php:113），
 *   与 spider-mode / spider-pool 插件共用同一钩子点（process_hook 拼接所有插件同名文件），
 *   执行时 $this 可用（$this->db / $this->runtime / $this->_cfg）。
 * - hook 文件被内联进 runcache 编译产物，__DIR__ 失效，require 必须用 ROOT_PATH 绝对路径。
 *
 * @author     沐尘100
 * @version    1.0.0
 * @cms_version 3.0.0
 */

// 防重复（base_control 可能被多次构造）
if (defined('SEO_METADATA_RUN')) {
    return;
}
define('SEO_METADATA_RUN', true);

require_once ROOT_PATH . 'lecms/plugin/seo_metadata/model/seo_resolver.class.php';

try {
    $seo_get = isset($_GET) ? $_GET : array();
    $seo_cfg = (isset($this->_cfg) && is_array($this->_cfg)) ? $this->_cfg : array();
    $seo_meta = seo_resolver::resolve($this->db, $this->db->tablepre, $this->runtime, $seo_get, $seo_cfg);

    $seo_title = $seo_meta['title'];
    $seo_keywords = $seo_meta['keywords'];
    $seo_description = $seo_meta['description'];

    $this->assign('seo_title', $seo_title);
    $this->assign('seo_keywords', $seo_keywords);
    $this->assign('seo_description', $seo_description);
} catch (Exception $e) {
    // SEO 注入失败不影响页面访问
}
