<?php
/**
 * Cron 定时检查外链收录状态
 * 定期检查已提交的外链是否被搜索引擎收录
 */

/**
 * 适配器工厂函数（Hook 全局上下文中使用，避免 $this 问题）
 */
function adapter_factory_create($platform) {
    switch ($platform) {
        case 'aiqicha':
            return new aiqicha_adapter();
        case 'qcc':
            return new qcc_adapter();
        case 'tianyancha':
            return new tianyancha_adapter();
        default:
            return null;
    }
}

$site_id = defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0;
if (empty($site_id)) {
    return;
}

// 加载外链模型（插件模型，不在核心模型搜索路径中）

$model = core::model('external_link');
$pending = $model->get_pending_check(50);

if (empty($pending)) {
    return;
}

foreach ($pending as $link) {
    // 获取对应平台的适配器
    $adapter = adapter_factory_create($link['platform']);
    if (!$adapter) {
        continue;
    }

    // 检查收录状态
    $result = $adapter->checkStatus($link['platform_url']);

    // 更新状态
    if ($result['indexed']) {
        $model->update_status($link['id'], 2, array(
            'last_check_at' => date('Y-m-d H:i:s'),
            'check_count' => $link['check_count'] + 1,
        ));
    } else {
        // 增加检查次数，最多 5 次后标记为失败
        $new_check_count = ($link['check_count'] ?? 0) + 1;
        if ($new_check_count >= 5) {
            $model->update_status($link['id'], 3, array(
                'last_check_at' => date('Y-m-d H:i:s'),
                'check_count' => $new_check_count,
            ));
        } else {
            $model->update_status($link['id'], 1, array(
                'last_check_at' => date('Y-m-d H:i:s'),
                'check_count' => $new_check_count,
            ));
        }
    }
}
