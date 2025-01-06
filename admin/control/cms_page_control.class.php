<?php
defined('ROOT_PATH') or exit;
class cms_page_control extends admin_control{

    // 单页管理
    public function index() {
        // hook admin_cms_page_control_index_before.php
        //表格显示列表
        $cols = "{field: 'cid', width: 80, title: '".lang('cid')."', align: 'center'},";
        $cols .= "{field: 'name', minWidth: 150, title: '".lang('cate_name')."'},";
        $cols .= "{field: 'alias', title: '".lang('alias')."', edit: 'text'},";
        $cols .= "{field: 'cate_tpl', title: '".lang('cate_tpl')."', edit: 'text'},";
        $cols .= "{field: 'orderby', width: 80, title: '".lang('orderby')."', edit: 'number', align: 'center'},";
        // hook admin_cms_page_control_index_cols_after.php
        $cols .= "{title: '".lang('opt')."', width: 145, toolbar: '#currentTableBar', align: 'center'}";

        // hook admin_cms_page_control_index_after.php
        $this->assign('cols', $cols);
        $this->display();
    }

    //获取单页分类
    public function get_list(){
        $where = array('mid'=>1);
        // hook admin_cms_page_control_get_list_before.php

        $tmp = $this->category->find_fetch($where, array('orderby'=>1,'cid'=>1));
        $category_arr = array();
        foreach ($tmp as &$v){
            if( $v['upid'] == 0 ){  //顶级的父ID 必须为 -1
                $v['upid'] = -1;
            }

            $v['url'] = $this->category->category_url($v);
            $category_arr[] = $v;
        }
        $total = $this->category->find_count($where);
        // hook admin_cms_page_control_get_list_after.php
        $arr = array(
            'code' => 0,
            'msg' => '',
            'count' => $total,
            'data' => $category_arr,
        );
        exit( json_encode($arr) );
    }

    //编辑表格字段
    public function set_field(){
        // hook admin_cms_page_control_set_field_before.php
        if( !empty($_POST) ){
            $field = trim( R('field','P') );
            $cid = intval( R('cid','P') );
            $value = trim( R('value','P') );

            ($field != 'orderby' && empty($value)) && E(1, lang('filed_not_empty', array('field'=>$field)));

            $categorys = $this->category->get($cid);
            empty($categorys) && E(1, lang('data_no_exists'));

            //单页分类
            $categorys['mid'] != 1 && E(1, lang('data_error'));

            $change_alias = 0;
            if($field == 'alias'){  //修改别名 需要判断
                $value = strtolower($value);
                if($categorys['alias'] != $value){
                    if($err = $this->category->check_alias($value)) {
                        E(1, $err['msg']);
                    }
                    // 修改导航中的分类的别名
                    $change_alias = 1;
                }
            }

            $categorys[$field] = $value;

            if(!$this->category->update($categorys)) {
                E(1, lang('edit_failed'));
            }

            // 修改导航中的分类的别名
            if($change_alias){
                $this->category->update_navigate_alias($categorys);
            }

            // hook admin_cms_page_control_set_field_after.php

            // 删除缓存
            $this->runtime->truncate();

            E(0, lang('edit_field_successfully', array('field'=>$field)) );
        }
    }

    //添加单页分类
    public function add(){
        if(empty($_POST)){
            // hook admin_cms_page_control_add_before.php

            $def_mid = 1;
            $def_model = $this->models->get($def_mid);

            $data = array(
                'cid'=>0,
                'mid'=>$def_mid,
                'type'=>0,
                'upid'=>0,
                'name'=>'',
                'alias'=>'',
                'pic'=>'',
                'intro'=>'',
                'cate_tpl'=>$def_model['cate_tpl'],
                'show_tpl'=>'',
                'count'=>0,
                'orderby'=>0,
                'seo_title'=>'',
                'seo_keywords'=>'',
                'seo_description'=>'',
                'contribute'=>0,
                'son_cate'=>0
            );
            // hook admin_cms_page_control_add_data_after.php
            $this->assign('data',$data);

            $input = $this->get_input($data);
            // hook admin_category_control_add_get_input_after.php
            $this->assign('input',$input);

            $this->display('cms_page_set.htm');
        }else{
            $post = $this->get_post();
            $page_content = R('page_content', 'P');
            $category = &$this->category;
            // hook admin_cms_page_control_add_post_before.php

            // 检查基本参数是否填写
            if($err = $category->check_base($post)) {
                E(1, $err['msg'], $err['name']);
            }

            // 检查别名是否被使用
            if($err = $category->check_alias($post['alias'])) {
                E(1, $err['msg'], $err['name']);
            }

            $maxid = $category->create($post);
            if(!$maxid) {
                E(1, lang('add_failed'));
            }

            $pagedata = array('content' => $page_content);
            // hook admin_cms_page_control_add_page_content_after.php

            if(!$this->cms_page->set($maxid, $pagedata)) {
                $category->delete($maxid);
                E(1, lang('add_failed'));
            }

            // hook admin_cms_page_control_add_post_success.php

            // 删除缓存
            $this->runtime->truncate();
            E(0, lang('add_successfully'));
        }
    }

