<?php
defined('ROOT_PATH') or exit;
/**
 * 站群数据同步客户端 后台设置控制器
 * 配置：主站 API URL、Token、目标站点、图片白名单、cron key、启用类型
 * 存储于 le_site_manager.config 的 sync_client 子配置
 */

class admin_sync_client_control extends admin_control {

    public function index() {
        require_once ROOT_PATH . 'lecms/plugin/sync_client/model/sync_client_model.class.php';
        $m = new sync_client();

        $site_id = max(1, (int)R('site_id', 'R'));
        $st = $m->get_settings($site_id);

        $this->assign('st', $st);
        $this->assign('site_id', $site_id);

        // 站点下拉
        $sites = $this->db->fetch_all("SELECT sid, site_name, domain FROM `" . $_ENV['_config']['db']['master']['tablepre'] . "site_manager` ORDER BY sid ASC");
        if(empty($sites)) $sites = array();
        $this->assign('sites', $sites);

        // 最近日志
        $logs = $m->recent_logs($site_id, 15);
        if(empty($logs)) $logs = array();
        $this->assign('logs', $logs);

        $this->display('admin_sync_client_set.htm');
    }

    /**
     * 保存设置
     */
    public function set_post() {
        if(!form_submit()) $this->message(1, lang('submit_invalid'));

        $site_id = max(1, (int)R('site_id', 'P'));
        require_once ROOT_PATH . 'lecms/plugin/sync_client/model/sync_client_model.class.php';
        $m = new sync_client();

        $master_url = trim(R('master_url', 'P'));
        if($master_url === '' || !preg_match('#^https?://#i', $master_url)) {
            $this->message(1, '主站 API URL 必须以 http(s):// 开头');
        }

        // cron key：留空则自动生成
        $ckey = trim(R('cron_key', 'P'));
        if($ckey === '') {
            $ckey = bin2hex(random_bytes(8));
        }

        $whitelist = '';
        foreach(explode(',', (string)R('img_whitelist', 'P')) as $h) {
            $h = strtolower(trim($h));
            if($h !== '') $whitelist .= ($whitelist === '' ? '' : ',') . $h;
        }

        $arr = array(
            'master_url' => $master_url,
            'token' => trim(R('token', 'P')),
            'target_site' => max(1, (int)R('target_site', 'P')),
            'cron_key' => $ckey,
            'img_whitelist' => $whitelist,
            'sync_games' => R('sync_games', 'P') ? 1 : 0,
            'sync_articles' => R('sync_articles', 'P') ? 1 : 0,
            'sync_categories' => R('sync_categories', 'P') ? 1 : 0,
            'sync_tags' => R('sync_tags', 'P') ? 1 : 0,
        );

        // 目标站点由所选站点决定（设置页面配置的站点即目标站点，target_site 保留兼容字段）
        $m->save_settings($arr, $site_id);
        $this->message(0, '已保存，cron key: ' . $ckey, 'index.php?admin_sync_client-index-site_id-' . $site_id);
    }

    /**
     * 立即手动触发同步（页面点击，走同一 sync 逻辑）
     */
    public function run() {
        $site_id = max(1, (int)R('site_id', 'R'));
        $type = trim(R('type', 'R'));
        require_once ROOT_PATH . 'lecms/plugin/sync_client/model/sync_client_model.class.php';
        $m = new sync_client();
        $res = $m->sync_all($site_id, $type !== '' && in_array($type, array('games', 'articles', 'categories', 'tags'), true) ? $type : '');
        $msg = $res['message'];
        foreach((array)$res['results'] as $t => $r) {
            $msg .= ' | ' . $r['message'];
        }
        $this->message($res['ok'] ? 0 : 1, $msg, 'index.php?admin_sync_client-index-site_id-' . $site_id);
    }
}
