<?php
defined('ROOT_PATH') || exit;

/**
 * 列表页模块 (不推荐频道分类使用此模块，影响性能)
 * @param int pagenum 每页显示条数
 * @param int titlenum 标题长度
 * @param int intronum 简介长度
 * @param string dateformat 时间格式
 * @param string orderby 排序方式
 * @param int orderway 降序(-1),升序(1)
 * @param int showcate 是否显示分类信息
 * @param int showviews 是否读取内容浏览量信息
 * @param int pageoffset 分页显示偏移量
 * @param int pageoffset_mobile 手机端分页显示偏移量
 * @param int showmaxpage 最多显示多少页
 * @param int field_format 是否格式化主表自定义字段内容（主要是单选框、多选框、下拉框）
 * @param string page_function 分页函数，xiunophp/ext/paginator.class.php
 * @param int life 缓存时间
 * @return array
 */
function block_global_cate($conf) {
	global $run;

	// hook block_global_cate_before.php
	$pagenum = empty($conf['pagenum']) ? 20 : max(1, (int)$conf['pagenum']);
	$titlenum = isset($conf['titlenum']) ? (int)$conf['titlenum'] : 0;
	$intronum = isset($conf['intronum']) ? (int)$conf['intronum'] : 0;
	$dateformat = empty($conf['dateformat']) ? 'Y-m-d H:i:s' : $conf['dateformat'];
	$orderby = isset($conf['orderby']) && in_array($conf['orderby'], array('id', 'dateline', 'lasttime', 'comments')) ? $conf['orderby'] : 'id';
	$orderway = isset($conf['orderway']) && $conf['orderway'] == 1 ? 1 : -1;
    $showcate = _int($conf, 'showcate', 0);
    $showviews = _int($conf, 'showviews', 0);
    $pageoffset = _int($conf, 'pageoffset', 5);
    $pageoffset_mobile = _int($conf, 'pageoffset_mobile', 0);
    if($pageoffset_mobile && is_mobile()){
        $pageoffset = $pageoffset_mobile;
    }
    $showmaxpage = _int($conf, 'showmaxpage', 0);
    $field_format = _int($conf, 'field_format', 0);
    $page_function = empty($conf['page_function']) ? 'pages' : $conf['page_function'];
    $life = isset($conf['life']) ? (int)$conf['life'] : (isset($run->_cfg['life']) ? (int)$run->_cfg['life'] : 0);
    $extra = array('block_name'=>'block_global_cate');

    // 排除单页模型
    $cid = &$run->_var['cid'];
    $mid = &$run->_var['mid'];
    if($mid == 1) return FALSE;

    $page = max(1, intval(R('page')));
    $cache_params = array($page, $cid, $mid, $pagenum, $titlenum, $intronum, $dateformat, $orderby, $orderway, $showcate, $showviews, $pageoffset, $pageoffset_mobile, $showmaxpage, $field_format, $page_function);
    // hook block_global_cate_conf_after.php

    //优先从缓存表读取。加前缀，避免不同的block，相同的cache_params，导致缓存数据错乱
    $cache_key = $life ? md5('global_cate'.serialize($cache_params)) : '';
    if($cache_key){
        $cache_data = $run->runtime->get_block_data_cache($cache_key);
        if($cache_data){
            return $cache_data;
        }
    }

	if(!empty($run->_var['son_cids']) && is_array($run->_var['son_cids'])) {
		// 影响数据库性能
		$where = array('cid' => array("IN" => $run->_var['son_cids']));
		$total = 0;
		$cate_arr = array();
		foreach($run->_var['son_cids'] as $v) {
			$cate_arr[$v] = $run->category->get_cache($v);
			$total += $cate_arr[$v]['count'];
		}
	}else{
		$where = array('cid' => $cid);
        $cate_arr[$cid] = $run->_var;
		$total = &$run->_var['count'];
	}
    // hook block_global_cate_where_after.php

	// 分页相关
	$maxpage = max(1, ceil($total/$pagenum));

	//最大页数控制（超出进入404页面）
	if( $page > $maxpage || ($showmaxpage && $page > $showmaxpage)){core::error404();}

	//只显示最大指定页数
    if($showmaxpage && $maxpage > $showmaxpage){
        $maxpage = $showmaxpage;
    }

	$page = min($maxpage, $page);
	$pages = paginator::$page_function($page, $maxpage, $run->category->category_url($run->_var, TRUE), $pageoffset);

	// 初始模型表名
	$run->cms_content->table = 'cms_'.$run->_var['table'];

    // hook block_global_cate_list_arr_before.php

	// 获取内容列表
	$list_arr = $run->cms_content->list_arr($where, $orderby, $orderway, ($page-1)*$pagenum, $pagenum, $total, $extra);

    // hook block_global_cate_list_arr_after.php

    if($showviews && $list_arr){
        $run->cms_content_views->table = 'cms_'.$run->_var['table'].'_views';
        $keys = array();
        foreach($list_arr as $v) {
            $keys[] = $v['id'];
        }
        $views_list_arr = $run->cms_content_views->mget($keys);
        $views_key = 'cms_'.$run->_var['table'].'_views-id-';
    }else{
        $views_key = '';
        $views_list_arr = array();
    }

    $xuhao = 1;
	foreach($list_arr as &$v) {
		$run->cms_content->format($v, $mid, $dateformat, $titlenum, $intronum, $field_format);

		//内容列表加上分类信息（列表页基本用不上，频道页可能会用）
        if($showcate && isset($cate_arr[$v['cid']])){
            $run->category->getCategoryInfoByList($v, $cate_arr[$v['cid']]);
        }

        if($showviews && $views_list_arr){
            $v['views'] = isset($views_list_arr[$views_key.$v['id']]['views']) ? (int)$views_list_arr[$views_key.$v['id']]['views'] : 0;
        }
        $v['xuhao'] = $xuhao;
        $xuhao++;
        // hook block_global_cate_foreach_after.php
	}
    // hook block_global_cate_list_arr_after.php
    $ret = array('total'=> $total, 'pages'=> $pages, 'list'=> $list_arr);

    // hook block_global_cate_set_block_data_cache_before.php
    if($cache_key){
        $run->runtime->set_block_data_cache($cache_key, $ret, $life);
    }

	// hook block_global_cate_after.php

	return $ret;
}
