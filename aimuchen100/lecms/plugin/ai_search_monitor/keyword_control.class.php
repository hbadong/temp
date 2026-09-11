<?php
defined('ROOT_PATH') or exit;


/**
 * 品牌词管理控制器
 */

class keyword_control extends admin_control {

    public function index() {
        $site_id = defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0;
        $page = max(1, (int)R('page', 'R'));
        $pagenum = 20;
        $keyword = trim(R('keyword', 'R'));

        $tablepre = $_ENV['_config']['db']['master']['tablepre'];
        $sql = "SELECT id, keyword, keyword_variant, created_at FROM `{$tablepre}ai_search_log`
                WHERE site_id = {$site_id} AND found = 0";
        $extra = array();
        if($keyword !== '') {
            $kw = addslashes($keyword);
            $sql .= " AND (keyword LIKE '%{$kw}%' OR keyword_variant LIKE '%{$kw}%')";
            $extra['keyword'] = $keyword;
        }
        $sql .= " GROUP BY keyword ORDER BY id ASC";

        $total_row = $this->db->fetch_first("SELECT COUNT(*) AS cnt FROM ({$sql}) t");
        $total = $total_row ? $total_row['cnt'] : 0;

        $sql .= " LIMIT {$pagenum} OFFSET " . (($page - 1) * $pagenum);
        $keywords = $this->db->fetch_all($sql);

        $this->assign('keywords', $keywords);
        $this->assign('total', $total);
        $this->assign('keyword', $keyword);
        $pagebar = $this->get_pagebar($total, $pagenum, $page, 5, $extra); $this->assign('pagebar', $pagebar);
        $this->display('keyword_list.htm');
    }

    public function edit() {
        $this->display('keyword_edit.htm');
    }

    public function edit_post() {
        if (!form_submit()) {
            E(1, lang('submit_invalid'));
        }

        $site_id = defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0;
        $keyword = trim(R('keyword', 'P'));
        $platforms = R('platforms', 'P'); // array
        $variants = trim(R('variants', 'P'));

        if (empty($keyword)) {
            E(1, '品牌词不能为空');
        }

        // 字段长度校验
        if (mb_strlen($keyword) > 200) {
            E(1, '品牌词长度不能超过200字符');
        }
        if (mb_strlen($variants) > 200) {
            E(1, '词变体长度不能超过200字符');
        }

        // 保存品牌词配置（存储到监测日志中作为配置标记）
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];
        $site_id = (int)$site_id;
        $keyword_sql = addslashes(mb_substr($keyword, 0, 200));
        $keyword_variant_sql = addslashes(mb_substr(json_encode((array)$platforms), 0, 200));

        // db_pdo_mysql 无 insert() 方法，手工拼接 INSERT SQL（字符串 addslashes 转义）
        $this->db->query("
            INSERT INTO `{$tablepre}ai_search_log`
                (`site_id`, `keyword`, `platform`, `keyword_variant`, `found`, `position`, `confidence`, `created_at`)
            VALUES ({$site_id}, '{$keyword_sql}', '', '{$keyword_variant_sql}', 0, 0, 0, " . (int)$_ENV['_time'] . ")
        ");
        E(0, '品牌词添加成功');
    }

    public function delete_post() {
        if (!form_submit()) {
            E(1, lang('submit_invalid'));
        }

        $id = (int)R('id', 'P');
        empty($id) && E(1, '参数错误');

        $tablepre = $_ENV['_config']['db']['master']['tablepre'];

        $this->db->query("DELETE FROM `{$tablepre}ai_search_log` WHERE id = " . (int)$id . " AND site_id = " . (int)(defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0));
        E(0, '删除成功');
    }
}
