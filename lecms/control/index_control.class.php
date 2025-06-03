<?php
/**
 * Author: dadadezhou <zhoudada97@foxmail.com>
 * Date: 2022-09-24
 * Time: 9:05
 * Description:前台首页控制器
 */
defined('ROOT_PATH') or exit;
// hook index_control_start.php
class index_control extends base_control{

    //首页
    public function index(){
        // hook index_control_index_before.php

        $this->_cfg['titles'] = empty($this->_cfg['seo_title']) ? $this->_cfg['webname'] : $this->_cfg['seo_title'];
        $this->_var['topcid'] = 0;

        $page = (int)R('page','G');
        if( $page > 1 ){
            $this->_cfg['titles']  .= '-'.lang('page_current', array('page'=>$page));
        }
        // hook index_control_index_seo_after.php

        $this->assign('cfg', $this->_cfg);
        $this->assign('cfg_var', $this->_var);

        $GLOBALS['run'] = &$this;
        $_ENV['_theme'] = &$this->_cfg['theme'];
        $tpl = 'index.htm';

        // hook index_control_index_after.php
        $this->display($tpl);
    }

    // hook index_control_after.php
}