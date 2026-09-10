<?php
defined('ROOT_PATH') || exit;

/**
 * 磁盘空间检查
 */
class spider_disk_space {
    public static function free_bytes($path) {
        $path = rtrim((string)$path, '/\\');
        if ($path === '') $path = '.';
        return @disk_free_space($path);
    }
    public static function ensure($path, $required_bytes) {
        $free = self::free_bytes($path);
        return $free !== false && $free >= (int)$required_bytes;
    }
}
