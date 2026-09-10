<?php
defined('ROOT_PATH') or exit;
require_once ROOT_PATH . 'lecms/plugin/seo_tools/model/seo_verification.class.php';

/**
 * SEO 工具后台控制器——推送日志与验证文件管理入口。
 *
 * 模型文件名不符合 LECMS core::model() 的 <name>_model.class.php 约定，
 * 因此显式 require_once ROOT_PATH 加载。
 */
require_once ROOT_PATH . 'lecms/plugin/seo_tools/model/seo_verification.class.php';
require_once ROOT_PATH . 'lecms/plugin/seo_tools/model/baidu_submit.class.php';

class seo_tools_control extends admin_control
{
    /**
     * 获取后台当前站点 ID：GET/POST → CURRENT_SITE_ID → 首条推送日志记录。
     */
    private function get_site_id()
    {
        if (isset($_GET['site_id']) && (int)$_GET['site_id'] > 0) {
            return (int)$_GET['site_id'];
        }
        if (isset($_POST['site_id']) && (int)$_POST['site_id'] > 0) {
            return (int)$_POST['site_id'];
        }
        if (defined('CURRENT_SITE_ID') && (int)CURRENT_SITE_ID > 0) {
            return (int)CURRENT_SITE_ID;
        }

        $db = $this->db;
        if (!is_object($db)) {
            return 0;
        }
        $row = $db->fetch_first("SELECT `site_id` FROM `{$db->tablepre}seo_push_log` WHERE `site_id` > 0 ORDER BY `site_id` ASC LIMIT 1");
        return ($row && !empty($row['site_id'])) ? (int)$row['site_id'] : 0;
    }

    /**
     * 推送日志列表（分页、按引擎筛选）。
     */
    public function index()
    {
        $site_id = $this->get_site_id();
        $page = isset($_GET['page']) && (int)$_GET['page'] > 0 ? (int)$_GET['page'] : 1;
        $pagesize = 20;
        $start = ($page - 1) * $pagesize;
        $engine = isset($_GET['engine']) && $_GET['engine'] !== '' ? (string)$_GET['engine'] : '';

        $db = $this->db;
        $where = "`site_id` = " . intval($site_id);
        if ($engine !== '') {
            $where .= " AND `engine` = '" . addslashes($engine) . "'";
        }

        $logs = $db->fetch_all("SELECT * FROM `{$db->tablepre}seo_push_log` WHERE {$where} ORDER BY `dateline` DESC LIMIT " . intval($start) . ", " . intval($pagesize));

        $count_row = $db->fetch_first("SELECT COUNT(*) AS `total` FROM `{$db->tablepre}seo_push_log` WHERE {$where}");
        $total = isset($count_row['total']) ? (int)$count_row['total'] : 0;

        // 插件设置（原 settings() 逻辑合并进主页第二个 tab）；runtime 在测试/mock 环境可能为 null
        $settings = isset($this->runtime) && is_object($this->runtime) ? $this->runtime->xget('seo_tools_settings') : null;
        if (!$settings || !is_array($settings)) {
            $settings = array(
                'baidu_token' => '',
                'baidu_site' => '',
                'daily_quota' => 100,
                'push_frequency' => 'day',
                'push_type' => 'all',
                'engines' => array('baidu', 'sogou', '360', 'bing', 'toutiao', 'shenma', 'google'),
                'auto_push_on_publish' => 1,
                'cron_batch_size' => 50,
            );
        }
        $submitter = new baidu_submit(0, $this->db);
        $endpoints = $submitter->get_endpoints();

        // 当前激活 tab：main=功能 / settings=插件设置
        $active_tab = isset($_GET['tab']) && $_GET['tab'] === 'settings' ? 'settings' : 'main';

        $this->assign('logs', $logs);
        $this->assign('settings', $settings);
        $this->assign('endpoints', $endpoints);
        $this->assign_value('site_id', $site_id);
        $this->assign_value('total', $total);
        $this->assign_value('page', $page);
        $this->assign_value('pagesize', $pagesize);
        $this->assign_value('engine_filter', $engine);
        $this->assign_value('active_tab', $active_tab);
        $this->display('seo_tools_index.htm');
    }

