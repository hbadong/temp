<?php
defined('ROOT_PATH') || exit;

/**
 * 获取分类信息
 * @param string cids 多个分类ID 英文逗号隔开，优先级别最高
 * @param int cid 分类ID 如果不填：自动识别
 * @return array
 */
function block_category_info($conf) {
	global $run;

	// hook block_category_info_before.php

    $cids = isset($conf['cids']) ? trim($conf['cids']) : '';
	$cid = isset($conf['cid']) ? intval($conf['cid']) : _int($_GET, 'cid');
    // hook block_category_info_conf_after.php

	if( empty($cids) && empty($cid) ) return array();

	if($cids){
        $list_arr = array();
        $cid_arr = explode(',', $cids);
        foreach ($cid_arr as $cid){
            $cate = $run->category->get_cache($cid);
            if( empty($cate) ){
                continue;
            }else{
                $run->category->format($cate);
                if($cate['mid'] == 1){
                    $page = $run->cms_page->get($cid);
                    $cate['content'] = $page['content'];
                }

                $list_arr[$cid] = $cate;
            }
        }

        // hook block_category_info_cid_after.php

        return array('list'=> $list_arr);
    }else{
        $cate = $run->category->get_cache($cid);
        if( empty($cate) ) return array();

        $run->category->format($cate);
        if($cate['mid'] == 1){
            $page = $run->cms_page->get($cid);
            $cate['content'] = $page['content'];
        }

        // hook block_category_info_cid_after.php

        return $cate;
    }
}
