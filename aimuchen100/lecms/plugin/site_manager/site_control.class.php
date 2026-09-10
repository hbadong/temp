<?php
defined('ROOT_PATH') or exit;

/**
 * 后台站点管理控制器
 */

class site_control extends admin_control {

    /**
     * 站点列表
     */
    public function index() {
        $page = max(1, (int)R('page', 'R'));
        $pagenum = 15;

        // 获取站点列表
        $sites = $this->site_manager->get_list();
        $total = count($sites);

        $this->assign('sites', $sites);
        $sites_json = json_encode(array_values($sites));
        $this->assign('sites_json', $sites_json);
        $this->assign('total', $total);

        // 插件设置（已合并为主页面第二个 tab）
        $settings = $this->runtime->xget('site_manager_settings');
        if (!$settings || !is_array($settings)) {
            $settings = array(
                'default_theme' => 'default',
                'default_site_name' => '默认站点',
                'wildcard_domain' => 1,
                'domain_match' => 'wildcard',
                'default_status' => 1,
            );
        }
        $this->assign('settings', $settings);

        // 获取可用主题列表
        $themes = $this->get_available_themes();
        $this->assign('themes', $themes);

        // 默认激活的 tab（兼容旧入口 ?site-index-tab-settings）
        $active_tab = trim(R('tab', 'R')) == 'settings' ? 'settings' : 'default';
        $this->assign('active_tab', $active_tab);

        $this->display();
    }

    /**
     * 创建站点（GET=显示表单，POST=处理提交）
     */
    public function create() {
        if(form_submit()) {
            $site_name = trim(R('site_name', 'P'));
            $domain = trim(R('domain', 'P'));
            $theme = trim(R('theme', 'P')) ?: 'default';

            if(empty($site_name) || empty($domain)) {
                $this->message(1, '站点名称和域名不能为空');
            }

            $exists = $this->site_manager->get_by_domain($domain);
            if($exists) {
                $this->message(1, '该域名已被使用');
            }

            $this->site_manager->create(array(
                'site_name' => $site_name,
                'domain' => $domain,
                'theme' => $theme,
                'config' => array(),
            ));

            $this->runtime->set('site_domain_map', null);
            $this->message(0, '站点创建成功');
        }

        $themes = $this->get_available_themes();
        $this->assign('themes', $themes);
        $this->display();
    }

    /**
     * 编辑站点（GET=显示表单，POST=处理提交）
     *
     * 站点配置采用可视化表单（品牌设置 / 启用主题 / CSS 变量），
     * 不再暴露 JSON 文本域；表单未覆盖的历史扩展键原样保留。
     */
    public function edit() {
        $sid = (int)R('sid', 'R');

        // 可视化配置表单（品牌设置 / 启用主题 / CSS 变量）
        if(form_submit()) {
            $site_name = trim(R('site_name', 'P'));
            $domain = trim(R('domain', 'P'));
            $theme = trim(R('theme', 'P'));

            if(empty($site_name) || empty($domain)) {
                E(1, '站点名称和域名不能为空');
            }

            $exists = $this->site_manager->get_by_domain($domain);
            if($exists && $exists['sid'] != $sid) {
                E(1, '该域名已被其他站点使用');
            }

            // 在原有配置基础上合并可视化表单项（保留未知扩展键）
            $config = $this->site_manager->get_config($sid);

            // 品牌设置
            foreach(array('logo_url', 'favicon_url', 'watermark_url') as $k) {
                $v = trim(R($k, 'P'));
                if($v === '') {
                    unset($config[$k]);
                } else {
                    $config[$k] = $v;
                }
            }

            // 启用主题白名单：一个都不勾 = 不限制，删除该项
            $enabled = R('enabled_themes', 'P');
            if(!empty($enabled) && is_array($enabled)) {
                $config['enabled_themes'] = array_values(array_map('trim', $enabled));
            } else {
                unset($config['enabled_themes']);
            }

            // CSS 变量：keys/values 平行数组配对，空行丢弃
            $var_keys = R('theme_var_keys', 'P');
            $var_values = R('theme_var_values', 'P');
            $vars = array();
            if(!empty($var_keys) && is_array($var_keys)) {
                foreach($var_keys as $i => $k) {
                    $k = trim((string)$k);
                    $v = isset($var_values[$i]) ? trim((string)$var_values[$i]) : '';
                    if($k !== '') {
                        $vars[$k] = $v;
                    }
                }
            }
            if(!empty($vars)) {
                $config['theme_vars'] = $vars;
            } else {
                unset($config['theme_vars']);
            }

            $this->site_manager->save($sid, array(
                'site_name' => $site_name,
                'domain' => $domain,
                'theme' => $theme,
                'config' => $config,
            ));

            $this->runtime->set('site_domain_map', null);
            E(0, '站点更新成功');
        }

        $site = $this->site_manager->get($sid);
        if(!$site) {
            $this->message(1, '站点不存在');
        }
        $this->assign('site', $site);

        // 可视化配置回显数据
        $config_arr = $this->site_manager->get_config($sid);

        // 品牌三项（不存在时给空串）
        $brand = array();
        foreach(array('logo_url', 'favicon_url', 'watermark_url') as $k) {
            $brand[$k] = isset($config_arr[$k]) ? $config_arr[$k] : '';
        }
        $this->assign('config', $brand);

        // 启用主题勾选映射：主题名 => 'checked' 或 ''
        $themes = $this->get_available_themes();
        $enabled_list = isset($config_arr['enabled_themes']) && is_array($config_arr['enabled_themes']) ? $config_arr['enabled_themes'] : array();
        $enabled_map = array();
        foreach($themes as $t) {
            $enabled_map[$t] = in_array($t, $enabled_list, true) ? 'checked' : '';
        }
        $this->assign('themes', $themes);
        $this->assign('enabled_map', $enabled_map);

        // CSS 变量键值对列表
        $theme_vars = array();
        if(isset($config_arr['theme_vars']) && is_array($config_arr['theme_vars'])) {
            foreach($config_arr['theme_vars'] as $k => $v) {
                $theme_vars[] = array('k' => $k, 'v' => (string)$v);
            }
        }
        $this->assign('theme_vars', $theme_vars);

        $this->display();
    }

