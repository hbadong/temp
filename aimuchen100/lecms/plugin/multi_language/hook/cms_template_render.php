<?php
/**
 * 模板变量注入 Hook
 * 向模板引擎注入多语言相关变量
 */

$site_id = defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0;
$current_lang = defined('CURRENT_LANGUAGE') ? CURRENT_LANGUAGE : 'zh';

if (empty($site_id)) {
    return;
}


$lang_model = core::model('language_config');
try {
    $enabled_langs = $lang_model->get_enabled($site_id);
    $default_lang = $lang_model->get_default($site_id);
} catch (Exception $e) {
    return;
}

// 注入当前语言信息
$this->assign('current_language', $current_lang);
$this->assign('default_language', $default_lang);

// 注入可用语言列表（排除默认语言，用于语言切换器）
$switchable = array();
foreach ($enabled_langs as $lang) {
    if ($lang['language'] != $default_lang) {
        $switchable[] = $lang;
    }
}
$this->assign('available_languages', $switchable);

// 注入翻译进度（当前文章）
if (!empty($this->_vars['article'])) {
    $article = $this->_vars['article'];
    $trans_model = core::model('article_translation');
    $translations = $trans_model->get_by_source($article['id']);

    // 构建翻译映射：language_code => record
    $trans_map = array();
    foreach ($translations as $t) {
        $trans_map[$t['language']] = $t;
    }
    $this->assign('translations', $trans_map);

    // 翻译进度统计
    $progress = $trans_model->get_progress($article['id']);
    $this->assign('translation_progress', $progress);
}

// ====== SEO: hreflang 标签注入 ======
if (!empty($this->_vars['article']) || !empty($this->_vars['page_url'])) {
    $current_url = $this->_vars['page_url'] ?: '';
    $hreflang_tags = '';

    if ($current_url) {
        foreach ($enabled_langs as $lang) {
            if ($lang['language'] == $default_lang) {
                // 默认语言 URL 去除语言前缀
                $lang_url = str_replace('/' . $current_lang . '/', '/', $current_url);
                // 处理根路径情况
                $lang_url = preg_replace('#^([^:]+://[^/]+)/' . $current_lang . '/#', '$1/', $lang_url);
            } else {
                // 非默认语言 URL 添加语言前缀
                $lang_url = preg_replace('#^(https?://[^/]+)(/.*)?#', '$1/' . $lang['language'] . '$2', $current_url);
            }

            $hreflang_tags .= '<link rel="alternate" hreflang="' . $lang['language'] . '" href="' . htmlspecialchars($lang_url, ENT_QUOTES, 'UTF-8') . '">' . "\n";
        }
        // x-default
        $hreflang_tags .= '<link rel="alternate" hreflang="x-default" href="' . htmlspecialchars($current_url, ENT_QUOTES, 'UTF-8') . '">' . "\n";
    }

    $this->assign('hreflang_tags', $hreflang_tags);
}
