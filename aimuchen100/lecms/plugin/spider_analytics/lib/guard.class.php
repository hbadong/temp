<?php
/**
 * 黑名单匹配：UA exact → IP exact → CIDR 包含
 */
class spider_guard {
    public static function match($ua, $ip, $site_id) {
        $rows = self::load_blacklist($site_id);
        $best = null;
        // UA exact
        foreach ($rows as $r) {
            if ($r['match_type'] === 'ua' && strcasecmp($r['match_value'], $ua) === 0) {
                if ($best === null || $r['id'] < $best['id']) $best = $r;
            }
        }
        if ($best) return $best;
        // IP exact
        foreach ($rows as $r) {
            if ($r['match_type'] === 'ip' && $r['match_value'] === $ip) {
                if ($best === null || $r['id'] < $best['id']) $best = $r;
            }
        }
        if ($best) return $best;
        // CIDR
        foreach ($rows as $r) {
            if ($r['match_type'] === 'ip_cidr' && spider_cidr::match($ip, $r['match_value'])) {
                if ($best === null || $r['id'] < $best['id']) $best = $r;
            }
        }
        return $best;
    }

    /**
     * 加载候选黑名单。优先 spider_guard_mock（测试用），否则从 DB 读。
     */
    private static function load_blacklist($site_id) {
        if (class_exists('spider_guard_mock', false)) {
            return array_filter(spider_guard_mock::$rows, function($r) use ($site_id) {
                return (int)$r['enabled'] === 1
                    && ((int)$r['site_id'] === 0 || (int)$r['site_id'] === (int)$site_id);
            });
        }
        // 生产路径
        $pre = $_ENV['_config']['db']['master']['tablepre'];
        $db_type = 'db_' . $_ENV['_config']['db']['type'];
        $db = new $db_type($_ENV['_config']['db']);
        $db = $db->db ?? $db;
        $rows = $db->fetch_all("SELECT id,site_id,match_type,match_value,enabled FROM {$pre}spider_blacklist WHERE enabled=1 AND (site_id=0 OR site_id=" . (int)$site_id . ")");
        return $rows ?: array();
    }
}
