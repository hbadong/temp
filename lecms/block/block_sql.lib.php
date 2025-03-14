<?php
defined('ROOT_PATH') || exit;

/**
 * SQL模块
 * @param string sql SQL语句
 * @param int life 缓存时间
 * @return array
 */
function block_sql($conf) {
    global $run;
    // hook block_sql_before.php
    $sql = empty($conf['sql']) ? '' : $conf['sql'];
    $life = isset($conf['life']) ? (int)$conf['life'] : (isset($run->_cfg['life']) ? (int)$run->_cfg['life'] : 0);
    $cache_params = array($sql);
    // hook block_sql_conf_after.php

    //优先从缓存表读取。加前缀，避免不同的block，相同的cache_params，导致缓存数据错乱
    $cache_key = $life ? md5('sql'.serialize($cache_params)) : '';
    if($cache_key){
        $cache_data = $run->runtime->get_block_data_cache($cache_key);
        if($cache_data){
            return $cache_data;
        }
    }

    if(empty($sql)) return array();

    if( strpos($sql, '@#') ) {    //使用表前缀
        $table_prefix = $_ENV['_config']['db']['master']['tablepre'];
        $sql = str_replace('@#', $table_prefix, $sql);
    }

    $list_arr = $run->db->fetch_all($sql);

    $ret = array('list'=> $list_arr);

    // hook block_sql_set_block_data_cache_before.php
    if($cache_key){
        $run->runtime->set_block_data_cache($cache_key, $ret, $life);
    }

    // hook block_sql_after.php

    return $ret;
}
