<?php
defined('ROOT_PATH') or exit;

/**
 * 企业站点管理控制器
 */

class enterprise_control extends admin_control {

    public function index() {
        $site_id = defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0;
        $page = max(1, (int)R('page', 'R'));
        $pagenum = 20;

        $tablepre = $_ENV['_config']['db']['master']['tablepre'];

        $site_id = (int)$site_id;
        $keyword = trim(R('keyword', 'R'));
        $extra = array();
        $cond = '';
        if($keyword !== '') {
            $kw = addslashes($keyword);
            $cond = " AND (enterprise_name LIKE '%{$kw}%' OR industry LIKE '%{$kw}%' OR address LIKE '%{$kw}%')";
            $extra['keyword'] = $keyword;
        }

        $sql = "SELECT * FROM `{$tablepre}enterprise_site`
                WHERE site_id = {$site_id}{$cond}
                ORDER BY id DESC
                LIMIT {$pagenum} OFFSET " . (($page - 1) * $pagenum);

        $list = $this->db->fetch_all($sql);

        // 统计总数
        $row = $this->db->fetch_first("
            SELECT COUNT(*) AS cnt FROM `{$tablepre}enterprise_site`
            WHERE site_id = {$site_id}{$cond}
        ");
        $total = $row ? (int)$row['cnt'] : 0;

        $this->assign('list', $list);
        $this->assign('total', $total);
        $this->assign('keyword', $keyword);
        $pagebar = $this->get_pagebar($total, $pagenum, $page, 5, $extra); $this->assign('pagebar', $pagebar);

        // 插件设置（原 settings() 逻辑合并进主页面第二个 tab）
        $settings = $this->runtime->xget('enterprise_seo_settings');
        if (!$settings || !is_array($settings)) {
            $settings = array(
                'ai_daily_quota' => 50,
                'external_link_interval_minutes' => 60,
                'review_required' => 1,
            );
        }
        $this->assign('settings', $settings);

        $this->display('enterprise_list.htm');
    }

    /**
     * 编辑企业站点
     */
    public function edit() {
        $id = (int)R('id', 'G');
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];

        $site = $this->db->fetch_first("
            SELECT * FROM `{$tablepre}enterprise_site`
            WHERE id = " . (int)$id . " LIMIT 1
        ");

        if (!$site) {
            $this->message(1, '企业站点不存在');
        }

        $this->assign('site', $site);
        $this->display('enterprise_edit.htm');
    }

    /**
     * 保存企业站点
     */
    public function edit_post() {
        if (!form_submit()) {
            $this->message(1, lang('submit_invalid'));
        }

        $id = (int)R('id', 'P');
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];

        $data = array(
            'enterprise_name' => trim(R('enterprise_name', 'P')),
            'industry' => trim(R('industry', 'P')),
            'address' => trim(R('address', 'P')),
            'phone' => trim(R('phone', 'P')),
            'email' => trim(R('email', 'P')),
            'description' => trim(R('description', 'P')),
            'template_id' => (int)R('template_id', 'P'),
            'status' => (int)R('status', 'P'),
        );

        $id = (int)$id;
        $esc = function($v) use ($tablepre) { return "'" . addslashes($v) . "'"; };
        $this->db->query("UPDATE `{$tablepre}enterprise_site` SET
            enterprise_name = " . $esc($data['enterprise_name']) . ",
            industry = " . $esc($data['industry']) . ",
            address = " . $esc($data['address']) . ",
            phone = " . $esc($data['phone']) . ",
            email = " . $esc($data['email']) . ",
            description = " . $esc($data['description']) . ",
            template_id = " . (int)$data['template_id'] . ",
            status = " . (int)$data['status'] . "
            WHERE id = {$id} LIMIT 1");

        $this->message(0, '保存成功', '?enterprise-enterprise');
    }

    /**
     * 插件设置页面（已废弃独立页）
     * 设置表单已合并进主页面第二个 tab，此处仅做兼容跳转
     */
    public function settings() {
        $this->message(0, '', 'index.php?enterprise-index-tab-settings');
    }

    /**
     * 保存插件设置
     */
    public function settings_post() {
        if (!form_submit()) {
            $this->message(1, lang('submit_invalid'));
        }

        $ai_quota = (int)R('ai_daily_quota', 'P');
        if ($ai_quota < 0) {
            $ai_quota = 0;
        }
        if ($ai_quota > 10000) {
            $ai_quota = 10000;
        }

        $interval = (int)R('external_link_interval_minutes', 'P');
        if ($interval < 1) {
            $interval = 1;
        }
        if ($interval > 1440) {
            $interval = 1440;
        }

        $settings = array(
            'ai_daily_quota' => $ai_quota,
            'external_link_interval_minutes' => $interval,
            'review_required' => R('review_required', 'P') ? 1 : 0,
        );

        $this->runtime->set('enterprise_seo_settings', $settings);
        $this->runtime->save_changed();
        E(0, '插件设置已保存');
    }

    /**
     * 删除企业站点
     */
    public function delete_post() {
        if (!form_submit()) {
            $this->message(1, lang('submit_invalid'));
        }

        $id = (int)R('id', 'P');
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];

        $this->db->query("DELETE FROM `{$tablepre}enterprise_site` WHERE id = " . (int)$id . "");

        $this->message(0, '删除成功', '?enterprise-enterprise');
    }
}
