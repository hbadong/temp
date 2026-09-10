<?php
/**
 * Author: dadadezhou <zhoudada97@foxmail.com>
 * Date: 2022-09-24
 * Time: 9:05
 * Description:前台模型页控制器
 */
defined('ROOT_PATH') or exit;
// hook model_control_start.php
class model_control extends base_control{

    //模型内容列表
    public function index(){
        // hook model_control_index_before.php

        $_GET['mid'] = (int)R('mid');
        if( !isset($this->_cfg['table_arr'][$_GET['mid']]) ){
            core::error404();
        }

        //模型信息
        $this->_var = $this->models->get($_GET['mid']);
        empty($this->_var) && core::error404();

        $this->_var['topcid'] = -1;
        $table = $this->_var['tablename'];
        $this->_var['url'] = $this->urls->model_url($this->_var['tablename'], $this->_var['mid']);
        
        // hook model_control_index_center.php

        // SEO 相关
        $this->_cfg['titles'] = $this->_var['name'].'-'.$this->_cfg['webname'];
        $this->_cfg['seo_keywords'] = $this->_var['name'].','.$this->_cfg['webname'];
        $this->_cfg['seo_description'] =  $this->_var['name'];

        $page = (int)R('page','G');
        if( $page > 1 ){
            $this->_cfg['titles']  .= '-'.lang('page_current', array('page'=>$page));
        }
        // hook model_control_index_seo_after.php

        $this->assign('cfg', $this->_cfg);
        $this->assign('cfg_var', $this->_var);

        $GLOBALS['run'] = &$this;
        $_ENV['_theme'] = &$this->_cfg['theme'];
        $tpl = $table.'_model.htm';

        // hook model_control_index_after.php
        $this->display($tpl);
    }

    // hook model_control_after.php
}