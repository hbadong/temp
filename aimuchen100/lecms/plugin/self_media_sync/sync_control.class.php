<?php
defined('ROOT_PATH') or exit;

/**
 * 同步管理控制器
 */

// sync_queue 类文件命名不符合 core::model() 的 <name>_model.class.php 约定，需手动加载
require_once ROOT_PATH . 'lecms/plugin/self_media_sync/model/sync_queue.class.php';

class sync_control extends admin_control {

    public function index() {
        $site_id = defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0;
        $page = max(1, (int)R('page', 'R'));
        $pagenum = 20;
        $platform = trim(R('platform', 'R'));
        $status = (int)R('status', 'R');

        $tablepre = $_ENV['_config']['db']['master']['tablepre'];
        $queue = new sync_queue($site_id, $this->db);

        $site_id = (int)$site_id;
        // l.* 已含 platform 列，a.platform 会导致 Duplicate column name
        $sql = "SELECT l.*, a.account_name
                FROM `{$tablepre}media_sync_log` l
                INNER JOIN `{$tablepre}media_account` a ON l.account_id = a.id
                WHERE l.site_id = {$site_id}";

        if ($platform) {
            $platform = addslashes($platform);
            $sql .= " AND l.platform = '{$platform}'";
        }

        if ($status) {
            $sql .= " AND l.status = " . (int)$status;
        }

        $sql .= " ORDER BY l.id DESC LIMIT {$pagenum} OFFSET " . (($page - 1) * $pagenum);
        $list = $this->db->fetch_all($sql);

        $total_sql = str_replace("LIMIT {$pagenum} OFFSET " . (($page - 1) * $pagenum), '', $sql);
        $total_row = $this->db->fetch_first("SELECT COUNT(*) AS cnt FROM ({$total_sql}) t");
        $total = $total_row ? $total_row['cnt'] : 0;

        $this->assign('list', $list);
        $this->assign('total', $total);
        $pagebar = $this->get_pagebar($total, $pagenum, $page); $this->assign('pagebar', $pagebar);
        $this->display('sync_log.htm');
    }

    public function retry_post() {
        if (!form_submit()) {
            E(1, lang('submit_invalid'));
        }

        $id = (int)R('id', 'P');
        $site_id = defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0;
        $queue = new sync_queue($site_id, $this->db);

        if ($queue->retry_task($id)) {
            E(0, '已加入重试队列');
        } else {
            E(1, '重试失败（已达到最大重试次数或等待时间不足）');
        }
    }

    public function trigger_post() {
        if (!form_submit()) {
            E(1, lang('submit_invalid'));
        }

        $article_id = (int)R('article_id', 'P');
        $platforms = R('platforms', 'P');

        // 支持数组和逗号分隔字符串两种格式
        if (is_string($platforms)) {
            $platforms = array_filter(explode(',', $platforms));
        }
        $platforms = (array)$platforms;

        if (empty($platforms)) {
            E(1, '请选择至少一个平台');
        }

        $site_id = defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0;
        $queue = new sync_queue($site_id, $this->db);

        // 获取该站点对应平台的账号
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];
        $account_ids = array();
        $platform_map = array();
        foreach ((array)$platforms as $platform) {
            $account = $this->db->fetch_first("
                SELECT id FROM `{$tablepre}media_account`
                WHERE site_id = {$site_id} AND platform = '" . addslashes($platform) . "' AND status = 1 LIMIT 1
            ");
            if ($account) {
                $account_ids[] = $account['id'];
                $platform_map[$account['id']] = $platform;
            }
        }

        if (empty($account_ids)) {
            E(1, '所选平台没有可用账号');
        }

        foreach ($account_ids as $account_id) {
            $queue->add_task($article_id, $account_id, $platform_map[$account_id]);
        }

        E(0, '同步任务已添加');
    }
}
