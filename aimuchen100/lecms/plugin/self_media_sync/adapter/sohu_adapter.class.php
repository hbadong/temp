<?php
/**
 * 搜狐适配器（备选平台）
 */
class sohu_adapter extends BaseMediaAdapter {

    public function getPlatformName() {
        return 'sohu';
    }

    public function publish($article) {
        $formatted = $this->formatArticle($article);

        // 搜狐自媒体平台（简化实现）
        $url = 'https://mp.sohu.com/api/article/publish';

        $headers = array(
            'Authorization: Bearer ' . $this->account['access_token'],
        );

        $data = array(
            'title' => $formatted['title'],
            'content' => $formatted['content'],
            'category_id' => 1,
        );

        $result = $this->http_request($url, 'POST', $data, $headers);

        if ($result['success']) {
            return array(
                'success' => true,
                'url' => 'https://www.sohu.com/a/' . md5($article['id']),
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
