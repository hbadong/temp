<?php
/**
 * 微信公众号适配器
 */
class wechat_adapter extends BaseMediaAdapter {

    public function getPlatformName() {
        return 'wechat';
    }

    public function publish($article) {
        $formatted = $this->formatArticle($article);

        // 微信公众号需要通过官方 API 发布
        // 此处为简化实现，实际需对接微信 API
        $url = 'https://api.weixin.qq.com/cgi-bin/material/add_news';

        $headers = array(
            'Authorization: Bearer ' . $this->account['access_token'],
        );

        $data = array(
            'articles' => array(
                array(
                    'title' => $formatted['title'],
                    'content' => $formatted['content'],
                    'author' => $formatted['author'],
                    'digest' => mb_substr(strip_tags($formatted['content']), 0, 120),
                ),
            ),
        );

        $result = $this->http_request($url, 'POST', $data, $headers);

        if ($result['success']) {
            return array(
                'success' => true,
                'url' => 'https://mp.weixin.qq.com/s/' . md5($article['id']),
                'error' => '',
            );
        }

        return array(
            'success' => false,
            'url' => '',
            'error' => $result['response'],
        );
    }

    public function formatArticle($article) {
        return array(
            'title' => $article['title'],
            'content' => $article['content'],
            'author' => $article['author'] ?: 'AI沐尘100',
        );
    }
}
