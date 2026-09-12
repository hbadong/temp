<?php
header('Content-Type: application/json; charset=utf-8');
echo json_encode(array(
    'err' => 1,
    'msg' => '请改用后台入口：/admin/index.php?ai_task-cron-key-{md5(api_key)}',
));
