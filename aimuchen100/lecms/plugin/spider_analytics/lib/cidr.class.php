<?php
/**
 * CIDR 解析与匹配（IPv4 / IPv6）
 */
class spider_cidr {
    /**
     * 校验 CIDR 字符串格式是否合法（ip/bits，v4/v6 均可）
     * @return bool 合法返回 true
     */
    public static function valid($cidr) {
        if (!is_string($cidr)) return false;
        if (strpos($cidr, '/') === false) return false;
        list($subnet, $bits) = explode('/', $cidr, 2);
        if (!ctype_digit((string)$bits)) return false;
        $bits = (int)$bits;
        $sn_bin = @inet_pton($subnet);
        if ($sn_bin === false) return false;
        $max_bits = strlen($sn_bin) * 8;
        return $bits >= 0 && $bits <= $max_bits;
    }

    /**
     * 判断 $ip 是否落在 $cidr 内
     * @param string $ip   IP 地址
     * @param string $cidr CIDR 字符串（ip/bits）
     * @return bool 命中返回 true，否则 false；非法输入返回 false
     */
    public static function match($ip, $cidr) {
        if (!is_string($ip) || !is_string($cidr)) return false;
        if (strpos($cidr, '/') === false) return false;

        list($subnet, $bits) = explode('/', $cidr, 2);
        if (!ctype_digit((string)$bits)) return false; // 防止 'abc' 被当 0
        $bits = (int)$bits;
        if ($bits < 0) return false;

        $ip_bin = @inet_pton($ip);
        $sn_bin = @inet_pton($subnet);
        if ($ip_bin === false || $sn_bin === false) return false;
        if (strlen($ip_bin) !== strlen($sn_bin)) return false; // v4 vs v6 mismatch

        if ($bits === 0) return true;
        $max_bits = strlen($ip_bin) * 8;
        if ($bits > $max_bits) return false;

        $full_bytes = intdiv($bits, 8);
        $rem_bits   = $bits % 8;
        if ($full_bytes > 0 && substr($ip_bin, 0, $full_bytes) !== substr($sn_bin, 0, $full_bytes)) return false;
        if ($rem_bits === 0) return true;
        $mask = chr(0xFF << (8 - $rem_bits) & 0xFF);
        return (($ip_bin[$full_bytes] & $mask) === ($sn_bin[$full_bytes] & $mask));
    }
}

/**
 * 兼容函数别名
 */
if (!function_exists('cidr_match')) {
    function cidr_match($ip, $cidr) { return spider_cidr::match($ip, $cidr); }
}
