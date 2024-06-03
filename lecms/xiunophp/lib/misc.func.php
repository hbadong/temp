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
    if( isset($_GET['control']) && isset($_GET['action']) && $_GET['control'] == 'index' && $_GET['action'] == 'index' ){
        return true;
    }else{
        return false;
    }
}
//分类页
function is_cate(){
    if( isset($_GET['control']) && isset($_GET['action']) && $_GET['control'] == 'cate' && $_GET['action'] == 'index' ){
        return true;
    }else{
        return false;
    }
}
//单页分类
function is_cate_page(){
    if( is_cate() && R('mid', 'G') == 1 ){
        return true;
    }else{
        return false;
    }
}
//列表分类页
function is_cate_list(){
    if( is_cate() && R('mid', 'G') > 1 && isset($_GET['type']) && R('type', 'G') == 0 ){
        return true;
    }else{
        return false;
    }
}
//频道分类页
function is_cate_channel(){
    if( is_cate() && R('mid', 'G') > 1 && isset($_GET['type']) && R('type', 'G') == 1 ){
        return true;
    }else{
        return false;
    }
}
//内容页
function is_show(){
    if( isset($_GET['control']) && isset($_GET['action']) && $_GET['control'] == 'show' && $_GET['action'] == 'index' ){
        return true;
    }else{
        return false;
    }
}
//标签列表页
function is_tag(){
    if( isset($_GET['control']) && isset($_GET['action']) && $_GET['control'] == 'tag' && $_GET['action'] == 'index' ){
        return true;
    }else{
        return false;
    }
}
//搜索结果页
function is_search(){
    if( isset($_GET['control']) && isset($_GET['action']) && $_GET['control'] == 'search' && $_GET['action'] == 'index' ){
        return true;
    }else{
        return false;
    }
}

// hook misc_func.php
?>