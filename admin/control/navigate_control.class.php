<?php
/**
 * Author: dadadezhou <zhoudada97@foxmail.com>
 * Date: 2021-10-11
 * Time: 14:11
 * Description:后台 导航管理 控制器
 */

defined('ROOT_PATH') or exit;

class navigate_control extends admin_control {
	// 导航管理
	public function index() {
        // hook admin_navigate_control_index_before.php
		// 模型名称
		$models = $this->models->get_models();
		$this->assign('mod_name', $models);

		// 全部分类
		$category_arr = $this->category->get_category();
		$this->assign('category_arr', $category_arr);

        $location = R('location','R');
        if($location){
            $field = 'navigate_'.$location;
            $template = 'navigate_defined.htm';
            $this->assign('location', $location);
        }else{
            $field = 'navigate';
            $template = 'navigate_index.htm';
        }

		// 导航数组
		$nav_arr = $this->kv->xget($field);
		foreach($nav_arr as $k=>$v) {
			if($v['cid'] > 0) $nav_arr[$k]['url'] = $this->category->category_url($v);
			if(isset($v['son'])) {
				foreach($v['son'] as $k2=>$v2) {
					if($v2['cid'] > 0) $nav_arr[$k]['son'][$k2]['url'] = $this->category->category_url($v2);
				}
			}
		}
		$this->assign('nav_arr', $nav_arr);

        //已有导航位
        $nav_location_arr = $this->kv->get('navigate_location');
        $this->assign('nav_location_arr', $nav_location_arr);

		// hook admin_navigate_control_index_after.php

		$this->display($template);
	}

	// 导航管理
	public function get_navigate_content() {
        $location = R('location','R');
        if($location){
            $field = 'navigate_'.$location;
        }else{
            $field = 'navigate';
        }
		// 导航数组
		$nav_arr = $this->kv->xget($field);
        // hook admin_navigate_control_get_navigate_content_after.php
		$this->assign('nav_arr', $nav_arr);

		$this->display('inc-navigate_content.htm');
	}

	// 保存修改
	public function nav_save() {
		$navi = R('navi', 'P');
        $location = R('location','R');
        if($location){
            $field = 'navigate_'.$location;
        }else{
            $field = 'navigate';
        }
        // hook admin_navigate_control_nav_save_before.php

		if(!empty($navi) && is_array($navi)) {
			$nav_arr = array();
			$i = 0;
			foreach($navi as $v) {
				$cid = $v[0] >= 0 ? intval($v[0]) : -99;
				$name = htmlspecialchars(trim($v[1]));
				$url = $cid > 0 ? $cid : htmlspecialchars(trim($v[2]));
				$target = $v[3] ? '_blank' : '_self';
				$rank = intval($v[4]);
                $class = htmlspecialchars(trim($v[5]));

				$alias = '';
				if($cid > 0) {
					$row = $this->category->get($cid);
					$alias = $row['alias'];
				}

				if($rank > 1) {
					$nav_arr[$i]['son'][] = array('cid'=>$cid, 'alias'=>$alias, 'name'=>$name, 'url'=>$url, 'target'=>$target, 'class'=>$class);
				}else{
					$i++;
					$nav_arr[$i] = array('cid'=>$cid, 'alias'=>$alias, 'name'=>$name, 'url'=>$url, 'target'=>$target, 'class'=>$class);
				}
			}
            // hook admin_navigate_control_nav_save_after.php
			$this->kv->set($field, $nav_arr);
		}else{
			E(1, lang('data_error'));
		}

		E(0, lang('edit_successfully'));
	}

	// 添加分类
	public function add_cate() {
		$cate = R('cate', 'P');
        $location = R('location','R');
        if($location){
            $field = 'navigate_'.$location;
        }else{
            $field = 'navigate';
        }
        // hook admin_navigate_control_add_cate_before.php

		if(!empty($cate) && is_array($cate)) {
			$nav_arr = $this->kv->xget($field);
			foreach($cate as $arr) {
				if(isset($arr[0]) && isset($arr[1])) {
					$name = htmlspecialchars(trim($arr[0]));
					$cid = intval($arr[1]);
					$row = $this->category->get($cid);
					$alias = $row['alias'];
					$nav_arr[] = array('cid'=>$cid, 'alias'=>$alias, 'name'=>$name, 'url'=>'', 'target'=>'_self','class'=>'');
				}
			}
            // hook admin_navigate_control_add_cate_after.php
			$this->kv->set($field, $nav_arr);

			E(0, lang('add_successfully'));
		}else{
			E(1, lang('data_error'));
		}
	}

