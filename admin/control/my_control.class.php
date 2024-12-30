<?php
/**
 * Author: dadadezhou <zhoudada97@foxmail.com>
 * Date: 2022-10-09
 * Time: 15:21
 * Description: 后台网站基本信息
 */

defined('ROOT_PATH') or exit;

class my_control extends admin_control {
    // 我的首页
    public function index() {
        // hook admin_my_control_index_info_before.php
        // 格式化后显示给用户
        $this->user->format($this->_user);

        // 常用功能
        $used_array = $this->get_used();

        //服务器信息
        $info = array();
        $is_ini_get = function_exists('ini_get');	// 考虑禁用 ini_get 的服务器
        $info['os'] = function_exists('php_uname') ? php_uname() : '未知';
        $info['software'] = R('SERVER_SOFTWARE', 'S');
        $info['php'] = PHP_VERSION;
        $info['mysql'] = $this->db->version();
        $info['filesize'] = $is_ini_get ? ini_get('upload_max_filesize') : '未知';
        $info['exectime'] = $is_ini_get ? ini_get('max_execution_time') : '未知';
        //$info['safe_mode'] = $is_ini_get ? (ini_get('safe_mode') ? 'Yes' : 'No') : '未知';
        //$info['url_fopen'] = $is_ini_get ? (ini_get('allow_url_fopen') ? 'Yes' : 'No') : '未知';
        $info['space'] = function_exists('disk_free_space') ? get_byte(disk_free_space(ROOT_PATH)) : '未知';
        $info['other'] = $this->get_other();
        // hook admin_my_control_index_info_after.php

        // 综合统计
        $stat = array();
        $stat['user'] = $this->user->count();
        $stat['category'] = $this->category->count();
        $stat['comment'] = $this->cms_content_comment->count();

//        $models = $this->models->get_models();
//        foreach ($models as $v){
//            if($v['mid'] >1){
//                $this->cms_content->table = 'cms_'.$v['tablename'];
//                if( $_ENV['_config']['admin_lang'] == 'zh-cn' ){
//                    $modelname = $v['name'];
//                }else{
//                    $modelname = ucfirst($v['tablename']);
//                }
//                $stat['content'][$modelname] = $this->cms_content->count();
//            }
//        }

        $this->cms_content->table = 'cms_article';
        if( $_ENV['_config']['admin_lang'] == 'zh-cn' ){
            $modelname = '文章';
        }else{
            $modelname = 'Article';
        }
        $stat['content'][$modelname] = $this->cms_content->count();

        // hook admin_my_control_index_stat_after.php

        $this->assign('used_array', $used_array);
        $this->assign('info', $info);
        $this->assign('stat', $stat);

        // hook admin_my_control_index_after.php

        $this->display();
    }

    // 修改密码
    public function password() {
        // hook admin_my_control_password_before.php
        if(empty($_POST)) {
            $input = array();
            $def = '';
            $input['oldpw'] = form::get_text('oldpw', $def, 'layui-input', 'required lay-verify="required" maxlength="32" placeholder="'.lang('input_old_password').'"');
            $input['newpw'] = form::get_text('newpw', $def, 'layui-input', 'required lay-verify="required" minlength="6" maxlength="32" placeholder="'.lang('input_new_password').'"');
            $input['confirm_newpw'] = form::get_text('confirm_newpw', $def, 'layui-input', 'required lay-verify="required" minlength="6" maxlength="32" placeholder="'.lang('input_confirm_new_password').'"');

            // hook admin_my_control_password_after.php
            $this->assign('input', $input);
            $this->display();
        }else{
            $oldpw = trim(R('oldpw', 'P'));
            $newpw = trim(R('newpw', 'P'));
            $confirm_newpw = trim(R('confirm_newpw', 'P'));
            $data = $this->_user;

            if(empty($oldpw)) {
                E(1, lang('old_pwd_no_empty'));
            }elseif($err = $this->user->check_password($newpw)) {
                E(1, $err, 'newpw');
            }elseif($confirm_newpw != $newpw) {
                E(1, lang('new_pwd_inconsistent'));
            }elseif(!$this->user->verify_password($oldpw, $data['salt'], $data['password'])) {
                E(1, lang('old_pwd_error'));
            }

            // hook admin_my_control_password_post_after.php

            $data['salt'] = random(16, 3); // 增加破解难度
            $data['password'] = $this->user->safe_password($newpw, $data['salt']);
            if(!$this->user->update($data)) {
                E(1, lang('edit_failed'));
            }else{
                // hook admin_my_control_password_post_success.php
                //清除登录信息，重新登录
                $this->user->user_token_logout(1);

                $login_url = $this->admin_safe_login_url();
                E(0, lang('edit_successfully'), $login_url);
            }
        }
    }

