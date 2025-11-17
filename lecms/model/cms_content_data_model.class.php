<?php
defined('ROOT_PATH') or exit;

class cms_content_data extends model {
    protected $data = array();

	function __construct() {
		$this->table = '';			// 内容数据表表名 比如 cms_article_content
		$this->pri = array('id');	// 主键
		$this->maxid = 'id';		// 自增字段
	}

    //根据ID获取内容附表数据
    public function get_cms_content_data($id = 0, $table = 'article'){
        $this->table = 'cms_'.$table.'_content';
        $data = array();
        // hook cms_content_data_model_get_cms_content_data_before.php
        $data = $this->get($id);
        // hook cms_content_data_model_get_cms_content_data_after.php
        return $data;
    }

	/*内容字段分页*/
	public function format_content($data = array(), $curpage = 1){
        // hook cms_content_data_model_format_content_before.php

        if (isset($data['content']) && strpos($data['content'], '<hr class="ui_editor_pagebreak"/>') !== FALSE) {
            $page = 1;
            $match = explode('<hr class="ui_editor_pagebreak"/>', $data['content']);
            $content_fmt = array();
            foreach ($match as $i => $t) {
                $content_fmt[$page] = $t;
                $page ++;
            }

            //最大页数
            $data['maxpage'] = count($match);
            if($page > $data['maxpage']){
                $page = $data['maxpage'];
            }

            $page = max(1, min($page, $curpage));
            $data['content'] = $content_fmt[$page]; // 当前页的内容
            $data['content_page'] = $content_fmt; // 全部分页内容

            return $data;
        }else{
            return $data;
        }
    }

    // hook cms_content_data_model_after.php
}
