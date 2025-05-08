<?php
defined('ROOT_PATH') or exit;

class tag_control extends admin_control {
    public $_mid = 2;
    public $_table = 'article';
    public $_name = '文章';

    function __construct(){
        parent::__construct();

        $this->_mid = max(2, (int)R('mid','R'));

        if($this->_mid > 2){
            $models = $this->models->get($this->_mid);
            empty($models) && $this->message(1, lang('data_error'));

            $this->_table = $models['tablename'];
        }else{
            $models = array(
                'name'=>$this->_name,
                'tablename'=>$this->_table
            );
        }

        if( $_ENV['_config']['admin_lang'] == 'zh-cn' ){
            $this->_name = $models['name'];
        }else{
            $this->_name = ucfirst($models['tablename']);
        }

        // 初始模型表名
        $this->cms_content_tag->table = 'cms_'.$this->_table.'_tag';

        $this->assign('mid',$this->_mid);
        $this->assign('table',$this->_table);
        $this->assign('name',$this->_name);
    }

	// 标签管理
	public function index() {
		// hook admin_tag_control_index_before.php

        $midhtml = $this->cms_content_tag->get_taghtml_mid($this->_mid, 'lay-filter="mid"');
        $this->assign('midhtml',$midhtml);
        // hook admin_tag_control_index_after.php
		$this->display();
	}

    //获取列表
    public function get_list(){
        // hook admin_tag_control_get_list_before.php
        //分页
        $page = isset( $_REQUEST['page'] ) ? intval($_REQUEST['page']) : 1;
        $pagenum = isset( $_REQUEST['limit'] ) ? intval($_REQUEST['limit']) : 15;

        //获取查询条件
        $keyword = isset( $_REQUEST['keyword'] ) ? trim($_REQUEST['keyword']) : '';
        if($keyword) {
            $keyword = urldecode($keyword);
            $keyword = safe_str($keyword);
        }

        //组合查询条件
        $where = array();
        if( $keyword ){
            $where['name'] = array('LIKE'=>$keyword);
        }
        // hook admin_tag_control_get_list_where_after.php
        //数据量
        if( $where ){
            $total = $this->cms_content_tag->find_count($where);
        }else{
            $total = $this->cms_content_tag->count();
        }

        //页数
        $maxpage = max(1, ceil($total/$pagenum));
        $page = min($maxpage, max(1, $page));

        // 获取列表
        $data_arr = array();
        $cms_arr = $this->cms_content_tag->list_arr($where, 'tagid', -1, ($page-1)*$pagenum, $pagenum, $total);
        foreach($cms_arr as &$v) {
            $v['url'] = $this->cms_content->tag_url($this->_mid, $v);

            $data_arr[] = $v;   //排序需要索引从0开始
        }
        unset($cms_arr);
        // hook admin_tag_control_get_list_data_arr_after.php

        //组合数据 输出到页面
        $arr = array(
            'code' => 0,
            'msg' => '',
            'count' => $total,
            'data' => $data_arr,
        );
        exit( json_encode($arr) );
    }

    //编辑表格字段
    public function set(){
        // hook admin_tag_control_set_before.php
        if( !empty($_POST) ){
            $field = trim( R('field','P') );
            $tagid = intval( R('tagid','P') );
            $value = trim( R('value','P') );

            $tag = $this->cms_content_tag->get($tagid);
            empty($tag) && E(1, lang('data_no_exists'));

            $tag[$field] = $value;

            if(!$this->cms_content_tag->update($tag)) {
                E(1, lang('edit_failed'));
            }
            // hook admin_tag_control_set_after.php

            E(0, lang('edit_field_successfully', array('field'=>$field)) );
        }
    }

