<?php
defined('ROOT_PATH') || exit;
require_once ROOT_PATH . 'lecms/plugin/spider_analytics/lib/boot.class.php';
class spider_hit_control extends admin_control {
    public function index() {
        $site_id = defined('CURRENT_SITE_ID') ? (int)CURRENT_SITE_ID : 0;
        $page = max(1, (int)R('page', 'R'));
        $pagenum = 20;
        spider_boot($this->db);
        $bl = new spider_blacklist();
        $total = $bl->count_hits($site_id);
        $rows = $bl->list_hits($site_id, $pagenum, ($page - 1) * $pagenum);
        $this->view->assign('rows', $rows);
        $pagebar = $this->get_pagebar($total, $pagenum, $page);
        $this->view->assign('pagebar', $pagebar);
        $this->view->display('spider_hit.htm');
    }
}
