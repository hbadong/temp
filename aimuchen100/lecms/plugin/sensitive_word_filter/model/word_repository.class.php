<?php
/**
 * 敏感词数据访问层
 *
 * 封装敏感词库和过滤日志的数据库 CRUD 操作。
 * 所有 SQL 均使用 $db->tablepre + addslashes()/intval() 转义。
 */

// 防止直接访问（仅当通过 HTTP 直接访问时阻止，CLI/require 场景放行）
if (!defined('ROOT_PATH') && php_sapi_name() !== 'cli') {
    exit('Access denied');
}

class word_repository {

    private $db;
    private $tablepre;

    /**
     * 构造函数
     * @param object $db LECMS db_mysql 实例
     */
    public function __construct($db) {
        $this->db       = $db;
        $this->tablepre = $db->tablepre;
    }

    /**
     * 获取站点活跃敏感词列表
     * @param int $site_id 站点 ID
     * @return array 词库列表，每个元素含 id, word, category, level, status
     */
    public function get_active_words($site_id) {
        $site_id = intval($site_id);
        $sql = "SELECT `id`, `word`, `category`, `level`, `status` FROM `{$this->tablepre}sensitive_words` WHERE `site_id`='$site_id' AND `status`=1";
        return $this->db->fetch_all($sql) ?: array();
    }

    /**
     * 添加敏感词
     * @param int $site_id 站点 ID
     * @param string $word 敏感词
     * @param string $category 分类
     * @param int $level 级别 1/2/3
     * @return int 新词条 ID
     */
    public function save_word($site_id, $word, $category, $level) {
        $site_id = intval($site_id);
        $word    = addslashes($word);
        $category = addslashes($category);
        $level   = intval($level);

        $sql = "INSERT INTO `{$this->tablepre}sensitive_words` (`site_id`, `word`, `category`, `level`, `status`) VALUES ('$site_id', '$word', '$category', '$level', '1')";
        // db_pdo_mysql 无 insert_id()：exec() 对 INSERT 返回 last_insert_id
        return (int)$this->db->exec($sql);
    }

    /**
     * 删除敏感词（带 site_id 隔离）
     * @param int $id 词条 ID
     * @param int $site_id 站点 ID
     * @return bool
     */
    public function delete_word($id, $site_id) {
        $id      = intval($id);
        $site_id = intval($site_id);

        $sql = "DELETE FROM `{$this->tablepre}sensitive_words` WHERE `id`='$id' AND `site_id`='$site_id'";
        return $this->db->query($sql);
    }

    /**
     * 记录过滤日志
     * @param int $site_id 站点 ID
     * @param string $word 命中的敏感词
     * @param int $content_id 关联内容 ID
     * @param string $action 处理动作：warning/replace/reject
     * @return bool
     */
    public function log_filter($site_id, $word, $content_id, $action) {
        $site_id    = intval($site_id);
        $word       = addslashes($word);
        $content_id = intval($content_id);
        $action     = addslashes($action);
        $created_at = addslashes(date('Y-m-d H:i:s'));

        $sql = "INSERT INTO `{$this->tablepre}sensitive_log` (`site_id`, `word`, `content_id`, `action`, `created_at`) VALUES ('$site_id', '$word', '$content_id', '$action', '$created_at')";
        return $this->db->query($sql);
    }

    /**
     * 批量导入敏感词
     * @param int $site_id 站点 ID
     * @param array $words 词库数组，每个元素含 word, category, level
     * @return int 实际导入成功数量（去重后）
     */
    public function import_words($site_id, $words) {
        if (empty($words) || !is_array($words)) {
            return 0;
        }

        $site_id = intval($site_id);
        $now     = addslashes(date('Y-m-d H:i:s'));

        // 批量构建 VALUES 列表，使用 INSERT IGNORE + ON DUPLICATE KEY UPDATE 去重
        $values = array();
        foreach ($words as $w) {
            $word     = addslashes($w['word']);
            $category = addslashes($w['category']);
            $level    = intval($w['level']);
            $values[] = "('$site_id', '$word', '$category', '$level', '1', '$now')";
        }

        $sql = "INSERT IGNORE INTO `{$this->tablepre}sensitive_words` (`site_id`, `word`, `category`, `level`, `status`, `created_at`) VALUES " . implode(',', $values) . " ON DUPLICATE KEY UPDATE `category`=VALUES(`category`), `level`=VALUES(`level`)";

        // db_pdo_mysql 无 affected_rows()：用导入前后行数差计算实际新增
        $before = (int)$this->count_words($site_id);
        $this->db->exec($sql);
        $after = (int)$this->count_words($site_id);

        return max(0, $after - $before);
    }

    /**
     * 统计站点敏感词总数（内部辅助）
     * @param int $site_id 站点 ID
     * @return int
     */
    private function count_words($site_id) {
        $rows = $this->db->fetch_all("SELECT COUNT(*) AS cnt FROM `{$this->tablepre}sensitive_words` WHERE `site_id`='$site_id'");
        return isset($rows[0]['cnt']) ? (int)$rows[0]['cnt'] : 0;
    }
}
