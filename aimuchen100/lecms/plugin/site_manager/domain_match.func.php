<?php
defined('ROOT_PATH') || exit;

/**
 * 域名→站点匹配（REQ-01-AC6/AC7）
 *
 * 匹配顺序：
 * 1. 精确匹配 site_map 中的域名键
 * 2. 泛解析：site_map 中含 * 的键，如 *.a.com
 * 3. 二级域名回退：host 为 sub.a.com 形式时，若 a.com 在 site_map 且 sid
 *    在 SUB_DOMAIN_SITEIDS 白名单内，则命中 a.com 对应站点
 *
 * @param string $host 当前请求 HTTP_HOST
 * @param array $site_map domain => sid 映射（仅启用站点）
 * @param array $sub_domain_siteids 二级域名模式白名单 sid 数组（SUB_DOMAIN_SITEIDS 常量内容）
 * @return int 站点ID，未命中返回 0
 */
function match_domain_host($host, $site_map, $sub_domain_siteids = array()) {
    $host = strtolower(trim($host));
    if($host === '' || empty($site_map)) return 0;

    // 去掉端口号（dev 环境常带 :8080 / :80），与 url_generator hook 的解析端保持一致；
    // 站点登记域名一律不含端口，带端口 Host 不做归一化会精确匹配失败落入 404
    if(strpos($host, ':') !== false) {
        $host = substr($host, 0, strpos($host, ':'));
    }

    // 1) 精确匹配
    if(isset($site_map[$host])) return (int)$site_map[$host];

    // 2) 泛解析匹配
    foreach($site_map as $domain => $sid) {
        if(strpos($domain, '*') === false) continue;
        $pattern = '#^' . str_replace('\*', '([^\.]+)', preg_quote(strtolower($domain), '#')) . '$#';
        if(preg_match($pattern, $host)) return (int)$sid;
    }

    // 3) 二级域名回退（受 SUB_DOMAIN_SITEIDS 白名单控制）
    if(!empty($sub_domain_siteids)) {
        // 去掉端口后按段拆分：sub.a.com → [sub, a, com]
        $host_no_port = preg_replace('#:\d+$#', '', $host);
        $parts = explode('.', $host_no_port);
        if(count($parts) > 2) {
            $root = implode('.', array_slice($parts, -2)); // a.com
            if(isset($site_map[$root]) && in_array((int)$site_map[$root], array_map('intval', $sub_domain_siteids))) {
                return (int)$site_map[$root];
            }
        }
    }

    return 0;
}
