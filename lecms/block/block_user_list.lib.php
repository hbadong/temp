<?php
defined('ROOT_PATH') || exit;

/**
 * 用户列表模块
 * @param int groupid 用户组ID 默认为注册用户
 * @param string uids 用户UID串(优先级最高)
 * @param string dateformat 时间格式
 * @param int intronum 用户介绍长度
 * @param string orderby 排序方式
 * @param int orderway 降序(-1),升序(1)
 * @param int start 开始位置
 * @param int limit 显示几条
 * @param int showgroup 是否读取用户组信息
 * @param int life 缓存时间
 * @return array
 */
function block_user_list($conf) {
	global $run;

	// hook block_user_list_before.php
    $groupid = _int($conf, 'groupid', 11);
    $uids = isset($conf['uids']) ? $conf['uids']: '';  //多个uid 用,隔开
    $dateformat = empty($conf['dateformat']) ? 'Y-m-d H:i:s' : $conf['dateformat'];
    $intronum = _int($conf, 'intronum', 0);
	$orderby = isset($conf['orderby']) && in_array($conf['orderby'], array('uid', 'regdate','logindate','contents','logins','credits','golds')) ? $conf['orderby'] : 'uid';
	$orderway = isset($conf['orderway']) && $conf['orderway'] == 1 ? 1 : -1;
	$start = _int($conf, 'start', 0);
	$limit = _int($conf, 'limit', 10);
    $showgroup = _int($conf, 'showgroup', 0);
    $life = isset($conf['life']) ? (int)$conf['life'] : (isset($run->_cfg['life']) ? (int)$run->_cfg['life'] : 0);
    $extra = array('block_name'=>'block_user_list');
    $cache_params = array($groupid, $uids, $dateformat, $intronum, $orderby, $orderway, $start, $limit, $showgroup);
    // hook block_user_list_conf_after.php

    //优先从缓存表读取。加前缀，避免不同的block，相同的cache_params，导致缓存数据错乱
    $cache_key = $life ? md5('user_list'.serialize($cache_params)) : '';
    if($cache_key){
        $cache_data = $run->runtime->get_block_data_cache($cache_key);
        if($cache_data){
            return $cache_data;
        }
    }

    if($uids){
        $uid_arr = explode(',', $uids);
        $where['uid'] = array('uid'=>array('IN'=>$uid_arr));
    }else{
        $where['groupid'] = $groupid;
    }

    // hook block_user_list_where_after.php

    if($showgroup){
        $allgroups = $run->user_group->get_name();
    }else{
        $allgroups = array();
    }

	// 读取用户列表
    if($uids){
        $list_arr = $run->user->find_fetch($where, array($orderby => $orderway));
    }else{
        $list_arr = $run->user->list_arr($where, $orderby, $orderway, $start, $limit, $limit, $extra);
    }

    $xuhao = 1;
	foreach($list_arr as &$v) {
		$run->user->format($v, $dateformat, 0, $intronum);
		if($showgroup && $allgroups){
		    if(isset( $allgroups[$v['groupid']] )){
		        $v['groupname'] = $allgroups[$v['groupid']];
            }else{
                $v['groupname'] = '';
            }
        }
        $v['xuhao'] = $xuhao;
        $xuhao++;
        // hook block_user_list_foreach_after.php
	}

    $ret = array('list'=> $list_arr);

    // hook block_user_list_set_block_data_cache_before.php
    if($cache_key){
        $run->runtime->set_block_data_cache($cache_key, $ret, $life);
    }

	// hook block_user_list_after.php

	return $ret;
}
