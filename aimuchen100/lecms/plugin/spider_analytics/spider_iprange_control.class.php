<?php
defined('ROOT_PATH') || exit;
require_once ROOT_PATH . 'lecms/plugin/spider_analytics/lib/boot.class.php';
class spider_iprange_control extends admin_control {
    public function index() {
        spider_boot($this->db);
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
        spider_boot($this->db);
        $ir = new spider_iprange();
        try {
            $id = $ir->add(trim(R('engine', 'P')), trim(R('cidr', 'P')), trim(R('note', 'P')), 1);
        } catch (InvalidArgumentException $e) {
            E(1, $e->getMessage());
        }
        if (!$id) E(1, '添加失败');
        E(0, '已添加 #' . $id);
    }
    public function del() {
        if (!form_submit()) E(1, lang('submit_invalid'));
        $id = (int)R('id', 'P');
        spider_boot($this->db);
        $ir = new spider_iprange();
        if (!$ir->get($id)) E(1, '记录不存在');
        $res = $ir->delete($id);
        if (!$res) E(1, '删除失败');
        E(0, '已删除');
    }
}
