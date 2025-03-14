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
    $mid = isset($conf['mid']) ? max(2, intval($conf['mid'])) : (isset($_GET['mid']) ? max(2, intval($_GET['mid'])) : 2);
	$limit = _int($conf, 'limit', 10);
    $life = isset($conf['life']) ? (int)$conf['life'] : (isset($run->_cfg['life']) ? (int)$run->_cfg['life'] : 0);
    $cache_params = array($mid, $limit);
    // hook block_taglist_rand_conf_after.php

    //优先从缓存表读取。加前缀，避免不同的block，相同的cache_params，导致缓存数据错乱
    $cache_key = $life ? md5('taglist_rand'.serialize($cache_params)) : '';
    if($cache_key){
        $cache_data = $run->runtime->get_block_data_cache($cache_key);
        if($cache_data){
            return $cache_data;
        }
    }

    $table_arr = &$run->_cfg['table_arr'];
    $table = isset($table_arr[$mid]) ? $table_arr[$mid] : 'article';
    $table_prefix = $_ENV['_config']['db']['master']['tablepre'];
    $table_full = $table_prefix.'cms_'.$table.'_tag';

    $run->cms_content_tag->table = 'cms_'.$table.'_tag';
    $total = $run->cms_content_tag->count();

    $beishu = $limit > 10 ? 10 : 5;
    if($total > 1000 && $total > $limit*$beishu){//最低倍数，如果太少，可能陷入死循环，导致网站崩溃
        $keys = array();
        $i = 0;
        while ($i<$limit){
            //$sql = "SELECT tagid FROM {$table_full} WHERE tagid >= ((SELECT MAX(tagid) FROM {$table_full})-(SELECT MIN(tagid) FROM {$table_full})) * RAND() + (SELECT MIN(tagid) FROM {$table_full}) LIMIT 1";//这个sql一直在小范围循环
            $sql = "SELECT t1.tagid FROM {$table_full} AS t1 JOIN (SELECT ROUND(RAND() * (SELECT MAX(tagid) FROM {$table_full})) AS tagid) AS t2 WHERE t1.tagid >= t2.tagid LIMIT 1";  //这里不能是limit 多条，多条的话是连续的tagid
            $arr = $run->db->fetch_first($sql);
            if($arr && !in_array($arr['tagid'], $keys)){
                $keys[] = $arr['tagid'];
                $i++;
            }
        }
        // 读取内容列表
        $list_arr = $run->cms_content_tag->mget($keys);
    }else{
        $keys = array();
        $sql = "SELECT tagid FROM {$table_full} ORDER BY RAND() LIMIT {$limit}";
        $arr = $run->db->fetch_all($sql);
        foreach ($arr as $v){
            $keys[] = $v['tagid'];
        }
        // 读取内容列表
        $list_arr = $run->cms_content_tag->mget($keys);
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

    // hook block_taglist_rand_set_block_data_cache_before.php
	if($cache_key){
        $run->runtime->set_block_data_cache($cache_key, $ret, $life);
    }

	// hook block_taglist_rand_after.php

	return $ret;
}
