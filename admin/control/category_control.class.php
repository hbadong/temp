<?php
defined('ROOT_PATH') or exit;
class category_control extends admin_control{

    // 分类管理
    public function index() {
        // hook admin_category_control_index_before.php
        $_ENV['_category_class'] = &$this->category;
        $_cfg = $this->runtime->xget();
        $this->assign('_cfg', $_cfg);

        $category_arr = $this->category->get_category();
        $this->assign('category_arr', $category_arr);

        // hook admin_category_control_index_after.php

        $this->display();
    }

    //获取分类
    public function get_list(){
        // hook admin_category_control_get_list_before.php
        $models = $this->models->get_models();

        $tmp = $this->category->find_fetch(array(), array('orderby'=>1));
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
        $total = $this->category->count();
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

            empty($value) && E(1, lang('filed_not_empty', array('field'=>$field)));

            $categorys = $this->category->get($cid);
            empty($categorys) && E(1, lang('data_no_exists'));

            if($field == 'alias'){  //修改别名 需要判断
                $value = strtolower($value);
                if($categorys['alias'] != $value){
                    if($err = $this->category->check_alias($value)) {
                        E(1, $err['msg']);
                    }
                }
            }

            $categorys[$field] = $value;

            if(!$this->category->update($categorys)) {
                E(1, lang('edit_failed'));
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
            foreach ($models as $v){
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
                'upid'=>0,
                'cid'=>0,
                'type'=>0,
                'orderby'=>0,
                'count'=>0,
                'son_cate'=>0,
                'cate_tpl'=>$def_model['cate_tpl'],
                'show_tpl'=>$def_model['show_tpl'],
                'contribute'=>0,
            );
            // hook admin_category_control_add_data_after.php
            $this->assign('data',$data);

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
            // 单页时
            if($post['mid'] == 1) {
                $pagedata = array('content' => R('page_content', 'P'));
                if(!$this->cms_page->set($maxid, $pagedata)) {
                    E(1, lang('add_failed'));
                }
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

    //编辑分类
    public function edit(){
        if(empty($_POST)){
            // hook admin_category_control_edit_before.php
            $cid = (int)R('cid','G');
            $data = $this->category->get($cid);
            if(empty($data)){
                $this->message(0, lang('data_error'));
            }

            $mod_name = $this->models->get_name();
            $this->assign('mod_name', $mod_name);

            $models = json_encode($this->models->get_models());
            $this->assign('models', $models);

            // 读取单页内容
            if($data['mid'] == 1) {
                $data2 = $this->cms_page->get($cid);
                if($data2) $data['page_content'] = $data2['content'];
            }

            // 为频道时，检测是否有下级分类
            if($data['type'] == 1 && $this->category->find_fetch_key(array('upid' => $data['cid']), array(), 0, 1)) {
                $data['son_cate'] = 1;
            }else{
                $data['son_cate'] = 0;
            }
            // hook admin_category_control_edit_data_after.php
            $this->assign('data',$data);

            $this->display('category_set.htm');
        }else{
            $post = $this->get_post();
            $category = &$this->category;
            // hook admin_category_control_edit_post_before.php

            // 检查基本参数是否填写
            if($err = $category->check_base($post)) {
                E(1, $err['msg'], $err['name']);
            }
            $data = $category->read($post['cid']);

            // 检查分类是否符合编辑条件
            if($err = $category->check_is_edit($post, $data)) {
                E(1, $err['msg'], $err['name']);
            }

            // 别名被修改过才检查是否被使用
            if($post['alias'] != $data['alias']) {
                $err = $category->check_alias($post['alias']);
                if($err) {
                    E(1, $err['msg'], $err['name']);
                }

                // 修改导航中的分类的别名
                $navigate = $this->kv->xget('navigate');
                foreach($navigate as $k=>$v) {
                    if($v['cid'] == $post['cid']) $navigate[$k]['alias'] = $post['alias'];
                    if(isset($v['son'])) {
                        foreach($v['son'] as $k2=>$v2) {
                            if($v2['cid'] == $post['cid']) $navigate[$k]['son'][$k2]['alias'] = $post['alias'];
                        }
                    }
                }
                $this->kv->set('navigate', $navigate);

                // 修改移动端导航中的分类的别名
                $navigate_mobile = $this->kv->xget('navigate_mobile');
                foreach($navigate_mobile as $k=>$v) {
                    if($v['cid'] == $post['cid']) $navigate_mobile[$k]['alias'] = $post['alias'];
                    if(isset($v['son'])) {
                        foreach($v['son'] as $k2=>$v2) {
                            if($v2['cid'] == $post['cid']) $navigate_mobile[$k]['son'][$k2]['alias'] = $post['alias'];
                        }
                    }
                }
                $this->kv->set('navigate_mobile', $navigate_mobile);
            }

            // 这里赋值，是为了开启缓存后，编辑时更新缓存
            $post['count'] = $data['count'];
            if(!$category->update($post)) {
                E(1, lang('edit_failed'));
            }

            // 删除以前的单页数据
            if($data['mid'] == 1 && $post['mid'] > 1) {
                $this->cms_page->delete($post['cid']);
            }

            // 单页时
            if($post['mid'] == 1) {
                $pagedata = array('content' => R('page_content', 'P'));
                if(!$this->cms_page->set($post['cid'], $pagedata)) {
                    E(1, lang('edit_failed'));
                }
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
            'contribute' => $mid == 1 ? 0 : intval(R('contribute', 'P')),
        );
        // hook admin_category_control_get_post_after.php
        return $post;
    }

    // 删除分类
    public function del() {
        // hook admin_category_control_del_before.php

        $cid = intval(R('cid', 'P'));

        $data = $this->category->read($cid);

        // 检查是否符合删除条件
        if($err_msg = $this->category->check_is_del($data)) {
            E(1, $err_msg);
        }

        if(!$this->category->delete($cid)) {
            E(1, lang('delete_failed'));
        }

        if($data['mid'] == 1 && !$this->cms_page->delete($cid)) {
            E(1, lang('delete_failed'));
        }

        // 删除导航中的分类
        $navigate = $this->kv->xget('navigate');
        foreach($navigate as $k=>$v) {
            if($v['cid'] == $cid) unset($navigate[$k]);
            if(isset($v['son'])) {
                foreach($v['son'] as $k2=>$v2) {
                    if($v2['cid'] == $cid) unset($navigate[$k]['son'][$k2]);
                }
            }
        }
        $this->kv->set('navigate', $navigate);

        // 删除移动端导航中的分类
        $navigate_mobile = $this->kv->xget('navigate_mobile');
        foreach($navigate_mobile as $k=>$v) {
            if($v['cid'] == $cid) unset($navigate_mobile[$k]);
            if(isset($v['son'])) {
                foreach($v['son'] as $k2=>$v2) {
                    if($v2['cid'] == $cid) unset($navigate_mobile[$k]['son'][$k2]);
                }
            }
        }
        $this->kv->set('navigate_mobile', $navigate_mobile);

        // 删除缓存
        $this->runtime->truncate();

        // hook admin_category_control_del_after.php

        E(0, lang('delete_successfully'));
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
            foreach ($models as $v){
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

            $page_content = trim(R('page_content', 'P'));
            $categorys = trim(R('categorys', 'P'));

            // hook admin_category_control_batch_add_post_after.php

            empty($mid) AND E(1, lang('select_cate_model'));
            empty($cate_tpl) AND E(1, lang('cate_tpl_no_empty'));
            ($mid > 1 && empty($show_tpl)) AND E(1, lang('show_tpl_no_empty'));

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
                    if($mid == 1){
                        $pagedata = array('content' => $page_content);
                        if(!$this->cms_page->set($maxid, $pagedata)) {
                            $category->delete($maxid);
                            $fail++;
                        }
                    }
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