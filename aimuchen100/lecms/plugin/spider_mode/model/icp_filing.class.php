<?php
/**
 * IcpFiling - 备案页部署机制
 *
 * 负责管理 ICP 备案页的部署和获取：
 * - 从 beian.zip 解压备案页文件到 runtime/spider_mode/beian/
 * - 按优先级获取备案页 HTML：文件 > 数据库 > 默认模板
 * - 路径安全检查，防止目录遍历攻击
 *
 * 数据表：pre_spider_icp_page（site_id, html_content, updated_at）
 *
 * @author     沐尘100
 * @version    1.0.0
 * @cms_version 3.0.0
 */

class IcpFiling
{
    /**
     * @var int 站点 ID
     */
    private $site_id;

    /**
     * @var object|null 数据库对象（真实环境注入 $this->db）
     */
    private $db;

    /**
     * 备案页文件存储目录
     */
    const BEIAN_DIR = 'runtime/spider_mode/beian';

    /**
     * 默认备案页模板
     */
    const DEFAULT_HTML = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>ICP 备案信息</title></head><body><div class="icp-filing-page"><h1>ICP 备案信息</h1><p>本站已完成 ICP 备案。</p></div></body></html>';

    /**
     * 构造函数
     *
     * @param   int     $site_id  站点 ID（多站点隔离）
     * @param   object  $db       数据库对象（LECMS 的 db_mysql 实例，如 $this->db）
     */
    public function __construct($site_id, $db = null)
    {
        $this->site_id = (int)$site_id;
        $this->db = $db;
    }

    /**
     * 解压 beian.zip 到 runtime/spider_mode/beian/ 目录
     *
     * 解压前先备份目录中已有文件，解压失败时恢复备份，
     * 确保旧备案页内容不丢失。
     *
     * @param   string  $zip_path  beian.zip 文件路径
     * @return  bool               解压成功返回 true，失败返回 false
     */
    public function extract_zip($zip_path)
    {
        if (!file_exists($zip_path) || !is_readable($zip_path)) {
            return false;
        }

        $beian_dir = self::BEIAN_DIR;

        // 确保目录存在
        if (!is_dir($beian_dir)) {
            if (!mkdir($beian_dir, 0777, true)) {
                return false;
            }
        }

        // 备份旧文件
        $backup_files = [];
        if (is_dir($beian_dir)) {
            $files = scandir($beian_dir);
            if ($files !== false) {
                foreach ($files as $file) {
                    if ($file === '.' || $file === '..') {
                        continue;
                    }
                    $file_path = $beian_dir . '/' . $file;
                    if (is_file($file_path)) {
                        $backup_files[$file] = file_get_contents($file_path);
                    }
                }
            }
        }

        // 先打开 zip（保持文件句柄），再清空目录
        $zip = new ZipArchive();
        $result = $zip->open($zip_path);

        // ZipArchive::open() 成功时返回 true 或 1（PHP 版本差异）
        if ($result !== true && $result !== 1) {
            $this->restore_backup($beian_dir, $backup_files);
            return false;
        }

        // 清空目录中的文件（保留目录结构）
        $this->clear_directory($beian_dir);

        $extract_ok = true;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->getNameIndex($i);
            $content = $zip->getFromIndex($i);

            // 路径安全检查
            if (!$this->is_safe_path($beian_dir . '/' . $entry)) {
                $extract_ok = false;
                continue;
            }

            // 只解压文件，不解压目录条目
            if (substr($entry, -1) === '/') {
                if (!is_dir($beian_dir . '/' . $entry)) {
                    mkdir($beian_dir . '/' . $entry, 0777, true);
                }
                continue;
            }

            $target_path = $beian_dir . '/' . $entry;
            $target_dir = dirname($target_path);
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            if (file_put_contents($target_path, $content) === false) {
                $extract_ok = false;
            }
        }

        $zip->close();

        if (!$extract_ok) {
            // 部分解压失败，恢复备份
            $this->clear_directory($beian_dir);
            $this->restore_backup($beian_dir, $backup_files);
            return false;
        }

