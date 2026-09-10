<?php
defined('ROOT_PATH') or exit;

/**
 * 分析报告管理控制器
 */

class report_control extends admin_control {

    public function index() {
        $site_id = defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0;
        $page = max(1, (int)R('page', 'R'));
        $pagenum = 20;
        $offset = (int)(($page - 1) * $pagenum);

        $tablepre = $_ENV['_config']['db']['master']['tablepre'];

        $site_id = (int)$site_id;
        $sql = "SELECT r.*, s.name AS competitor_name
                FROM `{$tablepre}competitor_report` r
                INNER JOIN `{$tablepre}competitor_site` s ON r.competitor_id = s.id
                WHERE s.site_id = {$site_id}
                ORDER BY r.id DESC
                LIMIT {$pagenum} OFFSET {$offset}";

        $list = $this->db->fetch_all($sql);

        $row = $this->db->fetch_first("
            SELECT COUNT(*) AS cnt FROM `{$tablepre}competitor_report` r
            INNER JOIN `{$tablepre}competitor_site` s ON r.competitor_id = s.id
            WHERE s.site_id = {$site_id}
        ");
        $total = $row ? $row['cnt'] : 0;

        $this->assign('list', $list);
        $this->assign('total', $total);
        $pagebar = $this->get_pagebar($total, $pagenum, $page); $this->assign('pagebar', $pagebar);
        $this->display('report_list.htm');
    }

    public function detail() {
        $id = (int)R('id', 'G');
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];

        $report = $this->db->fetch_first("
            SELECT r.*, s.name AS competitor_name, s.url AS competitor_url
            FROM `{$tablepre}competitor_report` r
            INNER JOIN `{$tablepre}competitor_site` s ON r.competitor_id = s.id
            WHERE r.id = " . (int)$id . " LIMIT 1
        ");

        if (!$report) {
            $this->message(1, '报告不存在');
        }

        // 解析 JSON 字段
        $report['title_data'] = json_decode($report['title_data'], true) ?: array();
        $report['template_data'] = json_decode($report['template_data'], true) ?: array();
        $report['server_info'] = json_decode($report['server_info'], true) ?: array();
        $report['ai_suggestions'] = json_decode($report['ai_suggestions'], true) ?: array();

        $this->assign('report', $report);
        $this->display('report_detail.htm');
    }
}
