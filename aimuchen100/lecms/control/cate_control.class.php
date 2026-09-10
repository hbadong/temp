<?php
/**
 * Author: dadadezhou <zhoudada97@foxmail.com>
 * Date: 2022-09-24
 * Time: 9:05
 * Description:前台分类控制器
 */
defined('ROOT_PATH') or exit;
// hook cate_control_start.php
class cate_control extends base_control{

    //分类列表
    public function index(){
        // hook cate_control_index_before.php

        $_GET['cid'] = (int)R('cid');
        $this->_var = $this->category->get_cache($_GET['cid']);
        empty($this->_var) && core::error404();

        $this->category->format($this->_var);

        $_GET['mid'] = $this->_var['mid'];
        $_GET['type'] = $this->_var['type'];
        
        // hook cate_control_index_center.php

        // SEO 相关
        if($this->_var['seo_title']){
            $this->_cfg['titles'] = $this->_var['seo_title'];
        }else{
            $this->_cfg['titles'] = $this->_var['name'].'-'.$this->_cfg['webname'];
        }
        if($this->_var['seo_keywords']){
            $this->_cfg['seo_keywords'] = $this->_var['seo_keywords'];
        }else{
            $this->_cfg['seo_keywords'] = $this->_var['name'].','.$this->_cfg['webname'];
        }
        if($this->_var['seo_description']){
            $this->_cfg['seo_description'] = $this->_var['seo_description'];
        }elseif($this->_var['intro']){
            $this->_cfg['seo_description'] = $this->_var['intro'];
        }else{
            $this->_cfg['seo_description'] = $this->_cfg['webname'].'：'.$this->_var['name'];
        }

        $page = (int)R('page','G');
        if( $page > 1 ){
            $this->_cfg['titles']  .= '-'.lang('page_current', array('page'=>$page));
        }
        // hook cate_control_index_seo_after.php

        $this->assign('cfg', $this->_cfg);
        $this->assign('cfg_var', $this->_var);

        $GLOBALS['run'] = &$this;
        $_ENV['_theme'] = &$this->_cfg['theme'];
        $tpl = $this->_var['cate_tpl'];

        // hook cate_control_index_after.php
        $this->display($tpl);
    }

    // hook cate_control_after.php
}