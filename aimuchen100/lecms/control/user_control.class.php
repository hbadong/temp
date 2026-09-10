<?php
/**
 * Author: dadadezhou <zhoudada97@foxmail.com>
 * Date: 2022-10-09
 * Time: 15:21
 * Description: 用户注册登录控制器
 */

defined('ROOT_PATH') or exit;
// hook user_control_start.php
class user_control extends base_control {
    public $_http_referer = ''; //来路
    public $_groupid = 11;  //默认为注册用户组

	function __construct() {
        parent::__construct();

        $dis_action = array('login','register','forget','resetpwd');    //登录后禁止访问的action
        // hook user_control_construct_dis_action_after.php

        if( !isset($this->_cfg['open_user']) || empty($this->_cfg['open_user']) ){//未开启用户功能
            $this->message(0, lang('open_user_0'), $this->_cfg['weburl']);
        }elseif ( $this->_uid && in_array($_GET['action'], $dis_action)){
            $this->message(0, lang('logged'), $this->urls->user_url('index','my'));
        }

        //来路
        $this->_http_referer = user_http_referer(array(), $this->_cfg['weburl']);
        $this->assign('_http_referer', $this->_http_referer);

        //忘记密码链接
        $forget_pwd_url = $this->urls->user_url('forget', 'user');
        $this->assign('forget_pwd_url', $forget_pwd_url);

        // hook user_control_construct_after.php
	}

    public function index(){
        // hook user_control_index_before.php
        if( $this->_uid ){
            http_location($this->urls->user_url('index','my'));
        }else{
            http_location($this->urls->user_url('login','user'));
        }
    }

    //全部用户
	public function all(){
        // hook user_control_all_before.php

        $this->_cfg['titles'] = lang('user_all').'_'.$this->_cfg['webname'];
        $this->_var['topcid'] = -1;

        $page = (int)R('page','G');
        if( $page > 1 ){
            $this->_cfg['titles']  .= '-'.lang('page_current', array('page'=>$page));
        }
        // hook user_control_all_seo_after.php

        $this->assign('cfg', $this->_cfg);
        $this->assign('cfg_var', $this->_var);

        $GLOBALS['run'] = &$this;
        $_ENV['_theme'] = &$this->_cfg['theme'];
        $tpl = 'user/user_all.htm';

        //优先使用主题下面的TPL，方便兼容当前主题css
        if(view_tpl_exists('user_all.htm')){
            $tpl = "user_all.htm";
        }

        // hook user_control_all_after.php
        $this->display($tpl);
    }

    // 用户登录
    public function login() {
        // hook user_control_login_before.php
        if( !isset($this->_cfg['open_user_login']) || empty($this->_cfg['open_user_login']) ){
            $this->message(0, lang('login_close'), $this->_cfg['weburl']);
        }
        if(empty($_POST)) {
            $this->_cfg['titles'] = lang('login').'_'.$this->_cfg['webname'];
            $this->_var['topcid'] = -1;

            $this->assign('cfg', $this->_cfg);
            $this->assign('cfg_var', $this->_var);

            $GLOBALS['run'] = &$this;
            $_ENV['_theme'] = &$this->_cfg['theme'];
            $tpl = 'user/login.htm';

            //优先使用主题下面的TPL，方便兼容当前主题css
            if(view_tpl_exists('login.htm')){
                $tpl = "login.htm";
            }

            // hook user_control_login_after.php
            $this->display($tpl);
        }elseif(form_submit()) {
            // hook user_control_login_post_before.php

            $user = &$this->user;
            $username = R('username', 'P');
            $password = R('password', 'P');
            // hook user_control_login_post_data_after.php

            if($message = $user->check_username($username)) {
                $this->message(0, $message);
            }elseif($message = $user->check_password($password)){
                $this->message(0, $message);
            }
            //开启了登录验证码
            if( !empty($this->_cfg['open_user_login_vcode']) ){
                $vcode = R('vcode', 'P');
                empty($vcode) && $this->message(0, lang('vcode_no_empty'));

                if(strtoupper($vcode) != _SESSION('loginvcode')){
                    $this->message(0, lang('vcode_error'));
                }
            }

            // hook user_control_login_post_check_after.php

            // 防IP暴力破解
            $ip = &$_ENV['_ip'];
            if($user->anti_ip_brute($ip)) {
                $this->message(0, lang('please_try_15_min'));
            }

            $data = $user->get_user_by_username($username);
            if($data && $user->verify_password($password, $data['salt'], $data['password'])) {
                // hook user_control_login_post_success.php

                //保存登录信息（cookie或session）
                $this->user->user_token_login(0, $data);

                // 更新登录信息
                $data['lastip'] = $data['loginip'];
                $data['lastdate'] = $data['logindate'];
                $data['loginip'] = ip2long($ip);
                $data['logindate'] = $_ENV['_time'];
                $data['logins']++;
                $user->update($data);

                // 删除密码错误记录
                $this->runtime->delete('password_error_'.$ip);

                $this->message(1, lang('login_successfully'), $this->_http_referer);
            }else{
                // hook user_control_login_post_error.php

                // 记录密码错误日志
                $log_password = '******'.substr($password, 6);
                log::write(lang('password_error')."：$username - $log_password", 'user_login_log.php');

                // 记录密码错误次数
                $user->password_error($ip);

                $this->message(0, lang('username_password_error'));
            }
        }else{
            $this->message(0, lang('form_invalid'));
        }
    }

