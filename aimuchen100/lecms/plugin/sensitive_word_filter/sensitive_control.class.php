<?php
defined('ROOT_PATH') or exit;

/**
 * 敏感词管理控制器
 */

class sensitive_control extends admin_control {

    private $tablepre;
    private $site_id;

    public function __construct() {
        parent::__construct();
        $this->site_id = defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0;
        $this->tablepre = $_ENV['_config']['db']['master']['tablepre'];
    }

    /**
     * 默认入口（跳转到敏感词列表）
     */
    public function index() {
        $this->words();
    }

    /**
     * 敏感词列表
     */
    public function words() {
        $page = max(1, (int)R('page', 'R'));
        $pagenum = 20;
        $site_id = (int)$this->site_id;

        $sql = "SELECT * FROM `{$this->tablepre}sensitive_words`
                WHERE site_id = {$site_id}
                ORDER BY id DESC";

        $total_row = $this->db->fetch_first("SELECT COUNT(*) AS cnt FROM ({$sql}) t");
        $total = $total_row ? $total_row['cnt'] : 0;

        $sql .= " LIMIT {$pagenum} OFFSET " . (($page - 1) * $pagenum);
        $list = $this->db->fetch_all($sql);

        $this->assign('list', $list);
        $this->assign('total', $total);
        $pagebar = $this->get_pagebar($total, $pagenum, $page); $this->assign('pagebar', $pagebar);

        // 插件设置（合并自原 settings() 页面，作为第二个 tab 展示）
        $setting_file = PLUGIN_PATH . 'sensitive_word_filter/setting.php';
        $settings = array(
            'enabled' => 1,
            'replace_char' => '*',
            'log_enabled' => 1,
            'filter_content_types' => array('ai_generate', 'content_create', 'content_update'),
            'refresh_interval_days' => 30,
            'filter_mode' => 'replace',
            'whitelist_domains' => '',
        );

        if (file_exists($setting_file)) {
            $saved = include $setting_file;
            if (is_array($saved)) {
                $settings = array_merge($settings, $saved);
            }
        }

        // 列表回显：内容类型白名单字符串
        $checked_types = array();
        if (!empty($settings['filter_content_types']) && is_array($settings['filter_content_types'])) {
            $checked_types = $settings['filter_content_types'];
        }

        $this->assign('settings', $settings);
        $this->assign('checked_types', $checked_types);
        $this->display('sensitive_words.htm');
    }

    /**
     * 添加敏感词
     */
    public function word_add_post() {
        if (!form_submit()) {
            E(1, lang('submit_invalid'));
        }

        $word = trim(R('word', 'P'));
        $category = trim(R('category', 'P'));
        $level = (int)R('level', 'P');

        if (empty($word)) {
            E(1, '敏感词不能为空');
        }

        $category = $category ?: 'default';
        $level = in_array($level, array(1, 2, 3)) ? $level : 2;

        // db_pdo_mysql 无 insert() 方法，手工拼接 INSERT SQL（字符串 addslashes 转义）
        $this->db->query("
            INSERT INTO `{$this->tablepre}sensitive_words`
                (`site_id`, `word`, `category`, `level`, `status`, `created_at`)
            VALUES ({$this->site_id}, '" . addslashes($word) . "', '" . addslashes($category) . "',
                    {$level}, 1, " . (int)$_ENV['_time'] . ")
        ");

        E(0, '添加成功');
    }

    /**
     * 删除敏感词
     */
    public function word_delete_post() {
        if (!form_submit()) {
            E(1, lang('submit_invalid'));
        }

        $id = (int)R('id', 'P');
        $site_id = (int)$this->site_id;
        $this->db->query("
            DELETE FROM `{$this->tablepre}sensitive_words`
            WHERE id = {$id} AND site_id = {$site_id}
        ");

        E(0, '删除成功');
    }

    /**
     * 过滤日志
     */
    public function logs() {
        $page = max(1, (int)R('page', 'R'));
        $pagenum = 20;
        $site_id = (int)$this->site_id;

        $sql = "SELECT l.*, w.category
                FROM `{$this->tablepre}sensitive_log` l
                LEFT JOIN `{$this->tablepre}sensitive_words` w ON l.word = w.word
                WHERE l.site_id = {$site_id}
                ORDER BY l.id DESC";

        $total_row = $this->db->fetch_first("SELECT COUNT(*) AS cnt FROM ({$sql}) t");
        $total = $total_row ? $total_row['cnt'] : 0;

        $sql .= " LIMIT {$pagenum} OFFSET " . (($page - 1) * $pagenum);
        $list = $this->db->fetch_all($sql);

        $this->assign('list', $list);
        $this->assign('total', $total);
        $pagebar = $this->get_pagebar($total, $pagenum, $page); $this->assign('pagebar', $pagebar);
        $this->display('sensitive_logs.htm');
    }

    /**
     * 规则配置页（兼容重定向）
     *
     * 设置页已合并进敏感词主页面第二个 tab，此处仅做跳转，
     * 兼容旧导航/收藏链接。配置展示逻辑见 words()。
     */
    public function settings() {
        $this->message(0, '', '?sensitive-index');
    }

    /**
     * 保存规则配置
     */
    public function settings_post() {
        if (!form_submit()) {
            E(1, lang('submit_invalid'));
        }

        $enabled = (int)R('enabled', 'P');
        $replace_char = trim(R('replace_char', 'P'));
        $log_enabled = (int)R('log_enabled', 'P');
        $content_types = R('content_types', 'P', array());

        if (empty($replace_char)) {
            $replace_char = '*';
        }
        if (mb_strlen($replace_char) > 10) {
            E(1, '替换字符不能超过10个字符');
        }

        // 白名单校验：仅保留合法内容类型
        $allowed_types = array('ai_generate', 'content_create', 'content_update');
        if (!is_array($content_types)) {
            $content_types = array();
        }
        $content_types = array_intersect($content_types, $allowed_types);

        // 新增字段：词库刷新周期
        $refresh_days = (int)R('refresh_interval_days', 'P');
        if ($refresh_days < 1) {
            $refresh_days = 1;
        }
        if ($refresh_days > 365) {
            $refresh_days = 365;
        }

        // 新增字段：过滤模式
        $mode = trim(R('filter_mode', 'P'));
        $allowed_modes = array('replace', 'reject', 'warning');
        if (!in_array($mode, $allowed_modes)) {
            $mode = 'replace';
        }

        // 新增字段：白名单域名（每行一个，PHP 5.4 兼容换行处理）
        $whitelist_raw = trim(R('whitelist_domains', 'P'));
        $whitelist_raw = str_replace(array("\r\n", "\r"), "\n", $whitelist_raw);
        $whitelist_arr = array();
        if ($whitelist_raw !== '') {
            $lines = explode("\n", $whitelist_raw);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line !== '') {
                    $whitelist_arr[] = $line;
                }
            }
        }

        $settings = array(
            'enabled' => $enabled ? 1 : 0,
            'replace_char' => $replace_char,
            'log_enabled' => $log_enabled ? 1 : 0,
            'filter_content_types' => $content_types,
            'refresh_interval_days' => $refresh_days,
            'filter_mode' => $mode,
            'whitelist_domains' => $whitelist_arr,
        );

        $setting_file = PLUGIN_PATH . 'sensitive_word_filter/setting.php';
        $export = "<?php\nreturn " . var_export($settings, true) . ";\n";
        $written = file_put_contents($setting_file, $export);
        if ($written === false) {
            E(1, '配置保存失败，请检查目录权限');
        }

        E(0, '配置保存成功');
    }
}
