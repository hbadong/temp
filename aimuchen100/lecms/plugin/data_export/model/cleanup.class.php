<?php
defined('ROOT_PATH') || exit;

class spider_cleanup {
    public static $pdo;
    public static $pre = 'le_';
    public static function init($pdo, $pre = 'le_') { self::$pdo = $pdo; self::$pre = $pre; }
    public static function random_password($len = 32) {
        $half = (int)($len / 2);
        if (function_exists('random_bytes')) return bin2hex(random_bytes($half));
        if (function_exists('openssl_random_pseudo_bytes')) return bin2hex(openssl_random_pseudo_bytes($half));
        return bin2hex(str_repeat('a', $half));
    }
    public function run($site_id) {
        $report = array('rules_version' => '1.0', 'tables' => array());
        // 1) user.password 随机化（逐行独立随机，避免所有用户同密码）
        $stmt = self::$pdo->prepare("SELECT uid FROM `" . self::$pre . "user` WHERE site_id=? OR site_id=0");
        $stmt->execute(array((int)$site_id));
        $upd = self::$pdo->prepare("UPDATE `" . self::$pre . "user` SET password=? WHERE uid=?");
        $count = 0;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $upd->execute(array(self::random_password(), $row['uid']));
            $count++;
        }
        $report['tables']['user.password'] = array('count' => $count);
        // 2) ai_config.api_key 置空
        if (self::table_exists(self::$pre . 'ai_config')) {
            $n = self::$pdo->prepare("UPDATE `" . self::$pre . "ai_config` SET api_key='' WHERE site_id=? OR site_id=0");
            $n->execute(array((int)$site_id));
            $report['tables']['ai_config.api_key'] = array('count' => $n->rowCount());
        }
        // 3) user.email 匿名化
        $stmt = self::$pdo->prepare("SELECT uid FROM `" . self::$pre . "user` WHERE site_id=? OR site_id=0");
        $stmt->execute(array((int)$site_id));
        $upd = self::$pdo->prepare("UPDATE `" . self::$pre . "user` SET email=? WHERE uid=?");
        $count = 0;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $upd->execute(array('anon-' . $row['uid'] . '@example.com', $row['uid']));
            $count++;
        }
        $report['tables']['user.email'] = array('count' => $count);
        return $report;
    }
    private static function table_exists($name) {
        try {
            $driver = self::$pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'sqlite') {
                $r = self::$pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='{$name}'")->fetch();
            } else {
                $r = self::$pdo->query("SHOW TABLES LIKE '{$name}'")->fetch();
            }
            return $r !== false;
        } catch (Exception $e) { return false; }
    }
}
