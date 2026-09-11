<?php
defined('ROOT_PATH') or exit;

/**
 * 外链管理控制器
 */

/**
 * 适配器工厂函数
 */
function adapter_factory_create($platform) {
    switch ($platform) {
        case 'aiqicha':
            return new aiqicha_adapter();
        case 'qcc':
            return new qcc_adapter();
        case 'tianyancha':
            return new tianyancha_adapter();
        default:
            return null;
    }
}

class external_control extends admin_control {

    public function index() {
        $site_id = defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0;
        $page = max(1, (int)R('page', 'R'));
        $pagenum = 20;
        $keyword = trim(R('keyword', 'R'));

        $tablepre = $_ENV['_config']['db']['master']['tablepre'];

        $site_id = (int)$site_id;
        $extra = array();
        $cond = '';
        if($keyword !== '') {
            $kw = addslashes($keyword);
            $cond = " AND (e.platform LIKE '%{$kw}%' OR e.platform_url LIKE '%{$kw}%' OR s.enterprise_name LIKE '%{$kw}%')";
            $extra['keyword'] = $keyword;
        }

        $sql = "SELECT e.*, s.enterprise_name
                FROM `{$tablepre}external_link` e
                INNER JOIN `{$tablepre}enterprise_site` s ON e.enterprise_id = s.id
                WHERE s.site_id = {$site_id}{$cond}
                ORDER BY e.id DESC
                LIMIT {$pagenum} OFFSET " . (($page - 1) * $pagenum);

        $list = $this->db->fetch_all($sql);

        // 统计总数
        $row = $this->db->fetch_first("
            SELECT COUNT(*) AS cnt FROM `{$tablepre}external_link` e
            INNER JOIN `{$tablepre}enterprise_site` s ON e.enterprise_id = s.id
            WHERE s.site_id = {$site_id}{$cond}
        ");
        $total = $row ? (int)$row['cnt'] : 0;

        $this->assign('list', $list);
        $this->assign('total', $total);
        $this->assign('keyword', $keyword);
        $pagebar = $this->get_pagebar($total, $pagenum, $page, 5, $extra); $this->assign('pagebar', $pagebar);
        $this->display('external_list.htm');
    }

    /**
     * 手动提交外链
     */
    public function submit_post() {
        if (!form_submit()) {
            E(1, lang('submit_invalid'));
        }

        $enterprise_id = (int)R('enterprise_id', 'P');
        $platform = trim(R('platform', 'P'));

        $tablepre = $_ENV['_config']['db']['master']['tablepre'];

        // 获取企业信息
        $enterprise = $this->db->fetch_first("
            SELECT * FROM `{$tablepre}enterprise_site`
            WHERE id = {$enterprise_id} LIMIT 1
        ");

        if (!$enterprise) {
            E(1, '企业站点不存在');
        }

        // 检查 7 天内是否已存在相同提交记录（防止重复提交）
        $platform = addslashes($platform);
        $existing = $this->db->fetch_first("
            SELECT id FROM `{$tablepre}external_link`
            WHERE enterprise_id = {$enterprise_id}
              AND platform = '{$platform}'
              AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
            LIMIT 1
        ");

        if ($existing) {
            E(1, '7 天内已提交过该平台，请勿重复提交');
        }

        // 获取适配器
        $adapter = adapter_factory_create($platform);
        if (!$adapter) {
            E(1, '不支持的平台');
        }

        // 提交外链
        $result = $adapter->submit($enterprise);

        // 记录外链
        $link_id = $this->db->insert("`{$tablepre}external_link`", array(
            'enterprise_id' => $enterprise_id,
            'platform' => $platform,
            'platform_url' => '',
            'status' => $result['success'] ? 1 : 3,
            'submit_response' => $result['response'],
            'check_count' => 0,
        ));

        if ($result['success']) {
            E(0, '外链提交成功');
        } else {
            E(1, '外链提交失败：HTTP ' . $result['http_code']);
        }
    }

    /**
     * 获取平台适配器
     */
    private function get_adapter($platform) {
        return adapter_factory_create($platform);
    }

    /**
     * 删除外链记录
     */
    public function delete_post() {
        if (!form_submit()) {
            E(1, lang('submit_invalid'));
        }

        $id = (int)R('id', 'P');
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];

        $this->db->query("DELETE FROM `{$tablepre}external_link` WHERE id = " . (int)$id);

        E(0, '删除成功');
    }
}
