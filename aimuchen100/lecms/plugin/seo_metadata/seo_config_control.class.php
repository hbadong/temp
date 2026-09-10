<?php
defined('ROOT_PATH') or exit;

/**
 * 后台 SEO 配置管理控制器
 */

class seo_config_control extends admin_control {

    /**
     * SEO 配置表单
     */
    public function index() {
        $config = $this->runtime->xget('seo_default_config');
        if(!$config) {
            // 站点名称优先从后台 KV 配置读取（$_ENV['_cfg'] 在后台不存在）
            $webname = $this->runtime->xget('webname');
            $config = array(
                'title' => $webname ?: 'LeCMS',
                'keywords' => '',
                'description' => '',
            );
        }
        $this->assign('config', $config);
        $sitemap_freqs = array('always','hourly','daily','weekly','monthly','yearly','never');
        $this->assign('sitemap_freqs', $sitemap_freqs);

        // 综合设置（原独立 settings 页，现合并为主页第二个 Tab）
        $settings = $this->runtime->xget('seo_metadata_settings');
        if (!$settings || !is_array($settings)) {
            $settings = array(
                'default_title' => '',
                'default_keywords' => '',
                'default_description' => '',
                'category_title_tpl' => '{category_name} - {site_name}',
                'category_keywords_tpl' => '{category_name},{site_name}',
                'category_description_tpl' => '{category_description}',
                'detail_title_tpl' => '{content_title} - {site_name}',
                'detail_keywords_tpl' => '{content_keywords}',
                'detail_description_tpl' => '{content_description}',
                'sitemap_freq' => 'daily',
                'sitemap_enabled' => 1,
                'robots_txt' => "User-agent: *\nAllow: /\nDisallow: /admin/\nSitemap: /sitemap.xml",
            );
        }

        // 兼容老 KV：把 seo_default_config 合并到新结构
        $old = $this->runtime->xget('seo_default_config');
        if (is_array($old)) {
            if (empty($settings['default_title']) && !empty($old['title'])) {
                $settings['default_title'] = $old['title'];
            }
            if (empty($settings['default_keywords']) && !empty($old['keywords'])) {
                $settings['default_keywords'] = $old['keywords'];
            }
            if (empty($settings['default_description']) && !empty($old['description'])) {
                $settings['default_description'] = $old['description'];
            }
        }
        $this->assign('settings', $settings);

        $this->display('seo_config.htm');
    }

    /**
     * 保存 SEO 配置
     */
    public function index_post() {
        if(!form_submit()) {
            $this->message(1, lang('submit_invalid'));
        }

        $config = array(
            'title' => trim(R('seo_title', 'P')),
            'keywords' => trim(R('seo_keywords', 'P')),
            'description' => trim(R('seo_description', 'P')),
        );

        $this->runtime->set('seo_default_config', $config);
        $this->message(0, 'SEO配置保存成功', '?seo_config-index');
    }

    /**
     * 兼容重定向：设置页已合并为主页第二个 Tab
     */
    public function settings() {
        $this->message(0, '', 'index.php?seo_config-index-tab-settings');
    }

    /**
     * 保存 SEO 元数据插件设置
     */
    public function settings_post() {
        if (!form_submit()) {
            $this->message(1, lang('submit_invalid'));
        }

        $settings = array(
            'default_title' => trim(R('default_title', 'P')),
            'default_keywords' => trim(R('default_keywords', 'P')),
            'default_description' => trim(R('default_description', 'P')),
            'category_title_tpl' => trim(R('category_title_tpl', 'P')),
            'category_keywords_tpl' => trim(R('category_keywords_tpl', 'P')),
            'category_description_tpl' => trim(R('category_description_tpl', 'P')),
            'detail_title_tpl' => trim(R('detail_title_tpl', 'P')),
            'detail_keywords_tpl' => trim(R('detail_keywords_tpl', 'P')),
            'detail_description_tpl' => trim(R('detail_description_tpl', 'P')),
            'sitemap_freq' => in_array(R('sitemap_freq', 'P'), array('always','hourly','daily','weekly','monthly','yearly','never')) ? R('sitemap_freq', 'P') : 'daily',
            'sitemap_enabled' => R('sitemap_enabled', 'P') ? 1 : 0,
            'robots_txt' => (string)R('robots_txt', 'P'),
        );

        $this->runtime->set('seo_metadata_settings', $settings);
        // 同步一份到旧 KV，保持向后兼容
        $this->runtime->set('seo_default_config', array(
            'title' => $settings['default_title'],
            'keywords' => $settings['default_keywords'],
            'description' => $settings['default_description'],
        ));
        $this->runtime->save_changed();
        E(0, 'SEO 设置已保存');
    }
}
