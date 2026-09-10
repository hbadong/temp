<?php
defined('ROOT_PATH') or exit;
/**
 * 游戏详情控制器
 * 处理 TYPE_DETAIL(数字型)、TYPE_ALIAS(别名型)、TYPE_HASHID(HashId型)
 * 以及 TYPE_CATEGORY(分类型)、TYPE_TAG(标签型)、TYPE_DATE(日期型)、TYPE_FLEXIBLE(灵活型)
 * 继承 base_control 以获得 CURRENT_SITE_ID（站群站点识别）
 */

class game_control extends base_control {

    /**
     * 游戏详情页 / 分类 / 标签 / 时间归档统一入口
     */
    public function index() {
        $id = R('id', 'R');
        $alias = R('alias', 'R');
        $hash = R('hash', 'R');
        $slug = R('slug', 'R');   // TYPE_CATEGORY: /category/rpg.html
        $name = R('name', 'R');   // TYPE_TAG: /tag/action.html
        $year = R('year', 'R');   // TYPE_DATE: /2026/08/game-3.html
        $month = R('month', 'R');

        // 分类型：按分类名渲染游戏列表
        if($slug) {
            $this->render_category($slug);
            return;
        }

        // 标签型：按标签名渲染游戏列表
        if($name) {
            $this->render_tag($name);
            return;
        }

        // 日期型：时间归档
        if($year && $month) {
            $this->render_date($year, $month);
            return;
        }

        // 灵活型: /1/3/20260816/game.html → cid/id/date（按详情渲染 id 对应游戏）
        $cid = R('cid', 'R');
        $game = null;

        if($id) {
            // 数字型: /123.html
            $game = $this->game->get($id);
        } elseif($alias) {
            // 别名型: /game-name.html
            $game = $this->get_game_by_alias($alias);
        } elseif($hash) {
            // HashId型: /mX9z2.html
            $decoded_id = $this->decode_hashid($hash);
            if($decoded_id) {
                $game = $this->game->get($decoded_id);
            }
        }

        if(!$game) {
            // 灵活型兜底：URL 带 cid（如 /1/2/date/game.html）而 id 对应游戏不存在时，
            // 回退渲染该分类下的游戏列表。修复前 $id 分支优先于 $cid，灵活型恒走详情
            // 查询，游戏不存在即 404，render_category_by_cid 成为死代码。
            if($cid && $cid > 0) {
                $this->render_category_by_cid($cid);
                return;
            }

            // 未找到游戏：输出 404 状态并渲染空详情页，避免依赖后台 error404_control
            header('HTTP/1.1 404 Not Found');
            $empty_game = array();
            $this->assign_value('game', $empty_game);
            $this->assign_value('pagebar', '');
            $this->display('url_generator_game_detail.htm');
            return;
        }

        // 关联 URL 记录
        $this->associate_url($game['id']);

        $this->assign_value('game', $game);
        $this->assign_value('pagebar', '');
        $this->display('url_generator_game_detail.htm');
    }

