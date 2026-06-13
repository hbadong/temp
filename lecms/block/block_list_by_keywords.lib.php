<?php
defined('ROOT_PATH') || exit;

/**
 * 根据标题关键词读取内容列表模块，不适合大数据，建议使用缓存参数life
 * @param string keywords 关键词
 * @param int cid 分类ID 如果不填：自动识别 (不推荐用于读取频道分类，影响性能)
 * @param int mid 模型ID (默认为2)
 * @param string dateformat 时间格式
 * @param int titlenum 标题长度
 * @param int intronum 简介长度
 * @param string orderby 排序方式
 * @param int orderway 降序(-1),升序(1)
 * @param int start 开始位置
 * @param int limit 显示几条
 * @param int showcate 是否显示分类信息
 * @param int showviews 是否内容浏览量信息
 * @param int field_format 是否格式化主表自定义字段内容（主要是单选框、多选框、下拉框、图集等）
 * @param int life 缓存时间
 * @return array
 */
function block_list_by_keywords($conf) {
	global $run;

	// hook block_list_by_keywords_before.php

    //方便传递动态参数过来~     $GLOBALS['keywords'] = 'lecms';
    if((!isset($conf['keywords']) || empty($conf['keywords'])) && isset($GLOBALS['keywords'])){
        $conf['keywords'] = $GLOBALS['keywords'];
    }
    $keywords = isset($conf['keywords']) ? strtolower($conf['keywords']) : '';
    $cid = isset($conf['cid']) ? intval($conf['cid']) : (isset($_GET['cid']) ? intval($_GET['cid']) : 0);
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
    $extra = array('block_name'=>'block_list_by_keywords');
    $cache_params = array($keywords, $cid, $mid, $dateformat, $titlenum, $intronum, $orderby, $orderway, $start, $limit, $showcate, $showviews, $field_format);
    // hook block_list_by_keywords_conf_after.php

    // 初始化返回数组
    $ret = array('cate_name'=> '', 'cate_url'=> '', 'list'=> array());

    if($keywords == ''){
        return $ret;
    }

    //优先从缓存表读取。加前缀，避免不同的block，相同的cache_params，导致缓存数据错乱
    $cache_key = $life ? md5('list_by_keywords'.serialize($cache_params)) : '';
    if($cache_key){
        $cache_data = $run->runtime->get_block_data_cache($cache_key);
        if($cache_data){
            return $cache_data;
        }
    }

    if($cid){
        $cate_arr = $run->category->get_cache($cid);
        if(empty($cate_arr)) return $ret;
        $cate_name = $cate_arr['name'];
        $cate_url = $run->category->category_url($cate_arr);
        $table = &$cate_arr['table'];
        $mid = $cate_arr['mid'];

        if(!empty($cate_arr['son_cids']) && is_array($cate_arr['son_cids'])) {
            $where = array('cid' => array("IN" => $cate_arr['son_cids']), 'title'=>array('LIKE'=>$keywords)); // 影响数据库性能
        }else{
            $where = array('cid' => $cid, 'title'=>array('LIKE'=>$keywords));
        }
    }else{
        $cate_name = 'No Title';
        $cate_url = 'javascript:;';

        $table_arr = &$run->_cfg['table_arr'];
        $table = isset($table_arr[$mid]) ? $table_arr[$mid] : 'article';
        $where = array('title'=>array('LIKE'=>$keywords));
    }

    //过滤单页模型
    if($table == 'page'){
        return $ret;
    }

    $run->cms_content->table = 'cms_'.$table;

    if($showcate){
        $allcategorys = $run->category->get_category_db();
    }else{
        $allcategorys = array();
    }

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
        // hook block_list_by_keywords_foreach_after.php
    }

    $ret = array('cate_name'=> $cate_name, 'cate_url'=> $cate_url, 'list'=> $list_arr);

    // hook block_list_by_keywords_set_block_data_cache_before.php
    if($cache_key){
        $run->runtime->set_block_data_cache($cache_key, $ret, $life);
    }

    // hook block_list_by_keywords_after.php

    return $ret;
}
