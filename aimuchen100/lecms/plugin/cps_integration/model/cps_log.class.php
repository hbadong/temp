<?php
defined('ROOT_PATH') or exit;
/**
 * CpsLog 模型 — 点击日志
 *
 * 修复（对照验证发现）：桩代码 → 真实 DB 操作
 * - REQ-06-AC4 record_click：完整记录 IP/UA/Referer/时间戳（IP 用 MySQL INET6_ATON 存二进制）
 * - REQ-06-AC5 is_unique_click：同 cps_id + 同 IP 86400 秒窗口内只计 1 次
 * - 全部 SQL 使用 addslashes() 内联转义
 */
class CpsLog extends model {
    public function __construct() {
        $this->table = 'cms_cps_click_log';
        $this->pri = array('id');
        $this->maxid = 'id';
    }

    /**
     * 检查是否唯一点击（同 cps 链接 + 同 IP，24 小时窗口）
     * REQ-06-AC5
     * @param int $cps_id CPS配置ID
     * @param string $ip_text 客户端IP文本
     * @return bool true=24h 内首击（记唯一） false=已有点击
     */
    public function is_unique_click($cps_id, $ip_text) {
        $cps_id = (int)$cps_id;
        if($cps_id <= 0 || trim((string)$ip_text) === '') return true;
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];
        $ip_text = addslashes((string)$ip_text);
        $since = (int)$_ENV['_time'] - 86400;
        $row = $this->db->fetch_first("
            SELECT id FROM `{$tablepre}cms_cps_click_log`
            WHERE cps_id = {$cps_id} AND ip_text = '{$ip_text}' AND click_at >= {$since}
            LIMIT 1
        ");
        return $row ? false : true;
    }

    /**
     * 记录点击日志
     * REQ-06-AC4 完整记录 IP、User-Agent、Referer、时间戳
     * @param int $site_id 站点ID
     * @param int $game_id 游戏ID
     * @param int $cps_id CPS配置ID
     * @param int $is_unique 是否24h首击
     * @param int $is_degraded 是否降级备用链接
     * @param string $ip_text 客户端IP文本
     * @param string $ua User-Agent
     * @param string $referer 来源页
     * @return int 日志ID
     */
    public function record_click($site_id, $game_id, $cps_id, $is_unique, $is_degraded, $ip_text, $ua, $referer) {
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];
        $site_id = (int)$site_id;
        $game_id = (int)$game_id;
        $cps_id = (int)$cps_id;
        $is_unique = (int)$is_unique;
        $is_degraded = (int)$is_degraded;
        $ip_text = addslashes(substr((string)$ip_text, 0, 45));
        $ua = addslashes(substr((string)$ua, 0, 500));
        $referer = addslashes(substr((string)$referer, 0, 500));
        $now = (int)$_ENV['_time'];

        // ip 二进制由 MySQL INET6_ATON 生成（VARBINARY(16)，兼容 IPv4/IPv6）
        $ip_bin_sql = ($ip_text === '') ? 'NULL' : "INET6_ATON('{$ip_text}')";

        $this->db->query("
            INSERT INTO `{$tablepre}cms_cps_click_log`
                (site_id, game_id, cps_id, ip, ip_text, user_agent, referer, is_unique, is_degraded, click_at)
            VALUES
                ({$site_id}, {$game_id}, {$cps_id}, {$ip_bin_sql}, '{$ip_text}', '{$ua}', '{$referer}', {$is_unique}, {$is_degraded}, {$now})
        ");
        return (int)$this->db->last_insert_id();
    }

    /**
     * 按时间维度统计点击
     * REQ-06-AC7 支持按天/周/月
     * @param int $site_id 站点ID（0=全部）
     * @param int $from 起始时间戳
     * @param int $to 结束时间戳
     * @return array ['clicks'=>int,'unique_ips'=>int,'per_day'=>array,'per_game'=>array,'per_cps'=>array]
     */
    public function get_stats($site_id, $from, $to) {
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];
        $where = " WHERE click_at >= " . (int)$from . " AND click_at <= " . (int)$to;
        if($site_id > 0) $where .= " AND site_id = " . (int)$site_id;

        // 总点击 / 唯一IP
        $total = $this->db->fetch_first("
            SELECT COUNT(*) AS clicks, COUNT(DISTINCT ip_text) AS unique_ips
            FROM `{$tablepre}cms_cps_click_log`{$where}
        ");

        // 按天分组
        $per_day = $this->db->fetch_all("
            SELECT FROM_UNIXTIME(click_at, '%Y-%m-%d') AS day, COUNT(*) AS clicks,
                   COUNT(DISTINCT ip_text) AS unique_ips
            FROM `{$tablepre}cms_cps_click_log`{$where}
            GROUP BY day ORDER BY day ASC
        ");

        // 按游戏分组
        $per_game = $this->db->fetch_all("
            SELECT game_id, COUNT(*) AS clicks, COUNT(DISTINCT ip_text) AS unique_ips
            FROM `{$tablepre}cms_cps_click_log`{$where}
            GROUP BY game_id ORDER BY clicks DESC LIMIT 20
        ");

        // 按 CPS 链接分组
        $per_cps = $this->db->fetch_all("
            SELECT cps_id, COUNT(*) AS clicks, COUNT(DISTINCT ip_text) AS unique_ips,
                   SUM(is_degraded) AS degraded_count
            FROM `{$tablepre}cms_cps_click_log`{$where}
            GROUP BY cps_id ORDER BY clicks DESC LIMIT 20
        ");

        return array(
            'clicks' => (int)$total['clicks'],
            'unique_ips' => (int)$total['unique_ips'],
            'per_day' => $per_day,
            'per_game' => $per_game,
            'per_cps' => $per_cps,
        );
    }
}
