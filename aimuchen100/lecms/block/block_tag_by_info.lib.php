<?php
defined('ROOT_PATH') || exit;

/**
 * 标签列表模块
 * @param int mid 模型ID
 * @param int tagid 标签ID
 * @param string tagids 标签ID列表，多个用,隔开
 * @param string tagname 标签名称
 * @param string tagnames 标签名称列表，多个用,隔开
 * @param int life 缓存时间
 * @return array
 */
function block_tag_by_info($conf) {
	global $run;

	// hook block_tag_by_info_before.php
    $mid = isset($conf['mid']) ? max(2, intval($conf['mid'])) : (isset($_GET['mid']) ? max(2, intval($_GET['mid'])) : 2);
    $_GET['mid'] = $mid;
	$table = isset($run->_cfg['table_arr'][$mid]) ? $run->_cfg['table_arr'][$mid] : 'article';
    $tagid = _int($conf, 'tagid', 0);
    $tagids = isset($conf['tagids']) ? $conf['tagids'] : '';
    $tagname = isset($conf['tagname']) ? $conf['tagname'] : '';
    $tagnames = isset($conf['tagnames']) ? $conf['tagnames'] : '';
    $life = isset($conf['life']) ? (int)$conf['life'] : (isset($run->_cfg['life']) ? (int)$run->_cfg['life'] : 0);
    $extra = array('block_name'=>'block_tag_by_info');
    $cache_params = array($mid, $table, $tagid, $tagids, $tagname, $tagnames);
    // hook block_tag_by_info_conf_after.php

    //优先从缓存表读取。加前缀，避免不同的block，相同的cache_params，导致缓存数据错乱
    $cache_key = $life ? md5('tag_by_info'.serialize($cache_params)) : '';
    if($cache_key){
        $cache_data = $run->runtime->get_block_data_cache($cache_key);
        if($cache_data){
            return $cache_data;
        }
    }

	$run->cms_content_tag->table = 'cms_'.$table.'_tag';

	$where = array();
    if($tagid){
        $where['tagid'] = $tagid;
    }elseif($tagids){
        $tagidarr = explode(',', $tagids);
        $where['tagid'] = array("IN" => $tagidarr);
    }elseif($tagname){
        $where['name'] = $tagname;
    }elseif($tagnames){
        $tagnamesarr = explode(',', $tagnames);
        $where['name'] = array("IN" => $tagnamesarr);
    }

    // hook block_tag_by_info_where_after.php

    //标签列表
    if($where){
        $list_arr = $run->cms_content_tag->find_fetch($where);
        $xuhao = 1;
        foreach($list_arr as &$v) {
            $v['url'] = $run->cms_content->tag_url($mid, $v);
            if( empty($v['pic']) ){
                $v['pic'] = $run->_cfg['webdir'].'static/img/nopic.gif';
            }else{
                if( substr($v['pic'], 0, 2) != '//' && substr($v['pic'], 0, 4) != 'http' ){ //不是外链图片
                    $v['pic'] = $run->_cfg['webdir'].$v['pic'];
                }
            }

            $v['xuhao'] = $xuhao;
            $xuhao++;
            // hook block_tag_by_info_foreach_after.php
        }
    }else{
        $list_arr = array();
    }
    // hook block_tag_by_info_list_arr_after.php

    $ret = array('list'=> $list_arr);

    // hook block_tag_by_info_set_block_data_cache_before.php
    if($cache_key){
        $run->runtime->set_block_data_cache($cache_key, $ret, $life);
    }

	// hook block_tag_by_info_after.php

	return $ret;
}