    // 用户注册
    public function register() {
        // hook user_control_register_before.php
        if( !isset($this->_cfg['open_user_register']) || empty($this->_cfg['open_user_register']) ){
            $this->message(0, lang('register_close'), $this->_cfg['weburl']);
        }

        if(empty($_POST)) {
            $this->_cfg['titles'] = lang('register').'_'.$this->_cfg['webname'];
            $this->_var['topcid'] = -1;

            $this->assign('cfg', $this->_cfg);
            $this->assign('cfg_var', $this->_var);

            $GLOBALS['run'] = &$this;
            $_ENV['_theme'] = &$this->_cfg['theme'];
            $tpl = 'user/register.htm';

            //优先使用主题下面的TPL，方便兼容当前主题css
            if(view_tpl_exists('register.htm')){
                $tpl = "register.htm";
            }

            // hook user_control_register_after.php
            $this->display($tpl);
        }elseif(form_submit()) {
            // hook user_control_register_post_before.php

            $user = &$this->user;
            $username = R('username', 'P');
            $password = R('password', 'P');
            $repassword = R('repassword', 'P');
            // hook user_control_register_post_data_after.php

            if($message = $user->check_username($username)) {
                $this->message(0, $message);
            }elseif($message = $user->check_password($password)){
                $this->message(0, $message);
            }elseif ($password != $repassword){
                $this->message(0, lang('pwd_inconsistent'));
            }

            //开启了注册验证码
            if( !empty($this->_cfg['open_user_register_vcode']) ){
                $vcode = R('vcode', 'P');
                empty($vcode) && $this->message(0, lang('vcode_no_empty'));

                if(strtoupper($vcode) != _SESSION('registervcode')){
                    $this->message(0, lang('vcode_error'));
                }
            }

            // hook user_control_register_post_check_after.php

            if($user->get_user_by_username($username)){
                $this->message(0, lang('username_is_exists'));
            }

            $salt = random(16, 3); // 增加破解难度
            $password = $user->safe_password($password, $salt);
            $ip = ip2long($_ENV['_ip']);
            $data = array(
                'username'=>$username,
                'password'=>$password,
                'salt'=>$salt,
                'groupid'=>$this->_groupid,
                'author'=>$username,
                'regip'=>$ip,
                'regdate'=>$_ENV['_time'],
            );
            // hook user_control_register_post_after.php

            $uid = $user->create($data);
            if($uid){
                // hook user_control_register_post_create_success_after.php
                $data['uid'] = $uid;

                //保存登录信息（cookie或session）
                $this->user->user_token_login(0, $data);

                // 更新登录信息
                $data['loginip'] = ip2long($ip);
                $data['logindate'] = $_ENV['_time'];
                $data['logins'] = 1;
                $user->update($data);

                // hook user_control_register_post_create_success.php

                $this->message(1, lang('register_successfully'), $this->_http_referer);
            }else{
                // hook user_control_register_post_create_failed.php
                $this->message(0, lang('register_failed'));
            }
        }else{
            $this->message(0, lang('form_invalid'));
        }
    }

