<?php
/**
 * Author: dadadezhou <zhoudada97@foxmail.com>
 * Date: 2022-10-09
 * Time: 15:21
 * Description: 程序后台入口
 */
//当前APP文件夹名称
define('APP_NAME', 'admin');
//后台目录
define('ADMIN_PATH', dirname($_SERVER['SCRIPT_FILENAME']).'/');
//程序根目录
define('ROOT_PATH', dirname(ADMIN_PATH).'/');
//系统核心目录名
define('F_APP_NAME', 'lecms');
//系统核心目录
define('APP_PATH', ROOT_PATH.F_APP_NAME.'/');
//系统配置文件 不存在则开始安装
if(!is_file(APP_PATH.'config/config.inc.php')) exit('<html><body><script>location="../install/'.'"</script></body></html>');
//系统模型缓存目录
define('RUNTIME_MODEL', ROOT_PATH.'runcache/'.F_APP_NAME.'_model/');
//后台控制器目录
define('CONTROL_PATH', ADMIN_PATH.'control/');
//后台模板目录
define('VIEW_PATH', ADMIN_PATH.'view/');
//程序框架目录
define('FRAMEWORK_PATH', APP_PATH.'xiunophp/');
require FRAMEWORK_PATH.'xiunophp.php';
echo "\r\n<!--".number_format(microtime(1) - $_ENV['_start_time'], 4).'-->';