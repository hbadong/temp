<?php
defined('ROOT_PATH') || exit;

/**
 * 单页模块
 * @return array
 */
function block_global_page($conf) {
	global $run;

	// hook block_global_page_before.php

    if($run->_var['mid'] != 1){
        return array();
    }
	$arr = $run->cms_page->get($run->_var['cid']);

	// hook block_global_page_after.php

	return $arr;
}
