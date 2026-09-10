<?php
defined('ROOT_PATH') or exit;

class models extends model {
	private $data = array();		// 防止重复查询

    public $_field_length = array(
        'name'=>30,
        'tablename'=>20,
        'index_tpl'=>80,
        'cate_tpl'=>80,
        'show_tpl'=>80,
        'icon'=>30,
        // hook models_model_field_length_after.php
    );

	function __construct() {
		$this->table = 'models';	// 表名
		$this->pri = array('mid');	// 主键
		$this->maxid = 'mid';		// 自增字段
	}

	// 获取所有模型
	public function get_models() {
		if(isset($this->data['models'])) {
			return $this->data['models'];
		}
		return $this->data['models'] = $this->find_fetch();
	}

	// 获取所有模型的名称
	public function get_name() {
		if(isset($this->data['name'])) {
			return $this->data['name'];
		}

		$models_arr = $this->get_models();
		$arr = array();
		foreach ($models_arr as $v) {
			$arr[$v['mid']] = $v['name'];
		}
		return $this->data['name'] = $arr;
	}

	// 获取所有模型的表名
	public function get_table_arr() {
		if(isset($this->data['table_arr'])) {
			return $this->data['table_arr'];
		}

		$models_arr = $this->get_models();
		//unset($models_arr[1]);
		$arr = array();
		foreach ($models_arr as $v) {
			$arr[$v['mid']] = $v['tablename'];
		}
		return $this->data['table_arr'] = $arr;
	}

	// 根据 mid 获取模型的表名
	public function get_table($mid) {
		$data = $this->get($mid);
		return isset($data['tablename']) ? $data['tablename'] : 'article';
	}

    //模型下拉框列表（不含单页）
    public function get_models_html($mid = 2, $s = ''){
        $models = $this->get_models();
        $select = '<select name="mid" id="mid" '.$s.'>';

        foreach ($models as $v){
            if($v['mid'] > 1){
                if( $_ENV['_config']['admin_lang'] == 'zh-cn' ){
                    $modelname = $v['name'];
                }else{
                    $modelname = ucfirst($v['tablename']);
                }
                $select .= '<option value="'.$v['mid'].'"'.($v['mid'] == $mid ? ' selected="selected"' : '').'>'.$modelname.'</option>';
            }
        }
        $select .= '</select>';
        // hook models_model_get_models_html_after.php
        return $select;
    }

    // 根据表名获取模型数据
    public function get_models_by_tablename($tablename) {
        // hook models_model_get_models_by_tablename_before.php
        $data = $this->find_fetch(array('tablename'=>$tablename), array(), 0, 1);
        $models = $data ? current($data) : array();
        // hook models_model_get_models_by_tablename_after.php
        return $models;
    }

