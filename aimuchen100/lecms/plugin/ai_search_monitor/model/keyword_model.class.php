<?php
/**
 * 品牌词模型
 * 表: pre_ai_search_log (keyword 配置存储在 ai_search_log 中)
 */
class keyword extends model {

    public function __construct() {
        $this->table = 'ai_search_log';
        $this->pri = array('id');
        $this->maxid = 'id';
    }

    /**
     * 获取站点的品牌词列表（含ID）
     * @param int $site_id 站点ID
     * @return array [{id, keyword}, ...]
     */
    public function get_keywords($site_id) {
        $site_id = (int)$site_id;
        $sql = "SELECT id, keyword, keyword_variant, created_at FROM `{$this->db->tablepre}{$this->table}`
                WHERE site_id = {$site_id}
                  AND found = 0
                GROUP BY keyword
                ORDER BY id ASC";
        return $this->db->fetch_all($sql);
    }

    /**
     * 获取站点的监测记录
     * @param int $site_id 站点ID
     * @param string $platform 平台（可选）
     * @param int $limit 数量限制
     * @return array
     */
    public function get_logs($site_id, $platform = '', $limit = 100) {
        $site_id = (int)$site_id;
        $sql = "SELECT * FROM `{$this->db->tablepre}{$this->table}`
                WHERE site_id = {$site_id}";

        if ($platform) {
            $platform = safe_str($platform);
            $sql .= " AND platform = '{$platform}'";
        }

        $sql .= " ORDER BY checked_at DESC LIMIT " . (int)$limit;
        return $this->db->fetch_all($sql);
    }

    /**
     * 添加监测记录
     * @param array $data 监测数据
     * @return int 新记录ID
     */
    public function add_log($data) {
        $data['site_id'] = (int)$data['site_id'];
        $data['checked_at'] = $_ENV['_time'];
        $data['created_at'] = $_ENV['_time'];
        return $this->db->insert("`{$this->db->tablepre}{$this->table}`", $data);
    }

    /**
     * 获取统计信息
     * @param int $site_id 站点ID
     * @param int $days 天数
     * @return array
     */
    public function get_stats($site_id, $days = 7) {
        $site_id = (int)$site_id;
        $days = (int)$days;
        // checked_at 为 INT UNSIGNED 时间戳，用 PHP 计算截止时间，兼容 MySQL/SQLite
        $cutoff = $_ENV['_time'] - $days * 86400;
        $sql = "SELECT
                    platform,
                    COUNT(*) AS total_checks,
                    SUM(found) AS found_count,
                    AVG(confidence) AS avg_confidence
                FROM `{$this->db->tablepre}{$this->table}`
                WHERE site_id = {$site_id}
                  AND checked_at > {$cutoff}
                GROUP BY platform";
        return $this->db->fetch_all($sql);
    }

    /**
     * 清理过期数据（超过90天）
     * @return int 删除记录数
     */
    public function cleanup_old_logs() {
        // checked_at 为 INT UNSIGNED 时间戳，用 PHP 计算截止时间
        $cutoff = $_ENV['_time'] - 90 * 86400;
        $sql = "DELETE FROM `{$this->db->tablepre}{$this->table}`
                WHERE checked_at < {$cutoff}";
        $this->db->query($sql);
        return $this->db->affected_rows();
    }
}
