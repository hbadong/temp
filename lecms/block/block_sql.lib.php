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
    $life = _int($conf, 'life', 0);
    // hook block_sql_conf_after.php

    if(empty($sql)) return array();

    if( strpos($sql, '@#') ) {    //使用表前缀
        $table_prefix = $_ENV['_config']['db']['master']['tablepre'];
        $sql = str_replace('@#', $table_prefix, $sql);
    }

    //优先从缓存表读取。加前缀，避免不同的block，相同的conf，导致缓存数据错乱
    $cache_key = $life ? md5('sql_'.$sql) : '';
    if($cache_key){
        $list_arr = $run->runtime->get($cache_key);
        if($list_arr){
            return array('list'=> $list_arr);
        }
    }

    $list_arr = $run->db->fetch_all($sql);

    if($cache_key){
        $run->runtime->set($cache_key, $list_arr, $life);
    }

    // hook block_sql_after.php

    return array('list'=> $list_arr);
}
