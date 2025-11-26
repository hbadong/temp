<?php
defined('ROOT_PATH') or exit;

class cms_content_views extends model {
    protected $data = array();

	function __construct() {
		$this->table = '';			// 内容浏览量表表名 比如 cms_article_views
		$this->pri = array('id');	// 主键
		$this->maxid = 'id';		// 自增字段
	}

    public function get_instance($table = 'article'){
        // hook cms_content_views_model_get_instance_before.php
        $table = 'cms_'.$table.'_views';
        // hook cms_content_views_model_get_instance_after.php
        $this->table = $table;
    }

    //根据ID获取内容浏览量
    public function get_cms_content_views($id = 0, $table = 'article'){
        $this->get_instance($table);
        $data = array();
        // hook cms_content_views_model_get_cms_content_views_before.php
        $data = $this->get($id);
        // hook cms_content_views_model_get_cms_content_views_after.php
        return $data;
    }

    //根据ID创建内容浏览量数据(如果ID存在则更新,否则创建)
    public function set_cms_content_views($id = 0, $data = array(), $table = 'article'){
        $this->get_instance($table);
        // hook cms_content_views_model_set_cms_content_views_before.php
        $r = $this->set($id, $data);
        // hook cms_content_views_model_set_cms_content_views_after.php
        return $r;
    }
	
	//根据ID删除内容浏览量数据
    public function delete_cms_content_views($id = 0, $table = 'article'){
        $this->get_instance($table);
        // hook cms_content_views_model_delete_cms_content_views_before.php
        $r = $this->delete($id);
        // hook cms_content_views_model_delete_cms_content_views_after.php
        return $r;
    }

    //根据ID数组获取多条数据
	public function mgets($id_arr = array(), $table = 'article'){
		$this->get_instance($table);
        // hook cms_content_views_model_mgets_before.php
        $data = $this->mget($id_arr);
        // hook cms_content_views_model_mgets_after.php
        return $data;
	}

	//根据ID数组获取浏览量（废弃，使用mgets方法）
    public function get_views_by_ids($id_arr = array(), $table = 'article'){
        $this->get_instance($table);
        // hook cms_content_views_model_get_views_by_ids_after.php
        return $this->mget($id_arr);
    }

    // hook cms_content_views_model_after.php
}
