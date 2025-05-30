<?php
/**
 * Author: dadadezhou <zhoudada97@foxmail.com>
 * Date: 2022-10-09
 * Time: 15:28
 * Description: 程序安装
 */

version_compare(PHP_VERSION, '5.4.0', '>') || die('require PHP > 5.4.0 !');

//从PHP 8.2开始，动态属性被弃用。将值设置为未声明的类属性将在第一次设置该属性时发出弃用通知
version_compare(PHP_VERSION, '8.2.0', '<') || die('require PHP < 8.2.0 !');
//安装目录
define('INSTALL_PATH', dirname($_SERVER['SCRIPT_FILENAME']));
//程序根目录
define('ROOT_PATH', dirname(INSTALL_PATH).'/');
//系统核心目录名
define('APP_NAME', 'lecms');
//核心目录
define('APP_PATH', ROOT_PATH.APP_NAME.'/');
//框架名
define('FRAMEWORK_NAME', 'xiunophp');

error_reporting(0);
date_default_timezone_set('Asia/Shanghai');
header('Content-Type: text/html; charset=UTF-8');

include APP_PATH.FRAMEWORK_NAME.'/lib/base.func.php';
include INSTALL_PATH.'/function.php';

$installweburl = http_url_path();   //当前安装网址，带install/
if( substr($installweburl,-8) == 'install/' ){   //当前安装网址，不带install/
    $installwebdir = substr($installweburl,0,-8);
}elseif( substr($installweburl,-7) == 'install' ){
    $installwebdir = substr($installweburl,0,-7);
}elseif( substr($installweburl,-17) == 'install/index.php' ){
    $installwebdir = substr($installweburl,0,-17);
}else{
    $installwebdir = '../../';
}

// 从 cookie 中获取数据，默认为中文
$_lang = R('install_lang', 'C');
$_lang == '' && $_lang = 'zh-cn';
if($_lang == 'zh-cn'){
    $_SERVER['lang'] = $lang = include INSTALL_PATH."/lang/zh-cn.php";
}else{
    $_SERVER['lang'] = $lang = include INSTALL_PATH."/lang/en.php";
}

// 已安装过程序
if(is_file(APP_PATH.'/config/config.inc.php')) {
    header("HTTP/1.0 404 Not Found");
    header("Status: 404 Not Found");
    include INSTALL_PATH.'/view/lock.php';
    exit;
}

//安装步骤
$do = isset($_GET['do']) && in_array($_GET['do'], array('lang','license', 'check_env', 'check_db', 'complete')) ? $_GET['do'] : 'lang';

