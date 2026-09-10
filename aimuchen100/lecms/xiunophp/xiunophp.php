<?php
//框架入口文件
defined('FRAMEWORK_PATH') || exit('Access Denied.');
define('FRAMEWORK_VERSION', '1.0.0');

//PHP环境检查
version_compare(PHP_VERSION, '5.4.0', '>') || exit('require PHP > 5.4.0 !');
version_compare(PHP_VERSION, '8.2.0', '<') || exit('require PHP < 8.2.0 !');

// 记录开始运行时间
$_ENV['_start_time'] = microtime(1);

// 记录内存初始使用
define('MEMORY_LIMIT_ON', function_exists('memory_get_usage'));
if(MEMORY_LIMIT_ON) $_ENV['_start_memory'] = memory_get_usage();

//常量定义
defined('MAGIC_QUOTES_GPC') || define('MAGIC_QUOTES_GPC', ini_set("magic_quotes_runtime",0)? TRUE : FALSE);
defined('CONFIG_PATH') || define('CONFIG_PATH', APP_PATH.'config/');
defined('CONTROL_PATH') || define('CONTROL_PATH', APP_PATH.'control/');
defined('BLOCK_PATH') || define('BLOCK_PATH', APP_PATH.'block/');
defined('MODEL_PATH') || define('MODEL_PATH', APP_PATH.'model/');
defined('VIEW_PATH') || define('VIEW_PATH', ROOT_PATH.'view/');
defined('LOG_PATH') || define('LOG_PATH', ROOT_PATH.'log/');
defined('PLUGIN_PATH') || define('PLUGIN_PATH', APP_PATH.'plugin/');
defined('LANG_PATH') || define('LANG_PATH', APP_PATH.'lang/');
defined('RUNTIME_PATH') || define('RUNTIME_PATH', ROOT_PATH.'runcache/');
defined('RUNTIME_MODEL') || define('RUNTIME_MODEL', RUNTIME_PATH.APP_NAME.'_model/');
defined('RUNTIME_CONTROL') || define('RUNTIME_CONTROL', RUNTIME_PATH.APP_NAME.'_control/');

// 定义当前请求的系统常量
define('NOW_TIME',      $_SERVER['REQUEST_TIME']);
define('REQUEST_METHOD',$_SERVER['REQUEST_METHOD']);
define('IS_GET',        REQUEST_METHOD =='GET' ? true : false);
define('IS_POST',       REQUEST_METHOD =='POST' ? true : false);
define('IS_PUT',        REQUEST_METHOD =='PUT' ? true : false);
define('IS_DELETE',     REQUEST_METHOD =='DELETE' ? true : false);

//系统配置文件
include CONFIG_PATH.'config.inc.php';
//加载自定义路由配置文件
if( isset($_ENV['_config']['route_open']) && !empty($_ENV['_config']['route_open']) && is_file(CONFIG_PATH.'route.inc.php') ){
    include CONFIG_PATH.'route.inc.php';
}

//后台和前台，调试模式，分三种：0 关闭调试; 1 开启调试; 2 开发调试   注意：开启调试会暴露绝对路径和表前缀
if( defined('F_APP_NAME') ){
    defined('DEBUG') || define('DEBUG', (int)$_ENV['_config']['debug_admin']);
}else{
    defined('DEBUG') || define('DEBUG', (int)$_ENV['_config']['debug']);
}

//引入核心文件
if(DEBUG) {
	include FRAMEWORK_PATH.'lib/base.func.php';
	include FRAMEWORK_PATH.'lib/core.class.php';
	include FRAMEWORK_PATH.'lib/debug.class.php';
	include FRAMEWORK_PATH.'lib/log.class.php';
	include FRAMEWORK_PATH.'lib/model.class.php';
	include FRAMEWORK_PATH.'lib/view.class.php';
	include FRAMEWORK_PATH.'lib/control.class.php';
	include FRAMEWORK_PATH.'db/db.interface.php';
	include FRAMEWORK_PATH.'db/db_'.$_ENV['_config']['db']['type'].'.class.php';
	include FRAMEWORK_PATH.'cache/cache.interface.php';
	include FRAMEWORK_PATH.'cache/cache_memcache.class.php';
    include FRAMEWORK_PATH.'ext/network/Network__interface.php';
}else{
	$runfile = RUNTIME_PATH.'_lecms.php';
	if(!is_file($runfile)) {
		$s  = trim(php_strip_whitespace(FRAMEWORK_PATH.'lib/base.func.php'), "<?ph>\r\n");
		$s .= trim(php_strip_whitespace(FRAMEWORK_PATH.'lib/core.class.php'), "<?ph>\r\n");
		$s .= trim(php_strip_whitespace(FRAMEWORK_PATH.'lib/debug.class.php'), "<?ph>\r\n");
		$s .= trim(php_strip_whitespace(FRAMEWORK_PATH.'lib/log.class.php'), "<?ph>\r\n");
		$s .= trim(php_strip_whitespace(FRAMEWORK_PATH.'lib/model.class.php'), "<?ph>\r\n");
		$s .= trim(php_strip_whitespace(FRAMEWORK_PATH.'lib/view.class.php'), "<?ph>\r\n");
		$s .= trim(php_strip_whitespace(FRAMEWORK_PATH.'lib/control.class.php'), "<?ph>\r\n");
		$s .= trim(php_strip_whitespace(FRAMEWORK_PATH.'db/db.interface.php'), "<?ph>\r\n");
        $s .= trim(php_strip_whitespace(FRAMEWORK_PATH.'db/db_'.$_ENV['_config']['db']['type'].'.class.php'), "<?ph>\r\n");
		$s .= trim(php_strip_whitespace(FRAMEWORK_PATH.'cache/cache.interface.php'), "<?ph>\r\n");
		$s .= trim(php_strip_whitespace(FRAMEWORK_PATH.'cache/cache_memcache.class.php'), "<?ph>\r\n");
        $s .= trim(php_strip_whitespace(FRAMEWORK_PATH.'ext/network/Network__interface.php'), "<?ph>\r\n");
		$s = str_replace('defined(\'ROOT_PATH\') || exit;', '', $s);
		if( file_put_contents($runfile, '<?php defined(\'ROOT_PATH\') || exit; '.$s) === FALSE ){
            exit('_lecms.php write failed');
        }
		unset($s);
	}
	include $runfile;
}

define('IS_AJAX',       ((isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || !empty(R('ajax', 'R'))) ? true : false);

//传输协议 http:// 还是 https:// ？
defined('HTTP') || define('HTTP', http());

//开启session
session_start();

//启动
core::init_start();

//输出调试信息，排除 ajax
if(DEBUG > 1 && !IS_AJAX) {
	debug::debug_info();
}