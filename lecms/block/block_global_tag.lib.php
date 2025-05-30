<?php
defined('ROOT_PATH') || exit;

/**
 * 标签列表页模块（分页）
 * @param int mid 模型ID，优先级高于 REQUEST里面的
 * @param int pagenum 每页显示条数
 * @param string orderby 排序方式 (参数有 tagid count)
 * @param int orderway 降序(-1),升序(1)
 * @param int pageoffset 分页显示偏移量
 * @param int pageoffset_mobile 手机端分页显示偏移量
 * @param int showmaxpage 最多显示多少页
 * @param string page_function 分页函数，xiunophp/ext/paginator.class.php
 * @param int life 缓存时间
 * @return array
 */
function block_global_tag($conf) {
	global $run;

	// hook block_global_tag_before.php
    $mid = isset($conf['mid']) ? max(2, intval($conf['mid'])) : (isset($_GET['mid']) ? max(2, intval($_GET['mid'])) : 2);
    $_GET['mid'] = $mid;
	$pagenum = empty($conf['pagenum']) ? 20 : max(1, (int)$conf['pagenum']);
    $orderby = isset($conf['orderby']) && in_array($conf['orderby'], array('tagid', 'count', 'orderby')) ? $conf['orderby'] : 'count';
	$orderway = isset($conf['orderway']) && $conf['orderway'] == 1 ? 1 : -1;
    $pageoffset = _int($conf, 'pageoffset', 5);
    $pageoffset_mobile = _int($conf, 'pageoffset_mobile', 0);
    if($pageoffset_mobile && is_mobile()){
        $pageoffset = $pageoffset_mobile;
    }
    $showmaxpage = _int($conf, 'showmaxpage', 0);
    $page_function = empty($conf['page_function']) ? 'pages' : $conf['page_function'];
    $life = isset($conf['life']) ? (int)$conf['life'] : (isset($run->_cfg['life']) ? (int)$run->_cfg['life'] : 0);
    $extra = array('block_name'=>'block_global_tag');
    $page = max(1, intval(R('page')));
    $cache_params = array($page, $mid, $pagenum, $orderby, $orderway, $pageoffset, $pageoffset_mobile, $showmaxpage, $page_function);
    // hook block_global_tag_conf_after.php

    //优先从缓存表读取。加前缀，避免不同的block，相同的cache_params，导致缓存数据错乱
    $cache_key = $life ? md5('global_tag'.serialize($cache_params)) : '';
    if($cache_key){
        $cache_data = $run->runtime->get_block_data_cache($cache_key);
        if($cache_data){
            return $cache_data;
        }
    }

    $table_arr = &$run->_cfg['table_arr'];
    $table = isset($table_arr[$mid]) ? $table_arr[$mid] : 'article';
    // 初始模型表名
    $run->cms_content_tag->table = 'cms_'.$table.'_tag';

    $where = $t_extra = array();
    // hook block_global_tag_where_after.php

	// 初始分页
	if($where){
        $total = $run->cms_content_tag->find_count($where);
    }else{
        $total = $run->cms_content_tag->count();
    }
	$maxpage = max(1, ceil($total/$pagenum));

    //最大页数控制（超出进入404页面）
    if( $page > $maxpage || ($showmaxpage && $page > $showmaxpage)){core::error404();}

    //只显示最大指定页数
    if($showmaxpage && $maxpage > $showmaxpage){
        $maxpage = $showmaxpage;
    }

	$page = min($maxpage, $page);
	$pages = paginator::$page_function($page, $maxpage, $run->urls->tag_all_url($mid, TRUE, $t_extra), $pageoffset);

    // hook block_global_tag_list_arr_before.php

    $list_arr = $run->cms_content_tag->list_arr($where, $orderby, $orderway, ($page-1)*$pagenum, $pagenum, $total, $extra);

    // hook block_global_tag_list_arr_after.php

    $xuhao = 1;
    foreach($list_arr as &$v) {
        $run->cms_content_tag->format($v, $mid);
        $v['xuhao'] = $xuhao;
        $xuhao++;
        // hook block_global_tag_list_foreach_after.php
    }

    $ret = array('total'=> $total, 'pages'=> $pages, 'list'=>$list_arr, 'title'=>lang('all'));

    // hook block_global_tag_set_block_data_cache_before.php
    if($cache_key){
        $run->runtime->set_block_data_cache($cache_key, $ret, $life);
    }

	// hook block_global_tag_after.php

	return $ret;
}
