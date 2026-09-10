<?php
/**
 * 知乎适配器
 */
class zhihu_adapter extends BaseMediaAdapter {

    public function getPlatformName() {
        return 'zhihu';
    }

    public function publish($article) {
        $formatted = $this->formatArticle($article);

        // 知乎专栏 API
        $url = 'https://www.zhihu.com/api/v4/columns/{column_id}/articles';

        $headers = array(
            'Authorization: Bearer ' . $this->account['access_token'],
        );

        $data = array(
            'title' => $formatted['title'],
            'content' => $formatted['content'],
            'column' => $this->account['column_id'],
        );

        $result = $this->http_request($url, 'POST', $data, $headers);

        if ($result['success']) {
            return array(
                'success' => true,
                'url' => 'https://zhuanlan.zhihu.com/p/' . md5($article['id']),
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
        // 知乎支持 Markdown，直接返回
        return array(
            'title' => $article['title'],
            'content' => $article['content'],
            'author' => $article['author'] ?: 'AI沐尘100',
        );
    }
}
