<?php
/**
 * BlockRender 模型 — 9 个 Block 渲染逻辑
 *
 * 三层主题继承链：
 *   - 全局主题模板（view/{theme}/{name}.htm）
 *   - 站点级覆写（view/{theme}/{name}_{site_id}.htm）
 *   - 调用方 Block 参数覆写
 *
 * 合并优先级：全局 < 站点配置 < 调用方参数
 */

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__, 4) . '/');
}

class BlockRender
{
    private $site_id;
    private $db;
    private $block_config = [];

    public function __construct($site_id, $db)
    {
        $this->site_id = (int)$site_id;
        $this->db = $db;
        $this->loadBlockConfig();
    }

    /**
     * 加载站点 Block 配置
     */
    private function loadBlockConfig()
    {
        $this->block_config = [];
        if (!$this->db) {
            return;
        }

        $tp = $_ENV['_config']['db']['master']['tablepre'];
        $rows = $this->db->fetch_all(
            "SELECT block_name, params FROM `{$tp}cms_block_config`
             WHERE site_id = {$this->site_id} AND enabled = 1
             ORDER BY id ASC"
        ) ?: [];

        foreach ($rows as $row) {
            $params = json_decode($row['params'], true);
            if (!is_array($params)) {
                $params = [];
            }
            $this->block_config[$row['block_name']] = array_merge(
                isset($this->block_config[$row['block_name']]) ? $this->block_config[$row['block_name']] : [],
                $params
            );
        }
    }

    /**
     * 渲染 Block
     *
     * @param string $block_name Block 名称
     * @param array $params Block 参数
     * @param array $context 渲染上下文
     * @return string 渲染结果
     */
    public function render($block_name, $params = [], $context = [])
    {
        // 合并参数：全局 < 站点配置 < 调用方参数
        $merged_params = $this->mergeParams($block_name, $params);

        // 解析模板路径
        $template_path = $this->resolve_template_path($block_name, $this->getCurrentTheme());

        if (!$template_path || !file_exists($template_path)) {
            return '';
        }

        // include 前再次校验：模板必须位于 view/{theme}/ 目录内，拒绝任意文件包含
        $real = realpath($template_path);
        $theme_dir = realpath(ROOT_PATH . 'view/' . $this->getCurrentTheme() . '/');
        if (!$real || !$theme_dir || strpos($real, $theme_dir) !== 0) {
            return '';
        }

        // 渲染模板：参数以固定前缀变量形式暴露，避免 extract() 覆盖局部变量
        $block_param_ = $merged_params;
        $context_ = $context;
        ob_start();
        include $real;
        return ob_get_clean();
    }

    /**
     * 解析模板路径
     *
     * 优先级：
     * 1. 站点级覆写：{name}_{site_id}.htm
     * 2. 主题模式模板：{name}_{mode}.htm（仅允许 lite/standard/rich）
     * 3. 主题模板：{name}.htm
     * 4. 默认模板：view/default/{name}.htm
     *
     * @param string $name 模板名称
     * @param string $theme 主题名称
     * @param string|null $mode 性能模式（lite/standard/rich）
     * @return string|null 模板路径
     */
    public function resolve_template_path($name, $theme = 'default', $mode = null)
    {
        // 允许的性能模式
        $allowed_modes = ['lite', 'standard', 'rich'];

        // 1. 站点级覆写（最高优先级）
        $site_override = ROOT_PATH . "view/{$theme}/{$name}_{$this->site_id}.htm";
        if (file_exists($site_override)) {
            return $site_override;
        }

        // 2. 主题模式模板（如果提供了合法模式）
        if ($mode && in_array($mode, $allowed_modes)) {
            $mode_template = ROOT_PATH . "view/{$theme}/{$name}_{$mode}.htm";
            if (file_exists($mode_template)) {
                return $mode_template;
            }
        }

        // 3. 主题模板
        $theme_template = ROOT_PATH . "view/{$theme}/{$name}.htm";
        if (file_exists($theme_template)) {
            return $theme_template;
        }

        // 4. 默认模板
        $default_template = ROOT_PATH . "view/default/{$name}.htm";
        if (file_exists($default_template)) {
            return $default_template;
        }

        return null;
    }

    /**
     * 合并参数（三层覆盖）
     */
    private function mergeParams($block_name, $caller_params)
    {
        // 全局默认值
        $global = [];

        // 站点配置值
        $site = isset($this->block_config[$block_name]) ? $this->block_config[$block_name] : [];

        // 合并：全局 < 站点 < 调用方
        return array_merge($global, $site, $caller_params);
    }

