<?php
defined('ROOT_PATH') || exit;

/**
 * 根据分类CID内容随机列表模块
 * @param int cid 分类ID
 * @param string dateformat 时间格式
 * @param int titlenum 标题长度
 * @param int intronum 简介长度
 * @param int limit 显示几条
 * @param int life 缓存时间
 * @param int showcate 是否读取分类信息
 * @param int showviews 是否读取内容浏览量信息
 * @param int field_format 是否格式化主表自定义字段内容（主要是单选框、多选框、下拉框）
 * @return array
 */
function block_list_rand_by_cid($conf) {
	global $run;

	// hook block_list_rand_by_cid_before.php

    $cid = isset($conf['cid']) ? intval($conf['cid']) : (isset($_GET['cid']) ? intval($_GET['cid']) : 0);
	$dateformat = empty($conf['dateformat']) ? 'Y-m-d H:i' : $conf['dateformat'];
	$titlenum = _int($conf, 'titlenum');
	$intronum = _int($conf, 'intronum');
	$limit = _int($conf, 'limit', 10);
    $life = isset($conf['life']) ? (int)$conf['life'] : (isset($run->_cfg['life']) ? (int)$run->_cfg['life'] : 0);
    $showcate = _int($conf, 'showcate', 0);
    $showviews = _int($conf, 'showviews', 0);
    $field_format = _int($conf, 'field_format', 0);

    $cache_params = array($cid, $dateformat, $titlenum, $intronum, $limit, $showcate, $showviews, $field_format);
    // hook block_list_rand_by_cid_conf_after.php

    if(!$cid){
        return array('list'=>array());
    }

    //优先从缓存表读取。加前缀，避免不同的block，相同的cache_params，导致缓存数据错乱
    $cache_key = $life ? md5('list_rand_by_cid'.serialize($cache_params)) : '';
    if($cache_key){
        $cache_data = $run->runtime->get_block_data_cache($cache_key);
        if($cache_data){
            return $cache_data;
        }
    }

    $category = $run->category->get_cache($cid);
    if(empty($category) || $category['mid'] == 1){return array();}

    $cate_name = $category['name'];
    $cate_url = $run->category->category_url($category);
    $mid = $category['mid'];
    $table = $category['table'];
    $table_prefix = $_ENV['_config']['db']['master']['tablepre'];
    $table_full = $table_prefix.'cms_'.$table;
    $run->cms_content->table = 'cms_'.$table;

    $where = array('cid'=>$cid);
    $total = $run->cms_content->find_count($where);
    $keys = array();

    if($total <= $limit){
        $list_arr = $run->cms_content->list_arr($where, 'id', 1, 0, $total, $total);
        shuffle($list_arr);
    }else{
        $maxAttempts = $total * 2; // 设置最大尝试次数
        $offsets = array();
        $attempts = 0;

        while (count($offsets) < $limit && $attempts < $maxAttempts) {
            $offset = mt_rand(0, $total - 1);
            if (!isset($offsets[$offset])) {
                $offsets[$offset] = $offset;
            }
            $attempts++;
        }

        // 如果尝试次数过多仍未获取足够数量，改用顺序偏移
        if (count($offsets) < $limit) {
            $offsets = range(0, min($limit-1, $total - 1));
        }
        shuffle($offsets);
        $offsets = array_slice($offsets, 0, $limit);
        $offsets = array_values($offsets);

        // 使用UNION ALL合并多个查询
        $sql = "";
        foreach($offsets as $offset) {
            $sql .= "(SELECT id FROM {$table_full} WHERE cid = {$cid} LIMIT $offset, 1) UNION ALL ";
        }
        $sql = rtrim($sql, " UNION ALL "); // 移除最后一个UNION ALL
        $arr = $run->db->fetch_all($sql);
        foreach ($arr as $v){
            $keys[] = $v['id'];
        }
        // 读取内容列表
        $list_arr = $run->cms_content->mget($keys);
    }

    if($showcate){
        $allcategorys = $run->category->get_category_db();
    }else{
        $allcategorys = array();
    }

    if($showviews && $list_arr){
        $run->cms_content_views->table = 'cms_'.$table.'_views';
        if(empty($keys)){
            foreach($list_arr as $v) {
                $keys[] = $v['id'];
            }
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
        // hook block_list_rand_by_cid_foreach.php
	}

	// hook block_list_rand_by_cid_list_arr_after.php
    $ret = array('cate_name'=> $cate_name, 'cate_url'=> $cate_url, 'list'=> $list_arr);

    // hook block_list_rand_by_cid_set_block_data_cache_before.php
	if($cache_key){
        $run->runtime->set_block_data_cache($cache_key, $ret, $life);
    }

	// hook block_list_rand_by_cid_after.php

	return $ret;
}
