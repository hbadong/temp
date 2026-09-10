<?php
defined('ROOT_PATH') || exit;

/**
 * 流式 ZIP 封装
 */
class spider_zip_streamer {
    private $zip;
    public $filename;
    public function __construct($path) {
        if (!class_exists('ZipArchive')) throw new Exception('ZipArchive extension missing');
        $this->filename = $path;
        $this->zip = new ZipArchive();
        if ($this->zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception("cannot open $path");
        }
    }
    public function add_file($src, $entry = null) {
        if (!is_file($src)) return false;
        $entry = $entry ?: basename($src);
        return $this->zip->addFile($src, $entry);
    }
    public function add_dir($dir, $prefix = '') {
        if (!is_dir($dir)) return false;
        $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
        foreach ($rii as $f) {
            if ($f->isFile()) {
                $rel = substr($f->getPathname(), strlen($dir) + 1);
                $entry = $prefix !== '' ? rtrim($prefix, '/\\') . '/' . $rel : $rel;
                $entry = str_replace('\\', '/', $entry);
                $this->zip->addFile($f->getPathname(), $entry);
            }
        }
        return true;
    }
    public function add_string($name, $content) {
        return $this->zip->addFromString($name, $content);
    }
    public function close() { return $this->zip->close(); }
}