    //找回密码
    public function forget(){
        // hook user_control_forget_before.php
        if( !isset($this->_cfg['open_user_reset_password']) || empty($this->_cfg['open_user_reset_password']) ){
            $this->message(0, lang('password_recovery_disabled'));
        }elseif( !isset($this->_cfg['open_email']) || empty($this->_cfg['open_email']) ){
            $this->message(0, lang('email_disabled_no_password'));
        }

        if(empty($_POST)) {
            $this->_cfg['titles'] = lang('forget_password').'_'.$this->_cfg['webname'];
            $this->_var['topcid'] = -1;

            $this->assign('cfg', $this->_cfg);
            $this->assign('cfg_var', $this->_var);

            $GLOBALS['run'] = &$this;
            $_ENV['_theme'] = &$this->_cfg['theme'];
            $tpl = 'user/forget_password.htm';

            //优先使用主题下面的TPL，方便兼容当前主题css
            if(view_tpl_exists('forget_password.htm')){
                $tpl = "forget_password.htm";
            }

            // hook user_control_forget_after.php
            $this->display($tpl);
        }elseif(form_submit()) {
            $username = R('username', 'P');
            $email = R('email', 'P');
            $vcode = R('vcode', 'P');

            // hook user_control_forget_post_data_after.php

            if(empty($username) || empty($email) || empty($vcode)){
                $this->message(0, lang('username_email_code_no_empty'));
            }elseif ( !check::check_email($email) ){
                $this->message(0, lang('email_format_error'));
            }elseif(strtoupper($vcode) != _SESSION('forgetvcode')){
                $this->message(0, lang('vcode_error'));
            }elseif (empty($this->_cfg['email_smtp']) || empty($this->_cfg['email_port']) || empty($this->_cfg['email_account']) || empty($this->_cfg['email_password']) ){
                $this->message(0, lang('send_email_config_error'));
            }

            $data = $this->user->get_user_by_username($username);
            if( empty($data) ){
                $this->message(0, lang('no_this_user'));
            }else{
                if( $data['email'] != $email ){
                    $this->message(0, lang('user_no_bing_email'));
                }

                $userauth = str_auth("$data[uid]\t$data[username]", 'ENCODE', '', 600); //10分钟有效期

                $reset_url = $this->_cfg['weburl'].'index.php?user-resetpwd-auth-'.$userauth.$_ENV['_config']['url_suffix'];
                $body = "<div><h2>{$this->_cfg['webname']}：".lang('reset_password')."</h2><p>".lang('reset_password_validity')."</p><p>".lang('copy_url_to_broswer')."：{$reset_url}</p></div>";

                // hook user_control_forget_post_body_after.php

                //邮件配置
                $config = array(
                    'debug' => 0,
                    'smtp' => $this->_cfg['email_smtp'],
                    'port' => $this->_cfg['email_port'],
                    'account' => $this->_cfg['email_account'],
                    'account_name' => isset($this->_cfg['email_account_name']) ? $this->_cfg['email_account_name'] : $this->_cfg['email_account'],
                    'password' => $this->_cfg['email_password'],
                    'to' => $email ,    //收件人
                    'title' => $this->_cfg['webname'].'：'.lang('reset_password'),  //邮件标题
                    'body' => $body,  //邮件内容
                );

                // hook user_control_forget_post_send_email_before.php

                $emailObj = new email();

                $result = $emailObj->sendemail($config);
                if($result){
                    $this->message(1, lang('send_email_successfully'));
                }else{
                    $this->message(0, lang('send_email_failed'));
                }
            }
        }else{
            $this->message(0, lang('form_invalid'));
        }
    }

    //重置密码
    public function resetpwd(){
        // hook user_user_control_resetpwd_before.php
        if( empty($_POST) ) {
            $auth = R('auth','G');
            if( empty($auth) ){
                $this->message(0, lang('data_error'));
            }

            $userauth = str_auth($auth);
            $arr = explode("\t", $userauth);
            if(count($arr) < 2) {
                $this->message(0, lang('reset_password_url_invalid'));
            }

            if( empty($arr[0]) || empty($arr[1]) ){
                $this->message(0, lang('reset_password_url_invalid'));
            }

            $this->assign('username', $arr[1]);
            $this->assign('auth', $auth);

            $this->_cfg['titles'] = lang('reset_password').'_'.$this->_cfg['webname'];
            $this->_var['topcid'] = -1;

            $this->assign('cfg', $this->_cfg);
            $this->assign('cfg_var', $this->_var);

            $GLOBALS['run'] = &$this;
            $_ENV['_theme'] = &$this->_cfg['theme'];
            $tpl = 'user/reset_password.htm';

            //优先使用主题下面的TPL，方便兼容当前主题css
            if(view_tpl_exists('reset_password.htm')){
                $tpl = "reset_password.htm";
            }

            // hook user_user_control_resetpwd_after.php
            $this->display($tpl);
        }elseif(form_submit()) {
            $user = &$this->user;
            $username = R('username', 'P');
            $password = R('password', 'P');
            $repassword = R('repassword', 'P');
            $auth = R('auth', 'P');
            // hook user_control_resetpwd_post_data_after.php

            if($message = $user->check_username($username)) {
                $this->message(0, $message);
            }elseif($message = $user->check_password($password)){
                $this->message(0, $message);
            }elseif ($password != $repassword){
                $this->message(0, lang('pwd_inconsistent'));
            }elseif (empty($auth)){
                $this->message(0, lang('data_error'));
            }

            $userauth = str_auth($auth);
            $arr = explode("\t", $userauth);
            if(count($arr) < 2) {
                $this->message(0, lang('reset_password_url_invalid'));
            }else {
                if( empty($arr[0]) || empty($arr[1]) || $arr[1] != $username ){
                    $this->message(0, lang('reset_password_url_invalid'));
                }
            }

            $data = $user->get_user_by_username($username);
            if( empty($data) ){
                $this->message(0, lang('no_this_user'));
            }

            $salt = random(16, 3); // 增加破解难度
            $password = $user->safe_password($password, $salt);

            $data['salt'] = $salt;
            $data['password'] = $password;

            if( $user->update($data) ){
                $this->message(1, lang('reset_password_successfully'));
            }else{
                $this->message(0, lang('reset_password_failed'));
            }
        }else{
            $this->message(0, lang('form_invalid'));
        }
    }

    //生成验证码图片
    public function vcode(){
        // hook user_control_vcode_before.php
        $vcode = new vcode();
        $name = R('name','G');
        $width = isset($_GET['width']) ? (int)$_GET['width'] : 0;
        $height = isset($_GET['height']) ? (int)$_GET['height'] : 0;
        // hook user_control_vcode_after.php
        return $vcode->get_vcode($name, $width, $height);
    }

	// hook user_control_after.php
}
