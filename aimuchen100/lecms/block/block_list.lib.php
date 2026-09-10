<?php
defined('ROOT_PATH') || exit;

/**
 * 内容列表模块
 * @param int cid 分类ID 如果不填：自动识别 (不推荐用于读取频道分类，影响性能)
 * @param string cids 分类ID串 (必须是同一个模型下的)
 * @param int mid 模型ID (当cid为0时，设置mid才能生效，否则程序自动识别)
 * @param string dateformat 时间格式
 * @param int titlenum 标题长度
 * @param int intronum 简介长度
 * @param string orderby 排序方式
 * @param int orderway 降序(-1),升序(1)
 * @param int start 开始位置
 * @param int limit 显示几条
 * @param int showcate 是否显示分类信息
 * @param int showviews 是否显示内容浏览量信息
 * @param int showsoncate 是否显示子分类信息（传递的cid有子级分类才有效）
 * @param int field_format 是否格式化主表自定义字段内容（主要是单选框、多选框、下拉框、图集等）
 * @param int life 缓存时间
 * @return array
 */
function block_list($conf) {
	global $run;

	// hook block_list_before.php

	$cid = isset($conf['cid']) ? intval($conf['cid']) : (isset($_GET['cid']) ? intval($_GET['cid']) : 0);
    $cids = empty($conf['cids']) ? '' : $conf['cids'];  //多个cid 用,隔开
	$mid = isset($conf['mid']) ? max(2, intval($conf['mid'])) : (isset($_GET['mid']) ? max(2, intval($_GET['mid'])) : 2);
	$dateformat = empty($conf['dateformat']) ? 'Y-m-d H:i:s' : $conf['dateformat'];
	$titlenum = _int($conf, 'titlenum');
	$intronum = _int($conf, 'intronum');
	$orderby = isset($conf['orderby']) && in_array($conf['orderby'], array('id', 'dateline', 'lasttime', 'comments')) ? $conf['orderby'] : 'id';
	$orderway = isset($conf['orderway']) && $conf['orderway'] == 1 ? 1 : -1;
	$start = _int($conf, 'start', 0);
	$limit = _int($conf, 'limit', 10);
    $showcate = _int($conf, 'showcate', 0);
    $showviews = _int($conf, 'showviews', 0);
    $showsoncate = _int($conf, 'showsoncate', 0);
    $field_format = _int($conf, 'field_format', 0);
    $life = isset($conf['life']) ? (int)$conf['life'] : (isset($run->_cfg['life']) ? (int)$run->_cfg['life'] : 0);
    $extra = array('block_name'=>'block_list');
    $cache_params = array($cid, $cids, $mid, $dateformat, $titlenum, $intronum, $orderby, $orderway, $start, $limit, $showcate, $showviews, $showsoncate, $field_format);
    // hook block_list_conf_after.php

    //优先从缓存表读取。加前缀，避免不同的block，相同的cache_params，导致缓存数据错乱
    $cache_key = $life ? md5('list'.serialize($cache_params)) : '';
    if($cache_key){
        $cache_data = $run->runtime->get_block_data_cache($cache_key);
        if($cache_data){
            return $cache_data;
        }
    }

    // 初始化返回数组
    $ret = array('cate_name'=> '', 'cate_url'=> '', 'list'=> array());

	// 读取分类内容
    if ($cids){
        $cid_arr = explode(',', $cids);
        $where = array('cid' => array("IN" => $cid_arr)); // 影响数据库性能

        $cate_name = 'No Title';
        $cate_url = 'javascript:;';

        $table_arr = &$run->_cfg['table_arr'];
        $table = isset($table_arr[$mid]) ? $table_arr[$mid] : 'article';
    }else{
        if($cid == 0) {
            $cate_name = 'No Title';
            $cate_url = 'javascript:;';

            $table_arr = &$run->_cfg['table_arr'];
            $table = isset($table_arr[$mid]) ? $table_arr[$mid] : 'article';

            $where = array();
        }else{
            $cate_arr = $run->category->get_cache($cid);
            if(empty($cate_arr)) return $ret;
            $cate_name = $cate_arr['name'];
            $cate_url = $run->category->category_url($cate_arr);
            $table = &$cate_arr['table'];
            $mid = $cate_arr['mid'];

            if(!empty($cate_arr['son_cids']) && is_array($cate_arr['son_cids'])) {
                $where = array('cid' => array("IN" => $cate_arr['son_cids'])); // 影响数据库性能
                if($showsoncate){
                    $soncatelist = $run->category->mget($cate_arr['son_cids']);
                    $xuhao = 1;
                    foreach($soncatelist as &$soncate){
                        $run->category->format($soncate);
                        $soncate['xuhao'] = $xuhao;
                        $xuhao++;
                    }
                }
            }else{
                $where = array('cid' => $cid);
            }
        }
    }
    //过滤单页模型
    if($table == 'page'){
        return $ret;
    }

    // hook block_list_where_after.php

    if($showcate){
        $allcategorys = $run->category->get_category_db();
    }else{
        $allcategorys = array();
    }

	// 初始模型表名
	$run->cms_content->table = 'cms_'.$table;

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
        // hook block_list_foreach_after.php
	}
    // hook block_list_list_arr_after.php
    $ret = array('cate_name'=> $cate_name, 'cate_url'=> $cate_url, 'list'=> $list_arr);
    if(isset($soncatelist)){
        $ret['soncate'] = $soncatelist;
    }

    // hook block_list_set_block_data_cache_before.php
    if($cache_key){
        $run->runtime->set_block_data_cache($cache_key, $ret, $life);
    }

	// hook block_list_after.php

	return $ret;
}
