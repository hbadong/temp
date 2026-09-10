<?php
defined('ROOT_PATH') or exit;

class only_alias extends model {

    public $_field_length = array(
        'alias'=>80,
        // hook only_alias_model_field_length_after.php
    );
    
	function __construct() {
		$this->table = 'only_alias';	// 表名
		$this->pri = array('alias');	// 主键
	}

	// 检查别名是否已被使用
	// 1.先排除 tag comment space 的别名
	// 2.再排除保留关键词 (tag tag_top comment index sitemap admin user space)
	// 3.再排除分类表的 alias 字段
	// 4.排除only_alias表的 alias 字段
	public function check_alias($alias, $contentalias = 0) {
        $alias_preg = "/^[0-9a-zA-Z-_]+$/i";
        // hook only_alias_model_check_alias_before.php

        if(!preg_match($alias_preg, $alias)){
            return lang('alias_error_2');
        }
		$cfg = $this->runtime->xget();
		$keywords = $this->kv->xget('link_keywords'); // 保留关键词

        //不能是模型表名
        if(isset($cfg['table_arr']) && !empty($cfg['table_arr'])){
            foreach ($cfg['table_arr'] as $table){
                if($alias == $table){
                    return lang('alias_error_9');
                }
            }
        }

		if(isset($cfg['link_tag_pre']) && $alias == $cfg['link_tag_pre']) {
			return lang('alias_error_3');
		}elseif(isset($cfg['link_comment_pre']) && $alias == $cfg['link_comment_pre']) {
			return lang('alias_error_4');
		}elseif(isset($cfg['link_space_pre']) && $alias == $cfg['link_space_pre']) {
            return lang('alias_error_5');
        }elseif(in_array($alias, $keywords)) {
			return lang('alias_error_6');
		}elseif($this->category->find_fetch_key(array('alias'=> $alias))) {
			return lang('alias_error_7');
		}elseif($this->find_fetch_key(array('alias'=> $alias))) {
			return lang('alias_error_8');
		}
        // hook only_alias_model_check_alias_after.php
		return '';
	}

    // hook only_alias_model_after.php
}
