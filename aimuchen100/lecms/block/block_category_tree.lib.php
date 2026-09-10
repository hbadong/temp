<?php
defined('ROOT_PATH') || exit;

/**
 * 分类展示模块(分类树)
 * @param int cid 分类ID 如果不填：自动识别
 * @param int mid 模型ID (默认自动识别)
 * @param string nocids 排除的分类cid串 多个用英文逗号隔开，如果你排除的是父级cid，那么他的所有子级也会被排除
 * @param int life 缓存时间
 * @return array
 */
function block_category_tree($conf)
{
    global $run;

    // hook block_category_tree_before.php

    $cid = isset($conf['cid']) ? intval($conf['cid']) : _int($_GET, 'cid');
    $mid = isset($conf['mid']) ? intval($conf['mid']) : (isset($run->_var['mid']) ? $run->_var['mid'] : _int($_GET, 'mid'));
    $nocids = empty($conf['nocids']) ? '' : $conf['nocids'];
    $life = isset($conf['life']) ? (int)$conf['life'] : (isset($run->_cfg['life']) ? (int)$run->_cfg['life'] : 0);
    $cache_params = array($cid, $mid, $nocids);
    // hook block_category_tree_conf_after.php

    //优先从缓存表读取。加前缀，避免不同的block，相同的cache_params，导致缓存数据错乱
    $cache_key = $life ? md5('category_tree'.serialize($cache_params)) : '';
    if($cache_key){
        $cache_data = $run->runtime->get_block_data_cache($cache_key);
        if($cache_data){
            return $cache_data;
        }
    }

    //排除的cid
    if($nocids){
        $nocids_arr = explode(',', $nocids);
    }else{
        $nocids_arr = array();
    }

    if($cid){
        if($nocids_arr && in_array($cid, $nocids_arr)){
            return false;
        }
        $category = $run->category->get($cid);
        if(empty($category)){
            return false;
        }else{
            $run->category->format($category);
            $mid = $category['mid'];
        }
    }else{
        $category = array();
    }

    $category_tree = $run->category->get_category_tree();

    if($cid || $mid){
        foreach ($category_tree as $k=>&$cate){
            if($cid && $cid != $cate['cid']){
                unset($category_tree[$k]);
                continue;
            }elseif($mid && $mid != $cate['mid']){
                unset($category_tree[$k]);
                continue;
            }elseif ($nocids_arr && in_array($cate['cid'], $nocids_arr)){
                unset($category_tree[$k]);
                continue;
            }
        }
    }

    block_category_tree_format($category_tree, 1, $nocids_arr);

    if($category){
        $ret = array('list'=> $category_tree, 'cate'=>$category, 'son'=>isset($category_tree[$cid]['son']) ? $category_tree[$cid]['son'] : array());
    }else{
        $ret = array('list'=> $category_tree);
    }

    // hook block_category_tree_set_block_data_cache_before.php
    if($cache_key){
        $run->runtime->set_block_data_cache($cache_key, $ret, $life);
    }

    // hook block_category_tree_after.php

    return $ret;
}

function block_category_tree_format(&$data, $pre = 1, $nocids_arr = array()){
    global $run;
    foreach ($data as $k=>&$v){
        if($nocids_arr && in_array($v['cid'], $nocids_arr)){
            unset($data[$k]);
            continue;
        }
        $v['pre'] = $pre;
        $run->category->format($v);
        if(isset($v['son'])) {
            block_category_tree_format($v['son'], $pre+1, $nocids_arr);
        }
    }
}