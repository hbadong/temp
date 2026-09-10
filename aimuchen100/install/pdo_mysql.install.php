<?php
/**
 * Author: dadadezhou <zhoudada97@foxmail.com>
 * Date: 2021-11-18
 * Time: 9:10
 * Description: mysql pdo扩展
 */
$error ='';
$attr = array(
    PDO::ATTR_TIMEOUT => 5,
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
);
try {
    $link = new PDO("mysql:host=$dbhost;port=$dbport;dbname=$dbname", $dbuser, $dbpw, $attr);
    $link->query("SET names utf8, sql_mode=''");
} catch (Exception $e){
    $error = $e->getMessage() ;
}
if($error){
    if(strpos($error, 'Unknown database') !== FALSE) {
        $link = new PDO("mysql:host=$dbhost;port=$dbport;", $dbuser, $dbpw);
        try {
            $query = $link->query("CREATE DATABASE $dbname DEFAULT CHARACTER SET UTF8");
        } catch (Exception $e) {
            $s = $link->errorInfo();
            $s = str_replace($tablepre, '***', $s); // 防止泄露敏感信息
            js_back('自动创建数据库失败！您的MySQL账号是否有权限创建数据库，'.$s, 1);
        }
        $link = new PDO("mysql:host=$dbhost;port=$dbport;dbname=$dbname", $dbuser, $dbpw, $attr);
        $link->query("SET names utf8, sql_mode=''");
    }else{
        js_back($error, 1);
    }
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

    try {
        $ret = $link->query($sql);
        if(substr($sql, 0, 6) == 'CREATE') {
            $name = preg_replace("/CREATE TABLE ([`a-z0-9_]+) .*/is", "\\1", $sql);

            if($ret) {
                js_show('创建数据表 '.$name.' ... 成功', 0);
            }else{
                js_back('创建数据表 '.$name.' ... 失败 (您的数据库没有写权限？)', 1);
                exit();
            }
        }
    } catch (Exception $e) {
        js_back('创建数据表失败(您的数据库没有权限？)'.$e->getMessage(), 1);
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
    try {
        $ret = $link->query($sql);
        js_show('创建基本数据 ... 成功', 0);
    } catch (Exception $e) {
        js_show('创建基本数据 ... 失败'.$e->getMessage(), 1);
        exit();
    }
}

// 创建创始人
$salt = random(16, 3, '0123456789abcdefghijklmnopqrstuvwxyz'); // 增加破解难度
$password = md5(md5($adm_pass).$salt);
$ip = ip2long(ip());
$time = time();
$sql = "INSERT INTO `{$tablepre}user` (`uid`, `username`, `author`, `password`, `salt`, `groupid`, `email`, `homepage`, `intro`, `regip`, `regdate`) VALUES (1, '{$adm_user}', '{$adm_author}', '{$password}', '{$salt}', 1, '', '', '', {$ip}, {$time});";

try {
    $ret = $link->query($sql);
    js_show('创建创始人 ... 成功', 0);
} catch (Exception $e) {
    js_show('创建创始人 ... 失败'.$e->getMessage(), 1);
    exit();
}

//初始网站设置
$settings = addslashes(json_encode($cfg));
try {
    $ret = $link->query("INSERT INTO {$tablepre}kv SET k='cfg',v='{$settings}',expiry='0'");
    js_show('初始网站设置 ... 成功', 0);
} catch (Exception $e) {
    js_show('初始网站设置 ... 失败'.$e->getMessage(), 1);
    exit();
}