<?php
defined('ROOT_PATH') or exit;
/**
 * CPS 推广链接配置管理（后台）
 * REQ-06-AC1：为每个游戏创建推广链接，支持权重分配
 *
 * 访问：index.php?cps_config-index / cps_config-create / cps_config-edit / cps_config-delete
 */

require_once ROOT_PATH . 'lecms/plugin/cps_integration/model/cps_config.class.php';

class cps_config_control extends admin_control {

    /**
     * 配置列表
     */
    public function index() {
        $page = max(1, (int)R('page', 'R'));
        $pagenum = 20;
        $keyword = trim(R('keyword', 'R'));

        $config = new CpsConfig();
        $extra = array();
        if($keyword !== '') $extra['keyword'] = $keyword;
        $result = $config->list_cps(0, null, $page, $pagenum, $keyword);

        // 站点名映射（显示用）
        $site_names = array();
        foreach($result['list'] as &$row) {
            $site_names[(int)$row['site_id']] = isset($site_names[(int)$row['site_id']]) ? $site_names[(int)$row['site_id']] : $this->get_site_name((int)$row['site_id']);
        }

        $this->assign_value('list', $result['list']);
        $this->assign_value('total', $result['total']);
        $this->assign_value('page', $page);
        $this->assign_value('keyword', $keyword);
        $this->assign_value('site_names', $site_names);
        $this->assign_value('pagebar', $this->get_pagebar($result['total'], $pagenum, $page, 5, $extra));

        // 插件设置（合并自 settings()，作为主页面第二个 tab）
        $settings = $this->runtime->xget('cps_integration_settings');
        if (!$settings || !is_array($settings)) {
            $settings = array(
                'click_dedupe_hours' => 24,
                'auto_degrade' => 1,
                'default_backup_url' => '',
                'default_weight' => 50,
                'weight_distribution' => 'balanced',
                'enable_click_track' => 1,
                'enable_log' => 1,
                'log_retention_days' => 90,
            );
        }
        $this->assign_value('settings', $settings);

        // 当前激活 tab：index.php?cps_config-index-tab-settings 定位到「插件设置」
        $tab = trim((string)R('tab', 'G'));
        if ($tab !== 'settings') {
            $tab = '';
        }
        $this->assign_value('tab', $tab);

        $this->display('cps_config_list.htm');
    }

    /**
     * 新建表单
     */
    public function create() {
        $this->assign_value('row', array());
        $this->assign_value('sites', $this->get_sites());
        $this->display('cps_config_form.htm');
    }

    /**
     * 新建提交
     */
    public function create_post() {
        if(!form_submit()) {
            $this->message(1, lang('submit_invalid'));
        }

        $site_id = (int)R('site_id', 'P');
        $game_id = (int)R('game_id', 'P');
        $url = trim(R('url', 'P'));
        $backup_url = trim(R('backup_url', 'P'));
        $weight = (int)R('weight', 'P');
        $name = trim(R('name', 'P'));

        if($site_id <= 0 || $game_id <= 0 || $url === '') {
            $this->message(1, '请完整填写站点、游戏ID与推广链接');
        }
        if(!preg_match('#^https?://#i', $url)) {
            $this->message(1, '推广链接必须以 http(s):// 开头');
        }
        if($weight < 0 || $weight > 100) {
            $this->message(1, '权重值必须在 0-100 之间');
        }

        $config = new CpsConfig();
        $id = $config->create_cps($site_id, $game_id, array(
            'name' => $name,
            'url' => $url,
            'backup_url' => $backup_url,
            'weight' => $weight,
            'enabled' => 1,
        ));

        if(!$id) {
            $this->message(1, '创建失败，请重试');
        }
        $this->message(0, '推广链接创建成功', '?cps_config-index');
    }

    /**
     * 编辑表单
     */
    public function edit() {
        $id = (int)R('id', 'G');
        $config = new CpsConfig();
        $row = $config->read_cps($id);
        if(!$row) {
            $this->message(1, '配置不存在', '?cps_config-index');
        }
        $this->assign_value('row', $row);
        $this->assign_value('sites', $this->get_sites());
        $this->display('cps_config_form.htm');
    }

