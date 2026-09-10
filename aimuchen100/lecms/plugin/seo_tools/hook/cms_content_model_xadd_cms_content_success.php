<?php
/**
 * SEO Tools - 新增内容自动推送 Hook（cms_content_model hook 点）
 *
 * 触发时机：lecms/model/cms_content_model.class.php xadd() 方法体 line 411
 * - 主表写入成功后（$cms_content['id'] = $id 已赋值）
 * - 附表写入前
 *
 * 功能：构造内容 URL，通过 register_shutdown_function 异步推送到搜索引擎。
 * - 有 alias：http://domain/{alias}.html
 * - 无 alias：http://domain/index.php?control=content&action=show&cid={cid}&id={id}
 *
 * 设计要点：
 * - 使用 seo_tools_register_shutdown() 包装器注册 shutdown 回调，
 *   便于测试时 mock（替换为自定义函数），production 行为不变。
 * - SEO_TOOLS_PUSH_REGISTERED 常量 + $id/$GLOBALS['run'] 双重防重复注册。
 * - 推送异常仅在 shutdown 回调内记录日志，不阻断 xadd() 执行。
 * - 无 $GLOBALS['run'] 时跳过推送（前台 hook 可能无 db 注入）。
 */

// seo_tools_register_shutdown 包装器：production 调用原生 register_shutdown_function，
// 测试时可通过 function_exists + 替换实现 mock。
if (!function_exists('seo_tools_register_shutdown')) {
    function seo_tools_register_shutdown($callback)
    {
        // 兜底：若 callback 是字符串但函数不存在，注册一个空函数避免 PHP 7.4+
        // "Invalid shutdown callback" Warning（LECMS set_error_handler 会把
        // 该 Warning 升级为 Exception 中断 xadd 主流程）。
        if (is_string($callback) && !function_exists($callback)) {
            $fallback = '_seo_tools_push_url_shutdown_noop';
            if (!function_exists($fallback)) {
                function _seo_tools_push_url_shutdown_noop() { /* no-op */ }
            }
            $callback = $fallback;
        }
        register_shutdown_function($callback);
    }
}

// 防御性跳过：$id 为空 / 无 $GLOBALS['run'] / $cms_content['id'] 缺失时不注册
if (!$id || !isset($GLOBALS['run']) || !isset($cms_content['id'])) {
    // 跳过推送，不阻断 xadd 执行
} elseif (!defined('SEO_TOOLS_PUSH_REGISTERED')) {
    define('SEO_TOOLS_PUSH_REGISTERED', true);

    // 提取推送数据
    $site_id = defined('CURRENT_SITE_ID') ? (int)CURRENT_SITE_ID : 0;
    $push_cid = isset($cms_content['cid']) ? (int)$cms_content['cid'] : 0;
    $push_alias = isset($cms_content['alias']) ? trim((string)$cms_content['alias']) : '';
    $push_title = isset($cms_content['title']) ? (string)$cms_content['title'] : '';

    // 构造 URL
    if ($push_alias !== '') {
        $push_url = 'http://' . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '') . '/' . $push_alias . '.html';
    } else {
        $push_url = 'http://' . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '') . '/index.php?control=content&action=show&cid=' . $push_cid . '&id=' . $id;
    }

    // 通过 $GLOBALS 传递数据给 shutdown 回调（局部变量在 shutdown 时不可用）
    $GLOBALS['seo_tools_push_data'] = array(
        'site_id' => $site_id,
        'id'      => $id,
        'cid'     => $push_cid,
        'alias'   => $push_alias,
        'title'   => $push_title,
        'url'     => $push_url,
    );

    // 注册 shutdown 回调（异步推送，不阻断内容发布流程）
    seo_tools_register_shutdown('seo_tools_push_url_shutdown');
}
