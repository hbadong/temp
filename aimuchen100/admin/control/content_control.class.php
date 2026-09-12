<?php
defined('ROOT_PATH') or exit;
// hook admin_content_control_before.php
class content_control extends admin_control{
    public $_mid = 2;
    public $_table = 'article';
    public $_name = '文章';
    public $_use_site = FALSE;   // 文章模型启用站点归属（渐进式内容站点隔离）
    public $_site_list = array();

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
        $this->cms_content->table = 'cms_'.$this->_table;

        $this->assign('mid',$this->_mid);
        $this->assign('table',$this->_table);
        $this->assign('name',$this->_name);

        // 渐进式内容站点隔离：仅文章模型挂站点归属（其它自定义模型保持全局）
        $this->_use_site = ($this->_table === 'article');
        if($this->_use_site) {
            $site_rows = $this->site_manager->get_list(1);
            foreach((array)$site_rows as $sr) {
                $this->_site_list[(int)$sr['sid']] = $sr['site_name'] . ' (' . $sr['domain'] . ')';
            }
            ksort($this->_site_list);
        }
        $cur_site_id = max(0, (int)R('site_id', 'R'));
        $this->assign('use_site', $this->_use_site);
        $this->assign('site_list', $this->_site_list);
        $this->assign('site_id', $cur_site_id);
        // hook admin_content_control_construct_after.php
    }

    // 内容管理
    public function index() {
        // hook admin_content_control_index_before.php

        // 获取分类下拉框
        $cid = intval(R('cid'));
        $cidhtml = $this->category->get_cidhtml_by_mid($this->_mid, $cid, lang('all'));
        $this->assign('cidhtml', $cidhtml);

        if( $_ENV['_config']['admin_lang'] == 'zh-cn' ){
            $add_name = '添加'.$this->_name;
            $edit_name = '编辑'.$this->_name;
        }else{
            $add_name = 'Add '.$this->_name;
            $edit_name = 'Edit '.$this->_name;
        }
        $this->assign('add_content', $add_name);
        $this->assign('edit_content', $edit_name);

        //操作列宽度和操作列的菜单初始化
        $opt_width = 140;
        $tr_btn = '';

        //表格显示列表
        $cols = "{type: 'checkbox', width: 50, fixed: 'left'},";
        $cols .= "{field: 'id', width: 100, title: 'ID', sort: true, align: 'center'},";
        $cols .= "{field: 'title', title: '".lang('title')."', edit: 'text'},";
        $cols .= "{field: 'category', title: '".lang('category')."'},";
        $cols .= "{field: 'tags_fmt', title: '".lang('tag')."'},";
        $cols .= "{field: 'flag_fmt', minwidth: 170, title: '".lang('flag')."', align: 'center'},";
        $cols .= "{field: 'author', width: 120, title: '".lang('author')."', align: 'center', edit: 'text'},";
        if($this->_use_site){
            $cols .= "{field: 'site_name', width: 160, title: '".lang('site')."', align: 'center'},";
        }
        $cols .= "{field: 'date', width: 145, title: '".lang('date')."', align: 'center'},";
        // hook admin_content_control_index_cols_after.php
        $cols .= "{title: '".lang('opt')."', width: {$opt_width}, toolbar: '#currentTableBar', align: 'center'}";

        $tr_btn .= '<a class="layui-btn layui-btn-xs" lay-event="edit">'.lang('edit').'</a><a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="view">'.lang('view').'</a><a class="layui-btn layui-btn-xs layui-btn-danger" lay-event="delete">'.lang('delete').'</a>';

        // hook admin_content_control_index_after.php
        $this->assign('cols', $cols);
        $this->assign('tr_btn', $tr_btn);
        $this->display();
    }

    //获取内容table数据
    public function get_list(){
        //分页
        $page = isset( $_REQUEST['page'] ) ? intval($_REQUEST['page']) : 1;
        $pagenum = isset( $_REQUEST['limit'] ) ? intval($_REQUEST['limit']) : 15;

        //获取查询条件
        $cid = isset( $_REQUEST['cid'] ) ? intval($_REQUEST['cid']) : 0;
        $keyword = isset( $_REQUEST['keyword'] ) ? trim($_REQUEST['keyword']) : '';
        if($keyword) {
            $keyword = urldecode($keyword);
            $keyword = safe_str($keyword);
        }

        //组合查询条件
        $where = array();
        if( $cid ){
            $where['cid'] = $cid;
        }
        if( $keyword ){
            $where['title'] = array('LIKE'=>$keyword);
        }
        // 站点筛选（渐进式内容站点隔离）
        $site_id = isset( $_REQUEST['site_id'] ) ? (int)$_REQUEST['site_id'] : 0;
        if( $this->_use_site && $site_id ){
            $where['site_id'] = $site_id;
        }
        // hook admin_content_control_get_list_where_after.php
        //数据量
        if( $where ){
            $total = $this->cms_content->find_count($where);
        }else{
            $total = $this->cms_content->count();
        }

        //页数
        $maxpage = max(1, ceil($total/$pagenum));
        $page = min($maxpage, max(1, $page));

        //对应模型所有分类
        $allcategory = $this->category->get_category_db_mid($this->_mid);

        //所有属性
        if($_ENV['_config']['admin_lang'] == 'zh-cn'){
            $flag_arr = $this->cms_content->flag_arr;
        }else{
            $flag_arr = $this->cms_content->flag_arr_en;
        }

        //数据列
        $data_arr = array();
        $cms_arr = $this->cms_content->list_arr($where, 'id', -1, ($page-1)*$pagenum, $pagenum, $total);
        foreach($cms_arr as &$v) {
            $this->cms_content->format($v, $this->_mid);

            $v['category'] = isset($allcategory[$v['cid']]) ? $allcategory[$v['cid']]['name'] : lang('unknown');

            // 站点列显示
            if($this->_use_site){
                $v['site_name'] = isset($this->_site_list[$v['site_id']]) ? $this->_site_list[$v['site_id']] : '站点' . (int)$v['site_id'];
            }

            //标签格式化显示
            $v['tags_fmt'] = '';
            $tags_fmt_arr = array();
            if(isset($v['tag_arr']) && $v['tag_arr']){
                foreach ($v['tag_arr'] as $tag){
                    $tags_fmt_arr[] = '<a class="layui-badge-rim" target="_blank" href="'.$tag['url'].'">'.$tag['name'].'</a>';
                }
                $v['tags_fmt'] = implode(' ', $tags_fmt_arr);
            }

            //属性格式化显示
            $v['flag_fmt'] = '';
            $flag_html_arr = array();
            if($v['flags']){
                $has_flags = explode(',', $v['flags']);
                foreach ($flag_arr as $f=>$fname){
                    if( in_array($f, $has_flags) ){
                        $badge_class = 'layui-badge layui-bg-blue';
                        $has_flag = 1;
                    }else{
                        $badge_class = 'layui-badge layui-bg-gray';
                        $has_flag = 0;
                    }
                    $js_param = "{$v['cid']},{$v['id']},$f,$has_flag";
                    $flag_html_arr[] = '<a class="'.$badge_class.'" href="javascript:setflag('.$js_param.');">'.$fname.'</a>';
                }
            }else{
                foreach ($flag_arr as $f=>$fname){
                    $js_param = "{$v['cid']},{$v['id']},$f,0";
                    $flag_html_arr[] = '<a class="layui-badge layui-bg-gray" href="javascript:setflag('.$js_param.');">'.$fname.'</a>';
                }
            }
            $v['flag_fmt'] = implode(' ', $flag_html_arr);

            // hook admin_content_control_get_list_foreach.php
            $data_arr[] = $v;   //排序需要索引从0开始
        }
        unset($cms_arr);
        // hook admin_content_control_get_list_data_arr_after.php

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
        // hook admin_content_control_set_before.php
        if( !empty($_POST) ){
            $field = trim( R('field','P') );
            $id = intval( R('id','P') );
            $value = trim( R('value','P') );

            $content = $this->cms_content->get($id);
            empty($content) && E(1, lang('data_no_exists'));

            //标题重复性校验
            if($field == 'title' && $value != $content['title']){
                $cfg = $this->kv->xget('cfg');
                if(isset($cfg['open_title_check']) && $cfg['open_title_check'] && $this->cms_content->find_fetch(array('title'=>$value), array('id' => 1), 0, 1)){
                    E(1, lang('title_exists'));
                }
            }

            $content[$field] = $value;

            // hook admin_content_control_set_update_before.php

            if(!$this->cms_content->update($content)) {
                // hook admin_content_control_set_update_failed.php
                E(1, lang('edit_failed'));
            }
            // hook admin_content_control_set_after.php

            E(0, lang('edit_field_successfully', array('field'=>$field)) );
        }
    }

    //添加
    public function add() {
        // hook admin_content_control_add_before.php

        $uid = $this->_user['uid'];
        if(empty($_POST)) {
            $habits = (array)$this->kv->get($this->_table.'_user_habits_uid_'.$uid);
            //默认值
            $data = array(
                'id'=>0,
                'cid'=>isset($habits['last_add_cid']) ? (int)$habits['last_add_cid'] : 0,
                'title'=>'',
                'alias'=>'',
                'tags'=>'',
                'intro'=>'',
                'pic'=>'',
                'flags'=>'',
                'author'=>empty($this->_user['author'] ) ? $this->_user['username'] : $this->_user['author'],
                'source'=>'',
                'iscomment'=>0, //允许评论
                'seo_title'=>'',
                'seo_keywords'=>'',
                'seo_description'=>'',
                'jumpurl'=>'',
                'show_tpl'=>'',
                'site_id'=>1,
                'content'=>'',
                'views'=>0,
                'dateline'=>$_ENV['_time'],
                'max_dateline'=>$_ENV['_time']
            );
            // hook admin_content_control_add_def_data_after.php

            $this->assign('data', $data);

            $input = $this->get_input($data);
            // hook admin_content_control_add_get_input_after.php
            $this->assign('input',$input);

            $edit_cid_id = '-mid-'.$this->_mid;
            $this->assign('edit_cid_id', $edit_cid_id);

            // hook admin_content_control_add_after.php

            $this->display('content_set.htm');
        }else{
            // hook admin_content_control_add_post_before.php

            // 渐进式内容站点隔离：新增内容记录站点归属
            if($this->_use_site){
                $_POST['site_id'] = max(1, (int)R('site_id', 'P'));
            }
            $res = $this->cms_content->xadd($_POST, $this->_user, $this->_table);
            if( $res['err'] ){
                E(1, $res['msg']);
            }

            // 记住最后一次发布的分类ID。
            $habits = (array) $this->kv->get($this->_table.'_user_habits_uid_'.$uid);
            $habits['last_add_cid'] = (int)R('cid', 'P');
            $this->kv->set($this->_table.'_user_habits_uid_'.$uid, $habits);

            // hook admin_content_control_add_post_after.php

            E(0, $res['msg']);
        }
    }

    protected function get_input($data = array())
    {
        $input = array();

        $input['cid'] = $this->category->get_cidhtml_by_mid($this->_mid, $data['cid'], lang('select_category'));

        $input['title'] = form::get_text('title', $data['title'], '', 'placeholder="'.lang('title').'" maxlength="'.$this->cms_content->_field_length['title'].'" required="required" lay-verify="required" lay-reqtext="'.lang('title_no_empty').'"');
        $input['alias'] = form::get_text('alias', $data['alias'], '', 'placeholder="'.lang('alias').'" maxlength="'.$this->cms_content->_field_length['alias'].'"');

        $input['intro'] = form::get_textarea('intro', $data['intro'], '', 'placeholder="'.lang('intro').'" maxlength="'.$this->cms_content->_field_length['intro'].'" style="min-height: 50px;"');
        $input['tags'] = form::get_text('tags', $data['tags'], '', 'placeholder="'.lang('tag_tips').'" maxlength="'.$this->cms_content->_field_length['tags'].'"');

        $input['pic'] = form::get_text('pic', $data['pic'], '', 'id="pic" placeholder="'.lang('thumb').'" maxlength="'.$this->cms_content->_field_length['pic'].'"');

        $input['author'] = form::get_text('author', $data['author'], '', 'placeholder="'.lang('author').'" maxlength="'.$this->cms_content->_field_length['author'].'"');
        $input['source'] = form::get_text('source', $data['source'], '', 'placeholder="'.lang('source').'" maxlength="'.$this->cms_content->_field_length['source'].'"');

        $input['views'] = form::get_number('views', $data['views'], '', 'placeholder="'.lang('views').'"');

        $iscomment_arr = array(0=>lang('enable'), 1=>lang('disable'));
        $input['iscomment'] = form::get_radio_layui('iscomment', $iscomment_arr, $data['iscomment']);

        $input['seo_title'] = form::get_text('seo_title', $data['seo_title'], '', 'placeholder="'.lang('seo_title').'" maxlength="'.$this->cms_content->_field_length['seo_title'].'"');
        $input['seo_keywords'] = form::get_text('seo_keywords', $data['seo_keywords'], '', 'placeholder="'.lang('seo_keywords').'" maxlength="'.$this->cms_content->_field_length['seo_keywords'].'"');
        $input['seo_description'] = form::get_text('seo_description', $data['seo_description'], '', 'placeholder="'.lang('seo_description').'" maxlength="'.$this->cms_content->_field_length['seo_description'].'"');
        $input['jumpurl'] = form::get_text('jumpurl', $data['jumpurl'], '', 'placeholder="'.lang('jump_url').'" maxlength="'.$this->cms_content->_field_length['jumpurl'].'"');
        $input['show_tpl'] = form::get_text('show_tpl', $data['show_tpl'], '', 'id="show_tpl" placeholder="'.lang('show_tpl').'" maxlength="'.$this->category->_field_length['show_tpl'].'"');

        //属性
        $input['flags'] = $this->cms_content->flag_html($data['flags'], $_ENV['_config']['admin_lang']);

        if(isset($data['content'])){
            $input['content'] = form::get_textarea('content', $data['content'], '', 'placeholder="'.lang('content').'" id="content" required autocomplete="off" style="min-height: 50px;"');
        }

        if(isset($data['dateline']) && $data['dateline']){
            $data['dateline'] = date('Y-m-d H:i:s', $data['dateline']);
            $input['dateline'] = form::get_text('dateline', $data['dateline'], '', 'id="dateline" placeholder="'.lang('date').'"');
        }

        //渐进式内容站点隔离：站点归属下拉
        if($this->_use_site){
            $site_opt = '';
            foreach($this->_site_list as $sid => $sname){
                $sel = (int)$data['site_id'] == (int)$sid ? ' selected' : '';
                $site_opt .= '<option value="' . (int)$sid . '"' . $sel . '>' . htmlspecialchars($sname) . '</option>';
            }
            $input['site_id'] = '<select name="site_id" class="layui-input" lay-search><option value="0">-- '.lang('select_site').' --</option>' . $site_opt . '</select>';
        }

        // hook admin_content_control_get_input_after.php
        return $input;
    }

    // 编辑
    public function edit(){
        // hook admin_content_control_edit_before.php
        $uid = $this->_user['uid'];
        if(empty($_POST)) {
            $id = intval(R('id'));
            $cid = intval(R('cid'));

            // 读取内容
            $this->cms_content->table = 'cms_'.$this->_table;
            $data = $this->cms_content->get($id);
            if(empty($data)) $this->message(0, lang('data_error'), -1);

            //获取内容附表数据
            $data2 = $this->cms_content_data->get_cms_content_data($id, $this->_table);
            //获取内容浏览量
            $data3 = $this->cms_content_views->get_cms_content_views($id, $this->_table);
            $data2 === FALSE && $data2 = array();
            $data3 === FALSE && $data3 = array();

            // hook admin_content_control_edit_data_before.php

            $data = array_merge($data, $data2, $data3);
            //$data['content'] = isset($data['content']) ? htmlspecialchars($data['content']) : '';	form get_textarea 里面已经有了
            $data['tags'] = implode(',', (array)_json_decode($data['tags']));
            $data['intro'] = str_replace('<br />', "\n", strip_tags($data['intro'], '<br>'));
            $data['max_dateline'] = $_ENV['_time'];
            // hook admin_content_control_edit_data_assign_before.php
            $this->assign('data', $data);

            $input = $this->get_input($data);
            // hook admin_content_control_edit_get_input_after.php
            $this->assign('input',$input);

            $edit_cid_id = '-mid-'.$this->_mid.'-cid-'.$data['cid'].'-id-'.$data['id'];
            $this->assign('edit_cid_id', $edit_cid_id);

            //当前内容在列表的第几页？用于编辑后定位到对应的列表页
            $currPage = max(1, (int)R('currpage', 'G'));
            $this->assign('currPage', $currPage);

            // hook admin_content_control_edit_after.php

            $this->display('content_set.htm');
        }else{
            // hook admin_content_control_edit_post_before.php

            //渐进式内容站点隔离：编辑时同步站点归属
            if($this->_use_site){
                $_POST['site_id'] = max(1, (int)R('site_id', 'P'));
            }
            $res = $this->cms_content->xedit($_POST, $this->_user, $this->_table);
            if( $res['err'] ){
                E(1, $res['msg']);
            }

            // hook admin_content_control_edit_post_after.php
            E(0, $res['msg']);
        }
    }

    // 删除
    public function del() {
        // hook admin_content_control_del_before.php

        $id = (int) R('id', 'P');
        $cid = (int) R('cid', 'P');

        empty($id) && E(1, lang('data_error'));
        empty($cid) && E(1, lang('data_error'));

        // hook admin_content_control_del_after.php

        $err = $this->cms_content->xdelete($this->_table, $id, $cid);
        if($err) {
            E(1, $err);
        }else{
            // hook admin_content_control_del_success.php
            E(0, lang('delete_successfully'));
        }
    }

    // 批量删除
    public function batch_del() {
        // hook admin_content_control_batch_del_before.php

        $id_arr = R('id_arr', 'P');
        if(!empty($id_arr) && is_array($id_arr)) {
            $err_num = 0;
            foreach($id_arr as $v) {
                $err = $this->cms_content->xdelete($this->_table, $v[0], $v[1]);
                if($err) $err_num++;

                // hook admin_content_control_batch_del_foreach.php
            }

            if($err_num) {
                E(1, $err_num.lang('num_del_failed'));
            }else{
                // hook admin_content_control_batch_del_success.php
                E(0, lang('delete_successfully'));
            }
        }else{
            E(1, lang('data_error'));
        }
    }

    //设置和取消某内容的某个属性
    public function set_flag(){
        if($_POST){
            $cid = (int)R('cid', 'P');
            $id = (int)R('id', 'P');
            $flag = (int)R('flag', 'P');
            $exist = (int)R('exist', 'P');
            // hook admin_content_control_set_flag_before.php

            $r = $this->cms_content_flag->set_flag($this->_table, $cid, $id, $flag, $exist);
            if($r){
                // hook admin_content_control_set_flag_after.php
                E(0, lang('opt_successfully'));
            }else{
                E(1, lang('opt_failed'));
            }
        }else{
            E(1, lang('data_error'));
        }
    }

    //批量设置属性
    public function batch_flags(){
        if($_POST){
            $id_arr = R('id_arr', 'P');
            // hook admin_content_control_batch_flags_before.php
            if(!empty($id_arr) && is_array($id_arr)) {
                $flag_arr = R('flags', 'P');
                if( !empty($flag_arr) && is_array($flag_arr) ){
                    foreach($id_arr as $v) {
                        $this->cms_content_flag->xflags($this->_table, $v[0], $v[1], $flag_arr);
                    }
                    E(0, lang('opt_successfully'));
                }else{
                    E(1, lang('data_error'));
                }
            }else{
                E(1, lang('data_error'));
            }
        }else{
            E(1, lang('data_error'));
        }
    }

    public function get_category_html(){
        // hook admin_content_control_get_category_html_before.php
        //分类下拉框
        $cidhtml = $this->category->get_cidhtml_by_mid($this->_mid, 0, lang('select_category'));
        $this->assign('cidhtml', $cidhtml);
        $this->display();
    }

    //批量移动
    public function batch_move(){
        if($_POST){
            $id_arr = R('id_arr', 'P');
            // hook admin_content_control_batch_move_before.php
            if(!empty($id_arr) && is_array($id_arr)) {
                $cid = (int)R('cid', 'P');
                if(empty($cid)){
                    E(1, lang('please_select_category'));
                }

                foreach($id_arr as $v) {
                    if($v[1] != $cid){
                        $this->cms_content->xmove($this->_table, $v[0], $v[1], $cid);
                    }
                }
                E(0, lang('opt_successfully'));
            }else{
                E(1, lang('data_error'));
            }
        }else{
            E(1, lang('data_error'));
        }
    }

    // hook admin_content_control_after.php
}