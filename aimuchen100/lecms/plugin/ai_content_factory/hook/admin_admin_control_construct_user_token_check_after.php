<?php
defined('ROOT_PATH') || exit;

// 定时执行入口：绕过后台登录校验，身份校验在 ai_task_control::cron() 内完成
if(R('control') == 'ai_task' && R('action') == 'cron') {
    $r = array(
        'err' => 0,
        'user' => array(
            'uid' => 1,
            'username' => 'cron',
            'groupid' => 1,
        ),
        'user_group' => array('groupid' => 1),
    );
}