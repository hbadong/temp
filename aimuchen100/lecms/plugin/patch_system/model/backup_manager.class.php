<?php
/**
 * 备份管理器
 * 负责升级前备份被修改文件，自动清理旧备份
 */
class backup_manager {

    private $site_id;
    private $backup_root;

    public function __construct($site_id = 0) {
        $this->site_id = (int)$site_id;
        $this->backup_root = RUNTIME_PATH . '/patch_system/backups/';
    }

    /**
     * 备份指定文件列表
     * @param array $files 文件路径列表（相对站点根目录）
     * @return string 备份目录路径（相对 backup_root）
     */
    public function backup($files) {
        $version = date('YmdHis') . '-' . substr(md5(microtime(true)), 0, 6);
        $backup_dir = $this->backup_root . $version . '/';

        if (!@mkdir($backup_dir, 0755, true) && !is_dir($backup_dir)) {
            throw new Exception('无法创建备份目录: ' . $backup_dir);
        }

        $backed_up = 0;
        foreach ($files as $file) {
            $source = ROOT_PATH . '/' . ltrim($file, '/');
            if (file_exists($source)) {
                $target = $backup_dir . ltrim($file, '/');
                $target_dir = dirname($target);
                if (!is_dir($target_dir)) {
                    @mkdir($target_dir, 0755, true);
                }
                if (@copy($source, $target)) {
                    $backed_up++;
                }
            }
        }

        // 清理旧备份（保留最近 3 次）
        $this->cleanup_old_backups(3);

        return $version;
    }

    /**
     * 清理旧备份
     * @param int $keep 保留最近几次
     */
    private function cleanup_old_backups($keep = 3) {
        if (!is_dir($this->backup_root)) {
            return;
        }

        $dirs = glob($this->backup_root . '*', GLOB_ONLYDIR);
        if (!$dirs) {
            return;
        }

        usort($dirs, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        $to_delete = array_slice($dirs, $keep);
        foreach ($to_delete as $dir) {
            $this->delete_dir($dir);
        }
    }

    /**
     * 递归删除目录
     */
    private function delete_dir($dir) {
        if (!is_dir($dir)) {
            return;
        }

        $items = glob($dir . '/*');
        if ($items) {
            foreach ($items as $item) {
                is_dir($item) ? $this->delete_dir($item) : @unlink($item);
            }
        }
        @rmdir($dir);
    }
}
