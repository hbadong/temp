<?php
defined('ROOT_PATH') or exit;

class user extends model {
    private $data = array();		// 防止重复查询
    public $_login_method = 'cookie';   //使用cookie方式登录

    public $_field_length = array(
        'username'=>16,
        'author'=>50,
        'email'=>50,
        'homepage'=>255,
        'intro'=>255,
        'mobile'=>20,
        'avatar'=>255,
        // hook user_model_field_length_after.php
    );

	function __construct() {
		$this->table = 'user';		// 表名
		$this->pri = array('uid');	// 主键
		$this->maxid = 'uid';		// 自增字段

        // hook usre_model_construct_after.php
	}

	// 根据用户名获取用户数据
	public function get_user_by_username($username) {
        // hook usre_model_get_user_by_username_before.php
		$data = $this->find_fetch(array('username'=>$username), array(), 0, 1);
        $user = $data ? current($data) : array();
        // hook usre_model_get_user_by_username_after.php
		return $user;
	}

    // 根据用户ID获取用户数据，为什么不直接用 get ？ 方便后续通过钩子扩展
    public function get_user_by_uid($uid = 0) {
        // hook usre_model_get_user_by_uid_before.php
        $user = $this->get($uid);

        // hook usre_model_get_user_by_uid_after.php
        return $user;
    }

	// 检查用户名是否合格
	public function check_username(&$username) {
        // hook usre_model_check_username_before.php
		$username = trim($username);
		if(empty($username)) {
			return lang('username_dis_empty');
		}elseif(utf8::strlen($username) > 16) {
			return lang('username_dis_over_16');
		}elseif(str_replace(array("\t","\r","\n",' ','　',',','，','-','"',"'",'\\','/','&','#','*'), '', $username) != $username) {
			return lang('username_has_illegal_characters');
		}elseif(htmlspecialchars($username) != $username) {
			return lang('username_has_illegal_bracket');
		}

		// hook usre_model_check_username_after.php
		return '';
	}

	// 返回安全的用户名
	public function safe_username(&$username) {
        // hook usre_model_safe_username_before.php
		$username = str_replace(array("\t","\r","\n",' ','　',',','，','-','"',"'",'\\','/','&','#','*'), '', $username);
		$username = htmlspecialchars($username);
        // hook usre_model_safe_username_after.php
	}

	// 检查密码是否合格
	public function check_password(&$password) {
        // hook usre_model_check_password_before.php
		if(empty($password)) {
			return lang('password_dis_empty');
		}elseif(utf8::strlen($password) < 6) {
			return lang('password_dis_less_6');
		}elseif(utf8::strlen($password) > 32) {
			return lang('password_dis_over_32');
		}
		return '';
	}

	//返回加密后的密码
    public function safe_password($password = '', $salt = ''){
		empty($salt) && $salt = random(16, 3);
        // hook usre_model_safe_password_before.php
        return md5(md5($password).$salt);
    }

	// 验证密码是否相等
	public function verify_password($password, $salt, $password_md5) {
        // hook usre_model_verify_password_before.php
		return md5(md5($password).$salt) == $password_md5;
	}

	// 防IP暴力破解
	public function anti_ip_brute($ip, $cishu = 8) {
        // hook usre_model_anti_ip_brute_before.php
		$password_error = $this->runtime->get('password_error_'.$ip);
		return ($password_error && $password_error >= $cishu) ? true : false;
	}

	// 根据IP记录密码错误次数
	public function password_error($ip, $second = 900) {
        // hook usre_model_password_error_before.php
		$password_error = (int)$this->runtime->get('password_error_'.$ip);
		$password_error++;
		$this->runtime->set('password_error_'.$ip, $password_error, $second);
	}

