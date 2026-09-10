<?php
defined('ROOT_PATH') || exit;

/**
 * 相关内容模块 (只能用于内容页)
 * @param int titlenum 标题长度
 * @param int intronum 简介长度
 * @param string dateformat 时间格式
 * @param int type 相关内容类型 (1为显示第一个tag相关内容，依次类推。0为随机显示一个tag相关内容，-1表示最后一个tag相关内容)
 * @param int orderway 降序(-1),升序(1)
 * @param int start 开始位置
 * @param int limit 显示几条
 * @param int showcate 是否读取分类信息
 * @param int showviews 是否读取内容浏览量信息
 * @param int field_format 是否格式化主表自定义字段内容（主要是单选框、多选框、下拉框）
 * @param int mid 模型ID， 一般不需要， 只有在跨模型调用数据时，才需要。
 * @param int life 缓存时间
 * @return array
 */
function block_taglike($conf) {
	global $run, $_show;

	// hook block_taglike_before.php

	if(!isset($_show['tags']) || empty($_show['tags'])) return array('tag_name'=>'', 'tag_url'=>'', 'list'=> array());

	$titlenum = isset($conf['titlenum']) ? (int)$conf['titlenum'] : 0;
	$intronum = isset($conf['intronum']) ? (int)$conf['intronum'] : 0;
	$dateformat = empty($conf['dateformat']) ? 'Y-m-d H:i:s' : $conf['dateformat'];
    $type = isset($conf['type']) ? (int)$conf['type'] : 1;
    $type = $type <= -1 ? -1 : (int)$type;
	$orderway = isset($conf['orderway']) && $conf['orderway'] == 1 ? 1 : -1;
	$start = _int($conf, 'start');
	$limit = _int($conf, 'limit', 10);
    $showcate = _int($conf, 'showcate', 0);
    $showviews = _int($conf, 'showviews', 0);
    $field_format = _int($conf, 'field_format', 0);
    $life = isset($conf['life']) ? (int)$conf['life'] : (isset($run->_cfg['life']) ? (int)$run->_cfg['life'] : 0);
    $mid = &$run->_var['mid'];
    $table = &$run->_var['table'];
    $cache_params = array($_show['tags'],$mid,$table,$titlenum, $intronum, $dateformat, $type, $orderway, $start, $limit, $showcate, $showviews, $field_format);
    // hook block_taglike_conf_after.php

    //优先从缓存表读取。加前缀，避免不同的block，相同的cache_params，导致缓存数据错乱
    $cache_key = $life ? md5('taglike'.serialize($cache_params)) : '';
    if($cache_key){
        $cache_data = $run->runtime->get_block_data_cache($cache_key);
        if($cache_data){
            return $cache_data;
        }
    }

    //global_show标签加上life参数后，这里会变成json数据，需要重新转一次数组
	if(!is_array($_show['tags'])){
		$_show['tags'] = (array)_json_decode($_show['tags']);
	}

	if($type == -1){    //最后一个
	    $end = array_slice($_show['tags'],-1, 1, true);
        $tagid = key( $end );
    }elseif ($type == 0){   //随机取一个
        $tagid = array_rand($_show['tags']);
    }else{  //第几个
        $tagid = key( array_slice($_show['tags'], $type-1, 1, true) );
        if( empty($tagid) ){
            return array('tag_name'=>'', 'tag_url'=>'', 'list'=> array());
        }
    }
    $tag_name = $_show['tags'][$tagid];
    $tag_url = $run->cms_content->tag_url($mid, array('tagid'=>$tagid, 'name'=>$tag_name));

    //跨模型调用相同标签的数据？根据标签名找到指定模型的标签ID
    if(isset($conf['mid']) && $conf['mid'] > 1 && $mid != $conf['mid'] && isset($run->_cfg['table_arr'][$conf['mid']])){
        $mid = $conf['mid'];
        $table = $run->_cfg['table_arr'][$conf['mid']];

        $run->cms_content_tag->table = 'cms_'.$table.'_tag';
        $tags = $run->cms_content_tag->get_tag_by_tagname($tag_name);
        if(empty($tags)){
            return array('tag_name'=>$tag_name, 'tag_url'=>$tag_url, 'list'=> array());
        }else{
            $tagid = $tags['tagid'];
            $tag_url = $run->cms_content->tag_url($mid, $tags);
        }
    }

	// 读取内容ID
	$run->cms_content_tag_data->table = 'cms_'.$table.'_tag_data';
	$tag_arr = $run->cms_content_tag_data->find_fetch(array('tagid'=>$tagid), array('id'=>$orderway), $start, $limit+1);
	$keys = array();
	foreach($tag_arr as $lv) {
	    //排除内容本身
	    if($lv['id'] != $_show['id']){
            $keys[] = $lv['id'];
        }
	}
    if( empty($keys) ){
        return array('tag_name'=>$tag_name, 'tag_url'=>$tag_url, 'list'=> array());
    }elseif (count($keys) > $limit){
        $keys = array_slice($keys, 0, $limit);
    }

    if($showcate){
        $allcategorys = $run->category->get_category_db();
    }else{
        $allcategorys = array();
    }

    // 读取内容列表
    $run->cms_content->table = 'cms_'.$table;
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
        // hook block_taglike_foreach_after.php
	}

    $ret = array('tag_name'=>$tag_name, 'tag_url'=>$tag_url, 'list'=> $list_arr);

    // hook block_taglike_set_block_data_cache_before.php
    if($cache_key){
        $run->runtime->set_block_data_cache($cache_key, $ret, $life);
    }

	// hook block_taglike_after.php

	return $ret;
}
