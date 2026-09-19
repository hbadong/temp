<?php
defined('ROOT_PATH') or exit;
/**
 * 分类管理总览控制器
 * 游戏下载 + 软件下载两大行业数据统计与新手引导
 */

class admin_download_control extends admin_control {

    public function index() {
        $site_id = (int)(R('site_id', 'R') ?: 1);
        $site_names = $this->get_site_names();

        // 游戏侧统计
        $game_stats = $this->game_stats($site_id);

        // 软件侧统计
        $soft_overview = $this->software->overview($site_id);
        $cate_overview = $this->software_category->overview($site_id);

        // URL 收录统计
        $url_stats = $this->url_stats($site_id);

        $this->assign('site_id', $site_id);
        $this->assign('site_names', $site_names);
        $this->assign('game_stats', $game_stats);
        $this->assign('soft_stats', $soft_overview);
        $this->assign('cate_stats', $cate_overview);
        $this->assign('url_stats', $url_stats);
        $this->display('admin_download_index.htm');
    }

    /**
     * 手动同步游戏数据（刷新统计并确保游戏 CPS 关联计数准确）
     */
    public function sync_games() {
        if(!form_submit()) E(1, lang('submit_invalid'));
        $site_id = (int)(R('site_id', 'P') ?: 1);
        $stats = $this->game_stats($site_id);
        E(0, '已刷新游戏数据：共 ' . $stats['total'] . ' 款游戏，其中 ' . $stats['cps_linked'] . ' 款已关联 CPS 链接');
    }

    /**
     * 游戏侧统计（总数/CPS 关联数/CPS 点击数）
     */
    private function game_stats($site_id) {
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];
        $site_id = (int)$site_id;
        $row = $this->db->fetch_first("SELECT COUNT(*) AS total FROM `{$tablepre}cms_game` WHERE site_id={$site_id}");
        $cps_row = $this->db->fetch_first("SELECT COUNT(DISTINCT game_id) AS num FROM `{$tablepre}cms_cps_config` WHERE site_id={$site_id} AND enabled=1");
        $click_row = $this->db->fetch_first("SELECT SUM(click_count) AS clicks FROM `{$tablepre}cms_cps_config` WHERE site_id={$site_id}");
        return array(
            'total' => $row ? (int)$row['total'] : 0,
            'cps_linked' => $cps_row ? (int)$cps_row['num'] : 0,
            'cps_clicks' => $click_row && $click_row['clicks'] ? (int)$click_row['clicks'] : 0,
        );
    }

    /**
     * URL 收录统计（总数/软件URL数/游戏URL数）
     */
    private function url_stats($site_id) {
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];
        $site_id = (int)$site_id;
        $total_row = $this->db->fetch_first("SELECT COUNT(*) AS num FROM `{$tablepre}cms_url_map` WHERE site_id={$site_id} AND status IN (1,2)");
        $soft_row = $this->db->fetch_first("SELECT COUNT(*) AS num FROM `{$tablepre}cms_url_map` WHERE site_id={$site_id} AND status IN (1,2) AND type IN (9,10,11)");
        $game_row = $this->db->fetch_first("SELECT COUNT(*) AS num FROM `{$tablepre}cms_url_map` WHERE site_id={$site_id} AND status IN (1,2) AND type IN (1,2,3,4,5,6,7,8)");
        return array(
            'total' => $total_row ? (int)$total_row['num'] : 0,
            'soft' => $soft_row ? (int)$soft_row['num'] : 0,
            'game' => $game_row ? (int)$game_row['num'] : 0,
        );
    }

    /**
     * 站点列表（sid => 站点名）
     */
    private function get_site_names() {
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];
        $rows = $this->db->fetch_all("SELECT sid, site_name FROM `{$tablepre}site_manager` ORDER BY sid ASC");
        $arr = array();
        if($rows) {
            foreach($rows as $r) {
                $arr[$r['sid']] = $r['site_name'];
            }
        }
        return $arr;
    }
}
