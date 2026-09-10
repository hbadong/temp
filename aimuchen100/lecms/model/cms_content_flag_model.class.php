<?php
defined('ROOT_PATH') or exit;

class cms_content_flag extends model {
	function __construct() {
		$this->table = '';					// 内容属性表表名 比如 cms_article_flag
		$this->pri = array('flag', 'id');	// 主键
	}

    // 获取内容列表
    public function list_arr($where = array(), $orderby = 'id', $orderway = 1, $start = 0, $limit = 0, $total = 0, $extra = array()) {
        // hook cms_content_flag_model_list_arr_before.php

        // 优化大数据量翻页
        if($start > 1000 && $total > 2000 && $start > $total/2) {
            $orderway = -$orderway;
            $newstart = $total-$start-$limit;
            if($newstart < 0) {
                $limit += $newstart;
                $newstart = 0;
            }
            $list_arr = $this->find_fetch($where, array($orderby => $orderway), $newstart, $limit);
            $list_arr = array_reverse($list_arr, TRUE);
        }else{
            $list_arr = $this->find_fetch($where, array($orderby => $orderway), $start, $limit);
        }

        // hook cms_content_flag_model_list_arr_after.php
        return $list_arr;
    }

    //获取模型属性下拉框
    public function get_flaghtml_mid($mid = 2, $str = ''){
        $tmp = $this->models->find_fetch(array(), array('mid'=>1));
        $s = '<select name="mid" id="mid" '.$str.'>';
        if(empty($tmp)) {
            $s .= '<option value="0">'.lang('no_data').'</option>';
        }else{
            foreach($tmp as $v) {
                if($v['mid'] > 1){
                    if( $_ENV['_config']['admin_lang'] == 'zh-cn' ){
                        $modelname = $v['name'];
                    }else{
                        $modelname = ucfirst($v['tablename']).' ';
                    }
                    $s .= '<option value="'.$v['mid'].'"'.($v['mid'] == $mid ? ' selected="selected"' : '').'>'.$modelname.'</option>';
                }
            }
        }
        $s .= '</select>';
        // hook cms_content_flag_model_get_flaghtml_mid_after.php
        return $s;
    }

    //属性下拉
    public function get_flaghtml($flag = 0, $lang = 'zh-cn', $tips = '选择属性'){
	    if($lang == 'zh-cn'){
            $tmp = $this->cms_content->flag_arr;
        }else{
            $tmp = $this->cms_content->flag_arr_en;
        }
        // hook cms_content_flag_model_get_flaghtml_before.php
        $s = '<select name="flag" id="flag" lay-filter="flag">';
        if(empty($tmp)) {
            $s .= '<option value="0">'.lang('no_data').'</option>';
        }else{
            $s .= '<option value="0"'.(empty($flag) ? ' selected="selected"': '').'>'.$tips.'</option>';
            foreach($tmp as $k=>$v) {
                $s .= '<option value="'.$k.'"'.($k == $flag ? ' selected="selected"' : '').'>'.$v.'</option>';
            }
        }
        $s .= '</select>';
        // hook cms_content_flag_model_get_flaghtml_after.php
        return $s;
    }

    //关联删除
    public function xdelete($table = 'article', $del_flag = 0, $id = 0){
        if(empty($del_flag) || empty($id)){return lang('data_error');}
        // hook cms_content_flag_model_xdelete_before.php

        $this->table = 'cms_'.$table.'_flag';
        $this->cms_content->table = 'cms_'.$table;

        $cms_data = $this->cms_content->get($id);
        if( $cms_data ){
            $r = $this->delete($del_flag, $id);
            if($r){
                $flag_arr = explode(',', $cms_data['flags']);
                $is_exists = 0;
                foreach ($flag_arr as $k=>$flag){
                    if($flag == $del_flag){
                        unset($flag_arr[$k]);
                        $is_exists = 1;
                        break;
                    }
                }
                if($is_exists){
                    $cms_data['flags'] = implode(',', $flag_arr);
                    $this->cms_content->update($cms_data);
                }
                return '';
            }else{
                return lang('delete_failed');
            }
        }else{
            $this->delete($del_flag, $id);
            return '';
        }
    }

