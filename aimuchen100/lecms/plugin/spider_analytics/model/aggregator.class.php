<?php
/**
 * 汇总 / 迁移 / 压缩
 */
class spider_aggregator {
    public function is_mysql() {
        return spider_runtime::$pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql';
    }

    public function aggregate_yesterday() {
        $pdo = spider_runtime::$pdo;
        $pre = spider_runtime::$pre;
        $yday_date = date('Y-m-d', strtotime('-1 day'));
        $start = strtotime($yday_date . ' 00:00:00');
        $end   = $start + 86400;
        $sql = "SELECT site_id,engine,COUNT(*) AS cnt,COUNT(DISTINCT url) AS pages FROM `{$pre}spider_visit_log` WHERE created_at >= ? AND created_at < ? GROUP BY site_id,engine";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array($start, $end));
        $now = time();
        $rows = 0;
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($this->is_mysql()) {
                $upd = $pdo->prepare("INSERT INTO `{$pre}spider_daily` (site_id,engine,visit_date,visit_count,page_count,avg_duration_ms,new_page_count,blacklist_hit_count,intercept_count,updated_at) VALUES (?,?,?,?,?,0,0,0,0,?) ON DUPLICATE KEY UPDATE visit_count=VALUES(visit_count),page_count=VALUES(page_count),updated_at=VALUES(updated_at)");
                $upd->execute(array($r['site_id'], $r['engine'], $yday_date, $r['cnt'], $r['pages'], $now));
            } else {
                $upd2 = $pdo->prepare("INSERT OR REPLACE INTO `{$pre}spider_daily` (id,site_id,engine,visit_date,visit_count,page_count,avg_duration_ms,new_page_count,blacklist_hit_count,intercept_count,updated_at) VALUES ((SELECT id FROM `{$pre}spider_daily` WHERE site_id=? AND engine=? AND visit_date=?),?,?,?,?,?,0,0,0,0,?)");
                $upd2->execute(array($r['site_id'], $r['engine'], $yday_date, $r['site_id'], $r['engine'], $yday_date, $r['cnt'], $r['pages'], $now));
            }
            $rows++;
        }
        spider_runtime_set('last_aggregate_date', $yday_date);
        // days = 实际汇总的天数（本方法固定汇总昨天，恒为 1）；rows = 写入的引擎分组数
        return array('days' => 1, 'rows' => $rows);
    }

    public function archive_old($keep_days) {
        $pdo = spider_runtime::$pdo;
        $pre = spider_runtime::$pre;
        $threshold = time() - ((int)$keep_days * 86400);
        $sql = "INSERT INTO `{$pre}spider_visit_log_hist` SELECT * FROM `{$pre}spider_visit_log` WHERE created_at < ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array($threshold));
        $n = $stmt->rowCount();
        $del = $pdo->prepare("DELETE FROM `{$pre}spider_visit_log` WHERE created_at < ?");
        $del->execute(array($threshold));
        spider_runtime_set('last_archive_at', (string)time());
        return $n;
    }

    public function compress_old_hist($years) {
        $pdo = spider_runtime::$pdo;
        $pre = spider_runtime::$pre;
        $threshold = time() - ((int)$years * 365 * 86400);
        if ($this->is_mysql()) {
            $sql = "SELECT site_id,engine,DATE(FROM_UNIXTIME(created_at)) AS d,COUNT(*) AS cnt FROM `{$pre}spider_visit_log_hist` WHERE created_at < ? GROUP BY site_id,engine,d";
        } else {
            $sql = "SELECT site_id,engine,DATE(created_at,'unixepoch') AS d,COUNT(*) AS cnt FROM `{$pre}spider_visit_log_hist` WHERE created_at < ? GROUP BY site_id,engine,d";
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array($threshold));
        $now = time();
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($this->is_mysql()) {
                $upd = $pdo->prepare("INSERT INTO `{$pre}spider_daily` (site_id,engine,visit_date,visit_count,page_count,avg_duration_ms,new_page_count,blacklist_hit_count,intercept_count,updated_at) VALUES (?,?,?,?,?,0,0,0,0,?) ON DUPLICATE KEY UPDATE visit_count=VALUES(visit_count),updated_at=VALUES(updated_at)");
                $upd->execute(array($r['site_id'], $r['engine'], $r['d'], $r['cnt'], 0, $now));
            } else {
                $upd2 = $pdo->prepare("INSERT OR REPLACE INTO `{$pre}spider_daily` (id,site_id,engine,visit_date,visit_count,page_count,avg_duration_ms,new_page_count,blacklist_hit_count,intercept_count,updated_at) VALUES ((SELECT id FROM `{$pre}spider_daily` WHERE site_id=? AND engine=? AND visit_date=?),?,?,?,?,?,0,0,0,0,?)");
                $upd2->execute(array($r['site_id'], $r['engine'], $r['d'], $r['site_id'], $r['engine'], $r['d'], $r['cnt'], 0, $now));
            }
        }
        $del = $pdo->prepare("DELETE FROM `{$pre}spider_visit_log_hist` WHERE created_at < ?");
        $del->execute(array($threshold));
        $n = $del->rowCount();
        spider_runtime_set('last_compress_at', (string)time());
        return $n;
    }

    public function try_run() {
        // pdo 未初始化（DB 连接失败或被跳过）时直接返回，避免对 null 调 prepare()
        if (!spider_runtime::$pdo) return array('ok' => false, 'reason' => 'no-pdo');
        $lock = spider_lock::acquire('spider_analytics');
        if (!$lock) return array('locked' => true);
        try {
            $this->aggregate_yesterday();
            $keep = (int)spider_runtime_get('archive_keep_days', 90);
            if (spider_runtime_get('archive_enabled', '1') === '1') {
                $this->archive_old($keep);
            }
            $years = (int)spider_runtime_get('archive_compress_years', 1);
            $this->compress_old_hist($years);
        } finally {
            spider_lock::release($lock);
        }
        return array('ok' => true);
    }
}
