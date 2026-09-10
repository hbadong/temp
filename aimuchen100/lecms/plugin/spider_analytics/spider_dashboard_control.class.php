<?php
defined('ROOT_PATH') || exit;
class spider_dashboard_control extends admin_control {

    /**
     * 插件默认设置（集中维护）
     */
    private function default_settings() {
        return array(
            'log_keep_days'             => 90,
            'stats_refresh_hours'       => 24,
            'blacklist_auto_threshold'  => 10,
        );
    }

    /**
     * 读取设置（runtime 缓存）
     */
    private function get_settings() {
        $saved = $this->runtime->xget('spider_analytics_settings');
        if (!is_array($saved)) {
            $saved = array();
        }
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
        require_once ROOT_PATH . 'lecms/plugin/spider_analytics/model/dashboard.class.php';;
        require_once ROOT_PATH . 'lecms/plugin/spider_analytics/lib/runtime.class.php';;
        require_once ROOT_PATH . 'lecms/plugin/spider_analytics/model/runtime.class.php';;
        $db = new spider_dashboard();
        $data = $db->get_trend($site_id, $days);
        $this->message(1, json_encode($data));
    }
    public function pie() {
        $site_id = defined('CURRENT_SITE_ID') ? (int)CURRENT_SITE_ID : 0;
        $days = (int)R('days', 'R');
        require_once ROOT_PATH . 'lecms/plugin/spider_analytics/model/dashboard.class.php';;
        require_once ROOT_PATH . 'lecms/plugin/spider_analytics/lib/runtime.class.php';;
        require_once ROOT_PATH . 'lecms/plugin/spider_analytics/model/runtime.class.php';;
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

        $log_days = (int)R('log_keep_days', 'P');
        if ($log_days < 1) {
            $log_days = 1;
        }
        if ($log_days > 3650) {
            $log_days = 3650;
        }

        $refresh_hours = (int)R('stats_refresh_hours', 'P');
        if ($refresh_hours < 1) {
            $refresh_hours = 1;
        }
        if ($refresh_hours > 168) {
            $refresh_hours = 168;
        }

        $threshold = (int)R('blacklist_auto_threshold', 'P');
        if ($threshold < 1) {
            $threshold = 1;
        }
        if ($threshold > 10000) {
            $threshold = 10000;
        }

        $settings = array(
            'log_keep_days'            => $log_days,
            'stats_refresh_hours'      => $refresh_hours,
            'blacklist_auto_threshold' => $threshold,
        );

        $this->runtime->set('spider_analytics_settings', $settings);
        $this->runtime->save_changed();

        E(0, '插件设置已保存');
    }
}
