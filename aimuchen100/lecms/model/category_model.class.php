<?php
defined('ROOT_PATH') or exit;

class category extends model {
	private $data = array();		// 防止重复查询

    public $_field_length = array(
        'name'=>50,
        'alias'=>50,
        'pic'=>255,
        'intro'=>255,
        'cate_tpl'=>80,
        'show_tpl'=>80,
        'seo_title'=>255,
        'seo_keywords'=>255,
        'seo_description'=>255,
        'seo_title_rule'=>255,
        'seo_keywords_rule'=>255,
        'seo_description_rule'=>255,
        // hook category_model_field_length_after.php
    );

	function __construct() {
		$this->table = 'category';	// 表名
		$this->pri = array('cid');	// 主键
		$this->maxid = 'cid';		// 自增字段
	}

	// 暂时用些方法解决获取 cfg 值
	function __get($var) {
		if($var == 'cfg') {
			return $this->cfg = $this->runtime->xget();
		}else{
			return parent::__get($var);
		}
	}

    // hook category_model_before.php

	// 检查基本参数是否填写
	public function check_base(&$post) {
        $msg = $name = '';
        // hook category_model_check_base_before.php
		if(empty($post['mid'])) {
			$name = 'mid';
			$msg = lang('select_cate_model');
		}elseif(!isset($post['type'])) {
			$name = 'type';
			$msg = lang('select_cate_type');
		}elseif(!isset($post['upid'])) {
			$name = 'upid';
			$msg = lang('select_cate_upid');
		}elseif(strlen($post['name']) < 1) {
			$name = 'name';
			$msg = lang('input_cate_name');
		}elseif(strlen($post['alias']) < 1) {
			$name = 'alias';
			$msg = lang('input_cate_alias');
		}elseif(strlen($post['alias']) > 50) {
			$name = 'alias';
			$msg = lang('cate_alias_than_50');
		}elseif(empty($post['cate_tpl'])) {
			$name = 'cate_tpl';
			$msg = lang('cate_tpl_no_empty');
		}elseif($post['mid'] > 1 && empty($post['show_tpl'])) {
			$name = 'show_tpl';
			$msg = lang('show_tpl_no_empty');
		}
        // hook category_model_check_base_after.php
		return empty($msg) ? FALSE : array('name' => $name, 'msg' => $msg);
	}

	// 检查别名是否被使用
	public function check_alias($alias) {
		$msg = $this->only_alias->check_alias($alias);
        // hook category_model_check_alias_after.php
		return empty($msg) ? FALSE : array('name' => 'alias', 'msg' => $msg);
	}

	// 检查是否符合修改条件
	public function check_is_edit($post, $data) {
		if($post['cid'] == $post['upid']) {
			$name = 'upid';
			$msg = lang('upid_not_my');//'所属频道不能修改为自己';	// 暂时不考虑 upid 不能为自己的下级分类或非频道分类 (前端已经限制)
		}elseif($data['count'] > 0 && $post['mid'] != $data['mid']) {
			$name = 'mid';
			$msg = lang('dis_category_edit_model_1');//'分类中有内容，不允许修改分类模型，请先清空分类内容';
		}elseif($data['count'] > 0 && $post['type'] != $data['type']) {
			$name = 'type';
			$msg = lang('dis_category_edit_type_1');//'分类中有内容，不允许修改分类属性，请先清空分类内容';
		}elseif($data['type'] == 1 && $post['mid'] != $data['mid'] && $this->check_is_son($data['cid'])) {
			$name = 'mid';
			$msg = lang('dis_category_edit_model');//'分类有下级分类，不允许修改分类模型';
		}elseif($data['type'] == 1 && $post['type'] != $data['type'] && $this->check_is_son($data['cid'])) {
			$name = 'type';
			$msg = lang('dis_category_edit_type');  //'分类有下级分类，不允许修改分类类型';
		}
        // hook category_model_check_is_edit_after.php
		return empty($msg) ? FALSE : array('name' => $name, 'msg' => $msg);
	}

