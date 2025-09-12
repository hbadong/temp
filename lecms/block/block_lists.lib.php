<?php
defined('ROOT_PATH') || exit;

/**
 * 多分类内容列表模块
 * @param string cids 分类ID串 (必须是同一个模型下的，同时是列表分类，不能是频道分类CID)
 * @param int mid 模型ID (不填写时程序自动识别，默认为2)
 * @param string dateformat 时间格式
 * @param int titlenum 标题长度
 * @param int intronum 简介长度
 * @param string orderby 排序方式
 * @param int orderway 降序(-1),升序(1)
 * @param int start 开始位置
 * @param int limit 显示几条
 * @param int showviews 是否显示内容浏览量信息
 * @param int field_format 是否格式化主表自定义字段内容（主要是单选框、多选框、下拉框、图集等）
 * @param int life 缓存时间
 * @return array 返回分类信息和分类内容列表信息
 */
function block_lists($conf) {
	global $run;

	// hook block_lists_before.php

    $cids = empty($conf['cids']) ? '' : $conf['cids'];  //用,隔开
    $mid = isset($conf['mid']) ? max(2, intval($conf['mid'])) : (isset($_GET['mid']) ? max(2, intval($_GET['mid'])) : 2);
	$dateformat = empty($conf['dateformat']) ? 'Y-m-d H:i:s' : $conf['dateformat'];
	$titlenum = _int($conf, 'titlenum');
	$intronum = _int($conf, 'intronum');
	$orderby = isset($conf['orderby']) && in_array($conf['orderby'], array('id', 'dateline', 'lasttime', 'comments')) ? $conf['orderby'] : 'id';
	$orderway = isset($conf['orderway']) && $conf['orderway'] == 1 ? 1 : -1;
	$start = _int($conf, 'start', 0);
	$limit = _int($conf, 'limit', 10);
    $showviews = _int($conf, 'showviews', 0);
    $field_format = _int($conf, 'field_format', 0);
    $life = isset($conf['life']) ? (int)$conf['life'] : (isset($run->_cfg['life']) ? (int)$run->_cfg['life'] : 0);
    $extra = array('block_name'=>'block_lists');
    $cache_params = array($cids,$mid,$dateformat,$titlenum,$intronum,$orderby,$orderway,$start,$limit,$showviews,$field_format);
    // hook block_lists_conf_after.php

    if(empty($cids)){
        return FALSE;
    }

    //优先从缓存表读取。加前缀，避免不同的block，相同的cache_params，导致缓存数据错乱
    $cache_key = $life ? md5('lists'.serialize($cache_params)) : '';
    if($cache_key){
        $cache_data = $run->runtime->get_block_data_cache($cache_key);
        if($cache_data){
            return $cache_data;
        }
    }

    $ret = array();

    $table_arr = &$run->_cfg['table_arr'];
    $table = isset($table_arr[$mid]) ? $table_arr[$mid] : 'article';
    // 初始模型表名
    $run->cms_content->table = 'cms_'.$table;
    $run->cms_content_views->table = 'cms_'.$table.'_views';

    $cid_arr = explode(',', $cids);
    foreach ($cid_arr as $cid){
        $category = $run->category->get_cache($cid);
        if( empty($category) || $category['mid'] == 1 || $category['mid'] != $mid ){
            continue;
        }

        $run->category->format($category);
        $ret[$cid] = $category;

        if(isset($category['son_cids']) && !empty($category['son_cids'])) {
            $where = array('cid' => array("IN" => $category['son_cids']));
        }else{
            $where = array('cid'=>$cid);
        }

        // 读取内容列表
        $list_arr = $run->cms_content->list_arr($where, $orderby, $orderway, $start, $limit, $limit, $extra);

        if($showviews && $list_arr){
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

            if($showviews && $views_list_arr){
                $v['views'] = isset($views_list_arr[$views_key.$v['id']]['views']) ? (int)$views_list_arr[$views_key.$v['id']]['views'] : 0;
            }
            $v['xuhao'] = $xuhao;
            $xuhao++;
            // hook block_lists_foreach_after.php
        }

        // hook block_lists_cids_foreach_after.php

        $ret[$cid]['list'] = $list_arr;
    }

    // hook block_lists_set_block_data_cache_before.php
    if($cache_key){
        $run->runtime->set_block_data_cache($cache_key, $ret, $life);
    }

	// hook block_lists_after.php

	return $ret;
}
