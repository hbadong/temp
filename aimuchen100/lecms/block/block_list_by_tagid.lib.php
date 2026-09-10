<?php
defined('ROOT_PATH') || exit;

/**
 * 根据标签ID读取内容列表模块
 * @param int tagid 标签ID
 * @param string tagids 标签ID串 (必须是同一个模型下的)
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
function block_list_by_tagid($conf) {
	global $run;

	// hook block_list_by_tagid_before.php

    $tagid = _int($conf, 'tagid', 0);   // 优先级高于tagids
    $tagids = empty($conf['tagids']) ? '' : $conf['tagids'];    //多个tagid 用,隔开
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
    $extra = array('block_name'=>'block_list_by_tagid');
    $cache_params = array($tagid, $tagids, $mid, $dateformat, $titlenum, $intronum, $orderway, $start, $limit, $showcate, $showviews);
    // hook block_list_by_tagid_conf_after.php

    //优先从缓存表读取。加前缀，避免不同的block，相同的cache_params，导致缓存数据错乱
    $cache_key = $life ? md5('list_by_tagid'.serialize($cache_params)) : '';
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
        return array('tag_name'=>'', 'tag_url'=>'', 'list'=> array());
    }

    $run->cms_content_tag->table = 'cms_'.$table.'_tag';
    $run->cms_content_tag_data->table = 'cms_'.$table.'_tag_data';
    $run->cms_content->table = 'cms_'.$table;

    if($showcate){
        $allcategorys = $run->category->get_category_db();
    }else{
        $allcategorys = array();
    }

    if($tagid){
        $tagdata = $run->cms_content_tag->get($tagid);
        if( empty($tagdata) ){
            return array('tag_name'=> 'No Title', 'tag_url'=> 'javascript:;', 'list'=> array());
        }
        $tag_arr = $run->cms_content_tag_data->find_fetch(array('tagid'=>$tagid), array('id'=>$orderway), $start, $limit);
        $keys = array();
        foreach($tag_arr as $v) {
            $keys[] = $v['id'];
        }

        $tag_name = $tagdata['name'];
        $tag_url = $run->cms_content->tag_url($mid, $tagdata);

        if( empty($keys) ){
            return array('tag_name'=> $tag_name, 'tag_url'=> $tag_url, 'list'=> array());
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
            // hook block_list_by_tagid_foreach_after.php
        }

        $ret = array('tag_name'=> $tag_name, 'tag_url'=> $tag_url, 'list'=> $list_arr);

        // hook block_list_by_tagid_set_block_data_cache_before.php
        if($cache_key){
            $run->runtime->set_block_data_cache($cache_key, $ret, $life);
        }

        // hook block_list_by_tagid_after.php

        return $ret;
    }elseif ($tagids){
        $ret = array();
        $tagid_arr = explode(',', $tagids);

        foreach($tagid_arr as $tagid){
            $tagdata = $run->cms_content_tag->get($tagid);
            if(empty($tagdata)){
                continue;
            }

            $ret[$tagid]['tag_name'] = $tagdata['name'];
            $ret[$tagid]['tag_url'] = $run->cms_content->tag_url($mid, $tagdata);

            $tag_arr = $run->cms_content_tag_data->find_fetch(array('tagid'=>$tagid), array('id'=>$orderway), $start, $limit);
            $keys = array();
            if( !empty($tag_arr) ){
                foreach($tag_arr as $tv) {
                    $keys[] = $tv['id'];
                }
            }

            if( empty($keys) ){
                $ret[$tagid]['list'] = array();
            }else{
                if($showviews){
                    $run->cms_content_views->table = 'cms_'.$table.'_views';
                    $views_list_arr = $run->cms_content_views->mget($keys);
                    $views_key = 'cms_'.$table.'_views-id-';
                }else{
                    $views_key = '';
                    $views_list_arr = array();
                }
                $list_arr = $run->cms_content->mget($keys);
                $xuhao = 1;
                foreach($list_arr as &$v) {
                    $run->cms_content->format($v, $mid, $dateformat, $titlenum, $intronum);
                    if($showcate && $allcategorys){
                        $cate = isset($allcategorys[$v['cid']]) ? $allcategorys[$v['cid']] : array();
                        $run->category->getCategoryInfoByList($v, $cate);
                    }
                    if($showviews && $views_list_arr){
                        $v['views'] = isset($views_list_arr[$views_key.$v['id']]) ? $views_list_arr[$views_key.$v['id']]['views'] : 0;
                    }
                    $v['xuhao'] = $xuhao;
                    $xuhao++;
                    // hook block_list_by_tagids_foreach_after.php
                }
                $ret[$tagid]['list'] = $list_arr;
            }
        }

        // hook block_list_by_tagid_set_block_data_cache_before.php
        if($cache_key){
            $run->runtime->set_block_data_cache($cache_key, $ret, $life);
        }

        // hook block_list_by_tagid_after.php

        return $ret;
    }else{
        return array('tag_name'=> 'No Title', 'tag_url'=> 'javascript:;', 'list'=> array());
    }
}
