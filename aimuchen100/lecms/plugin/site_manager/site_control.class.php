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

        // 概览统计（总数/启用/禁用/删除）
        $stats = $this->site_manager->count_status();
        $this->assign('stats', $stats);

        // 插件设置（已合并为主页面第二个 tab）
        $settings = $this->get_settings();
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
     * 服务端分页数据源（layui table url 模式）
     */
    public function json_list() {
        $page = max(1, (int)R('page', 'R'));
        $limit = (int)R('limit', 'R');
        if($limit < 1 || $limit > 100) $limit = 15;
        $keyword = trim(R('keyword', 'R'));
        $status = R('status', 'R');
        if($status === '1') $status = 1;
        elseif($status === '0') $status = 0;
        elseif($status === '-1') $status = -1;
        else $status = null;

        list($list, $total) = $this->site_manager->get_list_page($status, $keyword, $page, $limit);
        echo json_encode(array(
            'code' => 0,
            'msg' => '',
            'count' => $total,
            'data' => array_values($list),
        ));
        exit;
    }

    /**
     * 创建站点（GET=显示表单，POST=处理提交）
     */
    public function create() {
        if(form_submit()) {
            $site_name = trim(R('site_name', 'P'));
            $domain = trim(R('domain', 'P'));
            $theme = trim(R('theme', 'P'));

            if(empty($site_name) || empty($domain)) {
                $this->message(1, '站点名称和域名不能为空');
            }
            if(!$this->valid_domain($domain)) {
                $this->message(1, '域名格式不正确：仅允许字母、数字、点、连字符；通配符 * 仅能作为 *. 前缀');
            }

            $exists = $this->site_manager->get_by_domain($domain);
            if($exists) {
                $this->message(1, '该域名已被使用');
            }

            // 未指定主题时使用插件设置的默认主题（修复死配置：default_theme 此前未生效）
            if($theme === '') {
                $theme = $this->get_settings()['default_theme'];
            }

            $this->site_manager->create(array(
                'site_name' => $site_name,
                'domain' => $domain,
                'theme' => $theme,
                'config' => array(),
                'status' => $this->get_settings()['default_status'],
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
            if(!$this->valid_domain($domain)) {
                E(1, '域名格式不正确：仅允许字母、数字、点、连字符；通配符 * 仅能作为 *. 前缀');
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

            // 站点级 SEO 与维护提示语：留空即删除该项
            foreach(array('seo_title', 'seo_keywords', 'seo_description', 'maintenance_message') as $k) {
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

            // 排序值（数值大的在前）
            $sort_order = (int)R('sort_order', 'P');

            $this->site_manager->save($sid, array(
                'site_name' => $site_name,
                'domain' => $domain,
                'theme' => $theme,
                'sort_order' => $sort_order,
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
        $sort_order_val = (int)(isset($site['sort_order']) ? $site['sort_order'] : 0);
        $this->assign('sort_order', $sort_order_val);

        // 可视化配置回显数据
        $config_arr = $this->site_manager->get_config($sid);

        // 品牌三项（不存在时给空串）
        $brand = array();
        foreach(array('logo_url', 'favicon_url', 'watermark_url') as $k) {
            $brand[$k] = isset($config_arr[$k]) ? $config_arr[$k] : '';
        }
        $this->assign('config', $brand);

        // 站点级 SEO 与维护提示语（不存在时给空串）
        $seo = array();
        foreach(array('seo_title', 'seo_keywords', 'seo_description', 'maintenance_message') as $k) {
            $seo[$k] = isset($config_arr[$k]) ? $config_arr[$k] : '';
        }
        $this->assign('seo', $seo);

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
     * 复制站点：克隆源站点的主题/品牌/CSS 变量/SEO 配置到新站点
     */
    public function copy() {
        if(!form_submit()) {
            $this->message(1, lang('submit_invalid'));
        }
        $sid = (int)R('sid', 'P');
        $site_name = trim(R('site_name', 'P'));
        $domain = trim(R('domain', 'P'));

        if($sid <= 0) {
            $this->message(1, '无效的站点ID');
        }
        if($site_name === '' || $domain === '') {
            $this->message(1, '站点名称和域名不能为空');
        }
        if(!$this->valid_domain($domain)) {
            $this->message(1, '域名格式不正确：仅允许字母、数字、点、连字符；通配符 * 仅能作为 *. 前缀');
        }
        if($this->site_manager->get_by_domain($domain)) {
            $this->message(1, '该域名已被使用');
        }
        if(!$this->site_manager->copy_site($sid, $site_name, $domain)) {
            $this->message(1, '源站点不存在');
        }

        $this->runtime->set('site_domain_map', null);
        $this->message(0, '站点已复制', '?site-index');
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
     * 读取插件设置（含默认值兜底与类型规范化）
     * @return array
     */
    private function get_settings() {
        $settings = $this->runtime->xget('site_manager_settings');
        if(!$settings || !is_array($settings)) {
            $settings = array(
                'default_theme' => 'default',
                'default_site_name' => '默认站点',
                'wildcard_domain' => 1,
                'domain_match' => 'wildcard',
                'default_status' => 1,
            );
        }
        $settings['default_theme'] = trim((string)(isset($settings['default_theme']) ? $settings['default_theme'] : 'default')) ?: 'default';
        $settings['default_status'] = empty($settings['default_status']) ? 0 : 1;
        return $settings;
    }

    /**
     * 域名格式校验
     *
     * 允许字母/数字/点/连字符；通配符 * 仅允许作为 *. 前缀（泛解析）；
     * 禁止协议头、端口、斜杠、空白等特殊字符——域名会直接输出到前台模板与
     * 域名匹配逻辑，收紧规则同时规避存储型 XSS 与匹配歧义。
     *
     * @param string $domain
     * @return bool
     */
    private function valid_domain($domain) {
        $domain = trim($domain);
        if($domain === '') return false;
        // 协议头 / 特殊字符 / 连续点 / 首尾点
        if(preg_match('~^[a-zA-Z][a-zA-Z0-9\+\.\-]*://~', $domain)) return false;
        if(preg_match('~[/\s:@#%?&+=,;\\\\]~', $domain)) return false;
        if(preg_match('~(\.\.|^\.|\.$)~', $domain)) return false;
        if(strpos($domain, '*') !== false) {
            // 通配符仅允许 *.xxx 前缀形式，且整个域名至少含一个点
            return (bool)preg_match('~^\*\.[a-zA-Z0-9]([a-zA-Z0-9\-]*(\.[a-zA-Z0-9\-]+)*)?$~', $domain);
        }
        return (bool)preg_match('~^[a-zA-Z0-9]([a-zA-Z0-9\-]*(\.[a-zA-Z0-9\-]+)*)?$~', $domain);
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