        return true;
    }

    /**
     * 获取备案页 HTML
     *
     * 优先级：文件 > 数据库（pre_spider_icp_page） > 默认模板
     *
     * @return  string  备案页 HTML 内容
     */
    public function get_html()
    {
        // 优先级 1: 文件
        $file_html = $this->get_html_from_file();
        if ($file_html !== false) {
            return $file_html;
        }

        // 优先级 2: 数据库
        $db_html = $this->get_html_from_db();
        if ($db_html !== false) {
            return $db_html;
        }

        // 优先级 3: 默认模板
        return self::DEFAULT_HTML;
    }

    /**
     * 路径安全检查，防止目录遍历
     *
     * 检查给定路径是否在备案目录内，防止通过 ../ 等方式访问外部文件。
     *
     * @param   string  $path  待检查的路径
     * @return  bool           安全返回 true，不安全返回 false
     */
    public function is_safe_path($path)
    {
        $beian_dir = realpath(self::BEIAN_DIR);

        if ($beian_dir === false) {
            return false;
        }

        $real_path = realpath($path);

        // 路径不存在时，通过规范化路径进行安全检查
        if ($real_path === false) {
            // 将相对路径转为基于当前工作目录的绝对路径
            if (!preg_match('#^[a-zA-Z]:[\\\\/]#', $path)) {
                $resolved_path = rtrim(getcwd(), '/\\') . DIRECTORY_SEPARATOR . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $path), '/\\');
            } else {
                $resolved_path = str_replace('/', DIRECTORY_SEPARATOR, $path);
            }

            $normalized_beian = rtrim($beian_dir, '/\\');
            $normalized_path  = rtrim($resolved_path, '/\\');

            // 防止空路径
            if (empty($normalized_path) || empty($normalized_beian)) {
                return false;
            }

            // 检查是否以备案目录开头（防止 ../ 绕过）
            $prefix = $normalized_beian . DIRECTORY_SEPARATOR;
            if (strpos($normalized_path . DIRECTORY_SEPARATOR, $prefix) === 0) {
                return true;
            }

            return false;
        }

        // 确保路径在备案目录内
        return strpos($real_path, $beian_dir) === 0;
    }

    // ==================== 私有辅助方法 ====================

    /**
     * 从文件系统读取备案页 HTML
     *
     * @return  string|false  HTML 内容或 false（无文件）
     */
    private function get_html_from_file()
    {
        $beian_file = self::BEIAN_DIR . '/index.html';

        if (!is_file($beian_file) || !is_readable($beian_file)) {
            return false;
        }

        // 路径安全检查
        if (!$this->is_safe_path($beian_file)) {
            return false;
        }

        $content = file_get_contents($beian_file);
        return $content !== false ? $content : false;
    }

    /**
     * 从数据库读取备案页 HTML
     *
     * @return  string|false  HTML 内容或 false（无记录）
     */
    private function get_html_from_db()
    {
        // 未注入 db 对象时直接返回 false，走默认模板
        if ($this->db === null) {
            return false;
        }

        // 真实 db 无参数绑定，site_id 用 intval 转义，表名用 tablepre 拼接
        $sql = "SELECT `html_content` FROM `{$this->db->tablepre}spider_icp_page` WHERE `site_id` = " . intval($this->site_id) . " LIMIT 1";
        $row = $this->db->fetch_first($sql);

        if ($row && isset($row['html_content'])) {
            return $row['html_content'];
        }

        return false;
    }

    /**
     * 清空目录中的所有文件
     *
     * @param   string  $dir  目录路径
     */
    private function clear_directory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = scandir($dir);
        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->clear_directory($path);
                rmdir($path);
            } else {
                @unlink($path);
            }
        }
    }

    /**
     * 恢复备份文件
     *
     * @param   string  $dir          目录路径
     * @param   array   $backup_files  备份文件内容（filename => content）
     */
    private function restore_backup($dir, $backup_files)
    {
        foreach ($backup_files as $filename => $content) {
            $path = $dir . '/' . $filename;
            $target_dir = dirname($path);
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            file_put_contents($path, $content);
        }
    }
}
