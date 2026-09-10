<?php
/**
 * 标题策略采集器
 * 采集：title 标签、meta description、meta keywords、h1-h6 结构
 */
class title_collector implements DataCollectorInterface {

    public function collect($url) {
        // SSRF 防护：校验 URL 安全
        if (!url_security::is_safe($url)) {
            return array('error' => 'URL 不安全，已被拦截');
        }

        $html = $this->fetch_page($url);
        if ($html === false) {
            return array('error' => '无法获取页面内容');
        }

        $doc = new DOMDocument();
        @$doc->loadHTML($html);

        $result = array(
            'title' => $this->get_text($doc, 'title'),
            'meta_description' => $this->get_meta_content($doc, 'description'),
            'meta_keywords' => $this->get_meta_content($doc, 'keywords'),
            'h1_tags' => $this->get_tags($doc, 'h1'),
            'h2_tags' => $this->get_tags($doc, 'h2'),
            'title_length' => 0,
            'description_length' => 0,
        );

        $result['title_length'] = mb_strlen($result['title']);
        $result['description_length'] = mb_strlen($result['meta_description']);

        return $result;
    }

    public function getDataType() {
        return 'title_strategy';
    }

    private function fetch_page($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
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

    private function get_text($doc, $tag) {
        $elements = $doc->getElementsByTagName($tag);
        if ($elements->length > 0) {
            return trim($elements->item(0)->textContent);
        }
        return '';
    }

    private function get_meta_content($doc, $name) {
        $metas = $doc->getElementsByTagName('meta');
        foreach ($metas as $meta) {
            if (strtolower($meta->getAttribute('name')) === strtolower($name)) {
                return trim($meta->getAttribute('content'));
            }
        }
        return '';
    }

    private function get_tags($doc, $tag) {
        $elements = $doc->getElementsByTagName($tag);
        $tags = array();
        foreach ($elements as $el) {
            $text = trim($el->textContent);
            if ($text) {
                $tags[] = $text;
            }
        }
        return $tags;
    }
}
