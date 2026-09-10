<?php
/**
 * SEO 搜索引擎验证文件模型。
 *
 * 验证文件同时落盘并记录到 pre_seo_verification，所有数据库查询均按
 * site_id 隔离，路径参数经过白名单与目录边界校验。
 */
class seo_verification
{
    /** @var int 当前站点上下文 */
    private $site_id = 0;

    /** @var object|null LECMS 数据库对象 */
    private $db;

    /** @var array 允许的搜索引擎标识 */
    private $engines = array('baidu', 'google', 'sogou', '360', 'bing', 'toutiao', 'shenma');

    /**
     * @param int $site_id 当前站点 ID
     * @param object|null $db 数据库对象；未注入时从 $GLOBALS['run']->db 获取
     */
    public function __construct($site_id = 0, $db = null)
    {
        $this->site_id = (int)$site_id;
        $this->db = $db;
    }

    /**
     * 保存验证文件并写入元数据。
     *
     * @return bool 文件与数据库均写入成功时返回 true
     */
    public function save($site_id, $engine, $filename, $content)
    {
        $engine = (string)$engine;
        $filename = (string)$filename;
        if (!$this->is_valid_engine($engine) || !$this->is_valid_filename($filename)) {
            return false;
        }

        $base = $this->get_base_path();
        $engine_dir = $base . DIRECTORY_SEPARATOR . $engine;
        if (!is_dir($engine_dir) && !@mkdir($engine_dir, 0755, true) && !is_dir($engine_dir)) {
            return false;
        }

        $file_path = $engine_dir . DIRECTORY_SEPARATOR . $filename;
        if (!$this->is_safe_path($base, $file_path)) {
            return false;
        }

        $content = (string)$content;
        if (@file_put_contents($file_path, $content, LOCK_EX) === false) {
            return false;
        }

        try {
            $db = $this->get_db();
        } catch (Throwable $e) {
            @unlink($file_path);
            return false;
        }

        $dateline = isset($_ENV['_time']) ? (int)$_ENV['_time'] : time();
        $sql = "INSERT INTO `{$db->tablepre}seo_verification` "
            . "(`site_id`, `engine`, `filename`, `content`, `file_path`, `dateline`) VALUES ("
            . intval($site_id) . ", '" . addslashes($engine) . "', '" . addslashes($filename) . "', '"
            . addslashes($content) . "', '" . addslashes($file_path) . "', " . $dateline . ")";

        if (!$db->query($sql)) {
            @unlink($file_path);
            return false;
        }
        return true;
    }

    /**
     * 获取指定站点的验证文件内容。
     *
     * 内容以数据库记录为准，文件路径参数仍经过严格校验，防止越权查询。
     *
     * @return string|false
     */
    public function get($site_id, $engine, $filename)
    {
        $engine = (string)$engine;
        $filename = (string)$filename;
        if (!$this->is_valid_engine($engine) || !$this->is_valid_filename($filename)) {
            return false;
        }

        $path = $this->get_base_path() . DIRECTORY_SEPARATOR . $engine . DIRECTORY_SEPARATOR . $filename;
        if (!$this->is_safe_path($this->get_base_path(), $path)) {
            return false;
        }

        try {
            $db = $this->get_db();
        } catch (Throwable $e) {
            return false;
        }

        $sql = "SELECT `content`, `file_path` FROM `{$db->tablepre}seo_verification` WHERE `site_id` = "
            . intval($site_id) . " AND `engine` = '" . addslashes($engine) . "' AND `filename` = '"
            . addslashes($filename) . "' ORDER BY `id` DESC LIMIT 1";
        $row = $db->fetch_first($sql);
        if (is_array($row) && array_key_exists('content', $row)) {
            return (string)$row['content'];
        }
        return false;
    }

    /**
     * 获取站点全部验证文件元数据。
     *
     * @return array
     */
    public function get_list($site_id)
    {
        try {
            $db = $this->get_db();
        } catch (Throwable $e) {
            return array();
        }

        $sql = "SELECT * FROM `{$db->tablepre}seo_verification` WHERE `site_id` = "
            . intval($site_id) . " ORDER BY `dateline` DESC, `id` DESC";
        $rows = $db->fetch_all($sql);
        return is_array($rows) ? $rows : array();
    }

    /**
     * 获取数据库连接，兼容前台 hook 通过 $GLOBALS['run'] 注入连接。
     *
     * @return object
     * @throws RuntimeException
     */
    private function get_db()
    {
        if ($this->db === null && isset($GLOBALS['run']) && is_object($GLOBALS['run'])) {
            try {
                $controller_db = $GLOBALS['run']->db;
                if (is_object($controller_db)) {
                    $this->db = $controller_db;
                }
            } catch (Throwable $e) {
                // 统一转为下方的数据库缺失异常。
            }
        }
        if ($this->db === null) {
            throw new RuntimeException('seo_verification 需要注入 db 对象');
        }
        return $this->db;
    }

    private function get_base_path()
    {
        $root = defined('ROOT_PATH') ? ROOT_PATH : getcwd() . DIRECTORY_SEPARATOR;
        return rtrim($root, '/\\') . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR
            . 'seo-tools' . DIRECTORY_SEPARATOR . 'verification';
    }

    private function is_valid_engine($engine)
    {
        return in_array($engine, $this->engines, true);
    }

    private function is_valid_filename($filename)
    {
        if ($filename === '' || $filename === '.' || $filename === '..' || strpos($filename, '..') !== false) {
            return false;
        }
        return preg_match('/^[A-Za-z0-9.\-]+$/', $filename) === 1;
    }

    /**
     * 检查路径是否位于 base 目录内。
     *
     * base 不存在时返回 false；save() 会先创建目录，再进行检查。
     */
    private function is_safe_path($base, $path)
    {
        $base_real = realpath($base);
        $path_real = realpath($path);
        if ($base_real === false) {
            return false;
        }

        $base_real = rtrim(str_replace('\\', '/', $base_real), '/');
        if ($path_real !== false) {
            $path_real = str_replace('\\', '/', $path_real);
        } else {
            $path_real = $this->normalize_path($path);
        }

        return strpos($path_real . '/', $base_real . '/') === 0;
    }

    private function normalize_path($path)
    {
        $path = str_replace('\\', '/', (string)$path);
        $prefix = '';
        if (preg_match('/^[A-Za-z]:\//', $path)) {
            $prefix = substr($path, 0, 3);
            $path = substr($path, 3);
        } elseif (substr($path, 0, 1) === '/') {
            $prefix = '/';
            $path = ltrim($path, '/');
        }

        $parts = array();
        foreach (explode('/', $path) as $part) {
            if ($part === '' || $part === '.') continue;
            if ($part === '..') {
                array_pop($parts);
                continue;
            }
            $parts[] = $part;
        }
        return $prefix . implode('/', $parts);
    }
}

?>