    /**
     * 获取当前主题
     */
    private function getCurrentTheme()
    {
        return defined('CURRENT_THEME') ? CURRENT_THEME : 'default';
    }

    // ========== 9 个 Block 渲染方法（占位） ==========

    public function render_game_detail($context = [])
    {
        $game_id = isset($context['game_id']) ? (int)$context['game_id'] : 0;

        if ($game_id <= 0) {
            // 无效 game_id 返回转义错误占位
            return htmlspecialchars('游戏不存在', ENT_QUOTES, 'UTF-8');
        }

        // 查询游戏数据（待实现：实际数据库查询）
        $game = $this->queryGame($game_id);
        if (!$game) {
            return htmlspecialchars('游戏不存在', ENT_QUOTES, 'UTF-8');
        }

        // 准备渲染数据
        $data = [
            'game_name' => htmlspecialchars($game['name'], ENT_QUOTES, 'UTF-8'),
            'game_description' => htmlspecialchars($game['description'] ?? '', ENT_QUOTES, 'UTF-8'),
            'cover' => htmlspecialchars($game['cover'] ?? '', ENT_QUOTES, 'UTF-8'),
            'stats' => [
                'rating' => $game['rating'] ?? 0,
                'downloads' => $game['downloads'] ?? 0,
                'size' => $game['size'] ?? 'N/A',
            ],
        ];

        // 注入 VideoGame JSON-LD Schema
        $data['json_ld'] = $this->generateVideoGameSchema($game);

        return $this->render('block_game_detail', $data, $context);
    }

    /**
     * 解析 {inc:xxx} 路径（路径穿越防护）
     */
    public function resolve_inc_path($inc, $theme)
    {
        // 拒绝绝对路径
        if (strpos($inc, '/') === 0 || preg_match('/^[A-Z]:\\\\/', $inc)) {
            return false;
        }

        // 拒绝 ../ 穿越
        if (strpos($inc, '..') !== false) {
            return false;
        }

        $full_path = realpath(ROOT_PATH . "view/{$theme}/{$inc}");
        if (!$full_path) {
            return false;
        }

        // 白名单前缀验证
        $allowed_prefix = realpath(ROOT_PATH . "view/{$theme}/");
        if (strpos($full_path, $allowed_prefix) !== 0) {
            return false;
        }

        return $full_path;
    }

