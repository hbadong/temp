<?php
defined('ROOT_PATH') || exit;

/**
 * 标签内容列表页模块
 * @param int pagenum 每页显示条数
 * @param int titlenum 标题长度
 * @param int intronum 简介长度
 * @param string dateformat 时间格式
 * @param int orderway 降序(-1),升序(1)
 * @param int showcate 是否显示分类信息
 * @param int showviews 是否显示内容浏览量信息
 * @param int pageoffset 分页显示偏移量
 * @param int showmaxpage 最多显示多少页
 * @param int field_format 是否格式化主表自定义字段内容（主要是单选框、多选框、下拉框）
 * @param string page_function 分页函数，xiunophp/ext/paginator.class.php
 * @param int life 缓存时间
 * @return array
 */
function block_global_taglist($conf) {
	global $run, $tags, $mid, $table;

	// hook block_global_taglist_before.php

	$pagenum = empty($conf['pagenum']) ? 20 : max(1, (int)$conf['pagenum']);
	$titlenum = isset($conf['titlenum']) ? (int)$conf['titlenum'] : 0;
	$intronum = isset($conf['intronum']) ? (int)$conf['intronum'] : 0;
	$dateformat = empty($conf['dateformat']) ? 'Y-m-d H:i:s' : $conf['dateformat'];
	$orderway = isset($conf['orderway']) && $conf['orderway'] == 1 ? 1 : -1;
    $showcate = _int($conf, 'showcate', 0);
    $showviews = _int($conf, 'showviews', 0);
    $pageoffset = _int($conf, 'pageoffset', 5);
    $showmaxpage = _int($conf, 'showmaxpage', 0);
    $field_format = _int($conf, 'field_format', 0);
    $page_function = empty($conf['page_function']) ? 'pages' : $conf['page_function'];
    $life = isset($conf['life']) ? (int)$conf['life'] : (isset($run->_cfg['life']) ? (int)$run->_cfg['life'] : 0);
    $extra = array('block_name'=>'block_global_taglist');
    // hook block_global_taglist_conf_after.php

    //优先从缓存表读取。加前缀，避免不同的block，相同的conf，导致缓存数据错乱
    $cache_key = $life ? md5('global_taglist'.serialize($conf)) : '';
    if($cache_key){
        $cache_data = $run->runtime->get_block_data_cache($cache_key);
        if($cache_data){
            return $cache_data;
        }
    }

    if(empty($tags)){
        return false;
    }

	// 初始分页
	$tagid = $tags['tagid'];
	$total = $tags['count'];
	$maxpage = max(1, ceil($total/$pagenum));

    //最大页数控制（超出进入404页面）
    if( R('page', 'G') > $maxpage || ($showmaxpage && R('page', 'G') > $showmaxpage)){core::error404();}

    //只显示最大指定页数
    if($showmaxpage && $maxpage > $showmaxpage){
        $maxpage = $showmaxpage;
    }

	$page = min($maxpage, max(1, intval(R('page'))));
	$pages = paginator::$page_function($page, $maxpage, $run->cms_content->tag_url($mid, $tags, TRUE), $pageoffset);

	// 读取内容ID
	$run->cms_content_tag_data->table = 'cms_'.$table.'_tag_data';
	$tag_arr = $run->cms_content_tag_data->list_arr($tagid, $orderway, ($page-1)*$pagenum, $pagenum, $total, $extra);
	$keys = array();
	foreach($tag_arr as $v) {
		$keys[] = $v['id'];
	}

    if($showcate){
        $allcategorys = $run->category->get_category_db();
    }else{
        $allcategorys = array();
    }

    if($showviews){
        $run->cms_content_views->table = 'cms_'.$table.'_views';
        $views_list_arr = $run->cms_content_views->mget($keys);
        $views_key = 'cms_'.$table.'_views-id-';
    }else{
        $views_key = '';
        $views_list_arr = array();
    }

	// 读取内容列表
	$run->cms_content->table = 'cms_'.$table;
	$list_arr = $run->cms_content->mget($keys);

    $xuhao = 1;
	foreach($list_arr as &$v) {
		$run->cms_content->format($v, $mid, $dateformat, $titlenum, $intronum, $field_format);
        if($showcate && $allcategorys){
            $cate = isset($allcategorys[$v['cid']]) ? $allcategorys[$v['cid']] : array();
            $run->category->getCategoryInfoByList($v, $cate);
        }
        if($showviews && $views_list_arr){
            $v['views'] = isset($views_list_arr[$views_key.$v['id']]) ? $views_list_arr[$views_key.$v['id']]['views'] : 0;
        }
        $v['xuhao'] = $xuhao;
        $xuhao++;
        // hook block_global_taglist_foreach_after.php
	}

    $ret = array('total'=> $total, 'pages'=> $pages, 'list'=> $list_arr);

    if($cache_key){
        $run->runtime->set_block_data_cache($cache_key, $ret, $life);
    }

	// hook block_global_taglist_after.php

	return $ret;
}
