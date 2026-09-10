<?php
/**
 * Author: dadadezhou <zhoudada97@foxmail.com>
 * Date: 2022-10-09
 * Time: 15:40
 * Description: 程序安装 函数库
 */

// 递归检测目录/文件是否写
function _dir_write($dir, $clear = FALSE) {
    static $ret = array();

    if($clear) $ret = array('yes'=>array(), 'no'=>array());

    if(!is_dir($dir) || _no_writable($dir) || !$dh = opendir($dir)) {
        $ret['no'][] = array($dir, substr(sprintf('%o', fileperms($dir)), -4));
    }else{
        $ret['yes'][] = array($dir, substr(sprintf('%o', fileperms($dir)), -4));

        while(($file = readdir($dh)) !== FALSE) {
            if($file!='.' && $file!='..') {
                $fileson = $dir.'/'.$file;
                if(is_dir($fileson)) {
                    _dir_write($fileson); // 递归检测
                }elseif(is_file($fileson)) {
                    if(_no_writable($fileson)) {
                        $ret['no'][] = array($fileson, substr(sprintf('%o', fileperms($fileson)), -4));
                    }else{
                        $ret['yes'][] = array($fileson, substr(sprintf('%o', fileperms($fileson)), -4));
                    }
                }
            }
        }
        closedir($dh);
    }

    return $ret;
}

// 不可写返回 TRUE
function _no_writable($dir) {
    if(_is_writable($dir)) {
        return FALSE;
    }else{
        function_exists('chmod') && chmod($dir, 0777); // 尝试自动修复权限
        return !_is_writable($dir);
    }
}

// 获取所在目录
function get_webdir() {
    $str = dirname(dirname($_SERVER['PHP_SELF']));
    if($str == '\\') return '/';
    if(strlen($str)>1) return $str.'/';
    else return '/';
}

// 分割SQL语句
function split_sql($sql, $tablepre) {
    $sql = str_replace('pre_', $tablepre, $sql);
    $sql = str_replace("\r", '', $sql);
    $ret = array();
    $num = 0;
    $queriesarray = explode(";\n", trim($sql));
    unset($sql);
    foreach($queriesarray as $query) {
        $ret[$num] = isset($ret[$num]) ? $ret[$num] : '';
        $queries = explode("\n", trim($query));
        foreach($queries as $query) {
            $ret[$num] .= isset($query[0]) && $query[0] == "#" ? '' : trim(preg_replace('/\#.*/', '', $query));
        }
        $num++;
    }
    return $ret;
}

// JS输出
function js_show($s) {
    echo '<script type="text/javascript">jsShow(\''.addslashes($s).'\');</script>'."\r\n";
    flush();
    ob_flush();
}

// JS输出提示返回
function js_back($s) {
    js_show($s.' <a href="javascript:history.back();">[返回]</a>');
    exit;
}

//判断PHP版本
function is_php($version = '5.5.0') {
    $php_version = explode( '-', phpversion() );
    // =0表示版本version  ＝1表示大于version  =-1表示小于version
    $is_pass = strnatcasecmp( $php_version[0], $version ) >= 0 ? true : false;
    return $is_pass;
}