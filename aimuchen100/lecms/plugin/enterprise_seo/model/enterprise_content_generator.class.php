<?php
/**
 * 企业内容生成器
 * 使用 AI API 生成企业相关内容（公司介绍、产品描述、新闻稿等）
 */
class enterprise_content_generator {

    private $site_id;
    private $config;
    private $db;

    public function __construct($site_id = 0, $db = null) {
        $this->site_id = (int)$site_id;
        $this->db = $db;
        $this->load_config();
    }

    /**
     * 加载 AI 配置（复用 ai-content-factory 的 le_ai_config 表）
     */
    private function load_config() {
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];

        if ($this->site_id > 0) {
            $row = $this->db ? $this->db->fetch_first("
                SELECT * FROM `{$tablepre}ai_config`
                WHERE site_id = " . (int)$this->site_id . " LIMIT 1
            ") : null;
            if ($row) {
                $this->config = $row;
                return;
            }
        }

        $global = $this->db ? $this->db->fetch_first("
            SELECT * FROM `{$tablepre}ai_config`
            WHERE site_id = 0 LIMIT 1
        ") : null;

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
     * 为企业生成所有内容
     * @param int $enterprise_id 企业站点ID
     * @param array $enterprise_data 企业基础数据
     * @return bool
     */
    public function generate_for_enterprise($enterprise_id, $enterprise_data) {
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];

        // 生成公司介绍
        $description = $this->generate_description($enterprise_data);

        // 生成产品列表
        $products = $this->generate_products($enterprise_data);

        // 生成案例列表
        $cases = $this->generate_cases($enterprise_data);

        // 生成新闻动态
        $news = $this->generate_news($enterprise_data);

        // 更新企业站点数据（JSON 值用单引号加倍转义，MySQL/SQLite 均兼容；addslashes 的 \" 在 SQLite 会存成字面反斜杠）
        $esc = function ($s) { return str_replace("'", "''", $s); };
        $this->db->query("
            UPDATE `{$tablepre}enterprise_site`
            SET description = '" . $esc($description) . "',
                products = '" . $esc(json_encode($products)) . "',
                cases = '" . $esc(json_encode($cases)) . "',
                news = '" . $esc(json_encode($news)) . "'
            WHERE id = " . (int)$enterprise_id . "
        ");

        return true;
    }

    /**
     * 生成公司介绍
     */
    private function generate_description($data) {
        $prompt = "请为以下企业撰写一段专业、正式的公司介绍（200-300字）：\n\n";
        $prompt .= "企业名称：{$data['enterprise_name']}\n";
        $prompt .= "所属行业：{$data['industry']}\n";
        $prompt .= "企业地址：{$data['address']}\n";
        if (!empty($data['description'])) {
            $prompt .= "企业简介：{$data['description']}\n";
        }

        return $this->call_ai($prompt, 300);
    }

    /**
     * 生成产品列表
     */
    private function generate_products($data) {
        $prompt = "请为以下企业生成3-5个产品/服务项目，输出 JSON 格式：\n";
        $prompt .= "企业名称：{$data['enterprise_name']}\n";
        $prompt .= "所属行业：{$data['industry']}\n\n";
        $prompt .= "格式：[{\"name\": \"产品名称\", \"description\": \"产品描述\"}, ...]";

        $result = $this->call_ai_json($prompt);
        return $result ?: array();
    }

    /**
     * 生成案例列表
     */
    private function generate_cases($data) {
        $prompt = "请为以下企业生成2-3个成功案例，输出 JSON 格式：\n";
        $prompt .= "企业名称：{$data['enterprise_name']}\n";
        $prompt .= "所属行业：{$data['industry']}\n\n";
        $prompt .= "格式：[{\"title\": \"案例标题\", \"description\": \"案例描述\"}, ...]";

        $result = $this->call_ai_json($prompt);
        return $result ?: array();
    }

    /**
     * 生成新闻动态
     */
    private function generate_news($data) {
        $prompt = "请为以下企业生成2-3条最新新闻动态，输出 JSON 格式：\n";
        $prompt .= "企业名称：{$data['enterprise_name']}\n\n";
        $prompt .= "格式：[{\"title\": \"新闻标题\", \"date\": \"2026-07-26\"}, ...]";

        $result = $this->call_ai_json($prompt);
        return $result ?: array();
    }

    /**
     * 调用 AI API（返回文本）
     */
    protected function call_ai($prompt, $max_length = 500) {
        $url = rtrim($this->config['api_base_url'], '/') . '/chat/completions';

        $messages = array(
            array('role' => 'user', 'content' => $prompt),
        );

        $post_data = array(
            'model' => $this->config['model'],
            'messages' => $messages,
            'temperature' => 0.7,
            'max_tokens' => $max_length,
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
        $curl_error = curl_error($ch);
        curl_close($ch);

        // 检查 curl 执行错误
        if ($curl_error) {
            return '';
        }

        // 检查 HTTP 状态码
        if ($http_code !== 200) {
            return '';
        }

        if ($response) {
            $data = json_decode($response, true);
            if ($data && isset($data['choices'][0]['message']['content'])) {
                return trim($data['choices'][0]['message']['content']);
            }
        }

        return '';
    }

    /**
     * 调用 AI API（返回 JSON）
     */
    private function call_ai_json($prompt) {
        $result = $this->call_ai($prompt, 1000);

        // 尝试提取 JSON
        if (preg_match('/\[.*\]/s', $result, $m)) {
            $decoded = json_decode($m[0], true);
            if ($decoded) {
                return $decoded;
            }
        }

        return array();
    }
}