	// 添加链接
	public function add_link() {
        $location = R('location','R');
        if($location){
            $field = 'navigate_'.$location;
        }else{
            $field = 'navigate';
        }
        // hook admin_navigate_control_add_link_before.php

		$name = htmlspecialchars(trim(R('name', 'P')));
		$url = htmlspecialchars(trim(R('url', 'P')));
        $class = htmlspecialchars(trim(R('class', 'P')));
		$target = (int) R('target', 'P');

		!$name && E(1, lang('input_link_name'), 'name');
		!$url && E(1, lang('input_link_url'), 'url');

		$nav_arr = $this->kv->xget($field);
		$nav_arr[] = array('cid'=>-99, 'alias'=>'', 'name'=>$name, 'url'=>$url, 'target'=>($target ? '_blank' : '_self'), 'class'=>$class);

        // hook admin_navigate_control_add_link_after.php
		$this->kv->set($field, $nav_arr);

		E(0, lang('add_successfully'));
	}

	// 删除
	public function del() {
		$key = R('key', 'P');
        $location = R('location','R');
        if($location){
            $field = 'navigate_'.$location;
        }else{
            $field = 'navigate';
        }
        // hook admin_navigate_control_del_before.php

		$nav_arr = $this->kv->xget($field);
		if(is_numeric($key)) {
			unset($nav_arr[$key]);
		}else{
			$k = explode('-', $key);
			$k1 = intval($k[0]);
			$k2 = intval($k[1]);
			if(isset($nav_arr[$k1]['son'][$k2])) unset($nav_arr[$k1]['son'][$k2]);

			if( isset($nav_arr[$k1]['son']) && empty($nav_arr[$k1]['son']) ){unset($nav_arr[$k1]['son']);}
		}
        // hook admin_navigate_control_del_after.php
		$this->kv->set($field, $nav_arr);

		E(0, lang('delete_successfully'));
	}

	//添加导航位
	public function add_nav_location(){
	    if($_POST){
	        $nav_location_alias = trim(R('nav_location_alias', 'P'));
            $nav_location_name = trim(R('nav_location_name', 'P'));

            if(empty($nav_location_alias)){
                E(1, lang('nav_location_alias_no_empty'));
            }elseif (empty($nav_location_name)){
                E(1, lang('nav_location_name_no_empty'));
            }

            if(!preg_match("/^[0-9a-z]+$/i",$nav_location_alias)){
                E(1, lang('nav_location_alias_error'));
            }

            $nav_field = 'navigate_location';

            $nav_location_arr = $this->kv->get($nav_field);
            if(isset($nav_location_arr[$nav_location_alias])){
                E(1, lang('nav_location_alias_exists'));
            }

            $nav_location_arr[$nav_location_alias] = $nav_location_name;

            $this->kv->set($nav_field, $nav_location_arr);

            $this->clear_cache();
            E(0, lang('opt_successfully'));
        }
    }

    //删除导航位
    public function del_nav_location(){
        if($_POST){
            $alias = trim(R('alias', 'P'));
            if(empty($alias)){
                E(1, lang('data_error'));
            }

            $nav_field = 'navigate_location';
            $nav_location_arr = $this->kv->get($nav_field);

            if(isset($nav_location_arr[$alias])){
                unset($nav_location_arr[$alias]);

                $this->kv->set($nav_field, $nav_location_arr);

                //删除cfg里面的导航数据
                $this->kv->xdelete('navigate_'.$alias);

                $this->clear_cache();
            }

            E(0, lang('opt_successfully'));
        }
    }

	// hook admin_navigate_control_after.php
}
