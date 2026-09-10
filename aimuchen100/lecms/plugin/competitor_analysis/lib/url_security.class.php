<?php
/**
 * URL 安全校验工具
 * 防止 SSRF：禁止访问内网地址、本地文件、非 HTTP(S) 协议
 */
class url_security {

    /**
     * 校验 URL 是否安全可请求
     * @param string $url 待校验URL
     * @return bool true=安全, false=危险
     */
    public static function is_safe($url) {
        // 仅允许 http(s) 协议
        $parsed = parse_url($url);
        if (!$parsed || !isset($parsed['scheme'])) {
            return false;
        }

        $scheme = strtolower($parsed['scheme']);
        if ($scheme !== 'http' && $scheme !== 'https') {
            return false;
        }

        $host = isset($parsed['host']) ? strtolower($parsed['host']) : '';

        // 禁止 localhost
        if ($host === 'localhost' || $host === '127.0.0.1' || $host === '::1') {
            return false;
        }

        // 禁止内网 IP 段
        if (self::is_private_ip($host)) {
            return false;
        }

        // 禁止 IP 直接访问（必须是域名）
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return false;
        }

        return true;
    }

    /**
     * 判断是否为内网 IP
     */
    private static function is_private_ip($host) {
        // 解析域名到 IP
        $ip = gethostbyname($host);
        if ($ip === $host) {
            // 无法解析，保守策略：拒绝
            return true;
        }

        $private_ranges = array(
            '10.0.0.0/8',
            '172.16.0.0/12',
            '192.168.0.0/16',
            '127.0.0.0/8',
            '169.254.0.0/16',
            '0.0.0.0/8',
        );

        foreach ($private_ranges as $range) {
            if (self::ip_in_range($ip, $range)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 检查 IP 是否在 CIDR 范围内
     */
    private static function ip_in_range($ip, $range) {
        list($subnet, $bits) = explode('/', $range);
        $ip = ip2long($ip);
        $subnet = ip2long($subnet);
        $mask = -1 << (32 - $bits);
        $subnet &= $mask;
        return ($ip & $mask) === $subnet;
    }
}
