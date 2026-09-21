<?php defined('ROOT_PATH') or exit;
/**
 * 下载中转路由 Hook（内联于 parseurl_control::index() 的 rewrite 分支，
 * 位于站点地图/标准解析器之前）
 * 将 /download-{id}.html 映射到 game_control::download()，隐藏真实下载地址。
 * 变量名加 dl_ 前缀：本 hook 与 url_generator 同名 hook 依插件启用顺序
 * 拼接进同一作用域，避免覆盖其 $url/$site_id/$row 等变量。
 * 命中后 return 直接退出 index()，阻止后续标准解析器覆盖 control/action。
 */

$dl_uri = isset($uri) && $uri !== '' ? $uri : (isset($_GET['rewrite']) ? $_GET['rewrite'] : '');
if($dl_uri !== '' && preg_match('#^download-(\d+)\.(html|htm)$#', $dl_uri, $dl_m)) {
    $_GET['control'] = 'game';
    $_GET['action'] = 'download';
    $_GET['id'] = (int)$dl_m[1];
    return;
}
