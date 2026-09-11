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

    public function list($engine = null, $keyword = '', $page = 1, $size = 50) {
        $pdo = spider_runtime::$pdo;
        $pre = spider_runtime::$pre;
        $where = ' WHERE 1';
        $args = array();
        if ($engine) {
            $where .= " AND engine=?";
            $args[] = $engine;
        }
        if ($keyword !== '') {
            $where .= " AND (engine LIKE ? OR cidr LIKE ? OR note LIKE ?)";
            $kw = '%' . $keyword . '%';
            $args[] = $kw; $args[] = $kw; $args[] = $kw;
        }
        $offset = ($page - 1) * $size;
        $stmt = $pdo->prepare("SELECT * FROM `{$pre}spider_ip_range`{$where} ORDER BY id ASC LIMIT {$size} OFFSET {$offset}");
        $stmt->execute($args);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function count_list($engine = null, $keyword = '') {
        $pdo = spider_runtime::$pdo;
        $pre = spider_runtime::$pre;
        $where = ' WHERE 1';
        $args = array();
        if ($engine) {
            $where .= " AND engine=?";
            $args[] = $engine;
        }
        if ($keyword !== '') {
            $where .= " AND (engine LIKE ? OR cidr LIKE ? OR note LIKE ?)";
            $kw = '%' . $keyword . '%';
            $args[] = $kw; $args[] = $kw; $args[] = $kw;
        }
        $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM `{$pre}spider_ip_range`{$where}");
        $stmt->execute($args);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['cnt'] : 0;
    }

    public function all_enabled() {
        $pdo = spider_runtime::$pdo;
        $pre = spider_runtime::$pre;
        $stmt = $pdo->query("SELECT engine,cidr FROM `{$pre}spider_ip_range` WHERE enabled=1");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
