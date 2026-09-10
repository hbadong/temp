<?php
defined('ROOT_PATH') || exit;

$pre = $_ENV['_config']['db']['master']['tablepre'];

$tables = array(
    "spider_visit_log" => "CREATE TABLE IF NOT EXISTS `{$pre}spider_visit_log` (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        site_id INT UNSIGNED NOT NULL DEFAULT 0,
        engine VARCHAR(16) NOT NULL DEFAULT 'other',
        ip VARBINARY(16) NOT NULL,
        ip_text VARCHAR(45) NOT NULL,
        user_agent VARCHAR(512) NOT NULL,
        url VARCHAR(512) NOT NULL,
        referer VARCHAR(512) NULL,
        method VARCHAR(8) NOT NULL DEFAULT 'GET',
        response_code SMALLINT UNSIGNED NOT NULL DEFAULT 0,
        response_size INT UNSIGNED NOT NULL DEFAULT 0,
        duration_ms INT UNSIGNED NOT NULL DEFAULT 0,
        is_blacklist_hit TINYINT(1) NOT NULL DEFAULT 0,
        is_intercepted TINYINT(1) NOT NULL DEFAULT 0,
        created_at INT UNSIGNED NOT NULL,
        PRIMARY KEY (id),
        KEY idx_site_date (site_id, created_at),
        KEY idx_engine (engine),
        KEY idx_ip (ip),
        KEY idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "spider_visit_log_hist" => "CREATE TABLE IF NOT EXISTS `{$pre}spider_visit_log_hist` (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        site_id INT UNSIGNED NOT NULL DEFAULT 0,
        engine VARCHAR(16) NOT NULL DEFAULT 'other',
        ip VARBINARY(16) NOT NULL,
        ip_text VARCHAR(45) NOT NULL,
        user_agent VARCHAR(512) NOT NULL,
        url VARCHAR(512) NOT NULL,
        referer VARCHAR(512) NULL,
        method VARCHAR(8) NOT NULL DEFAULT 'GET',
        response_code SMALLINT UNSIGNED NOT NULL DEFAULT 0,
        response_size INT UNSIGNED NOT NULL DEFAULT 0,
        duration_ms INT UNSIGNED NOT NULL DEFAULT 0,
        is_blacklist_hit TINYINT(1) NOT NULL DEFAULT 0,
        is_intercepted TINYINT(1) NOT NULL DEFAULT 0,
        created_at INT UNSIGNED NOT NULL,
        PRIMARY KEY (id),
        KEY idx_site_date (site_id, created_at),
        KEY idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "spider_daily" => "CREATE TABLE IF NOT EXISTS `{$pre}spider_daily` (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        site_id INT UNSIGNED NOT NULL,
        engine VARCHAR(16) NOT NULL,
        visit_date DATE NOT NULL,
        visit_count INT UNSIGNED NOT NULL DEFAULT 0,
        page_count INT UNSIGNED NOT NULL DEFAULT 0,
        avg_duration_ms INT UNSIGNED NOT NULL DEFAULT 0,
        new_page_count INT UNSIGNED NOT NULL DEFAULT 0,
        blacklist_hit_count INT UNSIGNED NOT NULL DEFAULT 0,
        intercept_count INT UNSIGNED NOT NULL DEFAULT 0,
        updated_at INT UNSIGNED NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uk_site_engine_date (site_id, engine, visit_date),
        KEY idx_date (visit_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "spider_blacklist" => "CREATE TABLE IF NOT EXISTS `{$pre}spider_blacklist` (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        site_id INT UNSIGNED NOT NULL DEFAULT 0,
        match_type ENUM('ip','ua','ip_cidr') NOT NULL,
        match_value VARCHAR(255) NOT NULL,
        note VARCHAR(255) NULL,
        enabled TINYINT(1) NOT NULL DEFAULT 1,
        created_at INT UNSIGNED NOT NULL,
        created_by INT UNSIGNED NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uk_site_type_value (site_id, match_type, match_value(64)),
        KEY idx_enabled (enabled)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "spider_blacklist_hit" => "CREATE TABLE IF NOT EXISTS `{$pre}spider_blacklist_hit` (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        site_id INT UNSIGNED NOT NULL DEFAULT 0,
        match_type ENUM('ip','ua','ip_cidr') NOT NULL,
        match_value VARCHAR(255) NOT NULL,
        blacklist_id INT UNSIGNED NULL,
        visit_log_id BIGINT UNSIGNED NULL,
        ip VARBINARY(16) NULL,
        ip_text VARCHAR(45) NULL,
        user_agent VARCHAR(512) NULL,
        url VARCHAR(512) NULL,
        is_intercepted TINYINT(1) NOT NULL DEFAULT 0,
        hit_at INT UNSIGNED NOT NULL,
        PRIMARY KEY (id),
        KEY idx_site_hit (site_id, hit_at),
        KEY idx_blacklist (blacklist_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "spider_ip_range" => "CREATE TABLE IF NOT EXISTS `{$pre}spider_ip_range` (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        engine VARCHAR(16) NOT NULL,
        cidr VARCHAR(64) NOT NULL,
        note VARCHAR(255) NULL,
        enabled TINYINT(1) NOT NULL DEFAULT 1,
        created_at INT UNSIGNED NOT NULL,
        PRIMARY KEY (id),
        KEY idx_engine_enabled (engine, enabled),
        KEY idx_cidr (cidr(32))
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "spider_runtime" => "CREATE TABLE IF NOT EXISTS `{$pre}spider_runtime` (
        `key` VARCHAR(64) NOT NULL,
        value TEXT NOT NULL,
        updated_at INT UNSIGNED NOT NULL,
        PRIMARY KEY (`key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
);

foreach ($tables as $name => $sql) {
    if (!$this->db->query($sql)) return;
}

// 预装 IP 段
$ip_ranges = array(
    array('baidu',  '110.242.68.0/24',   '百度蜘蛛官方段'),
    array('baidu',  '220.181.108.0/24',  '百度蜘蛛回段'),
    array('google', '66.249.64.0/20',    'Googlebot 北美段'),
    array('google', '192.178.5.0/27',    'Googlebot 全球段'),
    array('bing',   '40.77.167.0/24',    'Bingbot 段'),
    array('bing',   '157.55.39.0/24',    'Bingbot 备用段'),
    array('sogou',  '220.181.125.0/24',  '搜狗蜘蛛段'),
    array('sogou',  '123.125.71.0/24',   '搜狗回段'),
    array('360',    '36.110.147.0/24',   '360 蜘蛛段'),
    array('360',    '42.236.10.0/24',    '360 备用段'),
);
$now = $_ENV['_time'];
foreach ($ip_ranges as $r) {
    $sql = "INSERT IGNORE INTO `{$pre}spider_ip_range` (engine,cidr,note,enabled,created_at) VALUES ('{addslashes($r[0])}','{addslashes($r[1])}','{addslashes($r[2])}',1,{$now})";
    $this->db->query($sql);
}

// 默认 runtime 配置
$defaults = array(
    'intercept_enabled'      => '0',
    'ip_range_strict'        => '0',
    'archive_enabled'        => '1',
    'archive_keep_days'      => '90',
    'archive_compress_years' => '1',
    'buffer_size'            => '20',
    'log_slow_threshold_ms'  => '200',
    'last_aggregate_date'    => '0',
    'last_archive_at'        => '0',
    'last_compress_at'       => '0',
);
foreach ($defaults as $k => $v) {
    $sql = "INSERT IGNORE INTO `{$pre}spider_runtime` (`key`,value,updated_at) VALUES ('{addslashes($k)}','{addslashes($v)}',{$now})";
    $this->db->query($sql);
}
