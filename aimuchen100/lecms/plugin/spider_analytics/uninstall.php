<?php
defined('ROOT_PATH') || exit;
$pre = $_ENV['_config']['db']['master']['tablepre'];
foreach (array(
    'spider_visit_log',
    'spider_visit_log_hist',
    'spider_daily',
    'spider_blacklist',
    'spider_blacklist_hit',
    'spider_ip_range',
    'spider_runtime',
) as $t) {
    $this->db->query("DROP TABLE IF EXISTS `{$pre}{$t}`");
}