    /**
     * 一键同步站点（REQ-01-AC8）：重建域名映射缓存、刷新站点运行时数据
     */
    public function sync() {
        if(!form_submit()) {
            $this->message(1, lang('submit_invalid'));
        }
        $n = $this->site_manager->sync_all();
        $this->runtime->set('site_domain_map', null);
        $this->message(0, '已同步 ' . (int)$n . ' 个站点', '?site-index');
    }

    /**
     * 删除站点（软删除）
     */
    public function delete() {
        if(!form_submit()) {
            $this->message(1, lang('submit_invalid'));
        }
        $sid = (int)R('sid', 'P');
        if($sid <= 0) {
            $this->message(1, '无效的站点ID');
        }
        $this->site_manager->delete_site($sid);
        $this->runtime->set('site_domain_map', null);
        $this->message(0, '站点已删除', '?site-index');
    }

    /**
     * 批量删除站点（软删除）
     */
    public function batch_delete() {
        $id_arr = R('id_arr', 'P');
        if (!empty($id_arr) && is_array($id_arr)) {
            foreach ($id_arr as $sid) {
                $this->site_manager->delete_site((int)$sid);
            }
            $this->runtime->set('site_domain_map', null);
            $this->message(0, '站点已批量删除', '?site-index');
        }
        $this->message(1, '请选择要删除的站点');
    }

    /**
     * 切换站点状态
     */
    public function toggle() {
        if(!form_submit()) {
            $this->message(1, lang('submit_invalid'));
        }
        $sid = (int)R('sid', 'P');
        if($sid <= 0) {
            $this->message(1, '无效的站点ID');
        }
        $this->site_manager->toggle_status($sid);
        $this->message(0, '状态已更新', '?site-index');
    }

    /**
     * 插件设置页已合并到主页面（第二个 tab），兼容跳转
     */
    public function settings() {
        $this->message(0, '', 'index.php?site-index-tab-settings');
    }

    /**
     * 保存插件设置
     */
    public function settings_post() {
        if (!form_submit()) {
            $this->message(1, lang('submit_invalid'));
        }

        $settings = array(
            'default_theme' => trim(R('default_theme', 'P')) ?: 'default',
            'default_site_name' => trim(R('default_site_name', 'P')) ?: '默认站点',
            'wildcard_domain' => R('wildcard_domain', 'P') ? 1 : 0,
            'domain_match' => R('domain_match', 'P') == 'exact' ? 'exact' : 'wildcard',
            'default_status' => R('default_status', 'P') ? 1 : 0,
        );

        $this->runtime->set('site_manager_settings', $settings);
        $this->runtime->save_changed();
        E(0, '插件设置已保存');
    }

    /**
     * 获取可用主题列表
     *
     * 注意：必须使用 ROOT_PATH 而非 APP_PATH。后台 admin/ 子应用的 APP_PATH
     * 指向 ROOT_PATH.'admin/'，而主题目录在 ROOT_PATH.'view/' 下；若用 APP_PATH
     * 会落到 ROOT_PATH.'admin/view/'（只有默认占位主题），导致站点创建/编辑
     * 的「主题」下拉只有 default 一项。
     *
     * @return array
     */
    private function get_available_themes() {
        $themes = array();
        $theme_dir = ROOT_PATH . 'view/';
        if (is_dir($theme_dir)) {
            $dirs = glob($theme_dir . '*', GLOB_ONLYDIR);
            foreach ($dirs as $dir) {
                $themes[] = basename($dir);
            }
        }
        // 后台 admin/view 下的占位主题（仅当 ROOT_PATH 下无主题时兜底，避免漏掉 admin 端 theme 列表）
        if (empty($themes)) {
            $fallback = APP_PATH . 'view/';
            if (is_dir($fallback)) {
                foreach (glob($fallback . '*', GLOB_ONLYDIR) as $dir) {
                    $themes[] = basename($dir);
                }
            }
        }
        if (empty($themes)) {
            $themes = array('default');
        }
        return $themes;
    }
}
