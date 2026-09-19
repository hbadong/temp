<?php
defined('ROOT_PATH') or exit;

$menu['menuInfo']['plugin']['child'][] = array(
    'title' => '分类管理',
    'href' => 'index.php?admin_download-index',
    'icon' => 'fa fa-sitemap',
    'target' => '_self'
);
$menu['menuInfo']['plugin']['child'][] = array(
    'title' => '软件管理',
    'href' => 'index.php?admin_soft-index',
    'icon' => 'fa fa-download',
    'target' => '_self'
);
$menu['menuInfo']['plugin']['child'][] = array(
    'title' => '软件分类',
    'href' => 'index.php?admin_softcate-index',
    'icon' => 'fa fa-tags',
    'target' => '_self'
);
$menu['menuInfo']['plugin']['child'][] = array(
    'title' => '批量导入',
    'href' => 'index.php?admin_soft-import',
    'icon' => 'fa fa-upload',
    'target' => '_self'
);
