<?php
defined('ROOT_PATH') || exit;

/**
 * 分类展示模块
 * @param int cid 分类ID 如果不填：自动识别
 * @param string type 显示类型   同级(sibling)、子级(child)、父级(parent)、顶级(top)
 * @param int mid 模型ID (默认自动识别)
 * @param string nocids 排除的分类cid串 多个用英文逗号隔开
 * @return array
 */
function block_category($conf) {
	global $run;

	// hook block_category_before.php

	$cid = isset($conf['cid']) ? intval($conf['cid']) : _int($_GET, 'cid');
	$mid = isset($conf['mid']) ? intval($conf['mid']) : (isset($run->_var['mid']) ? $run->_var['mid'] : 2);
	$type = isset($conf['type']) && in_array($conf['type'], array('sibling', 'child', 'parent', 'top')) ? $conf['type'] : 'sibling';
    $nocids = empty($conf['nocids']) ? '' : $conf['nocids'];
    // hook block_category_conf_after.php

	$cate_arr = $run->category->get_category_db();

    //排除的cid
    if($nocids){
        $nocids_arr = explode(',', $nocids);
    }else{
        $nocids_arr = array();
    }

	switch($type) {
		case 'sibling':
			$upid = isset($cate_arr[$cid]) ? $cate_arr[$cid]['upid'] : 0;
			break;
		case 'child':
			$upid = $cid;
			break;
		case 'parent':
			$upid = isset($cate_arr[$cid]) ? $cate_arr[$cid]['upid'] : 0;
			$upid = isset($cate_arr[$upid]) ? $cate_arr[$upid]['upid'] : $upid;
			break;
		case 'top':
			$upid = 0;
	}

	foreach($cate_arr as $k => &$v) {
		if($v['upid'] != $upid || $v['mid'] != $mid) {
			unset($cate_arr[$k]);
		}

        if($nocids_arr && in_array($v['cid'], $nocids_arr)){
            unset($cate_arr[$k]);
        }

        $run->category->format($v);
        // hook block_category_foreach_after.php
	}

	// hook block_category_after.php

	return $cate_arr;
}
