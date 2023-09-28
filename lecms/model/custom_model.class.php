<?php
defined('ROOT_PATH') or exit;
//用户自定义模型， 主键必须是id
class custom extends model {
    private $data = array();		// 防止重复查询

	function __construct() {
		$this->table = '';			// 表名 (用户自定义表)
		$this->pri = array('id');	// 主键
		$this->maxid = 'id';		// 自增字段
	}

    // 获取内容列表
    public function list_arr($where = array(), $orderby = 'id', $orderway = 1, $start = 0, $limit = 0, $total = 0, $extra = array()) {
        // hook custom_model_list_arr_before.php

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

        // hook custom_model_list_arr_after.php
        return $list_arr;
    }

    // hook custom_model_after.php
}
