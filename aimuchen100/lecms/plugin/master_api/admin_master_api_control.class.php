<?php
defined('ROOT_PATH') or exit;
/**
 * 同步主站 API Token 管理
 * Token 列表 / 生成 / 删除 / 启停；限频计数在 master_api 模型内更新，页面仅展示
 */

class admin_master_api_control extends admin_control {

    public function index() {
        require_once ROOT_PATH . 'lecms/plugin/master_api/model/master_api_model.class.php';
        $m = new master_api();

        $page = max(1, (int)R('page', 'R'));
        $pagenum = 20;
        $total = $m->admin_count();
        $rows = $m->admin_list($page, $pagenum);

        // 展示型字段：最近请求/限频窗口剩余（控制器拼好，模板不写复杂逻辑）
        $now = time();
        $win = $now - ($now % $m->rate_window);
        foreach($rows as $k => $r) {
            $rows[$k]['win_left'] = $win === (int)$r['last_request_at']
                ? max(0, $m->rate_limit - (int)$r['request_count'])
                : $m->rate_limit;
            $rows[$k]['last_hit'] = (int)$r['last_request_at'] ? date('Y-m-d H:i:s', (int)$r['last_request_at']) : '-';
        }

        $pagebar = $this->get_pagebar($total, $pagenum, $page, 5, array());
        $this->assign('rows', $rows);
        $this->assign('total', $total);
        $this->assign('pagebar', $pagebar);
        $this->assign('page', $page);
        $this->display('admin_master_api_list.htm');
    }

    /**
     * 生成新 Token
     */
    public function create_post() {
        if(!form_submit()) $this->message(1, lang('submit_invalid'));
        require_once ROOT_PATH . 'lecms/plugin/master_api/model/master_api_model.class.php';
        $m = new master_api();
        $remark = trim(R('remark', 'P'));
        $token = $m->generate($remark);
        if(!$token) $this->message(1, 'Token 生成失败');
        $this->message(0, '已生成 Token: ' . $token, 'index.php?admin_master_api-index');
    }

    /**
     * 删除 Token
     */
    public function delete() {
        require_once ROOT_PATH . 'lecms/plugin/master_api/model/master_api_model.class.php';
        $m = new master_api();
        $id = (int)R('id', 'R');
        if($id <= 0) $this->message(1, '参数错误');
        $m->delete($id);
        $this->message(0, '已删除', 'index.php?admin_master_api-index');
    }

    /**
     * 启停 Token
     */
    public function toggle() {
        require_once ROOT_PATH . 'lecms/plugin/master_api/model/master_api_model.class.php';
        $m = new master_api();
        $id = (int)R('id', 'R');
        $row = $id > 0 ? $m->read($id) : null;
        if(!$row) $this->message(1, 'Token 不存在');
        $m->update(array('id' => $id, 'enabled' => (int)$row['enabled'] ? 0 : 1));
        $this->message(0, '已更新', 'index.php?admin_master_api-index');
    }
}