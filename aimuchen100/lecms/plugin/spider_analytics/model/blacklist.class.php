<?php
/**
 * 黑名单 CRUD + CSV IO + 命中记录
 */
class spider_blacklist {
    private $cols = array('site_id','match_type','match_value','note','enabled','created_at');

    public function add($site_id, $match_type, $match_value, $note, $enabled) {
        $pdo = spider_runtime::$pdo;
        $pre = spider_runtime::$pre;
        $stmt = $pdo->prepare("INSERT INTO `{$pre}spider_blacklist` (site_id,match_type,match_value,note,enabled,created_at) VALUES (?,?,?,?,?,?)");
        $stmt->execute(array((int)$site_id, $match_type, $match_value, $note, (int)$enabled, time()));
        return (int)$pdo->lastInsertId();
    }

    public function delete($id) {
        $pdo = spider_runtime::$pdo;
        $pre = spider_runtime::$pre;
        $stmt = $pdo->prepare("DELETE FROM `{$pre}spider_blacklist` WHERE id=?");
        return $stmt->execute(array((int)$id));
    }

    public function list($site_id, $page = 1, $size = 50) {
        $pdo = spider_runtime::$pdo;
        $pre = spider_runtime::$pre;
        $offset = ($page - 1) * $size;
        $stmt = $pdo->prepare("SELECT * FROM `{$pre}spider_blacklist` WHERE site_id=? OR site_id=0 ORDER BY id DESC LIMIT {$size} OFFSET {$offset}");
        $stmt->execute(array((int)$site_id));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function import_csv($content) {
        $rows = spider_csv::parse($content);
        $pdo = spider_runtime::$pdo;
        $pre = spider_runtime::$pre;
        $n = 0;
        foreach ($rows as $r) {
            $stmt = $pdo->prepare("INSERT OR IGNORE INTO `{$pre}spider_blacklist` (site_id,match_type,match_value,note,enabled,created_at) VALUES (?,?,?,?,?,?)");
            $stmt->execute(array((int)$r['site_id'], $r['match_type'], $r['match_value'], $r['note'], (int)$r['enabled'], time()));
            if ($stmt->rowCount() > 0) $n++;
        }
        return $n;
    }

    public function export_csv() {
        $pdo = spider_runtime::$pdo;
        $pre = spider_runtime::$pre;
        $stmt = $pdo->query("SELECT site_id,match_type,match_value,note,enabled FROM `{$pre}spider_blacklist` ORDER BY id ASC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return spider_csv::emit($rows);
    }

    public function record_hit($site_id, $match_type, $match_value, $blacklist_id, $visit_log_id, $ip, $ip_text, $ua, $url, $is_intercepted, $time) {
        $pdo = spider_runtime::$pdo;
        $pre = spider_runtime::$pre;
        $stmt = $pdo->prepare("INSERT INTO `{$pre}spider_blacklist_hit` (site_id,match_type,match_value,blacklist_id,visit_log_id,ip,ip_text,user_agent,url,is_intercepted,hit_at) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute(array((int)$site_id, $match_type, $match_value, $blacklist_id, $visit_log_id, $ip, $ip_text, $ua, $url, (int)$is_intercepted, (int)$time));
    }

    public function list_hits($site_id, $limit = 50, $offset = 0) {
        $pdo = spider_runtime::$pdo;
        $pre = spider_runtime::$pre;
        $stmt = $pdo->prepare("SELECT * FROM `{$pre}spider_blacklist_hit` WHERE site_id=? ORDER BY id DESC LIMIT {$limit} OFFSET {$offset}");
        $stmt->execute(array((int)$site_id));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function count_hits($site_id) {
        $pdo = spider_runtime::$pdo;
        $pre = spider_runtime::$pre;
        $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM `{$pre}spider_blacklist_hit` WHERE site_id=?");
        $stmt->execute(array((int)$site_id));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['cnt'] : 0;
    }
}
