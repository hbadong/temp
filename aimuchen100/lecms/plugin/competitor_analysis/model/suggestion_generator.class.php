<?php
/**
 * AI 优化建议生成器
 * 复用 ai-content-factory 的 ai_config 配置
 */
class suggestion_generator {

    private $config;
    private $db;

    public function __construct($site_id = 0, $db = null) {
        $this->db = $db;
        $this->load_config($site_id);
    }

    /**
     * 加载 AI 配置
     */
    private function load_config($site_id) {
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];

        if ($site_id > 0) {
            $row = $this->db->fetch_first("
                SELECT * FROM `{$tablepre}ai_config`
                WHERE site_id = " . (int)$site_id . " LIMIT 1
            ");
            if ($row) {
                $this->config = $row;
                return;
            }
        }

        $global = $this->db->fetch_first("
            SELECT * FROM `{$tablepre}ai_config`
            WHERE site_id = 0 LIMIT 1
        ");

        $this->config = $global ?: array(
            'api_base_url' => 'https://api.deepseek.com/v1',
            'api_key' => '',
            'model' => 'deepseek-chat',
            'max_tokens' => 2048,
            'temperature' => 0.7,
            'timeout' => 60,
        );
    }

    /**
     * 生成优化建议
     * @param array $competitor 竞品站点数据
     * @param array $analysis_data 采集数据
     * @return array 结构化建议
     */
    public function generate($competitor, $analysis_data) {
        if (empty($this->config['api_key'])) {
            return $this->fallback_suggestions($competitor);
        }

        $prompt = $this->build_prompt($competitor, $analysis_data);
        $response = $this->call_api($prompt);

        if ($response) {
            return $this->parse_suggestions($response);
        }

        return $this->fallback_suggestions($competitor);
    }

    /**
     * 构建 prompt
     */
    private function build_prompt($competitor, $analysis_data) {
        $prompt = "你是专业的 SEO 分析专家。请基于以下竞品分析数据，生成竞争优化建议。\n\n";
        $prompt .= "竞品站点：{$competitor['name']}\n";
        $prompt .= "URL：{$competitor['url']}\n\n";
        $prompt .= "采集数据：\n";

        if (isset($analysis_data['domain_authority'])) {
            $prompt .= "- 域名权重评分：{$analysis_data['domain_authority']['authority_score']}\n";
        }

        if (isset($analysis_data['title_strategy'])) {
            $title = $analysis_data['title_strategy'];
            $prompt .= "- 标题长度：{$title['title_length']} 字符\n";
            $prompt .= "- 描述长度：{$title['description_length']} 字符\n";
            $prompt .= "- H1 标签数：" . count($title['h1_tags']) . "\n";
        }

        if (isset($analysis_data['template_structure'])) {
            $template = $analysis_data['template_structure'];
            $prompt .= "- CSS 框架：" . implode(', ', $template['css_frameworks']) . "\n";
            $prompt .= "- JS 库：" . implode(', ', $template['js_libraries']) . "\n";
            $prompt .= "- 页面大小：{$template['page_size_kb']} KB\n";
        }

        if (isset($analysis_data['server_info'])) {
            $server = $analysis_data['server_info'];
            $prompt .= "- 服务器：{$server['server']}\n";
            $prompt .= "- CDN：{$server['cdn']}\n";
        }

        $prompt .= "\n请从以下维度给出具体建议（输出 JSON 格式）：\n";
        $prompt .= "1. title_optimization: 标题优化建议数组\n";
        $prompt .= "2. content_strategy: 内容策略建议数组\n";
        $prompt .= "3. technical_seo: 技术 SEO 建议数组\n";
        $prompt .= "4. user_experience: 用户体验建议数组\n";

        return $prompt;
    }

    /**
     * 调用 AI API
     */
    protected function call_api($prompt) {
        $url = rtrim($this->config['api_base_url'], '/') . '/chat/completions';

        $messages = array(
            array('role' => 'user', 'content' => $prompt),
        );

        $post_data = array(
            'model' => $this->config['model'],
            'messages' => $messages,
            'temperature' => 0.7,
            'max_tokens' => 1000,
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->config['timeout']);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->config['api_key'],
        ));

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code === 200 && $response) {
            return $response;
        }

        return '';
    }

    /**
     * 解析 AI 响应
     */
    private function parse_suggestions($response) {
        $data = json_decode($response, true);
        if (!$data || !isset($data['choices'][0]['message']['content'])) {
            return $this->fallback_suggestions(array('name' => 'Unknown'));
        }

        $content = $data['choices'][0]['message']['content'];

        // 尝试提取 JSON
        if (preg_match('/\{.*\}/s', $content, $m)) {
            $decoded = json_decode($m[0], true);
            if ($decoded) {
                return $decoded;
            }
        }

        return $this->fallback_suggestions(array('name' => 'Unknown'));
    }

    /**
     * 降级建议（API 不可用时）
     */
    private function fallback_suggestions($competitor) {
        return array(
            'title_optimization' => array('确保 title 长度在 50-60 字符之间'),
            'content_strategy' => array('定期更新高质量内容'),
            'technical_seo' => array('检查页面加载速度'),
            'user_experience' => array('优化移动端体验'),
        );
    }
}
