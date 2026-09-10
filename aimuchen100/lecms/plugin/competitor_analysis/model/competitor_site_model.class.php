<?php
/**
 * 竞品站点模型
 * 表: pre_competitor_site
 */
class competitor_site extends model {

    public function __construct() {
        $this->table = 'competitor_site';
        $this->pri = array('id');
        $this->maxid = 'id';
    }

    /**
     * 获取站点下的竞品列表
     * @param int $site_id 站点ID
     * @param string $group 分组筛选（可选）
     * @return array
     */
    public function get_list($site_id, $group = '') {
        $site_id = (int)$site_id;
        $sql = "SELECT * FROM `{$this->db->tablepre}{$this->table}`
                WHERE site_id = {$site_id}";

        if ($group) {
            $group = addslashes($group);
            $sql .= " AND `group` = '{$group}'";
        }

        $sql .= " ORDER BY id DESC";
        return $this->db->fetch_all($sql);
    }

    /**
     * 获取单个站点
     * @param int $id 竞品站点ID
     * @return array|null
     */
    public function get_one($id) {
        return $this->db->fetch_first("SELECT * FROM `{$this->db->tablepre}{$this->table}` WHERE id = " . (int)$id . " LIMIT 1");
    }

    /**
     * 添加竞品站点
     * @param array $data 站点数据
     * @return int 新记录ID
     */
    public function add($data) {
        $data['site_id'] = (int)$data['site_id'];
        $data['status'] = isset($data['status']) ? (int)$data['status'] : 1;
        $data['created_at'] = $_ENV['_time'];
        $data['updated_at'] = $_ENV['_time'];
        return $this->db->insert("`{$this->db->tablepre}{$this->table}`", $data);
    }

    /**
     * 更新竞品站点（与父类 model::update($data, $life) 签名冲突，另命名为 save）
     * @param int $id 竞品站点ID
     * @param array $data 更新数据
     * @return bool
     */
    public function save($id, $data) {
        $data['updated_at'] = $_ENV['_time'];
        $set = array();
        foreach ($data as $k => $v) {
            $set[] = "`{$k}` = '" . addslashes($v) . "'";
        }
        $sql = "UPDATE `{$this->db->tablepre}{$this->table}` SET " . implode(', ', $set) . " WHERE id = " . (int)$id;
        return $this->db->query($sql);
    }

    /**
     * 删除竞品站点（与父类 model::delete($arg1, ...) 签名冲突，另命名为 delete_site）
     * @param int $id 竞品站点ID
     * @return bool
     */
    public function delete_site($id) {
        return $this->db->query("DELETE FROM `{$this->db->tablepre}{$this->table}` WHERE id = " . (int)$id);
    }

    /**
     * 获取待分析的站点列表
     * @param int $site_id 站点ID
     * @param int $days 未分析天数阈值
     * @return array
     */
    public function get_pending_analysis($site_id, $days = 7) {
        $site_id = (int)$site_id;
        $days = (int)$days;
        // last_analyzed_at 为 INT UNSIGNED 时间戳，用 PHP 计算截止时间，兼容 MySQL/SQLite
        $cutoff = $_ENV['_time'] - $days * 86400;
        $sql = "SELECT * FROM `{$this->db->tablepre}{$this->table}`
                WHERE site_id = {$site_id}
                  AND status = 1
                  AND (last_analyzed_at IS NULL OR last_analyzed_at < {$cutoff})
                ORDER BY id ASC";
        return $this->db->fetch_all($sql);
    }

    /**
     * 更新最后分析时间
     * @param int $id 竞品站点ID
     * @return bool
     */
    public function update_analyzed_time($id) {
        // last_analyzed_at 为 INT UNSIGNED，写时间戳而非 NOW()
        return $this->db->query("UPDATE `{$this->db->tablepre}{$this->table}` SET last_analyzed_at = " . (int)$_ENV['_time'] . " WHERE id = " . (int)$id);
    }
}
