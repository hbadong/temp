<?php
/**
 * 服务器信息采集器
 * 采集：Server、X-Powered-By、CDN 信息、HTTP 状态
 */
class server_collector implements DataCollectorInterface {

    public function collect($url) {
        // SSRF 防护：校验 URL 安全
        if (!url_security::is_safe($url)) {
            return array('error' => 'URL 不安全，已被拦截');
        }

        $headers = $this->fetch_headers($url);

        if ($headers === false) {
            return array('error' => '无法获取响应头');
        }

        // HTTP 头名称大小写不敏感，统一转小写存储
        $normalized = array();
        foreach ($headers as $key => $value) {
            $normalized[strtolower($key)] = $value;
        }

        $result = array(
            'server' => isset($normalized['server']) ? $normalized['server'] : 'Unknown',
            'x_powered_by' => isset($normalized['x-powered-by']) ? $normalized['x-powered-by'] : 'Hidden',
            'cdn' => $this->detect_cdn($normalized),
            'http_version' => isset($normalized['http']) ? $normalized['http'] : 'Unknown',
            'content_type' => isset($normalized['content-type']) ? $normalized['content-type'] : 'Unknown',
        );

        return $result;
    }

    public function getDataType() {
        return 'server_info';
    }

    private function fetch_headers($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        ));

        $response = curl_exec($ch);

        if ($response === false) {
            curl_close($ch);
            return false;
        }

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        if ($http_code !== 200) {
            return false;
        }

        $headers = array();
        $header_lines = explode("\n", substr($response, 0, $header_size));
        foreach ($header_lines as $line) {
            if (strpos($line, ':') !== false) {
                list($key, $value) = explode(':', $line, 2);
                $headers[trim($key)] = trim($value);
            }
        }

        return $headers;
    }

    private function detect_cdn($headers) {
        $cdn_signals = array(
            'Cloudflare' => array('CF-RAY', 'cloudflare'),
            'AWS CloudFront' => array('X-CDN', 'CloudFront'),
            '阿里云 CDN' => array('X-CDN', 'Aliyun'),
            '腾讯云 CDN' => array('X-CDN', 'Qcloud'),
        );

        foreach ($cdn_signals as $name => $signals) {
            foreach ($signals as $signal) {
                foreach ($headers as $key => $value) {
                    if (stripos($key . ':' . $value, $signal) !== false) {
                        return $name;
                    }
                }
            }
        }

        return 'None';
    }
}
