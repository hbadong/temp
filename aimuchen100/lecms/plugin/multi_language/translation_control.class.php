<?php
defined('ROOT_PATH') or exit;


/**
 * 翻译记录列表控制器
 */

class translation_control extends admin_control {

    public function index() {
        $site_id = defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0;
        $page = max(1, (int)R('page', 'R'));
        $pagenum = 20;

        // 筛选条件（未传 status 时视为"全部"，用 -1 表示）
        $language = trim(R('language', 'G'));
        $status_raw = R('status', 'G');
        $status = ($status_raw === null || $status_raw === '') ? -1 : (int)$status_raw;

        // 联表查询：翻译记录 + 原文
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];

        // 注意：le_cms_article 表无 site_id 列（LeCMS 标准表结构），且文章标题字段为 title 而非 subject
        $sql = "SELECT t.*, a.title AS source_title, a.cid
                FROM `{$tablepre}article_translation` t
                INNER JOIN `{$tablepre}cms_article` a ON t.source_id = a.id
                WHERE 1";

        $extra = array();
        if ($language) {
            $language_val = $language;
            $language = addslashes($language);
            $sql .= " AND t.language = '{$language}'";
            $extra['language'] = $language_val;
        }

        if ($status >= 0 && $status <= 3) {
            $sql .= " AND t.translator_status = {$status}";
            $extra['status'] = $status;
        }

        // 分页（COUNT 与列表共用同一 WHERE）
        $count_sql = "SELECT COUNT(*) AS cnt
                FROM `{$tablepre}article_translation` t
                INNER JOIN `{$tablepre}cms_article` a ON t.source_id = a.id
                WHERE 1";
        if ($language) {
            $count_sql .= " AND t.language = '{$language}'";
        }
        if ($status >= 0 && $status <= 3) {
            $count_sql .= " AND t.translator_status = {$status}";
        }
        $row = $this->db->fetch_first($count_sql);
        $total = $row ? $row['cnt'] : 0;

        $offset = ($page - 1) * $pagenum;
        $sql .= " ORDER BY t.id DESC LIMIT {$pagenum} OFFSET {$offset}";

        $list = $this->db->fetch_all($sql);

        // 获取可用语言列表（用于筛选下拉框）
        $lang_model = core::model('language_config');
        $enabled_langs = $lang_model->get_enabled($site_id);
        $default_lang = $lang_model->get_default($site_id);
        $target_langs = array();
        foreach ($enabled_langs as $lang) {
            if ($lang['language'] != $default_lang) {
                $target_langs[] = $lang;
            }
        }

        $language_val2 = $language ? stripslashes($language) : '';
        $this->assign('list', $list);
        $this->assign('total', $total);
        $this->assign('language', $language_val2);
        $this->assign('status', $status);
        $this->assign('target_langs', $target_langs);
        $pagebar = $this->get_pagebar($total, $pagenum, $page, 5, $extra); $this->assign('pagebar', $pagebar);
        $this->display('translation_list.htm');
    }
}
