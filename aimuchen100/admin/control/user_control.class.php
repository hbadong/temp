<?php
defined('ROOT_PATH') or exit;
class user_control extends admin_control{

    // 用户管理
    public function index() {
        // hook admin_user_control_index_before.php
        $groupid = intval(R('groupid'));
        // 获取用户组下拉框
        $groupidhtml = $this->user_group->get_groupidhtml($groupid, lang('all_user_group'));
        $this->assign('groupidhtml', $groupidhtml);

        //表格显示列表
        $cols = "{type: 'checkbox', width: 50, fixed: 'left'},";
        $cols .= "{field: 'uid', width: 80, title: 'uid', sort: true, align: 'center'},";
        $cols .= "{field: 'username', title: '".lang('username')."'},";
        $cols .= "{field: 'email', title: '".lang('email')."'},";
        $cols .= "{field: 'groupname', width: 120, title: '".lang('user_group')."', align: 'center'},";
        $cols .= "{field: 'regdate', width: 145, title: '".lang('reg_time')."', align: 'center'},";
        $cols .= "{field: 'lastdate', width: 145, title: '".lang('last_login')."', align: 'center'},";
        $cols .= "{field: 'logindate', width: 145, title: '".lang('this_login')."', align: 'center'},";
        $cols .= "{field: 'logins', width: 115, title: '".lang('logins_count')."', align: 'center'},";
        $cols .= "{field: 'contents', width: 100, title: '".lang('contents_count')."', align: 'center'},";
        $cols .= "{field: 'golds', width: 70, title: '".lang('golds')."', align: 'center', edit: 'text'},";
        // hook admin_user_control_index_cols_after.php
        $cols .= "{title: '".lang('opt')."', width: 170, toolbar: '#currentTableBar', align: 'center'}";

        // hook admin_user_control_index_after.php
        $this->assign('cols', $cols);
        $this->display();
    }

    //数据列表
    public function get_list(){
        // hook admin_user_control_get_list_before.php
        //分页
        $page = isset( $_REQUEST['page'] ) ? intval($_REQUEST['page']) : 1;
        $pagenum = isset( $_REQUEST['limit'] ) ? intval($_REQUEST['limit']) : 15;

        $groupid = isset( $_REQUEST['groupid'] ) ? intval($_REQUEST['groupid']) : 0;
        $uid = isset( $_REQUEST['uid'] ) ? intval($_REQUEST['uid']) : 0;
        $username = isset( $_REQUEST['username'] ) ? trim($_REQUEST['username']) : '';
        $email = isset( $_REQUEST['email'] ) ? trim($_REQUEST['email']) : '';

        if($username) {
            $username = urldecode($username);
            $username = safe_str($username);
        }
        if($email) {
            $email = urldecode($email);
            $email = safe_str($email);
        }

        $where = array();
        if( $groupid ){
            $where['groupid'] = $groupid;
        }
        if( $uid ){
            $where['uid'] = $uid;
        }
        if( $username ){
            $where['username'] = array('LIKE'=>$username);
        }
        if( $email ){
            $where['email'] = array('LIKE'=>$email);
        }

        // hook admin_user_control_get_list_where_after.php

        if( $where ){
            $total = $this->user->find_count($where);
        }else{
            $total = $this->user->count();
        }
        $maxpage = max(1, ceil($total/$pagenum));
        $page = min($maxpage, max(1, $page));

        $cms_arr = $this->user->list_arr($where, 'uid', -1, ($page-1)*$pagenum, $pagenum, $total);

        $group_arr = $this->user_group->get_name();
        $data_arr = array();
        foreach ($cms_arr as &$v){
            $this->user->format($v);
            if( isset($group_arr[$v['groupid']]) ){
                $v['groupname'] = $group_arr[$v['groupid']];
            }else{
                $v['groupname'] = '';
            }
            $data_arr[] = $v;
        }
        unset($cms_arr);
        // hook admin_user_control_get_list_data_arr_after.php

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
        // hook admin_user_control_set_before.php
        if( !empty($_POST) ){
            $field = trim( R('field','P') );
            $uid = intval( R('uid','P') );
            $value = trim( R('value','P') );

            $user = $this->user->get($uid);
            empty($user) && E(1, lang('data_no_exists'));

            $user[$field] = $value;

            if(!$this->user->update($user)) {
                E(1, lang('edit_failed'));
            }

            // hook admin_user_control_set_after.php

            E(0, lang('edit_field_successfully', array('field'=>$field)) );
        }
    }

