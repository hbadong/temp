<?php
defined('ROOT_PATH') || exit;
class data_export_control extends admin_control {
    public function index() {
        $page = max(1, (int)R('page', 'R'));
        $pagenum = 20;
        $pre = $_ENV['_config']['db']['master']['tablepre'];

        $status = trim(R('status', 'R'));
        $keyword = trim(R('keyword', 'R'));
        $extra = array();
        $where = ' WHERE 1';
        if($status && in_array($status, array('running', 'done', 'failed', 'expired', 'deleted'))) {
            $where .= " AND status = '" . addslashes($status) . "'";
            $extra['status'] = $status;
        }
        if($keyword !== '') {
            $kw = addslashes($keyword);
            $where .= " AND (file_path LIKE '%{$kw}%' OR current_file LIKE '%{$kw}%')";
            $extra['keyword'] = $keyword;
        }

        $total_row = $this->db->fetch_first("SELECT COUNT(*) AS cnt FROM {$pre}export_log{$where}");
        $total = $total_row ? (int)$total_row['cnt'] : 0;

        $offset = ($page - 1) * $pagenum;
        $rows = $this->db->fetch_all("SELECT * FROM {$pre}export_log{$where} ORDER BY id DESC LIMIT {$pagenum} OFFSET {$offset}");
        $rows = $rows ?: array();

        $pagebar = $this->get_pagebar($total, $pagenum, $page, 5, $extra);
        $this->assign('rows', $rows);
        $this->assign('status', $status);
        $this->assign('keyword', $keyword);
        $this->assign('pagebar', $pagebar);

        // 插件设置（原 settings() 逻辑合并进主页面第二个 tab）
        $settings = $this->runtime->xget('data_export_settings');
        if (!$settings || !is_array($settings)) {
            $settings = array(
                'default_mode' => 'full',
                'zip_level' => 6,
                'retention_days' => 30,
            );
        }
        $this->assign('settings', $settings);

        $this->display('export_list.htm');
    }
    public function start() {
        if (!form_submit()) $this->message(0, lang('submit_invalid'));
        $site_id = (int)R('site_id');
        $mode = R('mode') === 'cleanup' ? 'cleanup' : 'full';
        $operator_uid = $this->_uid;
        require_once ROOT_PATH . 'lecms/plugin/data_export/model/export_log.class.php';;
        require_once ROOT_PATH . 'lecms/plugin/data_export/model/exporter.class.php';;
        $pre = $_ENV['_config']['db']['master']['tablepre'];
        // PHP 5.4 兼容：不用 ?? 运算符
        // 注意：rlink 为魔术属性（__get 懒加载），不可用 isset() 判断，否则恒为 null
        $pdo = $this->db->rlink;
        spider_export_log::init($pdo, $pre);
        $log = new spider_export_log();
        $id = $log->create($site_id, $mode, $operator_uid);
        // ROOT_PATH 直接定位根目录；避免 __FILE__ 路径计算在 runcache 编译产物中失效
        $root = isset($_ENV['_config']['webdir_abs']) ? rtrim($_ENV['_config']['webdir_abs'], '/') : ROOT_PATH;
        $theme = isset($_ENV['_config']['theme']) ? $_ENV['_config']['theme'] : 'default';
        spider_exporter::init($pdo, $pre);
        spider_exporter::run($id, $site_id, $mode, $root, $theme);
        // HTTP_REFERER 常量框架未定义，改用 $_SERVER（PHP 5.4 兼容，避免未定义常量）
        $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '?data_export-index';
        $this->message(1, '导出已启动 #' . $id, $referer);
    }
    public function progress() {
        $id = (int)R('id');
        $pre = $_ENV['_config']['db']['master']['tablepre'];
        $row = $this->db->fetch_first("SELECT progress,current_file,status,error_msg FROM {$pre}export_log WHERE id=" . (int)$id);
        $this->message(1, json_encode($row ?: array('status' => 'not_found')));
    }
    public function delete() {
        if (!form_submit()) $this->message(0, lang('submit_invalid'));
        $id = (int)R('id', 'P');
        $pre = $_ENV['_config']['db']['master']['tablepre'];
        $row = $this->db->fetch_first("SELECT file_path FROM {$pre}export_log WHERE id=" . (int)$id);
        if ($row && !empty($row['file_path'])) {
            // ROOT_PATH 直接定位根目录；避免 __FILE__ 路径计算在 runcache 编译产物中失效
            $root = isset($_ENV['_config']['webdir_abs']) ? rtrim($_ENV['_config']['webdir_abs'], '/') : ROOT_PATH;
            $abs = $root . '/' . $row['file_path'];
            if (file_exists($abs)) @unlink($abs);
        }
        $this->db->query("UPDATE {$pre}export_log SET status='deleted' WHERE id=" . (int)$id);
        $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '?data_export-index';
        $this->message(1, '已删除', $referer);
    }

    /**
     * 插件设置已合并进主页面第二个 tab，兼容重定向
     */
    public function settings() {
        $this->message(0, '', '?data_export-index-tab-settings');
    }

    /**
     * 保存插件设置
     */
    public function settings_post() {
        if (!form_submit()) {
            $this->message(1, lang('submit_invalid'));
        }

        $mode = trim(R('default_mode', 'P'));
        $allowed_modes = array('full', 'cleanup');
        if (!in_array($mode, $allowed_modes)) {
            $mode = 'full';
        }

        $zip_level = (int)R('zip_level', 'P');
        if ($zip_level < 0) {
            $zip_level = 0;
        }
        if ($zip_level > 9) {
            $zip_level = 9;
        }

        $retention = (int)R('retention_days', 'P');
        if ($retention < 1) {
            $retention = 1;
        }
        if ($retention > 365) {
            $retention = 365;
        }

        $settings = array(
            'default_mode' => $mode,
            'zip_level' => $zip_level,
            'retention_days' => $retention,
        );

        $this->runtime->set('data_export_settings', $settings);
        $this->runtime->save_changed();
        E(0, '插件设置已保存');
    }
}
