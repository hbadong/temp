<?php
defined('ROOT_PATH') || exit;

/**
 * 内容页模块 当前内容的前 limit 条， 后 limit 条
 * @param string type prev(前 多少条),next(后 多少条)
 * @param string dateformat 时间格式
 * @param int titlenum 标题长度
 * @param int intronum 简介长度
 * @param int limit 显示几条
 * @param int start 起始位置，一般不用填写，默认为0
 * @param int showcate 是否显示分类信息
 * @param int showviews 是否显示内容浏览量信息
 * @param int field_format 是否格式化主表自定义字段内容（主要是单选框、多选框、下拉框、图集等）
 * @param int cid 读取数据是否要分类CID参数
 * @param int life 缓存时间
 * @return array
 */
function block_list_prev_next($conf) {
    global $run, $_show;

	// hook block_list_prev_next_before.php
    // 排除单页模型
    $mid = &$run->_var['mid'];
    $type = isset($conf['type']) && in_array($conf['type'], array('prev', 'next')) ? $conf['type'] : 'prev';
	$dateformat = empty($conf['dateformat']) ? 'Y-m-d H:i:s' : $conf['dateformat'];
	$titlenum = _int($conf, 'titlenum');
	$intronum = _int($conf, 'intronum');
	$limit = _int($conf, 'limit', 10);
    $start = _int($conf, 'start', 0);
    $showcate = _int($conf, 'showcate', 0);
    $showviews = _int($conf, 'showviews', 0);
    $field_format = _int($conf, 'field_format', 0);
    $prev_next_cid = isset($conf['cid']) ? (int)$conf['cid'] : intval($_GET['cid']);
    $life = isset($conf['life']) ? (int)$conf['life'] : (isset($run->_cfg['life']) ? (int)$run->_cfg['life'] : 0);
    $extra = array('block_name'=>'block_list_prev_next');
    $cache_params = array($mid, $type, $dateformat, $titlenum, $intronum, $limit, $start, $showcate, $showviews, $field_format, $prev_next_cid);
    // hook block_list_prev_next_conf_after.php

    if($mid == 1) return FALSE;

    //优先从缓存表读取。加前缀，避免不同的block，相同的cache_params，导致缓存数据错乱
    $cache_key = $life ? md5('list_prev_next'.serialize($cache_params)) : '';
    if($cache_key){
        $cache_data = $run->runtime->get_block_data_cache($cache_key);
        if($cache_data){
            return $cache_data;
        }
    }

    $table = $run->_var['table'];

    // 初始模型表名
    $run->cms_content->table = 'cms_'.$table;
    $id = &$_show['id'];

    if($prev_next_cid){
        $prev_where = array('cid'=>$prev_next_cid, 'id'=>array('<'=> $id));
        $next_where = array('cid'=>$prev_next_cid, 'id'=>array('>'=> $id));
    }else{
        $prev_where = array('id'=>array('<'=> $id));
        $next_where = array('id'=>array('>'=> $id));
    }
    // hook block_list_prev_next_where_after.php

    if($type == 'prev'){
        $list_arr = $run->cms_content->list_arr($prev_where, 'id', -1, $start, $limit, $limit, $extra);
    }elseif ($type == 'next'){
        $list_arr = $run->cms_content->list_arr($next_where, 'id', 1, $start, $limit, $limit, $extra);
    }else{
        $list_arr = array();
    }

    // hook block_list_prev_next_center.php
    if( empty($list_arr) ){
        return array('list'=> array());
    }

    if($showcate){
        $allcategorys = $run->category->get_category_db();
    }else{
        $allcategorys = array();
    }

    if($showviews){
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
        // hook block_list_prev_next_foreach_after.php
	}

    $ret = array('list'=> $list_arr);

    // hook block_list_prev_next_set_block_data_cache_before.php
    if($cache_key){
        $run->runtime->set_block_data_cache($cache_key, $ret, $life);
    }

	// hook block_list_prev_next_after.php

	return $ret;
}
