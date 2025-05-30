<?php
/**
 * Author: dadadezhou <zhoudada97@foxmail.com>
 * Date: 2022-09-24
 * Time: 9:05
 * Description:前台标签控制器
 */
defined('ROOT_PATH') or exit;
// hook tag_control_start.php
class tag_control extends base_control{

    //标签列表页
	public function index() {
		// hook tag_control_index_before.php

		$mid = (int)R('mid', 'G');
		//动态链接文章标签URL默认省略了mid（加密和hashid除外）
		if( empty($this->_parseurl) && !isset($_GET['mid'])){
            $_GET['mid'] = $mid = 2;
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
            $tags = $this->cms_content_tag->get_tag_by_tagname($name);
        }elseif ($encrypt){
            if($this->_cfg['link_tag_type'] == 2){
                $mid_tagid = decrypt($encrypt);//解密得到 mid_tagid
                preg_match('#(\d+)\_(\d+)#', $mid_tagid, $mat);
                if(isset($mat[2])){
                    $_GET['mid'] = (int)$mat[1];
                    $_GET['tagid'] = (int)$mat[2];
                    $tags = $this->cms_content_tag->get($_GET['tagid']);
                }
            }elseif($this->_cfg['link_tag_type'] == 3){
                $mid_tagid_arr = hashids_decrypt($encrypt);
                if(is_array($mid_tagid_arr) && isset($mid_tagid_arr[1])){
                    $_GET['mid'] = (int)$mid_tagid_arr[0];
                    $_GET['tagid'] = (int)$mid_tagid_arr[1];
                    $tags = $this->cms_content_tag->get($_GET['tagid']);
                }
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
        $_ENV['_theme'] = &$this->_cfg['theme'];
        $tpl = 'tag_list.htm';

        /**
         * 针对自定义模型（文章模型除外）
         * 不同模型 可以对应自己的标签列表页模板(优先表名 然后模型ID， 因为表名是固定的， mid是变化的)
         * 比如有一个产品模型， table=product mid=3
         * tag_list_product.htm   tag_list_3.htm
         */
        if($mid > 2){
            $tpl_arr = array("tag_list_{$table}.htm", "tag_list_{$mid}.htm");
            foreach ($tpl_arr as $t){
                if(view_tpl_exists($t)){
                    $tpl = $t;
                    break;
                }
            }
        }

		// hook tag_control_index_after.php
		$this->display($tpl);
	}

	// 热门标签
	public function top() {
		// hook tag_control_top_before.php

		$this->_cfg['titles'] = lang('hot_tag');
		$this->_var['topcid'] = -1;
        $_GET['mid'] = $mid = max(2, (int)R('mid'));

        // hook tag_control_top_seo_after.php

		$this->assign('cfg', $this->_cfg);
		$this->assign('cfg_var', $this->_var);

		$GLOBALS['run'] = &$this;
        $_ENV['_theme'] = &$this->_cfg['theme'];
        $tpl = 'tag_top.htm';

        //不同模型 可以对应自己的热门标签模板
        if($mid > 2 && view_tpl_exists("tag_top_{$mid}.htm")){
            $tpl = "tag_top_{$mid}.htm";
        }

		// hook tag_control_top_after.php
		$this->display($tpl);
	}

	//全部标签
    public function all() {
        // hook tag_control_all_before.php

        $this->_cfg['titles'] = lang('all_tag');
        $this->_var['topcid'] = -1;

        $_GET['mid'] = $mid = max(2, (int)R('mid'));
        $page = (int)R('page','G');
        if( $page > 1 ){
            $this->_cfg['titles']  .= '-'.lang('page_current', array('page'=>$page));
        }
        // hook tag_control_all_seo_after.php

        $this->assign('cfg', $this->_cfg);
        $this->assign('cfg_var', $this->_var);

        $GLOBALS['run'] = &$this;
        $_ENV['_theme'] = &$this->_cfg['theme'];
        $tpl = 'tag_all.htm';

        //不同模型 可以对应自己的全部标签模板
        if($mid > 2 && view_tpl_exists("tag_all_{$mid}.htm")){
            $tpl = "tag_all_{$mid}.htm";
        }

        // hook tag_control_all_after.php
        $this->display($tpl);
    }

    // hook tag_control_after.php
}
