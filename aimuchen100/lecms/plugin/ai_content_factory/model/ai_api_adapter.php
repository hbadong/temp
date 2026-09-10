<?php
defined('ROOT_PATH') or exit;
/**
 * AI API 适配层
 * 支持自定义 API Base URL、API Key、模型名
 * 兼容 OpenAI API 格式
 *
 * 修复（对照验证发现）：
 * - CODE-3: 补充 defined('ROOT_PATH') or exit; 安全头
 * - REQ-03-AC3: parse_response 剥离 markdown ```json fence 再解码
 * - REQ-03-AC6: 降级模板库扩充至 6 篇不同主题（原仅 1 篇 → 重复内容）
 * - 除零保护：fallback_to_template 在模板库为空时不除零
 */

class ai_api_adapter {
    private $config;
    private $db;
    private $last_error_code = 0;
    private $last_error_message = '';

    public function __construct($site_id = 0, $db = null) {
        $this->db = $db;
        $this->load_config($site_id);
    }

    /**
     * 加载 API 配置
     */
    private function load_config($site_id) {
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];
        $site_id = (int)$site_id;

        // 优先读取站点级配置
        if($site_id > 0) {
            $row = $this->db ? $this->db->fetch_first("
                SELECT * FROM `{$tablepre}ai_config`
                WHERE site_id = {$site_id}
                LIMIT 1
            ") : null;
            if($row) {
                $this->config = $row;
                return;
            }
        }

        // 回退到全局配置
        $global = $this->db ? $this->db->fetch_first("
            SELECT * FROM `{$tablepre}ai_config`
            WHERE site_id = 0
            LIMIT 1
        ") : null;

        if($global) {
            $this->config = $global;
        } else {
            // 默认配置
            $this->config = array(
                'api_base_url' => 'https://api.deepseek.com/v1',
                'api_key' => '',
                'model' => 'deepseek-chat',
                'max_tokens' => 2048,
                'temperature' => 0.7,
                'timeout' => 60,
            );
        }
    }

    /**
     * 批量生成文章
     * @param string $system_prompt 系统提示词
     * @param int $count 生成数量
     * @return array 文章列表 [{title, content, tags, seo_title, seo_keywords, seo_description}, ...]
     */
    public function generate($system_prompt, $count) {
        // 检查 API Key 是否配置
        if(empty($this->config['api_key'])) {
            return $this->fallback_to_template($count);
        }

        // 构建请求
        $url = rtrim($this->config['api_base_url'], '/') . '/chat/completions';

        $messages = array(
            array('role' => 'system', 'content' => $system_prompt),
            array('role' => 'user', 'content' => "请生成 {$count} 篇游戏相关的文章，每篇包含标题、正文、标签和SEO元数据。输出JSON数组格式。")
        );

        // 重试机制（最多3次）
        $max_retries = 3;
        for($i = 0; $i < $max_retries; $i++) {
            $response = $this->call_api($url, $messages);

            if($response !== false) {
                $articles = $this->parse_response($response);
                if(!empty($articles)) {
                    return $articles;
                }
            }

            // 根据错误类型处理
            if($this->last_error_code == 429) {
                // 限流：等待60秒后重试
                sleep(60);
                continue;
            }

            if($this->last_error_code >= 500) {
                // 服务不可用：降级到模板库
                return $this->fallback_to_template($count);
            }

            // 其他错误：短暂等待后重试
            sleep(5);
        }

        // 全部重试失败，降级到模板库
        return $this->fallback_to_template($count);
    }

    /**
     * 调用 AI API
     */
    private function call_api($url, $messages) {
        $post_data = array(
            'model' => $this->config['model'],
            'messages' => $messages,
            'temperature' => (float)$this->config['temperature'],
            'max_tokens' => (int)$this->config['max_tokens'],
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

        // 检查 cURL 执行错误（网络超时、DNS 失败等）
        if($response === false) {
            $this->last_error_code = 0; // cURL 错误，非 HTTP 状态码
            $this->last_error_message = curl_error($ch);
            curl_close($ch);
            return false;
        }

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->last_error_code = $http_code;
        curl_close($ch);

        if($http_code == 200) {
            return $response;
        }

        return false;
    }

    /**
     * 解析 API 响应
     * REQ-03-AC3：先剥离 ```json ... ``` markdown fence 再解码
     */
    private function parse_response($json) {
        $data = json_decode($json, true);
        if(!$data || !isset($data['choices'][0]['message']['content'])) {
            return array();
        }

        $content = $data['choices'][0]['message']['content'];

        // 剥离 markdown code fence：```json ... ``` 或 ``` ... ```
        $trimmed = trim($content);
        if(preg_match('/^```(?:json|php|text)?\s*(.*?)\s*```$/is', $trimmed, $m)) {
            $trimmed = trim($m[1]);
        }

        // 尝试解析 JSON 数组
        $articles = json_decode($trimmed, true);
        if(!is_array($articles)) {
            return array(); // 非法 JSON
        }
        if(empty($articles)) {
            return array(); // AI 返回空数组
        }
        // 单个对象（关联数组，非数字索引列表）→ 包装为数组
        if(array_keys($articles) !== range(0, count($articles) - 1)) {
            $articles = array($articles);
        }

        // 标准化每篇文章的结构
        $result = array();
        foreach($articles as $article) {
            if(!is_array($article)) continue;
            $result[] = array(
                'title' => isset($article['title']) ? $article['title'] : '',
                'content' => isset($article['content']) ? $article['content'] : '',
                'tags' => isset($article['tags']) ? (is_array($article['tags']) ? implode(',', $article['tags']) : $article['tags']) : '',
                'seo_title' => isset($article['seo_title']) ? $article['seo_title'] : '',
                'seo_keywords' => isset($article['seo_keywords']) ? $article['seo_keywords'] : '',
                'seo_description' => isset($article['seo_description']) ? $article['seo_description'] : '',
            );
        }

        return $result;
    }

    /**
     * 降级：使用本地模板库
     */
    private function fallback_to_template($count) {
        $articles = array();
        $templates = $this->get_template_library();

        $total_templates = count($templates);
        if($total_templates == 0) return $articles; // 除零保护

        for($i = 0; $i < $count; $i++) {
            $template = $templates[$i % $total_templates];
            $articles[] = array(
                'title' => $template['title'],
                'content' => $template['content'],
                'tags' => $template['tags'],
                'seo_title' => $template['seo_title'],
                'seo_keywords' => $template['seo_keywords'],
                'seo_description' => $template['seo_description'],
            );
        }

        return $articles;
    }

    /**
     * 获取本地模板库（6 篇预设游戏文章模板，主题互不相同避免重复内容）
     * REQ-03-AC6：原仅 1 篇 → 扩充为动作/RPG/休闲/竞技/独立/模拟 6 大主题
     */
    private function get_template_library() {
        return array(
            array(
                'title' => '2024年最受欢迎的动作游戏推荐',
                'content' => '动作游戏一直是游戏市场上最受欢迎的类型之一。这类游戏以爽快的打击感、紧张刺激的战斗节奏和精美的画面表现著称。本期为您精选多款年度动作大作，涵盖硬核格斗、开放世界与高速跑酷等细分类型，每一款都值得亲自上手体验。无论您是追求操作极限的硬核玩家，还是享受视觉盛宴的休闲玩家，都能在榜单中找到心仪之作。',
                'tags' => '动作,冒险,推荐',
                'seo_title' => '2024年最受欢迎的动作游戏推荐榜单',
                'seo_keywords' => '动作游戏,冒险游戏,游戏推荐',
                'seo_description' => '本文为您推荐2024年最受欢迎的动作游戏，包含最新热门游戏评测与下载指引。',
            ),
            array(
                'title' => '深度解析：2024年最值得投入的RPG角色扮演游戏',
                'content' => '角色扮演游戏（RPG）为玩家提供沉浸式的世界观与自由的角色成长体系。无论是经典日式回合制，还是强调自由探索的美式开放世界，都能带来数十小时的高质量体验。本文从剧情深度、战斗系统、支线内容与画面表现四个维度，深度解析今年最值得投入时间的RPG作品，帮助您在众多新作中找到最适合自己的冒险之旅。',
                'tags' => 'RPG,角色扮演,剧情',
                'seo_title' => '2024年最值得投入的RPG游戏深度解析',
                'seo_keywords' => 'RPG游戏,角色扮演,单机游戏',
                'seo_description' => '从剧情到战斗系统深度解析2024年最值得投入的RPG角色扮演游戏，助您选择心仪佳作。',
            ),
            array(
                'title' => '休闲益智游戏精选：轻松上手，快乐加倍',
                'content' => '在快节奏的生活中，休闲益智游戏凭借轻松的上手门槛与碎片化的游玩节奏，成为众多玩家的首选。三消、解谜、模拟经营等品类不仅缓解压力，还能锻炼思维。本文精选多款画面清新、玩法新颖的休闲佳作，无论通勤路上还是午后小憩，都能随时开启一段治愈的游戏时光。',
                'tags' => '休闲,益智,解谜',
                'seo_title' => '2024年休闲益智游戏精选推荐',
                'seo_keywords' => '休闲游戏,益智游戏,解谜游戏',
                'seo_description' => '精选2024年最受欢迎的休闲益智游戏，轻松上手、快乐加倍，适合碎片时间游玩。',
            ),
            array(
                'title' => '电竞时代：2024年最热门的竞技对战游戏盘点',
                'content' => '电子竞技产业持续升温，MOBA、射击、策略等竞技类游戏不仅带来紧张刺激的对抗体验，更构建起庞大的职业赛事生态。本文为您盘点当前最热门的竞技对战游戏，从赛事规模、英雄/角色体系、平衡性与上手门槛多个角度分析，带您快速了解当前电竞版图，找到适合上分的主战场。',
                'tags' => '电竞,竞技,对战',
                'seo_title' => '2024年最热门的电竞竞技对战游戏盘点',
                'seo_keywords' => '电竞游戏,竞技游戏,MOBA',
                'seo_description' => '盘点2024年最热门的电竞竞技对战游戏，从赛事到玩法全面分析当前电竞版图。',
            ),
            array(
                'title' => '独立游戏之光：那些值得一玩的小众创意之作',
                'content' => '独立游戏凭借天马行空的创意与真诚的制作态度，常常带来超越商业大作的独特体验。像素美学、解谜叙事、音乐互动……独立开发者们不断突破游戏表达的边界。本文精心挑选多款口碑爆棚的独立游戏，它们也许画面朴素，但创意与玩法足以让人眼前一亮，是追求新鲜体验玩家的不二之选。',
                'tags' => '独立游戏,创意,小众',
                'seo_title' => '值得一玩的独立游戏推荐：小众创意佳作',
                'seo_keywords' => '独立游戏,创意游戏,小众游戏',
                'seo_description' => '推荐多款口碑爆棚的独立游戏，创意玩法与独特表达带你发现游戏艺术的另一面。',
            ),
            array(
                'title' => '模拟经营游戏指南：打造属于你的梦幻王国',
                'content' => '模拟经营游戏让玩家从零开始建设属于自己的城市、农场、乐园甚至整个文明。资源规划、人口管理、产业链构建……每一步决策都考验玩家的远见与耐心。本文从新手入门到高阶运营，系统梳理多款经典模拟经营作品的核心玩法与特色，助您轻松上手，享受从无到有的建设成就感。',
                'tags' => '模拟经营,城市建设,策略',
                'seo_title' => '模拟经营游戏指南：从零打造梦幻王国',
                'seo_keywords' => '模拟经营,城市建设,策略游戏',
                'seo_description' => '系统梳理经典模拟经营游戏的入门技巧与玩法特色，助你打造属于自己的梦幻王国。',
            ),
        );
    }

    /**
     * 获取最后错误码
     */
    public function get_last_error_code() {
        return $this->last_error_code;
    }

    /**
     * 获取最后错误信息（cURL 错误详情）
     */
    public function get_last_error_message() {
        return $this->last_error_message;
    }
}
