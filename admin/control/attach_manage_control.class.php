<?php
defined('ROOT_PATH') or exit;

class attach_manage_control extends admin_control {
    public $_mid = 2;
    public $_table = 'article';
    public $_name = '文章';

    function __construct(){
        parent::__construct();

        $this->_mid = max(2, (int)R('mid','R'));
        $models = $this->models->get($this->_mid);
        empty($models) && $this->message(1, lang('data_error'));

        $this->_table = $models['tablename'];
        $this->_name = $models['name'];

        $this->assign('mid',$this->_mid);
        $this->assign('table',$this->_table);
        $this->assign('name',$this->_name);
    }

	// 内容管理
	public function index() {
		// hook admin_attach_manage_control_index_before.php

        $midhtml = $this->cms_content_attach->get_attachhtml_mid($this->_mid, 'lay-filter="mid"');
        $this->assign('midhtml',$midhtml);

        //表格显示列表
        $cols = "{field: 'filename', title: '".lang('filename')."', edit: 'text'},";
        $cols .= "{field: 'filetype', width: 80, title: '".lang('ext')."', align: 'center'},";
        $cols .= "{field: 'size', width: 120, title: '".lang('filesize')."', align: 'center'},";
        $cols .= "{field: 'filepath', title: '".lang('filepath')."'},";
        $cols .= "{field: 'date', width: 165, title: '".lang('uploadtime')."', align: 'center'},";
        $cols .= "{field: 'id', width: 100, title: '".lang('cmsid')."', align: 'center'},";
        $cols .= "{field: 'uid', width: 80, title: '".lang('uid')."', align: 'center'},";
        $cols .= "{field: 'downloads', width: 100, title: '".lang('downloads')."', align: 'center', edit: 'text'},";
        $cols .= "{field: 'golds', width: 80, title: '".lang('golds')."', align: 'center', edit: 'text'},";
        $cols .= "{field: 'credits', width: 80, title: '".lang('credits')."', align: 'center', edit: 'text'},";
        // hook admin_attach_manage_control_index_cols_after.php
        $cols .= "{title: '".lang('opt')."', width: 105, toolbar: '#currentTableBar', align: 'center'}";

        // hook admin_attach_manage_control_index_after.php
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

        // 初始模型表名
        $this->cms_content_attach->table = 'cms_'.$this->_table.'_attach';

        //组合查询条件
        $where = array();
        if( $id ){
            $where['id'] = $id;
        }
        if( $uid ){
            $where['uid'] = $uid;
        }
        if( $keyword ){
            $where['filename'] = array('LIKE'=>$keyword);
        }
        // hook admin_attach_manage_control_get_list_before.php

        $total = $this->cms_content_attach->find_count($where);

        //页数
        $maxpage = max(1, ceil($total/$pagenum));
        $page = min($maxpage, max(1, $page));

        // 获取列表
        $data_arr = array();
        $cms_arr = $this->cms_content_attach->list_arr($where, 'aid', -1, ($page-1)*$pagenum, $pagenum, $total);

        $keys = array();
        foreach($cms_arr as $v) {
            !empty($v['id']) && $keys[] = $v['id'];
        }
        $this->cms_content->table = 'cms_'.$this->_table;
        $list_arr = $this->cms_content->mget($keys);

        $cfg = $this->runtime->xget();
        foreach($cms_arr as &$v) {
            $v['date'] = date('Y-m-d H:i:s', $v['dateline']);
            $v['size'] = get_byte($v['filesize']);

            $key = 'cms_'.$this->_table.'-id-'.$v['id'];
            if(isset($list_arr[$key])){
                $v['title'] = $list_arr[$key]['title'];
            }else{
                $v['title'] = lang('unknown');
            }

            if( substr($v['filepath'], 0, 2) != '//' && substr($v['filepath'], 0, 4) != 'http' ) { //不是外链
                if($v['isimage']){
                    $updir = 'upload/'.$this->_table.'/';
                }else{
                    $updir = 'upload/attach/';
                }
                $v['url'] = $cfg['webdir'].$updir.$v['filepath'];
            }else{
                $v['url'] = $v['filepath'];
            }

            $data_arr[] = $v;   //排序需要索引从0开始
        }
        // hook admin_attach_manage_control_get_list_after.php
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
            $aid = intval( R('aid','P') );
            $value = trim( R('value','P') );

            $arr = array('downloads', 'golds', 'credits');
            // hook admin_attach_manage_control_set_arr_after.php
            if( in_array($field, $arr) ){
                $value = (int)$value;
            }

            // 初始模型表名
            $this->cms_content_attach->table = 'cms_'.$this->_table.'_attach';
            $attach = $this->cms_content_attach->get($aid);

            empty($attach) && E(1, lang('data_no_exists'));

            $attach[$field] = $value;

            if(!$this->cms_content_attach->update($attach)) {
                E(1, lang('edit_failed'));
            }
            // hook admin_attach_manage_control_set_after.php

            E(0, lang('edit_field_successfully', array('field'=>$field)) );
        }
    }

	// 删除
	public function del() {
		// hook admin_attach_manage_control_del_before.php

        $aid = (int) R('aid', 'P');
		empty($aid) && E(1, lang('data_error'));

        $this->cms_content_attach->table = 'cms_'.$this->_table.'_attach';
		$err = $this->cms_content_attach->xdelete($aid);
		if($err) {
			E(1, $err);
		}else{
            // hook admin_attach_manage_control_del_success.php
            E(0, lang('delete_successfully'));
		}
	}

	// 批量删除无用附件
	public function batch_del() {
		// hook admin_attach_manage_control_batch_del_before.php
		$do = (int) R('do', 'P');

		if(!empty($do)) {
            $this->cms_content_attach->table = 'cms_'.$this->_table.'_attach';
			$err_num = 0;

            $list_arr = $this->cms_content_attach->find_fetch(array('id'=>0), array('aid' => 1), 0, 100);
            foreach ($list_arr as $v){
                $err = $this->cms_content_attach->xdelete($v['aid']);
                if($err){
                    $err_num++;
                }
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

	// hook admin_attach_manage_control_after.php
}
