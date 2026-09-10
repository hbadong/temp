<?php
/**
 * Author: dadadezhou <zhoudada97@foxmail.com>
 * Date: 2022-09-24
 * Time: 9:05
 * Description:前台内容页控制器
 */
defined('ROOT_PATH') or exit;
// hook show_control_start.php
class show_control extends base_control{

    //内容详情
    public function index(){
        // hook show_control_index_before.php

        $_GET['id'] = (int)R('id');
        $_GET['cid'] = (int)R('cid');
		$_GET['mid'] = (int)R('mid');
        $page = max(1, (int)R('page','G') );
		
		// hook show_control_index_param_after.php

		$_show = array();
        if(empty($_GET['cid']) && !empty($_GET['id']) && !empty($_GET['mid'])){
            $mid = max(1, (int)$_GET['mid']);
            $table = isset($this->_cfg['table_arr'][$mid]) ? $this->_cfg['table_arr'][$mid] : '';
            if(empty($table) || $table == 'page'){core::error404();}

            // 初始模型表名
            $this->cms_content->table = 'cms_'.$table;
            $_show = $this->cms_content->get($_GET['id']);
            if( empty($_show) ) core::error404();

            $_GET['cid'] = $_show['cid'];
            $this->_var = $this->category->get_cache($_GET['cid']);
            (empty($this->_var) || $this->_var['mid'] == 1) && core::error404();
        }elseif($_GET['cid'] && $_GET['id']){
            $this->_var = $this->category->get_cache($_GET['cid']);
            (empty($this->_var) || $this->_var['mid'] == 1) && core::error404();

            // 初始模型表名
            $this->cms_content->table = 'cms_'.$this->_var['table'];

            // 读取内容
            $_show = $this->cms_content->get($_GET['id']);
            if(empty($_show['cid']) || $_show['cid'] != $_GET['cid']) core::error404();
        }
		
		// hook show_control_index_get_show_after.php
		
		if( empty($_show) || empty($this->_var) ){core::error404();}

        //灵活型URL 含有日期，对日期进行验证
        if($this->_cfg['link_show_type'] == 7 && (isset($_GET['date_ymd']) || isset($_GET['date_y']) || isset($_GET['date_m']) || isset($_GET['date_d']))){
            if(isset($_GET['date_ymd']) && $_GET['date_ymd'] != date('Ymd', $_show['dateline'])){
                core::error404();
            }elseif(isset($_GET['date_y']) && $_GET['date_y'] != date('Y', $_show['dateline'])){
                core::error404();
            }elseif(isset($_GET['date_m']) && $_GET['date_m'] != date('m', $_show['dateline'])){
                core::error404();
            }elseif(isset($_GET['date_d']) && $_GET['date_d'] != date('d', $_show['dateline'])){
                core::error404();
            }
        }

        //格式化分类信息
        $this->category->format($this->_var);
		$_GET['mid'] = $this->_var['mid'];
		$_GET['cid'] = $this->_var['cid'];  //重置GET cid参数（可以适配内容URL各种无cid参数的情况，防止内容页无GET cid参数）

        //内容标签json转字符串
        if($_show['tags']){
            $tags_arr = _json_decode($_show['tags']);
            $tags = implode(',', $tags_arr);
        }else{
            $tags = '';
        }

        // hook show_control_index_center.php

        // SEO 相关（优先内容本身SEO信息，其次是分类SEO规则，然后是后台设置-SEO设置-内容页SEO规则设置，最后是程序本身）
        $seo_find_variable = array('{webname}', '{title}', '{seo_title}', '{seo_keywords}', '{seo_description}', '{intro}', '{cate_name}', '{cate_seo_title}', '{cate_seo_keywords}', '{tags}', '{page}');
        $seo_replace_variable = array($this->_cfg['webname'], $_show['title'], $_show['seo_title'], $_show['seo_keywords'], $_show['seo_description'], $_show['intro'], $this->_var['name'], $this->_var['seo_title'], $this->_var['seo_keywords'], $tags, lang('page_current', array('page'=>$page)));

        // hook show_control_index_seo_before.php
        if($_show['seo_title']){
            $this->_cfg['titles'] = $_show['seo_title'];
        }else{
            if(isset($this->_var['seo_title_rule']) && !empty($this->_var['seo_title_rule'])){
                $this->_cfg['titles'] = str_replace($seo_find_variable, $seo_replace_variable, $this->_var['seo_title_rule']);
            }elseif(isset($this->_cfg['show_seo_title_rule']) && !empty($this->_cfg['show_seo_title_rule'])){
                $this->_cfg['titles'] = str_replace($seo_find_variable, $seo_replace_variable, $this->_cfg['show_seo_title_rule']);
            }else{
                $this->_cfg['titles'] = $_show['title'].'-'.$this->_cfg['webname'];
            }
        }

        if($_show['seo_keywords']){
            $this->_cfg['seo_keywords'] = $_show['seo_keywords'];
        }else{
            if(isset($this->_var['seo_keywords_rule']) && !empty($this->_var['seo_keywords_rule'])){
                $this->_cfg['seo_keywords'] = str_replace($seo_find_variable, $seo_replace_variable, $this->_var['seo_keywords_rule']);
            }elseif(isset($this->_cfg['show_seo_keywords_rule']) && !empty($this->_cfg['show_seo_keywords_rule'])){
                $this->_cfg['seo_keywords'] = str_replace($seo_find_variable, $seo_replace_variable, $this->_cfg['show_seo_keywords_rule']);
            }else{
                $this->_cfg['seo_keywords'] = $_show['title'].','.$this->_cfg['webname'];
            }
        }

        if($_show['seo_description']){
            $this->_cfg['seo_description'] = $_show['seo_description'];
        }else{
            if(isset($this->_var['seo_description_rule']) && !empty($this->_var['seo_description_rule'])){
                $this->_cfg['seo_description'] = str_replace($seo_find_variable, $seo_replace_variable, $this->_var['seo_description_rule']);
            }elseif(isset($this->_cfg['show_seo_description_rule']) && !empty($this->_cfg['show_seo_description_rule'])){
                $this->_cfg['seo_description'] = str_replace($seo_find_variable, $seo_replace_variable, $this->_cfg['show_seo_description_rule']);
            }else{
                $this->_cfg['seo_description'] = $this->_cfg['webname'].'：'.$_show['intro'];
            }
        }
        // hook show_control_index_seo_after.php

        $this->assign('cfg', $this->_cfg);
        $this->assign('cfg_var', $this->_var);

        $GLOBALS['run'] = &$this;
        $GLOBALS['_show'] = &$_show;
        $_ENV['_theme'] = &$this->_cfg['theme'];
        $tpl = $this->_var['show_tpl'];
        //内容单独指定的模板
        if(isset($_show['show_tpl']) && $_show['show_tpl']){
            $tpl = $_show['show_tpl'];
        }

        // hook show_control_index_after.php
        $this->display($tpl);
    }

    // hook show_control_after.php
}