<?php
/**
 * Author: dadadezhou <zhoudada97@foxmail.com>
 * Date: 2022-10-09
 * Time: 15:21
 * Description: 程序入口
 */
//代码压缩，分两种：0 关闭; 1 开启 ，当开启debug时 不压缩代码，注意 开启代码压缩 一定要有规范的html js 代码，否则会出错
define('CODE_COMPRESS', 0);
//程序根目录
define('ROOT_PATH', dirname($_SERVER['SCRIPT_FILENAME']).'/');
//系统核心目录名
define('APP_NAME', 'lecms');
//核心目录
define('APP_PATH', ROOT_PATH.APP_NAME.'/');
//系统配置文件 不存在则开始安装
if(!is_file(APP_PATH.'config/config.inc.php')) exit('<html><body><script>location="./install/'.'"</script></body></html>');
//程序框架目录
define('FRAMEWORK_PATH', APP_PATH.'xiunophp/');
require FRAMEWORK_PATH.'xiunophp.php';
