<?php
defined('ROOT_PATH') || exit;
require_once ROOT_PATH . 'lecms/plugin/spider_analytics/lib/boot.class.php';
class spider_dashboard_control extends admin_control {

    /**
     * 插件默认设置（集中维护）
     */
    private function default_settings() {
        return array(
            'archive_keep_days' => 90,
        );
    }

    /**
     * 读取设置（spider_runtime 表，与 aggregator/cron 同源）
     */
    private function get_settings() {
        spider_boot($this->db);
        $saved = array(
            'archive_keep_days' => (int)spider_runtime_get('archive_keep_days', 90),
        );
        return array_merge($this->default_settings(), $saved);
    }

    public function index() {
        $site_id = defined('CURRENT_SITE_ID') ? (int)CURRENT_SITE_ID : 0;
        $current_site_id = defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0;
        $this->view->assign('site_id', $site_id);
        $this->view->assign('current_site_id', $current_site_id);
        // 插件设置（合并进主页面第二个 tab）
        $settings = $this->get_settings();
        $this->view->assign('settings', $settings);
        $this->view->display('spider_dashboard.htm');
    }
    public function trend() {
        $site_id = defined('CURRENT_SITE_ID') ? (int)CURRENT_SITE_ID : 0;
        $days = (int)R('days', 'R');
        spider_boot($this->db);
        $db = new spider_dashboard();
        $data = $db->get_trend($site_id, $days);
        $this->message(1, json_encode($data));
    }
    public function pie() {
        $site_id = defined('CURRENT_SITE_ID') ? (int)CURRENT_SITE_ID : 0;
        $days = (int)R('days', 'R');
        spider_boot($this->db);
        $db = new spider_dashboard();
        $data = $db->get_engine_pie($site_id, $days);
        $this->message(1, json_encode($data));
    }

    /**
     * 插件设置页（已合并进 index 第二个 tab，此处仅兼容跳转）
     */
    public function settings() {
        $this->message(0, '', 'index.php?spider_dashboard-index-tab-settings');
    }

    /**
     * 保存插件设置（POST）
     */
    public function settings_post() {
        if (!form_submit()) {
            $this->message(1, lang('submit_invalid'));
        }

        $keep_days = (int)R('archive_keep_days', 'P');
        if ($keep_days < 1) {
            $keep_days = 1;
        }
        if ($keep_days > 3650) {
            $keep_days = 3650;
        }

        spider_boot($this->db);
        spider_runtime_set('archive_keep_days', (string)$keep_days);

        E(0, '插件设置已保存');
    }
}