	// 添加标签
	public function add() {
        // hook admin_tag_control_add_before.php
        if(empty($_POST)) {
            $data = array(
                'tagid'=>0,
                'name'=>'',
                'orderby'=>0,
                'pic'=>'',
                'content'=>'',
                'seo_title'=>'',
                'seo_keywords'=>'',
                'seo_description'=>'',
            );

            // hook admin_tag_control_add_data_after.php
            $this->assign('data',$data);

            $input = $this->get_input($data);
            // hook admin_tag_control_add_get_input_after.php
            $this->assign('input',$input);
            // hook admin_tag_control_add_after.php
            $this->display('tag_set.htm');
        }else{
            $name = trim(safe_str(R('name', 'P')));
            $content = htmlspecialchars(trim(R('content', 'P')));

            $batch_name = R('batch_name', 'P');
            $tags_arr = explode(PHP_EOL, $batch_name);
            $tags_arr = array_filter($tags_arr);    //去掉空值
            $tags_arr = array_unique($tags_arr);    //去掉重复

            // hook admin_tag_control_add_post_before.php

            if($tags_arr){
                $total = count($tags_arr);
                $guolv = $chongfu = $succ = $fail = 0;
                foreach($tags_arr as $name){
                    $name = $this->cms_content_tag->_tagformat($name);
                    if(empty($name)){
                        $guolv++;
                        continue;
                    }else{
                        if($this->cms_content_tag->find_fetch(array('name'=>$name), array(), 0, 1)){
                            $chongfu++;
                            continue;
                        }else{
                            $data = array(
                                'name'=>$name,
                                'count'=>0,
                                'content'=>$content,
                                'pic'=>R('pic', 'P'),
                                'seo_title' => trim(strip_tags(R('seo_title', 'P'))),
                                'seo_keywords' => trim(strip_tags(R('seo_keywords', 'P'))),
                                'seo_description' => trim(strip_tags(R('seo_description', 'P'))),
                                'orderby'=>(int)R('orderby', 'P')
                            );

                            if($this->cms_content_tag->create($data)) {
                                $succ++;
                            }else{
                                $fail++;
                            }
                        }
                    }
                }
                //$msg = "共{$total}条数据，成功{$succ}条，失败{$fail}条，重复{$chongfu}条，过滤{$guolv}条";
                E(0, lang('add_successfully'));
            }

            $name = $this->cms_content_tag->_tagformat($name);
            if(empty($name)){
                E(1, lang('data_error'));
            }

            if($this->cms_content_tag->find_fetch(array('name'=>$name), array(), 0, 1)){
                E(1, lang('tag_exists'));
            }

            $data = array(
                'name'=>$name,
                'count'=>0,
                'content'=>$content,
                'pic'=>R('pic', 'P'),
                'seo_title' => trim(strip_tags(R('seo_title', 'P'))),
                'seo_keywords' => trim(strip_tags(R('seo_keywords', 'P'))),
                'seo_description' => trim(strip_tags(R('seo_description', 'P'))),
                'orderby'=>(int)R('orderby', 'P')
            );

            // hook admin_tag_control_add_post_after.php
            $tagid = $this->cms_content_tag->create($data);
            // hook admin_tag_control_add_post_end.php
            if($tagid) {
                E(0, lang('add_successfully'));
            }else{
                E(1, lang('add_failed'));
            }
        }
	}

    protected function get_input($data = array())
    {
        $input = array();
        $input['mid'] = $this->cms_content_tag->get_taghtml_mid($this->_mid, 'lay-filter="mid"');
        $input['name'] = form::get_text('name', $data['name'], '', 'placeholder="'.lang('tagname').'" maxlength="'.$this->cms_content_tag->_field_length['name'].'"');

        $input['pic'] = form::get_text('pic', $data['pic'], '', 'id="pic" placeholder="'.lang('thumb').'" maxlength="'.$this->cms_content_tag->_field_length['pic'].'"');
        $input['orderby'] = form::get_number('orderby', $data['orderby'], '', 'placeholder="'.lang('orderby').'"');
        $input['content'] = form::get_text('content', $data['content'], '', 'placeholder="'.lang('content').'" maxlength="'.$this->cms_content_tag->_field_length['content'].'"');

        $input['seo_title'] = form::get_text('seo_title', $data['seo_title'], '', 'placeholder="'.lang('seo_title').'" maxlength="'.$this->cms_content_tag->_field_length['seo_title'].'"');
        $input['seo_keywords'] = form::get_text('seo_keywords', $data['seo_keywords'], '', 'placeholder="'.lang('seo_keywords').'" maxlength="'.$this->cms_content_tag->_field_length['seo_keywords'].'"');
        $input['seo_description'] = form::get_text('seo_description', $data['seo_description'], '', 'placeholder="'.lang('seo_description').'" maxlength="'.$this->cms_content_tag->_field_length['seo_description'].'"');

        // hook admin_tag_control_get_input_after.php
        return $input;
    }

