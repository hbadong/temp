<?php
/**
 * Author: dadadezhou <zhoudada97@foxmail.com>
 * Date: 2022-09-24
 * Time: 9:05
 * Description:前台搜索控制器
 */
defined('ROOT_PATH') or exit;
// hook search_control_start.php
class search_control extends base_control{

    //搜索结果页
	public function index() {
		// hook search_control_index_before.php

		$keyword = urldecode(R('keyword'));
		$keyword = safe_str($keyword);
		
		$this->_cfg['titles'] = $keyword;
		$this->_var['topcid'] = -1;

		//关闭搜索
		if(isset($this->_cfg['close_search']) && !empty($this->_cfg['close_search'])){
		    $this->message(0, lang('close_search_tips'));
        }

        $mid = max(2, (int)R('mid'));
        $table = isset($this->_cfg['table_arr'][$mid]) ? $this->_cfg['table_arr'][$mid] : '';
        if( empty($table) ) core::error404();

        $page = (int)R('page','G');
        if( $page > 1){
            $this->_cfg['titles']  .= '-'.lang('page_current', array('page'=>$page));
        }
		
		// hook search_control_index_center.php

		$this->assign('cfg', $this->_cfg);
		$this->assign('cfg_var', $this->_var);
		$this->assign('keyword', $keyword);

		$GLOBALS['run'] = &$this;
		$GLOBALS['keyword'] = &$keyword;
        $_ENV['_theme'] = &$this->_cfg['theme'];
        $tpl = 'search.htm';

        /**
         * 针对自定义模型（文章模型除外）
         * 不同模型 可以对应自己的搜索结果页模板(优先表名 然后模型ID， 因为表名是固定的， mid是变化的)
         * 比如有一个产品模型， table=product mid=3
         * search_product.htm   search_3.htm
         */

        if($mid > 2){
            $tpl_arr = array("search_{$table}.htm", "search_{$mid}.htm");
            foreach ($tpl_arr as $t){
                if(view_tpl_exists($t)){
                    $tpl = $t;
                    break;
                }
            }
        }

		// hook search_control_index_after.php
		$this->display($tpl);
	}

	//搜索页面
    public function so(){
        $this->_cfg['titles'] = lang('search');
        $this->_var['topcid'] = -1;

        // hook search_control_so_before.php

        //关闭搜索
        if(isset($this->_cfg['close_search']) && !empty($this->_cfg['close_search'])){
            $this->message(0, lang('close_search_tips'));
        }

        // hook search_control_so_center.php

        $this->assign('cfg', $this->_cfg);
        $this->assign('cfg_var', $this->_var);

        $GLOBALS['run'] = &$this;
        $_ENV['_theme'] = &$this->_cfg['theme'];
        $tpl = 'so.htm';

        // hook search_control_so_after.php
        $this->display($tpl);
    }

    // hook search_control_after.php
}
