<?php
/**
 * 收录报告生成器
 */
class report_generator {

    private $site_id;
    private $tablepre;
    private $db;

    public function __construct($site_id = 0, $db = null) {
        $this->site_id = (int)$site_id;
        $this->tablepre = $_ENV['_config']['db']['master']['tablepre'];
        $this->db = $db;
    }

    /**
     * 生成监测报告（按日期聚合）
     * @param string $start_date 开始日期 Y-m-d
     * @param string $end_date 结束日期 Y-m-d
     * @return array 报告数据
     */
    public function generate($start_date, $end_date) {
        $site_id = (int)$this->site_id;
        $start_date = safe_str($start_date);
        $end_date = safe_str($end_date);
        // checked_at 为 INT UNSIGNED 时间戳，用 PHP 计算日期边界，兼容 MySQL/SQLite
        $start_ts = strtotime($start_date . ' 00:00:00');
        $end_ts = strtotime($end_date . ' 23:59:59');
        if ($start_ts === false || $end_ts === false) {
            return array();
        }

        $rows = $this->db->fetch_all("SELECT platform, checked_at, found, confidence
                FROM `{$this->tablepre}ai_search_log`
                WHERE site_id = {$site_id}
                  AND checked_at BETWEEN {$start_ts} AND {$end_ts}
                ORDER BY checked_at ASC");

        // PHP 端按 (日期, 平台) 聚合
        return $this->aggregate($rows);
    }

    /**
     * 按 (日期, 平台) 聚合原始记录
     * @param array $rows [{platform, checked_at, found, confidence}, ...]
     * @return array [{check_date, platform, total_checks, found_count, avg_confidence}, ...]
     */
    private function aggregate($rows) {
        $groups = array();
        foreach ($rows as $r) {
            $date = date('Y-m-d', (int)$r['checked_at']);
            $key = $date . '|' . $r['platform'];
            if (!isset($groups[$key])) {
                $groups[$key] = array(
                    'check_date' => $date,
                    'platform' => $r['platform'],
                    'total_checks' => 0,
                    'found_count' => 0,
                    'conf_sum' => 0.0,
                );
            }
            $groups[$key]['total_checks']++;
            $groups[$key]['found_count'] += (int)$r['found'];
            $groups[$key]['conf_sum'] += (float)$r['confidence'];
        }

        $result = array();
        foreach ($groups as $g) {
            $g['avg_confidence'] = $g['total_checks'] > 0
                ? round($g['conf_sum'] / $g['total_checks'], 2)
                : 0;
            unset($g['conf_sum']);
            $result[] = $g;
        }

        // 按日期倒序、平台正序
        usort($result, function ($a, $b) {
            if ($a['check_date'] !== $b['check_date']) {
                return strcmp($b['check_date'], $a['check_date']);
            }
            return strcmp($a['platform'], $b['platform']);
        });
        return $result;
    }

    /**
     * 导出 CSV 报告
     * @param string $start_date 开始日期
     * @param string $end_date 结束日期
     * @return string CSV 内容
     */
    public function export_csv($start_date, $end_date) {
        $data = $this->generate($start_date, $end_date);

        $csv = "日期,平台,总检查数,展现数,收录率(%),平均置信度\n";

        foreach ($data as $row) {
            $rate = $row['total_checks'] > 0
                ? round(($row['found_count'] / $row['total_checks']) * 100, 2)
                : 0;
            $csv .= sprintf("%s,%s,%d,%d,%.2f,%.1f\n",
                $row['check_date'],
                $row['platform'],
                $row['total_checks'],
                $row['found_count'],
                $rate,
                round($row['avg_confidence'], 1)
            );
        }

        return $csv;
    }

    /**
     * 获取趋势数据（最近N天）
     * @param int $days 天数
     * @return array
     */
    public function get_trend($days = 30) {
        $site_id = (int)$this->site_id;
        $days = (int)$days;
        // checked_at 为 INT UNSIGNED 时间戳，用 PHP 计算截止时间
        $cutoff = $_ENV['_time'] - $days * 86400;
        $rows = $this->db->fetch_all("SELECT platform, checked_at, found
                FROM `{$this->tablepre}ai_search_log`
                WHERE site_id = {$site_id}
                  AND checked_at > {$cutoff}
                ORDER BY checked_at ASC");

        // PHP 端按 (日期, 平台) 聚合，日期正序
        $result = $this->aggregate($rows);
        usort($result, function ($a, $b) {
            if ($a['check_date'] !== $b['check_date']) {
                return strcmp($a['check_date'], $b['check_date']);
            }
            return strcmp($a['platform'], $b['platform']);
        });
        return $result;
    }
}
