<?php defined('ROOT_PATH') || exit;

/**
 * Hook: view_display_after.php
 *
 * 在 view::display() 输出前对渲染结果做运行时注入（模板管理插件前台链路）：
 *   1. 主题 CSS 变量注入（view/{theme}/css/theme.css 的 :root 块）
 *   2. 站点级 theme_vars 覆写（site_manager 的 SITE_CONFIG，经沙箱白名单过滤）
 *   3. 站点品牌 meta 注入（logo/favicon/watermark）
 *   4. SEO Schema JSON-LD 注入（按页面类型分发）
 *   5. BlockRender 挂载为 $_ENV['block_render']，供模板/后续 hook 消费 cms_block_config
 *
 * 仅前台生效：后台 admin 子应用不经过本视图层（admin 用自己的 view）。
 * 插件设置开关读取 template_manager/setting.php。
 */

// 快速判定：仅前台生效（后台 admin 子应用用自己的视图层），且输出为 HTML（含 </head>）
if (defined('APP_NAME') && APP_NAME === 'admin') {
    echo $html;
    return;
}

// 读取插件设置（文件缓存式，无 DB 开销）
// DEBUG 模式（debug=1/2）下 view::display() 每次渲染都会 include 本 hook 文件，
// 若页面单次请求触发多次 display（如 cps_redirect 中间页），重复 include 会
// 触发 "Cannot redeclare" 致命错误，故以 function_exists 包裹保证幂等。
if(!function_exists('tm_get_settings_safe')) {
function tm_get_settings_safe() {
    static $tm_settings = null;
    if ($tm_settings === null) {
        $tm_settings = array(
            'enabled' => 1, 'css_injection_enabled' => 1, 'schema_enabled' => 1,
            'brand_injection_enabled' => 0, 'block_enabled' => 1,
        );
        $file = PLUGIN_PATH . 'template_manager/setting.php';
        if (is_file($file)) {
            $saved = include $file;
            if (is_array($saved)) {
                $tm_settings = array_merge($tm_settings, $saved);
            }
        }
    }
    return $tm_settings;
}
}

$_tm_set = tm_get_settings_safe();
if (empty($_tm_set['enabled']) || strpos($html, '</head>') === false) {
    echo $html;
    return;
}

// 加载模型依赖（编译缓存下 __DIR__ 失效，必须 ROOT_PATH 绝对路径）
require_once ROOT_PATH . 'lecms/plugin/template_manager/model/sandbox.class.php';

// 主题取值优先 $_ENV['_theme']（view 层实际渲染用的主题目录），
// CURRENT_THEME 可能被 site_manager hook 设为站点表配置而与实际渲染主题不一致
$_tm_theme = isset($_ENV['_theme']) && $_ENV['_theme'] ? $_ENV['_theme'] : (defined('CURRENT_THEME') ? CURRENT_THEME : 'default');
$_tm_site_id = defined('CURRENT_SITE_ID') ? (int)CURRENT_SITE_ID : 0;

// ---------- 1. BlockRender 挂载 + Block 配置注入 ----------
// target 匹配当前 URL 的 Block 配置，其渲染结果追加到 </body> 前（组件式注入）。
// 同时把 BlockRender 实例挂到 $_ENV['block_render']，供模板内 {@$_ENV['block_render']->render(...)} 使用。
if (!empty($_tm_set['block_enabled'])) {
    require_once ROOT_PATH . 'lecms/plugin/template_manager/model/block_config.class.php';
    require_once ROOT_PATH . 'lecms/plugin/template_manager/model/block_render.class.php';
    // view 类无 db 属性，经 control::__get 同款方式构造 db 实例
    $_tm_db_type = 'db_' . $_ENV['_config']['db']['type'];
    $_tm_render = new BlockRender($_tm_site_id, new $_tm_db_type($_ENV['_config']['db']));
    $_ENV['block_render'] = $_tm_render;

    // 当前请求路径（去 query string），用于匹配 Block 配置的 target
    $_tm_uri = isset($_SERVER['REQUEST_URI']) ? explode('?', $_SERVER['REQUEST_URI'], 2)[0] : '/';

    foreach (BlockConfig::list_all_enabled($_tm_site_id, new $_tm_db_type($_ENV['_config']['db'])) as $__bc) {
        $__target = trim((string)$__bc['target']);
        // 空目标 = 全局注入；否则前缀匹配（/game/ 匹配 /game/123.html）
        if ($__target !== '' && strpos($_tm_uri, $__target) !== 0) {
            continue;
        }
        $__name = $__bc['block_name'];
        if (!method_exists($_tm_render, 'render_' . $__name)) {
            continue;
        }
        // 调用专用渲染方法（内部完成参数合并、模板解析与安全校验），
        // 不能直接调通用 render()——它按 {name}.htm 找模板而实际文件是 block_{name}.htm
        $__out = call_user_func(array($_tm_render, 'render_' . $__name), is_array($__bc['params']) ? $__bc['params'] : array());
        if ($__out !== '' && strpos($html, '</body>') !== false) {
            $html = preg_replace('/<\/body>/i', $__out . "\n</body>", $html, 1);
        }
    }
}

// ---------- 2. CSS 变量注入 ----------
if (!empty($_tm_set['css_injection_enabled'])) {
    $_tm_css_file = ROOT_PATH . 'view/' . $_tm_theme . '/css/theme.css';
    if (is_file($_tm_css_file)) {
        $_tm_css = file_get_contents($_tm_css_file);
        if (preg_match('/:root\s*\{([^}]+)\}/', $_tm_css, $_tm_m)) {
            $_tm_style = "<style id=\"theme-css-vars\">\n:root {\n" . trim($_tm_m[1]) . "\n}\n</style>";
            $html = preg_replace('/<\/head>/i', $_tm_style . "\n</head>", $html, 1);
        }
    }
}

// ---------- 3. 站点级 theme_vars 覆写 ----------
if (defined('SITE_CONFIG')) {
    $__sc = constant('SITE_CONFIG');
    if (is_array($__sc) && !empty($__sc['theme_vars']) && is_array($__sc['theme_vars'])) {
        $_tm_vars = $__sc['theme_vars'];
        $_tm_sb = new Sandbox();
        $_tm_vars = $_tm_sb->filter_css_vars($_tm_vars);
        if (!empty($_tm_vars)) {
            $_tm_css = ":root {\n";
            foreach ($_tm_vars as $_k => $_v) {
                $_tm_css .= "  " . htmlspecialchars((string)$_k, ENT_QUOTES, 'UTF-8')
                    . ": " . htmlspecialchars((string)$_v, ENT_QUOTES, 'UTF-8') . ";\n";
            }
            $_tm_css .= "}";
            $_tm_style = "<style id=\"theme-vars-override\">\n{$_tm_css}\n</style>";
            $html = preg_replace('/<\/head>/i', $_tm_style . "\n</head>", $html, 1);
        }
    }

    // ---------- 4. 品牌 meta 注入 ----------
    if (!empty($_tm_set['brand_injection_enabled'])) {
        foreach (array('logo_url', 'favicon_url', 'watermark_url') as $_bk) {
            if (empty($__sc[$_bk])) continue;
            $_bv = (string)$__sc[$_bk];
            if (stripos($_bv, 'javascript:') === 0) continue;
            $_meta = '<meta name="site_' . str_replace('_url', '', $_bk)
                . '" content="' . htmlspecialchars($_bv, ENT_QUOTES, 'UTF-8') . '">';
            $html = preg_replace('/<\/head>/i', $_meta . "\n</head>", $html, 1);
        }
    }
}

echo $html;
