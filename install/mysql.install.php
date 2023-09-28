<?php
/**
 * Author: dadadezhou <zhoudada97@foxmail.com>
 * Date: 2021-11-18
 * Time: 9:09
 * Description: mysql扩展
 */

// 连接数据库
if(!function_exists('mysql_connect')) {
    js_back('函数 mysql_connect() 不存在，请检查 php.ini 是否加载了 mysql 模块！', 1);
}

$link = mysql_connect($dbhost, $dbuser, $dbpw);
if(!$link) {
    js_back('MySQL 主机、账号或密码不正确！'.mysql_error(), 1);
}

try{
    mysql_select_db($dbname, $link);
    if(mysql_errno() == 1049) {
        mysql_query("CREATE DATABASE $dbname DEFAULT CHARACTER SET $charset");
        if(!mysql_select_db($dbname, $link)) {
            js_back('自动创建数据库失败，您的MySQL账号是否有权限创建数据库？'.mysql_error(), 1);
        }
    }
    // 为防止意外，让用户自己做选择
    if(empty($_POST['cover'])) {
        $query = mysql_query("SHOW TABLES FROM $dbname");
        while($row = mysql_fetch_row($query)) {
            if(preg_match("#^{$tablepre}#", $row[0])) {
                js_back('发现有相同表前缀，请返回选择“覆盖安装”或“修改表前缀”。', 1);
            }
        }
    }

    // 设置编码
    mysql_query("SET names utf8, sql_mode=''");
}catch(Exception $e) {
    js_back('未知错误！'.mysql_error(), 1);
}

// 创建数据表
$file = INSTALL_PATH.'/data/mysql.sql';
if(!is_file($file)) {
    js_back('mysql.sql 文件丢失', 1);
}
$s = file_get_contents($file);
$sqls = split_sql($s, $tablepre);

foreach($sqls as $sql) {
    $sql = str_replace("\n", '', trim($sql));

    $ret = mysql_query($sql);
    if(substr($sql, 0, 6) == 'CREATE') {
        $name = preg_replace("/CREATE TABLE ([`a-z0-9_]+) .*/is", "\\1", $sql);
        if($ret) {
            js_show('创建数据表 '.$name.' ... 成功', 0);
        }else{
            js_back('创建数据表 '.$name.' ... 失败 (您的数据库没有写权限？)'.mysql_error(), 1);
            exit();
        }
    }

    if(!$ret) {
        js_back('创建数据表失败(您的数据库没有权限？)'.mysql_error(), 1);
        exit();
    }
}

// 创建基本数据
$file = INSTALL_PATH.'/data/mysql_data.sql';
if(!is_file($file)) {
    js_back('mysql_data.sql 文件丢失', 1);
}
$s = file_get_contents($file);
$sqls = split_sql($s, $tablepre);
$ret = true;
foreach($sqls as $sql) {
    $sql = str_replace("\n", '', trim($sql));
    mysql_query($sql) || $ret = false;
}

if($ret){
    js_show('创建基本数据 ... 成功', 0);
}else{
    js_show('创建基本数据 ... 失败', 1);
    exit();
}

// 创建创始人
$salt = random(16, 3, '0123456789abcdefghijklmnopqrstuvwxyz'); // 增加破解难度
$password = md5(md5($adm_pass).$salt);
$ip = ip2long(ip());
$time = time();
$sql = "INSERT INTO `{$tablepre}user` (`uid`, `username`, `author`, `password`, `salt`, `groupid`, `email`, `homepage`, `intro`, `regip`, `regdate`) VALUES (1, '{$adm_user}', '{$adm_author}', '{$password}', '{$salt}', 1, '', '', '', {$ip}, {$time});";

$ret = mysql_query($sql);
if($ret){
    js_show('创建创始人 ... 成功', 0);
}else{
    js_show('创建创始人 ... 失败', 1);
    exit();
}

//初始网站设置
$settings = addslashes(json_encode($cfg));
$ret = mysql_query("INSERT INTO {$tablepre}kv SET k='cfg',v='{$settings}',expiry='0'");

if($ret){
    js_show('初始网站设置 ... 成功', 0);
}else{
    js_show('初始网站设置 ... 失败', 1);
    exit();
}