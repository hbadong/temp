<?php
defined('ROOT_PATH') or exit;

return array(
    'name' => '站群数据同步客户端',
    'brief' => '从同步主站 API 拉取数据（games/articles/categories/tags）：HMAC 验签 + 事务 upsert + url_map 登记 + 图片本地化白名单，跳过 is_ai_rewritten=1',
    'version' => '1.0.0',
    'cms_version' => '3.0.0',
    'update' => '2026-09-20',
    'author' => 'AI沐尘100',
    'authorurl' => '',
    'setting' => '',
    'rank' => 25,
);
