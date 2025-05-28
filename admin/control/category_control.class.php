<?php
defined('ROOT_PATH') or exit;
class category_control extends admin_control{

    // 分类管理
    public function index() {
        // hook admin_category_control_index_before.php

        //表格显示列表
        $cols = "{field: 'cid', width: 80, title: '".lang('cid')."', align: 'center'},";
        $cols .= "{field: 'name', minWidth: 150, title: '".lang('cate_name')."'},";
        $cols .= "{field: 'alias', title: '".lang('alias')."', edit: 'text'},";
        $cols .= "{field: 'modelname', width: 100, title: '".lang('model')."', align: 'center'},";
        $cols .= "{field: 'type', width: 85, title: '".lang('type')."', align: 'center', templet: '#cate-type'},";
        $cols .= "{field: 'count', width: 130, title: '".lang('cate_count')."', align: 'center'},";
        $cols .= "{field: 'cate_tpl', title: '".lang('cate_tpl')."', edit: 'text'},";
        $cols .= "{field: 'show_tpl', title: '".lang('show_tpl')."', edit: 'text'},";
        $cols .= "{field: 'orderby', width: 80, title: '".lang('orderby')."', edit: 'number', align: 'center'},";
        // hook admin_category_control_index_cols_after.php
        $cols .= "{title: '".lang('opt')."', width: 145, toolbar: '#currentTableBar', align: 'center'}";

        // hook admin_category_control_index_after.php
        $this->assign('cols', $cols);
        $this->display();
    }

