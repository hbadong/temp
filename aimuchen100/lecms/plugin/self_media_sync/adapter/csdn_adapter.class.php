<?php
/**
 * CSDN 适配器
 */
class csdn_adapter extends BaseMediaAdapter {

    public function getPlatformName() {
        return 'csdn';
    }

    public function publish($article) {
        $formatted = $this->formatArticle($article);

        // CSDN 博客发布 API
        $url = 'https://blog-console-api.csdn.net/v1/article/publish';

        $headers = array(
            'Authorization: Bearer ' . $this->account['access_token'],
        );

        $data = array(
            'title' => $formatted['title'],
            'markdowncontent' => $formatted['content'],
            'tags' => $formatted['tags'],
            'status' => 1, // 1=已发布
        );

        $result = $this->http_request($url, 'POST', $data, $headers);

        if ($result['success']) {
            return array(
                'success' => true,
                'url' => 'https://blog.csdn.net/' . md5($article['id']),
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
            'tags' => $article['tags'] ?: '游戏',
            'author' => $article['author'] ?: 'AI沐尘100',
        );
    }
}
