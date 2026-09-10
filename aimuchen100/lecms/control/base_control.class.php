<?php
/**
 * Author: dadadezhou <zhoudada97@foxmail.com>
 * Date: 2022-09-24
 * Time: 9:05
 * Description:前台共用控制器（禁止直接访问~~~）
 */
defined('ROOT_PATH') or exit;
// hook base_control_start.php
class base_control extends control{
    public $_cfg = array();	// 全站参数
    public $_var = array();	// 各个模块页参数

    public $_user = array(); // 用户信息
    public $_uid = 0; // 用户ID
    public $_group = array(); // 用户组
    public $_user_avatar = '';  //用户头像
    public $_author = '';   //用户昵称

    public $_login_url = '';    //登录链接
    public $_register_url = ''; //注册链接
    public $_my_url = '';   //个人中心链接
    public $_logout_url = '';    //退出登录链接
    public $_search_url = '';    //搜索页面链接（不是搜索结果页）
    public $_current_url = '';  //当前页面URL

    public $_parseurl = 0;  //是否开启了URL伪静态

    public $_control = 0;  //当前访问的控制器
    public $_action = 0;  //当前访问的方法函数

    // hook base_control_variable_after.php

    function __construct(){
        $this->_cfg = $this->runtime->xget();
        // hook base_control_construct_before.php

        $form_hash = '';
        $this->_author = (isset($this->_cfg['comment_default_author']) && !empty($this->_cfg['comment_default_author'])) ? $this->_cfg['comment_default_author'] : lang('visitor');

        //开启了用户功能，获取登录用户信息
        if( isset($this->_cfg['open_user']) && !empty($this->_cfg['open_user']) ){
            $form_hash = form_hash();

            // hook base_control_get_user_before.php
            $r = $this->user->user_token_check(0);
            if($r['err'] == 0 && isset($r['user']) && isset($r['user_group'])){
                $this->_uid = $r['user']['uid'];
                $this->_user = $r['user'];
                $this->user->format($this->_user);
                $this->_group = $r['user_group'];

                if( !empty($this->_user['author']) ){
                    $this->_author = $this->_user['author'];
                }else{
                    $this->_author = $this->_user['username'];
                }
                // hook base_control_get_user_success.php
            }else{
                // hook base_control_get_user_failed.php
            }

            $this->_cfg['open_user_login'] && $this->_login_url = $this->urls->user_url('login', 'user');
            $this->_cfg['open_user_register'] && $this->_register_url = $this->urls->user_url('register', 'user');
            $this->_my_url = $this->urls->user_url('index', 'my');
            $this->_logout_url = $this->urls->user_url('logout', 'my');

            // hook base_control_get_user_after.php
        }

        if( isset($this->_user['avatar']) ){
            $this->_user_avatar = $this->_user['avatar'];
        }else{
            $this->_user_avatar = $this->_cfg['webdir'].'static/img/avatar.png';
        }

        $this->_search_url = $this->urls->so_url();
        $this->_current_url = $this->_current_url();

        if( !empty($_ENV['_config']['lecms_parseurl']) ){
            $this->_parseurl = 1;
        }

        $this->_control = isset($_GET['control']) ? strtolower($_GET['control']) : '';
        $this->_action = isset($_GET['action']) ? strtolower($_GET['action']) : '';

        $GLOBALS['_user'] = &$this->_user;
        $this->assign('author',$this->_author); //兼容旧的版本
        $this->assign('_author',$this->_author);
        $this->assign('_uid',$this->_uid);
        $this->assign('_user',$this->_user);
        $this->assign('_group',$this->_group);
        $this->assign('_user_avatar',$this->_user_avatar);
        $this->assign('form_hash', $form_hash);

        $this->assign('login_url', $this->_login_url);
        $this->assign('register_url', $this->_register_url);
        $this->assign('my_url', $this->_my_url);
        $this->assign('logout_url', $this->_logout_url);

        $this->assign('search_url', $this->_search_url);
        $this->assign('current_url', $this->_current_url);

        $this->assign('_parseurl', $this->_parseurl);
        $this->assign('_control', $this->_control);
        $this->assign('_action', $this->_action);
		
		$this->assign_value('url_suffix', $_ENV['_config']['url_suffix']);

		//主题静态资源前缀（view/{theme}/ 目录的 web 路径），供模板 {$theme_path}/css/theme.css 引用；
		//部分新主题还使用 {$base_url} 作站内链接前缀，一并兜底，避免未定义时输出空串导致 404
		if(isset($this->_cfg['webdir']) && isset($this->_cfg['theme'])) {
			$this->assign_value('theme_path', $this->_cfg['webdir'].'view/'.$this->_cfg['theme']);
			$this->assign_value('base_url', rtrim($this->_cfg['webdir'], '/'));
		}

        //站点关闭
        $this->close_website();

        // hook base_control_construct_after.php
    }

    //站点关闭
    protected function close_website(){
        // hook base_control_close_website_before.php
        if(isset($this->_cfg['close_website']) && !empty($this->_cfg['close_website'])){
            $this->_cfg['titles'] = empty($this->_cfg['seo_title']) ? $this->_cfg['webname'] : $this->_cfg['seo_title'];
            $this->_var['topcid'] = 0;

            $this->assign('cfg', $this->_cfg);
            $this->assign('cfg_var', $this->_var);

            $GLOBALS['run'] = &$this;
            $tpl = 'close_website.htm';

            // hook base_control_construct_close_website_after.php

            if( !file_exists(VIEW_PATH.$_ENV['_theme'].'/'.$tpl) ){
                exit(lang('close_website_tips'));
            }

            $_ENV['_theme'] = &$this->_cfg['theme'];
            $this->display($tpl);
            exit();
        }
    }

    //获取当前页面URL
    protected function _current_url(){
        return HTTP.R('HTTP_HOST', 'S').R('REQUEST_URI', 'S');
    }

    // hook base_control_after.php
}