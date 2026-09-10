<?php
defined('ROOT_PATH') or exit;

class category_import_control extends admin_control {

    public function __construct() {
        parent::__construct();
        $this->import_log = core::model('import_log');
    }

    /**
     * 分类导入首页
     */
    public function index() {
        $site_id = defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0;

        // 获取统计数据
        $stats = $this->get_import_stats($site_id);

        // 获取分类列表
        $categories = $this->get_category_list($site_id);

        // 插件设置（原 title_rule_control::settings() 的配置读取逻辑，合并进本页第二个 tab）
        $settings = $this->runtime->xget('article_strategy_settings');
        if (!$settings || !is_array($settings)) {
            $settings = array(
                'default_title_rule' => '',
                'collect_interval' => 5,
                'random_factor' => 30,
                'max_concurrent' => 3,
            );
        }

        // 获取可用标题规则列表（仅当前站点）
        $rules_list = array();
        $all_rules = $this->title_rule->get_list($site_id);
        if (is_array($all_rules)) {
            foreach ($all_rules as $r) {
                $rules_list[] = array(
                    'sid' => isset($r['sid']) ? $r['sid'] : 0,
                    'name' => isset($r['name']) ? $r['name'] : '',
                );
            }
        }

        $this->assign('stats', $stats);
        $this->assign('categories', $categories);
        $this->assign('settings', $settings);
        $this->assign('rules_list', $rules_list);
        $this->display('category_import.htm');
    }

    /**
     * 从 Steam API 导入游戏
     */
    public function import() {
        $site_id = defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0;
        $category = isset($_GET['category']) ? trim($_GET['category']) : '';
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;

        // 验证参数
        $limit = max(1, min($limit, 500));

        // 创建导入日志
        $log_data = array(
            'site_id' => $site_id,
            'filename' => 'steam_api',
            'total_rows' => 0,
            'imported' => 0,
            'skipped' => 0,
            'status' => 0,
            'report' => array(),
        );
        $log_id = $this->import_log->create($log_data);

        // 调用 Steam 适配器
        $adapter = new steam_adapter($site_id, $this->db);
        $games = $adapter->fetch_games($category, $limit);

        if (empty($games)) {
            $this->import_log->save($log_id, array(
                'status' => 2,
                'report' => array('error' => 'No games fetched from Steam API', 'last_error' => $adapter->get_last_error_message()),
            ));
            E(1, 'Failed to fetch games from Steam API: ' . $adapter->get_last_error_message());
        }

        // 导入
        $report = $adapter->import($games);

        // 更新日志
        $this->import_log->save($log_id, array(
            'total_rows' => $report['total'],
            'imported' => $report['imported'],
            'skipped' => $report['skipped'],
            'status' => empty($report['errors']) ? 1 : 2,
            'report' => $report,
        ));

        $msg = "Steam import completed: {$report['imported']} imported, {$report['skipped']} skipped";
        if (!empty($report['errors'])) {
            $msg .= ', ' . count($report['errors']) . ' errors';
        }

        E(0, $msg);
    }

    /**
     * 处理 CSV 文件上传导入
     */
    public function import_post() {
        if (!form_submit()) {
            E(1, lang('submit_invalid'));
        }

        $site_id = defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0;

        if (empty($_FILES['csv_file']['name'])) {
            E(1, 'Please select a CSV file to upload');
        }

        $upload_file = $_FILES['csv_file'];
        if ($upload_file['error'] !== 0) {
            E(1, 'File upload failed with error code: ' . $upload_file['error']);
        }

        // 检查文件类型
        $file_ext = strtolower(pathinfo($upload_file['name'], PATHINFO_EXTENSION));
        if ($file_ext !== 'csv') {
            E(1, 'Only .csv files are allowed');
        }

        // 检查文件大小 (限制 10MB)
        $max_size = 10 * 1024 * 1024;
        if ($upload_file['size'] > $max_size) {
            E(1, 'File size exceeds 10MB limit');
        }

        // 创建导入日志
        $log_data = array(
            'site_id' => $site_id,
            'filename' => $upload_file['name'],
            'total_rows' => 0,
            'imported' => 0,
            'skipped' => 0,
            'status' => 0,
            'report' => array(),
        );
        $log_id = $this->import_log->create($log_data);
        if (!$log_id) {
            E(1, 'Failed to create import log');
        }

        // 解析 CSV
        $importer = new csv_importer();
        $importer->db = $this->db;
        $result = $importer->parse($upload_file['tmp_name']);

        if (isset($result['error'])) {
            $this->import_log->save($log_id, array(
                'status' => 2,
                'report' => array('error' => $result['error']),
            ));
            E(1, 'Parse error: ' . $result['error']);
        }

        $games = $result['games'];

        // 导入到数据库
        $report = $importer->import_to_db($site_id, $games);

        // 更新日志
        $this->import_log->save($log_id, array(
            'total_rows' => $report['total'],
            'imported' => $report['imported'],
            'skipped' => $report['skipped'],
            'status' => empty($report['errors']) ? 1 : 2,
            'report' => array(
                'imported' => $report['imported'],
                'skipped' => $report['skipped'],
                'invalid_rows' => $report['invalid_rows'],
                'parse_errors' => isset($result['parse_errors']) ? $result['parse_errors'] : array(),
                'errors' => $report['errors'],
            ),
        ));

        $msg = "CSV import completed: {$report['imported']} imported, {$report['skipped']} skipped";
        if (!empty($report['errors'])) {
            $msg .= ', ' . count($report['errors']) . ' errors';
        }

        E(0, $msg);
    }

    /**
     * 获取导入统计数据
     * @param int $site_id 站点ID
     * @return array 统计信息
     */
    private function get_import_stats($site_id) {
        $site_id = (int)$site_id;
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];

        $stats = array(
            'total_games' => 0,
            'total_categories' => 0,
            'recent_imports' => 0,
        );

        // 游戏总数
        $row = $this->db->fetch_first("
            SELECT COUNT(*) AS cnt FROM `{$tablepre}cms_game`
            WHERE site_id = '{$site_id}'
        ");
        if ($row) {
            $stats['total_games'] = (int)$row['cnt'];
        }

        // 分类总数
        $row = $this->db->fetch_first("
            SELECT COUNT(*) AS cnt FROM `{$tablepre}article_category`
            WHERE site_id = '{$site_id}' AND is_active = 1
        ");
        if ($row) {
            $stats['total_categories'] = (int)$row['cnt'];
        }

        // 最近7天导入数
        $row = $this->db->fetch_first("
            SELECT COUNT(*) AS cnt FROM `{$tablepre}article_import_log`
            WHERE site_id = '{$site_id}'
            AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
        if ($row) {
            $stats['recent_imports'] = (int)$row['cnt'];
        }

        return $stats;
    }

    /**
     * 获取分类列表
     * @param int $site_id 站点ID
     * @return array 分类列表
     */
    private function get_category_list($site_id) {
        $site_id = (int)$site_id;
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];

        $categories = array();
        $categories = $this->db->fetch_all("
            SELECT c.id, c.name, COUNT(g.id) AS game_count
            FROM `{$tablepre}article_category` c
            LEFT JOIN `{$tablepre}cms_game` g ON g.site_id = c.site_id AND g.category_id = c.id
            WHERE c.site_id = '{$site_id}' AND c.is_active = 1
            GROUP BY c.id, c.name
            ORDER BY c.sort ASC, c.id ASC
        ");

        if ($categories) {
            foreach ($categories as &$row) {
                $row['id'] = (int)$row['id'];
                $row['game_count'] = (int)$row['game_count'];
            }
            unset($row);
        }

        return $categories;
    }
}
