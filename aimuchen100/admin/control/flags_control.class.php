<?php
defined('ROOT_PATH') or exit;

class flags_control extends admin_control {
    public $_mid = 2;
    public $_table = 'article';
    public $_name = '文章';

    function __construct(){
        parent::__construct();

        $this->_mid = max(2, (int)R('mid','R'));

        if($this->_mid > 2){
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

        // 初始模型表名
        $this->cms_content->table = 'cms_'.$this->_table;
        $this->cms_content_flag->table = 'cms_'.$this->_table.'_flag';

        $this->assign('mid',$this->_mid);
        $this->assign('table',$this->_table);
        $this->assign('name',$this->_name);
    }

	// 管理
	public function index() {
		// hook admin_flags_control_index_before.php
        $midhtml = $this->cms_content_flag->get_flaghtml_mid($this->_mid, 'lay-filter="mid"');
        $this->assign('midhtml',$midhtml);

        $flag = intval(R('flag'));
        $flaghtml = $this->cms_content_flag->get_flaghtml($flag, $_ENV['_config']['admin_lang'], lang('all'));
        $this->assign('flaghtml',$flaghtml);

        //表格显示列表
        $cols = "{type: 'checkbox', width: 50, fixed: 'left'},";
        $cols .= "{field: 'title', title: '".lang('title')."'},";
        $cols .= "{field: 'category', title: '".lang('category')."'},";
        $cols .= "{field: 'flag_fmt', width: 100, title: '".lang('flag')."', align: 'center'},";
        // hook admin_flags_control_index_cols_after.php
        $cols .= "{title: '".lang('opt')."', width: 105, toolbar: '#currentTableBar', align: 'center'}";

        // hook admin_flags_control_index_after.php
        $this->assign('cols', $cols);
		$this->display();
	}

    //获取列表
    public function get_list(){
        // hook admin_flags_control_get_list_before.php
        //分页
        $page = isset( $_REQUEST['page'] ) ? intval($_REQUEST['page']) : 1;
        $pagenum = isset( $_REQUEST['limit'] ) ? intval($_REQUEST['limit']) : 15;

        //获取查询条件
        $flag = isset( $_REQUEST['flag'] ) ? intval($_REQUEST['flag']) : 0;

        //组合查询条件
        $where = array();
        if( $flag ){
            $where['flag'] = $flag;
        }
        // hook admin_flag_control_get_list_where_after.php
        //数据量
        if( $where ){
            $total = $this->cms_content_flag->find_count($where);
        }else{
            $total = $this->cms_content_flag->count();
        }

        //页数
        $maxpage = max(1, ceil($total/$pagenum));
        $page = min($maxpage, max(1, $page));

        // 获取列表
        $data_arr = array();
        $list_arr = $this->cms_content_flag->list_arr($where, 'id', -1, ($page-1)*$pagenum, $pagenum, $total);
        $keys = array();
        foreach ($list_arr as $v){
            $keys[] = $v['id'];
        }

        $cms_arr = $this->cms_content->mget($keys);
        $key = 'cms_'.$this->_table.'-id-';

        $allcategory = $this->category->get_category_db();

        foreach ($list_arr as &$v){
            $v['category'] = isset($allcategory[$v['cid']]) ? $allcategory[$v['cid']]['name'] : lang('unknown');

            $v['flag_fmt'] = '';
            $v['url'] = '';
            if( isset($cms_arr[$key.$v['id']]) ){
                $cms_data = $cms_arr[$key.$v['id']];
                $this->cms_content->format($cms_data, $this->_mid);

                $v['title'] = $cms_data['title'];
                $v['url'] = $cms_data['url'];

                foreach ($cms_data['flag_arr'] as $flag){
                    $v['flag_fmt'] .= '<a class="layui-badge-rim" target="_blank" href="'.$flag['url'].'">'.$flag['name'].'</a>&nbsp;';
                }

                if( $_ENV['_config']['admin_lang'] == 'zh-cn' ){
                    $v['flag_fmt'] = $this->cms_content->flag_arr[$v['flag']];
                }else{
                    $v['flag_fmt'] = $this->cms_content->flag_arr_en[$v['flag']];
                }

            }else{
                $v['title'] = lang('unknown');
            }

            $data_arr[] = $v;   //排序需要索引从0开始
        }

        unset($cms_arr);
        // hook admin_flags_control_get_list_data_arr_after.php

        //组合数据 输出到页面
        $arr = array(
            'code' => 0,
            'msg' => '',
            'count' => $total,
            'data' => $data_arr,
        );
        exit( json_encode($arr) );
    }

	// 删除
	public function del() {
		// hook admin_flags_control_del_before.php

		$flag = (int) R('flag', 'P');
        $id = (int) R('id', 'P');
        (empty($flag) || empty($id)) && E(1, lang('data_error'));

		$err = $this->cms_content_flag->xdelete($this->_table, $flag, $id);
		if($err) {
			E(1, $err);
		}else{
            // hook admin_flags_control_del_success.php
			E(0, lang('delete_successfully'));
		}
	}

	// 批量删除
	public function batch_del() {
		// hook admin_flags_control_batch_del_before.php
		$id_arr = R('id_arr', 'P');

		if(!empty($id_arr) && is_array($id_arr)) {
			$err_num = 0;
			foreach($id_arr as $v) {
				$err = $this->cms_content_flag->xdelete($this->_table, $v[0], $v[1]);
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

	// hook admin_flags_control_after.php
}
