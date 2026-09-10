<?php
/**
 * BlockRender - 游戏 Block 渲染引擎
 *
 * 三层主题继承链：
 * Layer 1: view/{theme}/css/theme.css :root CSS 变量（全局）
 * Layer 2: 站点 config.theme_vars 注入 <style>:root{...}</style>（站点覆写）
 * Layer 3: pre_cms_block_config.params JSON 覆写（Block 级）
 *
 * 渲染优先级：Layer 3 > Layer 2 > Layer 1
 *
 * @author     沐尘100
 * @version    1.0.0
 * @cms_version 3.0.0
 */
class BlockRender
{
    /** @var int 站点 ID */
    private $site_id;
    /** @var db_mysql 数据库实例 */
    private $db;
    /** @var string 当前主题名称 */
    private $theme;
    /** @var array 站点级 Block 配置缓存 */
    private $block_configs = array();

    public function __construct($site_id, $db) {
        $this->site_id = (int)$site_id;
        $this->db = $db;
        $this->theme = defined('CURRENT_THEME') ? CURRENT_THEME : 'default';
        $this->load_block_configs();
    }

    /**
     * 加载站点所有 Block 配置
     */
    private function load_block_configs() {
        $tablepre = isset($this->db->tablepre) ? $this->db->tablepre : '';
        $rows = $this->db->fetch_all("
            SELECT `block_name`, `params`, `enabled`
            FROM `{$tablepre}cms_block_config`
            WHERE `site_id` = '{$this->site_id}' AND `enabled` = 1
        ");
        if (!empty($rows)) {
            foreach ($rows as $row) {
                $this->block_configs[$row['block_name']] = json_decode($row['params'], true);
                if (!is_array($this->block_configs[$row['block_name']])) {
                    $this->block_configs[$row['block_name']] = array();
                }
            }
        }
    }

    /**
     * 解析模板文件路径（含性能模式后缀）
     *
     * 优先级：{name}_{mode}.htm > {name}.htm > default/{name}.htm
     *
     * @param   string  $base_name  模板基础名称（不含后缀）
     * @param   string  $theme      主题名称
     * @return  string  模板文件绝对路径，未找到返回空字符串
     */
    public function resolve_template_path($base_name, $theme) {
        $mode = 'standard';
        // 读取站点性能模式
        $mode_row = $this->db->fetch_first("
            SELECT `value` FROM `{$this->db->tablepre}site_config`
            WHERE `site_id` = '{$this->site_id}' AND `key` = 'performance_mode'
            LIMIT 1
        ");
        if ($mode_row && !empty($mode_row['value'])) {
            $mode = $mode_row['value'];
        }

        $valid_modes = array('lite', 'standard', 'rich');
        if (!in_array($mode, $valid_modes)) {
            $mode = 'standard';
        }

        // 按优先级尝试：带后缀 → 无后缀 → default 主题
        $candidates = array(
            ROOT_PATH . "/view/{$theme}/{$base_name}_{$mode}.htm",
            ROOT_PATH . "/view/{$theme}/{$base_name}.htm",
            ROOT_PATH . "/view/default/{$base_name}.htm",
        );

        foreach ($candidates as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
        return '';
    }

    /**
     * 渲染指定 Block
     *
     * @param   string  $block_name  Block 标识（如 game_detail）
     * @param   array   $params      调用方传入参数
     * @param   array   $context     渲染上下文（game_id 等）
     * @return  string  渲染后的 HTML
     */
    public function render($block_name, $params = array(), $context = array()) {
        // 合并参数优先级：调用参数 < Block 配置 < 全局默认
        $merged_params = $this->merge_params($block_name, $params);

        // 加载主题模板文件（支持性能模式后缀）
        $template_file = $this->resolve_template_path("block_{$block_name}", $this->theme);
        if (!$template_file || !file_exists($template_file)) {
            return ''; // Block 未配置，静默跳过
        }

        // 提取变量并渲染
        $vars = array_merge($context, $merged_params);
        // 将 game_id 显式注入模板作用域，供模板调用渲染方法时使用
        if (isset($context['game_id'])) {
            $vars['game_id'] = $context['game_id'];
        }
        // 将 $this 暴露为 $block_render，使模板可调用渲染方法
        $block_render = $this;
        extract($vars, EXTR_SKIP);
        ob_start();
        include $template_file;
        return ob_get_clean();
    }

    /**
     * 合并三层参数（调用参数 < Block 配置 < 全局默认）
     */
    private function merge_params($block_name, $call_params) {
        // Layer 1: 全局默认（空数组，由 theme.css :root 处理样式）
        $global = array();

        // Layer 2: 站点级 Block 配置
        $site = isset($this->block_configs[$block_name]) ? $this->block_configs[$block_name] : array();

        // Layer 3: Block 级参数覆写（最高优先级）
        $block = $call_params;

        // 逐层合并
        $result = array_replace_recursive($global, $site, $block);
        return $result;
    }

    /**
     * 注册 Block 配置
     */
    public function register_config($block_name, $params, $site_id = 0) {
        $sid = $site_id > 0 ? $site_id : $this->site_id;
        $params_json = json_encode($params);
        $dateline = isset($_ENV['_time']) ? $_ENV['_time'] : time();

        $this->db->query("
            INSERT INTO `{$this->db->tablepre}cms_block_config`
            (`site_id`, `block_name`, `params`, `updated_at`)
            VALUES ('{$sid}', '" . addslashes($block_name) . "', '"
            . addslashes($params_json) . "', '{$dateline}')
            ON DUPLICATE KEY UPDATE `params` = VALUES(`params`), `updated_at` = VALUES(`updated_at`)
        ");
    }

    /**
     * 延迟加载 CpsConfig 模型（按需加载，避免硬耦合 cps-integration 插件）
     *
     * 若 cps-integration 插件未加载（CpsConfig 类不存在），返回 null，
     * 确保 template-manager 插件独立运行不抛错。
     *
     * @return  CpsConfig|null
     */
    private function _get_cps_model() {
        if (!class_exists('CpsConfig')) {
            return null;
        }
        return new CpsConfig($this->db);
    }

    /**
     * 渲染游戏下载按钮 Block
     *
     * 集成 CPS 推广链接：优先使用权重最高的 CPS 链接，
     * 无配置或 CPS 禁用时降级到备用直链。
     *
     * 点击追踪：下载按钮的 onclick 调用全局 __cpsTrackClick() 函数，
     * 通过 AJAX 异步递增 click_count，不阻塞用户跳转。
     *
     * 主题差异化：不同主题渲染不同的 HTML 结构（icon 布局、按钮样式、附加元素）。
     * 支持的主题：default / green-tech / dark-esports / cartoon-cute / minimal-white / retro-pixel
     *
     * @param   array  $context  渲染上下文（game_id 必填，theme 可选）
     * @return  string  渲染后的 HTML
     */
    public function render_game_download_box($context = array()) {
        $game_id = isset($context['game_id']) ? (int)$context['game_id'] : 0;
        $theme  = isset($context['theme']) ? $context['theme'] : $this->theme;
        if (!$game_id) {
            return '<p class="block-error">game_id required</p>';
        }

        $site_id = (int)$this->site_id;

        // 获取 Block 配置中的备用链接
        $block_configs = $this->block_configs;
        $fallback_url = isset($block_configs['game_download_box']['backup_url'])
            ? $block_configs['game_download_box']['backup_url']
            : '';

        // 延迟加载 CpsConfig（若插件未加载则跳过 CPS 逻辑）
        $cps_model = $this->_get_cps_model();

        $download_url = '';
        $cps_id = 0;
        $is_degraded = false;

        // 尝试获取 CPS 推广链接
        if ($cps_model !== null) {
            $cps_link = $cps_model->get_best_link($site_id, $game_id);
            if ($cps_link) {
                $download_url = $cps_link['url'];
                $cps_id = (int)$cps_link['id'];
            }
        }

        // 降级：无 CPS 链接时使用备用直链
        if (!$download_url) {
            $download_url = $fallback_url;
            $is_degraded = true;
        }

        if (!$download_url) {
            return '<p class="block-error">No download URL available</p>';
        }

        // 构建 HTML（按主题分发）
        $escaped_url = htmlspecialchars($download_url);
        $onclick_attr = $cps_id > 0
            ? ' onclick="__cpsTrackClick(' . $cps_id . ', this.href);"'
            : '';
        $tracking_script = $cps_id > 0
            ? '<script>function __cpsTrackClick(cpsId,dlUrl){var xhr=new XMLHttpRequest();xhr.open("POST","' . ROOT_PATH . '/index.php?c=api&a=cps_click",true);xhr.setRequestHeader("Content-Type","application/x-www-form-urlencoded");xhr.send("cps_id="+cpsId);}</script>'
            : '';

        switch ($theme) {
            case 'green-tech':
                // 绿色科技：左侧 icon 带绿色圆环 + 进度条风格
                $output  = '<div class="game-download-box gdx-green-tech" data-game-id="' . $game_id . '"';
                $output .= ' data-cps-id="' . $cps_id . '" data-degraded="' . ($is_degraded ? '1' : '0') . '">';
                $output .= '<div class="gdx-icon-ring"><span class="gdx-icon">&#x26A1;</span></div>';
                $output .= '<div class="gdx-main">';
                $output .= '<span class="gdx-label">高速下载</span>';
                $output .= '<a href="' . $escaped_url . '" class="gdx-btn"' . $onclick_attr . ' target="_blank" rel="nofollow noopener">&#x2B07; 立即下载</a>';
                $output .= '</div>';
                if (!empty($fallback_url) && !$is_degraded) {
                    $output .= '<a href="' . htmlspecialchars($fallback_url) . '" class="gdx-mirror" target="_blank" rel="nofollow noopener">备用节点</a>';
                }
                $output .= '</div>';
                break;

            case 'dark-esports':
                // 暗黑电竞：大按钮居中 + 霓虹光晕 + 顶部 badge
                $output  = '<div class="game-download-box gdx-esports" data-game-id="' . $game_id . '"';
                $output .= ' data-cps-id="' . $cps_id . '" data-degraded="' . ($is_degraded ? '1' : '0') . '">';
                $output .= '<span class="gdx-badge">GAME DOWNLOAD</span>';
                $output .= '<a href="' . $escaped_url . '" class="gdx-btn-esports"' . $onclick_attr . ' target="_blank" rel="nofollow noopener">';
                $output .= '<span class="gdx-btn-icon">&#x1F3AE;</span>';
                $output .= '<span class="gdx-btn-text">立即下载</span>';
                $output .= '</a>';
                if (!empty($fallback_url) && !$is_degraded) {
                    $output .= '<a href="' . htmlspecialchars($fallback_url) . '" class="gdx-mirror-esports" target="_blank" rel="nofollow noopener">备用镜像</a>';
                }
                $output .= '</div>';
                break;

            case 'cartoon-cute':
                // 卡通萌系：大表情 icon + 圆角 pill 按钮 + 柔色背景
                $output  = '<div class="game-download-box gdx-cute" data-game-id="' . $game_id . '"';
                $output .= ' data-cps-id="' . $cps_id . '" data-degraded="' . ($is_degraded ? '1' : '0') . '">';
                $output .= '<div class="gdx-mascot">&#x1F60E;</div>';
                $output .= '<a href="' . $escaped_url . '" class="gdx-btn-cute"' . $onclick_attr . ' target="_blank" rel="nofollow noopener">&#x2764; 点我下载！</a>';
                if (!empty($fallback_url) && !$is_degraded) {
                    $output .= '<a href="' . htmlspecialchars($fallback_url) . '" class="gdx-alt-cute" target="_blank" rel="nofollow noopener">备用通道 &#x1F197;</a>';
                }
                $output .= '</div>';
                break;

            case 'minimal-white':
                // 极简白：纯文字链接风格，无图标，细线下划线
                $output  = '<div class="game-download-box gdx-minimal" data-game-id="' . $game_id . '"';
                $output .= ' data-cps-id="' . $cps_id . '" data-degraded="' . ($is_degraded ? '1' : '0') . '">';
                $output .= '<span class="gdx-minimal-label">下载：</span>';
                $output .= '<a href="' . $escaped_url . '" class="gdx-link-minimal"' . $onclick_attr . ' target="_blank" rel="nofollow noopener">官方下载</a>';
                if (!empty($fallback_url) && !$is_degraded) {
                    $output .= ' / <a href="' . htmlspecialchars($fallback_url) . '" class="gdx-link-minimal" target="_blank" rel="nofollow noopener">备用</a>';
                }
                $output .= '</div>';
                break;

            case 'retro-pixel':
                // 复古像素：方块边框 + 像素 icon + 无圆角
                $output  = '<div class="game-download-box gdx-pixel" data-game-id="' . $game_id . '"';
                $output .= ' data-cps-id="' . $cps_id . '" data-degraded="' . ($is_degraded ? '1' : '0') . '">';
                $output .= '<div class="gdx-pixel-box">';
                $output .= '<span class="gdx-pixel-icon">&#x25B6;</span>';
                $output .= '<a href="' . $escaped_url . '" class="gdx-btn-pixel"' . $onclick_attr . ' target="_blank" rel="nofollow noopener">[ DOWNLOAD ]</a>';
                $output .= '</div>';
                if (!empty($fallback_url) && !$is_degraded) {
                    $output .= '<a href="' . htmlspecialchars($fallback_url) . '" class="gdx-pixel-mirror" target="_blank" rel="nofollow noopener">[ MIRROR ]</a>';
                }
                $output .= '</div>';
                break;

            default:
                // default：标准卡片布局（icon + 文字 + 按钮）
                $output  = '<div class="game-download-box default-theme" data-game-id="' . $game_id . '"';
                $output .= ' data-cps-id="' . $cps_id . '" data-degraded="' . ($is_degraded ? '1' : '0') . '">';
                $output .= '<div class="dl-icon-wrap"><span class="dl-icon">&#x2B07;</span></div>';
                $output .= '<div class="dl-content">';
                $output .= '<span class="dl-label">下载游戏</span>';
                $output .= '<a href="' . $escaped_url . '" class="download-btn"' . $onclick_attr . ' target="_blank" rel="nofollow noopener">立即下载</a>';
                $output .= '</div>';
                if (!empty($fallback_url) && !$is_degraded) {
                    $output .= '<a href="' . htmlspecialchars($fallback_url) . '" class="download-mirror" target="_blank" rel="nofollow noopener">备用镜像</a>';
                }
                $output .= '</div>';
        }

        $output .= $tracking_script;
        return $output;
    }

    /**
     * 渲染游戏详情 Block
     *
     * 查询 cms_article + category + cms_content_flag 联表，生成 HTML + VideoGame JSON-LD Schema
     *
     * @param   array  $context  渲染上下文（game_id 必填）
     * @return  string  渲染后的 HTML
     */
    public function render_game_detail($context = array()) {
        $game_id = isset($context['game_id']) ? (int)$context['game_id'] : 0;
        if (!$game_id) {
            return '<p class="block-error">game_id required</p>';
        }

        $tablepre = $this->db->tablepre;
        $dateline = isset($_ENV['_time']) ? $_ENV['_time'] : time();

        $game = $this->db->fetch_first("
            SELECT a.*, c.name as category_name, cf.flag_type
            FROM `{$tablepre}cms_article` a
            LEFT JOIN `{$tablepre}category` c ON a.cid = c.cid
            LEFT JOIN `{$tablepre}cms_content_flag` cf ON a.id = cf.content_id
            WHERE a.id = '{$game_id}' AND a.status = 1
            LIMIT 1
        ");

        if (!$game) {
            return '<p class="block-error">Game not found</p>';
        }

        // 解析游戏字段
        $title = $game['subject'];
        $description = $game['description'] ?: ($game['summary'] ?: $game['seo_description']);
        $category = isset($game['category_name']) ? $game['category_name'] : '';
        $image = !empty($game['thumb']) ? $game['thumb'] : '';
        $publish_date = $game['dateline'] ? date('Y-m-d', $game['dateline']) : '';
        $rating = 4.5; // TODO: Task 5.1 对接评分表读取实际评分

        // 构建 VideoGame JSON-LD Schema
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'VideoGame',
            'name' => $title,
            'description' => $description,
            'image' => $image,
            'applicationCategory' => $category,
            'datePublished' => $publish_date,
            'aggregateRating' => array(
                '@type' => 'AggregateRating',
                'ratingValue' => $rating,
                'bestRating' => 5,
            ),
        );
        $schema_json = json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // 输出 HTML
        $output = '<div class="game-detail" data-game-id="' . $game_id . '">';
        $output .= '<script type="application/ld+json">' . $schema_json . '</script>';
        $output .= '<h1 class="game-title">' . htmlspecialchars($title) . '</h1>';
        $output .= '<div class="game-meta">';
        if ($category) {
            $output .= '<span class="game-category">' . htmlspecialchars($category) . '</span>';
        }
        if ($publish_date) {
            $output .= '<span class="game-date">' . htmlspecialchars($publish_date) . '</span>';
        }
        $output .= '<span class="game-rating">' . $rating . '</span>';
        $output .= '</div>';
        if ($description) {
            $output .= '<p class="game-description">' . htmlspecialchars($description) . '</p>';
        }
        $output .= '</div>';

        return $output;
    }

    /**
     * 渲染游戏系统配置要求 Block
     *
     * 从 cms_article_data.content 中提取 <!-- system_req: {...} --> JSON 标记，
     * 渲染 OS / CPU / RAM / GPU / Storage 配置要求表格。
     *
     * 主题差异化：不同主题渲染不同的 HTML 结构（边框、icon、颜色等）。
     * 支持的主题：default / green-tech / dark-esports / cartoon-cute / minimal-white / retro-pixel
     *
     * @param   array  $context  渲染上下文（game_id 必填）
     * @return  string  渲染后的 HTML
     */
    public function render_game_system_req($context = array()) {
        $game_id = isset($context['game_id']) ? (int)$context['game_id'] : 0;
        if (!$game_id) {
            return '<p class="block-error">game_id required</p>';
        }

        $tablepre = $this->db->tablepre;

        // 获取游戏基本信息
        $game = $this->db->fetch_first("
            SELECT a.*
            FROM `{$tablepre}cms_article` a
            WHERE a.id = '{$game_id}' AND a.status = 1
            LIMIT 1
        ");

        if (!$game) {
            return '<p class="block-error">Game not found</p>';
        }

        // 获取文章内容（包含系统要求 JSON）
        $article_data = $this->db->fetch_first("
            SELECT content FROM `{$tablepre}cms_article_data` WHERE id = '{$game_id}' LIMIT 1
        ");

        $system_reqs = array();
        if ($article_data && !empty($article_data['content'])) {
            $content = $article_data['content'];
            // 从内容中提取 <!-- system_req: {...} --> JSON 标记
            if (preg_match('/<!--\s*system_req:\s*(\{.*?\})\s*-->/s', $content, $matches)) {
                $decoded = json_decode($matches[1], true);
                if (is_array($decoded)) {
                    $system_reqs = $decoded;
                } else {
                    error_log("[BlockRender] game_system_req JSON decode failed for game_id={$game_id}: " . $matches[1]);
                }
            }
        }

        // 默认空系统要求
        $default_reqs = array(
            'os'      => '',
            'cpu'     => '',
            'ram'     => '',
            'gpu'     => '',
            'storage' => '',
        );
        $system_reqs = array_replace($default_reqs, $system_reqs);

        $title = $game['subject'];
        $theme = isset($context['theme']) ? $context['theme'] : $this->theme;

        // 按主题分发
        switch ($theme) {
            case 'green-tech':
                $output = $this->_render_system_req_green_tech($title, $system_reqs, $game_id);
                break;
            case 'dark-esports':
                $output = $this->_render_system_req_dark_esports($title, $system_reqs, $game_id);
                break;
            case 'cartoon-cute':
                $output = $this->_render_system_req_cartoon_cute($title, $system_reqs, $game_id);
                break;
            case 'minimal-white':
                $output = $this->_render_system_req_minimal_white($title, $system_reqs, $game_id);
                break;
            case 'retro-pixel':
                $output = $this->_render_system_req_retro_pixel($title, $system_reqs, $game_id);
                break;
            default:
                $output = $this->_render_system_req_default($title, $system_reqs, $game_id);
        }

        return $output;
    }

    // ========== 各主题渲染实现 ==========

    /**
     * default：标准卡片 + 两列配置表
     */
    private function _render_system_req_default($title, $reqs, $game_id) {
        $output  = '<div class="system-req default-theme" data-game-id="' . $game_id . '">';
        $output .= '<h3 class="sr-title">' . htmlspecialchars($title) . ' - 系统配置要求</h3>';
        $output .= '<table class="sr-table">';
        $output .= '<thead><tr><th>项目</th><th>最低配置</th></tr></thead>';
        $output .= '<tbody>';
        $output .= '<tr><td class="sr-label">操作系统</td><td>' . htmlspecialchars($reqs['os']) . '</td></tr>';
        $output .= '<tr><td class="sr-label">处理器</td><td>' . htmlspecialchars($reqs['cpu']) . '</td></tr>';
        $output .= '<tr><td class="sr-label">内存</td><td>' . htmlspecialchars($reqs['ram']) . '</td></tr>';
        $output .= '<tr><td class="sr-label">显卡</td><td>' . htmlspecialchars($reqs['gpu']) . '</td></tr>';
        $output .= '<tr><td class="sr-label">存储空间</td><td>' . htmlspecialchars($reqs['storage']) . '</td></tr>';
        $output .= '</tbody></table></div>';
        return $output;
    }

    /**
     * green-tech：绿色科技风格 - 带图标 + 左侧色条
     */
    private function _render_system_req_green_tech($title, $reqs, $game_id) {
        $icons = array('os' => '&#x1F4BB;', 'cpu' => '&#x2699;', 'ram' => '&#x1F4E6;', 'gpu' => '&#x1F3AE;', 'storage' => '&#x1F4BE;');
        $output  = '<div class="system-req srx-green-tech" data-game-id="' . $game_id . '">';
        $output .= '<div class="srx-header"><span class="srx-icon">&#x26A1;</span>';
        $output .= '<h3 class="sr-title">' . htmlspecialchars($title) . '</h3></div>';
        $output .= '<table class="sr-table">';
        $output .= '<thead><tr><th>组件</th><th>要求</th></tr></thead>';
        $output .= '<tbody>';
        $output .= '<tr><td class="sr-icon-cell">' . $icons['os'] . ' 操作系统</td><td>' . htmlspecialchars($reqs['os']) . '</td></tr>';
        $output .= '<tr><td class="sr-icon-cell">' . $icons['cpu'] . ' 处理器</td><td>' . htmlspecialchars($reqs['cpu']) . '</td></tr>';
        $output .= '<tr><td class="sr-icon-cell">' . $icons['ram'] . ' 内存</td><td>' . htmlspecialchars($reqs['ram']) . '</td></tr>';
        $output .= '<tr><td class="sr-icon-cell">' . $icons['gpu'] . ' 显卡</td><td>' . htmlspecialchars($reqs['gpu']) . '</td></tr>';
        $output .= '<tr><td class="sr-icon-cell">' . $icons['storage'] . ' 存储</td><td>' . htmlspecialchars($reqs['storage']) . '</td></tr>';
        $output .= '</tbody></table></div>';
        return $output;
    }

    /**
     * dark-esports：暗黑电竞 - 霓虹边框 + 大写标签
     */
    private function _render_system_req_dark_esports($title, $reqs, $game_id) {
        $output  = '<div class="system-req srx-esports" data-game-id="' . $game_id . '">';
        $output .= '<div class="srx-badge">SYSTEM REQUIREMENTS</div>';
        $output .= '<h3 class="sr-title sr-title-neon">' . htmlspecialchars($title) . '</h3>';
        $output .= '<table class="sr-table sr-table-neon">';
        $output .= '<thead><tr><th>SPEC</th><th>REQUIREMENT</th></tr></thead>';
        $output .= '<tbody>';
        $output .= '<tr><td class="sr-label">OS</td><td>' . htmlspecialchars($reqs['os']) . '</td></tr>';
        $output .= '<tr><td class="sr-label">CPU</td><td>' . htmlspecialchars($reqs['cpu']) . '</td></tr>';
        $output .= '<tr><td class="sr-label">RAM</td><td>' . htmlspecialchars($reqs['ram']) . '</td></tr>';
        $output .= '<tr><td class="sr-label">GPU</td><td>' . htmlspecialchars($reqs['gpu']) . '</td></tr>';
        $output .= '<tr><td class="sr-label">STORAGE</td><td>' . htmlspecialchars($reqs['storage']) . '</td></tr>';
        $output .= '</tbody></table></div>';
        return $output;
    }

    /**
     * cartoon-cute：卡通萌系 - 圆角 + 表情分隔 + 柔和色
     */
    private function _render_system_req_cartoon_cute($title, $reqs, $game_id) {
        $output  = '<div class="system-req srx-cute" data-game-id="' . $game_id . '">';
        $output .= '<div class="srx-cute-header"><span class="srx-mascot">&#x1F4BB;</span>';
        $output .= '<h3 class="sr-title">' . htmlspecialchars($title) . ' 要什么配置呀？</h3></div>';
        $output .= '<table class="sr-table sr-table-cute">';
        $output .= '<thead><tr><th>项目</th><th>需要的东西</th></tr></thead>';
        $output .= '<tbody>';
        $output .= '<tr><td>&#x1F4BB; 系统</td><td>' . htmlspecialchars($reqs['os']) . '</td></tr>';
        $output .= '<tr><td>&#x2699; 处理器</td><td>' . htmlspecialchars($reqs['cpu']) . '</td></tr>';
        $output .= '<tr><td>&#x1F4E6; 内存</td><td>' . htmlspecialchars($reqs['ram']) . '</td></tr>';
        $output .= '<tr><td>&#x1F3AE; 显卡</td><td>' . htmlspecialchars($reqs['gpu']) . '</td></tr>';
        $output .= '<tr><td>&#x1F4BE; 硬盘</td><td>' . htmlspecialchars($reqs['storage']) . '</td></tr>';
        $output .= '</tbody></table></div>';
        return $output;
    }

    /**
     * minimal-white：极简白 - 细边框 + 紧凑排版
     */
    private function _render_system_req_minimal_white($title, $reqs, $game_id) {
        $output  = '<div class="system-req srx-minimal" data-game-id="' . $game_id . '">';
        $output .= '<h3 class="sr-title sr-title-minimal">' . htmlspecialchars($title) . ' — 系统要求</h3>';
        $output .= '<table class="sr-table sr-table-minimal">';
        $output .= '<tbody>';
        $output .= '<tr><th>OS</th><td>' . htmlspecialchars($reqs['os']) . '</td></tr>';
        $output .= '<tr><th>CPU</th><td>' . htmlspecialchars($reqs['cpu']) . '</td></tr>';
        $output .= '<tr><th>RAM</th><td>' . htmlspecialchars($reqs['ram']) . '</td></tr>';
        $output .= '<tr><th>GPU</th><td>' . htmlspecialchars($reqs['gpu']) . '</td></tr>';
        $output .= '<tr><th>Storage</th><td>' . htmlspecialchars($reqs['storage']) . '</td></tr>';
        $output .= '</tbody></table></div>';
        return $output;
    }

    /**
     * retro-pixel：复古像素 - 方块边框 + 无圆角 + 像素字体
     */
    private function _render_system_req_retro_pixel($title, $reqs, $game_id) {
        $output  = '<div class="system-req srx-pixel" data-game-id="' . $game_id . '">';
        $output .= '<div class="srx-pixel-header">[ SYSTEM REQUIREMENTS ]</div>';
        $output .= '<div class="srx-pixel-title">' . htmlspecialchars($title) . '</div>';
        $output .= '<table class="sr-table sr-table-pixel">';
        $output .= '<tbody>';
        $output .= '<tr><td>[ OS ]</td><td>' . htmlspecialchars($reqs['os']) . '</td></tr>';
        $output .= '<tr><td>[ CPU ]</td><td>' . htmlspecialchars($reqs['cpu']) . '</td></tr>';
        $output .= '<tr><td>[ RAM ]</td><td>' . htmlspecialchars($reqs['ram']) . '</td></tr>';
        $output .= '<tr><td>[ GPU ]</td><td>' . htmlspecialchars($reqs['gpu']) . '</td></tr>';
        $output .= '<tr><td>[ STORAGE ]</td><td>' . htmlspecialchars($reqs['storage']) . '</td></tr>';
        $output .= '</tbody></table></div>';
        return $output;
    }

    /**
     * 渲染游戏评分 Block
     *
     * 从 cms_article_rating 表读取评分（article_id = game_id），
     * 渲染星级评分组件。支持 6 套主题视觉差异化。
     *
     * 评分数据流：
     * - 优先从 cms_article_rating.rating 读取
     * - 若无记录，尝试从 cms_article.rating 字段读取
     * - 均无数据时降级到 0.0
     *
     * @param   array  $context  渲染上下文（game_id 必填）
     * @return  string  渲染后的 HTML
     */
    public function render_game_rating($context = array()) {
        $game_id = isset($context['game_id']) ? (int)$context['game_id'] : 0;
        if (!$game_id) {
            return '<p class="block-error">game_id required</p>';
        }

        $tablepre = $this->db->tablepre;

        // 获取游戏基本信息
        $game = $this->db->fetch_first("
            SELECT a.*, a.rating as article_rating
            FROM `{$tablepre}cms_article` a
            WHERE a.id = '{$game_id}' AND a.status = 1
            LIMIT 1
        ");

        if (!$game) {
            return '<p class="block-error">Game not found</p>';
        }

        // 尝试从 cms_article_rating 表读取评分
        $rating_row = $this->db->fetch_first("
            SELECT rating FROM `{$tablepre}cms_article_rating`
            WHERE article_id = '{$game_id}' LIMIT 1
        ");

        $rating = 0.0;
        if ($rating_row && isset($rating_row['rating'])) {
            $rating = (float)$rating_row['rating'];
        } elseif (isset($game['article_rating']) && $game['article_rating'] !== '') {
            // 降级：使用 cms_article.rating 字段
            $rating = (float)$game['article_rating'];
        }

        // 限制评分范围 [0, 5]
        $rating = max(0.0, min(5.0, $rating));
        $rating_display = number_format($rating, 1);

        $theme = isset($context['theme']) ? $context['theme'] : $this->theme;

        // 按主题分发
        switch ($theme) {
            case 'green-tech':
                $output = $this->_render_rating_green_tech($game_id, $game['subject'], $rating, $rating_display);
                break;
            case 'dark-esports':
                $output = $this->_render_rating_dark_esports($game_id, $game['subject'], $rating, $rating_display);
                break;
            case 'cartoon-cute':
                $output = $this->_render_rating_cartoon_cute($game_id, $game['subject'], $rating, $rating_display);
                break;
            case 'minimal-white':
                $output = $this->_render_rating_minimal_white($game_id, $game['subject'], $rating, $rating_display);
                break;
            case 'retro-pixel':
                $output = $this->_render_rating_retro_pixel($game_id, $game['subject'], $rating, $rating_display);
                break;
            default:
                $output = $this->_render_rating_default($game_id, $game['subject'], $rating, $rating_display);
        }

        return $output;
    }

    // ========== 各主题评分渲染实现 ==========

    /**
     * default：标准评分卡片 - 数字 + 星级条
     */
    private function _render_rating_default($game_id, $title, $rating, $rating_display) {
        $stars_full = (int)floor($rating);
        $stars_empty = 5 - $stars_full;
        $stars_html = str_repeat('&#9733;', $stars_full) . str_repeat('&#9734;', $stars_empty);

        $output  = '<div class="game-rating default-theme" data-game-id="' . $game_id . '">';
        $output .= '<h3 class="gr-title">' . htmlspecialchars($title) . ' 评分</h3>';
        $output .= '<div class="gr-stars" aria-label="评分 ' . $rating_display . ' / 5">';
        $output .= '<span class="gr-stars-filled">' . $stars_html . '</span>';
        $output .= '<span class="gr-numeric">' . $rating_display . ' / 5</span>';
        $output .= '</div>';
        $output .= '</div>';
        return $output;
    }

    /**
     * green-tech：绿色科技 - 进度条风格评分
     */
    private function _render_rating_green_tech($game_id, $title, $rating, $rating_display) {
        $percentage = round(($rating / 5.0) * 100);

        $output  = '<div class="game-rating grx-green-tech" data-game-id="' . $game_id . '">';
        $output .= '<div class="grx-header"><span class="grx-icon">&#x26A1;</span>';
        $output .= '<span class="grx-title">' . htmlspecialchars($title) . ' 评分</span></div>';
        $output .= '<div class="grx-bar-wrap">';
        $output .= '<div class="grx-bar" style="width:' . $percentage . '%;"></div>';
        $output .= '</div>';
        $output .= '<div class="grx-score">' . $rating_display . ' <span class="grx-max">/ 5</span></div>';
        $output .= '</div>';
        return $output;
    }

    /**
     * dark-esports：暗黑电竞 - 霓虹大字评分
     */
    private function _render_rating_dark_esports($game_id, $title, $rating, $rating_display) {
        $stars_full = (int)floor($rating);
        $stars_empty = 5 - $stars_full;

        $output  = '<div class="game-rating grx-esports" data-game-id="' . $game_id . '">';
        $output .= '<div class="grx-badge">RATING</div>';
        $output .= '<div class="grx-big-score">' . $rating_display . '</div>';
        $output .= '<div class="grx-stars">';
        $output .= str_repeat('<span class="grx-star grx-star-on">&#9733;</span>', $stars_full);
        $output .= str_repeat('<span class="grx-star grx-star-off">&#9734;</span>', $stars_empty);
        $output .= '</div>';
        $output .= '<div class="grx-label">' . htmlspecialchars($title) . '</div>';
        $output .= '</div>';
        return $output;
    }

    /**
     * cartoon-cute：卡通萌系 - 表情星星 + 圆角卡片
     */
    private function _render_rating_cartoon_cute($game_id, $title, $rating, $rating_display) {
        $stars_full = (int)floor($rating);
        $stars_empty = 5 - $stars_full;

        $output  = '<div class="game-rating grx-cute" data-game-id="' . $game_id . '">';
        $output .= '<div class="grx-mascot">&#x1F929;</div>';
        $output .= '<div class="grx-label">' . htmlspecialchars($title) . ' 好可爱！</div>';
        $output .= '<div class="grx-stars">';
        $output .= str_repeat('<span class="grx-star grx-star-cute">&#9733;</span>', $stars_full);
        $output .= str_repeat('<span class="grx-star grx-star-cute-empty">&#9734;</span>', $stars_empty);
        $output .= '</div>';
        $output .= '<div class="grx-score">' . $rating_display . ' <span class="grx-emoji">&#x2764;</span></div>';
        $output .= '</div>';
        return $output;
    }

    /**
     * minimal-white：极简白 - 细字排版，无装饰
     */
    private function _render_rating_minimal_white($game_id, $title, $rating, $rating_display) {
        $stars_full = (int)floor($rating);
        $stars_empty = 5 - $stars_full;

        $output  = '<div class="game-rating grx-minimal" data-game-id="' . $game_id . '">';
        $output .= '<span class="grx-label-minimal">' . htmlspecialchars($title) . '</span>';
        $output .= '<span class="grx-stars-minimal">';
        $output .= str_repeat('<span class="grx-star-minimal">&#9733;</span>', $stars_full);
        $output .= str_repeat('<span class="grx-star-minimal grx-star-minimal-empty">&#9734;</span>', $stars_empty);
        $output .= '</span>';
        $output .= '<span class="grx-score-minimal">' . $rating_display . '</span>';
        $output .= '</div>';
        return $output;
    }

    /**
     * retro-pixel：复古像素 - 方块边框 + 像素风格
     */
    private function _render_rating_retro_pixel($game_id, $title, $rating, $rating_display) {
        $stars_full = (int)floor($rating);
        $stars_empty = 5 - $stars_full;

        $output  = '<div class="game-rating grx-pixel" data-game-id="' . $game_id . '">';
        $output .= '<div class="grx-pixel-header">[ RATING ]</div>';
        $output .= '<div class="grx-pixel-title">' . htmlspecialchars($title) . '</div>';
        $output .= '<div class="grx-pixel-score">SCORE: ' . $rating_display . ' / 5.0</div>';
        $output .= '<div class="grx-pixel-stars">';
        $output .= str_repeat('<span class="grx-star-pixel grx-star-pixel-on">[X]</span>', $stars_full);
        $output .= str_repeat('<span class="grx-star-pixel grx-star-pixel-off">[ ]</span>', $stars_empty);
        $output .= '</div>';
        $output .= '</div>';
        return $output;
    }

    /**
     * 渲染相关游戏推荐 Block
     *
     * 查询同分类（cid 相同）的其他游戏文章，排除当前游戏自身，
     * 最多返回 5 个，按 id 倒序排列。
     *
     * 数据流：
     * - 先从 cms_article 读取当前游戏的 cid
     * - 再查询同 cid、status=1、id != game_id 的文章
     * - 无相关游戏时返回空状态提示
     *
     * 主题差异化：不同主题渲染不同的卡片布局（grid、list、横向滚动等）。
     * 支持的主题：default / green-tech / dark-esports / cartoon-cute / minimal-white / retro-pixel
     *
     * @param   array  $context  渲染上下文（game_id 必填，theme 可选）
     * @return  string  渲染后的 HTML
     */
    public function render_game_related($context = array()) {
        $game_id = isset($context['game_id']) ? (int)$context['game_id'] : 0;
        if (!$game_id) {
            return '<p class="block-error">game_id required</p>';
        }

        $tablepre = $this->db->tablepre;

        // 获取当前游戏信息（cid + subject）
        $game = $this->db->fetch_first("
            SELECT a.cid, a.subject, c.name as category_name
            FROM `{$tablepre}cms_article` a
            LEFT JOIN `{$tablepre}category` c ON a.cid = c.cid
            WHERE a.id = '{$game_id}' AND a.status = 1
            LIMIT 1
        ");

        if (!$game) {
            return '<p class="block-error">Game not found</p>';
        }

        $cid = (int)$game['cid'];
        $category_name = isset($game['category_name']) ? $game['category_name'] : '';

        // 查询同分类的其他游戏（排除自身，最多 5 个，按 id 倒序）
        $related = $this->db->fetch_all("
            SELECT a.id, a.subject, a.thumb, a.description, a.dateline
            FROM `{$tablepre}cms_article` a
            WHERE a.cid = '{$cid}'
              AND a.id != '{$game_id}'
              AND a.status = 1
            ORDER BY a.id DESC
            LIMIT 5
        ");

        if (empty($related)) {
            return '<div class="game-related empty-state" data-game-id="' . $game_id . '">'
                . '<p class="gr-empty-text">暂无同分类推荐</p>'
                . '</div>';
        }

        $theme = isset($context['theme']) ? $context['theme'] : $this->theme;

        // 按主题分发
        switch ($theme) {
            case 'green-tech':
                $output = $this->_render_related_green_tech($game_id, $category_name, $related);
                break;
            case 'dark-esports':
                $output = $this->_render_related_dark_esports($game_id, $category_name, $related);
                break;
            case 'cartoon-cute':
                $output = $this->_render_related_cartoon_cute($game_id, $category_name, $related);
                break;
            case 'minimal-white':
                $output = $this->_render_related_minimal_white($game_id, $category_name, $related);
                break;
            case 'retro-pixel':
                $output = $this->_render_related_retro_pixel($game_id, $category_name, $related);
                break;
            default:
                $output = $this->_render_related_default($game_id, $category_name, $related);
        }

        return $output;
    }

    // ========== 各主题相关游戏渲染实现 ==========

    /**
     * default：标准网格卡片布局
     */
    private function _render_related_default($game_id, $category_name, $related) {
        $output  = '<div class="game-related default-theme" data-game-id="' . $game_id . '">';
        $output .= '<h3 class="gr-title">相关推荐</h3>';
        if ($category_name) {
            $output .= '<span class="gr-category">' . htmlspecialchars($category_name) . '</span>';
        }
        $output .= '<div class="gr-grid">';
        foreach ($related as $g) {
            $title = htmlspecialchars($g['subject']);
            $desc  = htmlspecialchars(mb_substr($g['description'], 0, 60, 'utf-8'));
            $thumb = !empty($g['thumb']) ? htmlspecialchars($g['thumb']) : 'https://via.placeholder.com/300x200/CCCCCC/666666?text=No+Cover';
            $link  = ROOT_PATH . '/index.php?c=article&a=show&id=' . $g['id'];
            $output .= '<a class="game-card" href="' . $link . '" title="' . $title . '">';
            $output .= '<div class="gc-thumb"><img src="' . $thumb . '" alt="' . $title . '" loading="lazy" /></div>';
            $output .= '<div class="gc-body">';
            $output .= '<h4 class="gc-title">' . $title . '</h4>';
            $output .= '<p class="gc-desc">' . $desc . '</p>';
            $output .= '</div></a>';
        }
        $output .= '</div></div>';
        return $output;
    }

    /**
     * green-tech：绿色科技 - 左侧色条 + 进度条装饰
     */
    private function _render_related_green_tech($game_id, $category_name, $related) {
        $output  = '<div class="game-related grx-green-tech" data-game-id="' . $game_id . '">';
        $output .= '<div class="grx-header"><span class="grx-icon">&#x26A1;</span>';
        $output .= '<h3 class="gr-title">同类推荐</h3></div>';
        if ($category_name) {
            $output .= '<div class="grx-cat-tag">' . htmlspecialchars($category_name) . '</div>';
        }
        $output .= '<div class="grx-list">';
        foreach ($related as $g) {
            $title = htmlspecialchars($g['subject']);
            $desc  = htmlspecialchars(mb_substr($g['description'], 0, 50, 'utf-8'));
            $thumb = !empty($g['thumb']) ? htmlspecialchars($g['thumb']) : 'https://via.placeholder.com/300x200/2E7D32/FFFFFF?text=Game';
            $link  = ROOT_PATH . '/index.php?c=article&a=show&id=' . $g['id'];
            $output .= '<a class="grx-card" href="' . $link . '" title="' . $title . '">';
            $output .= '<div class="grx-thumb"><img src="' . $thumb . '" alt="' . $title . '" loading="lazy" /></div>';
            $output .= '<div class="grx-info">';
            $output .= '<span class="grx-name">' . $title . '</span>';
            $output .= '<span class="grx-bar" style="width:' . (30 + ($g['id'] % 5) * 12) . '%;"></span>';
            $output .= '<span class="grx-desc">' . $desc . '</span>';
            $output .= '</div></a>';
        }
        $output .= '</div></div>';
        return $output;
    }

    /**
     * dark-esports：暗黑电竞 - 霓虹边框 + 大图卡片
     */
    private function _render_related_dark_esports($game_id, $category_name, $related) {
        $output  = '<div class="game-related grx-esports" data-game-id="' . $game_id . '">';
        $output .= '<div class="grx-badge">RELATED GAMES</div>';
        $output .= '<h3 class="gr-title gr-title-neon">同分类推荐</h3>';
        $output .= '<div class="grx-carousel">';
        foreach ($related as $g) {
            $title = htmlspecialchars($g['subject']);
            $desc  = htmlspecialchars(mb_substr($g['description'], 0, 50, 'utf-8'));
            $thumb = !empty($g['thumb']) ? htmlspecialchars($g['thumb']) : 'https://via.placeholder.com/300x200/1A1A2E/FF0055?text=GAME';
            $link  = ROOT_PATH . '/index.php?c=article&a=show&id=' . $g['id'];
            $output .= '<a class="grx-card-es" href="' . $link . '" title="' . $title . '">';
            $output .= '<div class="grx-thumb-es"><img src="' . $thumb . '" alt="' . $title . '" loading="lazy" />';
            $output .= '<span class="grx-id-badge">#' . $g['id'] . '</span></div>';
            $output .= '<div class="grx-body-es">';
            $output .= '<h4 class="gc-title-es">' . $title . '</h4>';
            $output .= '<p class="gc-desc-es">' . $desc . '</p>';
            $output .= '</div></a>';
        }
        $output .= '</div></div>';
        return $output;
    }

    /**
     * cartoon-cute：卡通萌系 - 圆角卡片 + 表情装饰
     */
    private function _render_related_cartoon_cute($game_id, $category_name, $related) {
        $mascots = array('&#x1F638;', '&#x1F639;', '&#x1F63A;', '&#x1F63B;', '&#x1F63D;');
        $output  = '<div class="game-related grx-cute" data-game-id="' . $game_id . '">';
        $output .= '<div class="grx-cute-header"><span class="grx-mascot">&#x1F3AE;</span>';
        $output .= '<h3 class="gr-title">更多好游戏！</h3></div>';
        $output .= '<div class="grx-cute-grid">';
        foreach ($related as $i => $g) {
            $title = htmlspecialchars($g['subject']);
            $desc  = htmlspecialchars(mb_substr($g['description'], 0, 40, 'utf-8'));
            $thumb = !empty($g['thumb']) ? htmlspecialchars($g['thumb']) : 'https://via.placeholder.com/300x200/FFB6C1/FF69B4?text=Game';
            $mascot = $mascots[$i % count($mascots)];
            $link  = ROOT_PATH . '/index.php?c=article&a=show&id=' . $g['id'];
            $output .= '<a class="grx-card-ct" href="' . $link . '" title="' . $title . '">';
            $output .= '<span class="grx-mascot-badge">' . $mascot . '</span>';
            $output .= '<div class="grx-thumb-ct"><img src="' . $thumb . '" alt="' . $title . '" loading="lazy" /></div>';
            $output .= '<div class="grx-body-ct">';
            $output .= '<span class="grx-name-ct">' . $title . '</span>';
            $output .= '<span class="grx-desc-ct">' . $desc . '</span>';
            $output .= '</div></a>';
        }
        $output .= '</div></div>';
        return $output;
    }

    /**
     * minimal-white：极简白 - 细边框列表 + 紧凑排版
     */
    private function _render_related_minimal_white($game_id, $category_name, $related) {
        $output  = '<div class="game-related grx-minimal" data-game-id="' . $game_id . '">';
        $output .= '<h3 class="gr-title gr-title-mw">相关推荐</h3>';
        if ($category_name) {
            $output .= '<span class="grx-cat-mw">' . htmlspecialchars($category_name) . '</span>';
        }
        $output .= '<ul class="grx-list-mw">';
        foreach ($related as $g) {
            $title = htmlspecialchars($g['subject']);
            $thumb = !empty($g['thumb']) ? htmlspecialchars($g['thumb']) : 'https://via.placeholder.com/60x60/EEEEEE/999999?text=?';
            $link  = ROOT_PATH . '/index.php?c=article&a=show&id=' . $g['id'];
            $output .= '<li class="grx-item-mw">';
            $output .= '<a class="grx-link-mw" href="' . $link . '" title="' . $title . '">';
            $output .= '<img class="grx-thumb-mw" src="' . $thumb . '" alt="' . $title . '" loading="lazy" />';
            $output .= '<span class="grx-name-mw">' . $title . '</span>';
            $output .= '<span class="grx-arrow-mw">&#x203A;</span>';
            $output .= '</a></li>';
        }
        $output .= '</ul></div>';
        return $output;
    }

    /**
     * retro-pixel：复古像素 - 方块边框 + 像素字体 + 无圆角
     */
    private function _render_related_retro_pixel($game_id, $category_name, $related) {
        $output  = '<div class="game-related grx-pixel" data-game-id="' . $game_id . '">';
        $output .= '<div class="grx-pixel-header">[ RELATED GAMES ]</div>';
        $output .= '<div class="grx-pixel-title">同类推荐</div>';
        $output .= '<div class="grx-pixel-grid">';
        foreach ($related as $g) {
            $title = htmlspecialchars($g['subject']);
            $desc  = htmlspecialchars(mb_substr($g['description'], 0, 40, 'utf-8'));
            $thumb = !empty($g['thumb']) ? htmlspecialchars($g['thumb']) : 'https://via.placeholder.com/300x200/333333/00FF00?text=PIXEL';
            $link  = ROOT_PATH . '/index.php?c=article&a=show&id=' . $g['id'];
            $output .= '<a class="grx-card-rp" href="' . $link . '" title="' . $title . '">';
            $output .= '<div class="grx-thumb-rp"><img src="' . $thumb . '" alt="' . $title . '" loading="lazy" /></div>';
            $output .= '<div class="grx-body-rp">';
            $output .= '<div class="grx-id-rp">[#' . $g['id'] . ']</div>';
            $output .= '<div class="grx-name-rp">' . $title . '</div>';
            $output .= '<div class="grx-desc-rp">' . $desc . '</div>';
            $output .= '</div></a>';
        }
        $output .= '</div></div>';
        return $output;
    }

    /**
     * 渲染游戏截图轮播 Block
     *
     * 从 cms_article 表的 thumb 和 imagenum 字段，以及 cms_article_data.content 中
     * <!-- screenshots: [...] --> JSON 标记提取截图列表，渲染带懒加载的图片轮播。
     *
     * 数据提取优先级：
     * 1. cms_article_data.content 中 <!-- screenshots: [...] --> JSON 标记
     * 2. cms_article.thumb + cms_article.imagenum 生成占位图列表
     * 3. 均无数据时降级到 placeholder 模拟图
     *
     * 主题差异化：不同主题渲染不同的轮播样式（箭头样式、过渡效果、布局等）。
     * 支持的主题：default / green-tech / dark-esports / cartoon-cute / minimal-white / retro-pixel
     *
     * @param   array  $context  渲染上下文（game_id 必填，theme 可选）
     * @return  string  渲染后的 HTML
     */
    public function render_game_screenshots($context = array()) {
        $game_id = isset($context['game_id']) ? (int)$context['game_id'] : 0;
        if (!$game_id) {
            return '<p class="block-error">game_id required</p>';
        }

        $tablepre = $this->db->tablepre;

        // 获取游戏基本信息（thumb + imagenum）
        $game = $this->db->fetch_first("
            SELECT a.thumb, a.imagenum, a.subject
            FROM `{$tablepre}cms_article` a
            WHERE a.id = '{$game_id}' AND a.status = 1
            LIMIT 1
        ");

        if (!$game) {
            return '<p class="block-error">Game not found</p>';
        }

        // 获取文章内容（提取 screenshots JSON 标记）
        $article_data = $this->db->fetch_first("
            SELECT content FROM `{$tablepre}cms_article_data` WHERE id = '{$game_id}' LIMIT 1
        ");

        $screenshots = array();
        if ($article_data && !empty($article_data['content'])) {
            $content = $article_data['content'];
            if (preg_match('/<!--\s*screenshots:\s*(\[.*?\])\s*-->/s', $content, $matches)) {
                $decoded = json_decode($matches[1], true);
                if (is_array($decoded)) {
                    $screenshots = array_values(array_filter($decoded, function($url) {
                        return is_string($url) && !empty(trim($url));
                    }));
                }
            }
        }

        // 降级：从 thumb + imagenum 生成占位图列表
        if (empty($screenshots) && !empty($game['thumb'])) {
            $imagenum = isset($game['imagenum']) ? (int)$game['imagenum'] : 1;
            $screenshots[] = $game['thumb'];
            for ($i = 2; $i <= $imagenum && count($screenshots) < 6; $i++) {
                $ext = pathinfo($game['thumb'], PATHINFO_EXTENSION);
                $base = pathinfo($game['thumb'], PATHINFO_FILENAME);
                $dir = pathinfo($game['thumb'], PATHINFO_DIRNAME);
                $screenshots[] = $dir . '/' . $base . '_' . $i . '.' . $ext;
            }
        }

        // 最终降级：placeholder 模拟图
        if (empty($screenshots)) {
            $screenshots = array(
                'https://via.placeholder.com/800x450/CCCCCC/666666?text=Screenshot+1',
                'https://via.placeholder.com/800x450/CCCCCC/666666?text=Screenshot+2',
                'https://via.placeholder.com/800x450/CCCCCC/666666?text=Screenshot+3',
            );
        }

        $title = $game['subject'];
        $theme = isset($context['theme']) ? $context['theme'] : $this->theme;

        // 按主题分发
        switch ($theme) {
            case 'green-tech':
                $output = $this->_render_screenshots_green_tech($title, $screenshots, $game_id);
                break;
            case 'dark-esports':
                $output = $this->_render_screenshots_dark_esports($title, $screenshots, $game_id);
                break;
            case 'cartoon-cute':
                $output = $this->_render_screenshots_cartoon_cute($title, $screenshots, $game_id);
                break;
            case 'minimal-white':
                $output = $this->_render_screenshots_minimal_white($title, $screenshots, $game_id);
                break;
            case 'retro-pixel':
                $output = $this->_render_screenshots_retro_pixel($title, $screenshots, $game_id);
                break;
            default:
                $output = $this->_render_screenshots_default($title, $screenshots, $game_id);
        }

        return $output;
    }

    // ========== 各主题截图轮播渲染实现 ==========

    /**
     * 构建轮播 slide HTML（所有主题共用）
     */
    private function _build_slides_html($title, $screenshots, $alt_separator = ' 截图 ') {
        $html = '';
        foreach ($screenshots as $i => $url) {
            $escaped_url = htmlspecialchars($url);
            $html .= '<div class="sc-slide" data-index="' . $i . '">';
            $html .= '<img src="' . $escaped_url . '" alt="' . htmlspecialchars($title) . $alt_separator . ($i + 1) . '" loading="lazy" />';
            $html .= '</div>';
        }
        return $html;
    }

    /**
     * 构建指示器 HTML（主题间差异：dot CSS class + 内容格式）
     *
     * @param   array     $screenshots      截图列表
     * @param   string    $dot_class        指示器 span 的 CSS class（不含 active）
     * @param   callable  $content_callback 回调：function($index, $total) => string 指示器内容
     * @return  string
     */
    private function _build_indicators_html($screenshots, $dot_class, $content_callback) {
        $html = '';
        foreach ($screenshots as $i => $_) {
            $class = $i === 0 ? ' class="' . $dot_class . ' active"' : ' class="' . $dot_class . '"';
            $content = call_user_func($content_callback, $i, count($screenshots));
            $html .= '<span' . $class . ' data-index="' . $i . '">' . $content . '</span>';
        }
        return $html;
    }

    /**
     * 构建轮播 IIFE JS 脚本（主题间差异：选择器 class）
     */
    private function _build_carousel_js($carousel_class, $dots_class, $prev_class, $next_class) {
        return '<script>(function(){var s=document.querySelector(".' . $carousel_class . '");if(!s)return;var t=s.querySelector(".sc-track"),d=s.querySelectorAll(".sc-slide"),dots=s.querySelectorAll(".' . $dots_class . '"),prev=s.querySelector(".' . $prev_class . '"),next=s.querySelector(".' . $next_class . '"),idx=0,w=100;function go(n){idx=(n+d.length)%d.length;t.style.transform="translateX(-"+idx*w+"%)";dots.forEach(function(dot,i){dot.classList.toggle("active",i===idx)})}prev&&prev.addEventListener("click",function(){go(idx-1)});next&&next.addEventListener("click",function(){go(idx+1)});dots.forEach(function(dot,i){dot.addEventListener("click",function(){go(i)})})}})();</script>';
    }

    /**
     * default：标准轮播 - 带箭头 + 底部指示器
     */
    private function _render_screenshots_default($title, $screenshots, $game_id) {
        $output  = '<div class="game-screenshots default-theme" data-game-id="' . $game_id . '">';
        $output .= '<h3 class="sc-title">' . htmlspecialchars($title) . ' - 游戏截图</h3>';
        $output .= '<div class="sc-carousel">';
        $output .= '<div class="sc-track">' . $this->_build_slides_html($title, $screenshots) . '</div>';
        $output .= '<button class="sc-arrow sc-arrow-prev" aria-label="上一张">&#x25C0;</button>';
        $output .= '<button class="sc-arrow sc-arrow-next" aria-label="下一张">&#x25B6;</button>';
        $output .= '<div class="sc-indicators">' . $this->_build_indicators_html($screenshots, 'sc-dot', function() { return ''; }) . '</div>';
        $output .= '</div>';
        $output .= $this->_build_carousel_js('sc-carousel', 'sc-dot', 'sc-arrow-prev', 'sc-arrow-next');
        $output .= '</div>';
        return $output;
    }

    /**
     * green-tech：绿色科技 - 带绿色进度条指示器 + 左侧光晕箭头
     */
    private function _render_screenshots_green_tech($title, $screenshots, $game_id) {
        $output  = '<div class="game-screenshots scx-green-tech" data-game-id="' . $game_id . '">';
        $output .= '<div class="scx-header"><span class="scx-icon">&#x26A1;</span>';
        $output .= '<h3 class="sc-title">' . htmlspecialchars($title) . ' 游戏截图</h3></div>';
        $output .= '<div class="sc-carousel sc-carousel-gtx">';
        $output .= '<div class="sc-track">' . $this->_build_slides_html($title, $screenshots) . '</div>';
        $output .= '<button class="sc-arrow sc-arrow-gtx sc-arrow-gtx-prev" aria-label="上一张">&#x2190;</button>';
        $output .= '<button class="sc-arrow sc-arrow-gtx sc-arrow-gtx-next" aria-label="下一张">&#x2192;</button>';
        $output .= '<div class="sc-indicators">' . $this->_build_indicators_html($screenshots, 'sc-dot sc-dot-gtx', function() { return ''; }) . '</div>';
        $output .= '</div>';
        $output .= $this->_build_carousel_js('sc-carousel-gtx', 'sc-dot-gtx', 'sc-arrow-gtx-prev', 'sc-arrow-gtx-next');
        $output .= '</div>';
        return $output;
    }

    /**
     * dark-esports：暗黑电竞 - 霓虹光晕箭头 + 角标指示器
     */
    private function _render_screenshots_dark_esports($title, $screenshots, $game_id) {
        $output  = '<div class="game-screenshots scx-esports" data-game-id="' . $game_id . '">';
        $output .= '<div class="scx-badge">SCREENSHOTS</div>';
        $output .= '<div class="sc-carousel sc-carousel-es">';
        $output .= '<div class="sc-track">' . $this->_build_slides_html($title, $screenshots) . '</div>';
        $output .= '<button class="sc-arrow sc-arrow-es sc-arrow-es-prev" aria-label="上一张">&#x25C0;</button>';
        $output .= '<button class="sc-arrow sc-arrow-es sc-arrow-es-next" aria-label="下一张">&#x25B6;</button>';
        $output .= '<div class="sc-indicators sc-indicators-es">' . $this->_build_indicators_html($screenshots, 'sc-dot sc-dot-es', function($i) { return ($i + 1); }) . '</div>';
        $output .= '</div>';
        $output .= $this->_build_carousel_js('sc-carousel-es', 'sc-dot-es', 'sc-arrow-es-prev', 'sc-arrow-es-next');
        $output .= '</div>';
        return $output;
    }

    /**
     * cartoon-cute：卡通萌系 - 圆角卡片 + 表情箭头 + 柔色指示器
     */
    private function _render_screenshots_cartoon_cute($title, $screenshots, $game_id) {
        $mascots = array('&#x1F638;', '&#x1F639;', '&#x1F63A;', '&#x1F63B;', '&#x1F63D;', '&#x1F63F;');
        $output  = '<div class="game-screenshots scx-cute" data-game-id="' . $game_id . '">';
        $output .= '<div class="scx-cute-header"><span class="scx-mascot">&#x1F4F8;</span>';
        $output .= '<h3 class="sc-title">' . htmlspecialchars($title) . ' 的截图</h3></div>';
        $output .= '<div class="sc-carousel sc-carousel-ct">';
        $output .= '<div class="sc-track">' . $this->_build_slides_html($title, $screenshots) . '</div>';
        $output .= '<button class="sc-arrow sc-arrow-ct sc-arrow-ct-prev" aria-label="上一张">&#x1F449;</button>';
        $output .= '<button class="sc-arrow sc-arrow-ct sc-arrow-ct-next" aria-label="下一张">&#x1F448;</button>';
        $output .= '<div class="sc-indicators">' . $this->_build_indicators_html($screenshots, 'sc-dot sc-dot-ct', function($i) use ($mascots) { return $mascots[$i % count($mascots)]; }) . '</div>';
        $output .= '</div>';
        $output .= $this->_build_carousel_js('sc-carousel-ct', 'sc-dot-ct', 'sc-arrow-ct-prev', 'sc-arrow-ct-next');
        $output .= '</div>';
        return $output;
    }

    /**
     * minimal-white：极简白 - 纯箭头 + 细指示器 + 无装饰
     */
    private function _render_screenshots_minimal_white($title, $screenshots, $game_id) {
        $output  = '<div class="game-screenshots scx-minimal" data-game-id="' . $game_id . '">';
        $output .= '<h3 class="sc-title sc-title-mw">' . htmlspecialchars($title) . ' &mdash; Screenshots</h3>';
        $output .= '<div class="sc-carousel sc-carousel-mw">';
        $output .= '<div class="sc-track">' . $this->_build_slides_html($title, $screenshots, ' screenshot ') . '</div>';
        $output .= '<button class="sc-arrow sc-arrow-mw sc-arrow-mw-prev" aria-label="Previous">&#x2039;</button>';
        $output .= '<button class="sc-arrow sc-arrow-mw sc-arrow-mw-next" aria-label="Next">&#x203A;</button>';
        $output .= '<div class="sc-indicators">' . $this->_build_indicators_html($screenshots, 'sc-dot sc-dot-mw', function() { return ''; }) . '</div>';
        $output .= '</div>';
        $output .= $this->_build_carousel_js('sc-carousel-mw', 'sc-dot-mw', 'sc-arrow-mw-prev', 'sc-arrow-mw-next');
        $output .= '</div>';
        return $output;
    }

    /**
     * retro-pixel：复古像素 - 方块边框 + 无圆角 + 像素箭头
     */
    private function _render_screenshots_retro_pixel($title, $screenshots, $game_id) {
        $output  = '<div class="game-screenshots scx-pixel" data-game-id="' . $game_id . '">';
        $output .= '<div class="scx-pixel-header">[ SCREENSHOTS ]</div>';
        $output .= '<div class="scx-pixel-title">' . htmlspecialchars($title) . '</div>';
        $output .= '<div class="sc-carousel sc-carousel-rp">';
        $output .= '<div class="sc-track">' . $this->_build_slides_html($title, $screenshots, ' SCREENSHOT ') . '</div>';
        $output .= '<button class="sc-arrow sc-arrow-rp sc-arrow-rp-prev" aria-label="prev">[&lt;]</button>';
        $output .= '<button class="sc-arrow sc-arrow-rp sc-arrow-rp-next" aria-label="next">[&gt;]</button>';
        $output .= '<div class="sc-indicators">' . $this->_build_indicators_html($screenshots, 'sc-dot sc-dot-rp', function($i) { return '[' . ($i + 1) . ']'; }) . '</div>';
        $output .= '</div>';
        $output .= $this->_build_carousel_js('sc-carousel-rp', 'sc-dot-rp', 'sc-arrow-rp-prev', 'sc-arrow-rp-next');
        $output .= '</div>';
        return $output;
    }
}
