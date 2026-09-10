<?php
/**
 * 微信AI搜索监测适配器
 */
class wechat_adapter implements AiSearchAdapterInterface {

    public function getPlatformName() {
        return 'wechat_ai';
    }

    public function search($keyword) {
        $url = 'https://weixin.sogou.com/weixin?type=2&query=' . urlencode($keyword);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        ));

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($response === false || $http_code !== 200) {
            return array(
                'found' => false,
                'position' => 0,
                'reference_url' => $url,
                'response_snippet' => $curl_error ?: 'HTTP ' . $http_code,
                'confidence' => 0,
            );
        }

        $found = stripos($response, $keyword) !== false;

        return array(
            'found' => $found ? 1 : 0,
            'position' => $found ? 1 : 0,
            'reference_url' => $url,
            'response_snippet' => mb_substr(strip_tags($response), 0, 500),
            'confidence' => $found ? 80 : 0,
        );
    }
}
