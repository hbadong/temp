<?php
defined('ROOT_PATH') || exit;

/**
 * 根据标签名读取内容列表模块
 * @param int name 标签名
 * @param int mid 模型ID (默认为2)
 * @param string dateformat 时间格式
 * @param int titlenum 标题长度
 * @param int intronum 简介长度
 * @param int orderway 降序(-1),升序(1)
 * @param int start 开始位置
 * @param int limit 总共显示几条内容列表
 * @param int showcate 是否显示分类信息
 * @param int showviews 是否内容浏览量信息
 * @param int life 缓存时间
 * @return array
 */
function block_list_by_tagname($conf) {
	global $run;

	// hook block_list_by_tagname_before.php

    $tagname = empty($conf['name']) ? '' : $conf['name'];
    $mid = isset($conf['mid']) ? max(2, intval($conf['mid'])) : (isset($_GET['mid']) ? max(2, intval($_GET['mid'])) : 2);
	$dateformat = empty($conf['dateformat']) ? 'Y-m-d H:i:s' : $conf['dateformat'];
	$titlenum = _int($conf, 'titlenum');
	$intronum = _int($conf, 'intronum');
	$orderway = isset($conf['orderway']) && $conf['orderway'] == 1 ? 1 : -1;
	$start = _int($conf, 'start');
	$limit = _int($conf, 'limit', 10);
    $showcate = _int($conf, 'showcate', 0);
    $showviews = _int($conf, 'showviews', 0);
    $life = isset($conf['life']) ? (int)$conf['life'] : (isset($run->_cfg['life']) ? (int)$run->_cfg['life'] : 0);
    $extra = array('block_name'=>'block_list_by_tagname');
    $cache_params = array($tagname, $mid, $dateformat, $titlenum, $intronum, $orderway, $start, $limit, $showcate, $showviews);
    // hook block_list_by_tagname_conf_after.php

    // 初始化返回数组
    $ret = array('tag_name'=>'', 'tag_url'=>'', 'list'=> array());

    if($tagname == ''){
        return $ret;
    }

    //优先从缓存表读取。加前缀，避免不同的block，相同的cache_params，导致缓存数据错乱
    $cache_key = $life ? md5('list_by_tagname'.serialize($cache_params)) : '';
    if($cache_key){
        $cache_data = $run->runtime->get_block_data_cache($cache_key);
        if($cache_data){
            return $cache_data;
        }
    }

    $table_arr = &$run->_cfg['table_arr'];
    $table = isset($table_arr[$mid]) ? $table_arr[$mid] : 'article';
    //过滤单页模型
    if($table == 'page'){
        return $ret;
    }

    $run->cms_content_tag->table = 'cms_'.$table.'_tag';
    $run->cms_content_tag_data->table = 'cms_'.$table.'_tag_data';
    $run->cms_content->table = 'cms_'.$table;

    //获取标签信息
    $tagdata = $run->cms_content_tag->get_tag_by_tagname($tagname);
    if( empty($tagdata) ){
        return array('tag_name'=> $tagname, 'tag_url'=> 'javascript:;', 'list'=> array());
    }
    $tagid = $tagdata['tagid'];

    $tag_arr = $run->cms_content_tag_data->list_arr($tagid, $orderway, $start, $limit, $limit, $extra);
    $keys = array();
    foreach($tag_arr as $v) {
        $keys[] = $v['id'];
    }

    $tag_url = $run->cms_content->tag_url($mid, $tagdata);

    if( empty($keys) ){
        return array('tag_name'=> $tagname, 'tag_url'=> $tag_url, 'list'=> array());
    }

    if($showcate){
        $allcategorys = $run->category->get_category_db();
    }else{
        $allcategorys = array();
    }

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
        $run->cms_content->format($v, $mid, $dateformat, $titlenum, $intronum);
        if($showcate && $allcategorys){
            $cate = isset($allcategorys[$v['cid']]) ? $allcategorys[$v['cid']] : array();
            $run->category->getCategoryInfoByList($v, $cate);
        }
        if($showviews && $views_list_arr){
            $v['views'] = isset($views_list_arr[$views_key.$v['id']]['views']) ? (int)$views_list_arr[$views_key.$v['id']]['views'] : 0;
        }
        $v['xuhao'] = $xuhao;
        $xuhao++;
        // hook block_list_by_tagname_foreach_after.php
    }

    $ret = array('tag_name'=> $tagname, 'tag_url'=> $tag_url, 'list'=>$list_arr);

    // hook block_list_by_tagname_set_block_data_cache_before.php
    if($cache_key){
        $run->runtime->set_block_data_cache($cache_key, $ret, $life);
    }

    // hook block_list_by_tagname_after.php

    return $ret;
}
