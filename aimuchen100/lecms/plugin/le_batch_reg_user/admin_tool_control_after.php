<?php
defined('ROOT_PATH') || exit;

//批量注册用户 插件设置
function le_batch_reg_user(){
    if(empty($_POST)){
        $input = array();
        $setting['password'] = '123456';
        $setting['groupid'] = 11;

        // 获取用户组下拉框
        $input['groupidhtml'] = $this->user_group->get_groupidhtml($setting['groupid'], lang('select_user_group'));
        $input['password'] = form::get_text('password', $setting['password']);

        $this->assign('input', $input);
        $this->display();
    }else{
        $groupid = (int)R('groupid','P');
        $password = htmlspecialchars(trim(R('password', 'P')));
        $users = htmlspecialchars(trim(R('users', 'P')));

        if(empty($groupid) || empty($password) || empty($users)){E(1, '都不能为空哦');}

        $user = &$this->user;
        if($message = $user->check_password($password)){
            E(1, $message);
        }

        $user_arr = explode(PHP_EOL, $users);
        if(empty($user_arr)){
            E(1, '请输入用户名，一行一个');
        }

        $ip = &$_ENV['_ip'];
        $succ = $fail = $chongfu = 0;
        foreach ($user_arr as $username){
            if(empty($username)){
                continue;
            }
            $user->safe_username($username);

            if($user->check_username($username)) {
                $fail++;
            }elseif ($user->get_user_by_username($username)){
                $chongfu++;
            }else{
                $salt = random(16, 3, '0123456789abcdefghijklmnopqrstuvwxyz');
                $password = md5(md5($password).$salt);

                $userdata = array(
                    'username'=>$username,
                    'password'=>$password,
                    'salt' => $salt,
                    'groupid'=>$groupid,
                    'author'=>$username,
                    'regip' => ip2long($ip),
                    'regdate' => $_ENV['_time'],
                );
                if( $user->create($userdata) ){
                    $succ++;
                }else{
                    E(1, '写入用户表失败！');
                }
            }
        }
        $msg = "注册完成，成功：{$succ}，失败：{$fail}，重复：{$chongfu}";

        E(0, $msg);
    }
}