    //设置或者取消某个属性
    public function set_flag($table = 'article', $cid = 0, $id = 0, $flag = 0, $exist = 0){
        if(empty($id) || empty($cid) || empty($flag)){return false;}

        // hook cms_content_flag_model_set_flag_before.php

        $this->table = 'cms_'.$table.'_flag';
        $this->cms_content->table = 'cms_'.$table;

        $cms_content = $this->cms_content->get($id);
        if( empty($cms_content) ){return false;}

        if($exist){ //取消属性
            $flags = $cms_content['flags'];
            $flags_arr = explode(',', $flags);
            if( empty($flags_arr) || !in_array($flag, $flags_arr) ){return false;}

            $remove = array($flag);
            $diff = array_diff($flags_arr, $remove);
            if($diff){
                $cms_content['flags'] = implode(',', $diff);
            }else{
                $cms_content['flags'] = '';
            }

            if($this->cms_content->update($cms_content)){
                $this->delete($flag, $id);
                return true;
            }else{
                return false;
            }
        }else{  //设置属性
            $flags = $cms_content['flags'];
            $flags_arr = explode(',', $flags);
            if( $flags_arr && in_array($flag, $flags_arr) ){
                return false;
            }

            if($flags_arr){
                $flags_arr = array_merge($flags_arr, array($flag));
                $cms_content['flags'] = implode(',', $flags_arr);
            }else{
                $cms_content['flags'] = $flag;
            }

            if($this->cms_content->update($cms_content)){
                $this->set(array($flag, $id), array('flag'=>$flag,'cid'=>$cid,'id'=>$id));
                return true;
            }else{
                return false;
            }
        }
    }

    //设置属性（保留旧的）
    public function xflags($table = 'article', $id = 0, $cid = 0, $flags = array()){
        if(empty($id) || empty($cid)){return false;}
        // hook cms_content_flag_model_xflags_before.php

        $this->table = 'cms_'.$table.'_flag';
        $this->cms_content->table = 'cms_'.$table;

        $cms_content = $this->cms_content->get($id);
        if( empty($cms_content) ){return false;}

        // 比较属性变化
        if($cms_content['flags']) {
            $flags_old = explode(',', $cms_content['flags']);
            $result = $flags_old;
            $cha = array();
            foreach ($flags as $flag){
                if( !in_array($flag, $result) ){
                    $result[] = $flag;
                    $cha[] = $flag;
                }
            }
        }else{
            $result = $cha = $flags;
        }
        $cms_content['flags'] = implode(',', $result);
        // hook cms_content_flag_model_xflags_center.php

        //更新内容表
        if( $this->cms_content->update($cms_content) ){
            // 写入内容属性标记表（如果有旧的，就只要写入差异的）
            foreach($cha as $flag) {
                $this->set(array($flag, $id), array('cid'=>$cid));
            }
            // hook cms_content_flag_model_xflags_success.php
            return true;
        }else{
            // hook cms_content_flag_model_xflags_failed.php
            return false;
        }
    }

    //设置属性（只要新的，不要旧的）
//    public function xflags($table = 'article', $id = 0, $cid = 0, $flags = array()){
//
//        if(empty($id) || empty($cid)){return false;}
//
//        $this->table = 'cms_'.$table.'_flag';
//        $this->cms_content->table = 'cms_'.$table;
//
//        $cms_content = $this->cms_content->get($id);
//        if( empty($cms_content) ){return false;}
//
//        // 比较属性变化
//        $flags_old = array();
//        if($cms_content['flags']) {
//            $flags_old = explode(',', $cms_content['flags']);
//            foreach($flags as $flag) {
//                $key = array_search($flag, $flags_old);
//                if($key !== false) unset($flags_old[$key]);
//            }
//        }
//
//        //更新内容表
//        if( $this->cms_content->update(array('id'=>$id, 'flags'=>implode(',', $flags))) ){
//
//            // 写入内容属性标记表
//            foreach($flags as $flag) {
//                $this->set(array($flag, $id), array('cid'=>$cid));
//            }
//            // 删除去掉的属性
//            foreach($flags_old as $flag) {
//                $flag = intval($flag);
//                if($flag) $this->delete($flag, $id);
//            }
//
//            return true;
//        }else{
//            return false;
//        }
//    }

    // hook cms_content_flag_model_after.php
}
