<?php
defined('ROOT_PATH') || exit;

/**
 * 标签列表模块
 * @param int mid 模型ID
 * @param string orderby 排序方式 (参数有 tagid count)
 * @param int orderway 降序(-1),升序(1)
 * @param int start 开始位置
 * @param int limit 显示几条标签
 * @param int cms_limit 读取标签相关的几条最新内容
 * @param int life 缓存时间
 * @return array
 */
function block_taglist($conf) {
	global $run;

	// hook block_taglist_before.php
    $mid = isset($conf['mid']) ? max(2, intval($conf['mid'])) : (isset($_GET['mid']) ? max(2, intval($_GET['mid'])) : 2);
    $_GET['mid'] = $mid;
	$table = isset($run->_cfg['table_arr'][$mid]) ? $run->_cfg['table_arr'][$mid] : 'article';
	$orderby = isset($conf['orderby']) && in_array($conf['orderby'], array('tagid', 'count', 'orderby')) ? $conf['orderby'] : 'count';
	$orderway = isset($conf['orderway']) && $conf['orderway'] == 1 ? 1 : -1;
    $start = _int($conf, 'start', 0);
	$limit = isset($conf['limit']) ? (int)$conf['limit'] : 10;
    $cms_limit = _int($conf, 'cms_limit', 0);
    $life = isset($conf['life']) ? (int)$conf['life'] : (isset($run->_cfg['life']) ? (int)$run->_cfg['life'] : 0);
    $extra = array('block_name'=>'block_taglist');
    $cache_params = array($mid, $table, $orderby, $orderway, $start, $limit, $cms_limit);
    // hook block_taglist_conf_after.php

    //优先从缓存表读取。加前缀，避免不同的block，相同的cache_params，导致缓存数据错乱
    $cache_key = $life ? md5('taglist'.serialize($cache_params)) : '';
    if($cache_key){
        $cache_data = $run->runtime->get_block_data_cache($cache_key);
        if($cache_data){
            return $cache_data;
        }
    }

	$run->cms_content_tag->table = 'cms_'.$table.'_tag';

	if($cms_limit){
        $run->cms_content->table = 'cms_'.$table;
        $run->cms_content_tag_data->table = 'cms_'.$table.'_tag_data';
    }

	$where = array();
    // hook block_taglist_where_after.php

    //标签列表
    $list_arr = $run->cms_content_tag->list_arr($where, $orderby, $orderway, $start, $limit, $limit, $extra);
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

        if($cms_limit){
            $tag_data_arr = $run->cms_content_tag_data->find_fetch(array('tagid'=>$v['tagid']), array('id'=>-1), 0, $cms_limit);
            $keys = array();
            foreach($tag_data_arr as $lv) {
                $keys[] = $lv['id'];
            }
            // 读取内容列表
            $cms_arr = $run->cms_content->mget($keys);
            foreach($cms_arr as &$cv) {
                $run->cms_content->format($cv, $mid);
            }
            $v['cms'] = $cms_arr;
            unset($cms_arr);
        }

        $v['xuhao'] = $xuhao;
        $xuhao++;
        // hook block_taglist_foreach_after.php
	}

    $ret = array('list'=> $list_arr);

    // hook block_taglist_set_block_data_cache_before.php
    if($cache_key){
        $run->runtime->set_block_data_cache($cache_key, $ret, $life);
    }

	// hook block_taglist_after.php

	return $ret;
}