	//编辑
    public function edit(){
        // hook admin_tag_control_edit_before.php

	    if(empty($_POST)){
            $tagid = intval( R('tagid','G') );
            $data = $this->cms_content_tag->get($tagid);
            if(empty($data)) $this->message(0, lang('data_error'), -1);

            // hook admin_tag_control_edit_data_after.php
            $this->assign('data',$data);

            $input = $this->get_input($data);
            // hook admin_tag_control_edit_get_input_after.php
            $this->assign('input',$input);

            $this->display('tag_set.htm');
        }else{
            $tagid = (int)R('tagid','P');

            $name = trim(R('name', 'P'));
            $content = htmlspecialchars(trim(R('content', 'P')));

            $name = $this->cms_content_tag->_tagformat($name);
            if(empty($name)){
                E(1, lang('data_error'));
            }

            $tagold = $this->cms_content_tag->get($tagid);
            empty($tagold) &&  E(1, lang('data_no_exists'));

            if($tagold['name'] != $name && $this->cms_content_tag->find_fetch(array('name'=>$name), array(), 0, 1)){
                E(1, lang('tag_exists'));
            }

            // 修改 cms_content 表的内容
            if($tagold['name'] != $name) {
                $this->cms_content->table = 'cms_'.$this->_table;
                $this->cms_content_tag_data->table = 'cms_'.$this->_table.'_tag_data';

                $list_arr = $this->cms_content_tag_data->find_fetch(array('tagid'=>$tagid));
                foreach($list_arr as $v) {
                    $data2 = $this->cms_content->get($v['id']);
                    if(empty($data2)){
                        $this->cms_content_tag_data->find_delete(array('tagid'=>$tagid, 'id'=>$v['id']));
                    }else{
                        $row = _json_decode($data2['tags']);
                        $row[$tagid] = $name;

                        $up_data2['id'] = $v['id'];
                        $up_data2['tags'] = _json_encode($row);
                        $this->cms_content->update($up_data2);
                    }
                }
            }

            $data = array(
                'tagid'=>$tagid,
                'name'=>$name,
                'content'=>$content,
                'pic'=>R('pic', 'P'),
                'seo_title' => trim(strip_tags(R('seo_title', 'P'))),
                'seo_keywords' => trim(strip_tags(R('seo_keywords', 'P'))),
                'seo_description' => trim(strip_tags(R('seo_description', 'P'))),
                'orderby'=>(int)R('orderby', 'P')
            );

            // hook admin_tag_control_edit_after.php
            $r = $this->cms_content_tag->update($data);
            // hook admin_tag_control_edit_post_end.php

            if($r) {
                E(0, lang('edit_successfully'));
            }else{
                E(1, lang('edit_failed'));
            }
        }
    }

	// 删除
	public function del() {
		// hook admin_tag_control_del_before.php

		$tagid = (int) R('tagid', 'P');
		empty($tagid) && E(1, lang('data_error'));

		$err = $this->cms_content_tag->xdelete($this->_table, $tagid);
		if($err) {
			E(1, $err);
		}else{
            // hook admin_tag_control_del_success.php
			E(0, lang('delete_successfully'));
		}
	}

	// 批量删除
	public function batch_del() {
		// hook admin_tag_control_batch_del_before.php
		$id_arr = R('id_arr', 'P');

		if(!empty($id_arr) && is_array($id_arr)) {
			$err_num = 0;
			foreach($id_arr as $tagid) {
				$err = $this->cms_content_tag->xdelete($this->_table, $tagid);
				if($err) $err_num++;
			}

			if($err_num) {
				E(1, $err_num.lang('num_del_failed'));
			}else{
				E(0, lang('delete_successfully'));
			}
		}else{
			E(1, lang('data_error'));
		}
	}

	// hook admin_tag_control_after.php
}
