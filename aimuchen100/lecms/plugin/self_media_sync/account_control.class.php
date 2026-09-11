<?php
defined('ROOT_PATH') or exit;

/**
 * 平台账号管理控制器
 */

class account_control extends admin_control {

    public function index() {
        $site_id = defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0;
        $page = max(1, (int)R('page', 'R'));
        $pagenum = 20;

        $tablepre = $_ENV['_config']['db']['master']['tablepre'];

        $offset = ($page - 1) * $pagenum;

        $keyword = trim(R('keyword', 'R'));
        $extra = array();
        $cond = '';
        if($keyword !== '') {
            $kw = addslashes($keyword);
            $cond = " AND (account_name LIKE '%{$kw}%' OR platform LIKE '%{$kw}%')";
            $extra['keyword'] = $keyword;
        }

        $list = $this->db->fetch_all("
            SELECT * FROM `{$tablepre}media_account`
            WHERE site_id = " . (int)$site_id . "{$cond}
            ORDER BY id DESC
            LIMIT {$pagenum} OFFSET {$offset}
        ");

        $total_row = $this->db->fetch_first("
            SELECT COUNT(*) AS cnt FROM `{$tablepre}media_account`
            WHERE site_id = {$site_id}{$cond}
        ");
        $total = $total_row ? $total_row['cnt'] : 0;

        $platforms = array(
            'wechat' => '微信公众号',
            'zhihu' => '知乎',
            'csdn' => 'CSDN',
            'sohu' => '搜狐',
            'bilibili' => 'B站',
        );

        // 插件设置（合并自原 settings()，作为主页面第二个 tab）
        $settings = $this->runtime->xget('self_media_sync_settings');
        if (!$settings || !is_array($settings)) {
            $settings = array(
                'default_account_id' => 0,
                'platform_switch_wechat' => 1,
                'platform_switch_zhihu' => 1,
                'platform_switch_csdn' => 1,
                'platform_switch_sohu' => 1,
                'platform_switch_bilibili' => 1,
                'publish_rate_limit' => 30,
            );
        }

        // 当前站点的账号列表（供「默认发布账号」下拉）
        $site_id = defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0;
        $accounts = $this->db->fetch_all("
            SELECT id, platform, account_name, status
            FROM `{$tablepre}media_account`
            WHERE site_id = " . (int)$site_id . "
            ORDER BY platform, id ASC
        ");

        $this->assign('list', $list);
        $this->assign('total', $total);
        $this->assign('platforms', $platforms);
        $this->assign('settings', $settings);
        $this->assign('accounts', $accounts);
        $this->assign('keyword', $keyword);
        $pagebar = $this->get_pagebar($total, $pagenum, $page, 5, $extra); $this->assign('pagebar', $pagebar);
        $this->display('account_list.htm');
    }

    public function edit() {
        $id = (int)R('id', 'G');
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];

        if ($id) {
            $account = $this->db->fetch_first("
                SELECT * FROM `{$tablepre}media_account`
                WHERE id = {$id} LIMIT 1
            ");
            if (!$account) {
                $this->message(1, '账号不存在');
            }
        } else {
            $account = array(
                'id' => 0,
                'platform' => 'wechat',
                'account_name' => '',
                'access_token' => '',
                'refresh_token' => '',
                'status' => 1,
            );
        }

        $this->assign('account', $account);
        $this->display('account_edit.htm');
    }

    public function edit_post() {
        if (!form_submit()) {
            $this->message(1, lang('submit_invalid'));
        }

        $id = (int)R('id', 'P');
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];

        $data = array(
            'site_id' => defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0,
            'platform' => trim(R('platform', 'P')),
            'account_name' => trim(R('account_name', 'P')),
            'access_token' => trim(R('access_token', 'P')),
            'refresh_token' => trim(R('refresh_token', 'P')),
            'expires_at' => R('expires_at', 'P'),
            'status' => (int)R('status', 'P'),
        );

        if (empty($data['platform'])) {
            $this->message(1, '平台不能为空');
        }
        if ($data['account_name'] === '') {
            $this->message(1, '账号名称不能为空');
        }

        if ($id) {
            $set = array();
            foreach ($data as $k => $v) {
                $set[] = "`{$k}` = '" . addslashes($v) . "'";
            }
            $this->db->query("UPDATE `{$tablepre}media_account` SET " . implode(', ', $set) . " WHERE id = " . (int)$id);
            $msg = '更新成功';
        } else {
            $this->db->insert("`{$tablepre}media_account`", $data);
            $msg = '添加成功';
        }

        $this->message(0, $msg, '?account-account');
    }

    public function delete_post() {
        if (!form_submit()) {
            $this->message(1, lang('submit_invalid'));
        }

        $id = (int)R('id', 'P');
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];

        $this->db->query("DELETE FROM `{$tablepre}media_account` WHERE id = " . (int)$id);
        $this->message(0, '删除成功', '?account-account');
    }

    /**
     * 插件设置页已合并进主页面（index 第二个 tab），此处仅做兼容跳转。
     */
    public function settings() {
        $this->message(0, '', 'index.php?account-index');
    }

    /**
     * 保存插件设置
     */
    public function settings_post() {
        if (!form_submit()) {
            $this->message(1, lang('submit_invalid'));
        }

        $rate = (int)R('publish_rate_limit', 'P');
        if ($rate < 1) $rate = 1;
        if ($rate > 1000) $rate = 1000;

        $settings = array(
            'default_account_id' => (int)R('default_account_id', 'P'),
            'platform_switch_wechat' => R('platform_switch_wechat', 'P') ? 1 : 0,
            'platform_switch_zhihu' => R('platform_switch_zhihu', 'P') ? 1 : 0,
            'platform_switch_csdn' => R('platform_switch_csdn', 'P') ? 1 : 0,
            'platform_switch_sohu' => R('platform_switch_sohu', 'P') ? 1 : 0,
            'platform_switch_bilibili' => R('platform_switch_bilibili', 'P') ? 1 : 0,
            'publish_rate_limit' => $rate,
        );

        $this->runtime->set('self_media_sync_settings', $settings);
        $this->runtime->save_changed();
        E(0, '插件设置已保存');
    }
}
