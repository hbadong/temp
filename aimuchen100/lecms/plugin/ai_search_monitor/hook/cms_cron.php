<?php
/**
 * AI搜索监测 Cron 定时任务
 * 每日执行品牌词监测
 */

if (!defined('ROOT_PATH')) {
    exit;
}

$site_id = defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0;
if (empty($site_id)) {
    return;
}

// 加载关键词模型（插件模型，不在核心模型搜索路径中）

$model = core::model('keyword');
$keywords = $model->get_keywords($site_id);

if (empty($keywords)) {
    return;
}

// 平台适配器白名单映射
$adapter_map = array(
    'baidu_wenxin' => 'wenxin_adapter',
    'doubao' => 'doubao_adapter',
    'wechat_ai' => 'wechat_adapter',
);

$total_checks = 0;

foreach ($keywords as $keyword_row) {
    $keyword = $keyword_row['keyword'];

    // 从 keyword_variant 读取配置的平台列表
    $platforms = array('baidu_wenxin', 'doubao', 'wechat_ai'); // 默认全平台
    if (!empty($keyword_row['keyword_variant'])) {
        $decoded = json_decode($keyword_row['keyword_variant'], true);
        if (is_array($decoded) && !empty($decoded)) {
            $platforms = $decoded;
        }
    }

    foreach ($platforms as $platform) {
        // 白名单校验：仅允许已知平台
        if (!isset($adapter_map[$platform])) {
            continue;
        }

        $adapter_class = $adapter_map[$platform];

        try {
            $adapter = new $adapter_class();
            $result = $adapter->search($keyword);

            // 记录监测结果
            $model->add_log(array(
                'site_id' => $site_id,
                'platform' => $platform,
                'keyword' => $keyword,
                'found' => $result['found'],
                'position' => $result['position'],
                'reference_url' => $result['reference_url'],
                'response_snippet' => $result['response_snippet'],
                'confidence' => $result['confidence'],
            ));

            $total_checks++;

            // 限频：每次查询间隔3秒
            sleep(3);
        } catch (Exception $e) {
            // 记录错误但继续下一个
            continue;
        }
    }
}