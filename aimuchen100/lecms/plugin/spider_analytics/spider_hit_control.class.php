<?php
defined('ROOT_PATH') || exit;
class spider_hit_control extends admin_control {
    public function index() {
        $site_id = defined('CURRENT_SITE_ID') ? (int)CURRENT_SITE_ID : 0;
        require_once ROOT_PATH . 'lecms/plugin/spider_analytics/model/blacklist.class.php';;
        require_once ROOT_PATH . 'lecms/plugin/spider_analytics/lib/cidr.class.php';;
        require_once ROOT_PATH . 'lecms/plugin/spider_analytics/lib/csv.class.php';;
        require_once ROOT_PATH . 'lecms/plugin/spider_analytics/lib/runtime.class.php';;
        require_once ROOT_PATH . 'lecms/plugin/spider_analytics/model/runtime.class.php';;
        // 注入 LECMS 数据库 PDO 连接（模型使用 spider_runtime::$pdo 静态属性）
        spider_runtime::init($this->db->rlink, $_ENV['_config']['db']['master']['tablepre']);
        $bl = new spider_blacklist();
        $rows = $bl->list_hits($site_id, 100);
        $this->view->assign('rows', $rows);
        $this->view->display('spider_hit.htm');
    }
}
