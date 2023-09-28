<?php
/**
 * Author: dadadezhou <zhoudada97@foxmail.com>
 * Date: 2022-09-24
 * Time: 9:05
 * Description:前台分类控制器
 */
defined('ROOT_PATH') or exit;

class cate_control extends base_control{

    //分类列表
    public function index(){
        // hook cate_control_index_before.php

        $_GET['cid'] = (int)R('cid');
        $this->_var = $this->category->get_cache($_GET['cid']);
        empty($this->_var) && core::error404();

        $this->category->format($this->_var);
        
        // hook cate_control_index_center.php

        // SEO 相关
        $this->_cfg['titles'] = (empty($this->_var['seo_title']) ? $this->_var['name'] : $this->_var['seo_title']).'-'.$this->_cfg['webname'];
        $this->_cfg['seo_keywords'] = (empty($this->_var['seo_keywords']) ? $this->_var['name'] : $this->_var['seo_keywords']).','.$this->_cfg['webname'];
        !empty($this->_var['seo_description']) && $this->_cfg['seo_description'] =  $this->_var['seo_description'];

        $page = (int)R('page','G');
        if( $page > 1 ){
            $this->_cfg['titles']  .= '-'.lang('page_current', array('page'=>$page));
        }
        // hook cate_control_index_seo_after.php

        $this->assign('cfg', $this->_cfg);
        $this->assign('cfg_var', $this->_var);

        $GLOBALS['run'] = &$this;
        $tpl = $this->_var['cate_tpl'];

        // hook cate_control_index_after.php
        $_ENV['_theme'] = &$this->_cfg['theme'];
        $this->display($tpl);
    }

    // hook cate_control_after.php
}