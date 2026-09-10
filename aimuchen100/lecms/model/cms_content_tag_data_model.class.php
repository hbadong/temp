<?php
defined('ROOT_PATH') or exit;

class cms_content_tag_data extends model {
	function __construct() {
		$this->table = '';					// 内容标签数据表表名 比如 cms_article_tag_data
		$this->pri = array('tagid', 'id');	// 主键
	}

	// 获取标签数据对应列表
	public function list_arr($tagid = 0, $orderway = 1, $start = 0, $limit = 0, $total = 0, $extra = array()) {
        // hook cms_content_tag_data_model_list_arr_before.php

		// 优化大数据量翻页
		if($start > 1000 && $total > 2000 && $start > $total/2) {
			$orderway = -$orderway;
			$newstart = $total-$start-$limit;
			if($newstart < 0) {
				$limit += $newstart;
				$newstart = 0;
			}
			$list_arr = $this->find_fetch(array('tagid' => $tagid), array('id' => $orderway), $newstart, $limit);
            $list_arr = array_reverse($list_arr, TRUE);
		}else{
            $list_arr = $this->find_fetch(array('tagid' => $tagid), array('id' => $orderway), $start, $limit);
		}

        // hook cms_content_tag_data_model_list_arr_after.php
        return $list_arr;
	}

    // hook cms_content_tag_data_model_after.php
}
