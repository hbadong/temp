<?php
$_ENV['_config'] = array(
	'plugin_disable' => 0,			// 禁止掉所有插件
    'route_open' => 0,			    // 是否启用自定义路由，需搭配插件和config/route.inc.php里面的规则
	'zone' => 'Asia/Shanghai',		// 时区
    'lang' => '',  //前台语言
    'admin_lang' => '',  //后台语言
	'gzip' => 1,	// 开启 GZIP 压缩
	'auth_key' => 'yxbe49fo8s5c5o4inqf1qj718pebuil3',	// 加密KEY

	'lecms_parseurl' => 1,			// 是否开启前台伪静态

    //cookie
	'cookie_pre' => 'letfCJb_',
	'cookie_path' => '/',
	'cookie_domain' => '',

	// 数据库配置，type 为默认的数据库类型: mysql|mysqli|pdo_mysql
	'db' => array(
		'type' => 'pdo_mysql',
		// 主数据库
		'master' => array(
			'host' => '127.0.0.1',
            'port' => '3306',
			'user' => 'aimuchen100',
			'password' => 'aimuchen100',
			'name' => 'aimuchen100',
			'charset' => 'utf8mb4',
			'tablepre' => 'le_',
			'engine'=>'MyISAM',
		),
        // 从数据库(可以是从数据库服务器群，如果不设置将使用主数据库)
        /*
        'slaves' => array(
            array(
                'host' => '127.0.0.1',
                'port' => '3306',
                'user' => 'aimuchen100',
                'password' => 'aimuchen100',
                'name' => 'aimuchen100',
                'charset' => 'utf8',
                'tablepre' => 'le_',
                'engine'=>'MyISAM',
            ),
        ),
        */
	),

	'cache' => array(
		'enable'=>0,
		'l2_cache'=>1,
		'type'=>'memcache',
		'pre' => 'le_',
		'memcache'=>array (
			'multi'=>1,
			'host'=>'127.0.0.1',
			'port'=>'11211',
            'id'=>0,
		)
	),

	// 前台 (静态文件可以使用绝对路径做cdn加速)
	'front_static' => 'static/',
	// 后台
	'admin_static' => '../static/',
	'url_suffix' => '.html',
	'version' => '3.0.4',			// 版本号
	'release' => '20260415',		// 发布日期

    'php_error' => '1',   //开启写错误日志
    'php_error404' => '1',   //开启写404错误日志
    'debug' => '2',   //前台调试模式是否开启
    'debug_admin' => '0',   //后台调试模式是否开启
    'spider_user_agent' => '',    //搜索引擎UA标志，多个用英文逗号隔开
);