    //修改资料
    public function info(){
        if(empty($_POST)) {
            $this->user->format($this->_user);
            $input = array();
            $input['username'] = form::get_text('username', $this->_user['username'], '', 'placeholder="'.lang('username').'" maxlength="'.$this->user->_field_length['username'].'" required="required" lay-verify="required"');
            $input['email'] = form::get_text('email', $this->_user['email'], '', 'placeholder="'.lang('email').'" maxlength="'.$this->user->_field_length['email'].'"');
            $input['author'] = form::get_text('author', $this->_user['author'], '', 'placeholder="'.lang('author').'" maxlength="'.$this->user->_field_length['author'].'"');
            $input['homepage'] = form::get_text('homepage', $this->_user['homepage'], '', 'placeholder="'.lang('homepage').'" maxlength="'.$this->user->_field_length['homepage'].'"');
            $input['intro'] = form::get_textarea('intro', $this->_user['intro'], '', 'placeholder="'.lang('intro').'" maxlength="'.$this->user->_field_length['intro'].'"');

            // hook admin_my_control_info_after.php
            $this->assign('input', $input);
            $this->display();
        }else{
            $username = trim(R('username', 'P'));
            $email = trim(R('email', 'P'));
            $author = trim(R('author', 'P'));
            $homepage = trim(R('homepage', 'P'));
            $intro = trim(R('intro', 'P'));

            if($err = $this->user->check_username($username)){
                E(1, $err);
            }

            if($email && check::check_email($email) == false){
                E(1, lang('email_format_error'));
            }

            empty($author) && $author = $username;

            if($username != $this->_user['username'] && $this->user->get_user_by_username($username)){
                E(1, lang('username_is_exists'));
            }

            $data = array(
                'uid'=>$this->_user['uid'],
                'username'=>$username,
                'email'=>$email,
                'author'=>$author,
                'homepage'=>$homepage,
                'intro'=>$intro,
            );
            // hook admin_my_control_info_post_data_after.php

            if($this->user->update($data)){
                // hook admin_my_control_info_post_data_success.php
                E(0, lang('edit_successfully'));
            }else{
                E(1, lang('edit_failed'));
            }
        }
    }

    // 获取常用功能
    private function get_used() {
        $arr = array(
            array('name'=>lang('article_manage'), 'url'=>'index.php?content-index-mid-2', 'icon'=>'fa fa-book'),
            array('name'=>lang('cate_manage'), 'url'=>'index.php?category-index', 'icon'=>'fa fa-window-restore'),
            array('name'=>lang('tags_manage'), 'url'=>'index.php?tag-index', 'icon'=>'fa fa-tags'),
            array('name'=>lang('comment_manage'), 'url'=>'index.php?comment-index', 'icon'=>'fa fa-comments-o'),
            array('name'=>lang('user_manage'), 'url'=>'index.php?user-index', 'icon'=>'fa fa-user'),
        );

        // hook admin_my_control_get_used_after.php

        return $arr;
    }

    // 获取其他信息
    private function get_other() {
        $s = '';
        if(function_exists('extension_loaded')) {
            if(extension_loaded('gd')) {
                function_exists('imagepng') && $s .= 'png';
                function_exists('imagejpeg') && $s .= ' jpg';
                function_exists('imagegif') && $s .= ' gif';
            }
            extension_loaded('iconv') && $s .= ' iconv';
            extension_loaded('mbstring') && $s .= ' mbstring';
            extension_loaded('zlib') && $s .= ' zlib';
            extension_loaded('ftp') && $s .= ' ftp';
            function_exists('fsockopen') && $s .= ' fsockopen';
            function_exists('curl_init') && $s .= ' curl ';
        }
        // hook admin_my_control_get_other_after.php
        return $s;
    }

    // hook admin_my_control_after.php
}
