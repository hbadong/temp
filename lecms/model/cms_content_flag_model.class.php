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
        // hook cms_content_flag_model_xflags_center.php

        //更新内容表
        if( $this->cms_content->update(array('id'=>$id, 'flags'=>implode(',', $result))) ){
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
