<?php
/**
 * 回滚管理器
 * 负责从备份恢复文件到站点根目录
 */
class rollback_manager {

    private $site_id;
    private $backup_root;

    public function __construct($site_id = 0) {
        $this->site_id = (int)$site_id;
        $this->backup_root = RUNTIME_PATH . '/patch_system/backups/';
    }

    /**
     * 从备份恢复
     * @param string $backup_path 备份目录路径（相对 backup_root）
     * @return bool
     */
    public function restore($backup_path) {
        $backup_dir = $this->backup_root . ltrim($backup_path, '/');
        if (!is_dir($backup_dir)) {
            return false;
        }

        $files = $this->get_all_files($backup_dir);
        foreach ($files as $file) {
            $relative = substr($file, strlen($backup_dir) + 1);
            $target = ROOT_PATH . '/' . $relative;

            $target_dir = dirname($target);
            if (!is_dir($target_dir)) {
                @mkdir($target_dir, 0755, true);
            }

            if (!@copy($file, $target)) {
                // 单个文件复制失败不影响其他文件
                continue;
            }
        }

        return true;
    }

    /**
     * 获取目录下所有文件（递归）
     */
    private function get_all_files($dir) {
        $files = array();
        if (!is_dir($dir)) {
            return $files;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            $files[] = $file->getPathname();
        }

        return $files;
    }
}
