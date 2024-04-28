<?php
defined('ROOT_PATH') or exit;
class user_group_control extends admin_control{

    public function index(){
        // hook admin_user_group_control_index_before.php

        //表格显示列表
        $cols = "{type: 'checkbox', width: 50, fixed: 'left'},";
        $cols .= "{field: 'groupid', width: 120, title: 'groupid', align: 'center'},";
        $cols .= "{field: 'groupname', minwidth: 100, title: '".lang('user_group')."', edit: 'text'},";
        $cols .= "{field: 'system', width: 100,  title: '".lang('system')."', templet: '#models-system', align: 'center'},";
        // hook admin_user_group_control_index_cols_after.php
        $cols .= "{title: '".lang('opt')."', width: 100, toolbar: '#currentTableBar', align: 'center'}";

        // hook admin_user_group_control_index_after.php
        $this->assign('cols', $cols);
        $this->display();
    }

    //获取数据
    public function get_list(){
        // hook admin_user_group_control_get_list_before.php

        $data_arr = array();
        $cms_arr = $this->user_group->find_fetch();
        foreach ($cms_arr as $v){
            $data_arr[] = $v;
        }
        unset($cms_arr);
        // hook admin_user_group_control_get_list_data_arr_after.php

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
        // hook admin_user_group_control_set_before.php
        if( !empty($_POST) ){
            $field = trim( R('field','P') );
            $groupid = intval( R('groupid','P') );
            $value = trim( R('value','P') );

            $group = $this->user_group->get($groupid);
            empty($group) && E(1, lang('data_no_exists'));

            $group['groupid'] < 5  && E(1, lang('admin_group_dis_edit'));

            $group[$field] = $value;

            if(!$this->user_group->update($group)) {
                E(1, lang('edit_failed'));
            }

            // hook admin_user_group_control_set_after.php

            E(0, lang('edit_field_successfully', array('field'=>$field)) );
        }
    }

    //添加
    public function add(){
        // hook admin_user_group_control_add_before.php
        if($_POST){
            $groupname = trim(R('groupname', 'P'));
            empty($groupname) && E(1, lang('groupname_no_empty'));

            $groupdata = $this->user_group->get_user_group_by_groupname($groupname);
            if($groupdata) E(1, lang('groupname_is_exists'));

            $data = array('groupname'=>$groupname, 'system'=>0);

            // hook admin_user_group_control_add_post_after.php

            if($this->user_group->create($data)) {
                E(0, lang('add_successfully'));
            }else{
                E(1, lang('add_failed'));
            }
        }
    }

    public function del() {
        // hook admin_user_group_control_del_before.php
        $groupid = (int) R('groupid', 'P');
        empty($groupid) && E(1, lang('data_error'));

        $err = $this->user_group->xdelete($groupid);

        // hook admin_user_group_control_del_after.php
        if(!$err) {
            E(0, lang('delete_successfully'));
        }else{
            E(1, $err);
        }
    }

    public function batch_del(){
        // hook admin_user_group_control_batch_del_before.php
        $id_arr = R('id_arr', 'P');
        if(!empty($id_arr) && is_array($id_arr)) {
            $err_num = 0;
            foreach($id_arr as $v) {
                $err = $this->user_group->xdelete($v);
                if($err) $err_num++;
            }

            // hook admin_user_group_control_batch_del_after.php
            if($err_num) {
                E(1, $err_num.lang('num_del_failed'));
            }else{
                E(0, lang('delete_successfully'));
            }
        }else{
            E(1, lang('data_error'));
        }
    }

    // hook admin_user_group_control_after.php
}