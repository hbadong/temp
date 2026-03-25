<?php
defined('ROOT_PATH') || exit;

/**
 * 内容随机列表模块
 * @param int mid 模型ID
 * @param int cid 分类ID（不参与数据读取，只有在使用life时，可以使一样的conf参数，传递的cid不一样，获取结果集不一样）
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
function block_list_rand($conf) {
	global $run;

	// hook block_list_rand_before.php

    $mid = isset($conf['mid']) ? max(2, intval($conf['mid'])) : (isset($_GET['mid']) ? max(2, intval($_GET['mid'])) : 2);
	$dateformat = empty($conf['dateformat']) ? 'Y-m-d H:i' : $conf['dateformat'];
	$titlenum = _int($conf, 'titlenum');
	$intronum = _int($conf, 'intronum');
	$limit = _int($conf, 'limit', 10);
    $life = isset($conf['life']) ? (int)$conf['life'] : (isset($run->_cfg['life']) ? (int)$run->_cfg['life'] : 0);
    $showcate = _int($conf, 'showcate', 0);
    $showviews = _int($conf, 'showviews', 0);
    $field_format = _int($conf, 'field_format', 0);
    //cid参数，目前并无实质作用
    if(isset($conf['cid'])){
        $cid = (int)$conf['cid'];
    }else{
        if(isset($_GET['cid'])){
            $cid = intval($_GET['cid']);
        }else{
            $cid = 0;
        }
    }
    $cache_params = array($mid, $cid, $dateformat, $titlenum, $intronum, $limit, $showcate, $showviews, $field_format);
    // hook block_list_rand_conf_after.php

    //优先从缓存表读取。加前缀，避免不同的block，相同的cache_params，导致缓存数据错乱
    $cache_key = $life ? md5('list_rand'.serialize($cache_params)) : '';
    if($cache_key){
        $cache_data = $run->runtime->get_block_data_cache($cache_key);
        if($cache_data){
            return $cache_data;
        }
    }

    // 初始化返回数组
    $ret = array('list'=> array());

    $table_arr = &$run->_cfg['table_arr'];
    $table = isset($table_arr[$mid]) ? $table_arr[$mid] : 'article';
    //过滤单页模型
    if($table == 'page'){
        return $ret;
    }
    $table_prefix = $_ENV['_config']['db']['master']['tablepre'];
    $table_full = $table_prefix.'cms_'.$table;

    $run->cms_content->table = 'cms_'.$table;
    $total = $run->cms_content->count();

    $beishu = $limit > 10 ? 10 : 5;
    if($total > 1000 && $total > $limit*$beishu){//最低倍数，如果太少，可能陷入死循环，导致网站崩溃
        $keys = array();
        $i = 0;
        while ($i<$limit){
            //$sql = "SELECT id FROM {$table_full} WHERE id >= ((SELECT MAX(id) FROM {$table_full})-(SELECT MIN(id) FROM {$table_full})) * RAND() + (SELECT MIN(id) FROM {$table_full}) LIMIT 1";//这个sql一直在小范围循环
            $sql = "SELECT t1.id FROM {$table_full} AS t1 JOIN (SELECT ROUND(RAND() * (SELECT MAX(id) FROM {$table_full})) AS id) AS t2 WHERE t1.id >= t2.id LIMIT 1";  //这里不能是limit 多条，多条的话是连续的id
            $arr = $run->db->fetch_first($sql);
            if($arr && !in_array($arr['id'], $keys)){
                $keys[] = $arr['id'];
                $i++;
            }
        }
        // 读取内容列表
        $list_arr = $run->cms_content->mget($keys);
    }else{
        $keys = array();
        $sql = "SELECT id FROM {$table_full} ORDER BY RAND() LIMIT {$limit}";
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
        // hook block_list_rand_foreach.php
	}

	// hook block_list_rand_list_arr_after.php
    $ret = array('list'=> $list_arr);

    // hook block_list_rand_set_block_data_cache_before.php
	if($cache_key){
        $run->runtime->set_block_data_cache($cache_key, $ret, $life);
    }

	// hook block_list_rand_after.php

	return $ret;
}
