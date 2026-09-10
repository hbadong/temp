<?php
/**
 * 页面抓取和解析引擎
 */
class scraper_engine {

    private $user_agents = array(
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36',
    );

    private $timeout = 30;
    private $min_interval = 3; // 最小请求间隔（秒）

    /**
     * 抓取页面内容
     * @param string $url 目标URL
     * @return string|false HTML内容
     */
    public function fetch($url) {
        // SSRF 防护：校验 URL
        if (!$this->is_safe_url($url)) {
            return false;
        }

        // 限频控制
        $this->respect_rate_limit();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'User-Agent: ' . $this->get_random_user_agent(),
            'Accept: text/html,application/xhtml+xml,application/xml',
            'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8',
        ));

        $response = curl_exec($ch);

        if ($response === false) {
            curl_close($ch);
            return false;
        }

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 200) {
            return false;
        }

        return $response;
    }

    /**
     * 校验 URL 安全性（防止 SSRF）
     */
    private function is_safe_url($url) {
        $parsed = parse_url($url);
        if (!$parsed || !isset($parsed['scheme'])) {
            return false;
        }

        $scheme = strtolower($parsed['scheme']);
        if ($scheme !== 'http' && $scheme !== 'https') {
            return false;
        }

        $host = isset($parsed['host']) ? strtolower($parsed['host']) : '';
        if (empty($host)) {
            return false;
        }

        // 禁止 localhost 和内网 IP
        if ($host === 'localhost' || $host === '127.0.0.1' || $host === '::1') {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return false;
        }

        return true;
    }

    /**
     * 解析页面中的引用链接
     * @param string $html HTML内容
     * @return array 引用URL列表
     */
    public function parse_references($html) {
        $references = array();

        // 匹配 <a href="..."> 标签
        preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/i', $html, $matches);

        if (!empty($matches[1])) {
            foreach ($matches[1] as $url) {
                if (filter_var($url, FILTER_VALIDATE_URL)) {
                    $references[] = $url;
                }
            }
        }

        return array_unique($references);
    }

    /**
     * 从 HTML 中提取文本内容
     * @param string $html HTML内容
     * @return string 纯文本
     */
    public function extract_text($html) {
        if (empty($html)) {
            return '';
        }

        $doc = new DOMDocument();
        $doc->loadHTML($html);
        $body = $doc->getElementsByTagName('body')->item(0);
        return $body ? trim($body->textContent) : '';
    }

    /**
     * 限频控制（使用文件锁防止竞态）
     */
    private function respect_rate_limit() {
        $cache_file = sys_get_temp_dir() . '/ai_search_monitor_last_request.cache';
        $fp = fopen($cache_file, 'c+');

        if ($fp) {
            if (flock($fp, LOCK_EX)) {
                $last_time = 0;
                if (filesize($cache_file) > 0) {
                    $last_time = (int)file_get_contents($cache_file);
                }
                $elapsed = time() - $last_time;
                if ($elapsed < $this->min_interval) {
                    sleep($this->min_interval - $elapsed);
                }
                ftruncate($fp, 0);
                rewind($fp);
                fwrite($fp, (string)time());
                fflush($fp);
                flock($fp, LOCK_UN);
            }
            fclose($fp);
        }
    }

    /**
     * 获取随机 User-Agent
     */
    private function get_random_user_agent() {
        return $this->user_agents[array_rand($this->user_agents)];
    }
}