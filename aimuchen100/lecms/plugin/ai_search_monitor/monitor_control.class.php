<?php
defined('ROOT_PATH') or exit;


/**
 * AI搜索监测仪表盘控制器
 */

// report_generator 类文件命名不符合 core::model() 的 <name>_model.class.php 约定，需手动加载
require_once ROOT_PATH . 'lecms/plugin/ai_search_monitor/model/report_generator.class.php';

class monitor_control extends admin_control {

    public function index() {
        $site_id = defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0;
        $model = core::model('keyword');
        $reporter = new report_generator($site_id, $this->db);

        // 获取统计数据
        $stats = $model->get_stats($site_id, 7);
        $keywords = $model->get_keywords($site_id);
        $trend = $reporter->get_trend(30);

        // 插件设置（合并进主页面第二个 tab）
        $settings = $this->runtime->xget('ai_search_monitor_settings');
        if (!$settings || !is_array($settings)) {
            $settings = array(
                'keyword_limit' => 50,
                'refresh_interval_hours' => 24,
                'notify_email' => '',
                'platform_switch_baidu_wenxin' => 1,
                'platform_switch_doubao' => 1,
                'platform_switch_wechat_ai' => 1,
            );
        }

        $this->assign('stats', $stats);
        $this->assign('keywords', $keywords);
        $this->assign('trend', $trend);
        $this->assign('settings', $settings);
        $this->display('dashboard.htm');
    }

    public function reports() {
        $site_id = defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0;
        $page = max(1, (int)R('page', 'R'));
        $pagenum = 20;
        $platform = trim(R('platform', 'R'));

        $tablepre = $_ENV['_config']['db']['master']['tablepre'];
        $model = core::model('keyword');

        $where = " WHERE site_id = " . (int)$site_id;
        if ($platform) {
            $where .= " AND platform = '" . addslashes($platform) . "'";
        }

        // 总数统计（用于分页）
        $total_row = $this->db->fetch_first("
            SELECT COUNT(*) AS cnt FROM `{$tablepre}ai_search_log`{$where}
        ");
        $total = $total_row ? $total_row['cnt'] : 0;

        $offset = ($page - 1) * $pagenum;
        $list = $this->db->fetch_all("
            SELECT * FROM `{$tablepre}ai_search_log`{$where}
            ORDER BY checked_at DESC
            LIMIT {$pagenum} OFFSET {$offset}
        ");

        $this->assign('list', $list);
        $this->assign('total', $total);
        $pagebar = $this->get_pagebar($total, $pagenum, $page); $this->assign('pagebar', $pagebar);
        $this->display('report_list.htm');
    }

    public function export_csv() {
        $site_id = defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0;

        $start_date = R('start_date', 'P') ?: date('Y-m-d', strtotime('-7 days'));
        $end_date = R('end_date', 'P') ?: date('Y-m-d');

        // 日期格式校验
        $start_ts = strtotime($start_date);
        $end_ts = strtotime($end_date);
        if ($start_ts === false || $end_ts === false || $start_ts > $end_ts) {
            $this->message(1, '日期格式无效或开始日期大于结束日期');
        }

        $start_date = date('Y-m-d', $start_ts);
        $end_date = date('Y-m-d', $end_ts);

        $reporter = new report_generator($site_id, $this->db);
        $csv = $reporter->export_csv($start_date, $end_date);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="ai_search_report_' . date('Ymd') . '.csv"');
        echo "\xEF\xBB\xBF" . $csv; // UTF-8 BOM
        exit;
    }

    /**
     * 插件设置页面
     * 字段映射：
     *   - keyword_limit         单站点监测关键词数量上限（占位：keyword_control.edit_post 未强制）
     *   - refresh_interval_hours 监测刷新间隔（小时，占位：hook/cms_cron 后续接入）
     *   - notify_email          通知邮箱（占位：未接入实际发送通道）
     *   - platform_switch_*     各 AI 平台开关（baidu_wenxin / doubao / wechat_ai；占位）
     */
    public function settings() {
        // 设置页已合并进主页面第二个 tab，兼容跳转
        $this->message(0, '', '?monitor-index-tab-settings');
    }

    /**
     * 保存插件设置
     */
    public function settings_post() {
        if (!form_submit()) {
            $this->message(1, lang('submit_invalid'));
        }

        $limit = (int)R('keyword_limit', 'P');
        if ($limit < 1) $limit = 1;
        if ($limit > 1000) $limit = 1000;

        $interval = (int)R('refresh_interval_hours', 'P');
        if ($interval < 1) $interval = 1;
        if ($interval > 720) $interval = 720;

        $email = trim(R('notify_email', 'P'));
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->message(1, '通知邮箱格式不合法');
        }

        $settings = array(
            'keyword_limit' => $limit,
            'refresh_interval_hours' => $interval,
            'notify_email' => $email,
            'platform_switch_baidu_wenxin' => R('platform_switch_baidu_wenxin', 'P') ? 1 : 0,
            'platform_switch_doubao' => R('platform_switch_doubao', 'P') ? 1 : 0,
            'platform_switch_wechat_ai' => R('platform_switch_wechat_ai', 'P') ? 1 : 0,
        );

        $this->runtime->set('ai_search_monitor_settings', $settings);
        $this->runtime->save_changed();
        E(0, '插件设置已保存');
    }
}