    /**
     * 编辑提交
     */
    public function update_post() {
        if(!form_submit()) {
            $this->message(1, lang('submit_invalid'));
        }
        $id = (int)R('id', 'P');
        if($id <= 0) {
            $this->message(1, '无效的配置ID');
        }

        $url = trim(R('url', 'P'));
        if($url === '' || !preg_match('#^https?://#i', $url)) {
            $this->message(1, '推广链接必须以 http(s):// 开头');
        }
        $weight = (int)R('weight', 'P');
        if($weight < 0 || $weight > 100) {
            $this->message(1, '权重值必须在 0-100 之间');
        }

        $config = new CpsConfig();
        $config->update_cps($id, array(
            'name' => trim(R('name', 'P')),
            'url' => $url,
            'backup_url' => trim(R('backup_url', 'P')),
            'weight' => $weight,
            'enabled' => (int)R('enabled', 'P'),
        ));
        $this->message(0, '更新成功', '?cps_config-index');
    }

    /**
     * 删除
     */
    public function delete() {
        if(!form_submit()) {
            $this->message(1, lang('submit_invalid'));
        }
        $id = (int)R('id', 'P');
        if($id <= 0) {
            $this->message(1, '无效的配置ID');
        }
        $config = new CpsConfig();
        $config->delete_cps($id);
        $this->message(0, '已删除', '?cps_config-index');
    }

    /**
     * 站点列表（下拉框）
     */
    private function get_sites() {
        try {
            $sites = $this->site_manager->get_list();
            return is_array($sites) ? $sites : array();
        } catch(Exception $e) {
            return array();
        }
    }

    /**
     * 站点名称
     */
    private function get_site_name($site_id) {
        if($site_id <= 0) return '全局';
        try {
            $site = $this->site_manager->get($site_id);
            return ($site && isset($site['name'])) ? $site['name'] : ('站点#' . $site_id);
        } catch(Exception $e) {
            return '站点#' . $site_id;
        }
    }

    /**
     * CPS 插件全局设置（已合并进 index() 的第二个 tab，此处仅做兼容跳转）
     */
    public function settings() {
        $this->message(0, '', '?cps_config-index-tab-settings');
    }

    /**
     * 保存 CPS 设置
     */
    public function settings_post() {
        if (!form_submit()) {
            $this->message(1, lang('submit_invalid'));
        }

        $dedupe_hours = (int)R('click_dedupe_hours', 'P');
        if ($dedupe_hours < 0) {
            $dedupe_hours = 0;
        }
        if ($dedupe_hours > 168) {
            $dedupe_hours = 168; // 上限 7 天
        }

        $settings = array(
            'click_dedupe_hours' => $dedupe_hours,
            'auto_degrade' => R('auto_degrade', 'P') ? 1 : 0,
            'default_backup_url' => trim((string)R('default_backup_url', 'P')),
            'default_weight' => min(100, max(0, (int)R('default_weight', 'P'))),
            'weight_distribution' => in_array(R('weight_distribution', 'P'), array('balanced', 'priority', 'random'), true) ? R('weight_distribution', 'P') : 'balanced',
            'enable_click_track' => R('enable_click_track', 'P') ? 1 : 0,
            'enable_log' => R('enable_log', 'P') ? 1 : 0,
            'log_retention_days' => min(365, max(7, (int)R('log_retention_days', 'P'))),
        );

        // 仅当 URL 非空时校验
        if ($settings['default_backup_url'] !== '' && !preg_match('#^https?://#i', $settings['default_backup_url'])) {
            $this->message(1, '降级 URL 必须以 http(s):// 开头');
        }

        $this->runtime->set('cps_integration_settings', $settings);
        $this->runtime->save_changed();
        E(0, 'CPS 设置已保存');
    }
}