    /**
     * 查询游戏数据（REQ-05-AC6：游戏 Block 数据源）
     *
     * 读 cms_game 表；该表无 rating/downloads/size 列，补齐渲染默认值。
     * @param int $game_id 游戏ID
     * @return array|null 游戏数据或 null
     */
    private function queryGame($game_id)
    {
        $game_id = (int)$game_id;
        if ($game_id <= 0 || !$this->db) {
            return null;
        }
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];
        $row = $this->db->fetch_first(
            "SELECT * FROM `{$tablepre}cms_game` WHERE id = {$game_id} LIMIT 1"
        );
        if (!$row) {
            return null;
        }
        return array_merge(array(
            'rating' => 0,
            'downloads' => 0,
            'size' => '',
        ), $row);
    }

    /**
     * 生成 VideoGame JSON-LD Schema
     */
    private function generateVideoGameSchema($game)
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'VideoGame',
            'name' => $game['name'] ?? '',
            'description' => $game['description'] ?? '',
            'applicationCategory' => 'Game',
            'operatingSystem' => $game['platform'] ?? 'Windows',
            'offers' => [
                '@type' => 'Offer',
                'price' => '0',
                'priceCurrency' => 'CNY',
            ],
            'aggregateRating' => [
                '@type' => 'AggregateRating',
                'ratingValue' => $game['rating'] ?? 0,
                'bestRating' => 5,
            ],
        ];

        return htmlspecialchars(json_encode($schema, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
    }

    public function render_game_download_box($context = [])
    {
        $site_id = $this->site_id;
        $game_id = isset($context['game_id']) ? (int)$context['game_id'] : 0;

        if ($game_id <= 0) {
            return htmlspecialchars('无效游戏', ENT_QUOTES, 'UTF-8');
        }

        $best_link = $this->getBestLink($site_id, $game_id);
        $url = ($best_link && !empty($best_link['url'])) ? $best_link['url'] : "/download/{$game_id}.html";
        $backup = ($best_link && !empty($best_link['backup_url'])) ? $best_link['backup_url'] : '';

        $data = [
            'download_url' => htmlspecialchars($url, ENT_QUOTES, 'UTF-8'),
            'cps_id' => $best_link ? (int)$best_link['id'] : 0,
            'backup_url' => htmlspecialchars($backup, ENT_QUOTES, 'UTF-8'),
            'game_id' => $game_id,
        ];

        return $this->render('block_game_download_box', $data, $context);
    }

    /**
     * 获取最优 CPS 推广链接（REQ-05-AC8 下载地址多源 / REQ-06-AC2）
     *
     * 取 cms_cps_config 中 enabled=1 且权重最高的链接，
     * 返回完整行（含 url 与 backup_url），供下载框主备降级。
     * @return array|null 链接行或 null
     */
    private function getBestLink($site_id, $game_id)
    {
        $site_id = (int)$site_id;
        $game_id = (int)$game_id;
        if ($game_id <= 0 || !$this->db) {
            return null;
        }
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];
        $row = $this->db->fetch_first(
            "SELECT * FROM `{$tablepre}cms_cps_config`
             WHERE site_id = {$site_id} AND game_id = {$game_id} AND enabled = 1
             ORDER BY weight DESC, id ASC
             LIMIT 1"
        );
        return $row ? $row : null;
    }

    public function render_game_system_req($context = [])
    {
        $data = [
            'os' => htmlspecialchars($context['os'] ?? '-', ENT_QUOTES, 'UTF-8'),
            'cpu' => htmlspecialchars($context['cpu'] ?? '-', ENT_QUOTES, 'UTF-8'),
            'ram' => htmlspecialchars($context['ram'] ?? '-', ENT_QUOTES, 'UTF-8'),
            'gpu' => htmlspecialchars($context['gpu'] ?? '-', ENT_QUOTES, 'UTF-8'),
            'storage' => htmlspecialchars($context['storage'] ?? '-', ENT_QUOTES, 'UTF-8'),
        ];
        return $this->render('block_game_system_req', $data, $context);
    }

    public function render_game_rating($context = [])
    {
        $rating = isset($context['rating']) ? (float)$context['rating'] : 0;
        $rating = max(0, min(5, $rating));

        $data = [
            'rating' => $rating,
            'stars' => $this->renderStars($rating),
            'review_schema' => '',
        ];
        return $this->render('block_game_rating', $data, $context);
    }

    private function renderStars($rating)
    {
        $full = floor($rating);
        $half = ($rating - $full) >= 0.5 ? 1 : 0;
        $empty = 5 - $full - $half;
        return str_repeat('★', $full) . ($half ? '½' : '') . str_repeat('☆', $empty);
    }

    public function render_game_screenshots($context = [])
    {
        $limit = isset($context['limit']) ? (int)$context['limit'] : 6;
        $screenshots = isset($context['screenshots']) ? $context['screenshots'] : [];

        $data = [
            'screenshots' => array_slice($screenshots, 0, $limit),
            'limit' => $limit,
        ];
        return $this->render('block_game_screenshots', $data, $context);
    }

    public function render_game_related($context = [])
    {
        $limit = isset($context['limit']) ? (int)$context['limit'] : 6;
        $related = isset($context['related_games']) ? $context['related_games'] : [];

        $data = [
            'games' => array_slice($related, 0, $limit),
            'limit' => $limit,
        ];
        return $this->render('block_game_related', $data, $context);
    }

    public function render_game_ranking($params = [])
    {
        $period = isset($params['period']) ? $params['period'] : 'weekly';
        $orderby = isset($params['orderby']) ? $params['orderby'] : 'downloads';
        $limit = isset($params['limit']) ? (int)$params['limit'] : 10;

        $data = [
            'period' => htmlspecialchars($period, ENT_QUOTES, 'UTF-8'),
            'orderby' => htmlspecialchars($orderby, ENT_QUOTES, 'UTF-8'),
            'limit' => $limit,
            'games' => [],
            'itemlist_schema' => '',
        ];
        return $this->render('block_game_ranking', $data, $params);
    }

    public function render_game_platform_filter($params = [])
    {
        $platforms = isset($params['platforms']) ? $params['platforms'] : 'PC,PS5,Xbox';
        $selected = isset($params['selected']) ? $params['selected'] : '';

        $data = [
            'platforms' => array_map('htmlspecialchars', explode(',', $platforms)),
            'selected' => htmlspecialchars($selected, ENT_QUOTES, 'UTF-8'),
        ];
        return $this->render('block_game_platform_filter', $data, $params);
    }

    public function render_game_tag_cloud($params = [])
    {
        $limit = isset($params['limit']) ? (int)$params['limit'] : 20;
        $min_weight = isset($params['min_weight']) ? (int)$params['min_weight'] : 1;

        $data = [
            'tags' => [],
            'limit' => $limit,
            'min_weight' => $min_weight,
        ];
        return $this->render('block_game_tag_cloud', $data, $params);
    }
}
