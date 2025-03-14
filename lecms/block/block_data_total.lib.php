<?php
defined('ROOT_PATH') || exit;

/**
 * 数据统计模块
 * @param int mid 模型ID 必填 默认为 2
 * @param string source 分类 ， content表示内容， comment 表示评论 tag 表示标签 views表示浏览量 category表示分类数
 * @param int showviews 是否内容浏览量信息
 * @param int life 缓存时间
 * @return array
 */
function block_data_total($conf) {
	global $run;

	// hook block_data_total_before.php

    $mid = _int($conf, 'mid', 2);
    $source = empty($conf['source']) ? '' : $conf['source'];
    $showviews = _int($conf, 'showviews', 0);
    $life = isset($conf['life']) ? (int)$conf['life'] : (isset($run->_cfg['life']) ? (int)$run->_cfg['life'] : 0);
    $cache_params = array($mid, $source, $showviews);
    // hook block_data_total_conf_after.php

    $allow_source = array('content','comment','tag','views','category');
    if($source && !in_array($source, $allow_source)){
        return array();
    }

    $table_arr = &$run->_cfg['table_arr'];
    $table = isset($table_arr[$mid]) ? $table_arr[$mid] : '';

    if( empty($table) ){
        return  array();
    }

    //优先从缓存表读取。加前缀，避免不同的block，相同的cache_params，导致缓存数据错乱
    $cache_key = $life ? md5('data_total'.serialize($cache_params)) : '';
    if($cache_key){
        $cache_data = $run->runtime->get_block_data_cache($cache_key);
        if($cache_data){
            return $cache_data;
        }
    }

    $total = array();
	switch ($source){
        case 'content':
            $run->cms_content->table = 'cms_'.$table;
            $total['content'] = $run->cms_content->count();
            break;
        case 'comment':
            $total['comment'] = $run->cms_content_comment->find_count(array('mid'=>$mid));
            break;
        case 'tag':
            $run->cms_content_tag->table = 'cms_'.$table.'_tag';
            $total['tag'] = $run->cms_content_tag->count();
            break;
        case 'views':
            $table_prefix = $_ENV['_config']['db']['master']['tablepre'];
            $sql = "SELECT SUM(views) as views FROM {$table_prefix}cms_{$table}_views";
            $res = $run->db->fetch_first($sql);
            if(isset($res['views'])){
                if($res['views'] > 1000000){
                    $total['views'] = '100W+';
                }elseif ($res['views'] > 100000){
                    $total['views'] = '10W+';
                }else{
                    $total['views'] = (int)$res['views'];
                }
            }else{
                $total['views'] = 0;
            }
            break;
        case 'category':
            $total['category'] = $run->category->find_count(array('mid'=>$mid));
            break;
        default:
            $run->cms_content->table = 'cms_'.$table;
            $total['content'] = $run->cms_content->count();

            $total['comment'] = $run->cms_content_comment->find_count(array('mid'=>$mid));

            $run->cms_content_tag->table = 'cms_'.$table.'_tag';
            $total['tag'] = $run->cms_content_tag->count();

            $total['category'] = $run->category->find_count(array('mid'=>$mid));

            if($showviews){
                $table_prefix = $_ENV['_config']['db']['master']['tablepre'];
                $sql = "SELECT SUM(views) as views FROM {$table_prefix}cms_{$table}_views";
                $res = $run->db->fetch_first($sql);
                if(isset($res['views'])){
                    if($res['views'] > 1000000){
                        $total['views'] = '100W+';
                    }elseif ($res['views'] > 100000){
                        $total['views'] = '10W+';
                    }else{
                        $total['views'] = (int)$res['views'];
                    }
                }else{
                    $total['views'] = 0;
                }
            }

    }

    // hook block_data_total_set_block_data_cache_before.php
    if($cache_key){
        $run->runtime->set_block_data_cache($cache_key, $total, $life);
    }

	// hook block_data_total_after.php

	return $total;
}
