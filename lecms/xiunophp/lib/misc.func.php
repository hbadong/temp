<?php
/**
 * 用户自定义函数库文件（建议函数名使用 misc_ 前缀开头，避免和lecms系统函数冲突）
 * 示例：（如果不确定某函数lecms是否存，建议先判断）
 * if( !function_exists('misc_xx') ){function misc_xx(){代码片段}}
 * 在插件里面实现下面的钩子，即可使用自定义函数
 */
defined('FRAMEWORK_PATH') || exit('Access Denied.');

//-----------------------常用页面判断
function is_home(){
    if( isset($_GET['control']) && isset($_GET['action']) && $_GET['control'] == 'index' && $_GET['action'] == 'index' ){
        return true;
    }else{
        return false;
    }
}

function is_cate(){
    if( isset($_GET['control']) && isset($_GET['action']) && $_GET['control'] == 'cate' && $_GET['action'] == 'index' ){
        return true;
    }else{
        return false;
    }
}

function is_show(){
    if( isset($_GET['control']) && isset($_GET['action']) && $_GET['control'] == 'show' && $_GET['action'] == 'index' ){
        return true;
    }else{
        return false;
    }
}

function is_tag(){
    if( isset($_GET['control']) && isset($_GET['action']) && $_GET['control'] == 'tag' && $_GET['action'] == 'index' ){
        return true;
    }else{
        return false;
    }
}

function is_search(){
    if( isset($_GET['control']) && isset($_GET['action']) && $_GET['control'] == 'search' && $_GET['action'] == 'index' ){
        return true;
    }else{
        return false;
    }
}

// hook misc_func.php
?>