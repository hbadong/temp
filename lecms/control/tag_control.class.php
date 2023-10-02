<?php
/**
 * Author: dadadezhou <zhoudada97@foxmail.com>
 * Date: 2022-09-24
 * Time: 9:05
 * Description:前台标签控制器
 */
defined('ROOT_PATH') or exit;

class tag_control extends base_control{

    //标签列表页
	public function index() {
		// hook tag_control_index_before.php

		$mid = (int)R('mid', 'G');
		//动态链接文章标签URL默认省略了mid
		if( empty($this->_parseurl) && !isset($_GET['mid'])){
		    $mid = 2;
        }
		$table = isset($this->_cfg['table_arr'][$mid]) ? $this->_cfg['table_arr'][$mid] : '';
        ($table == '' || $table == 'page') AND core::error404();
        $this->cms_content_tag->table = 'cms_'.$table.'_tag';

        $tagid = R('tagid', 'G');
        $encrypt = R('encrypt', 'G');
        $name = R('name', 'G');
        if($name){
            $name = str_replace('-', ' ', urldecode($name));//横线转空格
            $name = safe_str($name); // 牺牲一点性能
        }

        $tags = array();
        if($tagid){
            $tags = $this->cms_content_tag->get($tagid);
        }elseif($name){
            $tags = $this->cms_content_tag->find_fetch(array('name'=>$name), array(), 0, 1);
            !empty($tags) && $tags = current($tags);
        }elseif ($encrypt){
            $mid_tagid = decrypt($encrypt);//解密得到 mid_tagid
            preg_match('#(\d+)\_(\d+)#', $mid_tagid, $mat);
            if(isset($mat[1]) && isset($mat[2]) && $mat[1] == $mid){
                $tagid = (int)$mat[2];
                $tags = $this->cms_content_tag->get($tagid);
            }
        }
        // hook tag_control_index_tags_after.php
        empty($tags) && core::error404();

        $this->cms_content_tag->format($tags, $mid);

        $this->_var = $tags;
		$this->_var['topcid'] = -1;

        // SEO 相关
        if(!empty($this->_var['seo_title'])){
            $this->_cfg['titles'] = $this->_var['seo_title'].'-'.$this->_cfg['webname'];
        }else{
            $this->_cfg['titles'] = $this->_var['name'].'-'.$this->_cfg['webname'];
        }

        $page = (int)R('page','G');
        if( $page > 1){
            $this->_cfg['titles']  .= '-'.lang('page_current', array('page'=>$page));
        }
        if(!empty($this->_var['seo_keywords'])){
            $this->_cfg['seo_keywords'] = $this->_var['seo_keywords'].','.$this->_cfg['webname'];
        }else{
            $this->_cfg['seo_keywords'] = $this->_var['name'].','.$this->_cfg['webname'];
        }

        if(!empty($this->_var['seo_description'])){
            $this->_cfg['seo_description'] = $this->_cfg['webname'].'：'.$this->_var['seo_description'];
        }else{
            if($this->_var['content']){
                $this->_cfg['seo_description'] = $this->_cfg['webname'].'-'.$this->_var['name'].'：'.auto_intro('', $this->_var['content'], 200);
            }else{
                $this->_cfg['seo_description'] = $this->_cfg['webname'].'-'.$this->_var['name'];
            }
        }
        // hook tag_control_index_seo_after.php

        $this->assign('tags', $tags);
		$this->assign('cfg', $this->_cfg);
		$this->assign('cfg_var', $this->_var);

		$GLOBALS['run'] = &$this;
		$GLOBALS['tags'] = &$tags;
		$GLOBALS['mid'] = &$mid;
		$GLOBALS['table'] = &$table;
        $tpl = 'tag_list.htm';

		// hook tag_control_index_after.php

		$_ENV['_theme'] = &$this->_cfg['theme'];
		$this->display($tpl);
	}

	// 热门标签
	public function top() {
		// hook tag_control_top_before.php

		$this->_cfg['titles'] = lang('hot_tag');
		$this->_var['topcid'] = -1;

        // hook tag_control_top_seo_after.php

		$this->assign('cfg', $this->_cfg);
		$this->assign('cfg_var', $this->_var);

		$GLOBALS['run'] = &$this;
        $tpl = 'tag_top.htm';

		// hook tag_control_top_after.php

		$_ENV['_theme'] = &$this->_cfg['theme'];
		$this->display($tpl);
	}

	//全部标签
    public function all() {
        // hook tag_control_all_before.php

        $this->_cfg['titles'] = lang('all_tag');
        $this->_var['topcid'] = -1;

        $mid = max(2, (int)R('mid'));
        $page = (int)R('page','G');
        if( $page > 1 ){
            $this->_cfg['titles']  .= '-'.lang('page_current', array('page'=>$page));
        }
        // hook tag_control_all_seo_after.php

        $this->assign('cfg', $this->_cfg);
        $this->assign('cfg_var', $this->_var);

        $GLOBALS['run'] = &$this;
        $tpl = 'tag_all.htm';

        // hook tag_control_all_after.php

        $_ENV['_theme'] = &$this->_cfg['theme'];
        $this->display($tpl);
    }

    // hook tag_control_after.php
}
