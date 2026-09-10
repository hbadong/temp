<?php
defined('ROOT_PATH') || exit;

/**
 * 404 页面热门游戏推荐（REQ-04-AC3）
 *
 * @param object $db 框架 db 对象（需有 fetch_all 方法）
 * @param int $site_id 当前站点ID
 * @param int $limit 推荐数量
 * @return array 游戏列表（id/name/cover），站点无数据时回退全站最新
 */
function hot_games_list($db, $site_id, $limit = 5) {
    $tablepre = $_ENV['_config']['db']['master']['tablepre'];
    $limit = max(1, (int)$limit);
    $site_id = (int)$site_id;

    $sql = "SELECT id, name, cover FROM `{$tablepre}cms_game` WHERE site_id={$site_id} ORDER BY id DESC LIMIT {$limit}";
    $list = $db->fetch_all($sql);

    // 站点无数据时回退全站最新，避免新站 404 页空白
    if(empty($list) && $site_id > 0) {
        $sql = "SELECT id, name, cover FROM `{$tablepre}cms_game` ORDER BY id DESC LIMIT {$limit}";
        $list = $db->fetch_all($sql);
    }

    return is_array($list) ? $list : array();
}
