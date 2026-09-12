<?php
defined('ROOT_PATH') || exit;

// C4：cron 入口跳过后台登录，密钥校验在 ai_task_control::cron()
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
