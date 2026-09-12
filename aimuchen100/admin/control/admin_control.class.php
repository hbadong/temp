<?php
/**
 * Author: dadadezhou <zhoudada97@foxmail.com>
 * Date: 2022-10-09
 * Time: 15:21
 * Description: 后台父类控制器
 */

defined('ROOT_PATH') or exit;
// hook admin_admin_control_before.php
class admin_control extends control {
    public $_uid = 0;   //用户ID
    public $_user = array();	// 用户
    public $_group = array();	// 用户组
    private $data = array();		// 防止重复查询

    function __construct() {
        // hook admin_admin_control_construct_before.php
        $_ENV['_config']['FORM_HASH'] = form_hash();
        $this->assign('C', $_ENV['_config']);
        $this->assign_value('core', F_APP_NAME);

        $err = 0;
        //登录验证（cookie或者session）
        $r = $this->user->user_token_check();
        // hook admin_admin_control_construct_user_token_check_after.php
        if($r['err']){
            $err = 1;
            // hook admin_admin_control_admauth_failed.php
        }else{
            // hook admin_admin_control_admauth_success_before.php
            $this->_uid = $r['user']['uid'];
            $this->_user = $r['user'];
            $this->_group = $r['user_group'];

            // 检查用户组权限 (如果非管理员将重新定义导航数组)
            $this->check_user_group();

            $this->assign('_uid', $this->_user['uid']);
            $this->assign('_user', $this->_user);
            $this->assign('_group', $this->_group);
            // hook admin_admin_control_admauth_success.php
        }

        if(R('control') == 'index' && (R('action') == 'login' || R('action') == 'vcode')) { //这里不验证是否启用安全密钥和密钥是否正确
            if(!$err) {
                exit('<html><body><script>top.location="index.php?index-index"</script></body></html>');
            }
        }elseif(R('control') == 'admin' && R('action') == 'init_navigation' && $err) {
            E(1, lang('please_login_again'));
        }elseif($err) {
            $login_r = $this->admin_safe_login_url(1);
            if(isset($login_r['admin_safe'])){  //知道了后台目录，开启了安全验证，不是通过安全入口登录，跳转到首页
                $login_url = $login_r['jumpurl'];
            }else{
                $login_url = $login_r;
            }
            if(R('ajax')) {
                $this->message(0, lang('illegal_access'), $login_url);
            }
            exit('<html><body><script>top.location="'.$login_url.'"</script></body></html>');
        }

        $GLOBALS['run'] = &$this;

        // hook admin_admin_control_construct_after.php
    }

    //获取安全的登录入口
    protected function admin_safe_login_url($r = 0){
        // hook admin_admin_control_admin_safe_login_url_before.php
        if( isset($this->data['cfg']) ) {
            $cfg = $this->data['cfg'];
        }else{
            $cfg = $this->kv->xget('cfg');
            $this->data['cfg'] = $cfg;
        }
        if(isset($cfg['admin_safe_entrance']) && $cfg['admin_safe_entrance'] && $cfg['admin_safe_auth']){
            $login_url = 'index.php?index-login-auth-'.$cfg['admin_safe_auth'];
            $admin_safe_auth = $cfg['admin_safe_auth'];
        }else{
            $login_url = 'index.php?index-login';
            $admin_safe_auth = '';
        }
        // hook admin_admin_control_admin_safe_login_url_after.php
        if($r && $admin_safe_auth){
            return array('admin_safe'=>1, 'login_url'=>$login_url, 'jumpurl'=>HTTP.$cfg['webdomain'].$cfg['webdir']);
        }else{
            return $login_url;
        }
    }

    // 检查是不是管理员
    protected function check_isadmin() {
        // hook admin_admin_control_check_isadmin_before.php
        if($this->_group['groupid'] != 1) {
            $this->message(0, lang('access_dis1'), -1);
        }
    }

