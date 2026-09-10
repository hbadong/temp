<?php
/**
 * 翻译任务队列
 * 管理翻译任务的生命周期，支持批量生成
 */
class translation_queue {

    private $site_id;
    private $engine;
    private $db;

    public function __construct($site_id = 0, $db = null) {
        $this->site_id = (int)$site_id;
        $this->db = $db;
        $this->engine = new translation_engine($site_id, $db);
    }

    /**
     * 为单篇文章创建翻译任务（为所有启用的非默认语言）
     * @param int $article_id 原文文章ID
     * @return array 创建的任务列表 [{language, translation_id, status}, ...]
     */
    public function create_for_article($article_id) {
        $lang_model = core::model('language_config');
        $enabled_langs = $lang_model->get_enabled($this->site_id);

        // 过滤掉默认语言（默认语言不需要翻译）
        $default_lang = $lang_model->get_default($this->site_id);

        $tasks = array();
        foreach ($enabled_langs as $lang) {
            if ($lang['language'] == $default_lang) {
                continue;
            }

            $trans_model = core::model('article_translation');
            $record = $trans_model->get_or_create($article_id, $lang['language']);

            $tasks[] = array(
                'language' => $lang['language'],
                'translation_id' => $record['id'],
                'status' => $record['translator_status'],
            );
        }

        return $tasks;
    }

    /**
     * 批量为文章创建翻译任务
     * @param array $article_ids 文章ID列表
     * @param array $languages 目标语言代码列表（空=所有启用语言）
     * @return int 创建的任务总数
     */
    public function batch_create($article_ids, $languages = array()) {
        $lang_model = core::model('language_config');
        $enabled_langs = $lang_model->get_enabled($this->site_id);

        if (empty($languages)) {
            $languages = array_column($enabled_langs, 'language');
        }

        $total = 0;
        $trans_model = core::model('article_translation');

        foreach ($article_ids as $aid) {
            foreach ($languages as $lang) {
                $record = $trans_model->get_or_create($aid, $lang);
                if ($record['translator_status'] == 0) {
                    $total++;
                }
            }
        }

        return $total;
    }

    /**
     * 执行翻译任务（逐篇调用引擎，翻译成功落库 title/content 并置 status=2）
     * @param int $translation_id 翻译记录ID
     * @return bool 是否成功
     */
    public function execute($translation_id) {
        $trans_model = core::model('article_translation');
        $record = $trans_model->get($translation_id);

        if (!$record) {
            return false;
        }

        // 避免重复执行：translating 状态说明正在执行中
        if ($record['translator_status'] == 1) {
            return false;
        }

        // 标记执行中（防并发）
        $trans_model->update_status($translation_id, 1);

        $result = $this->engine->translate(
            $record['source_id'],
            $record['language'],
            $record['prompt_template']
        );

        if ($result === false) {
            $trans_model->update_status($translation_id, 0); // 回滚 pending
            return false;
        }

        // 落库翻译结果：title/content + 状态置 2（已翻译完成）
        $trans_model->update_status($translation_id, 2, array(
            'title' => isset($result['title']) ? (string)$result['title'] : '',
            'content' => isset($result['content']) ? (string)$result['content'] : '',
        ));

        return true;
    }

    /**
     * 批量执行翻译（控制器入口，对应批量翻译任务调度）
     *
     * @param array $article_ids 原文文章ID列表
     * @param array $languages 目标语言列表（空=所有启用的非默认语言）
     * @return array 每条任务的执行结果 [{article_id, language, translation_id, success, error}, ...]
     */
    public function batch_translate($article_ids, $languages = array()) {
        $trans_model = core::model('article_translation');

        // 取每个文章的 translation 记录（确保翻译任务已创建）
        $results = array();
        foreach ($article_ids as $aid) {
            // 若 languages 为空，回退到所有启用的非默认语言
            $langs = $languages;
            if (empty($langs)) {
                $lang_model = core::model('language_config');
                $enabled = $lang_model->get_enabled($this->site_id);
                $default = $lang_model->get_default($this->site_id);
                foreach ($enabled as $l) {
                    if ($l['language'] != $default) $langs[] = $l['language'];
                }
            }
            foreach ($langs as $lang) {
                $record = $trans_model->get_or_create((int)$aid, $lang);
                $ok = $this->execute((int)$record['id']);
                $results[] = array(
                    'article_id' => (int)$aid,
                    'language' => $lang,
                    'translation_id' => (int)$record['id'],
                    'success' => $ok === true,
                    'error' => $ok === true ? '' : (method_exists($this->engine, 'get_last_error') ? $this->engine->get_last_error() : ''),
                );
            }
        }

        return $results;
    }

    /**
     * 获取待执行的翻译任务列表
     * @param int $limit 数量限制
     * @return array 待翻译记录列表
     */
    public function get_pending($limit = 50) {
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];
        $limit = (int)$limit;

        $sql = "SELECT t.*, a.site_id
                FROM `{$tablepre}article_translation` t
                INNER JOIN `{$tablepre}cms_article` a ON t.source_id = a.id
                WHERE t.translator_status = 0
                ORDER BY t.id ASC
                LIMIT {$limit}";

        return $this->db->fetch_all($sql);
    }

    /**
     * 获取引擎实例（供外部直接使用）
     */
    public function get_engine() {
        return $this->engine;
    }
}
