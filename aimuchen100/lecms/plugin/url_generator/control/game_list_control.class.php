<?php
defined('ROOT_PATH') or exit;
/**
 * 游戏列表控制器
 * 处理 TYPE_LIST(列表型)
 * 继承 base_control 以获得 CURRENT_SITE_ID（站群站点识别）
 */

class game_list_control extends base_control {

    /**
     * 游戏列表页
     */
    public function index() {
        $page = max(1, (int)R('page', 'R'));
        $pagenum = 20;

        $site_id = defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0;

        // 获取游戏列表（find_fetch 返回复合 key 数组，不能依赖 $games[0] 取 total）
        $where = array('site_id' => (int)$site_id);
        $games = $this->game->get_list($site_id, $page, $pagenum);
        $total = $this->game->find_count($where);

        $this->assign_value('games', $games);
        $this->assign_value('page', $page);
        $this->assign_value('pagenum', $pagenum);
        $this->assign_value('total', $total);
        $this->assign_value('pagebar', $this->build_pagebar($total, $pagenum, $page));
        $this->display('url_generator_game_list.htm');
    }

    /**
     * 前台分页条
     */
    protected function build_pagebar($total, $pagenum, $page, $show_pages = 5) {
        if($total <= $pagenum) return '';
        $pages = max(1, (int)ceil($total / $pagenum));
        $page = max(1, min((int)$page, $pages));
        $control = isset($_GET['control']) ? $_GET['control'] : '';
        $action = isset($_GET['action']) ? $_GET['action'] : '';
        $base = "index.php?{$control}-{$action}-page-";

        $html = '<div class="pagination" style="margin:10px 0;text-align:center;">';
        if($page > 1) {
            $html .= '<a class="btn btn-xs" href="'.$base.($page-1).'">&laquo; 上一页</a> ';
        }
        $start = max(1, $page - $show_pages);
        $end = min($pages, $page + $show_pages);
        for($i = $start; $i <= $end; $i++) {
            if($i == $page) {
                $html .= '<span class="btn btn-xs btn-primary">'.$i.'</span> ';
            } else {
                $html .= '<a class="btn btn-xs" href="'.$base.$i.'">'.$i.'</a> ';
            }
        }
        if($page < $pages) {
            $html .= '<a class="btn btn-xs" href="'.$base.($page+1).'">&raquo; 下一页</a>';
        }
        $html .= '</div>';
        return $html;
    }
}
