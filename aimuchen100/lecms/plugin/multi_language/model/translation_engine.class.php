<?php
/**
 * AI 翻译引擎
 * 调用 AI API 将中文文章翻译为目标语言，复用 ai-content-factory 的 API 配置
 */
class translation_engine {

    private $site_id;
    private $config;
    private $last_error = '';
    private $db;

    /**
     * 默认 Prompt 模板
     */
    const DEFAULT_PROMPT_TEMPLATE = <<<EOT
你是专业的多语言内容翻译专家。请将以下中文文章翻译为 {target_language}，
保持原文的 Markdown 格式、标题层级和链接结构。
保留所有 HTML 标签不变。关键词 {keywords} 必须准确翻译。
保持 {style} 的写作风格。

用户输入：
标题：{title}
分类：{category}
关键词：{keywords}
内容：
{content}
EOT;

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

        // 优先站点级配置
        if ($this->site_id > 0 && $this->db) {
            $site_id = (int)$this->site_id;
            $row = $this->db->fetch_first("
                SELECT * FROM `{$tablepre}ai_config`
                WHERE site_id = {$site_id} LIMIT 1
            ");
            if ($row) {
                if (empty($row['api_key'])) {
                    throw new \RuntimeException('AI 翻译引擎未配置：站点级配置的 API 密钥为空');
                }
                $this->config = $row;
                return;
            }
        }

        // 全局配置
        $global = $this->db ? $this->db->fetch_first("
            SELECT * FROM `{$tablepre}ai_config`
            WHERE site_id = 0 LIMIT 1
        ") : null;

        if (!$global || empty($global['api_key'])) {
            throw new \RuntimeException('AI 翻译引擎未配置：请在后台配置 le_ai_config 表的 API 密钥');
        }

        $this->config = $global;
    }

    /**
     * 翻译单篇文章
     * @param int $article_id 原文文章ID
     * @param string $language 目标语言代码
     * @param string|null $prompt_template 自定义 prompt 模板（null=使用全局默认）
     * @return array|false 成功返回 ['title'=>..., 'content'=>...]，失败返回 false
     */
    public function translate($article_id, $language, $prompt_template = null) {
        $article_id = (int)$article_id;
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];

        // 获取原文
        $article = $this->db ? $this->db->fetch_first("
            SELECT a.id, a.title, d.content, a.cid
            FROM `{$tablepre}cms_article` a
            LEFT JOIN `{$tablepre}cms_article_data` d ON a.id = d.id
            WHERE a.id = {$article_id} LIMIT 1
        ") : null;

        if (!$article) {
            return false;
        }

        // 获取分类名称
        $cat = $this->db ? $this->db->fetch_first("SELECT name FROM `{$tablepre}category` WHERE cid = " . (int)$article['cid'] . " LIMIT 1") : null;
        $category_name = $cat ? $cat['name'] : '';

        // 选定 prompt 模板：自定义优先（支持 {target_language}/{title}/{category}/{content}/{keywords}/{style} 占位符），
        // 回退到全局默认模板
        $template = (!empty($prompt_template) && is_string($prompt_template)) ? $prompt_template : self::DEFAULT_PROMPT_TEMPLATE;

        // 调用 AI 翻译
        $prompt = strtr($template, array(
            '{target_language}' => $language,
            '{keywords}' => '',
            '{style}' => '专业',
            '{title}' => $article['title'],
            '{category}' => $category_name,
            '{content}' => $article['content'],
        ));

        $result = $this->call_ai($prompt);
        if (!$result) {
            return false;
        }

        return array(
            'title' => $article['title'],
            'content' => $result,
        );
    }

    /**
     * 调用 AI API
     * 注意：protected（非 private）—— 允许子类覆盖用于测试 mock 与自定义实现
     */
    protected function call_ai($prompt) {
        // ... AI API call implementation ...
        return null;
    }

    /**
     * 获取最后错误信息
     */
    public function get_last_error() {
        return $this->last_error;
    }
}
