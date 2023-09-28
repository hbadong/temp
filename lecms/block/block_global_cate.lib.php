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
 * @param int showviews 是否读取内容浏览量信息
 * @param int pageoffset 分页显示偏移量
 * @param int showmaxpage 最多显示多少页
 * @param int field_format 是否格式化主表自定义字段内容（主要是单选框、多选框、下拉框）
 * @param string page_function 分页函数，xiunophp/ext/paginator.class.php
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
    $showviews = _int($conf, 'showviews', 0);
    $pageoffset = _int($conf, 'pageoffset', 5);
    $showmaxpage = _int($conf, 'showmaxpage', 0);
    $field_format = _int($conf, 'field_format', 0);
    $page_function = empty($conf['page_function']) ? 'pages' : $conf['page_function'];
    $extra = array('block_name'=>'block_global_cate');
    // hook block_global_cate_conf_after.php

	// 排除单页模型
	$cid = &$run->_var['cid'];
	$mid = &$run->_var['mid'];
	if($mid == 1) return FALSE;

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
		$total = &$run->_var['count'];
	}
    // hook block_global_cate_where_after.php

	// 分页相关
	$maxpage = max(1, ceil($total/$pagenum));
    if($showmaxpage && $maxpage > $showmaxpage){
        $maxpage = $showmaxpage;
    }
	$page = min($maxpage, max(1, intval(R('page'))));
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
		if(isset($cate_arr)) {
			$v['cate_name'] = $cate_arr[$v['cid']]['name'];
			$v['cate_url'] = $run->category->category_url($cate_arr[$v['cid']]);
		}
        if($showviews && $views_list_arr){
            $v['views'] = isset($views_list_arr[$views_key.$v['id']]['views']) ? (int)$views_list_arr[$views_key.$v['id']]['views'] : 0;
        }
        $v['xuhao'] = $xuhao;
        $xuhao++;
        // hook block_global_cate_foreach_after.php
	}

	// hook block_global_cate_after.php

	return array('total'=> $total, 'pages'=> $pages, 'list'=> $list_arr);
}
