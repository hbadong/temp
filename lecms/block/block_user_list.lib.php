<?php
defined('ROOT_PATH') || exit;

/**
 * 用户列表模块
 * @param int groupid 用户组ID 默认为注册用户
 * @param string orderby 排序方式
 * @param int orderway 降序(-1),升序(1)
 * @param int start 开始位置
 * @param int limit 显示几条
 * @param int showgroup 是否读取用户组信息
 * @return array
 */
function block_user_list($conf) {
	global $run;

	// hook block_user_list_before.php
    $groupid = _int($conf, 'groupid', 11);
	$orderby = isset($conf['orderby']) && in_array($conf['orderby'], array('uid', 'regdate','logindate','contents','logins','credits','golds')) ? $conf['orderby'] : 'uid';
	$orderway = isset($conf['orderway']) && $conf['orderway'] == 1 ? 1 : -1;
	$start = _int($conf, 'start', 0);
	$limit = _int($conf, 'limit', 10);
    $showgroup = _int($conf, 'showgroup', 0);
    $extra = array('block_name'=>'block_user_list');
    // hook block_user_list_conf_after.php

    $where['groupid'] = $groupid;
    // hook block_user_list_where_after.php

    if($showgroup){
        $allgroups = $run->user_group->get_name();
    }else{
        $allgroups = array();
    }

	// 读取用户列表
    $list_arr = $run->user->list_arr($where, $orderby, $orderway, $start, $limit, $limit, $extra);
    $xuhao = 1;
	foreach($list_arr as &$v) {
		$run->user->format($v);
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

	// hook block_user_list_after.php

	return array('list'=> $list_arr);
}
