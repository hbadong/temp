<?php
/**
 * 模板结构分析器
 * 采集：CSS 框架、JS 库、meta 标签、页面结构特征
 */
class template_collector implements DataCollectorInterface {

    public function collect($url) {
        // SSRF 防护：校验 URL 安全
        if (!url_security::is_safe($url)) {
            return array('error' => 'URL 不安全，已被拦截');
        }

        $html = $this->fetch_page($url);
        if ($html === false) {
            return array('error' => '无法获取页面内容');
        }

        $result = array(
            'css_frameworks' => $this->detect_css_frameworks($html),
            'js_libraries' => $this->detect_js_libraries($html),
            'meta_tags_count' => $this->count_meta_tags($html),
            'has_structured_data' => $this->has_structured_data($html),
            'page_size_kb' => round(strlen($html) / 1024, 2),
        );

        return $result;
    }

    public function getDataType() {
        return 'template_structure';
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

    private function detect_css_frameworks($html) {
        $frameworks = array();

        if (stripos($html, 'bootstrap') !== false) {
            $frameworks[] = 'Bootstrap';
        }
        if (stripos($html, 'tailwind') !== false || stripos($html, 'tailwindcss') !== false) {
            $frameworks[] = 'Tailwind CSS';
        }
        if (stripos($html, 'foundation') !== false) {
            $frameworks[] = 'Foundation';
        }
        if (stripos($html, 'bulma') !== false) {
            $frameworks[] = 'Bulma';
        }
        if (stripos($html, 'semantic-ui') !== false) {
            $frameworks[] = 'Semantic UI';
        }

        return $frameworks ?: array('Unknown');
    }

    private function detect_js_libraries($html) {
        $libraries = array();

        $patterns = array(
            'jquery' => '/jquery/i',
            'react' => '/react/i',
            'vue' => '/vue\.js/i',
            'angular' => '/angular/i',
            'gsap' => '/gsap/i',
        );

        foreach ($patterns as $name => $pattern) {
            if (preg_match($pattern, $html)) {
                $libraries[] = ucfirst($name);
            }
        }

        return $libraries ?: array('Vanilla JS');
    }

    private function count_meta_tags($html) {
        preg_match_all('/<meta[^>]*>/i', $html, $matches);
        return count($matches[0]);
    }

    private function has_structured_data($html) {
        return stripos($html, 'application/ld+json') !== false
            || stripos($html, 'itemtype') !== false
            || stripos($html, 'schema.org') !== false;
    }
}