    protected function get_input($data = array())
    {
        $input = array();
        $type_arr = array(0=>lang('cate_type_0'), 1=>lang('cate_type_1'));
        $input['type'] = form::get_radio_layui('type', $type_arr, $data['type'], 'lay-filter="type"');
        $input['name'] = form::get_text('name', $data['name'], '', 'placeholder="'.lang('category_name').'" maxlength="'.$this->category->_field_length['name'].'" required="required" lay-verify="required"');
        $input['alias'] = form::get_text('alias', $data['alias'], '', 'placeholder="'.lang('alias').'" maxlength="'.$this->category->_field_length['alias'].'" required="required" lay-verify="required"');

        $input['intro'] = form::get_text('intro', $data['intro'], '', 'placeholder="'.lang('intro').'" maxlength="'.$this->category->_field_length['intro'].'"');
        $input['pic'] = form::get_text('pic', $data['pic'], '', 'id="pic" placeholder="'.lang('thumb').'" maxlength="'.$this->category->_field_length['pic'].'"');
        $input['orderby'] = form::get_number('orderby', $data['orderby'], '', 'placeholder="'.lang('orderby').'"');

        $contribute_arr = array(0=>lang('disable'), 1=>lang('enable'));
        $input['contribute'] = form::get_radio_layui('contribute', $contribute_arr, $data['contribute'], 'lay-filter="contribute"');

        $input['cate_tpl'] = form::get_text('cate_tpl', $data['cate_tpl'], '', 'id="cate_tpl" placeholder="'.lang('cate_tpl').'" maxlength="'.$this->category->_field_length['cate_tpl'].'" required="required" lay-verify="required"');
        $input['show_tpl'] = form::get_text('show_tpl', $data['show_tpl'], '', 'id="show_tpl" placeholder="'.lang('show_tpl').'" maxlength="'.$this->category->_field_length['show_tpl'].'" required="required" lay-verify="required"');

        $input['seo_title'] = form::get_text('seo_title', $data['seo_title'], '', 'placeholder="'.lang('seo_title').'" maxlength="'.$this->category->_field_length['seo_title'].'"');
        $input['seo_keywords'] = form::get_text('seo_keywords', $data['seo_keywords'], '', 'placeholder="'.lang('seo_keywords').'" maxlength="'.$this->category->_field_length['seo_keywords'].'"');
        $input['seo_description'] = form::get_text('seo_description', $data['seo_description'], '', 'placeholder="'.lang('seo_description').'" maxlength="'.$this->category->_field_length['seo_description'].'"');

        // hook admin_cms_page_control_get_input_after.php
        return $input;
    }

    //编辑单页分类
    public function edit(){
        if(empty($_POST)){
            // hook admin_cms_page_control_edit_before.php
            $cid = (int)R('cid','G');
            $data = $this->category->get($cid);
            if(empty($data)){
                $this->message(0, lang('data_no_exists'));
            }elseif ($data['mid'] != 1){
                $this->message(0, lang('data_error'));
            }

            // 读取单页内容
            $data2 = $this->cms_page->get($cid);
            if($data2) $data['page_content'] = $data2['content'];

            // hook admin_cms_page_control_edit_data_after.php
            $this->assign('data',$data);

            $input = $this->get_input($data);
            // hook admin_cms_page_control_edit_get_input_after.php
            $this->assign('input',$input);

            $this->display('cms_page_set.htm');
        }else{
            $post = $this->get_post();
            $page_content = R('page_content', 'P');
            $category = &$this->category;
            // hook admin_cms_page_control_edit_post_before.php

            // 检查基本参数是否填写
            if($err = $category->check_base($post)) {
                E(1, $err['msg'], $err['name']);
            }

            $data = $category->get($post['cid']);
            if(empty($data)){
                E(1, lang('data_no_exists'));
            }elseif ($data['mid'] != 1){
                E(1, lang('data_error'));
            }

            if($post['alias'] != $data['alias']) {
                $err = $category->check_alias($post['alias']);
                if($err) {
                    E(1, $err['msg'], $err['name']);
                }

                // 修改导航中的分类的别名
                $category->update_navigate_alias($post);
            }

            if(!$category->update($post)) {
                E(1, lang('edit_failed'));
            }

            $pagedata = array('content' => $page_content);
            // hook admin_cms_page_control_edit_page_content_after.php

            if(!$this->cms_page->set($post['cid'], $pagedata)) {
                E(1, lang('edit_failed'));
            }

            // hook admin_cms_page_control_edit_post_success.php

            // 删除缓存
            $this->runtime->truncate();
            E(0, lang('edit_successfully'));
        }
    }