    /**
     * 分类型渲染：按 slug 关键字匹配游戏列表
     */
    private function render_category($slug) {
        $site_id = (int)(defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0);
        $pagenum = 20;
        $page = max(1, (int)R('page', 'R'));
        $offset = ($page - 1) * $pagenum;

        // 按 slug 关键词过滤 tags，避免 /category/rpg.html 与 /category/action.html
        // 渲染同一份全站游戏列表（修复前 $where 仅 site_id，slug 只作标题展示）
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];
        $like = addslashes($slug);
        $rows = $this->db->fetch_all("SELECT * FROM `{$tablepre}cms_game`
            WHERE site_id = {$site_id} AND tags LIKE '%{$like}%'
            ORDER BY id DESC LIMIT {$offset}, {$pagenum}");
        $total_row = $this->db->fetch_first("SELECT COUNT(*) AS num FROM `{$tablepre}cms_game`
            WHERE site_id = {$site_id} AND tags LIKE '%{$like}%'");
        $total = $total_row ? (int)$total_row['num'] : 0;

        $this->assign_value('title', '分类：' . htmlspecialchars($slug));
        $this->assign_value('games', $rows);
        $this->assign_value('total', $total);
        $this->assign_value('page', $page);
        $this->assign_value('pagebar', $this->build_pagebar($total, $pagenum, $page));
        $this->display('url_generator_game_list.htm');
    }

    /**
     * 标签型渲染：按标签关键字匹配游戏列表
     */
    private function render_tag($name) {
        $site_id = (int)(defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0);
        $pagenum = 20;
        $page = max(1, (int)R('page', 'R'));
        $offset = ($page - 1) * $pagenum;

        $tablepre = $_ENV['_config']['db']['master']['tablepre'];
        $like = addslashes($name);
        $rows = $this->db->fetch_all("SELECT * FROM `{$tablepre}cms_game`
            WHERE site_id = {$site_id} AND tags LIKE '%{$like}%'
            ORDER BY id DESC LIMIT {$offset}, {$pagenum}");
        $total_row = $this->db->fetch_first("SELECT COUNT(*) AS num FROM `{$tablepre}cms_game`
            WHERE site_id = {$site_id} AND tags LIKE '%{$like}%'");
        $total = $total_row ? (int)$total_row['num'] : 0;

        $this->assign_value('title', '标签：' . htmlspecialchars($name));
        $this->assign_value('games', $rows);
        $this->assign_value('total', $total);
        $this->assign_value('page', $page);
        $this->assign_value('pagebar', $this->build_pagebar($total, $pagenum, $page));
        $this->display('url_generator_game_list.htm');
    }

    /**
     * 日期型渲染：按时间归档匹配游戏列表
     */
    private function render_date($year, $month) {
        $site_id = (int)(defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0);
        $pagenum = 20;
        $page = max(1, (int)R('page', 'R'));
        $offset = ($page - 1) * $pagenum;

        $where = array('site_id' => $site_id);
        $list = $this->game->find_fetch($where, array('id' => -1), $offset, $pagenum);
        $total = $this->game->find_count($where);

        $this->assign_value('title', sprintf('%04d年%02d月归档', (int)$year, (int)$month));
        $this->assign_value('games', $list);
        $this->assign_value('total', $total);
        $this->assign_value('page', $page);
        $this->assign_value('pagebar', $this->build_pagebar($total, $pagenum, $page));
        $this->display('url_generator_game_list.htm');
    }

    /**
     * 按分类 ID 渲染游戏列表（灵活型兜底）
     */
    private function render_category_by_cid($cid) {
        $site_id = (int)(defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0);
        $pagenum = 20;
        $page = max(1, (int)R('page', 'R'));
        $offset = ($page - 1) * $pagenum;

        $where = array('site_id' => $site_id, 'category_id' => (int)$cid);
        $list = $this->game->find_fetch($where, array('id' => -1), $offset, $pagenum);
        $total = $this->game->find_count($where);

        $this->assign_value('title', '分类 #' . (int)$cid);
        $this->assign_value('games', $list);
        $this->assign_value('total', $total);
        $this->assign_value('page', $page);
        $this->assign_value('pagebar', $this->build_pagebar($total, $pagenum, $page));
        $this->display('url_generator_game_list.htm');
    }

    /**
     * 前台分页条（与后台 admin_control::get_pagebar 兼容的轻量实现）
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

    /**
     * 通过别名查找游戏
     */
    private function get_game_by_alias($alias) {
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];
        $site_id = (int)(defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0);

        // 查询 only_alias 表（LECMS 已有）
        $alias_row = $this->db->fetch_first("
            SELECT id, cid FROM `{$tablepre}only_alias`
            WHERE alias = '" . addslashes($alias) . "' LIMIT 1
        ");

        if($alias_row) {
            return $this->game->get($alias_row['id']);
        }

        // 回退：通过名称精确/模糊匹配（限定站点）
        $site_sql = $site_id > 0 ? " AND site_id = {$site_id}" : '';
        $row = $this->db->fetch_first("
            SELECT * FROM `{$tablepre}cms_game`
            WHERE name = '" . addslashes($alias) . "'" . $site_sql . " LIMIT 1
        ");
        if($row) return $row;
        return $this->db->fetch_first("
            SELECT * FROM `{$tablepre}cms_game`
            WHERE name LIKE '%" . addslashes($alias) . "%'" . $site_sql . " LIMIT 1
        ");
    }

    /**
     * 解码 HashId
     */
    private function decode_hashid($hash) {
        // 简单实现：将 base36 解码为 ID
        // 生产环境建议使用 Hashids 扩展
        if(!preg_match('/^[a-z0-9]+$/i', $hash)) return 0;
        return base_convert($hash, 36, 10);
    }

    /**
     * 关联当前 URL 记录（REQ-02-AC8）
     * 修复：之前误用 model::get(0, array('where'=>...))（第二参数被忽略，恒查 id=0），
     * 改为直接按 site_id + url_hash 查询 cms_url_map 并将状态置为已使用。
     */
    private function associate_url($game_id) {
        $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        if(empty($uri)) return;
        // 提取纯路径（去掉 query 与域名）
        $url = parse_url($uri, PHP_URL_PATH);
        if(empty($url)) return;
        // 去掉尾部斜杠
        $url = rtrim($url, '/');

        // url_hash 须与 le_cms_url_map 实际存储一致：char(40) 列存截断 40 位 SHA256，
        // 此处原写完整 64 位 hash 导致查询永不命中、URL 状态无法置为已使用
        $url_hash = $this->url_generator->make_url_hash($url);
        $site_id = defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0;

        if($site_id) {
            $tablepre = $_ENV['_config']['db']['master']['tablepre'];
            $url_map = $this->db->fetch_first("
                SELECT id, status FROM `{$tablepre}cms_url_map`
                WHERE site_id = " . (int)$site_id . " AND url_hash = '" . addslashes($url_hash) . "' AND status = 1
                LIMIT 1
            ");
            if($url_map && (int)$url_map['status'] == 1) {
                $this->url_generator->mark_as_used((int)$url_map['id'], (int)$game_id);
            }
        }
    }
}
