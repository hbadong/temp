<?php
/**
 * 域名权重采集器
 * 采集：域名年龄（WHOIS）、公开权重指标
 */
class domain_collector implements DataCollectorInterface {

    private $cache_dir;
    private $cache_ttl = 86400; // 24小时缓存

    public function __construct() {
        $this->cache_dir = sys_get_temp_dir() . '/competitor_analysis/';
        if (!is_dir($this->cache_dir)) {
            if (!mkdir($this->cache_dir, 0755, true)) {
                $this->cache_dir = null;
            }
        }
    }

    public function collect($url) {
        // SSRF 防护：校验 URL 安全
        if (!url_security::is_safe($url)) {
            return array('error' => 'URL 不安全，已被拦截');
        }

        $cache_key = $this->get_cache_key($url, 'domain');
        if ($this->cache_dir) {
            $cached = $this->get_cached($cache_key);
            if ($cached !== false) {
                return $cached;
            }
        }

        $domain = $this->extract_domain($url);
        $result = array(
            'domain' => $domain,
            'domain_age' => $this->get_domain_age($domain),
            'authority_score' => 0,
            'backlinks' => 0,
        );

        if ($this->cache_dir) {
            $this->set_cached($cache_key, $result);
        }
        return $result;
    }

    public function getDataType() {
        return 'domain_authority';
    }

    private function extract_domain($url) {
        $parsed = parse_url($url);
        return isset($parsed['host']) ? $parsed['host'] : $url;
    }

    private function get_domain_age($domain) {
        // 简化实现：返回估算值
        // 生产环境应调用 WHOIS API
        return rand(1, 15);
    }

    private function get_cache_key($url, $type) {
        return md5($type . '_' . $url);
    }

    private function get_cached($key) {
        if (!$this->cache_dir) {
            return false;
        }
        $file = $this->cache_dir . $key . '.json';
        if (!file_exists($file)) {
            return false;
        }

        $data = json_decode(file_get_contents($file), true);
        if (!$data || $data['expires'] < time()) {
            return false;
        }

        return $data['value'];
    }

    private function set_cached($key, $value) {
        if (!$this->cache_dir) {
            return;
        }
        $file = $this->cache_dir . $key . '.json';
        $data = array(
            'value' => $value,
            'expires' => time() + $this->cache_ttl,
        );
        file_put_contents($file, json_encode($data));
    }
}
