<?php
defined('ROOT_PATH') || exit;

/**
 * 站点识别 Hook
 * 在 base_control 构造前执行，识别当前请求所属站点
 */

// 后台管理不执行站点识别
if(defined('APP_NAME') && APP_NAME === 'admin') {
    return;
}

// 获取当前域名
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : ''; // PHP 5.4 兼容

if(empty($host)) return;

// 定义站点域名映射缓存键
$cache_key = 'site_domain_map';

// 尝试从缓存获取
$site_map = $this->runtime->xget($cache_key);

if(!$site_map || !is_array($site_map)) {
    // 缓存未命中，查询数据库
    $site_list = $this->site_manager->get_list(1);
    $site_map = array();
    foreach($site_list as $site) {
        $site_map[$site['domain']] = $site['sid'];
    }
    // 写入缓存（set 整行写，与上方 xget($cache_key) 读取键一致）
    $this->runtime->set($cache_key, $site_map);
}

// 精确/泛解析/二级域名统一匹配（REQ-01-AC6/AC7）
// SUB_DOMAIN_SITEIDS 白名单控制二级域名模式：优先读常量（config.inc.php 中 define），回退全局配置数组
$sub_whitelist = isset($_ENV['_config']['SUB_DOMAIN_SITEIDS']) ? $_ENV['_config']['SUB_DOMAIN_SITEIDS'] : array();
if(defined('SUB_DOMAIN_SITEIDS') && is_array(constant('SUB_DOMAIN_SITEIDS'))) {
    $sub_whitelist = constant('SUB_DOMAIN_SITEIDS');
}
require_once PLUGIN_PATH . 'site_manager/domain_match.func.php';
$sid = match_domain_host($host, $site_map, $sub_whitelist);

// 结果处理
if($sid) {
    // 定义当前站点ID常量
    if(!defined('CURRENT_SITE_ID')) {
        define('CURRENT_SITE_ID', $sid);
    }

    // 加载站点信息
    $site = $this->site_manager->get($sid);
    if($site) {
        // 定义当前主题
        if(!defined('CURRENT_THEME')) {
            define('CURRENT_THEME', $site['theme']);
        }

        // 关键：把站点主题写回 _cfg['theme']。
        // 前台各控制器均以引用方式赋值 $_ENV['_theme'] = &$this->_cfg['theme']，
        // 仅 define(CURRENT_THEME) 不会影响实际渲染主题（view 层读的是 _cfg['theme']，
        // 即后台全局设置的 kv 值），导致站点选择的主题不生效——此处覆写后引用链全局生效。
        //
        // 站点 theme 为 'default'（后台建站默认占位值）视为「跟随全局」，不覆写——
        // 避免老站点登记值停留在 default 时，从全局主题（kv cfg.theme，当前为 blog_finpro）
        // 意外跌落到出厂主题；用户在站点编辑中主动选择其它主题时才真正切换。
        if(!empty($site['theme']) && $site['theme'] !== 'default') {
            $this->_cfg['theme'] = $site['theme'];
            // cfg['tpl'] 是 runtime 缓存的模板路径（webdir.view.{theme}/），需一并覆写，
            // 模板内 {$cfg[tpl]} 引用的静态资源才会指向站点主题目录
            $this->_cfg['tpl'] = (isset($this->_cfg['webdir']) ? $this->_cfg['webdir'] : '/').'view/'.$site['theme'].'/';
            $_ENV['_config']['theme'] = $site['theme'];
        }

        // 站点域名覆写：模板与 URL 生成大量使用 {$cfg[weburl]}（= HTTP.webdomain.webdir），
        // webdomain 是全局 kv 值（主站域名），不覆写会导致子站点页面里所有站内链接
        // 都指向主站。以当前匹配的站点域名为准，webroot/weburl 一并同步。
        if(!empty($site['domain'])) {
            $_tm_webdir = isset($this->_cfg['webdir']) ? $this->_cfg['webdir'] : '/';
            $this->_cfg['webdomain'] = $site['domain'];
            $this->_cfg['webroot'] = HTTP . $site['domain'];
            $this->_cfg['weburl'] = HTTP . $site['domain'] . $_tm_webdir;

            // 同步到全局覆写，供 runtime_model::xget() 返回前统一应用，
            // 使 model 层（如 cms_content->content_url 生成 {weburl} 链接）与控制器 _cfg 保持一致
            $_ENV['_site_override'] = array(
                'theme'     => isset($this->_cfg['theme']) ? $this->_cfg['theme'] : null,
                'tpl'       => isset($this->_cfg['tpl']) ? $this->_cfg['tpl'] : null,
                'webdomain' => $this->_cfg['webdomain'],
                'webroot'   => $this->_cfg['webroot'],
                'weburl'    => $this->_cfg['weburl'],
            );
        }

        // 加载站点级配置
        $site_config = json_decode($site['config'], true);
        if(!is_array($site_config)) $site_config = array();

        // 将站点配置注入到全局
        if(!defined('SITE_CONFIG')) {
            define('SITE_CONFIG', $site_config);
        }

        // 站点禁用检查
        if($site['status'] == 0) {
            // 返回维护页面
            $this->assign('cfg', $this->_cfg);
            $this->assign('site', $site);
            $this->display('site_disabled.htm');
            exit();
        }
    }
} else {
    // 未匹配到站点，返回404
    core::error404();
    exit();
}
