<?php
/**
 * Author: dadadezhou <zhoudada97@foxmail.com>
 * Date: 2022-10-09
 * Time: 15:21
 * Description: 程序入口
 */
define('CODE_COMPRESS', 0);
define('ROOT_PATH', dirname($_SERVER['SCRIPT_FILENAME']).'/');
define('APP_NAME', 'lecms');
define('APP_PATH', ROOT_PATH.APP_NAME.'/');
if(!is_file(APP_PATH.'config/config.inc.php')) exit('<html><body><script>location="./install/'.'"</script></body></html>');
define('FRAMEWORK_PATH', APP_PATH.'xiunophp/');
require FRAMEWORK_PATH.'xiunophp.php';
