<?php
/**
 * Spider Analytics 启动器
 * 编译期内联到 base_control::__construct() 末尾
 * 触发时机：每个前台请求构造时
 * 行为：
 *   1) 拦截判断：若命中黑名单且拦截模式开启 → core::error404()
 *   2) 启动 shutdown recorder：响应完成后写 pre_spider_visit_log
 *   3) 后台请求不触发（避免 admin 自身被记为蜘蛛）
 */
if (!function_exists('spider_analytics_init')) {
function spider_analytics_init() {
    static $inited = false;
    if ($inited) return;
    $inited = true;

    // 后台请求跳过（plugin/spider-analytics 通过 F_APP_NAME 区分）
    if (defined('F_APP_NAME')) return;

    $site_id = defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0;
    $ua = isset($_SERVER['HTTP_USER_AGENT']) ? (string)$_SERVER['HTTP_USER_AGENT'] : '';
    $ip = isset($_ENV['_ip']) ? (string)$_ENV['_ip']
        : (isset($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : '');

    $base = PLUGIN_PATH . 'spider_analytics/';
    require_once $base . 'lib/cidr.class.php';
    require_once $base . '/lib/guard.class.php';
    require_once $base . '/lib/recorder.class.php';
    require_once $base . '/lib/detector.class.php';
    require_once $base . '/lib/runtime.class.php';
    require_once $base . '/model/runtime.class.php';
    require_once $base . '/model/blacklist.class.php';
    require_once $base . '/model/logger.class.php';

    $start_time = microtime(true);

    // 拦截判断
    try {
        $hit = spider_guard::match($ua, $ip, $site_id);
        if ($hit && spider_runtime_get('intercept_enabled', '0') === '1') {
            $bl = new spider_blacklist();
            $bl->record_hit(
                $site_id, $hit['match_type'], $hit['match_value'], $hit['id'], null,
                @inet_pton($ip), $ip, $ua,
                isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '',
                1, time()
            );
            if (class_exists('core') && method_exists('core', 'error404')) {
                core::error404();
            }
            exit;
        }
    } catch (Exception $e) {
        if (class_exists('log')) log::le_log('spider_analytics:' . $e->getMessage());
    }

    // shutdown recorder：响应完成后写日志（不重复 ob_start，框架已开）
    register_shutdown_function(function() use ($site_id, $ua, $ip, $start_time) {
        try {
            $end_time = microtime(true);
            $duration_ms = (int)(($end_time - $start_time) * 1000);
            $engine = spider_detector::identify($ua, $ip);
            $resp_code = function_exists('http_response_code') ? http_response_code() : 200;
            $content_len = function_exists('ob_get_length') ? (ob_get_length() ?: 0) : 0;
            $url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
            $ref = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
            $rec = spider_recorder::build_record(array(
                'site_id'       => $site_id,
                'engine'        => $engine,
                'ip'            => $ip,
                'ua'            => $ua,
                'url'           => $url,
                'referer'       => $ref,
                'method'        => isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET',
                'response_code' => $resp_code,
                'response_size' => (int)$content_len,
                'duration_ms'   => $duration_ms,
                'time'          => time(),
            ));
            $logger = new spider_logger();
            $logger->enqueue($rec);
            $logger->flush();

            // 命中但未拦截时记录 hit
            $hit = spider_guard::match($ua, $ip, $site_id);
            if ($hit) {
                $bl = new spider_blacklist();
                $bl->record_hit(
                    $site_id, $hit['match_type'], $hit['match_value'], $hit['id'], null,
                    @inet_pton($ip), $ip, $ua, $url, 0, time()
                );
            }
        } catch (Exception $e) {
            if (class_exists('log')) log::le_log('spider_analytics_shutdown:' . $e->getMessage());
        }
    });
}
}

spider_analytics_init();
