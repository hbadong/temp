<?php
defined('ROOT_PATH') || exit;

/**
 * 内容列表排行模块 (排行功能比较消耗资源，故暂时不增加 一周内、一月内 评论/点击排行功能，有此需求的用户二次开发吧)
 * @param int cid 分类ID 如果不填，为自动识别；如果cid为0时，为整个模型
 * @param int mid 模型ID (当cid为0时，设置mid才能生效，否则程序自动识别)
 * @param string dateformat 时间格式
 * @param int titlenum 标题长度
 * @param int intronum 简介长度
 * @param string orderby 排序方式  最后评论排列[lastdate] 评论数排列[comments] 点击数排列[views]
 * @param int orderway 降序(-1),升序(1)
 * @param int start 开始位置
 * @param int limit 显示几条
 * @param int life 缓存时间 (存放在runtime表)
 * @param int showcate 是否读取分类信息
 * @param int field_format 是否格式化主表自定义字段内容（主要是单选框、多选框、下拉框）
 * @return array
 */
function block_list_top($conf) {
	global $run;

	// hook block_list_top_before.php
	$cid = isset($conf['cid']) ? intval($conf['cid']) : (isset($_GET['cid']) ? intval($_GET['cid']) : 0);
    $mid = isset($conf['mid']) ? max(2, intval($conf['mid'])) : (isset($_GET['mid']) ? max(2, intval($_GET['mid'])) : 2);
	$dateformat = empty($conf['dateformat']) ? 'Y-m-d H:i:s' : $conf['dateformat'];
	$titlenum = _int($conf, 'titlenum');
	$intronum = _int($conf, 'intronum');
	$orderby = isset($conf['orderby']) && in_array($conf['orderby'], array('lastdate', 'comments', 'views')) ? $conf['orderby'] : 'lastdate';
	$orderway = isset($conf['orderway']) && $conf['orderway'] == 1 ? 1 : -1;
	$start = _int($conf, 'start');
	$limit = _int($conf, 'limit', 10);
    $life = isset($conf['life']) ? (int)$conf['life'] : (isset($run->_cfg['life']) ? (int)$run->_cfg['life'] : 0);
    $showcate = _int($conf, 'showcate', 0);
    $field_format = _int($conf, 'field_format', 0);
    // hook block_list_top_conf_after.php

    //优先从缓存表读取。加前缀，避免不同的block，相同的conf，导致缓存数据错乱
    $cache_key = $life ? md5('list_top'.serialize($conf)) : '';
    if($cache_key){
        $cache_data = $run->runtime->get_block_data_cache($cache_key);
        if($cache_data){
            return $cache_data;
        }
    }

	if($cid == 0) {
		// 当cid为0时，根据mid确定table
		$table_arr = &$run->_cfg['table_arr'];
		$table = isset($table_arr[$mid]) ? $table_arr[$mid] : 'article';
		$where = array();
	}else{
		$cate_arr = $run->category->get_cache($cid);
		$table = &$cate_arr['table'];
        $mid = $cate_arr['mid'];
        if(!empty($cate_arr['son_cids']) && is_array($cate_arr['son_cids'])) {
            $where = array('cid' => array("IN" => $cate_arr['son_cids'])); // 影响数据库性能
        }else{
            $where = array('cid' => $cid);
        }
	}
    //过滤单页模型
    if($table == 'page'){
        return array();
    }

    if($showcate){
        $allcategorys = $run->category->get_category_db();
    }else{
        $allcategorys = array();
    }

	if($orderby == 'views') {
		$run->cms_content_views->table = $table_key = 'cms_'.$table.'_views';
		$key_arr = $run->cms_content_views->find_fetch($where, array($orderby => $orderway), $start, $limit, $life);

		$table_key .= '-id-';
		$views_key = 'cms_'.$table.'_views-id-';
		$keys = array();
		foreach($key_arr as $lk=>$lv) {
			$keys[] = isset($lv['id']) ? $lv['id'] : (int)str_replace($views_key,'',$lk);
		}

		// 读取内容列表
		$run->cms_content->table = 'cms_'.$table;
		$list_arr = $run->cms_content->mget($keys);
        $xuhao = 1;
		foreach($list_arr as &$v) {
			$run->cms_content->format($v, $mid, $dateformat, $titlenum, $intronum);
			isset($v['id']) && $v['views'] = isset($key_arr[$table_key.$v['id']]['views']) ? (int)$key_arr[$table_key.$v['id']]['views'] : 0;

            if($showcate && $allcategorys){
                $cate = isset($allcategorys[$v['cid']]) ? $allcategorys[$v['cid']] : array();
                $run->category->getCategoryInfoByList($v, $cate);
            }
            $v['xuhao'] = $xuhao;
            $xuhao++;
            // hook block_list_top_views_foreach_after.php
		}
	}elseif ($orderby == 'comments'){
        // 读取内容列表
        $list_arr = $run->cms_content->find_fetch($where, array($orderby => $orderway), $start, $limit);
        $xuhao = 1;
        foreach($list_arr as &$v) {
            $run->cms_content->format($v, $mid, $dateformat, $titlenum, $intronum);

            if($showcate && $allcategorys){
                $cate = isset($allcategorys[$v['cid']]) ? $allcategorys[$v['cid']] : array();
                $run->category->getCategoryInfoByList($v, $cate);
            }
            $v['xuhao'] = $xuhao;
            $xuhao++;
            // hook block_list_top_comments_foreach_after.php
        }
    }elseif ($orderby == 'lastdate'){
	    if($cid){
            if(!empty($cate_arr['son_cids']) && is_array($cate_arr['son_cids'])) {
                $where = array('cid' => array("IN" => $cate_arr['son_cids'])); // 影响数据库性能
            }else{
                $where = array('cid' => $cid);
            }
        }else{
            $where = array('mid' => $mid);
        }
		$key_arr = $run->cms_content_comment_sort->find_fetch($where, array($orderby => $orderway), $start, $limit);

		$keys = array();
		foreach($key_arr as $lv) {
			$keys[] = $lv['id'];
		}

		// 读取内容列表
		$run->cms_content->table = 'cms_'.$table;
		$list_arr = $run->cms_content->mget($keys);
        $xuhao = 1;
		foreach($list_arr as &$v) {
			$run->cms_content->format($v, $mid, $dateformat, $titlenum, $intronum, $field_format);
            if($showcate && $allcategorys){
                $cate = isset($allcategorys[$v['cid']]) ? $allcategorys[$v['cid']] : array();
                $run->category->getCategoryInfoByList($v, $cate);
            }
            $v['xuhao'] = $xuhao;
            $xuhao++;
            // hook block_list_top_lastdate_foreach_after.php
		}
	}

    $ret = array('list'=> $list_arr);

	//写入缓存表
    if($cache_key){
        $run->runtime->set_block_data_cache($cache_key, $ret, $life);
    }

	// hook block_list_top_after.php

	return $ret;
}
