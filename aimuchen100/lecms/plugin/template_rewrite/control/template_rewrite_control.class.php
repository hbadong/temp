<?php
defined('ROOT_PATH') or exit;
/**
 * 模板伪原创前台控制器
 * 仅提供 css() 动作：/dynamic-css.css 动态输出前缀化 CSS（Content-Type: text/css）
 * 继承 base_control 以获得 CURRENT_SITE_ID（站群站点识别）
 */

class template_rewrite_control extends base_control {

    public function css() {
        require_once ROOT_PATH . 'lecms/plugin/template_rewrite/lib/template_rewrite_helper.php';

        $site_id = defined('CURRENT_SITE_ID') ? (int)CURRENT_SITE_ID : 0;
        $cfg = tr_get_settings($site_id);
        $css = tr_load_css();

        if(!headers_sent()) {
            header('Content-Type: text/css; charset=utf-8');
            header('X-Content-Type-Options: nosniff');
            header('Cache-Control: public, max-age=3600');
        }

        if(empty($cfg['enabled']) || $cfg['prefix'] === '') {
            echo $css;
            exit;
        }

        echo tr_apply($css, $cfg['prefix']);
        exit;
    }
}
