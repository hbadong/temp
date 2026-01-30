<?php
defined('ROOT_PATH') || exit;

/**
 * 内容列表模块（增加根据字段读取，较少使用）
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
 * @param int field_format 是否格式化主表自定义字段内容（主要是单选框、多选框、下拉框、图集等）
 * @param int life 缓存时间
 * @param string field 字段名
 * @param string field_val 字段值（单个）
 * @param string field_match 匹配方式，默认为=，也可以是LIKE，只对单字段值有效
 * @param string field_vals 字段值（多个），优先级高于单字段值
 * @param string field_vals_sep 多个字段值分隔符，默认为,
 * @return array
 */
function block_list_by_field($conf) {
	global $run;

	// hook block_list_by_field_before.php

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
    $field_format = _int($conf, 'field_format', 0);
    $life = isset($conf['life']) ? (int)$conf['life'] : (isset($run->_cfg['life']) ? (int)$run->_cfg['life'] : 0);
    $field = isset($conf['field']) ? $conf['field'] : 'title';
    $field_val = isset($conf['field_val']) ? $conf['field_val'] : '';
    $field_vals = isset($conf['field_vals']) ? $conf['field_vals'] : '';
    $field_vals_sep = isset($conf['field_vals_sep']) ? $conf['field_vals_sep'] : ',';
    //内容页 field_val为某个变量？
    if(!$field_val && isset($GLOBALS['_show'][$field])){
        $field_val = $GLOBALS['_show'][$field];
    }
    $field_match = isset($conf['field_match']) ? $conf['field_match'] : '=';
    $extra = array('block_name'=>'block_list_by_field');
    $cache_params = array($cid, $cids, $mid, $dateformat, $titlenum, $intronum, $orderby, $orderway, $start, $limit, $showcate, $showviews, $field_format, $field, $field_val, $field_vals, $field_vals_sep, $field_match);
    // hook block_list_by_field_conf_after.php

    //优先从缓存表读取。加前缀，避免不同的block，相同的cache_params，导致缓存数据错乱
    $cache_key = $life ? md5('list_by_field'.serialize($cache_params)) : '';
    if($cache_key){
        $cache_data = $run->runtime->get_block_data_cache($cache_key);
        if($cache_data){
            return $cache_data;
        }
    }

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
            if(empty($cate_arr)) return;
            $cate_name = $cate_arr['name'];
            $cate_url = $run->category->category_url($cate_arr);
            $table = &$cate_arr['table'];
            $mid = $cate_arr['mid'];

            if(!empty($cate_arr['son_cids']) && is_array($cate_arr['son_cids'])) {
                $where = array('cid' => array("IN" => $cate_arr['son_cids'])); // 影响数据库性能
            }else{
                $where = array('cid' => $cid);
            }
        }
    }
    //过滤单页模型
    if($table == 'page'){
        return array();
    }

    //字段条件
    if($field){
        if($field_vals){
			$where[$field] = array("IN" => explode($field_vals_sep, $field_vals));
		}else{
			switch ($field_match){
				case 'LIKE':
					$where[$field] = array('LIKE'=>$field_val);
					break;
				default:
					$where[$field] = $field_val;
			}
		}
    }

    // hook block_list_by_field_where_after.php

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
        // hook block_list_by_field_foreach_after.php
	}
    // hook block_list_by_field_list_arr_after.php
    $ret = array('cate_name'=> $cate_name, 'cate_url'=> $cate_url, 'list'=> $list_arr);

    // hook block_list_by_field_set_block_data_cache_before.php
    if($cache_key){
        $run->runtime->set_block_data_cache($cache_key, $ret, $life);
    }

	// hook block_list_by_field_after.php

	return $ret;
}