	//删除分类
	public function xdelete($cid = 0){
        // hook category_model_xdelete_before.php
        $data = $this->get($cid);
        if(empty($data)){
            return array('err'=>1, 'msg'=>lang('data_no_exists'));
        }

        // 检查是否符合删除条件
        if($err_msg = $this->check_is_del($data)) {
            return array('err'=>1, 'msg'=>$err_msg);
        }

        if(!$this->delete($cid)) {
            return array('err'=>1, 'msg'=>lang('delete_failed'));
        }

        //单页模型分类删除~
        if($data['mid'] == 1){
            //删除单页内容
            $this->cms_page->delete($cid);

            //删除模型内容的评论
            $this->cms_content_comment->find_delete(array('mid'=>1,'id'=>$cid));
            $this->cms_content_comment_sort->find_delete(array('mid'=>1,'id'=>$cid));
        }

        // 删除导航中的分类
        $nav_key_arr = array('navigate', 'navigate_mobile');

        //用户自定义导航位
        $nav_location_arr = $this->kv->get('navigate_location');
        if($nav_location_arr){
            foreach ($nav_location_arr as $location=>$name){
                $nav_key_arr[] = 'navigate_'.$location;
            }
        }

        foreach ($nav_key_arr as $nav_key){
            $navigate = $this->kv->xget($nav_key);
            foreach($navigate as $k=>$v) {
                if($v['cid'] == $cid) unset($navigate[$k]);
                if(isset($v['son'])) {
                    foreach($v['son'] as $k2=>$v2) {
                        if($v2['cid'] == $cid) unset($navigate[$k]['son'][$k2]);
                    }
                }
            }
            $this->kv->set($nav_key, $navigate);
        }

        // hook category_model_xdelete_after.php
        return array('err'=>0, 'msg'=>lang('delete_successfully'));
    }

    //更新导航菜单中的分类alias
    public function update_navigate_alias($category = array()){
        $nav_key_arr = array('navigate', 'navigate_mobile');

        //用户自定义导航位
        $nav_location_arr = $this->kv->get('navigate_location');
        if($nav_location_arr){
            foreach ($nav_location_arr as $location=>$name){
                $nav_key_arr[] = 'navigate_'.$location;
            }
        }

        // hook category_model_update_navigate_alias_before.php

        foreach ($nav_key_arr as $nav_key){
            $navigate = $this->kv->xget($nav_key);
            foreach($navigate as $k=>$v) {
                if($v['cid'] == $category['cid']) $navigate[$k]['alias'] = $category['alias'];
                if(isset($v['son'])) {
                    foreach($v['son'] as $k2=>$v2) {
                        if($v2['cid'] == $category['cid']) $navigate[$k]['son'][$k2]['alias'] = $category['alias'];
                    }
                }
            }
            $this->kv->set($nav_key, $navigate);
        }

        // hook category_model_update_navigate_alias_after.php
        return TRUE;
    }

	// 检查是否符合删除条件
	public function check_is_del($data) {
        // hook category_model_check_is_del_before.php
		if($data['type'] == 1 && $this->check_is_son($data['cid'])) {
			return lang('dis_category_delete_level');//'分类有下级分类，请先删除下级分类';
		}elseif($data['count'] > 0) {
			return lang('dis_category_delete_contents');//'分类中有内容，请先删除内容';
		}
        // hook category_model_check_is_del_after.php
		return FALSE;
	}

	// 检查是否有下级分类
	public function check_is_son($upid) {
		return $this->find_fetch_key(array('upid' => $upid), array(), 0, 1) ? TRUE : FALSE;
	}

    // 格式化分类数组
    public function format(&$v) {
        // hook category_model_format_before.php

        if(empty($v)) return FALSE;

        if( isset($this->cfg['webdir']) ){
            $cfg = $this->cfg;
        }else{
            $cfg = $this->kv->xget();
        }

        if( isset($v['pic']) && !empty($v['pic']) ){
            $v['haspic'] = 1;
            if( substr($v['pic'], 0, 2) != '//' && substr($v['pic'], 0, 4) != 'http' ){ //不是外链图片
                $v['pic'] = $cfg['weburl'].$v['pic'];
            }
        }else{
            $v['haspic'] = 0;
            $v['pic'] = $cfg['weburl'].'static/img/nopic.gif';
        }
        !isset($v['url']) && $v['url'] = $this->category_url($v);

        // hook category_model_format_after.php
    }

