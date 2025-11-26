<?php
defined('ROOT_PATH') or exit;

class cms_content_data extends model {
    protected $data = array();

	function __construct() {
		$this->table = '';			// 内容数据表表名 比如 cms_article_data
		$this->pri = array('id');	// 主键
		$this->maxid = 'id';		// 自增字段
	}

    public function get_instance($table = 'article'){
        // hook cms_content_data_model_get_instance_before.php
        $table = 'cms_'.$table.'_data';
        // hook cms_content_data_model_get_instance_after.php
        $this->table = $table;
    }

    //根据ID获取内容附表数据
    public function get_cms_content_data($id = 0, $table = 'article'){
        $this->get_instance($table);
        $data = array();
        // hook cms_content_data_model_get_cms_content_data_before.php
        $data = $this->get($id);
        // hook cms_content_data_model_get_cms_content_data_after.php
        return $data;
    }

    //根据ID更新内容附表数据
    public function update_cms_content_data($id = 0, $data = array(), $table = 'article'){
        $this->get_instance($table);
        // hook cms_content_data_model_update_cms_content_data_before.php
        $data['id'] = $id;
        $r = $this->update($data);
        // hook cms_content_data_model_update_cms_content_data_after.php
        return $r;
    }

    //根据ID创建内容附表数据(如果ID存在则更新,否则创建)
    public function set_cms_content_data($id = 0, $data = array(), $table = 'article'){
        $this->get_instance($table);
        // hook cms_content_data_model_set_cms_content_data_before.php
        $r = $this->set($id, $data);
        // hook cms_content_data_model_set_cms_content_data_after.php
        return $r;
    }

    //根据ID删除内容附表数据
    public function delete_cms_content_data($id = 0, $table = 'article'){
        $this->get_instance($table);
        // hook cms_content_data_model_delete_cms_content_data_before.php
        $r = $this->delete($id);
        // hook cms_content_data_model_delete_cms_content_data_after.php
        return $r;
    }

    //根据ID数组获取多条数据
	public function mgets($id_arr = array(), $table = 'article'){
		$this->get_instance($table);
        // hook cms_content_data_model_mgets_before.php
        $data = $this->mget($id_arr);
        // hook cms_content_data_model_mgets_after.php
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
