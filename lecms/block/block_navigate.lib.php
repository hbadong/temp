<?php
defined('ROOT_PATH') || exit;

/**
 * 导航模块 (最多支持两级)
 * @param int mobile 移动端导航
 * @param int showcate 是否显示分类信息
 * @param string nocids 排除的分类cid串 多个用英文逗号隔开
 * @return array
 */
function block_navigate($conf) {
	global $run;

    // hook block_navigate_before.php
    $mobile = isset($conf['mobile']) ? intval($conf['mobile']) : 0;
    $showcate = _int($conf, 'showcate', 0);
    $nocids = empty($conf['nocids']) ? '' : $conf['nocids'];
	// hook block_navigate_conf_after.php

    if($mobile){
        $field = 'navigate_mobile';
    }else{
        $field = 'navigate';
    }

    if($showcate){
        $allcategorys = $run->category->get_category_db();
    }else{
        $allcategorys = array();
    }

    //排除的cid
    if($nocids){
        $nocids_arr = explode(',', $nocids);
    }else{
        $nocids_arr = array();
    }

	$nav_arr = $run->kv->xget($field);
	foreach($nav_arr as &$v) {
        if($nocids_arr && in_array($v['cid'], $nocids_arr)){
            continue;
        }
		if($v['cid'] > 0) {
			$v['url'] = $run->category->category_url($v);
		}
        if($v['cid'] > 0 && $allcategorys){
            $v['category'] = isset($allcategorys[$v['cid']]) ? $allcategorys[$v['cid']] : array();
            $run->category->format($v['category']);
        }else{
            $v['category'] = array();
        }

		if(!empty($v['son'])) {
			foreach($v['son'] as &$v2) {
                if($nocids_arr && in_array($v2['cid'], $nocids_arr)){
                    continue;
                }
				if($v2['cid'] > 0) {
					$v2['url'] = $run->category->category_url($v2);
				}
                if($v2['cid'] > 0 && $allcategorys){
                    $v2['category'] = isset($allcategorys[$v2['cid']]) ? $allcategorys[$v2['cid']] : array();
                    $run->category->format($v2['category']);
                }else{
                    $v2['category'] = array();
                }
			}
		}
        // hook block_navigate_foreach_after.php
	}

	// hook block_navigate_after.php

	return $nav_arr;
}
