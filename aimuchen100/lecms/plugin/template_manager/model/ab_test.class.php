<?php
defined('ROOT_PATH') || exit;

/**
 * ABTest 模型 — 主题 A/B 测试
 */
class ABTest
{
    /**
     * 分配 A/B 测试组
     * @return string 'A' | 'B'
     */
    public function assign($site_id, $theme_a, $theme_b, $ratio = 50)
    {
        // 稳定哈希：site_id + 固定盐
        $hash = crc32((string)$site_id . '_ab_test_salt');
        $bucket = abs($hash) % 100;

        return $bucket < $ratio ? 'A' : 'B';
    }
}
