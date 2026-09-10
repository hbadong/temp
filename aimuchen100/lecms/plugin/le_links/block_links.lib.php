<?php
defined('ROOT_PATH') || exit;

/**
 * 友情链接插件
 * @param string cate 分类
 * @param string orderby 排序方式
 * @param int orderway 降序(-1),升序(1)
 * @param int start 开始位置
 * @param int limit 显示几条
 * @return array
 */
function block_links($conf) {
	global $run;

	$where = array();

	// hook block_links_before.php

    $orderby = isset($conf['orderby']) && in_array($conf['orderby'], array('id', 'orderby')) ? $conf['orderby'] : 'id';
    $orderway = isset($conf['orderway']) && $conf['orderway'] == 1 ? 1 : -1;
    $start = _int($conf, 'start');
    $limit = _int($conf, 'limit', 10);

	$arr = $run->links->find_fetch($where, array($orderby => $orderway), $start, $limit);

	// hook block_links_after.php

    return $arr;
}
