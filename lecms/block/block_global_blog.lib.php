<?php
defined('ROOT_PATH') || exit;

/**
 * 模型页模块（类似博客列表）
 * @param int mid 模型ID
 * @param int cid 分类ID
 * @param int pagenum 每页显示条数
 * @param int titlenum 标题长度
 * @param int intronum 简介长度
 * @param string dateformat 时间格式
 * @param string orderby 排序方式
 * @param int orderway 降序(-1),升序(1)
 * @param int showcate 是否读取分类信息
 * @param int showviews 是否读取内容浏览量信息
 * @param int pageoffset 分页显示偏移量
 * @param int pageoffset_mobile 手机端分页显示偏移量
 * @param int showmaxpage 最多显示多少页
 * @param int field_format 是否格式化主表自定义字段内容（主要是单选框、多选框、下拉框）
 * @param string page_function 分页函数，xiunophp/ext/paginator.class.php
 * @param int life 缓存时间
 * @return array
 */
function block_global_blog($conf) {
	global $run;

	// hook block_global_blog_before.php

	$mid = isset($_GET['mid']) ? max(2, intval($_GET['mid'])) : _int($conf, 'mid', 2);
    $cid = _int($conf, 'cid', 0);
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
    $extra = array('block_name'=>'block_global_blog');
    $page = max(1, intval(R('page')));
    $cache_params = array($page, $mid, $cid, $pagenum, $titlenum, $intronum, $dateformat, $orderby, $orderway, $showcate, $showviews, $pageoffset, $pageoffset_mobile, $showmaxpage, $field_format, $page_function);
    // hook block_global_blog_conf_after.php

    //优先从缓存表读取。加前缀，避免不同的block，相同的cache_params，导致缓存数据错乱
    $cache_key = $life ? md5('global_blog'.serialize($cache_params)) : '';
    if($cache_key){
        $cache_data = $run->runtime->get_block_data_cache($cache_key);
        if($cache_data){
            return $cache_data;
        }
    }

	$table_arr = &$run->_cfg['table_arr'];
	$table = isset($table_arr[$mid]) ? $table_arr[$mid] : 'article';

	// 初始模型表名
	$run->cms_content->table = 'cms_'.$table;

	$where = array();
	if($cid){
        $where['cid'] = $cid;
        $total = $run->cms_content->find_count($where);
    }else{
        $total = $run->cms_content->count();
    }

	// 分页相关
	$maxpage = max(1, ceil($total/$pagenum));

    //最大页数控制（超出进入404页面）
    if( $page > $maxpage || ($showmaxpage && $page > $showmaxpage)){core::error404();}

    //只显示最大指定页数
    if($showmaxpage && $maxpage > $showmaxpage){
        $maxpage = $showmaxpage;
    }

	$page = min($maxpage, $page);

    if(R('control', 'G') == 'model'){
        $url = $run->urls->model_url($table, $mid, TRUE);
    }else{
        $url = $run->urls->index_url($mid, TRUE);
    }

	$pages = paginator::$page_function($page, $maxpage, $url, $pageoffset);

    if($showcate){
        $allcategorys = $run->category->get_category_db();
    }else{
        $allcategorys = array();
    }

    // hook block_global_blog_list_arr_before.php

	// 读取内容列表
	$list_arr = $run->cms_content->list_arr($where, $orderby, $orderway, ($page-1)*$pagenum, $pagenum, $total, $extra);

    // hook block_global_blog_list_arr_after.php

    if($showviews && $list_arr){
        $run->cms_content_views->table = 'cms_'.$table.'_views';
        $keys = array();
        foreach($list_arr as $v) {
            isset($v['id']) AND $keys[] = $v['id'];
        }
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
        // hook block_global_blog_foreach_after.php
	}
    // hook block_global_blog_list_arr_after.php
    $ret = array('total'=> $total, 'pages'=> $pages, 'list'=> $list_arr);

    // hook block_global_blog_set_block_data_cache_before.php
    if($cache_key){
        $run->runtime->set_block_data_cache($cache_key, $ret, $life);
    }

	// hook block_global_blog_after.php

	return $ret;
}
