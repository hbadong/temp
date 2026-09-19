<?php
defined('ROOT_PATH') or exit;
/**
 * 软件中心前台控制器
 * 路由由 url_generator 的 parseurl hook 注入：
 *   /soft/                  type=11 → action=index
 *   /soft/cate-{alias}.html type=10 → action=cate（参数 alias）
 *   /soft/{id}.html         type=9  → action=detail（参数 id）
 *   /soft/download-{id}.html           → action=download（参数 id，302 跳转并累计下载）
 */

class soft_control extends base_control {

    public function __construct() {
        parent::__construct();
        $GLOBALS['run'] = &$this;
        $_ENV['_theme'] = &$this->_cfg['theme'];
        $this->assign('cfg', $this->_cfg);
    }

    /**
     * 软件下载站首页：热门下载 + 分类导航 + 最新软件
     */
    public function index() {
        $site_id = (int)(defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0);
        if($site_id <= 0) $site_id = 1;

        $categories = $this->software_category->front_all($site_id);
        $hot = $this->software->top_downloads($site_id, 12);
        $latest = $this->software->front_list($site_id, 0, 1, 18);

        // 补分类名显示字段（模板不支持动态下标插值）
        $cat_names = array();
        $cat_aliases = array();
        foreach($categories as $c) {
            $cat_names[$c['id']] = $c['name'];
            $cat_aliases[$c['id']] = $c['alias'];
        }
        foreach($hot as $k => $r) {
            $hot[$k]['cat_name'] = isset($cat_names[$r['cat_id']]) ? $cat_names[$r['cat_id']] : '';
            $hot[$k]['cat_alias'] = isset($cat_aliases[$r['cat_id']]) ? $cat_aliases[$r['cat_id']] : '';
            $hot[$k]['url'] = '/soft/' . $r['id'] . '.html';
        }
        foreach($latest as $k => $r) {
            $latest[$k]['cat_name'] = isset($cat_names[$r['cat_id']]) ? $cat_names[$r['cat_id']] : '';
            $latest[$k]['cat_alias'] = isset($cat_aliases[$r['cat_id']]) ? $cat_aliases[$r['cat_id']] : '';
            $latest[$k]['url'] = '/soft/' . $r['id'] . '.html';
        }

        $this->_cfg['titles'] = '软件下载 - ' . $this->_cfg['webname'];
        $this->_cfg['seo_keywords'] = '软件下载,电脑软件,' . (isset($this->_cfg['seo_keywords']) ? $this->_cfg['seo_keywords'] : '');
        $this->assign('cfg', $this->_cfg);

        $this->assign_value('categories', $categories);
        $this->assign_value('hot', $hot);
        $this->assign_value('latest', $latest);
        $this->assign_value('site_id', $site_id);
        $this->display('soft_index.htm');
    }

    /**
     * 软件分类页
     */
    public function cate() {
        $site_id = (int)(defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0);
        if($site_id <= 0) $site_id = 1;
        $alias = trim(R('alias', 'R'));

        $cate = $alias !== '' ? $this->software_category->get_by_alias($alias, $site_id) : array();
        if(!$cate) {
            core::error404();
            return;
        }

        $page = max(1, (int)R('page', 'R'));
        $pagenum = 20;
        $rows = $this->software->front_list($site_id, (int)$cate['id'], $page, $pagenum);
        $total = $this->software->front_count($site_id, (int)$cate['id']);

        $categories = $this->software_category->front_all($site_id);
        foreach($rows as $k => $r) {
            $rows[$k]['url'] = '/soft/' . $r['id'] . '.html';
        }

        $this->_cfg['titles'] = $cate['name'] . ' - 软件下载 - ' . $this->_cfg['webname'];
        $this->_cfg['seo_keywords'] = $cate['seo_keywords'] ? $cate['seo_keywords'] : ($cate['name'] . ',软件下载');
        $this->_cfg['seo_description'] = $cate['seo_description'] ? $cate['seo_description'] : ($cate['name'] . '分类下的软件下载');
        $this->assign('cfg', $this->_cfg);

        $this->assign_value('cate', $cate);
        $this->assign_value('categories', $categories);
        $this->assign_value('rows', $rows);
        $this->assign_value('total', $total);
        $this->assign_value('page', $page);
        $this->assign_value('pagebar', $this->build_pagebar($total, $pagenum, $page, '/soft/cate-' . $alias . '.html'));
        $this->display('soft_cate.htm');
    }

