<?php
/**
 * Author: dadadezhou <379559090@qq.com>
 * Date: 2021-10-09
 * Time: 15:21
 * Description: 友情链接
 */

defined('ROOT_PATH') or exit;

class links_control extends admin_control {

	// 管理链接
	public function index() {
		$this->display();
	}

    //获取列表
    public function get_list(){
        //分页
        $page = isset( $_REQUEST['page'] ) ? intval($_REQUEST['page']) : 1;
        $pagenum = isset( $_REQUEST['limit'] ) ? intval($_REQUEST['limit']) : 15;

        //获取查询条件
        $keyword = isset( $_REQUEST['keyword'] ) ? trim($_REQUEST['keyword']) : '';
        if($keyword) {
            $keyword = urldecode($keyword);
            $keyword = safe_str($keyword);
        }

        //组合查询条件
        $where= array();
        if( $keyword ){
            $where['name'] = array('LIKE'=>$keyword);
        }

        //数据量
        if( $where ){
            $total = $this->links->find_count($where);
        }else{
            $total = $this->links->count();
        }

        //页数
        $maxpage = max(1, ceil($total/$pagenum));
        $page = min($maxpage, max(1, $page));

        // 获取列表
        $data_arr = array();
        $cms_arr = $this->links->list_arr($where, 'id', -1, ($page-1)*$pagenum, $pagenum, $total);
        foreach($cms_arr as &$v) {
            $data_arr[] = $v;   //排序需要索引从0开始
        }
        unset($cms_arr);
        //组合数据 输出到页面
        $arr = array(
            'code' => 0,
            'msg' => '',
            'count' => $total,
            'data' => $data_arr,
        );
        exit( json_encode($arr) );
    }

    //编辑表格字段
    public function set(){
        if( !empty($_POST) ){
            $field = trim( R('field','P') );
            $id = intval( R('id','P') );
            $value = trim( R('value','P') );
            $data = array(
                'id' => $id,
                $field => $value,
            );

            if(!$this->links->update($data)) {
                E(1, lang('edit_failed'));
            }
            E(0, lang('edit').' '.$field.' '.lang('successfully'));
        }
    }

    // 发布
    public function add() {
        if(empty($_POST)) {

            $data = array('orderby'=>0);
            $this->assign('data', $data);

            $this->display('links_set.htm');
        }else{
            $title = trim(strip_tags(R('title', 'P')));
            $url = trim(R('url', 'P'));

            empty($title) && E(1, lang('links_name_no_empty'));
            empty($url) && E(1, lang('links_url_no_empty'));

            if(!preg_match('#^(https?://|//|/|#)#i', $url)) {
                E(1, '链接地址必须以 http(s):// 或 / 开头');
            }

            // 写入内容表
            $data = array(
                'name' => $title,
                'dateline' => $_ENV['_time'],
                'url' => $url,
                'orderby' => intval(R('orderby', 'P')),
            );
            $id = $this->links->create($data);
            if(!$id) {
                E(1, lang('add_failed'));
            }
            E(0, lang('add_successfully'));
        }
    }

    // 编辑
    public function edit() {
        if(empty($_POST)) {
            $id = intval(R('id'));
            $data = $this->links->get($id);
            if(empty($data)) $this->message(0, lang('data_no_exists'), -1);
            $this->assign('data', $data);

            $this->display('links_set.htm');
        }else{
            $id = intval(R('id', 'P'));
            $title = trim(strip_tags(R('title', 'P')));
            $url = trim(R('url', 'P'));

            empty($id) && E(1, lang('data_error'));
            empty($title) && E(1, lang('links_name_no_empty'));
            empty($url) && E(1, lang('links_url_no_empty'));

            if(!preg_match('#^(https?://|//|/|#)#i', $url)) {
                E(1, '链接地址必须以 http(s):// 或 / 开头');
            }

            $data = $this->links->get($id);
            if(empty($data)) E(1, lang('data_no_exists'));

            // 写入内容表
            $data = array(
                'id' => $id,
                'name' => $title,
                'url' => $url,
                'orderby' => intval(R('orderby', 'P')),
            );
            if(!$this->links->update($data)) {
                E(1, lang('edit_failed'));
            }
            E(0, lang('edit_successfully'));
        }
    }

    // 删除
    public function del() {
        $id = (int) R('id', 'P');
        empty($id) && E(1, lang('data_error'));

        $res = $this->links->delete($id);
        if(!$res) {
            E(1, lang('delete_failed'));
        }else{
            E(0, lang('delete_successfully'));
        }
    }

    // 批量删除
    public function batch_del() {
        $id_arr = R('id_arr', 'P');
        if(!empty($id_arr) && is_array($id_arr)) {
            $err_num = 0;
            foreach($id_arr as $id) {
                $res = $this->links->delete($id);
                if(!$res) $err_num++;
            }

            if($err_num) {
                E(1, $err_num.lang('num_del_failed'));
            }else{
                E(0, lang('delete_successfully'));
            }
        }else{
            E(1, lang('data_error'));
        }
    }
}
