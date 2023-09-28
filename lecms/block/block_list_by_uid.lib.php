<?php
defined('ROOT_PATH') || exit;

/**
 * 根据用户UID读取内容列表模块
 * @param int uid 用户UID
 * @param int mid 模型ID (默认为2)
 * @param string dateformat 时间格式
 * @param int titlenum 标题长度
 * @param int intronum 简介长度
 * @param string orderby 排序方式
 * @param int orderway 降序(-1),升序(1)
 * @param int start 开始位置
 * @param int limit 显示几条
 * @param int showcate 是否显示分类信息
 * @param int showviews 是否内容浏览量信息
 * @param int field_format 是否格式化主表自定义字段内容（主要是单选框、多选框、下拉框、图集等）
 * @return array
 */
function block_list_by_uid($conf) {
	global $run;

	// hook block_list_by_uid_before.php

    $uid = _int($conf, 'uid', 0);
	$mid = _int($conf, 'mid', 2);
	$dateformat = empty($conf['dateformat']) ? 'Y-m-d H:i:s' : $conf['dateformat'];
	$titlenum = _int($conf, 'titlenum');
	$intronum = _int($conf, 'intronum');
    $orderby = isset($conf['orderby']) && in_array($conf['orderby'], array('id', 'dateline', 'lasttime', 'comments')) ? $conf['orderby'] : 'id';
    $orderway = isset($conf['orderway']) && $conf['orderway'] == 1 ? 1 : -1;
    $start = _int($conf, 'start', 0);
    $limit = _int($conf, 'limit', 10);
    $showcate = _int($conf, 'showcate', 0);
    $showviews = _int($conf, 'showviews', 0);
    $field_format = _int($conf, 'field_format', 0);
    $extra = array('block_name'=>'block_list_by_uid');
    // hook block_list_by_uid_conf_after.php

    $table_arr = &$run->_cfg['table_arr'];
    $table = isset($table_arr[$mid]) ? $table_arr[$mid] : 'article';
    //过滤单页模型
    if($table == 'page'){
        return array();
    }

    $run->cms_content->table = 'cms_'.$table;

    if($showcate){
        $allcategorys = $run->category->get_category_db();
    }else{
        $allcategorys = array();
    }

    if( empty($uid) ){
        global $_show;
        $uid = isset($_show['uid']) ? $_show['uid'] : 0;
        if( empty($uid) ){
            return array('username'=>'', 'author'=>'', 'list'=>array());
        }
    }

    $user = $run->user->read($uid);
    if( empty($user) ){
        return array('username'=>'', 'author'=>'', 'list'=>array());
    }

    $author = empty($user['author']) ? $user['username'] : $user['author'];

    $where['uid'] = $uid;
    // hook block_list_by_uid_where_after.php

    // 读取内容列表
    $list_arr = $run->cms_content->list_arr($where, $orderby, $orderway, $start, $limit, $limit, $extra);

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
        if($showcate && $allcategorys){
            $cate = isset($allcategorys[$v['cid']]) ? $allcategorys[$v['cid']] : array();
            $run->category->getCategoryInfoByList($v, $cate);
        }
        if($showviews && $views_list_arr){
            $v['views'] = isset($views_list_arr[$views_key.$v['id']]['views']) ? (int)$views_list_arr[$views_key.$v['id']]['views'] : 0;
        }
        $v['xuhao'] = $xuhao;
        $xuhao++;
        // hook block_list_by_uid_foreach_after.php
    }

    // hook block_list_by_uid_after.php

    return array('username'=>$user['username'], 'author'=>$author, 'list'=>$list_arr);
}
