<?php
/**
 * 用户自定义函数库文件（建议函数名使用 misc_ 前缀开头，避免和lecms系统函数冲突）
 * 示例：（如果不确定某函数lecms是否存，建议先判断）
 * if( !function_exists('misc_xx') ){function misc_xx(){代码片段}}
 * 在插件里面实现下面的钩子，即可使用自定义函数
 */
defined('FRAMEWORK_PATH') || exit('Access Denied.');

//-----------------------常用页面判断，常用于模板里面的共用页面
//首页
function is_home(){
    // hook misc_func_is_home_before.php
    if( isset($_GET['control']) && isset($_GET['action']) && $_GET['control'] == 'index' && $_GET['action'] == 'index' ){
        return true;
    }else{
        return false;
    }
}
//分类页
function is_cate(){
    // hook misc_func_is_cate_before.php
    if( isset($_GET['control']) && isset($_GET['action']) && $_GET['control'] == 'cate' && $_GET['action'] == 'index' ){
        return true;
    }else{
        return false;
    }
}
//单页分类
function is_cate_page(){
    // hook misc_func_is_cate_page_before.php
    if( is_cate() && R('mid', 'G') == 1 ){
        return true;
    }else{
        return false;
    }
}
//列表分类页
function is_cate_list(){
    // hook misc_func_is_cate_list_before.php
    if( is_cate() && R('mid', 'G') > 1 && isset($_GET['type']) && R('type', 'G') == 0 ){
        return true;
    }else{
        return false;
    }
}
//频道分类页
function is_cate_channel(){
    // hook misc_func_is_cate_channel_before.php
    if( is_cate() && R('mid', 'G') > 1 && isset($_GET['type']) && R('type', 'G') == 1 ){
        return true;
    }else{
        return false;
    }
}
//内容页
function is_show(){
    // hook misc_func_is_show_before.php
    if( isset($_GET['control']) && isset($_GET['action']) && $_GET['control'] == 'show' && $_GET['action'] == 'index' ){
        return true;
    }else{
        return false;
    }
}
//标签列表页
function is_tag(){
    // hook misc_func_is_tag_before.php
    if( isset($_GET['control']) && isset($_GET['action']) && $_GET['control'] == 'tag' && $_GET['action'] == 'index' ){
        return true;
    }else{
        return false;
    }
}
//搜索结果页
function is_search(){
    // hook misc_func_is_search_before.php
    if( isset($_GET['control']) && isset($_GET['action']) && $_GET['control'] == 'search' && $_GET['action'] == 'index' ){
        return true;
    }else{
        return false;
    }
}

//是否是搜索引擎，方便扩展
function misc_is_spider(){
    // hook misc_func_misc_is_spider_before.php
    $r = isSpider();
    if($r){
        return true;
    }
    // hook misc_func_misc_is_spider_after.php
    return false;
}

//是否是移动端，方便扩展
function misc_is_mobile(){
    // hook misc_func_misc_is_mobile_before.php
    $r = is_mobile();
    if($r){
        return true;
    }
    // hook misc_func_misc_is_mobile_after.php
    return false;
}

// hook misc_func.php
?>