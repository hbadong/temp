<?php
defined('ROOT_PATH') or exit;
/**
 * 模板伪原创后台设置控制器
 * 站点级启用开关 + CSS 类前缀配置，存储于 le_site_manager.config（JSON KV）
 */

class admin_template_rewrite_control extends admin_control {

    /**
     * 设置页：列出全部站点及其模板伪原创配置
     */
    public function index() {
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];
        $sites = $this->db->fetch_all("SELECT sid, site_name, domain, status, config FROM `{$tablepre}site_manager` ORDER BY sid ASC");
        if(empty($sites)) $sites = array();

        $rows = array();
        foreach($sites as $s) {
            $cfg = is_array($s) && isset($s['config']) ? @json_decode($s['config'], true) : array();
            if(!is_array($cfg)) $cfg = array();
            $rows[] = array(
                'sid' => (int)$s['sid'],
                'site_name' => $s['site_name'],
                'domain' => $s['domain'],
                'status' => (int)$s['status'],
                'enabled' => !empty($cfg['template_rewrite_enabled']) ? 1 : 0,
                'prefix' => isset($cfg['template_rewrite_prefix']) ? $cfg['template_rewrite_prefix'] : '',
            );
        }

        $this->assign('rows', $rows);
        $this->display('admin_template_rewrite_set.htm');
    }

    /**
     * 保存全部站点配置
     */
    public function set_post() {
        if(!form_submit()) $this->message(1, lang('submit_invalid'));

        $ids = R('sid', 'P');
        if(!is_array($ids) || empty($ids)) $this->message(1, '请至少选择一个站点');

        require_once ROOT_PATH . 'lecms/plugin/template_rewrite/lib/template_rewrite_helper.php';
        $sm = core::model('site_manager');
        if(!$sm || !method_exists($sm, 'save')) $this->message(1, 'site_manager 模型不可用');

        $enabled_arr = (array)R('enabled', 'P');
        $prefix_arr = (array)R('prefix', 'P');

        $tablepre = $_ENV['_config']['db']['master']['tablepre'];
        $saved = 0;
        foreach($ids as $sid) {
            $sid = (int)$sid;
            if($sid <= 0) continue;
            $enabled = !empty($enabled_arr[$sid]) ? 1 : 0;
            $prefix = tr_sanitize_prefix(isset($prefix_arr[$sid]) ? $prefix_arr[$sid] : '');
            // 已启用但前缀非法/为空时回退禁用，避免前缀替换破坏页面
            if($enabled && $prefix === '') $enabled = 0;

            // 后台语境下 site_manager::get_config 经模型缓存可能取不到，直接读 config 列合并，
            // 避免覆盖其它插件写入的配置项
            $row = $this->db->fetch_first("SELECT config FROM `{$tablepre}site_manager` WHERE sid={$sid}");
            $cur = $row && is_array($row) && isset($row['config']) ? @json_decode($row['config'], true) : array();
            if(!is_array($cur)) $cur = array();
            $cur['template_rewrite_enabled'] = $enabled;
            $cur['template_rewrite_prefix'] = $prefix;
            if($sm->save($sid, array('config' => $cur))) {
                $saved++;
            }
        }

        $this->message(0, '已保存 ' . $saved . ' 个站点配置');
    }
}
