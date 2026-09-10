<?php
/**
 * Author: dadadezhou <379559090@qq.com>
 * Date: 2021-10-11
 * Time: 10:32
 * Description: 友情链接 model
 */

defined('ROOT_PATH') or exit;

class links extends model {

    function __construct() {
        $this->table = 'links';	// 表名
        $this->pri = array('id');	// 主键
        $this->maxid = 'id';		// 自增字段
    }

    // 获取内容列表
    public function list_arr($where, $orderby, $orderway, $start, $limit, $total) {
        // 优化大数据量翻页
        if($start > 1000 && $total > 2000 && $start > $total/2) {
            $orderway = -$orderway;
            $newstart = $total-$start-$limit;
            if($newstart < 0) {
                $limit += $newstart;
                $newstart = 0;
            }
            $list_arr = $this->find_fetch($where, array($orderby => $orderway), $newstart, $limit);
            return array_reverse($list_arr, TRUE);
        }else{
            return $this->find_fetch($where, array($orderby => $orderway), $start, $limit);
        }
    }
}
