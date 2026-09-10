<?php
defined('ROOT_PATH') || exit;

/**
 * 用户分页模块
 * @param int groupid 用户组ID
 * @param int pagenum 每页显示条数
 * @param string dateformat 时间格式
 * @param string orderby 排序方式
 * @param int orderway 降序(-1),升序(1)
 * @param int showgroup 是否读取用户组信息
 * @param int pageoffset 分页显示偏移量
 * @param int pageoffset_mobile 手机端分页显示偏移量
 * @param int showmaxpage 最多显示多少页
 * @param string page_function 分页函数，xiunophp/ext/paginator.class.php
 * @param int life 缓存时间
 * @return array
 */
function block_global_user($conf) {
	global $run;

	// hook block_global_user_before.php
    $groupid = _int($conf, 'groupid', 0);
	$pagenum = empty($conf['pagenum']) ? 20 : max(1, (int)$conf['pagenum']);
	$dateformat = empty($conf['dateformat']) ? 'Y-m-d H:i:s' : $conf['dateformat'];
	$orderby = isset($conf['orderby']) && in_array($conf['orderby'], array('uid', 'contents', 'logins', 'golds', 'credits')) ? $conf['orderby'] : 'uid';
	$orderway = isset($conf['orderway']) && $conf['orderway'] == 1 ? 1 : -1;
    $showgroup = _int($conf, 'showgroup', 0);
    $pageoffset = _int($conf, 'pageoffset', 5);
    $pageoffset_mobile = _int($conf, 'pageoffset_mobile', 0);
    if($pageoffset_mobile && is_mobile()){
        $pageoffset = $pageoffset_mobile;
    }
    $showmaxpage = _int($conf, 'showmaxpage', 0);
    $page_function = empty($conf['page_function']) ? 'pages' : $conf['page_function'];
    $life = isset($conf['life']) ? (int)$conf['life'] : (isset($run->_cfg['life']) ? (int)$run->_cfg['life'] : 0);
    $extra = array('block_name'=>'block_global_user');
    $page = max(1, intval(R('page')));
    $cache_params = array($page, $groupid, $pagenum, $dateformat, $orderby, $orderway, $showgroup, $pageoffset, $pageoffset_mobile, $showmaxpage, $page_function);
    // hook block_global_user_conf_after.php

    //优先从缓存表读取。加前缀，避免不同的block，相同的cache_params，导致缓存数据错乱
    $cache_key = $life ? md5('global_user'.serialize($cache_params)) : '';
    if($cache_key){
        $cache_data = $run->runtime->get_block_data_cache($cache_key);
        if($cache_data){
            return $cache_data;
        }
    }

    $where = array();
	if($groupid){
	    $where['groupid'] = $groupid;
    }

    // hook block_global_user_where_after.php

    if($where){
        $total = $run->user->find_count($where);
    }else{
        $total = $run->user->count();
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
	$pages = paginator::$page_function($page, $maxpage, $run->urls->user_url('all', 'user', true, $where), $pageoffset);

    if($showgroup){
        $allgroups = &$run->_cfg['group_name'];
    }else{
        $allgroups = array();
    }

    // hook block_global_user_list_arr_before.php

	// 读取用户列表
	$list_arr = $run->user->list_arr($where, $orderby, $orderway, ($page-1)*$pagenum, $pagenum, $total, $extra);

    // hook block_global_user_list_arr_after.php

    $xuhao = 1;
	foreach($list_arr as &$v) {
		$run->user->format($v, $dateformat);

        if($showgroup && $allgroups){
            $v['groupname'] = isset($allgroups[$v['groupid']]['groupname']) ? $allgroups[$v['groupid']]['groupname'] : '';
        }
        $v['xuhao'] = $xuhao;
        $xuhao++;
        // hook block_global_user_foreach_after.php
	}

    $ret = array('total'=> $total, 'pages'=> $pages, 'list'=> $list_arr);

    // hook block_global_user_set_block_data_cache_before.php
    if($cache_key){
        $run->runtime->set_block_data_cache($cache_key, $ret, $life);
    }

	// hook block_global_user_after.php

	return $ret;
}
