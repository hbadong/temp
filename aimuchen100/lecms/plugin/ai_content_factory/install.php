<?php
defined('ROOT_PATH') || exit;
$tablepre = $_ENV['_config']['db']['master']['tablepre'];

$sql1 = "CREATE TABLE IF NOT EXISTS `{$tablepre}ai_config` (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT '配置ID',
    site_id INT NOT NULL DEFAULT 0 COMMENT '站点ID(0=全局配置)',
    api_base_url VARCHAR(500) DEFAULT 'https://api.deepseek.com/v1' COMMENT 'API Base URL',
    api_key VARCHAR(500) NOT NULL DEFAULT '' COMMENT 'API密钥',
    model VARCHAR(100) DEFAULT 'deepseek-chat' COMMENT '模型名称',
    max_tokens INT DEFAULT 2048 COMMENT '最大生成token数',
    temperature DECIMAL(2,1) DEFAULT 0.7 COMMENT '温度参数',
    timeout INT DEFAULT 60 COMMENT '请求超时(秒)',
    batch_limit INT DEFAULT 100 COMMENT '单任务生成上限',
    max_retries INT DEFAULT 3 COMMENT '自动重试次数',
    no_url_generate TINYINT DEFAULT 0 COMMENT 'URL耗尽时自动建链',
    created_at DATETIME DEFAULT NULL COMMENT '创建时间',
    updated_at DATETIME DEFAULT NULL COMMENT '更新时间',
    UNIQUE KEY uk_site (site_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='AI API配置表'";

$this->db->query($sql1);

// 创建 AI 任务表
$sql2 = "CREATE TABLE IF NOT EXISTS `{$tablepre}ai_task` (
    `id` INT PRIMARY KEY AUTO_INCREMENT COMMENT '任务ID',
    `site_id` INT NOT NULL COMMENT '站点ID',
    `category_id` INT DEFAULT 0 COMMENT '分类ID',
    `prompt_template` TEXT COMMENT '提示词模板',
    `batch_size` INT DEFAULT 50 COMMENT '每批数量',
    `total` INT DEFAULT 0 COMMENT '总数量',
    `success` INT DEFAULT 0 COMMENT '成功数',
    `fail` INT DEFAULT 0 COMMENT '失败数',
    `status` TINYINT DEFAULT 0 COMMENT '0=待处理 1=处理中 2=完成 3=失败',
    `creator_uid` INT NOT NULL DEFAULT 0 COMMENT '创建人UID',
    `exec_lock` TINYINT NOT NULL DEFAULT 0 COMMENT '执行锁 0=空闲 1=执行中',
    `created_at` DATETIME DEFAULT NULL COMMENT '创建时间',
    `updated_at` DATETIME DEFAULT NULL COMMENT '更新时间',
    `exec_log` TEXT NULL COMMENT '执行日志',
    INDEX `idx_site_status` (`site_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='AI内容生成任务表'";

$this->db->query($sql2);

// ===== 游嘻CMS AI 伪原创扩展 =====
$tablepre = $_ENV['_config']['db']['master']['tablepre'];

// 1. le_ai_task 增加任务类型（article=原有文章生成；game_detail/article_content/category_seo/tag_seo=伪原创改写）
$task_cols = array(
    'task_type' => "ALTER TABLE `{$tablepre}ai_task` ADD COLUMN `task_type` VARCHAR(20) DEFAULT 'article' COMMENT '任务类型: article/game_detail/article_content/category_seo/tag_seo' AFTER `creator_uid`",
);
$t_exists = array();
$t_rows = $this->db->fetch_all("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$tablepre}ai_task'");
foreach((array)$t_rows as $r) $t_exists[$r['COLUMN_NAME']] = 1;
foreach($task_cols as $col => $sql_alter) {
    if(isset($t_exists[$col])) continue;
    $this->db->query($sql_alter);
}

// 2. le_cms_article 增加同步与改写标记（sync_client 依赖）
$art_cols = array(
    'main_id'         => "ALTER TABLE `{$tablepre}cms_article` ADD COLUMN `main_id` INT UNSIGNED DEFAULT 0 COMMENT '主站源文章ID: 0为本站创建'",
    'is_ai_rewritten' => "ALTER TABLE `{$tablepre}cms_article` ADD COLUMN `is_ai_rewritten` TINYINT DEFAULT 0 COMMENT 'AI改写标记: 1已改写'",
);
$art_exists = array();
$art_rows = $this->db->fetch_all("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$tablepre}cms_article'");
foreach((array)$art_rows as $r) $art_exists[$r['COLUMN_NAME']] = 1;
foreach($art_cols as $col => $sql_alter) {
    if(isset($art_exists[$col])) continue;
    $this->db->query($sql_alter);
}

// 3. le_cms_game_category 增加同步与改写标记
$cate_cols = array(
    'main_id'         => "ALTER TABLE `{$tablepre}cms_game_category` ADD COLUMN `main_id` INT UNSIGNED DEFAULT 0 COMMENT '主站源分类ID: 0为本站创建'",
    'is_ai_rewritten' => "ALTER TABLE `{$tablepre}cms_game_category` ADD COLUMN `is_ai_rewritten` TINYINT DEFAULT 0 COMMENT 'AI改写标记: 1已改写'",
);
$cate_exists = array();
$cate_rows = $this->db->fetch_all("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$tablepre}cms_game_category'");
foreach((array)$cate_rows as $r) $cate_exists[$r['COLUMN_NAME']] = 1;
foreach($cate_cols as $col => $sql_alter) {
    if(isset($cate_exists[$col])) continue;
    $this->db->query($sql_alter);
}

// 4. le_cms_article_tag 增加改写标记（tag_seo 伪原创目标）
$tag_cols = array(
    'is_ai_rewritten' => "ALTER TABLE `{$tablepre}cms_article_tag` ADD COLUMN `is_ai_rewritten` TINYINT DEFAULT 0 COMMENT 'AI改写标记: 1已改写'",
);
$tag_exists = array();
$tag_rows = $this->db->fetch_all("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$tablepre}cms_article_tag'");
foreach((array)$tag_rows as $r) $tag_exists[$r['COLUMN_NAME']] = 1;
foreach($tag_cols as $col => $sql_alter) {
    if(isset($tag_exists[$col])) continue;
    $this->db->query($sql_alter);
}

// 5. le_ai_config 增加每类型独立提示词模板（留空用内置默认）
$cfg_cols = array(
    'prompt_game_detail'    => "ALTER TABLE `{$tablepre}ai_config` ADD COLUMN `prompt_game_detail` TEXT COMMENT '游戏详情改写提示词'",
    'prompt_article_content' => "ALTER TABLE `{$tablepre}ai_config` ADD COLUMN `prompt_article_content` TEXT COMMENT '文章内容改写提示词'",
    'prompt_category_seo'   => "ALTER TABLE `{$tablepre}ai_config` ADD COLUMN `prompt_category_seo` TEXT COMMENT '分类SEO改写提示词'",
    'prompt_tag_seo'        => "ALTER TABLE `{$tablepre}ai_config` ADD COLUMN `prompt_tag_seo` TEXT COMMENT '标签SEO改写提示词'",
);
$cfg_exists = array();
$cfg_rows = $this->db->fetch_all("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$tablepre}ai_config'");
foreach((array)$cfg_rows as $r) $cfg_exists[$r['COLUMN_NAME']] = 1;
foreach($cfg_cols as $col => $sql_alter) {
    if(isset($cfg_exists[$col])) continue;
    $this->db->query($sql_alter);
}