    /**
     * POST 手动触发 URL 推送。
     */
    public function submit()
    {
        if (!form_submit()) {
            $this->message(1, lang('submit_invalid'));
            return;
        }

        $site_id = $this->get_site_id();
        $urls = array();

        if (isset($_POST['urls']) && is_string($_POST['urls'])) {
            $urls = array_filter(explode("\n", $_POST['urls']), function ($u) {
                $u = trim($u);
                return $u !== '';
            });
        } elseif (isset($_POST['url']) && is_string($_POST['url']) && trim($_POST['url']) !== '') {
            $urls = array(trim($_POST['url']));
        }

        if (empty($urls)) {
            $this->message(1, '请提供要推送的 URL');
            return;
        }

        try {
            $submitter = new baidu_submit($site_id, $this->db);
            $count = $submitter->submit($site_id, $urls);
            if ($count > 0) {
                $this->message(0, "成功推送 {$count} 个搜索引擎", '?seo_tools-index&site_id=' . $site_id);
            } else {
                $this->message(1, '推送失败，请检查网络或搜索引擎配置', '?seo_tools-index&site_id=' . $site_id);
            }
        } catch (Throwable $e) {
            $this->message(1, '推送异常: ' . $e->getMessage(), '?seo_tools-index&site_id=' . $site_id);
        }
    }

    /**
     * 验证文件管理：重定向到 verification_control。
     */
    public function verification()
    {
        $site_id = $this->get_site_id();
        $this->message(0, '请使用验证文件管理入口', '?verification-index&site_id=' . $site_id);
    }

    /**
     * 插件设置已合并进主页第二个 tab，兼容重定向。
     */
    public function settings()
    {
        $this->message(0, '', '?seo_tools-index-tab-settings');
    }

    /**
     * 保存插件设置
     */
    public function settings_post()
    {
        if (!form_submit()) {
            $this->message(1, lang('submit_invalid'));
            return;
        }

        $engines_in = (array)R('engines', 'P');
        $allowed = array('baidu', 'sogou', '360', 'bing', 'toutiao', 'shenma', 'google');
        $engines = array();
        foreach ($engines_in as $e) {
            $e = (string)$e;
            if (in_array($e, $allowed, true)) {
                $engines[] = $e;
            }
        }
        if (empty($engines)) {
            $engines = array('baidu');
        }

        $settings = array(
            'baidu_token' => trim((string)R('baidu_token', 'P')),
            'baidu_site' => trim((string)R('baidu_site', 'P')),
            'daily_quota' => max(0, (int)R('daily_quota', 'P')),
            'push_frequency' => in_array(R('push_frequency', 'P'), array('day', 'week', 'month', 'realtime'), true) ? R('push_frequency', 'P') : 'day',
            'push_type' => in_array(R('push_type', 'P'), array('all', 'manual'), true) ? R('push_type', 'P') : 'all',
            'engines' => array_values(array_unique($engines)),
            'auto_push_on_publish' => R('auto_push_on_publish', 'P') ? 1 : 0,
            'cron_batch_size' => max(1, min(1000, (int)R('cron_batch_size', 'P'))),
        );

        $this->runtime->set('seo_tools_settings', $settings);

        // 把 token/site 同步到 baidu_submit 默认 endpoint（admin 可重置）
        try {
            $submitter = new baidu_submit(0, $this->db);
            if ($settings['baidu_token'] !== '' && $settings['baidu_site'] !== '') {
                $endpoint = 'http://data.zz.baidu.com/urls?site=' . urlencode($settings['baidu_site'])
                    . '&token=' . urlencode($settings['baidu_token']);
                $submitter->set_endpoint('baidu', $endpoint);
            }
        } catch (Throwable $e) {
            // 同步失败不影响保存
        }

        $this->runtime->save_changed();
        E(0, '推送设置已保存');
    }
}

?>
