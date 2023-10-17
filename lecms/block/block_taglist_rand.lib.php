<?php
defined('ROOT_PATH') || exit;

/**
 * 标签随机列表模块
 * @param int mid 模型ID 必填
 * @param int limit 显示几条
 * @param int life 缓存时间
 * @return array
 */
function block_taglist_rand($conf) {
	global $run;

	// hook block_taglist_rand_before.php
	$mid = _int($conf, 'mid', 2);
	$limit = _int($conf, 'limit', 10);
    $life = _int($conf, 'life', 0);
    // hook block_taglist_rand_conf_after.php

    //优先从缓存表读取。加前缀，避免不同的block，相同的conf，导致缓存数据错乱
    $cache_key = $life ? md5('taglist_rand'.serialize($conf)) : '';
    if($cache_key){
        $list_arr = $run->runtime->get_block_data_cache($cache_key);
        if($list_arr){
            return $list_arr;
        }
    }

    $table_arr = &$run->_cfg['table_arr'];
    $table = isset($table_arr[$mid]) ? $table_arr[$mid] : 'article';
    $table_prefix = $_ENV['_config']['db']['master']['tablepre'];
    $table_full = $table_prefix.'cms_'.$table.'_tag';

    $run->cms_content_tag->table = 'cms_'.$table.'_tag';
    $total = $run->cms_content_tag->count();

    if($total > $limit*5){//最低5倍，如果太少，可能陷入死循环，导致网站崩溃
        $keys = array();
        $i = 0;
        while ($i<$limit){
            $sql = "SELECT tagid FROM {$table_full} WHERE tagid >= ((SELECT MAX(tagid) FROM {$table_full})-(SELECT MIN(tagid) FROM {$table_full})) * RAND() + (SELECT MIN(tagid) FROM {$table_full}) LIMIT 1";
            $arr = $run->db->fetch_first($sql);
            if($arr && !in_array($arr['tagid'], $keys)){
                $keys[] = $arr['tagid'];
                $i++;
            }
        }
        // 读取内容列表
        $list_arr = $run->cms_content_tag->mget($keys);
    }else{
        $list_arr = $run->cms_content_tag->find_fetch(array(), array('tagid' => -1), 0, $limit);
        shuffle($list_arr);
        $list_arr = array_slice($list_arr, 0, $limit);
    }

    $xuhao = 1;
	foreach($list_arr as &$v) {
        $v['url'] = $run->cms_content->tag_url($mid, $v);
        if( empty($v['pic']) ){
            $v['pic'] = $run->_cfg['weburl'].'static/img/nopic.gif';
        }else{
            if( substr($v['pic'], 0, 2) != '//' && substr($v['pic'], 0, 4) != 'http' ){ //不是外链图片
                $v['pic'] = $run->_cfg['weburl'].$v['pic'];
            }
        }
        $v['xuhao'] = $xuhao;
        $xuhao++;
        // hook block_taglist_rand_foreach_after.php
	}

    $ret = array('list'=> $list_arr);

	if($cache_key){
        $run->runtime->set_block_data_cache($cache_key, $ret, $life);
    }

	// hook block_taglist_rand_after.php

	return $ret;
}
