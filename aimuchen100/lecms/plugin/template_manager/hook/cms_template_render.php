<?php
/**
 * Hook: cms_template_render
 * 渲染注入（CSS 变量、Block 实例、SEO Schema）
 *
 * 职责：
 *   - 注入 theme_css_injection（CSS 变量）
 *   - 挂载 BlockRender 实例
 *   - 按页面类型调用 SeoSchema::generate()
 *   - 不改变 LECMS 核心引擎
 */

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__, 4) . '/');
}

/**
 * 模板渲染 Hook
 *
 * @param string $content 原始模板内容
 * @param array $context 渲染上下文（含 site_id, page_type 等）
 * @return string 处理后内容
 */
function hook_cms_template_render($content, $context = [])
{
    // 1. 注入主题预设 CSS 变量（theme_css_injection）
    $content = theme_css_injection($content, $context);

    // 1.5 站点级 theme_vars 安全覆写（REQ-05-AC9，覆盖预设）
    $content = theme_vars_override($content, $context);

    // 2. 站点品牌设置注入（REQ-05-AC11）
    $content = inject_brand_settings($content, $context);

    // 3. 挂载 BlockRender 实例
    $block_render = null;
    if (isset($context['site_id'])) {
        $block_render = new BlockRender($context['site_id'], $GLOBALS['db'] ?? null);
    }

    // 4. 按页面类型调用 SEO Schema
    $page_type = isset($context['page_type']) ? $context['page_type'] : 'default';
    $schema = seo_schema_dispatch($page_type, $context);

    // 将 schema 注入到 <head> 区域
    if ($schema) {
        $content = preg_replace(
            '/<\/head>/i',
            $schema . "\n</head>",
            $content,
            1
        );
    }

    return $content;
}

/**
 * CSS 变量注入
 */
function theme_css_injection($content, $context)
{
    $theme = defined('CURRENT_THEME') ? CURRENT_THEME : 'default';
    $css_file = ROOT_PATH . "view/{$theme}/css/theme.css";

    if (!file_exists($css_file)) {
        return $content;
    }

    $css = file_get_contents($css_file);

    // 提取 :root 中的 CSS 变量
    if (preg_match('/:root\s*\{([^}]+)\}/', $css, $matches)) {
        $variables = $matches[1];
        $style = "<style>\n:root {\n{$variables}\n}\n</style>";

        // 注入到 <head> 区域
        $content = preg_replace('/<\/head>/i', $style . "\n</head>", $content, 1);
    }

    return $content;
}

/**
 * SEO Schema 分发
 */
function seo_schema_dispatch($page_type, $context)
{
    $schema_map = [
        'game_detail' => 'VideoGame',
        'review' => 'Review',
        'download' => 'SoftwareApplication',
        'guide' => 'Article',
        'ranking' => 'ItemList',
    ];

    $schema_type = isset($schema_map[$page_type]) ? $schema_map[$page_type] : null;
    if (!$schema_type) {
        return '';
    }

    // 调用 SeoSchema::generate()
    if (class_exists('SeoSchema')) {
        return SeoSchema::generate($schema_type, $context);
    }

    return '';
}

/**
 * 站点级 CSS 变量覆写（REQ-05-AC9）
 *
 * 读取核心 base_control 注入的 SITE_CONFIG['theme_vars']（site 表 config JSON），
 * 经 Sandbox 白名单过滤后注入 :root 覆写主题预设变量。
 * 无 SITE_CONFIG / 无 theme_vars / 全部被过滤时原样返回。
 */
function theme_vars_override($content, $context)
{
    if (!defined('SITE_CONFIG')) {
        return $content;
    }
    $sc = constant('SITE_CONFIG');
    if (!is_array($sc) || empty($sc['theme_vars']) || !is_array($sc['theme_vars'])) {
        return $content;
    }

    $vars = $sc['theme_vars'];
    if (class_exists('Sandbox')) {
        $sandbox = new Sandbox();
        $vars = $sandbox->filter_css_vars($vars);
    }
    if (empty($vars)) {
        return $content;
    }

    $css = ":root {\n";
    foreach ($vars as $k => $v) {
        $css .= "  {$k}: " . htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8') . ";\n";
    }
    $css .= "}";
    $style = "<style id=\"theme-vars\">\n{$css}\n</style>";
    return preg_replace('/<\/head>/i', $style . "\n</head>", $content, 1);
}

/**
 * 站点品牌设置注入（REQ-05-AC11）
 *
 * 从 SITE_CONFIG（site 表 config JSON）读取 logo/favicon/watermark，
 * 注入 <head>；URL 校验拒绝 javascript: 等危险伪协议。
 */
function inject_brand_settings($content, $context)
{
    $site_id = isset($context['site_id']) ? $context['site_id'] : 0;

    // 品牌配置来源：核心 base_control 注入的 SITE_CONFIG（site 表 config JSON）
    $brand = array('logo_url' => '', 'favicon_url' => '', 'watermark_url' => '');
    if (defined('SITE_CONFIG')) {
        $sc = constant('SITE_CONFIG');
        if (is_array($sc)) {
            foreach (array('logo_url', 'favicon_url', 'watermark_url') as $k) {
                if (isset($sc[$k])) {
                    $brand[$k] = (string)$sc[$k];
                }
            }
        }
    }

    foreach ($brand as $key => $url) {
        if ($url === '') {
            continue;
        }

        // 校验 URL 协议，拒绝 javascript:（REQ-05-MGT22 安全防护）
        if (stripos($url, 'javascript:') === 0) {
            continue;
        }

        // 注入到 <head>
        $var_name = 'site_' . str_replace('_url', '', $key);
        $meta = '<meta name="' . $var_name . '" content="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">';
        $content = preg_replace('/<\/head>/i', $meta . "\n</head>", $content, 1);
    }

    return $content;
}
