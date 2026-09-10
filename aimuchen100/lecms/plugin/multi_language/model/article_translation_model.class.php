<?php
/**
 * 文章翻译关联模型
 * 表: le_article_translation
 */
class article_translation extends model {

    public function __construct() {
        $this->table = 'article_translation';
        $this->pri = array('id');
        $this->maxid = 'id';
    }

    /**
     * 获取文章的翻译记录列表
     * @param int $source_id 原文文章ID
     * @return array 翻译记录列表
     */
    public function get_by_source($source_id) {
        $source_id = (int)$source_id;
        return $this->find_fetch(array('source_id' => $source_id));
    }

    /**
     * 获取指定文章指定语言的翻译记录
     * @param int $source_id 原文文章ID
     * @param string $language 语言代码
     * @return array|false
     */
    public function get_by_source_lang($source_id, $language) {
        $source_id = (int)$source_id;
        $language = addslashes($language);
        $row = $this->find_fetch(array('source_id' => $source_id, 'language' => $language), array(), 0, 1);
        return !empty($row) ? $row[0] : false;
    }

    /**
     * 获取或创建翻译记录（幂等操作，使用 INSERT IGNORE 避免竞态条件）
     * @param int $source_id 原文文章ID
     * @param string $language 语言代码
     * @return array 翻译记录（已有则返回已有，否则创建新记录）
     */
    public function get_or_create($source_id, $language) {
        $record = $this->get_by_source_lang($source_id, $language);
        if ($record) {
            return $record;
        }

        $tablepre = $_ENV['_config']['db']['master']['tablepre'];
        $source_id = (int)$source_id;
        $language = addslashes($language);

        // 使用 INSERT IGNORE 原子操作避免竞态条件
        $this->db->query("INSERT IGNORE INTO `{$tablepre}article_translation`
            (source_id, language, translator_status)
            VALUES ({$source_id}, '{$language}', 0)");

        // 无论插入是否实际执行（IGNORE 忽略重复），都能正确获取记录
        $record = $this->get_by_source_lang($source_id, $language);
        return $record;
    }

    /**
     * 更新翻译状态
     * @param int $id 翻译记录ID
     * @param int $status 状态：0=pending, 1=translating, 2=translated, 3=verified
     * @param array $extra 额外更新字段（title, content, ai_model, prompt_template 等）
     * @return bool
     */
    public function update_status($id, $status, $extra = array()) {
        $data = array('id' => (int)$id, 'translator_status' => (int)$status);
        $data = array_merge($data, $extra);
        return $this->update($data);
    }

    /**
     * 获取站点下待翻译的文章列表（排除已 verified 的记录）
     * @param int $site_id 站点ID
     * @param string $language 目标语言
     * @param int $limit 数量限制
     * @return array 原文文章列表 [{id, title, content, cid, subject}, ...]
     */
    public function get_pending_articles($site_id, $language, $limit = 50) {
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];
        $site_id = (int)$site_id;
        $language = addslashes($language);
        $limit = (int)$limit;

        $sql = "SELECT a.id, a.title, d.content, a.cid
                FROM `{$tablepre}cms_article` a
                LEFT JOIN `{$tablepre}cms_article_data` d ON a.id = d.id
                INNER JOIN `{$tablepre}article_translation` t ON a.id = t.source_id
                WHERE t.language = '{$language}'
                  AND t.translator_status IN (0, 1)
                ORDER BY t.id ASC
                LIMIT {$limit}";

        return $this->db->fetch_all($sql);
    }

    /**
     * 统计翻译进度
     * @param int $source_id 原文文章ID
     * @return array [{total, translated, verified}]
     */
    public function get_progress($source_id) {
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];
        $source_id = (int)$source_id;

        $sql = "SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN translator_status >= 2 THEN 1 ELSE 0 END) AS translated,
                    SUM(CASE WHEN translator_status = 3 THEN 1 ELSE 0 END) AS verified
                FROM `{$tablepre}article_translation`
                WHERE source_id = {$source_id}";

        $row = $this->db->fetch_first($sql);
        return $row ?: array('total' => 0, 'translated' => 0, 'verified' => 0);
    }
}
