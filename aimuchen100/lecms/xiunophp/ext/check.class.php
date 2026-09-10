<?php
class check{
    // 验证码手机号
    public static function check_mobile($mobile='') {
        if(preg_match("/^1[345789]{1}\d{9}$/",$mobile)){
            return true;
        }else{
            return false;
        }
    }
    //验证邮箱号
    public static function check_email($email = ''){
        if( filter_var($email, FILTER_VALIDATE_EMAIL) ){
            return true;
        }else{
            return false;
        }
    }
}
