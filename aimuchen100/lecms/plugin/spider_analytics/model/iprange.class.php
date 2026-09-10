<?php
class spider_iprange {
    public function add($engine, $cidr, $note, $enabled) {
        $pdo = spider_runtime::$pdo;
        $pre = spider_runtime::$pre;
        $stmt = $pdo->prepare("INSERT INTO `{$pre}spider_ip_range` (engine,cidr,note,enabled,created_at) VALUES (?,?,?,?,?)");
        $stmt->execute(array($engine, $cidr, $note, (int)$enabled, time()));
        return (int)$pdo->lastInsertId();
    }

    public function delete($id) {
        $pdo = spider_runtime::$pdo;
        $pre = spider_runtime::$pre;
        $stmt = $pdo->prepare("DELETE FROM `{$pre}spider_ip_range` WHERE id=?");
        return $stmt->execute(array((int)$id));
    }

    public function list($engine = null) {
        $pdo = spider_runtime::$pdo;
        $pre = spider_runtime::$pre;
        if ($engine) {
            $stmt = $pdo->prepare("SELECT * FROM `{$pre}spider_ip_range` WHERE engine=? ORDER BY id ASC");
            $stmt->execute(array($engine));
        } else {
            $stmt = $pdo->query("SELECT * FROM `{$pre}spider_ip_range` ORDER BY id ASC");
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function all_enabled() {
        $pdo = spider_runtime::$pdo;
        $pre = spider_runtime::$pre;
        $stmt = $pdo->query("SELECT engine,cidr FROM `{$pre}spider_ip_range` WHERE enabled=1");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
