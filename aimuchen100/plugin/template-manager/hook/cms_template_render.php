<?php
/**
 * Template Manager - cms_template_render Hook
 *
 * 模板渲染前置注入（view.class.php 渲染前执行）：
 * 1. 注入站点级 CSS 变量 <style>:root{...}</style>
 * 2. 注册 {block:xxx} 标签处理器到模板引擎
 * 3. 按页面类型注入 SEO Schema（JSON-LD）
 *
 * 框架机制：
 * - cms_template_render 在 view.class.php 渲染阶段触发
 * - $this 为前台 control 实例
 * - 所有操作通过 $this-> 直接访问控制器实例属性与方法
 */

// ========== 文件顶部引入依赖（与 brief 一致） ==========
require_once ROOT_PATH . 'plugin/template-manager/model/block_render.class.php';

// 表前缀（SEO Schema 查询需要在文件顶部可用）
$tablepre = (isset($_ENV['_config']['db']['master']['tablepre'])
    ? $_ENV['_config']['db']['master']['tablepre']
    : 'pre_');

// ========== SEO Schema 注入逻辑（直接使用 $this 上下文） ==========

// 获取内容 ID
$content_id = 0;
$page_type = '';

if (isset($_GET['id']) || isset($_GET['alias'])) {
    $content_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if (!$content_id && isset($_GET['alias'])) {
        $alias_row = null;
        try {
            $alias_row = $this->db->fetch("
                SELECT id FROM `{$tablepre}only_alias`
                WHERE alias = ? LIMIT 1
            ", array($_GET['alias']));
        } catch (Exception $e) {
            $alias_row = null;
        }
        $content_id = $alias_row ? (int)$alias_row['id'] : 0;
    }

    if ($content_id) {
        $content = $this->db->fetch_first("
            SELECT subject, seo_title, seo_keywords, seo_description, cid
            FROM `{$tablepre}cms_article`
            WHERE id = '{$content_id}' AND status = 1
            LIMIT 1
        ");

        if ($content) {
            // 按分类 ID 判断页面类型
            $cid = (int)$content['cid'];

            $category = $this->db->fetch("
                SELECT name, seo_title, seo_keywords, seo_description
                FROM `{$tablepre}category`
                WHERE cid = '{$cid}'
                LIMIT 1
            ");

            if ($category) {
                $name = strtolower($category['name']);

                if (strpos($name, '评测') !== false || strpos($name, 'review') !== false) {
                    $page_type = 'review';
                } elseif (strpos($name, '下载') !== false || strpos($name, 'download') !== false) {
                    $page_type = 'software';
                } elseif (strpos($name, '攻略') !== false || strpos($name, 'guide') !== false) {
                    $page_type = 'article';
                } elseif (strpos($name, '排行') !== false || strpos($name, '排名') !== false || strpos($name, 'rank') !== false) {
                    $page_type = 'itemlist';
                } else {
                    // 默认为游戏详情页
                    $page_type = 'game';
                }
            }
        }
    }
}

if (!empty($page_type)) {
    $webname = isset($_ENV['_cfg']['webname']) ? $_ENV['_cfg']['webname'] : '';
    $dateline = isset($_ENV['_time']) ? $_ENV['_time'] : time();
    $schema = null;

    switch ($page_type) {
        case 'game':
            $schema = array(
                '@context' => 'https://schema.org',
                '@type' => 'VideoGame',
                'name' => $content['subject'],
                'description' => $content['seo_description'] ?: $content['subject'],
                'datePublished' => date('Y-m-d', $dateline),
            );
            break;

        case 'review':
            $rating = $this->db->fetch("
                SELECT rating FROM `{$tablepre}cms_article_rating`
                WHERE article_id = '{$content_id}' LIMIT 1
            ");
            $score = $rating ? (float)$rating['rating'] : 0;

            $schema = array(
                '@context' => 'https://schema.org',
                '@type' => 'Review',
                'name' => $content['seo_title'] ?: $content['subject'],
                'reviewBody' => $content['seo_description'],
                'datePublished' => date('Y-m-d', $dateline),
                'reviewRating' => array(
                    '@type' => 'Rating',
                    'ratingValue' => $score > 0 ? $score : 7,
                    'bestRating' => 10,
                    'worstRating' => 1,
                ),
                'itemReviewed' => array(
                    '@type' => 'VideoGame',
                    'name' => $content['subject'],
                ),
            );
            break;

        case 'software':
            $schema = array(
                '@context' => 'https://schema.org',
                '@type' => 'SoftwareApplication',
                'name' => $content['subject'],
                'description' => $content['seo_description'],
                'applicationCategory' => 'Game',
                'operatingSystem' => 'Windows',
                'datePublished' => date('Y-m-d', $dateline),
            );
            break;

        case 'article':
            $schema = array(
                '@context' => 'https://schema.org',
                '@type' => 'Article',
                'headline' => $content['subject'],
                'description' => $content['seo_description'],
                'datePublished' => date('Y-m-d', $dateline),
            );
            break;

        case 'itemlist':
            $items = $this->db->fetch_all("
                SELECT id, subject FROM `{$tablepre}cms_article`
                WHERE cid = '{$content['cid']}' AND status = 1
                ORDER BY id DESC LIMIT 10
            ");
            $item_list = array();
            if (!empty($items)) {
                foreach ($items as $item) {
                    $item_list[] = array(
                        '@type' => 'ListItem',
                        'position' => count($item_list) + 1,
                        'url' => $_ENV['_cfg']['weburl'] . '/index.php?id=' . $item['id'],
                        'name' => $item['subject'],
                    );
                }
            }

            $schema = array(
                '@context' => 'https://schema.org',
                '@type' => 'ItemList',
                'name' => $content['subject'] ?: $webname . ' 游戏排行榜',
                'itemListElement' => $item_list,
            );
            break;
    }

    if ($schema) {
        $this->assign('seo_schema', json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}

// ========== 主执行逻辑 ==========

// ========== 1. 注入站点级 CSS 变量 ==========
$theme_css = '';
if (isset($this) && property_exists($this, 'theme_css_vars') && !empty($this->theme_css_vars)) {
    $theme_css = $this->theme_css_vars;
} elseif (!empty($GLOBALS['theme_css_vars'])) {
    $theme_css = $GLOBALS['theme_css_vars'];
}

if (!empty($theme_css)) {
    // 仅通过 assign 注入到模板引擎，符合 brief 规范（移除 echo 副作用）
    $this->assign('theme_css_injection',
        '<style id="theme-vars">:root{' . $theme_css . ';}</style>'
    );
}

// ========== 2. 注册 Block 渲染器 ==========
$site_id = defined('CURRENT_SITE_ID') ? (int)CURRENT_SITE_ID : 0;
$db = isset($this) && isset($this->db) ? $this->db : null;

if ($site_id > 0 && $db) {
    $block_render = new BlockRender($site_id, $db);

    // 注入模板变量，使模板可以直接调用
    $this->assign('block_render', $block_render);
}
