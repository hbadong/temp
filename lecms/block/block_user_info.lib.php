<?php
defined('ROOT_PATH') || exit;

/**
 * 用户信息模块
 * @param int pagenum 每页显示条数
 * @param int firstnum 首次显示条数 (有利于SEO)
 * @param string dateformat 时间格式
 * @param int humandate 人性化时间显示 默认开启 (开启: 1 关闭: 0)
 * @param int orderway 降序(-1),升序(1)
 * @return array
 */
function block_user_info($conf) {
	global $run;

	// hook block_user_info_before.php
    $uid = _int($conf, 'uid', 0);
    // hook block_user_info_conf_after.php

    if( empty($uid) ){
        global $_show;
        $uid = isset($_show['uid']) ? $_show['uid'] : 0;
        if( empty($uid) ){
            return array();
        }
    }

	$user = $run->user->get($uid);
    if( empty($user) ){
        return array();
    }

    empty($user['author']) && $user['author'] = $user['username'];

    $run->user->format($user);

	// hook block_user_info_after.php

	return $user;
}