    //添加
    public function add(){
        // hook admin_user_control_add_before.php
        if(empty($_POST)){
            $data = array(
                'uid'=>0,
                'username'=>'',
                'password'=>'123456',
                'author'=>'',
                'groupid'=>11,
                'email'=>'',
                'homepage'=>'',
                'intro'=>'',
                'credits'=>0,
                'golds'=>0,
                'mobile'=>'',
                'vip_times'=>''
            );
            // hook admin_user_control_add_data_after.php
            $this->assign('data', $data);

            $input = $this->get_input($data);
            // hook admin_user_control_add_get_input_after.php
            $this->assign('input',$input);

            $this->display('user_set.htm');
        }else{
            $user = &$this->user;
            $groupid = intval(R('groupid', 'P'));
            $username = R('username', 'P');
            $password = R('password', 'P');
            $author = R('author', 'P');
            $email = R('email', 'P');
            $homepage = trim(R('homepage', 'P'));
            $intro = trim(R('intro', 'P'));
            $mobile = trim(R('mobile', 'P'));
            $credits = intval(R('credits', 'P'));
            $golds = intval(R('golds', 'P'));
            $vip_times = R('vip_times', 'P');

            // hook admin_user_control_add_post_info_after.php

            empty($groupid) && E(1, lang('please_select_group'));
            //VIP用户
            if($groupid == 12){
                if($vip_times){
                    $vip_times = strtotime($vip_times);
                }else{
                    E(1, lang('vip_times_dis_empty'));
                }
            }else{
                $vip_times = 0;
            }

            if($message = $user->check_username($username)) {
                E(1, $message);
            }elseif($message = $user->check_password($password)){
                E(1, $message);
            }
            $data = $user->get_user_by_username($username);
            if($data){
                E(1, lang('username_is_exists'));
            }elseif($email && check::check_email($email) == false){
                E(1, lang('email_format_error'));
            }elseif( $email && $this->user->find_fetch_key(array('email'=>$email)) ){ //判断重复
                E(1, lang('email_is_exists'));
            }

            $salt = random(16, 3);
            $password = $user->safe_password($password, $salt);
            $ip = &$_ENV['_ip'];

            $userdata = array(
                'username'=>$username,
                'password'=>$password,
                'salt' => $salt,
                'author'=>$author,
                'email'=>$email,
                'groupid'=>$groupid,
                'regip' => ip2long($ip),
                'regdate' => $_ENV['_time'],
                'homepage' =>$homepage,
                'intro' =>$intro,
                'mobile'=>$mobile,
                'credits'=>$credits,
                'golds'=>$golds,
                'vip_times'=>$vip_times
            );

            // hook admin_user_control_add_post_before.php
            $uid = $user->create($userdata);
            if(!$uid) {
                E(1, lang('add_failed'));
            }
            // hook admin_user_control_add_post_after.php

            E(0, lang('add_successfully'));
        }
    }

    protected function get_input($data = array())
    {
        $input = array();

        // 获取用户组下拉框
        $input['groupid'] = $this->user_group->get_groupidhtml($data['groupid'], lang('select_user_group'));

        $input['username'] = form::get_text('username', $data['username'], '', 'placeholder="'.lang('username').'" maxlength="'.$this->user->_field_length['username'].'" required="required" lay-verify="required" lay-reqtext="'.lang('username_dis_empty').'" autocomplete="off"');
        $input['author'] = form::get_text('author', $data['author'], '', 'placeholder="'.lang('author').'" maxlength="'.$this->user->_field_length['author'].'"');
        $input['email'] = form::get_text('email', $data['email'], '', 'placeholder="'.lang('email').'" maxlength="'.$this->user->_field_length['email'].'"');
        $input['mobile'] = form::get_text('mobile', $data['mobile'], '', 'placeholder="'.lang('mobile').'" maxlength="'.$this->user->_field_length['mobile'].'"');
        $input['credits'] = form::get_number('credits', $data['credits'], '', 'placeholder="'.lang('credits').'"');
        $input['golds'] = form::get_number('golds', $data['golds'], '', 'placeholder="'.lang('golds').'"');
        $input['homepage'] = form::get_text('homepage', $data['homepage'], '', 'placeholder="'.lang('homepage').'" maxlength="'.$this->user->_field_length['homepage'].'"');
        $input['intro'] = form::get_text('intro', $data['intro'], '', 'placeholder="'.lang('intro').'" maxlength="'.$this->user->_field_length['intro'].'"');
        if(isset($data['vip_times']) && $data['vip_times']){
            $data['vip_times'] = date('Y-m-d H:i:s', $data['vip_times']);
        }else{
            $data['vip_times'] = '';
        }
        $input['vip_times'] = form::get_text('vip_times', $data['vip_times'], '', 'id="vip_times" placeholder="'.lang('vip_times').'"');

        // hook admin_user_control_get_input_after.php
        return $input;
    }

