<?php
defined('ROOT_PATH') || exit;

/**
 * 获取分类信息
 * @param string cids 多个分类ID 英文逗号隔开，优先级别最高
 * @param int cid 分类ID 如果不填：自动识别
 * @param int life 缓存时间
 * @return array
 */
function block_category_info($conf) {
	global $run;

	// hook block_category_info_before.php

    $cids = isset($conf['cids']) ? trim($conf['cids']) : '';
	$cid = isset($conf['cid']) ? intval($conf['cid']) : _int($_GET, 'cid');
    $life = isset($conf['life']) ? (int)$conf['life'] : (isset($run->_cfg['life']) ? (int)$run->_cfg['life'] : 0);
    $cache_params = array($cids, $cid);
    // hook block_category_info_conf_after.php

	if( empty($cids) && empty($cid) ) return array();

    //优先从缓存表读取。加前缀，避免不同的block，相同的cache_params，导致缓存数据错乱
    $cache_key = $life ? md5('category_info'.serialize($cache_params)) : '';
    if($cache_key){
        $cache_data = $run->runtime->get_block_data_cache($cache_key);
        if($cache_data){
            return $cache_data;
        }
    }

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

        $ret = array('list'=> $list_arr);

        // hook block_category_info_set_block_data_cache_before.php
        if($cache_key){
            $run->runtime->set_block_data_cache($cache_key, $ret, $life);
        }

        // hook block_category_info_cid_after.php

        return $ret;
    }else{
        $cate = $run->category->get_cache($cid);
        if( empty($cate) ) return array();

        $run->category->format($cate);
        if($cate['mid'] == 1){
            $page = $run->cms_page->get($cid);
            $cate['content'] = $page['content'];
        }

        // hook block_category_info_set_block_data_cache_before.php
        if($cache_key){
            $run->runtime->set_block_data_cache($cache_key, $cate, $life);
        }

        // hook block_category_info_cid_after.php

        return $cate;
    }
}
