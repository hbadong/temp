<?php
defined('ROOT_PATH') || exit;

/**
 * 内容属性列表模块
 * @param int flag 属性ID (默认为0) [1=推荐 2=热点 3=头条 4=精选 5=幻灯]
 * @param int cid 分类ID 如果不填：自动识别 (不推荐用于读取频道分类，影响性能)
 * @param int mid 模型ID (当cid为0时，设置mid才能生效，否则程序自动识别)
 * @param string dateformat 时间格式
 * @param int titlenum 标题长度
 * @param int intronum 简介长度
 * @param int orderway 降序(-1),升序(1)
 * @param int start 开始位置
 * @param int limit 显示几条
 * @param int showcate 是否读取分类信息
 * @param int showviews 是否读取内容浏览量信息
 * @param int field_format 是否格式化主表自定义字段内容（主要是单选框、多选框、下拉框）
 * @param int life 缓存时间
 * @return array
 */
function block_list_flag($conf) {
	global $run;

	// hook block_list_flag_before.php

	$flag = _int($conf, 'flag', 0);
	$cid = isset($conf['cid']) ? intval($conf['cid']) : (isset($_GET['cid']) ? intval($_GET['cid']) : 0);
	$mid = isset($conf['mid']) ? max(2,intval($conf['mid'])) : (isset($_GET['mid']) ? max(2,intval($_GET['mid'])) : 2);
	$dateformat = empty($conf['dateformat']) ? 'Y-m-d H:i:s' : $conf['dateformat'];
	$titlenum = _int($conf, 'titlenum');
	$intronum = _int($conf, 'intronum');
	$orderway = isset($conf['orderway']) && $conf['orderway'] == 1 ? 1 : -1;
	$start = _int($conf, 'start');
	$limit = _int($conf, 'limit', 10);
    $showcate = _int($conf, 'showcate', 0);
    $showviews = _int($conf, 'showviews', 0);
    $field_format = _int($conf, 'field_format', 0);
    $life = isset($conf['life']) ? (int)$conf['life'] : (isset($run->_cfg['life']) ? (int)$run->_cfg['life'] : 0);
    $extra = array('block_name'=>'block_list_flag');
    $cache_params = array($flag, $cid, $mid, $dateformat, $titlenum, $intronum, $orderway, $start, $limit, $showcate, $showviews, $field_format);
    // hook block_list_flag_conf_after.php

    // 初始化返回数组
    $ret = array('list'=> array());

    if($flag == 0){
        return $ret;
    }

    //优先从缓存表读取。加前缀，避免不同的block，相同的cache_params，导致缓存数据错乱
    $cache_key = $life ? md5('list_flag'.serialize($cache_params)) : '';
    if($cache_key){
        $cache_data = $run->runtime->get_block_data_cache($cache_key);
        if($cache_data){
            return $cache_data;
        }
    }

	// 读取分类内容
	if($cid == 0) {
		$table_arr = &$run->_cfg['table_arr'];
		$table = isset($table_arr[$mid]) ? $table_arr[$mid] : 'article';

		$where = array('flag' => $flag);
	}else{
		$cate_arr = $run->category->get_cache($cid);
		if(empty($cate_arr)) return $ret;	// 分类不存在时直接返回，避免对 false 取下标触发 Notice
		$table = &$cate_arr['table'];
        $mid = $cate_arr['mid'];

		if(!empty($cate_arr['son_cids']) && is_array($cate_arr['son_cids'])) {
			$where = array('flag' => $flag, 'cid' => array("IN" => $cate_arr['son_cids'])); // 影响数据库性能
		}else{
			$where = array('flag' => $flag, 'cid' => $cid);
		}
	}
    //过滤单页模型
    if($table == 'page'){
        return $ret;
    }
    // hook block_list_flag_where_after.php

	// 初始模型表名
	$run->cms_content_flag->table = 'cms_'.$table.'_flag';

	// 读取内容列表
    $key_arr = $run->cms_content_flag->list_arr($where, 'id', $orderway, $start, $limit, $limit, $extra);

	$keys = array();
	foreach($key_arr as $v) {
		$keys[] = $v['id'];
	}

    if($showcate){
        $allcategorys = $run->category->get_category_db();
    }else{
        $allcategorys = array();
    }

    // 读取内容列表
    $run->cms_content->table = 'cms_'.$table;
    $list_arr = $run->cms_content->mget($keys);

    if($showviews && $list_arr){
        $run->cms_content_views->table = 'cms_'.$table.'_views';
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
        // hook block_list_flag_foreach_after.php
	}
    // hook block_list_flag_list_arr_after.php
    $ret = array('list'=> $list_arr);
    //分类信息
    if(isset($cate_arr) && $cate_arr){
        $run->category->format($cate_arr);
        $ret['cate'] = $cate_arr;
    }

    // hook block_list_flag_set_block_data_cache_before.php
    if($cache_key){
        $run->runtime->set_block_data_cache($cache_key, $ret, $life);
    }

	// hook block_list_flag_after.php

	return $ret;
}
