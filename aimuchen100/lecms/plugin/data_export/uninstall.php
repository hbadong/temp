<?php
defined('ROOT_PATH') || exit;
$pre = $_ENV['_config']['db']['master']['tablepre'];
foreach (array('export_log', 'data_export_runtime') as $t) {
    $this->db->query("DROP TABLE IF EXISTS `{$pre}{$t}`");
}