if($do == 'lang') {
    if( empty($_POST) ){
        include INSTALL_PATH.'/view/lang.php';
    }else{
        $post_lang = R('lang', 'P');
        setcookie('install_lang', $post_lang);
        $res = array('status'=>1, 'message'=>'Successfully');
        echo json_encode($res);
        exit();
    }
}elseif($do == 'license') {
    $_lang = ( isset($_GET['lang']) && in_array($_GET['lang'], array('zh-cn', 'en')) ) ? $_GET['lang'] : 'zh-cn';
    setcookie('lang', $_lang);
    include INSTALL_PATH.'/view/license.php';
}elseif($do == 'check_env') {
    include INSTALL_PATH.'/view/check_env.php';
}elseif($do == 'check_db') {
    $mysql_support = function_exists('mysql_connect');
    $mysqli_support = extension_loaded('mysqli');
    $pdo_mysql_support = extension_loaded('pdo_mysql');

    $isphp5 = is_php('5.5.0');

    include INSTALL_PATH.'/view/check_db.php';
}elseif($do == 'complete') {
    include INSTALL_PATH.'/view/complete.php';

    if( empty($_POST) ){
        js_back(lang('data_error'), 1);
    }

    $dbtype = isset($_POST['dbtype']) ? trim($_POST['dbtype']) : '';
    $dbhost = isset($_POST['dbhost']) ? trim($_POST['dbhost']) : '';
    $dbport = isset($_POST['dbport']) ? trim($_POST['dbport']) : '3306';
    $dbuser = isset($_POST['dbuser']) ? trim($_POST['dbuser']) : '';
    $dbpw = isset($_POST['dbpw']) ? trim($_POST['dbpw']) : '';
    $dbname = isset($_POST['dbname']) ? trim($_POST['dbname']) : '';
    $charset = 'utf8';
    $tablepre = isset($_POST['dbpre']) ? trim($_POST['dbpre']) : '';
    $adm_user = isset($_POST['adm_user']) ? trim($_POST['adm_user']) : '';
    $adm_pass = isset($_POST['adm_pass']) ? trim(str_replace(' ', '', $_POST['adm_pass'])) : '';
    $adm_author = isset($_POST['adm_author']) ? trim($_POST['adm_author']) : '';
    empty($adm_author) && $adm_author = $adm_user;

    if(empty($dbhost)) {
        js_back(lang('db_host_no_empty'), 1);
    }elseif(empty($dbport)) {
        js_back(lang('db_port_no_empty'), 1);
    }elseif(empty($dbuser)) {
        js_back(lang('db_name_no_empty'), 1);
    }elseif(!preg_match('/^\w+$/', $dbname)) {
        js_back(lang('db_name_error'), 1);
    }elseif(empty($tablepre)) {
        js_back(lang('db_prefix_no_empty'), 1);
    }elseif(!preg_match('/^[a-z_]+$/', $tablepre)) {
        js_back(lang('db_prefix_error'), 1);
    }elseif(empty($adm_user)) {
        js_back(lang('username_no_empty'), 1);
    }elseif(strlen($adm_user) < 2) {
        js_back(lang('username_dis_less_2'), 1);
    }elseif(strlen($adm_user) > 16) {
        js_back(lang('username_dis_over_16'), 1);
    }elseif(strlen($adm_pass) < 6) {
        js_back(lang('password_dis_less_6'), 1);
    }elseif(strlen($adm_pass) > 32) {
        js_back(lang('password_dis_over_32'), 1);
    }

    // 初始网站设置
    $webdomain = empty($_SERVER['HTTP_HOST']) ? $_SERVER['SERVER_NAME'] : $_SERVER['HTTP_HOST'];
    $webdir = get_webdir();
    $weburl = http().$webdomain.$webdir;

    $cfg = array(
        'webname' => 'LECMS',
        'webdomain' => $webdomain,
        'webdir' => $webdir,
        'webmail' => 'admin@qq.com',
        'webqq'=>'',
        'webweixin'=>'',
        'webtel'=>'',
        'tongji' => '',
        'beian' => '',
        'copyright' => '底部版权信息',
        'seo_title' => 'LECMS',
        'seo_keywords' => 'LECMS',
        'seo_description' => 'LECMS',
        'show_seo_title_rule'=>'{title}-{webname}', //内容页SEO标题规则
        'show_seo_keywords_rule'=>'{title},{webname}', //内容页SEO关键词规则
        'show_seo_description_rule'=>'{webname}：{intro}', //内容页SEO描述规则
        'admin_layout'=>1,  //后台布局
        'auto_pic'=>1,  //自动缩略图
        'close_website'=>0, //关闭网站
        'close_search'=>0, //关闭搜索
        'close_views'=>0,   //关闭浏览量功能

        'open_attach' => 0, //是否开启附件上传
        'open_comment' => 0, //是否开启全站评论
        'open_comment_vcode' => 0, //是否开启评论验证码
        'comment_default_author' => '游客', //评论默认昵称
        'open_user' => 0,    //是否开启用户功能
        'open_user_register' => 0,   //是否开启用户注册
        'open_user_register_vcode' => 0, //是否开启用户注册验证码
        'open_user_login' => 0,  //是否开启用户登录
        'open_user_login_vcode' => 0,    //是否开启用户登录验证码
        'open_user_reset_password' => 0,    //是否开启用户找回密码，需开启邮件功能
        'user_avatar_width' => 200, //用户头像宽度
        'user_avatar_height' => 200, //用户头像高度
        'open_no_login_comment' => 0,    //是否开启未登录用户评论
        'open_mobile_view' => 0,     //是否开启移动端模板
        'open_title_check' => 0,    //是否开启标题重复检查
        'mobile_view'=>'mobile',   //移动端模板名
        'content_min_len'=>5,   //内容字段最小长度

        'open_email' => 0,
        'email_smtp'=>'smtp.qq.com',
        'email_port'=> 465,
        'email_account'=>'',
        'email_account_name'=>'',
        'email_password'=>'',

        'link_show' => '{cate_alias}/{id}.html',
        'link_show_type' => 2,
        'link_show_end' => '.html',
        'link_cate_page_pre' => '/page_',
        'link_cate_page_end' => '/',
        'link_cate_end' => '/',
        'link_tag_type' => 0,   //默认标签名形式的URL
        'link_tag_pre' => 'tag/',
        'link_tag_end' => '/',
        'link_tag_top' => 'tag_top',
        'link_comment_pre' => 'comment/',
        'link_space_pre' => 'space/',
        'link_space_end' => '/',

        'up_img_ext' => 'jpg,jpeg,gif,png,webp',
        'up_img_max_size' => '3074',
        'up_file_ext' => 'zip,gz,rar,iso,xls,xlsx,csv,doc,docx,ppt,wps,txt,pdf',
        'up_file_max_size' => '20480',
        'thumb_type' => 2,
        'thumb_quality' => 90,
        'watermark_pos' => 0,
        'watermark_pct' => 90,

        'admin_vcode' => 0, //后台登录验证码
        'admin_safe_entrance' => 0, //后台安全入口
        'admin_safe_auth' => random(6, 2),    //后台安全密钥
        'url_path'=>0,                      //0表示绝对URL，1表示相对URL
    );

    //写数据库操作
    if($dbtype == 'mysql'){
        include INSTALL_PATH.'/mysql.install.php';
    }elseif ($dbtype == 'mysqli'){
        include INSTALL_PATH.'/mysqli.install.php';
    }elseif($dbtype == 'pdo_mysql'){
        include INSTALL_PATH.'/pdo_mysql.install.php';
    }else{
        js_back(lang('no_mysql'), 1);
    }

    // 清空缓存
    $runtime = ROOT_PATH.'/runcache/';
    $file = $runtime.'_lecms.php';
    if(is_file($file)) {
        $ret = unlink($runtime.'_lecms.php');
        js_show(lang('clear').' runcache/_lecms.php ...'.lang('successfully'), 0);
    }
    $tpmdir = array('_control', '_model', '_view');
    foreach($tpmdir as $dir) {
        $ret = _rmdir($runtime.'lecms'.$dir);
        js_show(lang('clear').' runcache/lecms'.$dir.' ...'.lang('successfully'), 0);
    }
    foreach($tpmdir as $dir) {
        if($dir == '_model') continue;
        $ret = _rmdir($runtime.'admin'.$dir);
        js_show(lang('clear').' runcache/admin'.$dir.' ...'.lang('successfully'), 0);
    }

    // 初始插件配置
    $file = INSTALL_PATH.'/plugin.sample.php';
    if(!is_file($file)) {
        js_back(lang('plugin_file_non_existent'), 1);
    }
    $ret = file_put_contents(APP_PATH.'/config/plugin.inc.php', file_get_contents($file));
    if($ret){
        js_show(lang('setting').' lecms/config/plugin.inc.php ...'.lang('successfully'), 0);
    }else{
        js_show(lang('setting').' lecms/config/plugin.inc.php ...'.lang('failed'), 1);
        exit();
    }

    // 生成配置文件
    $file = INSTALL_PATH.'/config.sample.php';
    if(!is_file($file)) {
        js_back(lang('config_file_non_existent'), 1);
    }
    $auth_key = random(32, 2);
    $cookie_pre = 'le'.random(5, 3).'_';

    $s = file_get_contents($file);
    $s = preg_replace("#'auth_key' => '\w*',#", "'auth_key' => '".addslashes($auth_key)."',", $s);
    $s = preg_replace("#'cookie_pre' => '\w*',#", "'cookie_pre' => '".addslashes($cookie_pre)."',", $s);
    $s = preg_replace("#'type' => '\w*',#", "'type' => '".addslashes($dbtype)."',", $s);
    $s = preg_replace("#'host' => '\w*',#", "'host' => '".addslashes($dbhost)."',", $s);
    $s = preg_replace("#'port' => '\w*',#", "'port' => '".addslashes($dbport)."',", $s);
    $s = preg_replace("#'user' => '\w*',#", "'user' => '".addslashes($dbuser)."',", $s);
    $s = preg_replace("#'password' => '\w*',#", "'password' => '".addslashes($dbpw)."',", $s);
    $s = preg_replace("#'name' => '\w*',#", "'name' => '".addslashes($dbname)."',", $s);
    $s = preg_replace("#'tablepre' => '\w*',#", "'tablepre' => '".addslashes($tablepre)."',", $s);
    $s = preg_replace("#'pre' => '\w*',#", "'pre' => '".addslashes($tablepre)."',", $s);
    if($_lang == 'en'){
        $s = preg_replace("#'core_lang' => '[\w-]*',#", "'core_lang' => 'en',", $s);
        $s = preg_replace("#'lang' => '[\w-]*',#", "'lang' => 'en',", $s);
        $s = preg_replace("#'admin_lang' => '[\w-]*',#", "'admin_lang' => 'en',", $s);
    }

    $ret = file_put_contents(APP_PATH.'/config/config.inc.php', $s);
    if($ret){
        js_show(lang('setting').' lecms/config/config.inc.php ...'.lang('successfully'), 0);
    }else{
        js_show(lang('setting').' lecms/config/config.inc.php ...'.lang('failed'), 1);
        exit();
    }

    //自定义路由
    $file = INSTALL_PATH.'/route.sample.php';
    if( is_file($file) ){
        $s = file_get_contents($file);
        $ret = file_put_contents(APP_PATH.'/config/route.inc.php', $s);
        if($ret){
            js_show(lang('setting').' lecms/config/route.inc.php ...'.lang('successfully'), 0);
        }else{
            js_show(lang('setting').' lecms/config/route.inc.php ...'.lang('failed'), 1);
            exit();
        }
    }

    // 安装结束提示
    js_show(lang('install_successfully'), 3);
    $s = lang('home_url').'：<a style="color:#fff;font-size:16px;" href="'.$installwebdir.'" target="_blank">'.$installwebdir.'</a>';
    js_show($s, 3);
    $s = lang('admin_url').'：<a style="color:#fff;font-size:16px;" href="'.$installwebdir.'admin/" target="_blank">'.$installwebdir.'admin/</a>';
    js_show($s, 3);
    $s = lang('username').'：'.$adm_user.'，　'.lang('password').'：'.$adm_pass;
    js_show($s, 3);
    js_show(lang('delete_install_dir'), 1);
}