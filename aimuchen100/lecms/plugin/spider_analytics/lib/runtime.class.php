<?php
/**
 * 运行时门面：提供 spider_runtime_get/set 函数
 * lib/ 下的门面，调用 model/runtime.class.php 的方法
 */
require_once dirname(__FILE__) . '/../model/runtime.class.php';

if (!function_exists('spider_runtime_get')) {
    function spider_runtime_get($key, $default = null) {
        if (class_exists('spider_runtime_mock', false)) {
            return spider_runtime_mock::get($key, $default);
        }
        $m = new spider_runtime();
        return $m->get($key, $default);
    }
}

if (!function_exists('spider_runtime_set')) {
    function spider_runtime_set($key, $value) {
        if (class_exists('spider_runtime_mock', false)) {
            spider_runtime_mock::set($key, $value);
            return;
        }
        $m = new spider_runtime();
        $m->set($key, $value);
    }
}

/**
 * 文件锁
 */
class spider_lock {
    public static function acquire($name) {
        $path = sys_get_temp_dir() . '/' . $name . '.lock';
        $fp = fopen($path, 'c');
        if (!$fp) return false;
        if (!flock($fp, LOCK_EX | LOCK_NB)) { fclose($fp); return false; }
        return $fp;
    }

    public static function release($fp) {
        if (is_resource($fp)) { flock($fp, LOCK_UN); fclose($fp); }
    }
}
