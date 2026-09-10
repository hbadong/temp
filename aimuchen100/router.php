<?php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $path;
if ($path !== '/' && is_file($file)) {
    return false;
}

if (preg_match('#^/admin(?:/|$)#', $path)) {
    $_SERVER['SCRIPT_NAME'] = '/admin/index.php';
    $_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/admin/index.php';
    $rewrite = ltrim(substr($path, 6), '/');
    $_GET['u'] = $rewrite === '' ? 'index-login' : $rewrite;
    $_SERVER['QUERY_STRING'] = $rewrite === '' ? 'index-login' : $rewrite;
    require __DIR__ . '/admin/index.php';
    return;
}

$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/index.php';
$_GET['rewrite'] = ltrim($path, '/');
require __DIR__ . '/index.php';
