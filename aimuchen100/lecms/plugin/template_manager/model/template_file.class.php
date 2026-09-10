<?php
defined('ROOT_PATH') || exit;

/**
 * TemplateFile 模型 — 文件版本管理（cms_template_file 表）
 */
class TemplateFile
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
        return "`{$this->tablepre}cms_template_file`";
    }

    /**
     * 保存新版本（将原当前版本置为非当前，写入新版本）
     *
     * @return int 新版本号，失败返回 0
     */
    public function save_version($template_id, $path, $content, $created_by)
    {
        $template_id = (int)$template_id;
        $path = addslashes($path);
        $created_by = (int)$created_by;
        $created_at = (int)$_ENV['_time'];
        $sha256 = hash('sha256', $content);
        $file_size = strlen($content);

        // 先取消旧当前版本
        $this->db->query(
            "UPDATE " . $this->table() . "
             SET `is_current` = 0
             WHERE template_id = {$template_id} AND `path` = '{$path}'"
        );

        // 计算新版本号
        $cur = $this->db->fetch_first(
            "SELECT MAX(version_no) AS maxv FROM " . $this->table() . "
             WHERE template_id = {$template_id} AND `path` = '{$path}'"
        );
        $version_no = ($cur && $cur['maxv']) ? (int)$cur['maxv'] + 1 : 1;

        $content = addslashes($content);

        $ok = $this->db->query(
            "INSERT INTO " . $this->table() . "
                (`template_id`, `path`, `version_no`, `content`, `sha256`, `file_size`, `is_current`, `created_at`, `created_by`)
             VALUES ({$template_id}, '{$path}', {$version_no}, '{$content}', '{$sha256}', {$file_size}, 1, {$created_at}, {$created_by})"
        );

        return $ok ? $version_no : 0;
    }

    /**
     * 获取当前版本
     */
    public function get_current($template_id, $path)
    {
        $template_id = (int)$template_id;
        $path = addslashes($path);
        return $this->db->fetch_first(
            "SELECT * FROM " . $this->table() . "
             WHERE template_id = {$template_id} AND `path` = '{$path}' AND is_current = 1"
        ) ?: null;
    }

    /**
     * 统计版本总数（不拉取 content，性能友好）
     */
    public function count_versions($template_id, $path)
    {
        $template_id = (int)$template_id;
        $path = addslashes($path);
        $row = $this->db->fetch_first(
            "SELECT COUNT(*) AS cnt FROM " . $this->table() . "
             WHERE template_id = {$template_id} AND `path` = '{$path}'"
        );
        return $row ? (int)$row['cnt'] : 0;
    }

    /**
     * 获取指定版本
     */
    public function get_version($template_id, $path, $version_no)
    {
        $template_id = (int)$template_id;
        $version_no = (int)$version_no;
        $path = addslashes($path);
        return $this->db->fetch_first(
            "SELECT * FROM " . $this->table() . "
             WHERE template_id = {$template_id} AND `path` = '{$path}' AND version_no = {$version_no}"
        ) ?: null;
    }

    /**
     * 获取历史版本（默认 20 条，新→旧）
     */
    public function get_history($template_id, $path, $limit = 20)
    {
        $template_id = (int)$template_id;
        $path = addslashes($path);
        $limit = max(1, (int)$limit);
        return $this->db->fetch_all(
            "SELECT * FROM " . $this->table() . "
             WHERE template_id = {$template_id} AND `path` = '{$path}'
             ORDER BY version_no DESC LIMIT {$limit}"
        ) ?: array();
    }

    /**
     * 回滚到指定版本
     *
     * @return array ['ok' => bool, 'version_no' => int, 'content' => string]
     */
    public function rollback($template_id, $path, $version_no)
    {
        $template_id = (int)$template_id;
        $version_no = (int)$version_no;
        $path = addslashes($path);

        $target = $this->db->fetch_first(
            "SELECT * FROM " . $this->table() . "
             WHERE template_id = {$template_id} AND `path` = '{$path}' AND version_no = {$version_no}"
        );
        if (!$target) {
            return array('ok' => false, 'error' => '目标版本不存在');
        }

        // 先写回文件系统（成功后再切换 DB 状态，保证一致性）
        $written = $this->write_back($template_id, $path, $target['content']);
        if (!$written) {
            return array('ok' => false, 'error' => '文件写入失败，请检查目录权限');
        }

        // 取消当前版本
        $this->db->query(
            "UPDATE " . $this->table() . "
             SET `is_current` = 0
             WHERE template_id = {$template_id} AND `path` = '{$path}'"
        );

        // 目标版本置为当前
        $this->db->query(
            "UPDATE " . $this->table() . "
             SET `is_current` = 1
             WHERE id = " . (int)$target['id']
        );

        return array('ok' => true, 'version_no' => (int)$target['version_no'], 'content' => $target['content']);
    }

    /**
     * 将版本内容写回 view/{theme}/{path}
     */
    private function write_back($template_id, $path, $content)
    {
        $template = (new TplTheme($this->db))->get($template_id);
        if (empty($template['name'])) {
            return false;
        }

        // 路径防护：拒绝穿越与绝对路径
        $path = str_replace(array('../', '..\\', '/..', '\\..'), '', $path);
        if ($path === '' || strpos($path, ':') !== false || strpos($path, '\\') !== false || strpos($path, '//') !== false) {
            return false;
        }
        $theme = preg_replace('/[^a-zA-Z0-9_\-]/', '', $template['name']);
        $base = realpath(ROOT_PATH . "view/{$theme}/");
        if (!$base) {
            return false;
        }

        // 先创建目标目录（避免 realpath 对不存在目录返回 false 导致的前缀校验死代码）
        $full = ROOT_PATH . "view/{$theme}/" . trim($path, '/');
        $dir = dirname($full);
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0755, true) && !is_dir($dir)) {
                return false;
            }
        }

        // 前缀校验：目标文件必须位于 view/{theme}/ 内
        $real_dir = realpath($dir);
        if (!$real_dir || strpos($real_dir . DIRECTORY_SEPARATOR, $base . DIRECTORY_SEPARATOR) !== 0) {
            return false;
        }

        // 原子写：临时文件 + rename，避免半写入
        $tmp = $dir . '/.tm_tmp_' . uniqid('', true);
        if (file_put_contents($tmp, $content, LOCK_EX) === false) {
            return false;
        }
        $renamed = @rename($tmp, $full);
        if (!$renamed) {
            @unlink($tmp);
            return false;
        }
        return true;
    }
}
