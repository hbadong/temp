<?php
defined('ROOT_PATH') or exit;

class cms_page extends model {
	function __construct() {
		$this->table = 'cms_page';	// 表名 单页模型
		$this->pri = array('cid');	// 主键
	}

    // hook cms_page_model_after.php
}
