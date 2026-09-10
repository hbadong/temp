<?php
/**
 * 平台适配器基类
 */
abstract class BaseMediaAdapter {

    protected $site_id;
    protected $account;

    public function __construct($site_id = 0, $account = array()) {
        $this->site_id = (int)$site_id;
        $this->account = $account;
    }

    /**
     * 发布文章到平台
     * @param array $article 文章数据
     * @return array ['success' => bool, 'url' => '', 'error' => '']
     */
    abstract public function publish($article);

    /**
     * 获取平台名称
     * @return string
     */
    abstract public function getPlatformName();

    /**
     * 格式化文章内容（适配平台格式）
     * @param array $article 文章数据
     * @return array 格式化后的文章
     */
    public function formatArticle($article) {
        return $article;
    }

    /**
     * 通用 HTTP 请求方法
     */
    protected function http_request($url, $method = 'GET', $data = array(), $headers = array()) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        // 合并所有 HTTP 头，避免二次赋值覆盖
        $http_headers = array_merge(array(
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        ), $headers);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            $http_headers[] = 'Content-Type: application/json';
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $http_headers);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return array(
            'success' => $http_code === 200,
            'http_code' => $http_code,
            'response' => $response,
        );
    }
}
