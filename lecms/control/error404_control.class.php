<?php
/**
 * Author: dadadezhou <zhoudada97@foxmail.com>
 * Date: 2022-09-24
 * Time: 9:05
 * Description:前台404页面控制器，该页面不做其他任何判断
 */
defined('ROOT_PATH') or exit;

class error404_control extends base_control{

    //404页面
	public function index() {
		// hook error404_control_index_before.php

		header('HTTP/1.1 404 Not Found');
		header("status: 404 Not Found");

		$this->_cfg['titles'] = '404 Not Found';
		$this->_var['topcid'] = -1;

        // hook error404_control_index_seo_after.php

		$this->assign('cfg', $this->_cfg);
		$this->assign('cfg_var', $this->_var);

		$GLOBALS['run'] = &$this;
        $tpl = '404.htm';

		// hook error404_control_index_after.php

		$_ENV['_theme'] = &$this->_cfg['theme'];
		$this->display($tpl);
	}

    // hook error404_control_after.php
}
