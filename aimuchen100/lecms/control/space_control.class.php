<?php
/**
 * Author: dadadezhou <zhoudada97@foxmail.com>
 * Date: 2022-11-29
 * Time: 9:05
 * Description:前台用户主页控制器
 */
defined('ROOT_PATH') or exit;
// hook space_control_start.php
class space_control extends base_control{

    //用户主页详情
    public function index(){
        // hook show_control_index_before.php

        $_GET['uid'] = (int)R('uid');
        $this->_var = $this->user->get($_GET['uid']);
        empty($this->_var) && core::error404();

        $this->user->format($this->_var);
        empty($this->_var['author']) && $this->_var['author'] = $this->_var['username'];
        $this->_var['topcid'] = -1;
        $_show = $this->_var;

        // hook space_control_index_center.php

        // SEO 相关
        // hook space_control_index_seo_before.php
        $this->_cfg['titles'] = $this->_var['author'].'-'.$this->_cfg['webname'];
		$page = (int)R('page','G');
        if( $page > 1 ){
            $this->_cfg['titles']  .= '-'.lang('page_current', array('page'=>$page));
        }
        $this->_cfg['seo_keywords'] = $this->_var['author'].','.$this->_cfg['webname'];
        $this->_cfg['seo_description'] = empty($this->_var['intro']) ? $this->_cfg['webname'] : $this->_var['intro'];
        // hook space_control_index_seo_after.php

        $this->assign('cfg', $this->_cfg);
        $this->assign('cfg_var', $this->_var);

        $GLOBALS['run'] = &$this;
        $GLOBALS['_show'] = &$_show;
        $_ENV['_theme'] = &$this->_cfg['theme'];
        $tpl = 'space.htm';

        // hook space_control_index_after.php
        $this->display($tpl);
    }

    // hook space_control_after.php
}