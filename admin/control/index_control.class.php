<?php
/**
 * Author: dadadezhou <zhoudada97@foxmail.com>
 * Date: 2022-10-09
 * Time: 15:21
 * Description: 后台首页控制器
 */

defined('ROOT_PATH') or exit;

class index_control extends admin_control{

	// 后台首页
	public function index() {
        // hook admin_index_control_index_before.php
        $cfg = $this->kv->xget('cfg');

        // hook admin_index_control_index_after.php
        $this->assign('cfg', $cfg);
        $this->display();
	}

	// 后台登录
	public function login() {
		if(empty($_POST)) {
			// hook admin_index_control_login_before.php
            $cfg = $this->kv->xget('cfg');
            !isset($cfg['admin_vcode']) && $cfg['admin_vcode'] = 0;

            //判断是否启用安全入口和验证安全密钥
            if(isset($cfg['admin_safe_entrance']) && $cfg['admin_safe_entrance'] && $cfg['admin_safe_auth'] && R('auth', 'G') != $cfg['admin_safe_auth']){
				// hook admin_index_control_login_no_safe_auth_after.php
                exit;
            }

            //输出，登录页面需要判断是否要登录验证码
            $this->assign('cfg', $cfg);

            // hook admin_index_control_login_after.php

			$this->display();
		}elseif(form_submit()) {
            // hook admin_index_control_login_post_before.php

			$user = &$this->user;
			$username = R('username', 'P');
			$password = R('password', 'P');

			if($message = $user->check_username($username)) {
			    E(1, $message);
			}elseif($message = $user->check_password($password)){
                E(1, $message);
			}

			//登录验证码
            $cfg = $this->kv->xget('cfg');
			if(isset($cfg['admin_vcode']) && $cfg['admin_vcode']){
                $vcode = R('vcode', 'P');
                if(empty($vcode) || strtoupper($vcode) != _SESSION('adminlogin')){
                    E(1, lang('vcode_error'));
                }
            }

            // hook admin_index_control_login_post_check_after.php

			// 防IP暴力破解
			$ip = &$_ENV['_ip'];
			if($user->anti_ip_brute($ip)) {
                E(1, lang('please_try_15_min'));
			}

			$data = $user->get_user_by_username($username);
            // hook admin_index_control_login_get_user_after.php
			if($data && $user->verify_password($password, $data['salt'], $data['password'])) {
                // hook admin_index_control_login_post_success.php

                //保存登录信息（cookie或session）
                $this->user->user_token_login(1, $data);

				// 更新登录信息
				$data['lastip'] = $data['loginip'];
				$data['lastdate'] = $data['logindate'];
				$data['loginip'] = ip2long($ip);
				$data['logindate'] = $_ENV['_time'];
				$data['logins']++;
				$user->update($data);

				// 删除密码错误记录
				$this->runtime->delete('password_error_'.$ip);

                // hook admin_index_control_login_post_success_after.php
                E(0, lang('login_successfully'));
			}else{
                // hook admin_index_control_login_post_error.php

				// 记录密码错误日志
				$log_password = '******'.substr($password, 6);
				log::write(lang('password_error')."：$username - $log_password", 'login_log.php');

				// 记录密码错误次数
				$user->password_error($ip);

                // hook admin_index_control_login_post_error_after.php
                E(1, lang('username_password_error'));
			}
		}else{
            E(1, lang('form_invalid'));
		}
	}

	// 后台退出登录
	public function logout(){
        if($_POST){
            // hook admin_index_control_logout_before.php
            $this->user->user_token_logout(1);

            $res = array(
                'code'=>1, 'msg'=>lang('logout_successfully')
            );

            $res['login_url'] = $this->admin_safe_login_url();

            // hook admin_index_control_logout_after.php
            exit( json_encode($res) );
        }else{
            $res = array(
                'code'=>0, 'msg'=>lang('data_error')
            );
            exit( json_encode($res) );
        }
	}

    //生成验证码
    public function vcode(){
        $vcode = new vcode();
        $name = R('name','G');
        empty($name) && $name = 'adminlogin';

        $width = isset($_GET['width']) ? (int)$_GET['width'] : 115;
        $height = isset($_GET['height']) ? (int)$_GET['height'] : 44;
        // hook admin_index_control_vcode_after.php

        return $vcode->get_vcode($name, $width, $height);
    }

	// hook admin_index_control_after.php
}
