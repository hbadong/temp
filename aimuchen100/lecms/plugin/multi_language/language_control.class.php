<?php
defined('ROOT_PATH') or exit;


/**
 * 多语言配置管理控制器
 */

class language_control extends admin_control {

    /**
     * 语言列表（站点级配置）
     */
    public function index() {
        $site_id = defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0;
        $lang_model = core::model('language_config');

        // 全局默认配置（不可编辑，只读展示）
        $global_langs = $lang_model->find_fetch(array('site_id' => 0));

        // 站点级配置
        $site_langs = $lang_model->find_fetch(array('site_id' => (int)$site_id));

        // 插件设置（第二个 tab 展示）
        // 字段映射：
        //   - default_target_language 默认翻译目标语言（占位，language_config.get_default 才是权威值）
        //   - batch_translate_limit   批量翻译任务单次最多文章数（translation_batch.translate_post 入口）
        //   - translation_engine      翻译引擎选择（占位，当前仅 ai；预留本地/谷歌/DeepL 等接入）
        //   - glossary                术语表（占位，未落库；后续 translation_engine.translate() 可读取替换 prompt）
        $settings = $this->runtime->xget('multi_language_settings');
        if (!$settings || !is_array($settings)) {
            $settings = array(
                'default_target_language' => 'en',
                'batch_translate_limit' => 50,
                'translation_engine' => 'ai',
                'glossary' => '',
            );
        }

        // 提供常用目标语言选项（ISO 639-1 短码 + 中文名称）
        $common_targets = array(
            'en' => '英语',
            'ja' => '日语',
            'ko' => '韩语',
            'fr' => '法语',
            'de' => '德语',
            'es' => '西班牙语',
            'ru' => '俄语',
            'pt' => '葡萄牙语',
            'it' => '意大利语',
            'ar' => '阿拉伯语',
        );

        // 已启用的语言列表（用于"默认目标语言"下拉参考值）
        $enabled_langs = $lang_model->get_enabled($site_id);

        $this->assign('global_langs', $global_langs);
        $this->assign('site_langs', $site_langs);
        $this->assign('settings', $settings);
        $this->assign('common_targets', $common_targets);
        $this->assign('enabled_langs', $enabled_langs);
        $this->display('language_list.htm');
    }

    /**
     * 编辑站点语言配置表单
     */
    public function edit() {
        $site_id = defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0;
        $lang_model = core::model('language_config');
        $site_langs = $lang_model->find_fetch(array('site_id' => (int)$site_id));

        // 所有66种语言选项
        $all_languages = $this->get_all_languages();

        $this->assign('site_langs', $site_langs);
        $this->assign('all_languages', $all_languages);
        $this->display('language_edit.htm');
    }

    /**
     * 保存站点语言配置
     */
    public function edit_post() {
        if (!form_submit()) {
            $this->message(1, lang('submit_invalid'));
        }

        $site_id = defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0;
        $lang_model = core::model('language_config');

        $configs = array();
        foreach ($_POST['languages'] as $lang_code => $cfg) {
            $configs[] = array(
                'language' => $lang_code,
                'language_name' => $cfg['language_name'],
                'is_default' => isset($cfg['is_default']) ? 1 : 0,
                'is_enabled' => isset($cfg['is_enabled']) ? 1 : 0,
                'subdomain_enabled' => isset($cfg['subdomain_enabled']) ? 1 : 0,
                'prompt_template' => isset($cfg['prompt_template']) ? $cfg['prompt_template'] : '',
                'sort' => isset($cfg['sort']) ? (int)$cfg['sort'] : 0,
            );
        }

        $lang_model->save_site_config($site_id, $configs);

        $this->message(0, '语言配置已保存', '?language-index');
    }

    /**
     * 插件设置页（兼容重定向）
     *
     * 设置页已合并进主页面第二个 tab，此处仅做跳转，
     * 兼容旧导航/收藏链接。配置展示逻辑见 index()。
     */
    public function settings() {
        $this->message(0, '', '?language-index');
    }

    /**
     * 保存插件设置
     */
    public function settings_post() {
        if (!form_submit()) {
            $this->message(1, lang('submit_invalid'));
        }

        $batch_limit = (int)R('batch_translate_limit', 'P');
        if ($batch_limit < 1) $batch_limit = 1;
        if ($batch_limit > 500) $batch_limit = 500;

        $engine = R('translation_engine', 'P');
        $engine_allow = array('ai');
        if (!in_array($engine, $engine_allow, true)) {
            $engine = 'ai';
        }

        $settings = array(
            'default_target_language' => trim(R('default_target_language', 'P')) ?: 'en',
            'batch_translate_limit' => $batch_limit,
            'translation_engine' => $engine,
            'glossary' => trim(R('glossary', 'P')),
        );

        $this->runtime->set('multi_language_settings', $settings);
        $this->runtime->save_changed();
        E(0, '插件设置已保存');
    }

    /**
     * 66种语言列表（ISO 639-1 短码 + 中文名称）
     */
    private function get_all_languages() {
        return array(
            'zh' => '中文',
            'en' => '英语',
            'ja' => '日语',
            'ko' => '韩语',
            'fr' => '法语',
            'de' => '德语',
            'es' => '西班牙语',
            'ru' => '俄语',
            'pt' => '葡萄牙语',
            'it' => '意大利语',
            'ar' => '阿拉伯语',
            'th' => '泰语',
            'vi' => '越南语',
            'id' => '印尼语',
            'ms' => '马来语',
            'tr' => '土耳其语',
            'pl' => '波兰语',
            'nl' => '荷兰语',
            'sv' => '瑞典语',
            'da' => '丹麦语',
            'fi' => '芬兰语',
            'no' => '挪威语',
            'cs' => '捷克语',
            'el' => '希腊语',
            'he' => '希伯来语',
            'hu' => '匈牙利语',
            'ro' => '罗马尼亚语',
            'uk' => '乌克兰语',
            'bg' => '保加利亚语',
            'hr' => '克罗地亚语',
            'sk' => '斯洛伐克语',
            'sl' => '斯洛文尼亚语',
            'et' => '爱沙尼亚语',
            'lv' => '拉脱维亚语',
            'lt' => '立陶宛语',
            'sr' => '塞尔维亚语',
            'fa' => '波斯语',
            'hi' => '印地语',
            'bn' => '孟加拉语',
            'ta' => '泰米尔语',
            'te' => '泰卢固语',
            'ml' => '马拉雅拉姆语',
            'mr' => '马拉地语',
            'gu' => '古吉拉特语',
            'pa' => '旁遮普语',
            'kn' => '卡纳达语',
            'sw' => '斯瓦希里语',
            'af' => '南非语',
            'am' => '阿姆哈拉语',
            'ka' => '格鲁吉亚语',
            'hy' => '亚美尼亚语',
            'az' => '阿塞拜疆语',
            'kk' => '哈萨克语',
            'uz' => '乌兹别克语',
            'mn' => '蒙古语',
            'my' => '缅甸语',
            'km' => '高棉语',
            'lo' => '老挝语',
            'ne' => '尼泊尔语',
            'si' => '僧伽罗语',
            'ur' => '乌尔都语',
            'tl' => '菲律宾语',
            'ca' => '加泰罗尼亚语',
        );
    }
}
