<?php
defined('ROOT_PATH') or exit;
/**
 * 游戏管理控制器
 * 游戏列表 / 表单 / 删除 / 上下架（对齐软件管理）
 */

class admin_game_control extends admin_control {

    private $platforms = array('PC', 'Android', 'iOS', 'PlayStation', 'Xbox', 'Switch');

    /**
     * 游戏列表
     */
    public function index() {
        $filter = array(
            'site_id' => (int)(R('site_id', 'R') ?: 1),
            'cat_id' => (int)R('cat_id', 'R'),
            'status' => in_array(R('status', 'R'), array('', null), true) ? '' : (int)R('status', 'R'),
            'platform' => trim(R('platform', 'R')),
            'keyword' => trim(R('keyword', 'R')),
        );
        $page = max(1, (int)R('page', 'R'));
        $pagenum = 20;

        $total = $this->game_center->admin_count($filter);
        $rows = $this->game_center->admin_list($filter, $page, $pagenum);

        // 分类名映射（模板不支持动态下标插值，控制器拼好显示字段）
        $cat_names = $this->get_cat_names($filter['site_id']);
        foreach($rows as $k => $r) {
            $rows[$k]['cat_name'] = isset($cat_names[$r['category_id']]) ? $cat_names[$r['category_id']] : '-';
        }

        $pagebar = $this->get_pagebar($total, $pagenum, $page, 5, array(
            'site_id' => $filter['site_id'],
            'cat_id' => $filter['cat_id'],
            'status' => $filter['status'],
            'platform' => $filter['platform'],
            'keyword' => $filter['keyword'],
        ));

        $cats = $this->game_category->admin_all($filter['site_id']);
        $platforms = $this->platforms;
        $site_names = $this->get_site_names();
        $this->assign('rows', $rows);
        $this->assign('total', $total);
        $this->assign('pagebar', $pagebar);
        $this->assign('filter', $filter);
        $this->assign('cats', $cats);
        $this->assign('platforms', $platforms);
        $this->assign('site_names', $site_names);
        $this->display('admin_game_list.htm');
    }

    /**
     * 添加/编辑游戏表单
     */
    public function set() {
        $id = (int)R('id', 'R');
        $site_id = (int)(R('site_id', 'R') ?: 1);
        $data = $id > 0 ? $this->game_center->get($id) : array(
            'id' => 0, 'site_id' => $site_id, 'category_id' => 0, 'name' => '',
            'platform' => 'PC', 'download_url' => '', 'cover' => '',
            'intro' => '', 'description' => '', 'tags' => '', 'status' => 1,
        );
        if($id > 0 && !$data) $this->message(1, lang('data_no_exists'));

        $cats = $this->game_category->admin_all($data['site_id']);
        $this->assign('data', $data);
        $this->assign('cats', $cats);
        $this->assign('platforms', $this->platforms);
        $this->display('admin_game_set.htm');
    }

    /**
     * 保存游戏（新增/更新）
     */
    public function set_post() {
        if(!form_submit()) $this->message(1, lang('submit_invalid'));
        $id = (int)R('id', 'P');
        $site_id = (int)(R('site_id', 'P') ?: 1);
        $name = trim(strip_tags(R('name', 'P')));
        $cat_id = (int)R('category_id', 'P');

        if($name === '') $this->message(1, '游戏名称不能为空');

        // 站点内按名称去重（编辑时排除自身）
        $dup = $this->game_center->get_by_name($name, $site_id);
        if($dup && (int)$dup['id'] !== $id) $this->message(1, '该游戏名称已存在（#' . $dup['id'] . '）');

        $data = array(
            'site_id' => $site_id,
            'category_id' => $cat_id,
            'name' => $name,
            'platform' => trim(R('platform', 'P')),
            'download_url' => trim(R('download_url', 'P')),
            'cover' => trim(R('cover', 'P')),
            'intro' => mb_substr(trim(R('intro', 'P')), 0, 1000, 'UTF-8'),
            'description' => trim(R('description', 'P')),
            'tags' => trim(R('tags', 'P')),
            'status' => (int)R('status', 'P') ? 1 : 0,
        );

        if($id > 0) {
            $data['id'] = $id;
            $ret = $this->game_center->update_game($data);
            $msg = '保存成功';
        } else {
            $id = $this->game_center->create_game($data);
            $ret = (bool)$id;
            $msg = '添加成功';
        }
        if(!$ret) $this->message(1, lang('edit_failed'));
        $this->message(0, $msg, '?admin_game-index');
    }

    /**
     * 删除游戏
     */
    public function del() {
        if(!form_submit()) $this->message(1, lang('submit_invalid'));
        $id = (int)R('id', 'P');
        if(!$this->game_center->get($id)) $this->message(1, lang('data_no_exists'));
        $ret = $this->game_center->delete_game($id);
        $this->message($ret ? 0 : 1, $ret ? '已删除' : lang('delete_failed'));
    }

    /**
     * 上下架切换
     */
    public function toggle() {
        if(!form_submit()) $this->message(1, lang('submit_invalid'));
        $id = (int)R('id', 'P');
        $row = $this->game_center->get($id);
        if(!$row) $this->message(1, lang('data_no_exists'));
        $new_status = $row['status'] == 1 ? 0 : 1;
        $ret = $this->game_center->update_game(array('id' => $id, 'status' => $new_status));
        $this->message($ret ? 0 : 1, $new_status == 1 ? '已上架' : '已下架');
    }

    private function get_cat_names($site_id) {
        $arr = array();
        foreach($this->game_category->admin_all($site_id) as $c) {
            $arr[$c['id']] = $c['name'];
        }
        return $arr;
    }

    private function get_site_names() {
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];
        $rows = $this->db->fetch_all("SELECT sid, site_name FROM `{$tablepre}site_manager` ORDER BY sid ASC");
        $arr = array();
        if($rows) {
            foreach($rows as $r) {
                $arr[$r['sid']] = $r['site_name'];
            }
        }
        return $arr;
    }
}