	// 格式化后显示给用户
	public function format(&$user, $dateformat = 'Y-m-d H:i', $group_fmt = 0, $intronum = 0, $extra = array()) {
		if(!$user) return;
		$user['regdate'] = empty($user['regdate']) ? '0000-00-00 00:00' : date($dateformat, $user['regdate']);
		$user['regip'] = long2ip((int)$user['regip']);
		$user['logindate'] = empty($user['logindate']) ? '0000-00-00 00:00' : date($dateformat, $user['logindate']);
		$user['loginip'] = long2ip((int)$user['loginip']);
		$user['lastdate'] = empty($user['lastdate']) ? '0000-00-00 00:00' : date($dateformat, $user['lastdate']);
		$user['lastip'] = long2ip((int)$user['lastip']);

        //用户个人主页
		$user['user_url'] = $this->urls->space_url($user['uid']);

        empty($user['author']) && $user['author'] = $user['username'];
        
        if($group_fmt){
            $group_arr = $this->user_group->get_name();
            if( isset($group_arr[$user['groupid']]) ){
                $user['groupname'] = $group_arr[$user['groupid']];
            }else{
                $user['groupname'] = lang('data_error');
            }
        }

        //用户头像
        $user['avatar'] = $this->urls->user_avatar($user['uid'], $user['avatar'], array('user'=>$user));

        $intronum && $user['intro'] = utf8::cutstr_cn($user['intro'], $intronum);

		// hook usre_model_format_after.php
	}

    // 获取列表
    public function list_arr($where = array(), $orderby = 'uid', $orderway = 1, $start = 0, $limit = 0, $total = 0, $extra = array()) {
        // hook user_model_list_arr_before.php

        // 优化大数据量翻页
        if($start > 1000 && $total > 2000 && $start > $total/2) {
            $orderway = -$orderway;
            $newstart = $total-$start-$limit;
            if($newstart < 0) {
                $limit += $newstart;
                $newstart = 0;
            }
            $list_arr = $this->find_fetch($where, array($orderby => $orderway), $newstart, $limit);
            $list_arr = array_reverse($list_arr, TRUE);
        }else{
            $list_arr = $this->find_fetch($where, array($orderby => $orderway), $start, $limit);
        }

        // hook user_model_list_arr_after.php
        return $list_arr;
    }

    //用户登录验证（不判断用户组）
    public function user_token_check($isadmin = 1){
        $r = array('err'=>1, 'msg'=>'failed');
        // hook user_model_user_token_check_before.php

        if($this->_login_method == 'cookie'){
            if($isadmin){
                $cookieauth = R($_ENV['_config']['cookie_pre'].'admauth', 'R');
            }else{
                $cookieauth = R($_ENV['_config']['cookie_pre'].'userauth', 'R');
                //前台未登录 就看看后台是否登录
                empty($cookieauth) && $cookieauth = R($_ENV['_config']['cookie_pre'].'admauth', 'R');
            }
            if(empty($cookieauth)){
                $r = array('err'=>1, 'msg'=>'failed');
            }else{
                $cookieauth = str_auth($cookieauth);
                if(empty($cookieauth)) {
                    $r = array('err'=>1, 'msg'=>'failed');
                }else{
                    $arr = explode("\t", $cookieauth);
                    if(count($arr) < 3) {
                        $r = array('err'=>1, 'msg'=>'failed');
                    }else{
                        $uid      = (int)$arr[0];
                        $username = $arr[1];
                        $password = $arr[2];

                        $user = $this->get_user_by_uid($uid);
                        if(empty($user)) {
                            $r = array('err'=>1, 'msg'=>'failed');
                        }elseif($user['password'] != $password || $user['username'] != $username) {
                            $r = array('err'=>1, 'msg'=>'failed');
                        }else{
                            //VIP用户是否过期，过期的重置会员组，vip_times不归0，方便后续追踪
                            if($user['groupid'] == 12 && isset($user['vip_times']) && (int)$user['vip_times'] < $_ENV['_time']){
                                $user['groupid'] = 11;
                                $this->update($user);
                            }
                            $user_group = $this->user_group->get_user_group_by_gid($user['groupid']);

                            // hook user_model_user_token_check_cookie_success.php
                            $r = array('err'=>0, 'msg'=>'successfully', 'user'=>$user, 'user_group'=>$user_group);
                        }
                    }
                }
            }
            // hook user_model_user_token_check_cookie_after.php
        }else{
            if($isadmin){
                $uid = intval(_SESSION('adminuid'));
            }else{
                $uid = intval(_SESSION('uid'));
                //前台未登录 就看看后台是否登录
                empty($uid) && $uid = intval(_SESSION('adminuid'));
            }
            if(empty($uid)){
                $r = array('err'=>1, 'msg'=>'failed');
            }else{
                $user = $this->get_user_by_uid($uid);
                if($user){
                    //VIP用户是否过期，过期的重置会员组，vip_times不归0，方便后续追踪
                    if($user['groupid'] == 12 && isset($user['vip_times']) && (int)$user['vip_times'] < $_ENV['_time']){
                        $user['groupid'] = 11;
                        $this->update($user);
                    }

                    $user_group = $this->user_group->get_user_group_by_gid($user['groupid']);
                    // hook user_model_user_token_check_session_success.php

                    $r = array('err'=>0, 'msg'=>'successfully', 'user'=>$user, 'user_group'=>$user_group);
                }else{
                    $_SESSION['uid'] = 0;
                    $r = array('err'=>1, 'msg'=>'failed');
                }
            }
            // hook user_model_user_token_check_session_after.php
        }
        // hook user_model_user_token_check_after.php
        return $r;
    }

