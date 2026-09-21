<?php defined('ROOT_PATH') or exit;
/**
 * 动态 CSS 路由 Hook（内联于 parseurl_control::index() 的 rewrite 分支）
 * 将 /dynamic-css.css 映射到 template_rewrite_control::css()，
 * 由该控制器按当前站点前缀动态输出 CSS（Content-Type: text/css）。
 * 变量名加 tr_ 前缀：与 url_generator / game_center 同名 hook 拼接进同一作用域时避免变量覆盖。
 * 命中后 return 直接退出 index()，阻止标准解析器覆盖 control/action。
 */

$tr_uri = isset($uri) && $uri !== '' ? $uri : (isset($_GET['rewrite']) ? $_GET['rewrite'] : '');
if($tr_uri !== '' && $tr_uri === 'dynamic-css.css') {
    // 命中 /dynamic-css.css
    $_GET['control'] = 'template_rewrite';
    $_GET['action'] = 'css';
    return;
}
