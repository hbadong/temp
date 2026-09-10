<?php
defined('ROOT_PATH') || exit;
$pre = $_ENV['_config']['db']['master']['tablepre'];

$tables = array(
    "export_log" => "CREATE TABLE IF NOT EXISTS `{$pre}export_log` (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        site_id INT UNSIGNED NOT NULL,
        mode ENUM('full','cleanup') NOT NULL DEFAULT 'full',
        operator_uid INT UNSIGNED NOT NULL,
        started_at INT UNSIGNED NOT NULL,
        finished_at INT UNSIGNED NULL,
        status ENUM('running','done','failed','expired','deleted') NOT NULL DEFAULT 'running',
        progress TINYINT UNSIGNED NOT NULL DEFAULT 0,
        current_file VARCHAR(255) NULL,
        file_path VARCHAR(512) NULL,
        file_size BIGINT UNSIGNED NULL,
        cleanup_report TEXT NULL,
        error_msg TEXT NULL,
        created_at INT UNSIGNED NOT NULL,
        PRIMARY KEY (id),
        KEY idx_site_status (site_id, status),
        KEY idx_started (started_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "data_export_runtime" => "CREATE TABLE IF NOT EXISTS `{$pre}data_export_runtime` (
        `key` VARCHAR(64) NOT NULL,
        value TEXT NOT NULL,
        updated_at INT UNSIGNED NOT NULL,
        PRIMARY KEY (`key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
);
foreach ($tables as $name => $sql) {
    if (!$this->db->query($sql)) return;
}

$now = $_ENV['_time'];
$defaults = array(
    'auto_cleanup_days' => '7',
    'db_batch_size' => '1000',
    'disk_space_multiplier' => '1.5',
    'cleanup_default_enabled' => '1',
);
foreach ($defaults as $k => $v) {
    $this->db->query("INSERT IGNORE INTO `{$pre}data_export_runtime` (`key`,value,updated_at) VALUES ('{addslashes($k)}','{addslashes($v)}',{$now})");
}
