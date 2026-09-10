<?php
defined('ROOT_PATH') or exit;
/**
 * 后台导航 Hook：添加 CPS 挂载菜单
 * 内联到 admin_control::init_navigation() 的 // hook admin_admin_control_init_nav_after.php 标记
 */

$menu['menuInfo']['plugin']['child'][] = array(
    'title' => 'CPS挂载',
    'href' => 'index.php?cps_config-index',
    'icon' => 'fa fa-link',
    'target' => '_self'
);
$menu['menuInfo']['plugin']['child'][] = array(
    'title' => 'CPS统计',
    'href' => 'index.php?cps_stats-index',
    'icon' => 'fa fa-bar-chart-o',
    'target' => '_self'
);
