<?php
defined('ROOT_PATH') or exit;


/**
 * 后台URL管理控制器
 */

class admin_url_control extends admin_control {

    /**
     * URL列表
     */
    public function index() {
        $page = max(1, (int)R('page', 'R'));
        $pagenum = 20;
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];

        $site_id = (int)(R('site_id', 'R') ?: 0);
        $status = (int)(R('status', 'R') ?: 0);
        $keyword = trim(R('keyword', 'R'));

        // 构建查询条件
        $sql = "SELECT * FROM `{$tablepre}cms_url_map` WHERE 1";
        $extra = array();
        if($site_id > 0) { $sql .= " AND site_id = {$site_id}"; $extra['site_id'] = $site_id; }
        if($status > 0) { $sql .= " AND status = {$status}"; $extra['status'] = $status; }
        if($keyword !== '') {
            $kw = addslashes($keyword);
            $sql .= " AND (url LIKE '%{$kw}%' OR control LIKE '%{$kw}%' OR action LIKE '%{$kw}%')";
            $extra['keyword'] = $keyword;
        }
        $sql .= " ORDER BY id DESC";

        $total_row = $this->db->fetch_first("SELECT COUNT(*) AS cnt FROM ({$sql}) t");
        $total = $total_row ? $total_row['cnt'] : 0;

        $sql .= " LIMIT {$pagenum} OFFSET " . (($page - 1) * $pagenum);
        $urls = $this->db->fetch_all($sql);

        // 获取站点列表（用于筛选）
        $sites = $this->site_manager->get_list();

        // 读取插件设置（供「插件设置」tab 渲染）
        $settings = $this->runtime->xget('url_generator_settings');
        if (!$settings || !is_array($settings)) {
            $settings = array(
                'ratio_list' => 0.15,
                'ratio_detail' => 0.25,
                'ratio_category' => 0.15,
                'ratio_tag' => 0.15,
                'ratio_date' => 0.10,
                'ratio_alias' => 0.10,
                'ratio_flexible' => 0.05,
                'ratio_hashid' => 0.05,
                'batch_limit' => 50000,
                'auto_dedup' => 1,
                'clean_days' => 30,
                'min_score' => 70,
            );
        }

        $this->assign('urls', $urls);
        $this->assign('sites', $sites);
        $this->assign('total', $total);
        $this->assign('page', $page);
        $this->assign('keyword', $keyword);
        $this->assign('settings', $settings);
        $pagebar = $this->get_pagebar($total, $pagenum, $page, 5, $extra); $this->assign('pagebar', $pagebar);
        $this->display('admin_url_list.htm');
    }

    /**
     * URL生成表单
     */
    public function generate() {
        $sites = $this->site_manager->get_list();
        $this->assign('sites', $sites);
        $this->display();
    }

    /**
     * URL生成提交
     */
    public function generate_post() {
        if(!form_submit()) {
            $this->message(1, lang('submit_invalid'));
        }

        $site_id = (int)R('site_id', 'P');
        $count = (int)R('count', 'P');

        if($site_id <= 0 || $count <= 0) {
            $this->message(1, '请选择站点并输入生成数量');
        }

        // 生成URL
        $inserted = $this->url_generator->generate_urls($site_id, $count);

        $this->message(0, "成功生成 {$inserted} 个URL", '?admin_url-generate');
    }

    /**
     * 清理失效URL
     */
    public function cleanup() {
        $days = (int)R('days', 'P') ?: 30;
        $deleted = $this->url_generator->clean_unused_urls($days);
        $this->message(0, "清理完成，共处理 {$deleted} 个URL", '?admin_url-index');
    }

    /**
     * 设置页已合并进主页面第二个 tab，此处仅做兼容跳转
     */
    public function settings() {
        $this->message(0, '', 'index.php?admin_url-index-tab-settings');
    }

    /**
     * 保存 URL 生成引擎设置
     */
    public function settings_post() {
        if (!form_submit()) {
            $this->message(1, lang('submit_invalid'));
        }

        $settings = array(
            'ratio_list' => max(0, min(1, (float)R('ratio_list', 'P'))),
            'ratio_detail' => max(0, min(1, (float)R('ratio_detail', 'P'))),
            'ratio_category' => max(0, min(1, (float)R('ratio_category', 'P'))),
            'ratio_tag' => max(0, min(1, (float)R('ratio_tag', 'P'))),
            'ratio_date' => max(0, min(1, (float)R('ratio_date', 'P'))),
            'ratio_alias' => max(0, min(1, (float)R('ratio_alias', 'P'))),
            'ratio_flexible' => max(0, min(1, (float)R('ratio_flexible', 'P'))),
            'ratio_hashid' => max(0, min(1, (float)R('ratio_hashid', 'P'))),
            'batch_limit' => max(100, min(1000000, (int)R('batch_limit', 'P'))),
            'auto_dedup' => R('auto_dedup', 'P') ? 1 : 0,
            'clean_days' => max(1, min(365, (int)R('clean_days', 'P'))),
            'min_score' => min(90, max(70, (int)R('min_score', 'P'))),
        );

        $this->runtime->set('url_generator_settings', $settings);
        $this->runtime->save_changed();
        E(0, 'URL 生成设置已保存');
    }
}
