<?php
/**
 * 敏感词过滤插件 — 基础词库（种子数据）
 *
 * 分类说明：
 * - political（政治）: level 3 — 严重，直接拒绝
 * - adult（色情）    : level 3 — 严重，直接拒绝
 * - violence（暴力） : level 2 — 替换为 ***，允许入库但脱敏
 * - spam（广告/垃圾）: level 2 — 替换为 ***，允许入库但脱敏
 *
 * 用户可通过后台添加/导入扩展词库。
 */

return array(
    // ======================== political（政治，level 3） ========================
    array('word' => '共产党', 'category' => 'political', 'level' => 3),
    array('word' => '台独',   'category' => 'political', 'level' => 3),
    array('word' => '港独',   'category' => 'political', 'level' => 3),
    array('word' => '藏独',   'category' => 'political', 'level' => 3),
    array('word' => '法轮功', 'category' => 'political', 'level' => 3),
    array('word' => '疆独',   'category' => 'political', 'level' => 3),
    array('word' => '六四',   'category' => 'political', 'level' => 3),
    array('word' => '反党',   'category' => 'political', 'level' => 3),
    array('word' => '反革命', 'category' => 'political', 'level' => 3),
    array('word' => '颠覆国家', 'category' => 'political', 'level' => 3),
    array('word' => '邪教',   'category' => 'political', 'level' => 3),
    array('word' => '民主运动', 'category' => 'political', 'level' => 3),
    array('word' => '政治迫害', 'category' => 'political', 'level' => 3),
    array('word' => '言论审查', 'category' => 'political', 'level' => 3),
    array('word' => '维权律师', 'category' => 'political', 'level' => 3),

    // ======================== adult（色情，level 3） ========================
    array('word' => '色情',   'category' => 'adult', 'level' => 3),
    array('word' => '裸聊',   'category' => 'adult', 'level' => 3),
    array('word' => '卖淫',   'category' => 'adult', 'level' => 3),
    array('word' => '嫖娼',   'category' => 'adult', 'level' => 3),
    array('word' => '性交易', 'category' => 'adult', 'level' => 3),
    array('word' => '援交',   'category' => 'adult', 'level' => 3),
    array('word' => '乱伦',   'category' => 'adult', 'level' => 3),
    array('word' => '淫秽',   'category' => 'adult', 'level' => 3),
    array('word' => '色情视频', 'category' => 'adult', 'level' => 3),
    array('word' => '成人电影', 'category' => 'adult', 'level' => 3),
    array('word' => '一夜情', 'category' => 'adult', 'level' => 3),
    array('word' => '性服务', 'category' => 'adult', 'level' => 3),
    array('word' => '招嫖',   'category' => 'adult', 'level' => 3),
    array('word' => '洗荤浴', 'category' => 'adult', 'level' => 3),
    array('word' => '按摩小姐', 'category' => 'adult', 'level' => 3),

    // ======================== violence（暴力，level 2） ========================
    array('word' => '暴力',   'category' => 'violence', 'level' => 2),
    array('word' => '杀人',   'category' => 'violence', 'level' => 2),
    array('word' => '恐怖袭击', 'category' => 'violence', 'level' => 2),
    array('word' => '爆炸',   'category' => 'violence', 'level' => 2),
    array('word' => '枪支',   'category' => 'violence', 'level' => 2),
    array('word' => '毒品',   'category' => 'violence', 'level' => 2),
    array('word' => '赌博',   'category' => 'violence', 'level' => 2),
    array('word' => '绑架',   'category' => 'violence', 'level' => 2),
    array('word' => '抢劫',   'category' => 'violence', 'level' => 2),
    array('word' => '自杀',   'category' => 'violence', 'level' => 2),

    // ======================== spam（广告/垃圾，level 2） ========================
    array('word' => '博彩',   'category' => 'spam', 'level' => 2),
    array('word' => '贷款',   'category' => 'spam', 'level' => 2),
    array('word' => '中奖',   'category' => 'spam', 'level' => 2),
    array('word' => '免费赠送', 'category' => 'spam', 'level' => 2),
    array('word' => '代开发票', 'category' => 'spam', 'level' => 2),
    array('word' => '刷单',   'category' => 'spam', 'level' => 2),
    array('word' => '兼职刷信誉', 'category' => 'spam', 'level' => 2),
    array('word' => '高薪招聘', 'category' => 'spam', 'level' => 2),
    array('word' => '加微信', 'category' => 'spam', 'level' => 2),
    array('word' => '扫码加群', 'category' => 'spam', 'level' => 2),
);
