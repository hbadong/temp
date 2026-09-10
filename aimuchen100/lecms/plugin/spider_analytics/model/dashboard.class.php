<?php
class spider_dashboard {
    public function get_trend($site_id, $days = 30) {
        $pdo = spider_runtime::$pdo;
        $pre = spider_runtime::$pre;
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'mysql') {
            $sql = "SELECT visit_date,engine,visit_count FROM `{$pre}spider_daily` WHERE site_id=? AND visit_date >= DATE_SUB(CURDATE(), INTERVAL {$days} DAY) ORDER BY visit_date ASC";
        } else {
            $sql = "SELECT visit_date,engine,visit_count FROM `{$pre}spider_daily` WHERE site_id=? AND visit_date >= date('now','-{$days} day') ORDER BY visit_date ASC";
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array((int)$site_id));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function get_engine_pie($site_id, $days = 7) {
        $pdo = spider_runtime::$pdo;
        $pre = spider_runtime::$pre;
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'mysql') {
            $sql = "SELECT engine,SUM(visit_count) AS total FROM `{$pre}spider_daily` WHERE site_id=? AND visit_date >= DATE_SUB(CURDATE(), INTERVAL {$days} DAY) GROUP BY engine";
        } else {
            $sql = "SELECT engine,SUM(visit_count) AS total FROM `{$pre}spider_daily` WHERE site_id=? AND visit_date >= date('now','-{$days} day') GROUP BY engine";
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array((int)$site_id));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
