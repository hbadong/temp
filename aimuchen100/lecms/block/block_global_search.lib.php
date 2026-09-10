<?php
defined('ROOT_PATH') || exit;

/**
 * 搜索页模块 (比较占用资源，大站可使用sphinx做搜索引擎)
 * @param int pagenum 每页显示条数
 * @param int titlenum 标题长度
 * @param int intronum 简介长度
 * @param string dateformat 时间格式
 * @param string orderby 排序方式
 * @param int orderway 降序(-1),升序(1)
 * @param int maxcount 允许最大内容数(数据库搜索)
 * @param int showcate 是否读取分类信息
 * @param int showviews 是否读取内容浏览量信息
 * @param int pageoffset 分页显示偏移量
 * @param int pageoffset_mobile 手机端分页显示偏移量
 * @param int showmaxpage 最多显示多少页
 * @param int field_format 是否格式化主表自定义字段内容（主要是单选框、多选框、下拉框）
 * @param string page_function 分页函数，xiunophp/ext/paginator.class.php
 * @return array
 */
function block_global_search($conf) {
	global $run, $keyword;

	// hook block_global_search_before.php

	$pagenum = empty($conf['pagenum']) ? 20 : max(1, (int)$conf['pagenum']);
	$titlenum = isset($conf['titlenum']) ? (int)$conf['titlenum'] : 0;
	$intronum = isset($conf['intronum']) ? (int)$conf['intronum'] : 0;
	$dateformat = empty($conf['dateformat']) ? 'Y-m-d H:i:s' : $conf['dateformat'];
    $orderby = isset($conf['orderby']) && in_array($conf['orderby'], array('id', 'dateline', 'lasttime', 'comments')) ? $conf['orderby'] : 'id';
    $orderway = isset($conf['orderway']) && $conf['orderway'] == 1 ? 1 : -1;
	$maxcount = isset($conf['maxcount']) ? (int)$conf['maxcount'] : 100000;
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
    $extra = array('block_name'=>'block_global_search');
    $mid = max(2, (int)R('mid'));
    // hook block_global_search_conf_after.php

    if( empty($keyword) ) return array('total'=> 0, 'pages'=> '', 'list'=> array());

    //关闭搜索
    if(isset($run->_cfg['close_search']) && !empty($run->_cfg['close_search'])){
        return array('total'=> 0, 'pages'=> '', 'list'=> array());
    }

	$table_arr = &$run->_cfg['table_arr'];
	$table = isset($table_arr[$mid]) ? $table_arr[$mid] : '';
	if( empty($table) ){
        return array('total'=> 0, 'pages'=> '', 'list'=> array());
    }

	$where = array('title'=>array('LIKE'=>$keyword));
	$run->cms_content->table = 'cms_'.$table;

    // hook block_global_search_where_after.php

	if($run->cms_content->count() > $maxcount) return array('total'=> 0, 'pages'=> '', 'list'=> array());

	// 初始分页
	$total = $run->cms_content->find_count($where);
	$maxpage = max(1, ceil($total/$pagenum));

	//最大页数控制（超出进入404页面）
    if( R('page', 'G') > $maxpage || ($showmaxpage && R('page', 'G') > $showmaxpage)){core::error404();}

    //只显示最大指定页数
    if($showmaxpage && $maxpage > $showmaxpage){
        $maxpage = $showmaxpage;
    }

	$page = min($maxpage, max(1, intval(R('page'))));

    $search_url = $run->urls->search_url($mid, $keyword, true);
	$pages = paginator::$page_function($page, $maxpage, $search_url, $pageoffset);

    if($showcate){
        $allcategorys = $run->category->get_category_db();
    }else{
        $allcategorys = array();
    }

    // hook block_global_search_list_arr_before.php

	// 读取内容列表
	$list_arr = $run->cms_content->list_arr($where, $orderby, $orderway, ($page-1)*$pagenum, $pagenum, $total, $extra);

    // hook block_global_search_list_arr_after.php

    if($showviews && $list_arr){
        $run->cms_content_views->table = 'cms_'.$table.'_views';
        $keys = array();
        foreach($list_arr as $v) {
            $keys[] = $v['id'];
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
		$v['subject'] = str_ireplace($keyword, '<font color="red">'.$keyword.'</font>', $v['subject']);
		$v['intro'] = str_ireplace($keyword, '<font color="red">'.$keyword.'</font>', $v['intro']);
        if($showcate && $allcategorys){
            $cate = isset($allcategorys[$v['cid']]) ? $allcategorys[$v['cid']] : array();
            $run->category->getCategoryInfoByList($v, $cate);
        }
        if($showviews && $views_list_arr){
            $v['views'] = isset($views_list_arr[$views_key.$v['id']]['views']) ? (int)$views_list_arr[$views_key.$v['id']]['views'] : 0;
        }
        $v['xuhao'] = $xuhao;
        $xuhao++;
        // hook block_global_foreach_search_after.php
	}

	// hook block_global_search_after.php

	return array('total'=> $total, 'pages'=> $pages, 'list'=> $list_arr);
}
