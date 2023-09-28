<?php
defined('ROOT_PATH') or exit;
class db_control extends admin_control{

    public function index() {
        // hook admin_db_control_index_before.php

        $this->display();
    }

    //获取数据
    public function get_list(){
        // hook admin_db_control_get_list_before.php

        $data_arr = array();
        $sql = "show table status";
        $cms_arr = $this->db->fetch_all($sql);
        foreach ($cms_arr as $v){
            $v['Data_length'] = empty($v['Data_length']) ? 0 : get_byte($v['Data_length']);
            $v['Data_free'] = empty($v['Data_free']) ? 0 : get_byte($v['Data_free']);
            $data_arr[] = $v;
        }
        unset($cms_arr);
        // hook admin_db_control_get_list_data_arr_after.php

        //组合数据 输出到页面
        $arr = array(
            'code' => 0,
            'msg' => '',
            'count' => count($data_arr),
            'data' => $data_arr,
        );
        exit( json_encode($arr) );
    }

    //优化表
    public function optimize_table(){
        // hook admin_db_control_optimize_table_before.php
        $id_arr = R('id_arr', 'P');
        if(!empty($id_arr) && is_array($id_arr)) {
            foreach($id_arr as $table) {
                $sql = 'OPTIMIZE TABLE `'.$table.'`';
                $this->db->fetch_first($sql);
            }
            E(0, lang('opt_successfully'));
        }else{
            E(1, lang('data_error'));
        }
    }

    //修复表
    public function repair_table(){
        // hook admin_db_control_repair_table_before.php
        $id_arr = R('id_arr', 'P');
        if(!empty($id_arr) && is_array($id_arr)) {
            foreach($id_arr as $table) {
                $sql = 'REPAIR TABLE `'.$table.'`';
                $this->db->fetch_first($sql);
            }
            E(0, lang('opt_successfully'));
        }else{
            E(1, lang('data_error'));
        }
    }

    //检查表
    public function check_table(){
        // hook admin_db_control_check_table_before.php
        $id_arr = R('id_arr', 'P');
        if(!empty($id_arr) && is_array($id_arr)) {
            foreach($id_arr as $table) {
                $sql = 'CHECK TABLE `'.$table.'`';
                $this->db->fetch_first($sql);
            }
            E(0, lang('opt_successfully'));
        }else{
            E(1, lang('data_error'));
        }
    }

    //表结构
    public function table_structure(){
        $table = R('table', 'R');
        if($table){
            $sql = 'SHOW CREATE TABLE `'.$table.'`';
            $data = $this->db->fetch_first($sql);
            $table_structure = isset($data['Create Table']) ? $data['Create Table'] : '';
        }else{
            $table_structure = '';
        }
        $this->assign('table', $table);
        $this->assign('table_structure', $table_structure);
        $this->display();
    }


    // hook admin_db_control_after.php
}