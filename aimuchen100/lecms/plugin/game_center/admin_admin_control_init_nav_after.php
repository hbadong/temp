<?php
defined('ROOT_PATH') or exit;

$menu['menuInfo']['plugin']['child'][] = array(
    'title' => '游戏管理',
    'href' => 'index.php?admin_game-index',
    'icon' => 'fa fa-gamepad',
    'target' => '_self'
);
$menu['menuInfo']['plugin']['child'][] = array(
    'title' => '游戏分类',
    'href' => 'index.php?admin_gamecate-index',
    'icon' => 'fa fa-tags',
    'target' => '_self'
);