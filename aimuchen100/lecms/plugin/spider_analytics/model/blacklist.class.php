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

    public function count_list($site_id) {
        $pdo = spider_runtime::$pdo;
        $pre = spider_runtime::$pre;
        $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM `{$pre}spider_blacklist` WHERE site_id=? OR site_id=0");
        $stmt->execute(array((int)$site_id));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['cnt'] : 0;
    }

    public function import_csv($content) {
        $rows = spider_csv::parse($content);
        $pdo = spider_runtime::$pdo;
        $pre = spider_runtime::$pre;
        $n = 0;
        foreach ($rows as $r) {
            $stmt = $pdo->prepare("INSERT INTO `{$pre}spider_blacklist` (site_id,match_type,match_value,note,enabled,created_at) VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE note=VALUES(note),enabled=VALUES(enabled)");
            $stmt->execute(array((int)$r['site_id'], $r['match_type'], $r['match_value'], $r['note'], (int)$r['enabled'], time()));
            if ($stmt->rowCount() >= 1) $n++;
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

    // 命中明细概览统计：总数/拦截数/IP与UA命中数/类型分布
    public function hit_overview($site_id) {
        $pdo = spider_runtime::$pdo;
        $pre = spider_runtime::$pre;
        $today = time() - 86400;
        $single_sql = "FROM `{$pre}spider_blacklist_hit` WHERE site_id=?";
        $all_sql = "FROM `{$pre}spider_blacklist_hit` WHERE site_id=?";
        $total = $pdo->prepare("SELECT COUNT(*) AS cnt " . $all_sql);
        $total->execute(array((int)$site_id));
        $total = $total->fetch(PDO::FETCH_ASSOC);
        $intercepted = $pdo->prepare("SELECT COUNT(*) AS cnt " . $single_sql . " AND is_intercepted=1");
        $intercepted->execute(array((int)$site_id));
        $intercepted = $intercepted->fetch(PDO::FETCH_ASSOC);
        $today_st = $pdo->prepare("SELECT COUNT(*) AS cnt " . $single_sql . " AND hit_at >= ?");
        $today_st->execute(array((int)$site_id, $today));
        $today_st = $today_st->fetch(PDO::FETCH_ASSOC);
        $types = $pdo->prepare("SELECT match_type, COUNT(*) AS cnt " . $single_sql . " GROUP BY match_type");
        $types->execute(array((int)$site_id));
        $type_map = array();
        foreach ($types->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $type_map[$row['match_type']] = (int)$row['cnt'];
        }
        // 生成友好文案，如 "IP 12 / UA 3 / CIDR 1"
        $type_labels = array('ip' => 'IP', 'ua' => 'UA', 'ip_cidr' => 'CIDR');
        $type_text = array();
        foreach ($type_map as $k => $v) {
            $type_text[] = (isset($type_labels[$k]) ? $type_labels[$k] : $k) . ' ' . $v;
        }
        return array(
            'total' => $total ? (int)$total['cnt'] : 0,
            'intercepted' => $intercepted ? (int)$intercepted['cnt'] : 0,
            'today' => $today_st ? (int)$today_st['cnt'] : 0,
            'types' => $type_map,
            'type_text' => $type_text ? implode(' / ', $type_text) : '0',
        );
    }
}
