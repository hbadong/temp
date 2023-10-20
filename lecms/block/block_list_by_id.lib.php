<?php
defined('ROOT_PATH') || exit;

/**
 * 根据ID或者ID串读取内容列表模块
 * @param int id 内容ID
 * @param string ids 内容ID串 (必须是同一个模型下的)
 * @param int mid 模型ID (默认为2)
 * @param string dateformat 时间格式
 * @param int titlenum 标题长度
 * @param int intronum 简介长度
 * @param int showcate 是否显示分类信息
 * @param int showviews 是否内容浏览量信息
 * @param int field_format 是否格式化主表自定义字段内容（主要是单选框、多选框、下拉框、图集等）
 * @param int life 缓存时间
 * @return array
 */
function block_list_by_id($conf) {
	global $run;

	// hook block_list_by_id_before.php

    $id = _int($conf, 'id', 0);   // 优先级高于ids
    $ids = empty($conf['ids']) ? '' : $conf['ids'];    //多个id 用,隔开
	$mid = _int($conf, 'mid', 2);
	$dateformat = empty($conf['dateformat']) ? 'Y-m-d H:i:s' : $conf['dateformat'];
	$titlenum = _int($conf, 'titlenum');
	$intronum = _int($conf, 'intronum');
    $showcate = _int($conf, 'showcate', 0);
    $showviews = _int($conf, 'showviews', 0);
    $field_format = _int($conf, 'field_format', 0);
    $life = _int($conf, 'life', 0);
    // hook block_list_by_id_conf_after.php

    //优先从缓存表读取。加前缀，避免不同的block，相同的conf，导致缓存数据错乱
    $cache_key = $life ? md5('list_by_id'.serialize($conf)) : '';
    if($cache_key){
        $list_arr = $run->runtime->get_block_data_cache($cache_key);
        if($list_arr){
            return $list_arr;
        }
    }

    $table_arr = &$run->_cfg['table_arr'];
    $table = isset($table_arr[$mid]) ? $table_arr[$mid] : 'article';
    //过滤单页模型
    if($table == 'page'){
        return array();
    }

    $run->cms_content->table = 'cms_'.$table;

    if($showcate){
        $allcategorys = $run->category->get_category_db();
    }else{
        $allcategorys = array();
    }

    if($id){
        $data = $run->cms_content->get($id);
        if( empty($data) ){
            return array();
        }

        if($showviews){
            $run->cms_content_views->table = 'cms_'.$table.'_views';
            $views_arr = $run->cms_content_views->get($id);
        }else{
            $views_arr = array();
        }

        $run->cms_content->format($data, $mid, $dateformat, $titlenum, $intronum, $field_format);
        if($showcate && $allcategorys){
            $cate = isset($allcategorys[$data['cid']]) ? $allcategorys[$data['cid']] : array();
            $run->category->getCategoryInfoByList($data, $cate);
        }

        if($showviews && $views_arr){
            $data['views'] = isset($views_arr['views']) ? (int)$views_arr['views'] : 0;
        }

        if($cache_key){
            $run->runtime->set_block_data_cache($cache_key, $data, $life);
        }

        // hook block_list_by_id_after.php

        return $data;
    }elseif ($ids){
        $keys = explode(',', $ids);
        $list_arr = $run->cms_content->mget($keys);

        if($showviews && $list_arr){
            $run->cms_content_views->table = 'cms_'.$table.'_views';
            $views_list_arr = $run->cms_content_views->mget($keys);
            $views_key = 'cms_'.$table.'_views-id-';
        }else{
            $views_key = '';
            $views_list_arr = array();
        }

        $xuhao = 1;
        foreach($list_arr as &$v) {
            $run->cms_content->format($v, $mid, $dateformat, $titlenum, $intronum, $field_format);
            if($showcate && $allcategorys){
                $cate = isset($allcategorys[$v['cid']]) ? $allcategorys[$v['cid']] : array();
                $run->category->getCategoryInfoByList($v, $cate);
            }
            if($showviews && $views_list_arr){
                $v['views'] = isset($views_list_arr[$views_key.$v['id']]['views']) ? (int)$views_list_arr[$views_key.$v['id']]['views'] : 0;
            }
            $v['xuhao'] = $xuhao;
            $xuhao++;
            // hook block_list_by_ids_foreach_after.php
        }

        $ret = array('list'=> $list_arr);
        if($cache_key){
            $run->runtime->set_block_data_cache($cache_key, $ret, $life);
        }

        // hook block_list_by_id_after.php

        return $ret;
    }else{
        return array('list'=> array());
    }
}