    //获取分类（排除单页）
    public function get_list(){
        $where = array('mid'=>array('>'=>1));
        // hook admin_category_control_get_list_before.php
        $models = $this->models->get_models();

        $tmp = $this->category->find_fetch($where, array('orderby'=>1,'cid'=>1));
        $category_arr = array();
        foreach ($tmp as &$v){
            if( $v['upid'] == 0 ){  //顶级的父ID 必须为 -1
                $v['upid'] = -1;
            }
            $mid =  $v['mid'];
            $m_key = 'models-mid-'.$mid;

            if( $_ENV['_config']['admin_lang'] == 'zh-cn' ){
                $v['modelname'] = isset($models[$m_key]) ? $models[$m_key]['name'] : '未知';
            }else{
                $v['modelname'] = isset($models[$m_key]) ? ucfirst($models[$m_key]['tablename']) : 'unknow';
            }

            $v['url'] = $this->category->category_url($v);
            $category_arr[] = $v;
        }
        $total = $this->category->find_count($where);
        // hook admin_category_control_get_list_after.php
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
        // hook admin_category_control_set_field_before.php
        if( !empty($_POST) ){
            $field = trim( R('field','P') );
            $cid = intval( R('cid','P') );
            $value = trim( R('value','P') );

            ($field != 'orderby' && empty($value)) && E(1, lang('filed_not_empty', array('field'=>$field)));

            $categorys = $this->category->get($cid);
            empty($categorys) && E(1, lang('data_no_exists'));

            //单页分类
            $categorys['mid'] == 1 && E(1, lang('data_error'));

            $change_alias = 0;
            if($field == 'alias'){  //修改别名 需要判断
                $value = strtolower($value);
                if($categorys['alias'] != $value){
                    if($err = $this->category->check_alias($value)) {
                        E(1, $err['msg']);
                    }
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

            // hook admin_category_control_set_field_after.php

            // 删除缓存
            $this->runtime->truncate();

            E(0, lang('edit_field_successfully', array('field'=>$field)) );
        }
    }

    //添加分类
    public function add(){
        if(empty($_POST)){
            // hook admin_category_control_add_before.php
            $mod_name = array();
            $models = $this->models->get_models();
            foreach ($models as $k=>$v){
                //排除单页
                if($v['mid'] == 1){
                    unset($models[$k]);
                    continue;
                }

                if( $_ENV['_config']['admin_lang'] == 'zh-cn' ){
                    $mod_name[$v['mid']] = $v['name'];
                }else{
                    $mod_name[$v['mid']] = ucfirst($v['tablename']);
                }
            }
            $this->assign('mod_name', $mod_name);

            $models_json = json_encode($models);
            $this->assign('models', $models_json);

            //默认为文章模型
            $def_mid = 2;
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
                'show_tpl'=>$def_model['show_tpl'],
                'count'=>0,
                'orderby'=>0,
                'seo_title'=>'',
                'seo_keywords'=>'',
                'seo_description'=>'',
                'contribute'=>0,
                'son_cate'=>0,
                'seo_title_rule'=>'',
                'seo_keywords_rule'=>'',
                'seo_description_rule'=>''
            );
            // hook admin_category_control_add_data_after.php
            $this->assign('data',$data);

            $input = $this->get_input($data);
            // hook admin_category_control_add_get_input_after.php
            $this->assign('input',$input);

            $this->display('category_set.htm');
        }else{
            $post = $this->get_post();
            $category = &$this->category;
            // hook admin_category_control_add_post_before.php

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

            //子分类允许投稿时，父分类也设置成允许投稿
            if($post['contribute'] && $post['upid']){
                $category->update(array('cid'=>$post['upid'], 'contribute'=>1));
            }

            // hook admin_category_control_add_post_success.php

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

        $input['seo_title_rule'] = form::get_text('seo_title_rule', $data['seo_title_rule'], '', 'placeholder="'.lang('seo_title').'" maxlength="'.$this->category->_field_length['seo_title_rule'].'"');
        $input['seo_keywords_rule'] = form::get_text('seo_keywords_rule', $data['seo_keywords_rule'], '', 'placeholder="'.lang('seo_keywords').'" maxlength="'.$this->category->_field_length['seo_keywords_rule'].'"');
        $input['seo_description_rule'] = form::get_text('seo_description_rule', $data['seo_description_rule'], '', 'placeholder="'.lang('seo_description').'" maxlength="'.$this->category->_field_length['seo_description_rule'].'"');


        // hook admin_category_control_get_input_after.php
        return $input;
    }

    //编辑分类
    public function edit(){
        if(empty($_POST)){
            // hook admin_category_control_edit_before.php
            $cid = (int)R('cid','G');
            $data = $this->category->get($cid);
            if(empty($data)){
                $this->message(0, lang('data_no_exists'));
            }elseif ($data['mid'] == 1){
                $this->message(0, lang('data_error'));
            }

            $mod_name = array();
            $models = $this->models->get_models();
            foreach ($models as $k=>$v){
                //排除单页
                if($v['mid'] == 1){
                    unset($models[$k]);
                    continue;
                }

                if( $_ENV['_config']['admin_lang'] == 'zh-cn' ){
                    $mod_name[$v['mid']] = $v['name'];
                }else{
                    $mod_name[$v['mid']] = ucfirst($v['tablename']);
                }
            }
            $this->assign('mod_name', $mod_name);

            $models_json = json_encode($models);
            $this->assign('models', $models_json);

            // 为频道时，检测是否有下级分类
            if($data['type'] == 1 && $this->category->find_fetch_key(array('upid' => $data['cid']), array(), 0, 1)) {
                $data['son_cate'] = 1;
            }else{
                $data['son_cate'] = 0;
            }
            // hook admin_category_control_edit_data_after.php
            $this->assign('data',$data);

            $input = $this->get_input($data);
            // hook admin_category_control_edit_get_input_after.php
            $this->assign('input',$input);

            $this->display('category_set.htm');
        }else{
            $post = $this->get_post();
            $category = &$this->category;
            // hook admin_category_control_edit_post_before.php

            // 检查基本参数是否填写
            if($err = $category->check_base($post)) {
                E(1, $err['msg'], $err['name']);
            }

            $data = $category->get($post['cid']);
            if(empty($data)){
                E(1, lang('data_no_exists'));
            }elseif ($data['mid'] == 1){
                E(1, lang('data_error'));
            }

            // 检查分类是否符合编辑条件
            if($err = $category->check_is_edit($post, $data)) {
                E(1, $err['msg'], $err['name']);
            }

            if($post['alias'] != $data['alias']) {
                $err = $category->check_alias($post['alias']);
                if($err) {
                    E(1, $err['msg'], $err['name']);
                }

                // 修改导航中的分类的别名
                $category->update_navigate_alias($post);
            }

            $post['count'] = $data['count'];
            if(!$category->update($post)) {
                E(1, lang('edit_failed'));
            }

            //子分类允许投稿时，父分类也设置成允许投稿
            if($post['contribute'] && $post['upid']){
                $category->update(array('cid'=>$post['upid'], 'contribute'=>1));
            }

            // hook admin_category_control_edit_post_success.php

            // 删除缓存
            $this->runtime->truncate();
            E(0, lang('edit_successfully'));
        }
    }

    //获取POST数据
    private function get_post(){
        $mid = intval(R('mid', 'P'));
        $post = array(
            'cid' => intval(R('cid', 'P')),
            'mid' => $mid,
            'type' => intval(R('type', 'P')),
            'upid' => intval(R('upid', 'P')),
            'name' => trim(strip_tags(R('name', 'P'))),
            'pic' => trim(R('pic', 'P')),
            'alias' => strtolower(trim(R('alias', 'P'))),
            'intro' => trim(strip_tags(R('intro', 'P'))),
            'cate_tpl' => trim(strip_tags(R('cate_tpl', 'P'))),
            'show_tpl' => trim(strip_tags(R('show_tpl', 'P'))),
            'count' => 0,
            'orderby' => intval(R('orderby', 'P')),
            'seo_title' => trim(strip_tags(R('seo_title', 'P'))),
            'seo_keywords' => trim(strip_tags(R('seo_keywords', 'P'))),
            'seo_description' => trim(strip_tags(R('seo_description', 'P'))),
            'contribute' => $mid == 1 ? 0 : intval(R('contribute', 'P'))
        );

        //列表分类
        if($post['mid'] > 1 && empty($post['type'])){
            $post['seo_title_rule'] = strip_tags(R('seo_title_rule', 'P'));
            $post['seo_keywords_rule'] = strip_tags(R('seo_keywords_rule', 'P'));
            $post['seo_description_rule'] = strip_tags(R('seo_description_rule', 'P'));
        }

        // hook admin_category_control_get_post_after.php
        return $post;
    }

    // 删除分类
    public function del() {
        // hook admin_category_control_del_before.php

        $cid = intval(R('cid', 'P'));

        $r = $this->category->xdelete($cid);

        // 删除缓存
        empty($r['err']) && $this->runtime->truncate();

        // hook admin_category_control_del_after.php

        E($r['err'], $r['msg']);
    }

    // 读取上级分类
    public function get_category_upid() {
        $data['upid'] = '<select name="upid" id="upid">'.$this->category->get_category_upid(intval(R('mid')), intval(R('upid')), intval(R('noid'))).'</select>';
        echo json_encode($data);
        exit;
    }

    //批量添加分类
    public function batch_add(){
        if(empty($_POST)){
            // hook admin_category_control_batch_add_before.php
            $mod_name = array();
            $models = $this->models->get_models();
            foreach ($models as $k=>$v){
                //排除单页
                if($v['mid'] == 1){
                    unset($models[$k]);
                    continue;
                }

                if( $_ENV['_config']['admin_lang'] == 'zh-cn' ){
                    $mod_name[$v['mid']] = $v['name'];
                }else{
                    $mod_name[$v['mid']] = ucfirst($v['tablename']);
                }
            }
            $this->assign('mod_name', $mod_name);

            $models_json = json_encode($models);
            $this->assign('models', $models_json);

            //默认为文章模型
            $def_mid = 2;
            $def_model = $this->models->get($def_mid);

            $data = array(
                'mid'=>$def_mid,
                'type'=>0,
                'count'=>0,
                'son_cate'=>0,
                'cate_tpl'=>$def_model['cate_tpl'],
                'show_tpl'=>$def_model['show_tpl'],
                'contribute'=>0,
            );
            // hook admin_category_control_batch_add_data_after.php
            $this->assign('data',$data);

            $this->display('category_batch_add.htm');
        }else{
            $category = &$this->category;
            // hook admin_category_control_batch_add_post_before.php

            $mid = (int)R('mid', 'P');
            $type = (int)R('type', 'P');
            $upid = (int)R('upid', 'P');
            $orderby = (int)R('orderby', 'P');
            $contribute = (int)R('contribute', 'P');

            $cate_tpl = trim(R('cate_tpl', 'P'));
            $show_tpl = trim(R('show_tpl', 'P'));

            $categorys = trim(R('categorys', 'P'));

            // hook admin_category_control_batch_add_post_after.php

            empty($mid) AND E(1, lang('select_cate_model'));
            empty($cate_tpl) AND E(1, lang('cate_tpl_no_empty'));
            empty($show_tpl) AND E(1, lang('show_tpl_no_empty'));

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
                    'type'=>$type,
                    'upid'=>$upid,
                    'name'=>$name,
                    'alias'=>$alias,
                    'cate_tpl'=>$cate_tpl,
                    'show_tpl'=>$show_tpl,
                    'orderby'=>$orderby,
                    'contribute'=>$contribute
                );
                $maxid = $category->create($post);
                if( $maxid ) {
                    $succ++;
                }else{
                    $fail++;
                }
            }

            //子分类允许投稿时，父分类也设置成允许投稿
            if($contribute && $upid){
                $category->update(array('cid'=>$upid, 'contribute'=>1));
            }

            // hook admin_category_control_batch_add_post_success.php

            // 删除缓存
            $this->runtime->truncate();
            E(0, lang('add_successfully'));
        }
    }

    // hook admin_category_control_after.php
}