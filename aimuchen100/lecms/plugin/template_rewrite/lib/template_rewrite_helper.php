<?php
defined('ROOT_PATH') or exit;
/**
 * 模板伪原创助手（输出层 CSS 类前缀替换）
 *
 * 语义：模板作者以统一基准前缀 `gm-` 命名游戏模块类名（HTML class 与 CSS 选择器）。
 * 每个站点可配置独立前缀，站点级开启后，前台输出（HTML 与 /dynamic-css.css）中所有
 * `\bgm-` 词边界前缀统一替换为站点前缀，使各站 DOM/CSS 结构互不相同，降低整站模板雷同度。
 *
 * DEBUG 模式（debug=1/2）下 view::display() 每次渲染都会 include view_display_after.php，
 * 单次请求多次 display 会导致函数重复声明，故全部以 function_exists 包裹保证幂等。
 */

if(!function_exists('tr_sanitize_prefix')) {
function tr_sanitize_prefix($prefix) {
    $prefix = trim((string)$prefix);
    // 仅允许字母/数字/下划线/连字符，长度 2-20；非法返回空串（=不启用替换）
    if(preg_match('/^[a-zA-Z0-9_-]{2,20}$/', $prefix)) return $prefix;
    return '';
}
}

if(!function_exists('tr_get_settings')) {
function tr_get_settings($site_id) {
    static $_tr_cache = array();
    $site_id = (int)$site_id;
    if(isset($_tr_cache[$site_id])) return $_tr_cache[$site_id];
    $cfg = array('enabled' => 0, 'prefix' => '');
    if(class_exists('core') && is_callable(array('core', 'model'))) {
        try {
            $sm = core::model('site_manager');
            if($sm && method_exists($sm, 'get_config')) {
                $sc = $sm->get_config($site_id);
                if(is_array($sc)) {
                    $cfg['enabled'] = !empty($sc['template_rewrite_enabled']) ? 1 : 0;
                    $cfg['prefix'] = tr_sanitize_prefix(isset($sc['template_rewrite_prefix']) ? $sc['template_rewrite_prefix'] : '');
                }
            }
        } catch(Exception $e) {
            // 站点模型不可用时按未启用处理
        }
    }
    $_tr_cache[$site_id] = $cfg;
    return $cfg;
}
}

if(!function_exists('tr_apply')) {
function tr_apply($html, $prefix) {
    if($html === '' || $html === null) return $html;
    $prefix = tr_sanitize_prefix($prefix);
    if($prefix === '') return $html;
    // \bgm- 词边界前缀 → {prefix}-（gm 前不能是单词字符，避免误伤 xgm-/mygm- 等内部出现）
    return preg_replace('/\bgm-/', $prefix . '-', $html);
}
}

if(!function_exists('tr_get_theme')) {
function tr_get_theme() {
    if(isset($_ENV['_theme']) && $_ENV['_theme']) return $_ENV['_theme'];
    if(defined('CURRENT_THEME') && CURRENT_THEME) return CURRENT_THEME;
    return 'default';
}
}

if(!function_exists('tr_load_css')) {
/**
 * 加载游戏模块 CSS 原文（未做前缀替换），不存在返回空串
 *
 * 游戏模块样式以内联 <style> 写在 url_generator 的列表/详情模板里，
 * /dynamic-css.css 端点把这两段抽取并合并，供浏览器外链缓存 + 前缀替换。
 */
function tr_load_css() {
    $css = '';
    foreach(array(
        ROOT_PATH . 'lecms/plugin/url_generator/url_generator_game_list.htm',
        ROOT_PATH . 'lecms/plugin/url_generator/url_generator_game_detail.htm',
    ) as $__f) {
        if(!is_file($__f)) continue;
        $__s = @file_get_contents($__f);
        if($__s === false) continue;
        if(preg_match_all('/<style[^>]*>(.*?)<\/style>/is', $__s, $__m)) {
            foreach($__m[1] as $__block) {
                $css .= "\n" . trim($__block);
            }
        }
    }
    return $css;
}
}
