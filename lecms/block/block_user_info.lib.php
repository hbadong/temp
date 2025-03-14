<?php
defined('ROOT_PATH') || exit;

/**
 * 用户信息模块
 * @param int uid 用户UID
 * @param int showgroup 是否读取用户组信息
 * @param string dateformat 时间格式
 * @param int life 缓存时间
 * @return array
 */
function block_user_info($conf) {
	global $run;

	// hook block_user_info_before.php
    $uid = _int($conf, 'uid', 0);
    $showgroup = _int($conf, 'showgroup', 0);
    $dateformat = empty($conf['dateformat']) ? 'Y-m-d H:i:s' : $conf['dateformat'];
    $life = isset($conf['life']) ? (int)$conf['life'] : (isset($run->_cfg['life']) ? (int)$run->_cfg['life'] : 0);
    $cache_params = array($uid, $showgroup, $dateformat);
    // hook block_user_info_conf_after.php

    //优先从缓存表读取。加前缀，避免不同的block，相同的cache_params，导致缓存数据错乱
    $cache_key = $life ? md5('user_info'.serialize($cache_params)) : '';
    if($cache_key){
        $list_arr = $run->runtime->get_block_data_cache($cache_key);
        if($list_arr){
            return $list_arr;
        }
    }

    if( empty($uid) ){
        global $_show;
        $uid = isset($_show['uid']) ? $_show['uid'] : 0;
        if( empty($uid) ){
            return array();
        }
    }

	$user = $run->user->get_user_by_uid($uid);
    if( empty($user) ){
        return array();
    }

    empty($user['author']) && $user['author'] = $user['username'];

    $run->user->format($user, $dateformat, $showgroup);

    // hook block_userinfo_set_block_data_cache_before.php
    if($cache_key){
        $run->runtime->set_block_data_cache($cache_key, $user, $life);
    }

	// hook block_user_info_after.php

	return $user;
}
