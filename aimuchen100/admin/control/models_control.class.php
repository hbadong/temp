<?php
defined('ROOT_PATH') or exit;

class models_control extends admin_control {
	// 模型管理
	public function index() {
        // hook admin_models_control_index_before.php

	    $models_filed_plugin = plugin_is_enable('models_filed') ? 1 : 0;
	    $this->assign('models_filed',$models_filed_plugin);

        //表格显示列表
        $cols = "{field: 'mid', width: 80, title: 'mid', align: 'center'},";
        $cols .= "{field: 'name', minwidth: 100, title: '".lang('model_name')."'},";
        $cols .= "{field: 'tablename', minwidth: 100, title: '".lang('table')."'},";
        $cols .= "{field: 'index_tpl', minwidth: 100, title: '".lang('channel_tpl')."', edit: 'text'},";
        $cols .= "{field: 'cate_tpl', minwidth: 100, title: '".lang('cate_tpl')."', edit: 'text'},";
        $cols .= "{field: 'show_tpl', minwidth: 100, title: '".lang('show_tpl')."', edit: 'text'},";
        $cols .= "{field: 'width', width: 100, title: '".lang('pic_width')."', align: 'center', edit: 'text'},";
        $cols .= "{field: 'height', width: 100, title: '".lang('pic_height')."', align: 'center', edit: 'text'},";
        $cols .= "{field: 'icon', minwidth: 100, title: '".lang('menu_icon')."', align: 'center', edit: 'text'},";
        $cols .= "{field: 'system', width: 90,  title: '".lang('system')."', templet: '#models-system', align: 'center'},";
        // hook admin_models_control_index_cols_after.php
        $cols .= "{title: '".lang('opt')."', width: 120, toolbar: '#currentTableBar', align: 'center'}";

        // hook admin_models_control_index_after.php
        $this->assign('cols', $cols);
        $this->display();
	}

    //ajax获取数据
    public function get_list(){
        // hook admin_models_control_get_list_before.php

        $data_arr = array();
        $cms_arr = $this->models->find_fetch(array(), array('mid' => 1));
        foreach ($cms_arr as $v){
            $data_arr[] = $v;
        }
        unset($cms_arr);
        // hook admin_models_control_get_list_data_arr_after.php

        //组合数据 输出到页面
        $arr = array(
            'code' => 0,
            'msg' => '',
            'count' => count($data_arr),
            'data' => $data_arr,
        );
        exit( json_encode($arr) );
    }

    //编辑表格字段
    public function set(){
        // hook admin_models_control_set_before.php
        if( !empty($_POST) ){
            $field = trim( R('field','P') );
            $mid = intval( R('mid','P') );
            $value = trim( R('value','P') );

            if($mid == 1 && $field != 'cate_tpl'){
                E(1, lang('page_model_edit_tpl'));
            }

            if($field == 'width' || $field == 'height'){
                $value = (int)$value;
                if( empty($value) ){
                    E(1, lang('width_and_height_no_0'));
                }
            }

            if(($field == 'index_tpl' || $field == 'cate_tpl' || $field == 'show_tpl') && strlen($value) > $this->models->_field_length[$field]){
                E(1, lang('many_characters', array('field'=>$field,'length'=>$this->models->_field_length[$field])));
            }

            $models = $this->models->get($mid);
            empty($models) && E(1, lang('data_no_exists'));

            $models[$field] = $value;

            if(!$this->models->update($models)) {
                E(1, lang('edit_failed'));
            }
            // hook admin_models_control_set_after.php

            E(0, lang('edit_field_successfully', array('field'=>$field)) );
        }
    }

    //添加
    public function add(){
        if(empty($_POST)) {
            // hook admin_models_control_add_before.php
            $data = array(
                'mid'=>0,
                'name'=>'',
                'tablename'=>'',
                'width'=>160,
                'height'=>120,
                'icon'=>'fa fa-bars',
            );
            // hook admin_models_control_add_data_after.php
            $this->assign('data', $data);

            $input = $this->get_input($data);
            // hook admin_models_control_add_get_input_after.php
            $this->assign('input',$input);

            $this->display('models_set.htm');
        }else{
            // hook admin_models_control_add_post_before.php
            $name = trim(R('name', 'P'));
            $tablename = strtolower(trim(R('tablename', 'P')));
            empty($name) && E(1, lang('modelname_no_empty'));
            empty($tablename) && E(1, lang('modeltablename_no_empty'));

            if( $this->models->find_fetch_key(array('tablename'=> $tablename)) ){
                E(1, lang('modeltablename_is_exist'));
            }elseif( !preg_match('/^[a-z]+$/', $tablename) ) {
                E(1, lang('modeltablename_no_safe'));
            }

            $data = array(
                'name' => $name,
                'tablename' => $tablename,
                'index_tpl' => $tablename.'_index.htm',
                'cate_tpl' => $tablename.'_list.htm',
                'show_tpl' => $tablename.'_show.htm',
                'width' => intval(R('width', 'P')),
                'height' => intval(R('height', 'P')),
                'icon' => trim(R('icon', 'P')),
            );
            // hook admin_models_control_add_post_data_after.php

            if($err = $this->models->xadd($data)) {
                E(1, $err);
            }
            // hook admin_models_control_add_post_success.php
            E(0, lang('add_successfully'));
        }
    }

    protected function get_input($data = array())
    {
        $input = array();

        $input['name'] = form::get_text('name', $data['name'], '', 'placeholder="'.lang('model_name').'" maxlength="'.$this->models->_field_length['name'].'" required="required" lay-verify="required"');
        $input['tablename'] = form::get_text('tablename', $data['tablename'], '', 'placeholder="'.lang('table').'" maxlength="'.$this->models->_field_length['tablename'].'" required="required" lay-verify="required"');
        $input['icon'] = form::get_text('icon', $data['icon'], '', 'placeholder="'.lang('menu_icon').'" maxlength="'.$this->models->_field_length['icon'].'"');
        $input['width'] = form::get_number('width', $data['width'], '', 'placeholder="'.lang('pic_width').'" required="required" lay-verify="required"');
        $input['height'] = form::get_number('height', $data['height'], '', 'placeholder="'.lang('pic_height').'" required="required" lay-verify="required"');

        // hook admin_models_control_get_input_after.php
        return $input;
    }

    //删除
    public function del() {
        // hook admin_models_control_del_before.php
        $mid = (int) R('mid', 'P');
        empty($mid) && E(1, lang('data_error'));

        $err = $this->models->xdelete($mid);
        if($err) {
            E(1, $err);
        }else{
            // hook admin_models_control_del_success.php
            E(0, lang('delete_successfully'));
        }
    }

    // hook admin_models_control_after.php
}
