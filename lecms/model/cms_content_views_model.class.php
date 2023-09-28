<?php
defined('ROOT_PATH') or exit;

class cms_content_views extends model {
	function __construct() {
		$this->table = '';			// 内容浏览量表表名 比如 cms_article_views
		$this->pri = array('id');	// 主键
		$this->maxid = 'id';		// 自增字段
	}

	//根据ID数组获取浏览量
    public function get_views_by_ids($id_arr = array(), $table = 'article'){
        $this->table = 'cms_'.$table.'_views';
        return $this->mget($id_arr);
    }

    // hook cms_content_views_model_after.php
}
