<?php
defined('ROOT_PATH') || exit;

/**
 * 指定时间内的内容数量统计
 * @param int mid 模型ID 必填 默认为 2
 * @param string type 时间类型   今天(today) 昨天(yesterday) 本周(week) 本月(month) 本年(year) 所有(all)
 * @param int life 缓存时间
 * @return int
 */
function block_content_total_by_date($conf) {
	global $run;

	// hook block_content_total_by_date_before.php

    $mid = _int($conf, 'mid', 2);
    $type = isset($conf['type']) ? $conf['type'] : 'all';
    $life = isset($conf['life']) ? (int)$conf['life'] : (isset($run->_cfg['life']) ? (int)$run->_cfg['life'] : 0);
    $cache_params = array($mid, $type);
    // hook block_content_total_by_date_conf_after.php

    $table_arr = &$run->_cfg['table_arr'];
    $table = isset($table_arr[$mid]) ? $table_arr[$mid] : '';

    if( empty($table) ){
        return  0;
    }

    //优先从缓存表读取。加前缀，避免不同的block，相同的cache_params，导致缓存数据错乱
    $cache_key = $life ? md5('content_total_by_date'.serialize($cache_params)) : '';
    if($cache_key){
        $cache_data = $run->runtime->get_block_data_cache($cache_key);
        if($cache_data){
            return (int)$cache_data;
        }
    }

    $where = array();
    $run->cms_content->table = 'cms_'.$table;
    $total = 0;
	switch($type) {
		case 'all':
            $where = array();
			break;
		case 'today':
            $starttime = mktime(0,0,0,date('m'),date('d'),date('Y'));
            $where = array('dateline'=>array('>'=>$starttime));
            break;
		case 'yesterday':
            $starttime = mktime(0,0,0,date('m'),date('d')-1,date('Y'));
            $endtime = mktime(0,0,0,date('m'),date('d'),date('Y'))-1;

            $where = array('dateline'=>array('>'=>$starttime, '<='=>$endtime));
            break;
        case 'week':
            //$starttime = mktime(0,0,0,date('m'),date('d')-date('w')+1,date('y')); //这个按周日开始

            $starttime = mktime(0, 0, 0, date('m'), (date('d') - (date('w')>0 ? date('w') : 7) + 1), date('Y'));    //周一开始

            $where = array('dateline'=>array('>'=>$starttime));
            break;
		case 'month':
            $starttime = mktime(0,0,0,date('m'),1,date('Y'));

            $where = array('dateline'=>array('>'=>$starttime));
            break;
        case 'year':
            $starttime  = strtotime(date('Y',time())."-1"."-1");

            $where = array('dateline'=>array('>'=>$starttime));
            break;
	}

    // hook block_content_total_by_date_type_after.php

    if($where){
        $total = $run->cms_content->find_count($where);
    }else{
        $total = $run->cms_content->count();
    }

    // hook block_content_total_by_date_set_block_data_cache_before.php
    if($cache_key){
        $run->runtime->set_block_data_cache($cache_key, $total, $life);
    }

	// hook block_content_total_by_date_after.php

	return $total;
}
