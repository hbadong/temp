<?php
defined('ROOT_PATH') || exit;
$tableprefix = $_ENV['_config']['db']['master']['tablepre'];	//表前缀

$sql = "CREATE TABLE IF NOT EXISTS ".$tableprefix."links (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  name varchar(80) NOT NULL DEFAULT '' COMMENT '名称',
  url varchar(255) NOT NULL DEFAULT '' COMMENT '链接地址',
  orderby smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '排序值',
  dateline int(10) unsigned NOT NULL DEFAULT '0' COMMENT '发表时间',
  PRIMARY KEY (id)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;";
$this->db->query($sql);