    // 检查用户组权限
    protected function check_user_group() {
        // hook admin_admin_control_check_user_group_before.php

        if($this->_group['groupid'] == 1) return;
        if($this->_group['groupid'] > 5) {
            //清除登录信息，重新登录
            $this->user->user_token_logout(1);

            log::write(lang('access_dis2_log'), 'login_log.php');
            $this->message(0, lang('access_dis2'), -1);
        }else{
            // hook admin_admin_control_check_user_group_purviews_before.php

        }
    }

    // 清除缓存
    public function clear_cache() {
        // hook admin_admin_control_clear_cache_before.php

        $this->runtime->truncate();

        try{ unlink(RUNTIME_PATH.'_runtime.php'); }catch(Exception $e) {}
        $tpmdir = array('_control', '_model', '_view');
        foreach($tpmdir as $dir) _rmdir(RUNTIME_PATH.APP_NAME.$dir);
        foreach($tpmdir as $dir) _rmdir(RUNTIME_PATH.F_APP_NAME.$dir);
        return TRUE;
    }

    // 生成分页条（插件控制器通用）
    protected function get_pagebar($total, $pagenum, $page, $show_pages = 5, $extra = array()) {
        if($total <= $pagenum) return '';
        $pages = max(1, (int)ceil($total / $pagenum));
        $page = max(1, min((int)$page, $pages));
        $control = isset($_GET['control']) ? $_GET['control'] : '';
        $action = isset($_GET['action']) ? $_GET['action'] : '';
        $base = "index.php?{$control}-{$action}-page-";
        $qs = '';
        if(!empty($extra) && is_array($extra)) {
            foreach($extra as $k => $v) {
                $qs .= '&' . $k . '=' . urlencode($v);
            }
        }

        $html = '<div class="pagination" style="margin:10px 0;text-align:center;">';
        if($page > 1) {
            $html .= '<a class="layui-btn layui-btn-xs" href="'.$base.($page-1).$qs.'">&laquo; 上一页</a> ';
        }
        $start = max(1, $page - $show_pages);
        $end = min($pages, $page + $show_pages);
        for($i = $start; $i <= $end; $i++) {
            if($i == $page) {
                $html .= '<span class="layui-btn layui-btn-xs layui-btn-primary">'.$i.'</span> ';
            } else {
                $html .= '<a class="layui-btn layui-btn-xs" href="'.$base.$i.$qs.'">'.$i.'</a> ';
            }
        }
        if($page < $pages) {
            $html .= '<a class="layui-btn layui-btn-xs" href="'.$base.($page+1).$qs.'">&raquo; 下一页</a>';
        }
        $html .= '</div>';
        return $html;
    }

