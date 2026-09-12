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
 * 受插件设置（settings）控制：
 * - domain_match = exact：仅精确匹配，禁用泛解析与二级域名回退
 * - domain_match = wildcard（默认）：完整三级匹配
 * - wildcard_domain = 0：即使 wildcard 模式也禁用泛解析（*. 条目）匹配
 *
 * @param string $host 当前请求 HTTP_HOST
 * @param array $site_map domain => sid 映射（仅启用站点）
 * @param array $sub_domain_siteids 二级域名模式白名单 sid 数组（SUB_DOMAIN_SITEIDS 常量内容）
 * @param array $settings 插件设置（site_manager_settings runtime）
 * @return int 站点ID，未命中返回 0
 */
function match_domain_host($host, $site_map, $sub_domain_siteids = array(), $settings = array()) {
    $host = strtolower(trim($host));
    if($host === '' || empty($site_map)) return 0;

    // 解析插件设置（默认值兜底）
    $match_mode = isset($settings['domain_match']) && $settings['domain_match'] === 'exact' ? 'exact' : 'wildcard';
    $allow_wildcard = !isset($settings['wildcard_domain']) || !empty($settings['wildcard_domain']);

    // 去掉端口号（dev 环境常带 :8080 / :80），与 url_generator hook 的解析端保持一致；
    // 站点登记域名一律不含端口，带端口 Host 不做归一化会精确匹配失败落入 404
    if(strpos($host, ':') !== false) {
        $host = substr($host, 0, strpos($host, ':'));
    }

    // 1) 精确匹配
    if(isset($site_map[$host])) return (int)$site_map[$host];

    // exact 模式到此为止：只认精确登记（含 *. 通配条目本身）
    if($match_mode === 'exact') return 0;

    // 2) 泛解析匹配（受 wildcard_domain 开关控制）
    if($allow_wildcard) {
        foreach($site_map as $domain => $sid) {
            if(strpos($domain, '*') === false) continue;
            $pattern = '#^' . str_replace('\*', '([^\.]+)', preg_quote(strtolower($domain), '#')) . '$#';
            if(preg_match($pattern, $host)) return (int)$sid;
        }
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
