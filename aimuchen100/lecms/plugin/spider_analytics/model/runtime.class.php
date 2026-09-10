<?php
/**
 * Runtime KV 状态（pre_spider_runtime 表）
 * 通过 spider_runtime::$pdo 测试 init；生产由 db() 提供
 */
class spider_runtime {
    public static $pdo;
    public static $pre = 'le_';

    public static function init($pdo, $pre = 'le_') {
        self::$pdo = $pdo;
        self::$pre = $pre;
    }

    public static function get($key, $default = null) {
        if (self::$pdo) {
            $stmt = self::$pdo->prepare("SELECT value FROM `" . self::$pre . "spider_runtime` WHERE `key`=?");
            $stmt->execute(array($key));
            $r = $stmt->fetch(PDO::FETCH_ASSOC);
            return ($r === false) ? $default : $r['value'];
        }
        return $default;
    }

    public static function set($key, $value) {
        if (self::$pdo) {
            $now = time();
            $driver = self::$pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'mysql') {
                $stmt = self::$pdo->prepare("INSERT INTO `" . self::$pre . "spider_runtime` (`key`,value,updated_at) VALUES (?,?,?) ON DUPLICATE KEY UPDATE value=VALUES(value), updated_at=VALUES(updated_at)");
            } else {
                $stmt = self::$pdo->prepare("INSERT OR REPLACE INTO `" . self::$pre . "spider_runtime` (`key`,value,updated_at) VALUES (?,?,?)");
            }
            $stmt->execute(array($key, (string)$value, $now));
            return;
        }
    }
}
