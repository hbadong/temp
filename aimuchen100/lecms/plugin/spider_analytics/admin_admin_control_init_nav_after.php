<?php
defined('ROOT_PATH') or exit;

$menu['menuInfo']['plugin']['child'][] = array(
    'title' => '蜘蛛统计',
    'href' => 'index.php?spider_dashboard-index',
    'icon' => 'fa fa-eye',
    'target' => '_self'
);

$menu['menuInfo']['plugin']['child'][] = array(
    'title' => '蜘蛛黑名单',
    'href' => 'index.php?spider_blacklist-index',
    'icon' => 'fa fa-ban',
    'target' => '_self'
);

$menu['menuInfo']['plugin']['child'][] = array(
    'title' => 'IP 段管理',
    'href' => 'index.php?spider_iprange-index',
    'icon' => 'fa fa-map-marker',
    'target' => '_self'
);

$menu['menuInfo']['plugin']['child'][] = array(
    'title' => '命中明细',
    'href' => 'index.php?spider_hit-index',
    'icon' => 'fa fa-crosshairs',
    'target' => '_self'
);
