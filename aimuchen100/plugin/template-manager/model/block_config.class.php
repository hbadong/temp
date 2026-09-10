<?php
/**
 * BlockConfig - Block 配置模型
 *
 * 管理 pre_cms_block_config 表的 CRUD 操作。
 * 提供站点级 Block 配置的读取、写入、启用/禁用等功能。
 *
 * @author     沐尘100
 * @version    1.0.0
 * @cms_version 3.0.0
 */
class BlockConfig
{
    /** @var int 站点 ID */
    private $site_id;
    /** @var db_mysql 数据库实例 */
    private $db;
    /** @var string 表前缀 */
    private $tablepre;

    public function __construct($site_id, $db) {
        $this->site_id = (int)$site_id;
        $this->db = $db;
        $this->tablepre = isset($db->tablepre) ? $db->tablepre : '';
    }

    /**
     * 获取单个 Block 配置
     *
     * @param   string  $block_name  Block 标识
     * @param   string  $target      目标位置（可选）
     * @return  array|null  配置数组或 null
     */
    public function get_config($block_name, $target = '') {
        $target_cond = $target !== '' ? "AND `target` = '" . addslashes($target) . "'" : '';
        $row = $this->db->fetch_first("
            SELECT `params`, `enabled`
            FROM `{$this->tablepre}cms_block_config`
            WHERE `site_id` = '{$this->site_id}'
            AND `block_name` = '" . addslashes($block_name) . "'
            {$target_cond}
            LIMIT 1
        ");
        if ($row) {
            $params = json_decode($row['params'], true);
            return is_array($params) ? $params : array();
        }
        return null;
    }

    /**
     * 获取站点所有 Block 配置
     *
     * @param   int     $site_id  站点 ID（0 使用当前站点）
     * @return  array   block_name => params 的关联数组
     */
    public function get_all_configs($site_id = 0) {
        $sid = $site_id > 0 ? $site_id : $this->site_id;
        $rows = $this->db->fetch_all("
            SELECT `block_name`, `params`, `enabled`
            FROM `{$this->tablepre}cms_block_config`
            WHERE `site_id` = '{$sid}' AND `enabled` = 1
        ");
        $configs = array();
        foreach ($rows as $row) {
            $params = json_decode($row['params'], true);
            $configs[$row['block_name']] = is_array($params) ? $params : array();
        }
        return $configs;
    }

    /**
     * 保存 Block 配置（插入或更新）
     *
     * @param   string  $block_name  Block 标识
     * @param   array   $params      参数数组
     * @param   string  $target      目标位置（可选）
     * @param   int     $site_id     站点 ID（0 使用当前站点）
     * @return  bool   是否成功
     */
    public function save_config($block_name, $params, $target = '', $site_id = 0) {
        $sid = $site_id > 0 ? $site_id : $this->site_id;
        $params_json = json_encode($params);
        $dateline = isset($_ENV['_time']) ? $_ENV['_time'] : time();

        $result = $this->db->query("
            INSERT INTO `{$this->tablepre}cms_block_config`
            (`site_id`, `block_name`, `target`, `params`, `enabled`, `updated_at`)
            VALUES ('{$sid}', '" . addslashes($block_name) . "', '"
            . addslashes($target) . "', '"
            . addslashes($params_json) . "', '1', '{$dateline}')
            ON DUPLICATE KEY UPDATE
            `params` = VALUES(`params`),
            `target` = VALUES(`target`),
            `updated_at` = VALUES(`updated_at`)
        ");
        return $result !== false;
    }

    /**
     * 删除 Block 配置
     *
     * @param   string  $block_name  Block 标识
     * @param   string  $target      目标位置（可选）
     * @param   int     $site_id     站点 ID（0 使用当前站点）
     * @return  bool   是否成功
     */
    public function delete_config($block_name, $target = '', $site_id = 0) {
        $sid = $site_id > 0 ? $site_id : $this->site_id;
        $target_cond = $target !== '' ? "AND `target` = '" . addslashes($target) . "'" : '';
        $result = $this->db->query("
            DELETE FROM `{$this->tablepre}cms_block_config`
            WHERE `site_id` = '{$sid}'
            AND `block_name` = '" . addslashes($block_name) . "'
            {$target_cond}
        ");
        return $result !== false;
    }

    /**
     * 启用 Block 配置
     *
     * @param   string  $block_name  Block 标识
     * @param   int     $site_id     站点 ID（0 使用当前站点）
     * @return  bool   是否成功
     */
    public function enable($block_name, $site_id = 0) {
        return $this->set_enabled($block_name, 1, $site_id);
    }

    /**
     * 禁用 Block 配置
     *
     * @param   string  $block_name  Block 标识
     * @param   int     $site_id     站点 ID（0 使用当前站点）
     * @return  bool   是否成功
     */
    public function disable($block_name, $site_id = 0) {
        return $this->set_enabled($block_name, 0, $site_id);
    }

    /**
     * 设置 Block 配置启用状态
     *
     * @param   string  $block_name  Block 标识
     * @param   int     $enabled     启用状态（0/1）
     * @param   int     $site_id     站点 ID（0 使用当前站点）
     * @return  bool   是否成功
     */
    private function set_enabled($block_name, $enabled, $site_id = 0) {
        $sid = $site_id > 0 ? $site_id : $this->site_id;
        $result = $this->db->query("
            UPDATE `{$this->tablepre}cms_block_config`
            SET `enabled` = '{$enabled}'
            WHERE `site_id` = '{$sid}'
            AND `block_name` = '" . addslashes($block_name) . "'
        ");
        return $result !== false;
    }
}
