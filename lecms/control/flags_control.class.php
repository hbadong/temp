<?php
/**
 * Author: dadadezhou <zhoudada97@foxmail.com>
 * Date: 2022-09-24
 * Time: 9:05
 * Description:前台属性内容控制器
 */
defined('ROOT_PATH') or exit;

class flags_control extends base_control{

    //属性内容列表
    public function index(){
        // hook flags_control_index_before.php
        $flag = (int)R('flag');
        $mid = (int)R('mid');

        $table = isset($this->_cfg['table_arr'][$mid]) ? $this->_cfg['table_arr'][$mid] : '';
        if( empty($table) || $table == 'page' ) core::error404();

        if( !isset($this->cms_content->flag_arr[$flag]) ){
            core::error404();
        }
        $flag_name = $this->cms_content->flag_arr[$flag];

        // hook flags_control_index_center.php

        // SEO 相关
        $this->_cfg['titles'] = $flag_name.'-'.$this->_cfg['webname'];
        $this->_var['topcid'] = -1;

        $page = (int)R('page','G');
        if( $page > 1 ){
            $this->_cfg['titles']  .= '-'.lang('page_current', array('page'=>$page));
        }
        // hook flags_control_index_seo_after.php

        $this->assign('cfg', $this->_cfg);
        $this->assign('cfg_var', $this->_var);
        $this->assign('flag_name', $flag_name);

        $GLOBALS['run'] = &$this;
        $GLOBALS['mid'] = &$mid;
        $GLOBALS['flag'] = &$flag;
        $tpl = 'flags.htm';

        //不同模型 可以对应自己的属性内容页模板
        if($mid > 2 && view_tpl_exists("flags_{$mid}.htm")){
            $tpl = "flags_{$mid}.htm";
        }

        // hook flags_control_index_after.php
        $_ENV['_theme'] = &$this->_cfg['theme'];
        $this->display($tpl);
    }

    // hook flags_control_after.php
}