<?php
defined('ROOT_PATH') || exit;

require_once dirname(__FILE__) . '/../lib/zip_streamer.class.php';
require_once dirname(__FILE__) . '/../lib/disk_space.class.php';
require_once dirname(__FILE__) . '/export_log.class.php';
require_once dirname(__FILE__) . '/dbdump.class.php';
require_once dirname(__FILE__) . '/cleanup.class.php';

class spider_exporter {
    public static $pre = 'le_';
    public static $pdo;
    public static $export_dir = 'runtime/data_export';
    public static function init($pdo, $pre = 'le_') { self::$pdo = $pdo; self::$pre = $pre; }
    public static function run($export_id, $site_id, $mode, $root_path, $theme = 'default') {
        $log = new spider_export_log();
        $zip_filename = self::$export_dir . "/site-{$site_id}-" . date('Ymd-His') . "-{$export_id}.zip";
        $zip_path = $root_path . '/' . $zip_filename;
        $dir = dirname($zip_path);
        if (!is_dir($dir)) @mkdir($dir, 0777, true);

        // 磁盘检查
        $upload_size = self::dir_size($root_path . '/upload');
        $multiplier = 1.5;
        $required = (int)max(($upload_size ?: 100 * 1024 * 1024) * $multiplier, 50 * 1024 * 1024);
        if (!spider_disk_space::ensure($dir, $required)) {
            $log->mark_failed($export_id, '磁盘空间不足');
            return false;
        }

        try {
            $log->update_progress($export_id, 5, 'opening zip');
            $zip = new spider_zip_streamer($zip_path);

            // 1) view/<theme>/
            $theme_path = $root_path . '/view/' . $theme;
            if (is_dir($theme_path)) {
                $log->update_progress($export_id, 10, 'theme');
                $zip->add_dir($theme_path, 'view/' . $theme);
            }
            // 2) upload/
            $upload_path = $root_path . '/upload';
            if (is_dir($upload_path)) {
                $log->update_progress($export_id, 30, 'upload');
                $zip->add_dir($upload_path, 'upload');
            }
            // 3) DB SQL
            $log->update_progress($export_id, 40, 'database');
            spider_dbdump::init(self::$pdo, self::$pre);
            $sql_content = (new spider_dbdump())->export_to_string($site_id);
            $zip->add_string('site.sql', $sql_content);

            // 4) 清理模式
            $report = null;
            if ($mode === 'cleanup') {
                $log->update_progress($export_id, 70, 'cleanup');
                spider_cleanup::init(self::$pdo, self::$pre);
                $report = (new spider_cleanup())->run($site_id);
                $sql_content = (new spider_dbdump())->export_to_string($site_id);
            }
            $log->update_progress($export_id, 90, 'finalize');
            $zip->add_string('site.sql', $sql_content);
            if ($report !== null) {
                $zip->add_string('cleanup_report.json', json_encode($report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            }
            $zip->close();
            $log->mark_done($export_id, $zip_filename, filesize($zip_path));
            return true;
        } catch (Exception $e) {
            if (file_exists($zip_path)) @unlink($zip_path);
            $log->mark_failed($export_id, $e->getMessage());
            return false;
        }
    }
    public static function dir_size($dir) {
        if (!is_dir($dir)) return 0;
        $size = 0;
        $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
        foreach ($rii as $f) if ($f->isFile()) $size += $f->getSize();
        return $size;
    }
}