    //获取POST数据
    private function get_post(){
        $post = array(
            'cid' => intval(R('cid', 'P')),
            'mid' => 1,
            'type' => 0,
            'upid' => intval(R('upid', 'P')),
            'name' => trim(strip_tags(R('name', 'P'))),
            'pic' => trim(R('pic', 'P')),
            'alias' => strtolower(trim(R('alias', 'P'))),
            'intro' => trim(strip_tags(R('intro', 'P'))),
            'cate_tpl' => trim(strip_tags(R('cate_tpl', 'P'))),
            'show_tpl' => '',
            'count' => 0,
            'orderby' => intval(R('orderby', 'P')),
            'seo_title' => trim(strip_tags(R('seo_title', 'P'))),
            'seo_keywords' => trim(strip_tags(R('seo_keywords', 'P'))),
            'seo_description' => trim(strip_tags(R('seo_description', 'P'))),
            'contribute' => 0,
        );
        // hook admin_cms_page_control_get_post_after.php
        return $post;
    }

    // 删除单页分类
    public function del() {
        // hook admin_cms_page_control_del_before.php

        $cid = intval(R('cid', 'P'));

        $r = $this->category->xdelete($cid);

        // 删除缓存
        empty($r['err']) && $this->runtime->truncate();

        // hook admin_cms_page_control_del_after.php

        E($r['err'], $r['msg']);
    }

    // 读取上级分类
    public function get_category_upid() {
        $data['upid'] = '<select name="upid" id="upid">'.$this->category->get_category_upid(intval(R('mid')), intval(R('upid')), intval(R('noid'))).'</select>';
        echo json_encode($data);
        exit;
    }

    //批量添加单页分类
    public function batch_add(){
        if(empty($_POST)){
            // hook admin_cms_page_control_batch_add_before.php

            $def_mid = 1;
            $def_model = $this->models->get($def_mid);

            $data = array(
                'mid'=>$def_mid,
                'type'=>0,
                'count'=>0,
                'son_cate'=>0,
                'cate_tpl'=>$def_model['cate_tpl'],
            );
            // hook admin_cms_page_control_batch_add_data_after.php
            $this->assign('data',$data);

            $this->display('cms_page_batch_add.htm');
        }else{
            $category = &$this->category;
            // hook admin_cms_page_control_batch_add_post_before.php

            $mid = 1;
            $upid = (int)R('upid', 'P');
            $categorys = trim(R('categorys', 'P'));
            $orderby = (int)R('orderby', 'P');
            $cate_tpl = trim(R('cate_tpl', 'P'));
            $page_content = '';
            // hook admin_cms_page_control_batch_add_post_after.php

            empty($cate_tpl) AND E(1, lang('cate_tpl_no_empty'));
            empty($categorys) AND E(1, lang('category_no_empty'));

            $categorys_arr = explode(PHP_EOL, $categorys);
            empty($categorys_arr) AND E(1, lang('category_no_empty'));

            $succ = $fail = 0;
            foreach ($categorys_arr as $c){
                if( empty($c) ){
                    continue;
                }
                $c_arr = explode('#', $c);
                $name = trim($c_arr[0]);
                $alias = isset($c_arr[1]) ? trim($c_arr[1]) : pinyin::getpinyin($name);
                !empty($alias) AND $alias = strtolower($alias);

                if(empty($alias)){
                    $fail++;
                    continue;
                }elseif($err = $category->check_alias($alias)) {
                    $fail++;
                    continue;
                }

                $post = array(
                    'mid'=>$mid,
                    'upid'=>$upid,
                    'name'=>$name,
                    'alias'=>$alias,
                    'cate_tpl'=>$cate_tpl,
                    'orderby'=>$orderby
                );
                $maxid = $category->create($post);
                if( $maxid ) {
                    $pagedata = array('content' => $page_content);
                    if(!$this->cms_page->set($maxid, $pagedata)) {
                        $category->delete($maxid);
                        $fail++;
                    }
                    $succ++;
                }else{
                    $fail++;
                }
            }

            // hook admin_cms_page_control_batch_add_post_success.php

            // 删除缓存
            $this->runtime->truncate();
            E(0, lang('add_successfully'));
        }
    }

    // hook admin_cms_page_control_after.php
}