    // 根据模型ID从数据库获取分类
    public function get_category_db_mid($mid = 2) {
        if(isset($this->data['category_db_'.$mid])) {
            return $this->data['category_db_'.$mid];
        }

        // hook category_model_get_category_db_mid_before.php

        $arr = array();
        $tmp = $this->find_fetch(array('mid'=>$mid), array('orderby'=>1, 'cid'=>1));
        foreach($tmp as $v) {
            $arr[$v['cid']] = $v;
        }

        // hook category_model_get_category_db_mid_after.php

        return $this->data['category_db_'.$mid] = $arr;
    }

	// 从数据库获取分类
	public function get_category_db() {
		if(isset($this->data['category_db'])) {
			return $this->data['category_db'];
		}

		// hook category_model_get_category_db_before.php

		$arr = array();
		$tmp = $this->find_fetch(array(), array('orderby'=>1, 'cid'=>1));
		foreach($tmp as $v) {
			$arr[$v['cid']] = $v;
		}

		// hook category_model_get_category_db_after.php

		return $this->data['category_db'] = $arr;
	}

	// 获取分类 (树状结构)
	public function get_category_tree() {
		if(isset($this->data['category_tree'])) {
			return $this->data['category_tree'];
		}

		$this->data['category_tree'] = array();
		$tmp = $this->get_category_db();

		// 格式化为树状结构 (会舍弃不合格的结构)
		foreach($tmp as $v) {
			$tmp[$v['upid']]['son'][$v['cid']] = &$tmp[$v['cid']];
		}
		$this->data['category_tree'] = isset($tmp['0']['son']) ? $tmp['0']['son'] : array();

		// 格式化为树状结构 (不会舍弃不合格的结构)
		// foreach($tmp as $v) {
		// 	if(isset($tmp[$v['upid']])) $tmp[$v['upid']]['son'][] = &$tmp[$v['cid']];
		// 	else $this->data['category_tree'][] = &$tmp[$v['cid']];
		// }

		return $this->data['category_tree'];
	}

	// 获取分类 (二维数组)
	public function get_category() {
		if(isset($this->data['category_array'])) {
			return $this->data['category_array'];
		}

		$arr = $this->get_category_tree();
		return $this->data['category_array'] = $this->to_array($arr);
	}

	// 递归转换为二维数组
	public function to_array($data, $pre = 1) {
		static $arr = array();

		foreach($data as $k => $v) {
			$v['pre'] = $pre;
			if(isset($v['son'])) {
				$arr[$v['mid']][] = $v;
				self::to_array($v['son'], $pre+1);
			}else{
				$arr[$v['mid']][] = $v;
			}
		}

		return $arr;
	}

	// 获取模型下级所有列表分类的cid
	public function get_cids_by_mid($mid) {
		$k = 'cate_by_mid_'.$mid;
		if(isset($this->data[$k])) return $this->data[$k];

		$arr = $this->runtime->xget($k);
		if(empty($arr)) {
			$arr = $this->get_cids_by_upid(0, $mid);
			$this->runtime->set($k, $arr);
		}
		$this->data[$k] = $arr;
		return $arr;
	}

	// 获取频道分类下级列表分类的cid
	public function get_cids_by_upid($upid, $mid) {
		$arr = array();
		$tmp = $this->get_category_db();
		if($upid != 0 && !isset($tmp[$upid])) return FALSE;

		foreach($tmp as $k => $v) {
			if($v['mid'] == $mid) {
				$tmp[$v['upid']]['son'][$v['cid']] = &$tmp[$v['cid']];
			}else{
				unset($tmp[$k]);
			}
		}

		if(isset($tmp[$upid]['son'])) {
			foreach($tmp[$upid]['son'] as $k => $v) {
				if($v['type'] == 1) {
					$arr[$k] = isset($v['son']) ? self::recursion_cid($v['son']) : array();
				}elseif($v['type'] == 0) {
					$arr[$k] = 1;
				}
			}
		}

		return $arr;
	}

	// 递归获取下级分类全部 cid
	public function recursion_cid(&$data) {
		$arr = array();
		foreach($data as $k => $v) {
			if(isset($v['son'])) {
				$arr2 = self::recursion_cid($v['son']);
				$arr = array_merge($arr, $arr2);
			}else{
				if($v['type'] == 0) {
					$arr[] = intval($v['cid']);
				}
			}
		}
		return $arr;
	}