    /**
     * 软件详情页（含热门游戏引流区块）
     */
    public function detail() {
        $site_id = (int)(defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0);
        if($site_id <= 0) $site_id = 1;
        $id = (int)R('id', 'R');

        $soft = $this->software->get($id);
        if(!$soft || $soft['site_id'] != $site_id || $soft['status'] != 1) {
            core::error404();
            return;
        }

        // 分类信息
        $cate = array();
        if($soft['cat_id'] > 0) {
            $cate_row = $this->software_category->get($soft['cat_id']);
            if($cate_row && $cate_row['site_id'] == $site_id) $cate = $cate_row;
        }

        // 同类推荐
        $related = $this->software->front_list($site_id, (int)$soft['cat_id'], 1, 6);
        foreach($related as $k => $r) {
            $related[$k]['url'] = '/soft/' . $r['id'] . '.html';
        }

        // 热门游戏引流区块（游戏站数据带动软件收录）
        $hot_games = $this->get_hot_games($site_id, 8);
        $categories = $this->software_category->front_all($site_id);

        $this->_cfg['titles'] = $soft['name'] . ' ' . $soft['version'] . ' 下载 - 软件下载';
        $this->_cfg['seo_keywords'] = $soft['name'] . ',软件下载,下载' . ($cate ? ',' . $cate['name'] : '');
        $this->_cfg['seo_description'] = $soft['intro'] ? $soft['intro'] : ($soft['name'] . ' 免费下载');
        $this->assign('cfg', $this->_cfg);

        $this->assign_value('soft', $soft);
        $this->assign_value('cate', $cate);
        $this->assign_value('related', $related);
        $this->assign_value('hot_games', $hot_games);
        $this->assign_value('categories', $categories);
        $this->assign_value('site_id', $site_id);
        $this->display('soft_detail.htm');
    }

    /**
     * 下载跳转（302 + 累计下载数）
     * 前台按钮指向 /soft/download-{id}.html
     */
    public function download() {
        $site_id = (int)(defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0);
        if($site_id <= 0) $site_id = 1;
        $id = (int)R('id', 'R');

        $soft = $this->software->get($id);
        if(!$soft || $soft['site_id'] != $site_id || $soft['status'] != 1 || $soft['download_url'] === '') {
            core::error404();
            return;
        }

        $this->software->inc_download($id);
        header('Location: ' . $soft['download_url'], true, 302);
        exit;
    }

    /**
     * 热门游戏引流数据（取本游戏站下载排行前 N）
     */
    protected function get_hot_games($site_id, $num = 8) {
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];
        $site_id = (int)$site_id;
        $rows = $this->db->fetch_all("SELECT id, name, cover FROM `{$tablepre}cms_game` WHERE site_id={$site_id} ORDER BY id DESC LIMIT " . (int)$num);
        if(!$rows) return array();
        $xuhao = 0;
        foreach($rows as $k => $r) {
            $rows[$k]['xuhao'] = ++$xuhao;
            $rows[$k]['url'] = '/list-' . $r['id'] . '.html';
        }
        return $rows;
    }

    /**
     * 前台分页条
     */
    protected function build_pagebar($total, $pagenum, $page, $base_url) {
        if($total <= $pagenum) return '';
        $pages = max(1, (int)ceil($total / $pagenum));
        $page = max(1, min((int)$page, $pages));
        $sep = strpos($base_url, '?') === false ? '?' : '&';
        $html = '<div class="pagination" style="margin:10px 0;text-align:center;">';
        if($page > 1) {
            $html .= '<a class="btn btn-xs" href="'.$base_url.$sep.'page='.($page-1).'">&laquo; 上一页</a> ';
        }
        $start = max(1, $page - 4);
        $end = min($pages, $page + 4);
        for($i = $start; $i <= $end; $i++) {
            if($i == $page) {
                $html .= '<span class="btn btn-xs btn-primary">'.$i.'</span> ';
            } else {
                $html .= '<a class="btn btn-xs" href="'.$base_url.$sep.'page='.$i.'">'.$i.'</a> ';
            }
        }
        if($page < $pages) {
            $html .= '<a class="btn btn-xs" href="'.$base_url.$sep.'page='.($page+1).'">&raquo; 下一页</a>';
        }
        $html .= '</div>';
        return $html;
    }
}
