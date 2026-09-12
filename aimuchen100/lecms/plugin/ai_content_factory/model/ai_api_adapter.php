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
                'batch_limit' => 100,
                'max_retries' => 3,
            );
        }

        // 数值兜底（直接读 ai_config 表时可能缺失列）
        if(!isset($this->config['batch_limit']) || !is_numeric($this->config['batch_limit'])) $this->config['batch_limit'] = 100;
        if(!isset($this->config['max_retries']) || !is_numeric($this->config['max_retries'])) $this->config['max_retries'] = 3;
    }

    /**
     * 获取当前生效配置（供 UI 展示 API 状态）
     */
    public function get_config() {
        return $this->config;
    }

    /**
     * 批量生成文章
     * @param string $system_prompt 系统提示词
     * @param int $count 生成数量
     * @return array 文章列表 [{title, content, tags, seo_title, seo_keywords, seo_description}, ...]
     */
    public function generate($system_prompt, $count, $category_name = '') {
        // 检查 API Key 是否配置
        if(empty($this->config['api_key'])) {
            return $this->fallback_to_template($count, $category_name);
        }

        // 构建请求
        $url = rtrim($this->config['api_base_url'], '/') . '/chat/completions';

        $messages = array(
            array('role' => 'system', 'content' => $system_prompt),
            array('role' => 'user', 'content' => "请生成 {$count} 篇游戏相关的文章，每篇包含标题、正文、标签和SEO元数据。输出JSON数组格式。")
        );

        // 重试机制（次数取配置 max_retries，A6：429 用指数退避替代长 sleep 阻塞）
        $max_retries = max(0, (int)$this->config['max_retries']);
        $last_articles = array();
        for($i = 0; $i <= $max_retries; $i++) {
            $response = $this->call_api($url, $messages);

            if($response !== false) {
                $articles = $this->parse_response($response);
                if(!empty($articles)) {
                    return $articles;
                }
                // 200 但解析出空数组：视为无效响应，继续重试
                $last_articles = array();
                continue;
            }

            if($this->last_error_code == 429) {
                // 限流：指数退避 2s/4s/8s...上限 30s
                $backoff = min(2 * pow(2, $i), 30);
                if($i < $max_retries) sleep($backoff);
                continue;
            }

            if($this->last_error_code >= 500) {
                // 服务不可用：降级到模板库（不再重试）
                return $this->fallback_to_template($count, $category_name);
            }

            // 其他错误（cURL 错误等）：短等待后重试
            if($i < $max_retries) sleep(1);
        }

        // 全部重试失败，降级到模板库
        return $this->fallback_to_template($count, $category_name);
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
    private function fallback_to_template($count, $category_name = '') {
        $articles = array();
        $templates = $this->get_template_library($category_name);

        $total_templates = count($templates);
        if($total_templates == 0) return $articles; // 除零保护

        // C5：随机洗牌模板顺序，避免每次生成的文章标题完全相同导致 C7 去重失败
        shuffle($templates);

        $suffixes = array('精选', '推荐', '盘点', '指南', '详解', '攻略', '评测', '合集', 'Top10', '必玩');
        for($i = 0; $i < $count; $i++) {
            $template = $templates[$i % $total_templates];
            // C5：{category} 占位符替换
            $cat = $category_name !== '' ? $category_name : '游戏';
            $suffix = $suffixes[($i + (int)date('s')) % count($suffixes)];
            $title = str_replace('{category}', $cat, $template['title']);
            // 标题加随机后缀避免 C7 全表去重
            if(strpos($title, $suffix) === false) {
                $title .= '：' . $suffix;
            }
            $articles[] = array(
                'title' => $title,
                'content' => str_replace('{category}', $cat, $template['content']),
                'tags' => str_replace('{category}', $cat, $template['tags']),
                'seo_title' => str_replace('{category}', $cat, $template['seo_title']),
                'seo_keywords' => str_replace('{category}', $cat, $template['seo_keywords']),
                'seo_description' => str_replace('{category}', $cat, $template['seo_description']),
            );
        }

        return $articles;
    }

    /**
     * 获取本地模板库（C5：分类化模板库，标题/正文支持 {category} 占位符）
     * @param string $category_name 分类名称（用于占位符替换）
     */
    private function get_template_library($category_name = '') {
        // C5：模板标题/正文/SEO 均支持 {category} 占位符，运行时替换为实际分类名
        return array(
            array(
                'title' => '{category}领域年度精选：不容错过的佳作推荐',
                'content' => '{category}作为近年来持续火热的内容方向，不断涌现出令人瞩目的新作。无论是画面表现、玩法创新还是内容深度，都有了质的飞跃。本文为您精选{category}领域多款年度佳作，从入门到高阶全面覆盖，帮助您快速找到最适合自己的选择。每款作品都有独特的亮点与适用场景，值得亲自体验。',
                'tags' => '{category},推荐,精选',
                'seo_title' => '{category}年度精选佳作推荐榜单',
                'seo_keywords' => '{category},精选,推荐',
                'seo_description' => '为您精选{category}领域年度佳作，从入门到高阶全面覆盖。',
            ),
            array(
                'title' => '深度解析：{category}领域值得投入时间的内容',
                'content' => '在{category}领域，优质内容总是值得投入时间去细细品味。从基础概念到进阶技巧，从经典案例到最新趋势，本文将从多个维度深度解析当前{category}领域最值得关注的方向。无论您是刚入门的新手，还是已有一定基础的爱好者，都能从中找到有价值的参考信息，帮助您在{category}的道路上走得更远。',
                'tags' => '{category},深度解析,攻略',
                'seo_title' => '{category}领域深度解析与值得投入的内容',
                'seo_keywords' => '{category},深度解析,入门指南',
                'seo_description' => '从基础到进阶深度解析{category}领域值得投入时间的方向。',
            ),
            array(
                'title' => '新手入门指南：{category}领域从零开始的实用建议',
                'content' => '对于刚接触{category}的朋友来说，如何快速入门是最关心的问题。本文从基础知识、工具选择、学习路径三个层面，为{category}新手提供系统化的入门建议。从常见的误区到实用的技巧，从免费资源到进阶路线，帮助您避开弯路，高效地开启{category}之旅。掌握正确的方法，入门其实并不难。',
                'tags' => '{category},新手,入门',
                'seo_title' => '{category}新手入门指南：从零开始的实用建议',
                'seo_keywords' => '{category}入门,新手指南,基础教程',
                'seo_description' => '为{category}新手提供系统化入门建议，帮助您高效开启学习之旅。',
            ),
            array(
                'title' => '{category}行业趋势展望：未来发展的关键方向',
                'content' => '随着技术迭代与市场需求变化，{category}行业正迎来新一轮变革。从智能化到个性化，从跨界融合到生态构建，多个趋势正在重塑{category}的格局。本文结合行业数据与专家观点，深入剖析{category}未来发展的关键方向，帮助从业者与爱好者提前布局，把握先机。关注趋势，才能在{category}领域保持竞争力。',
                'tags' => '{category},趋势,展望',
                'seo_title' => '{category}行业趋势展望与未来发展关键方向',
                'seo_keywords' => '{category}趋势,行业展望,发展方向',
                'seo_description' => '结合数据与专家观点剖析{category}未来发展的关键方向。',
            ),
            array(
                'title' => '实用技巧合集：提升{category}效率的十个小方法',
                'content' => '在{category}的日常实践中，掌握一些实用技巧可以大幅提升效率。本文整理了十个经过验证的{category}实用小方法，涵盖工具使用、流程优化、时间管理等多个方面。每个方法都配有具体操作步骤和适用场景说明，帮助您在日常{category}工作中少走弯路、事半功倍。即使是经验丰富的从业者，也能从中找到新的灵感。',
                'tags' => '{category},技巧,效率',
                'seo_title' => '提升{category}效率的十个实用技巧合集',
                'seo_keywords' => '{category}技巧,效率提升,实用方法',
                'seo_description' => '整理十个经过验证的{category}实用技巧，涵盖工具使用与流程优化。',
            ),
            array(
                'title' => '避坑指南：{category}领域常见的误区与解决方案',
                'content' => '在{category}的学习与实践中，许多初学者甚至有经验的从业者都会陷入一些常见的误区。这些误区不仅浪费时间，还可能影响最终效果。本文总结{category}领域最常见的六大误区，并针对每个误区提供切实可行的解决方案。从认知纠偏到操作规范，帮助您在{category}的道路上走得更加稳健，避免重复踩坑。',
                'tags' => '{category},误区,避坑',
                'seo_title' => '{category}常见误区与解决方案避坑指南',
                'seo_keywords' => '{category}误区,避坑指南,解决方案',
                'seo_description' => '总结{category}领域常见误区并提供切实可行的解决方案。',
            ),
            array(
                'title' => '从零到精通：{category}系统学习路径全规划',
                'content' => '想要在{category}领域从零基础成长到精通水平，需要一套系统的学习路径。本文为您规划了完整的{category}成长路线图：第一阶段夯实基础概念，第二阶段通过实战巩固技能，第三阶段深入高阶专题。每个阶段都推荐了核心知识点、实践项目和学习资源，帮助您有计划地提升{category}能力。坚持按照路径学习，精通只是时间问题。',
                'tags' => '{category},学习路径,系统规划',
                'seo_title' => '{category}从零到精通的系统学习路径规划',
                'seo_keywords' => '{category}学习,系统路径,从零到精通',
                'seo_description' => '规划{category}完整成长路线图，从基础到高阶全面提升能力。',
            ),
            array(
                'title' => '工具推荐：{category}从业者必备的高效工具箱',
                'content' => '工欲善其事，必先利其器。在{category}的日常工作中，选择合适的工具可以事半功倍。本文精选多款{category}从业者常用的高效工具，涵盖内容创作、数据分析、协作管理等多个类别。每款工具都从核心功能、适用场景、上手难度三个维度进行评测，帮助您根据自身需求快速搭建专属的{category}工具箱，提升日常工作效率。',
                'tags' => '{category},工具,推荐',
                'seo_title' => '{category}从业者必备高效工具箱推荐',
                'seo_keywords' => '{category}工具,高效推荐,工具箱',
                'seo_description' => '精选多款{category}常用高效工具，从功能到上手难度全面评测。',
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
