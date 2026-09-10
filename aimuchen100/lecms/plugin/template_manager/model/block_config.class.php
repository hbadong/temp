<?php
defined('ROOT_PATH') || exit;

/**
 * BlockConfig 模型 — Block 配置读写（cms_block_config 表）
 */
class BlockConfig
{
    private $db;
    private $tablepre;

    public function __construct($db = null)
    {
        $this->db = $db ?: ($GLOBALS['db'] ?? null);
        $this->tablepre = $_ENV['_config']['db']['master']['tablepre'];
    }

    private function table()
    {
        return "`{$this->tablepre}cms_block_config`";
    }

    /**
     * 读取配置
     *
     * @param int $site_id 站点ID
     * @param string $block_name Block名称
     * @param string|null $target 页面/URL pattern，null 返回该 Block 全部配置
     * @return array 单条配置或配置数组
     */
    public function get($site_id, $block_name, $target = null)
    {
        $site_id = (int)$site_id;
        $block_name = addslashes($block_name);

        if ($target === null) {
            return $this->db->fetch_all(
                "SELECT * FROM " . $this->table() . "
                 WHERE site_id = {$site_id} AND block_name = '{$block_name}'
                 ORDER BY id DESC"
            ) ?: [];
        }

        $target = addslashes($target);
        return $this->db->fetch_first(
            "SELECT * FROM " . $this->table() . "
             WHERE site_id = {$site_id} AND block_name = '{$block_name}' AND target = '{$target}'"
        ) ?: [];
    }

    /**
     * 按 ID 读取
     */
    public function get_by_id($id)
    {
        $id = (int)$id;
        return $this->db->fetch_first(
            "SELECT * FROM " . $this->table() . " WHERE id = {$id}"
        ) ?: [];
    }

    /**
     * 保存配置（存在则更新，不存在则插入）
     */
    public function save($site_id, $block_name, $target, $params)
    {
        $site_id = (int)$site_id;
        $block_name = addslashes($block_name);
        $target = addslashes($target);
        $params_json = _json_encode(is_array($params) ? $params : []);
        $params_json = addslashes($params_json);
        $updated_at = $_ENV['_time'];

        return $this->db->query(
            "INSERT INTO " . $this->table() . "
                (`site_id`, `block_name`, `target`, `params`, `enabled`, `updated_at`)
             VALUES ({$site_id}, '{$block_name}', '{$target}', '{$params_json}', 1, {$updated_at})
             ON DUPLICATE KEY UPDATE
                `params` = VALUES(`params`),
                `enabled` = 1,
                `updated_at` = VALUES(`updated_at`)"
        );
    }

    /**
     * 按 ID 更新配置（用于编辑保存，避免先删后写覆盖其它记录）
     */
    public function update_by_id($id, $site_id, $block_name, $target, $params)
    {
        $id = (int)$id;
        $site_id = (int)$site_id;
        $block_name = addslashes($block_name);
        $target = addslashes($target);
        $params_json = addslashes(_json_encode(is_array($params) ? $params : []));
        $updated_at = $_ENV['_time'];

        return $this->db->query(
            "UPDATE " . $this->table() . "
             SET site_id = {$site_id}, block_name = '{$block_name}', target = '{$target}',
                 params = '{$params_json}', enabled = 1, updated_at = {$updated_at}
             WHERE id = {$id}"
        );
    }

    /**
     * 更新启停状态
     */
    public function set_enabled($id, $enabled)
    {
        $id = (int)$id;
        $enabled = $enabled ? 1 : 0;
        return $this->db->query("UPDATE " . $this->table() . " SET enabled = {$enabled} WHERE id = {$id}");
    }

    /**
     * 删除配置
     */
    public function delete($id)
    {
        $id = (int)$id;
        return $this->db->query("DELETE FROM " . $this->table() . " WHERE id = {$id}");
    }

    /**
     * 列表（后台页用）
     */
    public function list_configs($site_id = 0, $page = 1, $pagenum = 20)
    {
        $site_id = (int)$site_id;
        $page = max(1, (int)$page);
        $offset = ($page - 1) * $pagenum;

        $where = '';
        if (defined('CURRENT_SITE_ID') && CURRENT_SITE_ID) {
            $where = "WHERE site_id = " . (int)CURRENT_SITE_ID;
        } elseif ($site_id > 0) {
            $where = "WHERE site_id = {$site_id}";
        }

        $total_row = $this->db->fetch_first(
            "SELECT COUNT(*) AS cnt FROM " . $this->table() . " {$where}"
        );
        $list = $this->db->fetch_all(
            "SELECT * FROM " . $this->table() . " {$where}
             ORDER BY id DESC LIMIT {$pagenum} OFFSET {$offset}"
        ) ?: [];

        return array('total' => $total_row ? (int)$total_row['cnt'] : 0, 'list' => $list);
    }

    /**
     * 取站点（含全局 site_id=0）全部启用的 Block 配置（前台渲染 hook 用）
     *
     * @param int $site_id 站点ID
     * @param object|null $db 可选独立 db 实例（hook 场景 view 类无 db 属性）
     * @return array
     */
    public static function list_all_enabled($site_id = 0, $db = null)
    {
        if (!$db) return array();
        $tp = $_ENV['_config']['db']['master']['tablepre'];
        $site_id = (int)$site_id;
        $rows = $db->fetch_all(
            "SELECT block_name, target, params FROM `{$tp}cms_block_config`
             WHERE enabled = 1 AND (site_id = 0 OR site_id = {$site_id})
             ORDER BY id ASC"
        ) ?: [];
        foreach ($rows as &$row) {
            $decoded = json_decode($row['params'], true);
            $row['params'] = is_array($decoded) ? $decoded : array();
        }
        unset($row);
        return $rows;
    }
}
