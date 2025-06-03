<?php
/**
 * Author: dadadezhou <zhoudada97@foxmail.com>
 * Date: 2022-09-24
 * Time: 9:05
 * Description:前台404页面控制器，不能继承base控制器
 */
defined('ROOT_PATH') or exit;

class error404_control extends control{
    public $_cfg = array();	// 全站参数
    public $_var = array();	// 各个模块页参数

    public $_user = array(); // 用户信息
    public $_uid = 0; // 用户ID
    public $_group = array(); // 用户组

    public $_parseurl = 0;  //是否开启了URL伪静态

    public $_control = 'error404';  //当前访问的控制器
    public $_action = 'index';  //当前访问的方法函数

    //404页面
	public function index() {
        $this->_cfg = $this->runtime->xget();
		// hook error404_control_index_before.php

		header('HTTP/1.1 404 Not Found');
		header("status: 404 Not Found");

		$this->_cfg['titles'] = '404 Not Found';
		$this->_var['topcid'] = -1;

        if( !empty($_ENV['_config']['lecms_parseurl']) ){
            $this->_parseurl = 1;
        }

        // hook error404_control_index_seo_after.php

        $this->assign('_uid',$this->_uid);
        $this->assign('_user',$this->_user);
        $this->assign('_group',$this->_group);
        $this->assign('_parseurl', $this->_parseurl);
        $this->assign('_control', $this->_control);
        $this->assign('_action', $this->_action);

		$this->assign('cfg', $this->_cfg);
		$this->assign('cfg_var', $this->_var);

		$GLOBALS['run'] = &$this;
        $_ENV['_theme'] = &$this->_cfg['theme'];
        $tpl = '404.htm';

		// hook error404_control_index_after.php
		$this->display($tpl);
	}

    // hook error404_control_after.php
}