    //编辑
    public function edit(){
        if(empty($_POST)){
            $uid = intval(R('uid'));

            $data = $this->user->get($uid);
            if(empty($data)) $this->message(0, lang('data_error'), -1);

            // hook admin_user_control_edit_before.php

            $this->assign('data', $data);

            $input = $this->get_input($data);
            // hook admin_user_control_edit_get_input_after.php
            $this->assign('input',$input);

            $this->display('user_set.htm');
        }else{
            $uid = intval(R('uid', 'P'));
            $groupid = intval(R('groupid', 'P'));
            $author = R('author', 'P');
            $username = R('username', 'P');
            $email = R('email', 'P');
            $homepage = trim(R('homepage', 'P'));
            $intro = trim(R('intro', 'P'));
            $mobile = trim(R('mobile', 'P'));
            $credits = intval(R('credits', 'P'));
            $golds = intval(R('golds', 'P'));
            $vip_times = R('vip_times', 'P');

            // hook admin_user_control_edit_post_info_after.php

            empty($uid) && E(1, lang('data_error'));
            empty($groupid) && E(1, lang('data_error'));

            //VIP用户
            if($groupid == 12){
                if($vip_times){
                    $vip_times = strtotime($vip_times);
                }else{
                    E(1, lang('vip_times_dis_empty'));
                }
            }else{
                $vip_times = 0;
            }

            if($message = $this->user->check_username($username)) {
                E(1, $message);
            }

            $data = $this->user->get($uid);
            if(empty($data)) E(1, lang('data_error'));

            if($data['uid'] == 1 && $groupid != $data['groupid']){
                E(1, lang('admin1_group_dis_edit'));
            }elseif( $email && $email != $data['email'] && $this->user->find_fetch_key(array('email'=>$email)) ){
                E(1, lang('email_is_exists'));
            }elseif($email && check::check_email($email) == false){
                E(1, lang('email_format_error'));
            }elseif( $username != $data['username'] && $this->user->find_fetch_key(array('username'=>$username)) ){
                E(1, lang('username_is_exists'));
            }

            // hook admin_user_control_edit_post_before.php
            $data['groupid'] = $groupid;
            $data['username'] = $username;
            $data['author'] = $author;
            $data['email'] = $email;
            $data['homepage'] = $homepage;
            $data['intro'] = $intro;
            $data['mobile'] = $mobile;
            $data['credits'] = $credits;
            $data['golds'] = $golds;
            $data['vip_times'] = $vip_times;

            if(!$this->user->update($data)) {
                E(1, lang('edit_failed'));
            }

            // hook admin_user_control_edit_post_after.php
            E(0, lang('edit_successfully'));
        }
    }

    //改密
    public function pwd(){
        // hook admin_user_control_pwd_before.php
        if( !empty($_POST) ){
            $uid = intval( R('uid','P') );
            $newpw = trim( R('newpw','P') );
            if($err = $this->user->check_password($newpw)) {
                E(1, $err);
            }
            $user = $this->user->get($uid);
            empty($user) && E(1, lang('data_error'));

            $salt = random(16, 3); // 增加破解难度
            $user['salt'] = $salt;
            $user['password'] = $this->user->safe_password($newpw, $salt);

            // hook admin_user_control_pwd_after.php

            if(!$this->user->update($user)) {
                E(1, lang('edit_failed'));
            }else{
                if($uid == $this->_uid){
                    //修改自己的密码 清除登录信息，重新登录
                    $this->user->user_token_logout(1);
                    $login_url = $this->admin_safe_login_url();
                }else{
                    $login_url = '';
                }
                E(0, lang('edit_successfully'), $login_url);
            }
        }
    }

    // 查看
    public function info() {
        // hook admin_user_control_info_before.php
        $uid = (int) R('uid');
        empty($uid) && E(1, lang('data_error'));

        $_user = $this->user->get_user_by_uid($uid);
        empty($_user) && E(1, lang('data_error'));

        $this->user->format($_user);

        $group = $this->user_group->get_user_group_by_gid($_user['groupid']);
        $_user['groupname'] = $group['groupname'];

        // hook admin_user_control_info_after.php

        $this->assign('data', $_user);
        $this->display('user_info.htm');
    }

    // 删除
    public function del() {
        $uid = (int) R('uid', 'P');
        empty($uid) && E(1, lang('data_error'));

        // hook admin_user_control_del_before.php

        $err = $this->user->xdelete($uid, $this->_user['uid']);
        if( $err ) {
            E(1, $err);
        }else{
            // hook admin_user_control_del_success.php
            E(0, lang('delete_successfully'));
        }
    }

    // 批量删除
    public function batch_del() {
        // hook admin_user_control_batch_del_before.php
        $id_arr = R('id_arr', 'P');
        if(!empty($id_arr) && is_array($id_arr)) {
            $err_num = 0;
            foreach($id_arr as $uid) {
                $err = $this->user->xdelete($uid, $this->_user['uid']);
                if($err) {
                    $err_num++;
                }
                // hook admin_user_control_batch_del_foreach_after.php
            }
            if($err_num) {
                E(1, $err_num.lang('num_del_failed'));
            }else{
                // hook admin_user_control_batch_del_success.php
                E(0, lang('delete_successfully'));
            }
        }else{
            E(1, lang('data_error'));
        }

    }

    // hook admin_user_control_after.php
}