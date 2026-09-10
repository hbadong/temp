<?php
defined('ROOT_PATH') || exit;

/**
 * 分类展示模块(返回数据格式不一样)
 * @param int cid 分类ID 如果不填：自动识别
 * @param string type 显示类型   同级(sibling)、子级(child)、父级(parent)、顶级(top)
 * @param int mid 模型ID (默认自动识别)
 * @param string nocids 排除的分类cid串 多个用英文逗号隔开
 * @param int limit 显示几条
 * @param int life 缓存时间
 * @return array
 */
function block_category_new($conf) {
	global $run;

	// hook block_category_new_before.php

	$cid = isset($conf['cid']) ? intval($conf['cid']) : _int($_GET, 'cid');
	$mid = isset($conf['mid']) ? intval($conf['mid']) : (isset($run->_var['mid']) ? $run->_var['mid'] : 2);
	$type = isset($conf['type']) && in_array($conf['type'], array('sibling', 'child', 'parent', 'top')) ? $conf['type'] : 'sibling';
    $nocids = empty($conf['nocids']) ? '' : $conf['nocids'];
    $limit = _int($conf, 'limit', 0);
    $life = isset($conf['life']) ? (int)$conf['life'] : (isset($run->_cfg['life']) ? (int)$run->_cfg['life'] : 0);
    $cache_params = array($cid, $mid, $type, $nocids, $limit);
    // hook block_category_new_conf_after.php

    //优先从缓存表读取。加前缀，避免不同的block，相同的cache_params，导致缓存数据错乱
    $cache_key = $life ? md5('category_new'.serialize($cache_params)) : '';
    if($cache_key){
        $cache_data = $run->runtime->get_block_data_cache($cache_key);
        if($cache_data){
            return $cache_data;
        }
    }

	$cate_arr = $run->category->get_category_db();

    //cid 验证
    if($cid){
        if( !isset($cate_arr[$cid]) ){
            return false;
        }else{
            $category = $cate_arr[$cid];
            $mid = $category['mid'];
            $run->category->format($category);
        }
    }else{
        $category = array();
    }

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
        // hook block_category_new_foreach_after.php
	}

	//返回当前分类信息和分类列表信息
	$ret = $category;

    if($limit && $cate_arr){$cate_arr = array_slice($cate_arr, 0, $limit);}
	$ret['list'] = $cate_arr;

    // hook block_category_new_set_block_data_cache_before.php
    if($cache_key){
        $run->runtime->set_block_data_cache($cache_key, $ret, $life);
    }

	// hook block_category_new_after.php

	return $ret;
}
