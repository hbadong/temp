<?php
defined('ROOT_PATH') || exit;

/**
 * AuditLog 模型 — 审计日志（cms_template_audit_log 表）
 */
class AuditLog
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
        return "`{$this->tablepre}cms_template_audit_log`";
    }

    /**
     * 记录操作
     */
    public function log($operator_uid, $template_id, $path, $action, $old_version, $new_version)
    {
        $operator_uid = (int)$operator_uid;
        $template_id = $template_id === null ? 'NULL' : (int)$template_id;
        $path = addslashes((string)$path);
        $action = addslashes((string)$action);
        $old_version = $old_version === null ? 'NULL' : (int)$old_version;
        $new_version = $new_version === null ? 'NULL' : (int)$new_version;
        $ip = addslashes(substr($_ENV['_ip'] ?? '0.0.0.0', 0, 45));
        $user_agent = addslashes(substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255));
        $created_at = $_ENV['_time'];

        return $this->db->query(
            "INSERT INTO " . $this->table() . "
                (`operator_uid`, `template_id`, `path`, `action`, `old_version`, `new_version`, `ip`, `user_agent`, `created_at`)
             VALUES ({$operator_uid}, {$template_id}, '{$path}', '{$action}', {$old_version}, {$new_version}, '{$ip}', '{$user_agent}', {$created_at})"
        );
    }

    /**
     * 日志列表（后台页用）
     */
    public function get_logs($page = 1, $pagenum = 20, $template_id = null)
    {
        $page = max(1, (int)$page);
        $offset = ($page - 1) * $pagenum;
        $where = '';
        if ($template_id !== null) {
            $where = " WHERE template_id = " . (int)$template_id;
        }

        $total_row = $this->db->fetch_first(
            "SELECT COUNT(*) AS cnt FROM " . $this->table() . " {$where}"
        );
        $list = $this->db->fetch_all(
            "SELECT * FROM " . $this->table() . " {$where}
             ORDER BY id DESC LIMIT {$pagenum} OFFSET {$offset}"
        ) ?: array();

        return array('total' => $total_row ? (int)$total_row['cnt'] : 0, 'list' => $list);
    }

    /**
     * 清空日志
     */
    public function clear()
    {
        return $this->db->query("TRUNCATE TABLE " . $this->table());
    }
}
