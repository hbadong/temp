<?php
defined('ROOT_PATH') || exit;

/**
 * 评论列表模块（不含单页）
 * @param int id 内容ID
 * @param int uid 用户ID
 * @param int mid 模型ID 默认为2 文章模型
 * @param string dateformat 内容时间格式
 * @param int humandate 评论人性化时间显示 默认开启 (开启: 1 关闭: 0)
 * @param int titlenum 内容标题长度
 * @param int intronum 内容简介长度
 * @param string orderby 排序方式
 * @param int orderway 降序(-1),升序(1)
 * @param int start 开始位置
 * @param int limit 显示几条
 * @param int showcms 是否显示cms内容信息
 * @param int life 缓存时间
 * @return array
 */
function block_comment_list($conf) {
	global $run;

	// hook block_comment_list_before.php

    $id = _int($conf, 'id', 0);
    $uid = _int($conf, 'uid', 0);
	$mid = _int($conf, 'mid', 2);

	$dateformat = empty($conf['dateformat']) ? 'Y-m-d H:i:s' : $conf['dateformat'];
    $humandate = isset($conf['humandate']) ? ($conf['humandate'] == 1 ? TRUE : FALSE) : TRUE;
	$titlenum = _int($conf, 'titlenum');
	$intronum = _int($conf, 'intronum');
	$orderby = isset($conf['orderby']) && in_array($conf['orderby'], array('commentid', 'id', 'dateline')) ? $conf['orderby'] : 'dateline';
	$orderway = isset($conf['orderway']) && $conf['orderway'] == 1 ? 1 : -1;
	$start = _int($conf, 'start');
	$limit = _int($conf, 'limit', 10);
    $showcms = _int($conf, 'showcms', 0);
    $life = isset($conf['life']) ? (int)$conf['life'] : (isset($run->_cfg['life']) ? (int)$run->_cfg['life'] : 0);
    $extra = array('block_name'=>'block_comment_list');
    $cache_params = array($id, $uid, $mid, $dateformat, $humandate, $titlenum, $intronum, $orderby, $orderway, $start, $limit, $showcms);
    // hook block_comment_list_conf_after.php

    //优先从缓存表读取。加前缀，避免不同的block，相同的cache_params，导致缓存数据错乱
    $cache_key = $life ? md5('comment_list'.serialize($cache_params)) : '';
    if($cache_key){
        $cache_data = $run->runtime->get_block_data_cache($cache_key);
        if($cache_data){
            return $cache_data;
        }
    }

    if($mid == 1){return array('list'=> array());}
    $table_arr = &$run->_cfg['table_arr'];
    $table = isset($table_arr[$mid]) ? $table_arr[$mid] : 'article';
    
	$where = array('mid'=>$mid);

	if($id) $where['id'] = $id;
    if($uid) $where['uid'] = $uid;
    // hook block_comment_list_where_after.php

    // 获取评论列表
    $list_arr = $run->cms_content_comment->list_arr($where, $orderby, $orderway, $start, $limit, $limit, $extra);

    $keys = array();
    $xuhao = 1;
    foreach($list_arr as &$v) {
        $run->cms_content_comment->format($v, $dateformat, $humandate);
        if( $showcms && !in_array($v['id'], $keys) ){
            $keys[] = $v['id'];
        }
        $v['xuhao'] = $xuhao;
        $xuhao++;
        // hook block_comment_list_foreach_after.php
    }

    if($showcms && $keys){
        // 初始模型表名
        $run->cms_content->table = 'cms_'.$table;
        $content_list_arr = $run->cms_content->mget($keys);
        foreach($content_list_arr as &$v) {
            $run->cms_content->format($v, $mid, $dateformat, $titlenum, $intronum);
        }

        $content_key = 'cms_'.$table.'-id-';
        foreach ($list_arr as &$v){
            if(isset($content_list_arr[$content_key.$v['id']])){
                $v['cms'] = $content_list_arr[$content_key.$v['id']];
            }else{
                $v['cms'] = array();
            }
            // hook block_comment_list_cms_foreach_after.php
        }
    }

    $ret = array('list'=> $list_arr);

    // hook block_comment_list_set_block_data_cache_before.php
    if($cache_key){
        $run->runtime->set_block_data_cache($cache_key, $ret, $life);
    }

	// hook block_comment_list_after.php

	return $ret;
}