	//添加模型
    public function xadd($data = array()){
        // hook models_model_xadd_before.php
	    //数据检查
	    foreach ($data as $k=>$v){
	        if(empty($v)){
	            return lang('all_no_empty');
            }
        }
	    //合法性检查
        if($data['index_tpl'] == $data['cate_tpl'] || $data['index_tpl'] == $data['show_tpl'] || $data['cate_tpl'] == $data['show_tpl'] ){
            return lang('tpl_tips');
        }

        $table = isset($data['tablename']) ? trim($data['tablename']) : '';
        if(empty($table)){
            return lang('modeltablename_no_empty');
        }
        if($this->get_models_by_tablename($table)){
            return lang('modeltablename_is_exist');
        }

        $mid = $this->create($data);
        if(!$mid){
            return lang('write_table_failed');
        }
        $model_name = $data['name'];
        // hook models_model_xadd_create_after.php

        //创建模型相关表
        $tableprefix = $_ENV['_config']['db']['master']['tablepre'];	//表前缀
        $table_cms = $tableprefix.'cms_'.$table;
        $table_cms_attach = $tableprefix.'cms_'.$table.'_attach';
        $table_cms_data = $tableprefix.'cms_'.$table.'_data';
        $table_cms_flag = $tableprefix.'cms_'.$table.'_flag';
        $table_cms_tag = $tableprefix.'cms_'.$table.'_tag';
        $table_cms_tag_data = $tableprefix.'cms_'.$table.'_tag_data';
        $table_cms_views = $tableprefix.'cms_'.$table.'_views';

        $sql_table = "CREATE TABLE IF NOT EXISTS {$table_cms} (
          id int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '内容ID',
          cid int(10) unsigned NOT NULL DEFAULT '0' COMMENT '分类ID',
          title varchar(200) NOT NULL DEFAULT '' COMMENT '标题',
          alias varchar(80) NOT NULL DEFAULT '' COMMENT 'URL别名',
          tags varchar(500) NOT NULL DEFAULT '' COMMENT '标签 (json数组)',
          intro varchar(255) NOT NULL DEFAULT '' COMMENT '内容介绍',
          pic varchar(255) NOT NULL DEFAULT '' COMMENT '图片地址',
          uid int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
          author varchar(50) NOT NULL DEFAULT '' COMMENT '作者(昵称)',
          source varchar(100) NOT NULL DEFAULT '' COMMENT '来源',
          dateline int(10) unsigned NOT NULL DEFAULT '0' COMMENT '发表时间',
          lasttime int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
          ip int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'IP',
          imagenum smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '图片附件数',
          filenum smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '文件附件数',
          iscomment tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '禁止评论',
          comments int(10) unsigned NOT NULL DEFAULT '0' COMMENT '评论数',
          flags varchar(20) NOT NULL DEFAULT '' COMMENT '所有属性 ,分割',
          seo_title varchar(255) NOT NULL DEFAULT '' COMMENT 'SEO标题',
          seo_keywords varchar(255) NOT NULL DEFAULT '' COMMENT 'SEO关键词',
          seo_description varchar(255) NOT NULL DEFAULT '' COMMENT 'SEO描述',
          jumpurl varchar(255) NOT NULL DEFAULT '' COMMENT '跳转URL',
          show_tpl varchar(80) NOT NULL DEFAULT '' COMMENT '内容页模板',
          PRIMARY KEY (id),
		  KEY uid (uid),
          KEY cid_id (cid,id),
          KEY cid_dateline (cid,dateline)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT '{$model_name}主表';";
        $this->db->query($sql_table);

        $sql_table = "CREATE TABLE IF NOT EXISTS {$table_cms_attach} (
          aid int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '附件ID',
          cid int(10) unsigned NOT NULL DEFAULT '0' COMMENT '分类ID',
          uid int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
          id int(10) unsigned NOT NULL DEFAULT '0' COMMENT '内容ID',
          filename varchar(200) NOT NULL DEFAULT '' COMMENT '文件原名',
          filetype char(10) NOT NULL DEFAULT '' COMMENT '文件后缀',
          filesize int(10) unsigned NOT NULL DEFAULT '0' COMMENT '文件大小',
          filepath varchar(200) NOT NULL DEFAULT '' COMMENT '文件路径',
          dateline int(10) unsigned NOT NULL DEFAULT '0' COMMENT '上传时间',
          downloads int(10) unsigned NOT NULL DEFAULT '0' COMMENT '下载次数',
          credits int(10) unsigned NOT NULL DEFAULT '0' COMMENT '下载需要积分',
          golds int(10) unsigned NOT NULL DEFAULT '0' COMMENT '下载需要金币',
          isimage tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否是图片',
          PRIMARY KEY (aid),
          KEY id (id, aid),
          KEY uid (uid, aid)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT '{$model_name}附件表'";
        $this->db->query($sql_table);

        $sql_table = "CREATE TABLE IF NOT EXISTS {$table_cms_data} (
          id int(10) unsigned NOT NULL DEFAULT '0' COMMENT '内容ID',
          content mediumtext NOT NULL COMMENT '内容',
          PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT '{$model_name}数据表';";
        $this->db->query($sql_table);

        $sql_table = "CREATE TABLE IF NOT EXISTS {$table_cms_flag} (
          flag tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '属性标记',
          cid int(10) unsigned NOT NULL DEFAULT '0' COMMENT '分类ID',
          id int(10) unsigned NOT NULL DEFAULT '0' COMMENT '内容ID',
          PRIMARY KEY (flag,id),
          KEY flag_cid (flag,cid,id),
          KEY id (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT '{$model_name}属性表'";
        $this->db->query($sql_table);

        $sql_table = "CREATE TABLE IF NOT EXISTS {$table_cms_tag} (
          tagid int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '标签ID',
          `name` varchar(80) NOT NULL DEFAULT '' COMMENT '标签名',
          `count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '标签内容数量',
          content varchar(255) NOT NULL DEFAULT '' COMMENT '标签说明',
          pic varchar(255) NOT NULL DEFAULT '' COMMENT '标签缩略图',
          seo_title varchar(255) NOT NULL DEFAULT '' COMMENT 'SEO标题',
          seo_keywords varchar(255) NOT NULL DEFAULT '' COMMENT 'SEO关键词',
          seo_description varchar(255) NOT NULL DEFAULT '' COMMENT 'SEO描述',
          orderby int(10) unsigned NOT NULL DEFAULT '0' COMMENT '排序值',
          PRIMARY KEY (tagid),
          UNIQUE KEY tagname (`name`),
          KEY content_count (`count`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT '{$model_name}标签表'";
        $this->db->query($sql_table);

        $sql_table = "CREATE TABLE IF NOT EXISTS {$table_cms_tag_data} (
          tagid int(10) unsigned NOT NULL COMMENT '标签ID',
          id int(10) unsigned NOT NULL DEFAULT '0' COMMENT '内容ID',
          PRIMARY KEY (tagid,id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT '{$model_name}标签数据表'";
        $this->db->query($sql_table);

        $sql_table = "CREATE TABLE IF NOT EXISTS {$table_cms_views} (
          id int(10) unsigned NOT NULL DEFAULT '0' COMMENT '内容ID',
          cid int(10) unsigned NOT NULL DEFAULT '0' COMMENT '分类ID',
          views int(10) unsigned NOT NULL DEFAULT '0' COMMENT '查看次数',
          PRIMARY KEY (id),
          KEY cid (cid,views),
          KEY views (views)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT '{$model_name}浏览量表'";
        $this->db->query($sql_table);

        // hook models_model_xadd_after.php

        return '';
    }

    //删除或清空模型
    public function xdelete($mid = 0, $del_table = 1){
        // hook models_model_xdelete_before.php
        // 内容读取
        $data = $this->get($mid);
        if(empty($data)) return lang('data_no_exists');
        if( $data['system'] && $del_table ) return lang('system_model_no_delete');

        if($del_table){
            $ret = $this->delete($mid);
        }else{
            $ret = true;
        }

        if($ret){
            // hook models_model_xdelete_success.php

            //单页
            if($mid < 2){
                return '';
            }

            $table = $data['tablename'];
            $tableprefix = $_ENV['_config']['db']['master']['tablepre'];	//表前缀
            $table_cms = $tableprefix.'cms_'.$table;
            $table_cms_attach = $tableprefix.'cms_'.$table.'_attach';
            $table_cms_data = $tableprefix.'cms_'.$table.'_data';
            $table_cms_flag = $tableprefix.'cms_'.$table.'_flag';
            $table_cms_tag = $tableprefix.'cms_'.$table.'_tag';
            $table_cms_tag_data = $tableprefix.'cms_'.$table.'_tag_data';
            $table_cms_views = $tableprefix.'cms_'.$table.'_views';

            if($del_table){
                //删除相关表
                $sql = "DROP TABLE IF EXISTS ".$table_cms;
                $this->db->query($sql);
                $sql = "DROP TABLE IF EXISTS ".$table_cms_attach;
                $this->db->query($sql);
                $sql = "DROP TABLE IF EXISTS ".$table_cms_data;
                $this->db->query($sql);
                $sql = "DROP TABLE IF EXISTS ".$table_cms_flag;
                $this->db->query($sql);
                $sql = "DROP TABLE IF EXISTS ".$table_cms_tag;
                $this->db->query($sql);
                $sql = "DROP TABLE IF EXISTS ".$table_cms_tag_data;
                $this->db->query($sql);
                $sql = "DROP TABLE IF EXISTS ".$table_cms_views;
                $this->db->query($sql);

                //删除模型相关分类
                $this->category->find_delete(array('mid'=>$mid));
            }else{
                $this->cms_content->table = 'cms_'.$table;
                $this->cms_content_data->table = 'cms_'.$table.'_data';
                $this->cms_content_attach->table = 'cms_'.$table.'_attach';
                $this->cms_content_flag->table = 'cms_'.$table.'_flag';
                $this->cms_content_tag->table = 'cms_'.$table.'_tag';
                $this->cms_content_tag_data->table = 'cms_'.$table.'_tag_data';
                $this->cms_content_views->table = 'cms_'.$table.'_views';

                $this->cms_content->truncate();
                $this->cms_content_data->truncate();
                $this->cms_content_attach->truncate();
                $this->cms_content_flag->truncate();
                $this->cms_content_tag->truncate();
                $this->cms_content_tag_data->truncate();
                $this->cms_content_views->truncate();

                //清空模型相关分类内容数量
                $this->category->find_update(array('mid'=>$mid), array('count'=>0));
            }

            //删除模型内容的评论
            $this->cms_content_comment->find_delete(array('mid'=>$mid));
            $this->cms_content_comment_sort->find_delete(array('mid'=>$mid));

            // 重新统计用户的内容数量
            $pagenum = 500;
            $user_total = $this->user->count();

            //所有内容模型的表名
            $table_arr = $this->get_table_arr();

            $maxpage = max(1, ceil($user_total/$pagenum));
            for($i = 1; $i <= $maxpage; $i++){
                $user_arr = $this->user->list_arr(array(), 'uid', 1, ($i-1)*$pagenum, $pagenum, $user_total);
                foreach ($user_arr as $user){
                    $this->user->update_user_contents($user, $table_arr);
                }
            }

            //清除缓存
            $this->runtime->truncate();
            $this->db->truncate('framework_count');
            $this->db->truncate('framework_maxid');

            // hook models_model_xdelete_after.php

            return '';
        }else{
            return lang('delete_failed');
        }
    }

    // hook models_model_after.php
}