	// 获取分类下拉列表HTML (内容发布时使用)
	public function get_cidhtml_by_mid($_mid = 0, $cid = 0, $tips = '选择分类', $in_cid = array(), $other = '') {
		$category_arr = $this->get_category();

		$s = '<select name="cid" id="cid" lay-filter="cid" '.$other.'>';
		if(empty($category_arr)) {
			$s .= '<option value="0">'.lang('none').'</option>';
		}else{
			$s .= '<option value="0"'.(empty($cid) ? ' selected="selected"': '').'>'.$tips.'</option>';
			foreach($category_arr as $mid => $arr) {
				if($_mid && $mid != $_mid) continue;

				foreach($arr as $v) {
				    if($in_cid && !in_array($v['cid'], $in_cid)){
				        continue;
                    }
					$disabled = $v['type'] == 1 ? ' disabled="disabled"' : '';
					$s .= '<option value="'.$v['cid'].'"'.($v['type'] == 0 && $v['cid'] == $cid ? ' selected="selected"' : '').$disabled.'>';
                    if($v['pre'] > 1){
                        $s .= '|'.str_repeat("─", $v['pre']-1).' ';
                    }
					$s .= $v['name'].($v['type'] == 1 ? '['.lang('cate_type_1').']' : '').'</option>';
				}
			}
		}
		$s .= '</select>';
        // hook category_model_get_cidhtml_by_mid_after.php
		return $s;
	}

	// 获取上级分类的 HTML 代码 (只显示频道分类)
	public function get_category_upid($mid, $upid = 0, $noid = 0) {
		$category_arr = $this->get_category();

		$s = '<option value="0">'.lang('none').'</option>';
		if(isset($category_arr[$mid])) {
			foreach($category_arr[$mid] as $v) {
				// 不显示列表的分类
				if($mid> 1 && $v['type'] == 0) continue;

				// 当 $noid 有值时，排除等于它和它的下级分类
				if($noid) {
					if(isset($pre)) {
						if($v['pre'] > $pre) continue;
						else unset($pre);
					}
					if($v['cid'] == $noid) {
						$pre = $v['pre'];
						continue;
					}
				}

				$s .= '<option value="'.$v['cid'].'"'.($v['cid'] == $upid ? ' selected="selected"' : '').'>';
				$s .= str_repeat("　", $v['pre']-1);
				$s .= '|─'.$v['name'].'</option>';
			}
		}
        // hook category_model_get_category_upid_after.php
		return $s;
	}

	// 获取指定分类的 mid (如果 cid 为空，则读第一个分类的 mid)
	public function get_mid_by_cid($cid) {
		if($cid) {
			$arr = $this->get($cid);
		}else{
			$arr = $this->get_category();
			if(empty($arr)) return 2;

			$arr = current($arr);
			$arr = current($arr);
		}
        // hook category_model_get_mid_by_cid_after.php
		return $arr['mid'];
	}

	// 获取分类当前位置
	public function get_place($cid) {
		$p = array();
		$tmp = $this->get_category_db();

		while(isset($tmp[$cid]) && $v = &$tmp[$cid]) {
			array_unshift($p, array(
				'cid'=> $v['cid'],
				'name'=> $v['name'],
				'url'=> $this->category_url($v, 0, array('url_path'=>1))
			));
			$cid = $v['upid'];
		}
        // hook category_model_get_place_after.php
		return $p;
	}

	// 获取分类缓存合并数组
	public function get_cache($cid) {
		$k = 'cate_'.$cid;
		if(isset($this->data[$k])) return $this->data[$k];

		$arr = $this->runtime->xget($k);
		if(empty($arr)) {
			$arr = $this->update_cache($cid);
		}
		$this->data[$k] = $arr;
        // hook category_model_get_cache_after.php
		return $arr;
	}

