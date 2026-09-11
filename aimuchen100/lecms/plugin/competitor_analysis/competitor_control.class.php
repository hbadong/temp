<?php
defined('ROOT_PATH') or exit;

require_once ROOT_PATH . 'lecms/plugin/competitor_analysis/lib/url_security.class.php';

/**
 * 竞品站点管理控制器
 */

class competitor_control extends admin_control {

    public function index() {
        $site_id = defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0;
        $page = max(1, (int)R('page', 'R'));
        $pagenum = 20;
        $group = trim(R('group', 'R'));

        $tablepre = $_ENV['_config']['db']['master']['tablepre'];

        $keyword = trim(R('keyword', 'R'));
        $extra = array();
        $cond = '';
        if($group) {
            $cond .= " AND `group` = '" . addslashes($group) . "'";
            $extra['group'] = $group;
        }
        if($keyword !== '') {
            $kw = addslashes($keyword);
            $cond .= " AND (name LIKE '%{$kw}%' OR url LIKE '%{$kw}%')";
            $extra['keyword'] = $keyword;
        }

        $sql = "SELECT * FROM `{$tablepre}competitor_site`
                WHERE site_id = " . (int)$site_id . "{$cond}
                ORDER BY id DESC
                LIMIT {$pagenum} OFFSET " . (($page - 1) * $pagenum);
        $list = $this->db->fetch_all($sql);

        $total_row = $this->db->fetch_first("
            SELECT COUNT(*) AS cnt FROM `{$tablepre}competitor_site`
            WHERE site_id = " . (int)$site_id . "{$cond}
        ");
        $total = $total_row ? (int)$total_row['cnt'] : 0;

        $groups = array(
            'direct' => '直接竞品',
            'indirect' => '间接竞品',
            'benchmark' => '标杆站点',
        );

        $this->assign('list', $list);
        $this->assign('total', $total);
        $this->assign('groups', $groups);
        $this->assign('current_group', $group);
        $this->assign('keyword', $keyword);
        $pagebar = $this->get_pagebar($total, $pagenum, $page, 5, $extra); $this->assign('pagebar', $pagebar);

        // 插件设置（已并入主页面的第二个 Tab）
        $settings = $this->runtime->xget('competitor_analysis_settings');
        if (!$settings || !is_array($settings)) {
            $settings = array(
                'collect_interval_hours' => 24,
                'max_collect_per_run' => 20,
                'similarity_threshold' => 70,
            );
        }
        $this->assign('plugin_settings', $settings);

        $this->display('competitor_list.htm');
    }

    public function edit() {
        $id = (int)R('id', 'G');
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];
        $model = core::model('competitor_site');

        if ($id) {
            $site = $model->get_one($id);
            if (!$site) {
                $this->message(1, '竞品站点不存在');
            }
        } else {
            // 新增模式：提供完整默认键，避免模板直接访问 $site['id'] 等触发 Undefined index
            $site = array(
                'id' => 0,
                'name' => '',
                'url' => '',
                'group' => 'direct',
                'status' => 1,
            );
        }

        $this->assign('site', $site);
        $this->display('competitor_edit.htm');
    }

    public function edit_post() {
        if (!form_submit()) {
            $this->message(1, lang('submit_invalid'));
        }

        $id = (int)R('id', 'P');
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];
        $model = core::model('competitor_site');

        $data = array(
            'site_id' => defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0,
            'url' => trim(R('url', 'P')),
            'name' => trim(R('name', 'P')),
            'group' => trim(R('group', 'P')),
            'status' => (int)R('status', 'P'),
        );

        if (empty($data['url'])) {
            $this->message(1, 'URL不能为空');
        }

        // SSRF 防护：校验 URL 格式和安全
        if (!url_security::is_safe($data['url'])) {
            $this->message(1, 'URL 格式不安全，仅允许 http(s) 协议的公开域名');
        }

        if ($id) {
            $model->update($id, $data);
            $msg = '更新成功';
        } else {
            $id = $model->add($data);
            $msg = '添加成功';
        }

        $this->message(0, $msg, '?competitor-competitor');
    }

    public function delete_post() {
        if (!form_submit()) {
            $this->message(1, lang('submit_invalid'));
        }

        $id = (int)R('id', 'P');
        $model = core::model('competitor_site');
        $model->delete($id);

        $this->message(0, '删除成功', '?competitor-competitor');
    }

    /**
     * 插件设置页（已并入主页面第二个 Tab，此处仅做兼容跳转）
     */
    public function settings() {
        $this->message(0, '', 'index.php?competitor-index-tab-settings');
    }

    /**
     * 保存插件设置
     */
    public function settings_post() {
        if (!form_submit()) {
            $this->message(1, lang('submit_invalid'));
        }

        $interval = (int)R('collect_interval_hours', 'P');
        if ($interval < 1) {
            $interval = 1;
        }
        if ($interval > 720) {
            $interval = 720;
        }

        $max_collect = (int)R('max_collect_per_run', 'P');
        if ($max_collect < 1) {
            $max_collect = 1;
        }
        if ($max_collect > 200) {
            $max_collect = 200;
        }

        $threshold = (int)R('similarity_threshold', 'P');
        if ($threshold < 0) {
            $threshold = 0;
        }
        if ($threshold > 100) {
            $threshold = 100;
        }

        $settings = array(
            'collect_interval_hours' => $interval,
            'max_collect_per_run' => $max_collect,
            'similarity_threshold' => $threshold,
        );

        $this->runtime->set('competitor_analysis_settings', $settings);
        $this->runtime->save_changed();
        E(0, '插件设置已保存');
    }
}
