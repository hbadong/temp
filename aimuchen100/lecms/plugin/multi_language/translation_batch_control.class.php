<?php
defined('ROOT_PATH') or exit;


/**
 * 批量翻译 + 审核控制器
 */

class translation_batch_control extends admin_control {

    /**
     * 批量翻译页面
     */
    public function index() {
        $site_id = defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0;
        $lang_model = core::model('language_config');
        $enabled_langs = $lang_model->get_enabled($site_id);

        // 过滤默认语言
        $default_lang = $lang_model->get_default($site_id);
        $target_langs = array();
        foreach ($enabled_langs as $lang) {
            if ($lang['language'] != $default_lang) {
                $target_langs[] = $lang;
            }
        }

        $this->assign('target_langs', $target_langs);
        $this->display('translation_batch.htm');
    }

    /**
     * 执行批量翻译
     */
    public function translate_post() {
        if (!form_submit()) {
            E(1, lang('submit_invalid'));
        }

        $site_id = defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0;
        $article_ids = isset($_POST['article_ids']) ? $_POST['article_ids'] : array();
        $languages = isset($_POST['languages']) ? $_POST['languages'] : array();

        if (empty($article_ids) || empty($languages)) {
            E(1, '请选择文章和目标语言');
        }

        $queue = new translation_queue($site_id, $this->db);
        $results = $queue->batch_translate($article_ids, $languages);

        $success = 0;
        $fail = 0;
        foreach ($results as $r) {
            if ($r['success']) {
                $success++;
            } else {
                $fail++;
            }
        }

        E(0, "批量翻译完成：成功 {$success} 篇，失败 {$fail} 篇");
    }

    /**
     * 翻译审核页面
     */
    public function verify() {
        $site_id = defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0;
        $page = max(1, (int)R('page', 'R'));
        $pagenum = 20;

        $tablepre = $_ENV['_config']['db']['master']['tablepre'];

        $sql = "SELECT t.*, a.title AS source_title
                FROM `{$tablepre}article_translation` t
                INNER JOIN `{$tablepre}cms_article` a ON t.source_id = a.id
                WHERE t.translator_status IN (2, 3)
                ORDER BY t.id DESC
                LIMIT {$pagenum} OFFSET " . (($page - 1) * $pagenum);

        $list = $this->db->fetch_all($sql);

        $this->assign('list', $list);
        $this->display('translation_verify.htm');
    }

    /**
     * 标记为已验证
     */
    public function verify_post() {
        if (!form_submit()) {
            E(1, lang('submit_invalid'));
        }

        $id = (int)R('id', 'P');
        $action = R('action', 'P'); // verify 或 re-translate

        $site_id = defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0;

        // 验证翻译记录存在性：联表确认 source_id 对应的文章存在
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];
        $verify = $this->db->fetch_first("
            SELECT t.id
            FROM `{$tablepre}article_translation` t
            INNER JOIN `{$tablepre}cms_article` a ON t.source_id = a.id
            WHERE t.id = {$id} LIMIT 1
        ");

        if (!$verify) {
            E(1, '翻译记录不存在或不属于当前站点');
        }

        $trans_model = core::model('article_translation');

        if ($action == 'verify') {
            $trans_model->update_status($id, 3, array(
                'verified_by' => defined('CURRENT_USER_ID') ? CURRENT_USER_ID : 0,
                'verified_at' => $_ENV['_time'],
            ));
            E(0, '翻译已标记为已验证');
        } elseif ($action == 're-translate') {
            // 重新翻译：回滚到 pending，清空标题和内容
            $trans_model->update_status($id, 0, array(
                'title' => '',
                'content' => '',
                'verified_by' => 0,
                'verified_at' => null,
            ));
            E(0, '已重置为待翻译，将重新执行翻译');
        }

        E(1, '未知操作');
    }
}
