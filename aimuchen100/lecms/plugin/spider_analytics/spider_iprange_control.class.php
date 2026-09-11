<?php
defined('ROOT_PATH') || exit;
class spider_iprange_control extends admin_control {
    public function index() {
        require_once ROOT_PATH . 'lecms/plugin/spider_analytics/model/iprange.class.php';;
        require_once ROOT_PATH . 'lecms/plugin/spider_analytics/lib/cidr.class.php';;
        require_once ROOT_PATH . 'lecms/plugin/spider_analytics/lib/runtime.class.php';;
        require_once ROOT_PATH . 'lecms/plugin/spider_analytics/model/runtime.class.php';;
        // 注入 LECMS 数据库 PDO 连接（模型使用 spider_runtime::$pdo 静态属性）
        spider_runtime::init($this->db->rlink, $_ENV['_config']['db']['master']['tablepre']);
        $ir = new spider_iprange();
        $page = max(1, (int)R('page', 'R'));
        $engine = trim(R('engine', 'R'));
        $keyword = trim(R('keyword', 'R'));
        $extra = array();
        if($engine) $extra['engine'] = $engine;
        if($keyword !== '') $extra['keyword'] = $keyword;
        $total = $ir->count_list($engine, $keyword);
        $rows = $ir->list($engine, $keyword, $page, 50);
        $this->view->assign('rows', $rows);
        $this->view->assign('engine', $engine);
        $this->view->assign('keyword', $keyword);
        $pagebar = $this->get_pagebar($total, 50, $page, 5, $extra); $this->view->assign('pagebar', $pagebar);
        $this->view->display('spider_iprange.htm');
    }
    public function add() {
        if (!form_submit()) E(1, lang('submit_invalid'));
        require_once ROOT_PATH . 'lecms/plugin/spider_analytics/model/iprange.class.php';;
        require_once ROOT_PATH . 'lecms/plugin/spider_analytics/lib/cidr.class.php';;
        require_once ROOT_PATH . 'lecms/plugin/spider_analytics/lib/runtime.class.php';;
        require_once ROOT_PATH . 'lecms/plugin/spider_analytics/model/runtime.class.php';;
        // 注入 LECMS 数据库 PDO 连接（模型使用 spider_runtime::$pdo 静态属性）
        spider_runtime::init($this->db->rlink, $_ENV['_config']['db']['master']['tablepre']);
        $ir = new spider_iprange();
        $id = $ir->add(trim(R('engine', 'P')), trim(R('cidr', 'P')), trim(R('note', 'P')), 1);
        if (!$id) E(1, '添加失败');
        E(0, '已添加 #' . $id);
    }
    public function del() {
        if (!form_submit()) E(1, lang('submit_invalid'));
        $id = (int)R('id', 'P');
        require_once ROOT_PATH . 'lecms/plugin/spider_analytics/model/iprange.class.php';;
        require_once ROOT_PATH . 'lecms/plugin/spider_analytics/lib/cidr.class.php';;
        require_once ROOT_PATH . 'lecms/plugin/spider_analytics/lib/runtime.class.php';;
        require_once ROOT_PATH . 'lecms/plugin/spider_analytics/model/runtime.class.php';;
        // 注入 LECMS 数据库 PDO 连接（模型使用 spider_runtime::$pdo 静态属性）
        spider_runtime::init($this->db->rlink, $_ENV['_config']['db']['master']['tablepre']);
        $ir = new spider_iprange();
        $res = $ir->delete($id);
        if (!$res) E(1, '删除失败');
        E(0, '已删除');
    }
}