    //用户登录成功后写入信息（cookie或者session）
    public function user_token_login($isadmin = 1, $user = array()){
        // hook user_model_user_token_login_before.php
        if($this->_login_method == 'cookie'){
            $cookieauth = str_auth("$user[uid]\t$user[username]\t$user[password]", 'ENCODE');

            if($isadmin){
                $cookiename = 'admauth';
            }else{
                $cookiename = 'userauth';
            }
            _setcookie($cookiename, $cookieauth, $_ENV['_time'] + 86400, '', '', false, true);
            // hook user_model_user_token_login_cookie_after.php
        }else{
            if($isadmin){
                $sessionname = 'adminuid';
            }else{
                $sessionname = 'uid';
            }
            $_SESSION[$sessionname] = $user['uid'];
            // hook user_model_user_token_login_session_after.php
        }
        // hook user_model_user_token_login_after.php
        return array('err'=>0, 'msg'=>'successfully');
    }

    //用户退出登录后清除信息（cookie或者session）
    public function user_token_logout($isadmin = 1){
        // hook user_model_user_token_logout_before.php
        if($this->_login_method == 'cookie'){
            if($isadmin){
                $cookiename = 'admauth';
            }else{
                $cookiename = 'userauth';
            }
            _setcookie($cookiename, '', $_ENV['_time'] - 86400, '', '', false, true);
            // hook user_model_user_token_logout_cookie_after.php
        }else{
            if($isadmin){
                $sessionname = 'adminuid';
            }else{
                $sessionname = 'uid';
            }
            $_SESSION[$sessionname] = 0;
            // hook user_model_user_token_logout_session_after.php
        }
        // hook user_model_user_token_logout_after.php
    }

    //关联删除（不删除内容）
    public function xdelete($uid = 0, $login_uid = 0){
        // hook usre_model_xdelete_before.php

        if($login_uid && $login_uid == $uid){
            return lang('prohibit_delete_self');
        }

        // 内容读取
        $data = $this->get($uid);
        if(empty($data)) return lang('data_no_exists');
        if( $data['uid'] == 1 ) return lang('uid_1_dis_delete');

        $ret = $this->delete($uid);
        if($ret){
            $avatar_file = ROOT_PATH.'upload/avatar/'.substr(sprintf("%09d", $uid), 0, 3).'/'.$uid.'.png';
            try{
                is_file($avatar_file) && unlink($avatar_file);
            }catch(Exception $e) {}
        }
        return $ret ? '' : lang('delete_failed');
    }

    //更新用户内容数量
    public function update_user_contents($user = array(), $table_arr = array()){
        // hook usre_model_update_user_contents_before.php

        //所有内容模型的表名
	    if( empty($table_arr) ){
	        if( isset($this->data['table_arr']) && !empty($this->data['table_arr']) ){
	            $table_arr = $this->data['table_arr'];
            }else{
                $table_arr = $this->data['table_arr'] = $this->models->get_table_arr();
            }
        }

        //循环每个内容模型，得到它的内容数
        $content_total = 0;
        foreach ($table_arr as $table){
            if($table == 'page'){
                continue;
            }
            $this->cms_content->table = 'cms_'.$table;
            $content_total += $this->cms_content->find_count(array('uid'=>$user['uid']));
        }

        $user['contents'] = $content_total;

        // hook usre_model_update_user_contents_after.php

        if( $this->user->update($user) ){
            // hook usre_model_update_user_contents_success.php
            return true;
        }else{
            return false;
        }
    }

    // hook usre_model_after.php
}
