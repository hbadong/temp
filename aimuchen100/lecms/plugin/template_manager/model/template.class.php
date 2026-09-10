<?php
defined('ROOT_PATH') || exit;

/**
 * Template 模型 — 主题 CRUD（cms_template 表）
 */
class TplTheme
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
        return "`{$this->tablepre}cms_template`";
    }

    /**
     * 创建主题记录
     */
    public function create($data)
    {
        $name = addslashes(trim($data['name'] ?? ''));
        $title = addslashes(trim($data['title'] ?? ''));
        $description = addslashes(trim($data['description'] ?? ''));
        $version = addslashes(trim($data['version'] ?? '1.0.0'));
        $is_default = !empty($data['is_default']) ? 1 : 0;
        $enabled = !empty($data['enabled']) ? 1 : 0;
        $created_at = $_ENV['_time'];

        return $this->db->query(
            "INSERT INTO " . $this->table() . "
                (`name`, `title`, `description`, `version`, `is_default`, `enabled`, `created_at`)
             VALUES ('{$name}', '{$title}', '{$description}', '{$version}', {$is_default}, {$enabled}, {$created_at})"
        );
    }

    /**
     * 更新主题记录
     */
    public function save($id, $data)
    {
        $id = (int)$id;
        $sets = array();

        if (array_key_exists('name', $data)) {
            $sets[] = "`name` = '" . addslashes(trim($data['name'])) . "'";
        }
        if (array_key_exists('title', $data)) {
            $sets[] = "`title` = '" . addslashes(trim($data['title'])) . "'";
        }
        if (array_key_exists('description', $data)) {
            $sets[] = "`description` = '" . addslashes(trim($data['description'])) . "'";
        }
        if (array_key_exists('version', $data)) {
            $sets[] = "`version` = '" . addslashes(trim($data['version'])) . "'";
        }
        if (array_key_exists('is_default', $data)) {
            $sets[] = "`is_default` = " . (!empty($data['is_default']) ? 1 : 0);
        }
        if (array_key_exists('enabled', $data)) {
            $sets[] = "`enabled` = " . (!empty($data['enabled']) ? 1 : 0);
        }

        if (empty($sets)) {
            return true;
        }

        return $this->db->query("UPDATE " . $this->table() . " SET " . implode(', ', $sets) . " WHERE id = {$id}");
    }

    /**
     * 删除主题记录
     */
    public function delete($id)
    {
        $id = (int)$id;
        return $this->db->query("DELETE FROM " . $this->table() . " WHERE id = {$id}");
    }

    /**
     * 按 ID 读取
     */
    public function get($id)
    {
        $id = (int)$id;
        return $this->db->fetch_first("SELECT * FROM " . $this->table() . " WHERE id = {$id}") ?: [];
    }

    /**
     * 按名称读取
     */
    public function get_by_name($name)
    {
        $name = addslashes($name);
        return $this->db->fetch_first("SELECT * FROM " . $this->table() . " WHERE name = '{$name}'") ?: [];
    }

    /**
     * 全部主题记录（按 id 升序）
     */
    public function get_all()
    {
        return $this->db->fetch_all("SELECT * FROM " . $this->table() . " ORDER BY id ASC") ?: [];
    }

    /**
     * 批量切换站点主题
     *
     * 更新 le_site 表的 theme 字段（若存在）。核心默认主题切换由控制器通过 kv 机制完成。
     *
     * @param array|int $site_ids 站点ID数组，含 0 或空数组时视为仅标记默认
     * @param string $theme 主题标识
     * @return int 受影响站点数
     */
    public function batch_switch($site_ids, $theme)
    {
        $theme = addslashes($theme);
        $site_ids = is_array($site_ids) ? array_map('intval', $site_ids) : array((int)$site_ids);
        $site_ids = array_values(array_unique($site_ids));
        $site_ids = array_diff($site_ids, array(0));
        if (empty($site_ids)) {
            return 0;
        }

        // exist_table 内部会拼接表前缀，此处传不带前缀的表名
        if (!$this->db->exist_table('site')) {
            return 0;
        }

        $site_table = $this->tablepre . 'site';
        $id_str = implode(',', $site_ids);
        return $this->db->query("UPDATE `{$site_table}` SET `theme` = '{$theme}' WHERE `sid` IN ({$id_str})");
    }

    /**
     * 批量刷新模板缓存（站群场景清理 runcache）
     */
    public function batch_refresh_cache($site_ids)
    {
        // 站群缓存目录随站点结构变化，此处统一清理模板渲染缓存文件
        $cache_dir = ROOT_PATH . 'runcache/';
        if (is_dir($cache_dir)) {
            foreach (glob($cache_dir . '*template*') ?: array() as $f) {
                is_file($f) && @unlink($f);
            }
        }
        return true;
    }

    /**
     * 导出 Block 配置（JSON 字符串）
     */
    public function export_block_config($site_id)
    {
        $site_id = (int)$site_id;
        $rows = $this->db->fetch_all(
            "SELECT block_name, target, params, enabled FROM `{$this->tablepre}cms_block_config`
             WHERE site_id = {$site_id} ORDER BY id ASC"
        ) ?: array();
        return _json_encode($rows);
    }

    /**
     * 导入 Block 配置
     */
    public function import_block_config($site_id, $json)
    {
        $data = json_decode($json, true);
        if (!is_array($data)) {
            return false;
        }

        $block = new BlockConfig($this->db);
        $success = 0;
        foreach ($data as $row) {
            if (empty($row['block_name'])) {
                continue;
            }
            if ($block->save($site_id, $row['block_name'], $row['target'] ?? '', $row['params'] ?? array())) {
                $success++;
            }
        }
        return $success;
    }
}
