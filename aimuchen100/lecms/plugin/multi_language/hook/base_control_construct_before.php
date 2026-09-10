<?php
/**
 * 语言路由 Hook
 * 在 base_control 构造前执行，识别当前请求的语言
 * 优先级：URL 路径前缀 > URL 子域名 > Cookie > Accept-Language > 站点默认
 *
 * 与 site-manager 的关系：site-manager 先执行（识别站点），本 Hook 在其之后
 * 叠加语言层。语言子域名必须在 site-manager 泛域名匹配之后解析。
 */

// 加载语言配置模型（插件模型，不在核心模型搜索路径中）

$site_id = defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0;
if (empty($site_id)) {
    return;
}

$lang_model = core::model('language_config');
try {
    $enabled_langs = $lang_model->get_enabled($site_id);
} catch (Exception $e) {
    // 表未创建或查询失败，静默返回
    return;
}

if (empty($enabled_langs)) {
    return;
}

// 构建语言代码映射
$lang_codes = array_column($enabled_langs, 'language');
$lang_map = array();
foreach ($enabled_langs as $lang) {
    $lang_map[$lang['language']] = $lang;
}

$default_lang = $lang_model->get_default($site_id);
$detected_lang = $default_lang;

// ====== 优先级1: URL 路径前缀（/en/article/123）======
$request_uri = $_SERVER['REQUEST_URI'] ?? '';
$path_parts = explode('/', trim($request_uri, '/'));

if (!empty($path_parts[0]) && in_array($path_parts[0], $lang_codes)) {
    $detected_lang = $path_parts[0];
    // 重写 $_GET['s'] 以剥离语言前缀
    if (isset($path_parts[1]) && !empty($path_parts[1])) {
        $_GET['s'] = implode('/', array_slice($path_parts, 1));
    } else {
        $_GET['s'] = '';
    }
}

// ====== 优先级2: URL 子域名（en.domain.com）======
if ($detected_lang == $default_lang) {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $host_parts = explode('.', $host);

    if (!empty($host_parts[0]) && in_array($host_parts[0], $lang_codes)) {
        $detected_lang = $host_parts[0];
    }
}

// ====== 优先级3: Cookie（lang=en）======
if ($detected_lang == $default_lang && !empty($_COOKIE['lang'])) {
    $cookie_lang = $_COOKIE['lang'];
    if (in_array($cookie_lang, $lang_codes)) {
        $detected_lang = $cookie_lang;
    }
}

// ====== 优先级4: Accept-Language 浏览器头 ======
if ($detected_lang == $default_lang && !empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
    $browser_langs = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
    foreach ($browser_langs as $bl) {
        $bl = trim(explode(';', $bl)[0]);
        $bl_short = substr($bl, 0, 2);
        if (in_array($bl_short, $lang_codes)) {
            $detected_lang = $bl_short;
            break;
        }
    }
}

// ====== 设置常量 ======
if (!defined('CURRENT_LANGUAGE')) {
    define('CURRENT_LANGUAGE', $detected_lang);
}

// 设置 Cookie（30天有效期），添加安全属性
if ($detected_lang != $default_lang) {
    $cookie_expire = time() + 86400 * 30;
    $cookie_path = '/';
    $cookie_domain = '';
    $cookie_secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    $cookie_httponly = true;
    $cookie_options = array(
        'expires' => $cookie_expire,
        'path' => $cookie_path,
        'domain' => $cookie_domain,
        'secure' => $cookie_secure,
        'httponly' => $cookie_httponly,
        'samesite' => 'Lax',
    );
    setcookie('lang', $detected_lang, $cookie_options);
}
