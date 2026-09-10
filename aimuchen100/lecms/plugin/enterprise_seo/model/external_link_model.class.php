<?php
/**
 * 外链记录模型
 * 表: pre_external_link
 */
class external_link extends model {

    public function __construct() {
        $this->table = 'external_link';
        $this->pri = array('id');
        $this->maxid = 'id';
    }

    /**
     * 获取企业的外链列表
     * @param int $enterprise_id 企业站点ID
     * @return array
     */
    public function get_by_enterprise($enterprise_id) {
        return $this->find_fetch(array('enterprise_id' => (int)$enterprise_id));
    }

    /**
     * 获取待检查的外链列表
     * @param int $limit 数量限制
     * @return array
     */
    public function get_pending_check($limit = 50) {
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];

        // last_check_at 为 DATETIME，用 PHP 计算截止时间，兼容 MySQL/SQLite
        $cutoff = date('Y-m-d H:i:s', $_ENV['_time'] - 86400);

        $sql = "SELECT * FROM `{$tablepre}external_link`
                WHERE status IN (1, 3)  -- 已提交或失败，需要检查
                  AND check_count < 5  -- 最多检查 5 次
                  AND (last_check_at IS NULL OR last_check_at < '{$cutoff}')
                ORDER BY id ASC
                LIMIT " . (int)$limit;

        return $this->db->fetch_all($sql);
    }

    /**
     * 更新外链状态
     * @param int $id 外链记录ID
     * @param int $status 状态
     * @param array $extra 额外数据
     * @return bool
     */
    public function update_status($id, $status, $extra = array()) {
        $data = array(
            'id' => (int)$id,
            'status' => (int)$status,
        );
        $data = array_merge($data, $extra);
        return $this->update($data);
    }

    /**
     * 统计外链效果
     * @param int $enterprise_id 企业站点ID
     * @return array
     */
    public function get_stats($enterprise_id) {
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];

        $sql = "SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) AS indexed,
                    SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) AS submitted,
                    SUM(CASE WHEN status = 3 THEN 1 ELSE 0 END) AS failed
                FROM `{$tablepre}external_link`
                WHERE enterprise_id = " . (int)$enterprise_id;

        $row = $this->db->fetch_first($sql);
        return $row ?: array('total' => 0, 'indexed' => 0, 'submitted' => 0, 'failed' => 0);
    }
}
