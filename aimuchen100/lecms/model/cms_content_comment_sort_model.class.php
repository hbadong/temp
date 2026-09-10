<?php
defined('ROOT_PATH') or exit;

class cms_content_comment_sort extends model {
	function __construct() {
		$this->table = 'cms_comment_sort';			// 表名
		$this->pri = array('mid', 'id');	// 主键
	}

    // 获取列表
    public function list_arr($where = array(), $orderway = 1, $start = 0, $limit = 0, $total = 0, $field = 'id', $extra = array()) {
        // hook cms_content_comment_sort_model_list_arr_before.php

        // 优化大数据量翻页
        if($start > 1000 && $total > 2000 && $start > $total/2) {
            $orderway = -$orderway;
            $newstart = $total-$start-$limit;
            if($newstart < 0) {
                $limit += $newstart;
                $newstart = 0;
            }
            $list_arr = $this->find_fetch($where, array($field => $orderway), $newstart, $limit);
            $list_arr = array_reverse($list_arr, TRUE);
        }else{
            $list_arr = $this->find_fetch($where, array($field => $orderway), $start, $limit);
        }

        // hook cms_content_comment_sort_model_list_arr_after.php
        return $list_arr;
    }

    // hook cms_content_comment_sort_model_after.php
}
