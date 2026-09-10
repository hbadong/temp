<?php
defined('ROOT_PATH') or exit;

class comment_control extends admin_control {
    public $_mid = 2;
    public $_table = 'article';
    public $_name = '文章';

    function __construct(){
        parent::__construct();

        $this->_mid = max(1, (int)R('mid','R'));
        if($this->_mid != 2){
            $models = $this->models->get($this->_mid);
            empty($models) && $this->message(1, lang('data_error'));

            $this->_table = $models['tablename'];
        }else{
            $models = array(
                'name'=>$this->_name,
                'tablename'=>$this->_table
            );
        }

        if( $_ENV['_config']['admin_lang'] == 'zh-cn' ){
            $this->_name = $models['name'];
        }else{
            $this->_name = ucfirst($models['tablename']);
        }

        $this->assign('mid',$this->_mid);
        $this->assign('table',$this->_table);
        $this->assign('name',$this->_name);
    }

	// 内容管理
	public function index() {
		// hook admin_comment_control_index_before.php

        $midhtml = $this->cms_content_comment->get_commenthtml_mid($this->_mid, 'lay-filter="mid"', 1);
        $this->assign('midhtml',$midhtml);


        //表格显示列表
        $cols = "{type: 'checkbox', width: 50, fixed: 'left'},";
        $cols .= "{field: 'title', title: '".lang('title')."'},";
        $cols .= "{field: 'uid', width: 100, title: '".lang('uid')."', align: 'center'},";
        $cols .= "{field: 'author', width: 100, title: '".lang('author')."', align: 'center', edit: 'text'},";
        $cols .= "{field: 'date', width: 145, title: '".lang('date')."', align: 'center'},";
        $cols .= "{field: 'fullip', width: 145, title: '".lang('ip')."', align: 'center'},";
        $cols .= "{field: 'content', minwidth: 150, title: '".lang('content')."', edit: 'text'},";
        // hook admin_comment_control_index_cols_after.php
        $cols .= "{title: '".lang('opt')."', width: 100, toolbar: '#currentTableBar', align: 'center'}";

        // hook admin_comment_control_index_after.php
        $this->assign('cols', $cols);
		$this->display();
	}

    //ajax获取列表
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
        $uid = isset( $_REQUEST['uid'] ) ? trim($_REQUEST['uid']) : 0;
        $id = isset( $_REQUEST['id'] ) ? trim($_REQUEST['id']) : 0;

        //组合查询条件
        $where['mid'] = $this->_mid;
        if( $id ){
            $where['id'] = $id;
        }
        if( $uid ){
            $where['uid'] = $uid;
        }
        if( $keyword ){
            $where['content'] = array('LIKE'=>$keyword);
        }
        // hook admin_comment_control_get_list_where_after.php

        $total = $this->cms_content_comment->find_count($where);

        //页数
        $maxpage = max(1, ceil($total/$pagenum));
        $page = min($maxpage, max(1, $page));

        // 获取列表
        $data_arr = array();
        $cms_arr = $this->cms_content_comment->list_arr($where, 'commentid', -1, ($page-1)*$pagenum, $pagenum, $total);

        $keys = array();
        foreach($cms_arr as $v) {
            $keys[] = $v['id'];
        }

        if($this->_mid > 1){
            $this->cms_content->table = 'cms_'.$this->_table;
            $list_arr = $this->cms_content->mget($keys);

            foreach($cms_arr as &$v) {
                $this->cms_content_comment->format($v, 'Y-m-d H:i', false);

                $key = 'cms_'.$this->_table.'-id-'.$v['id'];
                if(isset($list_arr[$key])){
                    $v['title'] = $list_arr[$key]['title'];
                    $v['url'] = $this->cms_content->comment_url($list_arr[$key]['cid'], $list_arr[$key]['id']);
                }else{
                    $v['title'] = lang('unknown');
                    $v['url'] = '';
                }

                $data_arr[] = $v;   //排序需要索引从0开始
            }
        }else{
            $list_arr = $this->category->mget($keys);

            foreach($cms_arr as &$v) {
                $this->cms_content_comment->format($v, 'Y-m-d H:i', false);

                $key = 'category-cid-'.$v['id'];
                if(isset($list_arr[$key])){
                    $v['title'] = $list_arr[$key]['name'];
                    $v['url'] = $this->cms_content->comment_url($list_arr[$key]['cid'], $list_arr[$key]['cid']);
                }else{
                    $v['title'] = lang('unknown');
                    $v['url'] = '';
                }

                $data_arr[] = $v;   //排序需要索引从0开始
            }
        }

        unset($cms_arr);
        // hook admin_comment_control_get_list_data_arr_after.php

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
        // hook admin_comment_control_set_before.php
        if( !empty($_POST) ){
            $field = trim( R('field','P') );
            $commentid = intval( R('commentid','P') );
            $value = trim( R('value','P') );

            $comment = $this->cms_content_comment->get($commentid);
            empty($comment) && E(1, lang('data_no_exists'));

            $comment[$field] = $value;

            if(!$this->cms_content_comment->update($comment)) {
                E(1, lang('edit_failed'));
            }
            // hook admin_comment_control_set_after.php

            E(0, lang('edit_field_successfully', array('field'=>$field)) );
        }
    }

	// 删除
	public function del() {
		// hook admin_comment_control_del_before.php

        $commentid = (int) R('commentid', 'P');
		empty($commentid) && E(1, lang('data_error'));

		$err = $this->cms_content_comment->xdelete($this->_table, $commentid);
		if($err) {
			E(1, $err);
		}else{
            // hook admin_comment_control_del_success.php
			E(0, lang('delete_successfully'));
		}
	}

	// 批量删除
	public function batch_del() {
		// hook admin_comment_control_batch_del_before.php
		$id_arr = R('id_arr', 'P');

		if(!empty($id_arr) && is_array($id_arr)) {

			$err_num = 0;
			foreach($id_arr as $commentid) {
				$err = $this->cms_content_comment->xdelete($this->_table, $commentid);
				if($err) $err_num++;
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

	// hook admin_comment_control_after.php
}
