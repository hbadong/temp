<?php
/**
 * 引擎识别：UA 主识别 + IP 段可选验证
 */
class spider_detector {
    private static $ua_patterns = array(
        'baidu'  => '/Baiduspider|baiduboxapp/i',
        'google' => '/Googlebot|Google-InspectionTool/i',
        'bing'   => '/bingbot|msnbot/i',
        'sogou'  => '/Sogou Pic Spider|Sogou web spider/i',
        '360'    => '/360Spider|HaosouSpider/i',
        'sm'     => '/Yisouspider/i',
    );

    /**
     * 识别引擎。$ip 可选；若 spider_runtime_get('ip_range_strict')==='1' 则需 IP 段匹配。
     */
    public static function identify($ua, $ip = null) {
        $ua = is_string($ua) ? $ua : '';
        if (strlen($ua) > 512) $ua = substr($ua, 0, 512);
        if ($ua === '') return 'other';
        foreach (self::$ua_patterns as $engine => $re) {
            if (preg_match($re, $ua)) {
                if ($ip !== null && function_exists('spider_runtime_get')
                    && spider_runtime_get('ip_range_strict', '0') === '1') {
                    if (!self::ip_match_engine($ip, $engine)) return 'other';
                }
                return (string)$engine;
            }
        }
        return 'other';
    }

    private static function ip_match_engine($ip, $engine) {
        // 测试环境：从 spider_runtime_mock 读取
        $rows = spider_runtime_get('ip_range_cache', array());
        foreach ($rows as $r) {
            if ($r['engine'] === $engine
                && spider_cidr::match($ip, $r['cidr'])) return true;
        }
        return false;
    }
}
