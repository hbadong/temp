<?php
defined('ROOT_PATH') || exit;
class spider_blacklist_control extends admin_control {
    public function index() {
        $site_id = defined('CURRENT_SITE_ID') ? (int)CURRENT_SITE_ID : 0;
        $page = max(1, (int)R('page', 'R'));
        require_once ROOT_PATH . 'lecms/plugin/spider_analytics/model/blacklist.class.php';;
        require_once ROOT_PATH . 'lecms/plugin/spider_analytics/lib/cidr.class.php';;
        require_once ROOT_PATH . 'lecms/plugin/spider_analytics/lib/csv.class.php';;
        require_once ROOT_PATH . 'lecms/plugin/spider_analytics/lib/runtime.class.php';;
        require_once ROOT_PATH . 'lecms/plugin/spider_analytics/model/runtime.class.php';;
        // 注入 LECMS 数据库 PDO 连接（模型使用 spider_runtime::$pdo 静态属性）
        spider_runtime::init($this->db->rlink, $_ENV['_config']['db']['master']['tablepre']);
        $bl = new spider_blacklist();
        $total = $bl->count_list($site_id);
        $rows = $bl->list($site_id, $page, 50);
        $intercept = spider_runtime_get('intercept_enabled', '0');
        $this->view->assign('rows', $rows);
        $this->view->assign('intercept_enabled', $intercept);
        $pagebar = $this->get_pagebar($total, 50, $page); $this->view->assign('pagebar', $pagebar);
        $this->view->display('spider_blacklist.htm');
    }
    public function add() {
        if (!form_submit()) E(1, lang('submit_invalid'));
        $site_id = defined('CURRENT_SITE_ID') ? (int)CURRENT_SITE_ID : 0;
        $type = trim(R('match_type', 'P'));
        $value = trim(R('match_value', 'P'));
        $note = trim(R('note', 'P'));
        require_once ROOT_PATH . 'lecms/plugin/spider_analytics/model/blacklist.class.php';;
        require_once ROOT_PATH . 'lecms/plugin/spider_analytics/lib/cidr.class.php';;
        require_once ROOT_PATH . 'lecms/plugin/spider_analytics/lib/csv.class.php';;
        require_once ROOT_PATH . 'lecms/plugin/spider_analytics/lib/runtime.class.php';;
        require_once ROOT_PATH . 'lecms/plugin/spider_analytics/model/runtime.class.php';;
        // 注入 LECMS 数据库 PDO 连接（模型使用 spider_runtime::$pdo 静态属性）
        spider_runtime::init($this->db->rlink, $_ENV['_config']['db']['master']['tablepre']);
        $bl = new spider_blacklist();
        $id = $bl->add($site_id, $type, $value, $note, 1);
        if (!$id) E(1, '添加失败');
        E(0, '已添加 #' . $id);
    }
    public function del() {
        if (!form_submit()) E(1, lang('submit_invalid'));
        $id = (int)R('id', 'P');
        require_once ROOT_PATH . 'lecms/plugin/spider_analytics/model/blacklist.class.php';;
        require_once ROOT_PATH . 'lecms/plugin/spider_analytics/lib/cidr.class.php';;
        require_once ROOT_PATH . 'lecms/plugin/spider_analytics/lib/csv.class.php';;
        require_once ROOT_PATH . 'lecms/plugin/spider_analytics/lib/runtime.class.php';;
        require_once ROOT_PATH . 'lecms/plugin/spider_analytics/model/runtime.class.php';;
        // 注入 LECMS 数据库 PDO 连接（模型使用 spider_runtime::$pdo 静态属性）
        spider_runtime::init($this->db->rlink, $_ENV['_config']['db']['master']['tablepre']);
        $bl = new spider_blacklist();
        $res = $bl->delete($id);
        if (!$res) E(1, '删除失败');
        E(0, '已删除');
    }
    public function import() {
        if (!form_submit()) E(1, lang('submit_invalid'));
        $file = isset($_FILES['file']['tmp_name']) ? $_FILES['file']['tmp_name'] : '';
        if (!$file || !is_uploaded_file($file)) E(1, '未上传文件');
        $ext = strtolower(pathinfo(isset($_FILES['file']['name']) ? $_FILES['file']['name'] : '', PATHINFO_EXTENSION));
        if ($ext !== 'csv') E(1, '仅支持 .csv 文件');
        if (isset($_FILES['file']['size']) && $_FILES['file']['size'] > 2 * 1024 * 1024) E(1, '文件过大，最大 2MB');
        $content = file_get_contents($file);
        require_once ROOT_PATH . 'lecms/plugin/spider_analytics/model/blacklist.class.php';;
        require_once ROOT_PATH . 'lecms/plugin/spider_analytics/lib/cidr.class.php';;
        require_once ROOT_PATH . 'lecms/plugin/spider_analytics/lib/csv.class.php';;
        require_once ROOT_PATH . 'lecms/plugin/spider_analytics/lib/runtime.class.php';;
        require_once ROOT_PATH . 'lecms/plugin/spider_analytics/model/runtime.class.php';;
        try {
            $bl = new spider_blacklist();
            $n = $bl->import_csv($content);
            E(0, "导入 {$n} 条");
        } catch (Exception $e) {
            E(1, '解析失败: ' . $e->getMessage());
        }
    }
    public function export() {
        require_once ROOT_PATH . 'lecms/plugin/spider_analytics/model/blacklist.class.php';;
        require_once ROOT_PATH . 'lecms/plugin/spider_analytics/lib/cidr.class.php';;
        require_once ROOT_PATH . 'lecms/plugin/spider_analytics/lib/csv.class.php';;
        require_once ROOT_PATH . 'lecms/plugin/spider_analytics/lib/runtime.class.php';;
        require_once ROOT_PATH . 'lecms/plugin/spider_analytics/model/runtime.class.php';;
        // 注入 LECMS 数据库 PDO 连接（模型使用 spider_runtime::$pdo 静态属性）
        spider_runtime::init($this->db->rlink, $_ENV['_config']['db']['master']['tablepre']);
        $bl = new spider_blacklist();
        $content = $bl->export_csv();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=spider-blacklist-' . date('Ymd-His') . '.csv');
        echo $content;
        exit;
    }
    public function toggle_intercept() {
        if (!form_submit()) E(1, lang('submit_invalid'));
        require_once ROOT_PATH . 'lecms/plugin/spider_analytics/lib/runtime.class.php';;
        require_once ROOT_PATH . 'lecms/plugin/spider_analytics/model/runtime.class.php';;
        spider_runtime::init($this->db->rlink, $_ENV['_config']['db']['master']['tablepre']);
        $cur = spider_runtime_get('intercept_enabled', '0');
        spider_runtime_set('intercept_enabled', $cur === '1' ? '0' : '1');
        E(0, '已切换');
    }
}
