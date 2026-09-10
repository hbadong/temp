<?php
/**
 * 文章更新频率控制 — Cron Hook
 *
 * 在 LECMS 每日 cron 触发时执行文章更新调度：
 *   1. 查询待执行任务（next_run <= NOW() AND status = 1）
 *   2. 按站点/分类分组
 *   3. 对每个待执行任务调用 AI 重写文章
 *   4. 更新下次执行时间
 *
 * Hook 点：LECMS cron 每日执行流程中
 * 文件位置：plugin/article-strategy/hook/cms_cron.php
 */

// 确保插件已启用
if (!defined('ROOT_PATH')) {
    exit;
}

// 加载调度器模型（文件名不符合 core::model() 约定，显式 require）
require_once ROOT_PATH . 'lecms/plugin/article_strategy/model/update_scheduler.class.php';

// 加载调度器模型
$scheduler = core::model('update_scheduler');

// 执行调度
$scheduler->run();