	// 更新分类缓存合并数组
	public function update_cache($cid) {
		$k = 'cate_'.$cid;
		$arr = $this->get($cid);
		if(empty($arr)) return FALSE;

		$arr['place'] = $this->get_place($cid);	// 分类当前位置
		$arr['topcid'] = $arr['place'][0]['cid'];	// 顶级分类CID
		$arr['table'] = $this->cfg['table_arr'][$arr['mid']];	// 分类模型表名

		// 如果为频道，获取频道分类下级CID
		if($arr['type'] == 1) {
			$arr['son_list'] = $this->get_cids_by_upid($cid, $arr['mid']);
			$arr['son_cids'] = array();
			if(!empty($arr['son_list'])) {
				foreach($arr['son_list'] as $c => $v) {
					if(is_array($v)) {
						$v && $arr['son_cids'] = array_merge($arr['son_cids'], $v);
					}else{
						$arr['son_cids'][] = $c;
					}
				}
			}
		}

		// hook category_model_update_cache_after.php

		$this->runtime->set($k, $arr);
		return $arr;
	}

	//删除指定的分类缓存
    public function delete_cache_one($cid = 0){
        // hook category_model_delete_cache_one_before.php
        $k = 'cate_'.$cid;
        $this->runtime->delete($k);
        // hook category_model_delete_cache_one_after.php
        return true;
    }

	// 删除所有分类缓存 (最多读取2000条，如果缓存太大，需要手工清除缓存)
	public function delete_cache() {
		$key_arr = $this->runtime->find_fetch_key(array(), array(), 0, 2000);
		foreach ($key_arr as $v) {
			if(substr($v, 10, 5) == 'cate_') {
				$this->runtime->delete(substr($v, 10));
			}
		}
        // hook category_model_delete_cache_after.php
		return TRUE;
	}

	//内容列表获取分类信息
    public function getCategoryInfoByList(&$v, $cate = array()){
        (empty($cate) && isset($v['cid'])) AND $cate = $this->get_cache($v['cid']);

        // hook category_model_getCategoryInfoByList_before.php
	    if(isset($cate['cid']) && isset($v['cid']) && $cate['cid'] == $v['cid']){
            $v['cate_name'] = isset($cate['name']) ? $cate['name'] : '';
            $v['cate_url'] = $this->category_url($cate);
            $v['cate_alias'] = isset($cate['alias']) ? $cate['alias'] : '';
            $v['cate_intro'] = isset($cate['intro']) ? $cate['intro'] : '';

            // hook category_model_getCategoryInfoByList_after.php
        }
    }

    // 分类链接格式化
    public function category_url(&$cate, $page = FALSE, $extra = array()) {
        $url = '';
        $cid = isset($cate['cid']) ? (int)$cate['cid'] : 0;
        $alias = isset($cate['alias']) ? $cate['alias'] : '';
        //使用相对URL
        if( (isset($this->cfg['url_path']) && !empty($this->cfg['url_path'])) || isset($extra['url_path']) && !empty($extra['url_path']) ){
            unset($extra['url_path']);//要去掉这个参数，不然会到下面的附加参数里面
            $this->cfg['weburl'] = $this->cfg['webdir'];
        }
        // hook category_model_category_url_before.php

        if(empty($cid) || empty($alias)){
            return '';
        }

        $lecms_parseurl = empty($_ENV['_config']['lecms_parseurl']) ? 0 : 1;
        if(empty($lecms_parseurl)) {
            $url .= $this->cfg['weburl'].'index.php?cate--cid-'.$cid.($page ? '-page-{page}' : '');
        }else{
            // hook category_model_category_url_lecms_parseurl_before.php
            if($page) {
                $url .= $this->cfg['weburl'].$alias.$this->cfg['link_cate_page_pre'].'{page}';
            }else{
                $url .= $this->cfg['weburl'].$alias;
            }
        }

        // 附加参数
        if($extra) {
            foreach ($extra as $k=>$v){
                if(empty($lecms_parseurl)) {
                    $url .= '-'.$k.'-'.$v;
                }else{
                    $url .= '/'.$k.'-'.$v;
                }
            }
        }

        //后缀
        if(empty($lecms_parseurl)) {
            $url .= $_ENV['_config']['url_suffix'];
        }else{
            if($page) {
                $url .= $this->cfg['link_cate_page_end'];
            }else{
                $url .= $this->cfg['link_cate_end'];
            }
        }
        // hook category_model_category_url_after.php
        return $url;
    }

    // hook category_model_after.php
}
