<?php
defined('ROOT_PATH') || exit;

class spider_export_log {
    public static $pdo;
    public static $pre = 'le_';
    public static function init($pdo, $pre = 'le_') { self::$pdo = $pdo; self::$pre = $pre; }
    private static function tbl() { return '`' . self::$pre . 'export_log`'; }
    public function create($site_id, $mode, $operator_uid) {
        $stmt = self::$pdo->prepare("INSERT INTO " . self::tbl() . " (site_id,mode,operator_uid,started_at,status,progress,created_at) VALUES (?,?,?,?,?,?,?)");
        $now = time();
        $stmt->execute(array((int)$site_id, $mode, (int)$operator_uid, $now, 'running', 0, $now));
        return (int)self::$pdo->lastInsertId();
    }
    public function update_progress($id, $progress, $current_file = null) {
        $stmt = self::$pdo->prepare("UPDATE " . self::tbl() . " SET progress=?, current_file=? WHERE id=?");
        $stmt->execute(array((int)$progress, $current_file, (int)$id));
    }
    public function mark_done($id, $file_path, $file_size) {
        $stmt = self::$pdo->prepare("UPDATE " . self::tbl() . " SET status='done', progress=100, finished_at=?, file_path=?, file_size=? WHERE id=?");
        $stmt->execute(array(time(), $file_path, (int)$file_size, (int)$id));
    }
    public function mark_failed($id, $error_msg) {
        $stmt = self::$pdo->prepare("UPDATE " . self::tbl() . " SET status='failed', finished_at=?, error_msg=? WHERE id=?");
        $stmt->execute(array(time(), (string)$error_msg, (int)$id));
    }
    public function get($id) {
        $stmt = self::$pdo->prepare("SELECT * FROM " . self::tbl() . " WHERE id=?");
        $stmt->execute(array((int)$id));
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function list_recent($limit = 50) {
        $stmt = self::$pdo->prepare("SELECT * FROM " . self::tbl() . " ORDER BY id DESC LIMIT " . (int)$limit);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function list_running() {
        $stmt = self::$pdo->query("SELECT * FROM " . self::tbl() . " WHERE status='running'");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function list_expired($days) {
        $threshold = time() - ((int)$days * 86400);
        $stmt = self::$pdo->prepare("SELECT * FROM " . self::tbl() . " WHERE status='done' AND finished_at < ?");
        $stmt->execute(array($threshold));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function mark_expired($id) {
        $stmt = self::$pdo->prepare("UPDATE " . self::tbl() . " SET status='expired' WHERE id=?");
        $stmt->execute(array((int)$id));
    }
    public function mark_deleted($id) {
        $stmt = self::$pdo->prepare("UPDATE " . self::tbl() . " SET status='deleted' WHERE id=?");
        $stmt->execute(array((int)$id));
    }
}