    // 初始化导航数组
    public function init_navigation() {
        // hook admin_admin_control_init_navigation_before.php

        //优先从缓存读取
        $cache_key = 'admin_navigation';
        $admin_navigation_arr = $this->runtime->get($cache_key);
        if($admin_navigation_arr){
            echo json_encode($admin_navigation_arr);
            exit();
        }

        //首个tab页和左上角链接
        $menu = array(
            'homeInfo' => array('title' => lang('home'), 'href' => 'index.php?my-index'),
            'logoInfo' => array('title' => lang('admin_manage'), 'href' => '', 'image' => $_ENV['_config']['admin_static'].'admin/images/logo.png'),
        );

        //操作类菜单
        $menu['menuInfo'] = array();

        // hook admin_admin_control_init_nav_before.php

        $menu['menuInfo']['setting'] = array(
            'title' => lang('setting'),
            'icon' => 'fa fa-cogs',
            'href' => '',
            'target' => '_self',
            'child' =>array(
                array('title' => lang('basic_setting'), 'href' => 'index.php?setting-index', 'icon' => 'fa fa-cog', 'target' => '_self'),
                array('title' => lang('seo_setting'), 'href' => 'index.php?setting-seo', 'icon' => 'fa fa-life-ring', 'target' => '_self'),
                array('title' => lang('link_setting'), 'href' => 'index.php?setting-link', 'icon' => 'fa fa-link', 'target' => '_self'),
                array('title' => lang('sitemap_setting'), 'href' => 'index.php?setting-sitemap', 'icon' => 'fa fa-map', 'target' => '_self'),
                array('title' => lang('user_setting'), 'href' => 'index.php?setting-user', 'icon' => 'fa fa-user-circle', 'target' => '_self'),
                array('title' => lang('attach_setting'), 'href' => 'index.php?setting-attach', 'icon' => 'fa fa-paperclip', 'target' => '_self'),
                array('title' => lang('image_setting'), 'href' => 'index.php?setting-image', 'icon' => 'fa fa-file-image-o', 'target' => '_self'),
                array('title' => lang('comment_setting'), 'href' => 'index.php?setting-comment', 'icon' => 'fa fa-comments-o', 'target' => '_self'),
                array('title' => lang('email_setting'), 'href' => 'index.php?setting-email', 'icon' => 'fa fa-envelope', 'target' => '_self'),
                array('title' => lang('security_setting'), 'href' => 'index.php?setting-security', 'icon' => 'fa fa-shield', 'target' => '_self'),
                array('title' => lang('other_setting'), 'href' => 'index.php?setting-other', 'icon' => 'fa fa-info', 'target' => '_self'),
            ),
        );

        // hook admin_admin_control_init_nav_setting_after.php

        $menu['menuInfo']['category'] = array(
            'title' => lang('category_manage'),
            'icon' => 'fa fa-bars',
            'href' => '',
            'target' => '_self',
            'child' =>array(
                array('title' => lang('cate_manage'), 'href' => 'index.php?category-index', 'icon' => 'fa fa-window-restore', 'target' => '_self'),
                array('title' => lang('page_manage'), 'href' => 'index.php?cms_page-index', 'icon' => 'fa fa-window-maximize', 'target' => '_self'),
                array('title' => lang('navigate_manage'), 'href' => 'index.php?navigate-index', 'icon' => 'fa fa-list', 'target' => '_self'),
                //array('title' => lang('mobil_navigate_manage'), 'href' => 'index.php?navigate-index-mobile-1', 'icon' => 'fa fa-list', 'target' => '_self'),
            ),
        );

        //自定义导航位
        $nav_location_arr = $this->kv->get('navigate_location');
        if(is_array($nav_location_arr) && $nav_location_arr){
            foreach ($nav_location_arr as $nav_location_alias=>$nav_location_name){
                $menu['menuInfo']['category']['child'][] = array('title' => $nav_location_name, 'href' => 'index.php?navigate-index-location-'.$nav_location_alias, 'icon' => 'fa fa-list', 'target' => '_self');
            }
        }

        // hook admin_admin_control_init_nav_category_after.php

        $menu['menuInfo']['content'] = array(
            'title' => lang('content_manage'),
            'icon' => 'fa fa-folder-open',
            'href' => '',
            'target' => '_self',
            'child' =>array(),
        );

        $models_arr = $this->models->get_models();
        unset($models_arr['models-mid-1']); //去掉单页模型

        foreach ($models_arr as $model){
            if( $_ENV['_config']['admin_lang'] == 'zh-cn' ){
                $modelname = $model['name'];
            }else{
                $modelname = ucfirst($model['tablename']);
            }
            $href = 'index.php?content-index-mid-'.$model['mid'];

            //优先使用本模型的后台管理路由控制器，比如你添加了产品模型，表名为product，那么本身的管理路由文件为 product_admin_control.class.php
            $model_control_file = ADMIN_PATH.'control/'.$model['tablename'].'_admin_control.class.php';
            if( is_file($model_control_file) ){
                $href = 'index.php?'.$model['tablename'].'_admin-index';
            }

            // hook admin_admin_control_init_nav_content_foreach.php

            $menu['menuInfo']['content']['child'][] = array('title' => $modelname.lang('manage'), 'href' => $href, 'icon' => isset($model['icon']) ? $model['icon'] : 'fa fa-bars', 'target' => '_self' , 'table'=>$model['tablename']);
        }

        // hook admin_admin_control_init_nav_content_center.php

        $menu['menuInfo']['content']['child'][] = array('title' => lang('tags_manage'), 'href' => 'index.php?tag-index', 'icon' => 'fa fa-tags', 'target' => '_self');
        $menu['menuInfo']['content']['child'][] = array('title' => lang('comment_manage'), 'href' => 'index.php?comment-index', 'icon' => 'fa fa-comment-o', 'target' => '_self');
        $menu['menuInfo']['content']['child'][] = array('title' => lang('flag_manage'), 'href' => 'index.php?flags-index', 'icon' => 'fa fa-flag', 'target' => '_self');
        $menu['menuInfo']['content']['child'][] = array('title' => lang('attach_manage'), 'href' => 'index.php?attach_manage-index', 'icon' => 'fa fa-file', 'target' => '_self');
        $menu['menuInfo']['content']['child'][] = array('title' => lang('model_manage'), 'href' => 'index.php?models-index', 'icon' => 'fa fa-cube', 'target' => '_self');

        // hook admin_admin_control_init_nav_content_after.php

        $menu['menuInfo']['user'] = array(
            'title' => lang('user_manage'),
            'icon' => 'fa fa-user-circle-o',
            'href' => '',
            'target' => '_self',
            'child' =>array(
                array('title' => lang('user_manage'), 'href' => 'index.php?user-index', 'icon' => 'fa fa-user', 'target' => '_self'),
                array('title' => lang('user_group_manage'), 'href' => 'index.php?user_group-index', 'icon' => 'fa fa-users', 'target' => '_self'),
            ),
        );

        // hook admin_admin_control_init_nav_user_after.php

        $menu['menuInfo']['plugin'] = array(
            'title' => lang('plugin_theme'),
            'icon' => 'fa fa-plug',
            'href' => '',
            'target' => '_self',
            'child' =>array(
                array('title' => lang('plugin_manage'), 'href' => 'index.php?plugin-index', 'icon' => 'fa fa-plug', 'target' => '_self'),
                array('title' => lang('theme_manage'), 'href' => 'index.php?theme-index', 'icon' => 'fa fa-tachometer', 'target' => '_self'),
            ),
        );

        // hook admin_admin_control_init_nav_plugin_after.php

        $menu['menuInfo']['tools'] = array(
            'title' => lang('tool_manage'),
            'icon' => 'fa fa-wrench',
            'href' => '',
            'target' => '_self',
            'child' =>array(
                array('title' => lang('clear_cache'), 'href' => 'index.php?tool-index', 'icon' => 'fa fa-trash-o', 'target' => '_self'),
                array('title' => lang('rebuild_statistics'), 'href' => 'index.php?tool-rebuild', 'icon' => 'fa fa-wrench', 'target' => '_self'),
                array('title' => lang('clear_log'), 'href' => 'index.php?tool-log', 'icon' => 'fa fa-times-rectangle-o', 'target' => '_self'),
                array('title' => lang('db_dictionary'), 'href' => 'index.php?db-index', 'icon' => 'fa fa-database', 'target' => '_self'),
            ),
        );

        // hook admin_admin_control_init_nav_tools_after.php

        // hook admin_admin_control_init_nav_after.php

        if( isset($this->_group['purviews']) && !empty($this->_group['purviews']) ){
            // hook admin_admin_control_init_nav_purviews_before.php

            $menu['menuInfo'] = _json_decode($this->_group['purviews']);

            // hook admin_admin_control_init_nav_purviews_after.php
        }

        $menu['menuInfo'] = array_values($menu['menuInfo']);//重置数组的键为 0 1 2....

        //写入缓存表
        $life = 24*60*60;   //缓存一天
        $this->runtime->set($cache_key, $menu, $life);

        // hook admin_admin_control_init_navigation_after.php

        echo json_encode($menu);
        exit();
    }

    // hook admin_admin_control_after.php
}
