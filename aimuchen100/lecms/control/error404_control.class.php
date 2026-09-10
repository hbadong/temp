<?php
/**
 * Author: dadadezhou <zhoudada97@foxmail.com>
 * Date: 2022-09-24
 * Time: 9:05
 * Description:前台404页面控制器，不能继承base控制器
 */
defined('ROOT_PATH') or exit;

class error404_control extends control{
    public $_cfg = array();	// 全站参数
    public $_var = array();	// 各个模块页参数

    public $_user = array(); // 用户信息
    public $_uid = 0; // 用户ID
    public $_group = array(); // 用户组

    public $_parseurl = 0;  //是否开启了URL伪静态

    public $_control = 'error404';  //当前访问的控制器
    public $_action = 'index';  //当前访问的方法函数

    //404页面
	public function index() {
        // error404 不继承 base_control，site_manager 的 base_control_construct_before hook 不会执行，
        // 需在此按 HTTP_HOST 匹配站点并应用覆写，否则 404 页 cfg 仍指向全局 webdomain（如 localhost），
        // 页面内链接与正常页面不一致。与 site_manager hook 逻辑保持一致，且不落库。
        if(isset($_SERVER['HTTP_HOST']) && empty($_ENV['_site_override'])) {
            $_host_match = strtolower(trim($_SERVER['HTTP_HOST']));
            $_host_match = preg_replace('#:\d+$#', '', $_host_match);
            if($_host_match !== '') {
                $_site_map = $this->runtime->xget('site_domain_map');
                if(!is_array($_site_map)) $_site_map = array();
                if(empty($_site_map)) {
                    $_site_list = $this->site_manager->get_list(1);
                    foreach($_site_list as $_s) {
                        $_site_map[$_s['domain']] = $_s['sid'];
                    }
                    $this->runtime->set('site_domain_map', $_site_map);
                }
                require_once PLUGIN_PATH . 'site_manager/domain_match.func.php';
                $_sid = match_domain_host($_host_match, $_site_map, isset($_ENV['_config']['SUB_DOMAIN_SITEIDS']) ? $_ENV['_config']['SUB_DOMAIN_SITEIDS'] : array());
                if($_sid) {
                    $_site = $this->site_manager->get((int)$_sid);
                    if($_site) {
                        if(!defined('CURRENT_SITE_ID')) define('CURRENT_SITE_ID', (int)$_sid);
                        $_ENV['_site_override'] = array(
                            'theme'     => (isset($_site['theme']) && $_site['theme'] && $_site['theme'] !== 'default') ? $_site['theme'] : null,
                            'tpl'       => null,
                            'webdomain' => isset($_site['domain']) ? $_site['domain'] : null,
                            'webroot'   => isset($_site['domain']) ? HTTP . $_site['domain'] : null,
                            'weburl'    => null,
                        );
                        if($_ENV['_site_override']['theme'] !== null) {
                            $_ENV['_site_override']['tpl'] = (isset($this->_cfg['webdir']) ? $this->_cfg['webdir'] : '/') . 'view/' . $_ENV['_site_override']['theme'] . '/';
                        }
                        if($_ENV['_site_override']['weburl'] === null && $_ENV['_site_override']['webdomain'] !== null) {
                            $_ENV['_site_override']['weburl'] = HTTP . $_ENV['_site_override']['webdomain'] . (isset($this->_cfg['webdir']) ? $this->_cfg['webdir'] : '/');
                        }
                        // 重新读取 cfg，应用覆写
                        $this->_cfg = $this->runtime->xget();
                    }
                }
            }
        }

        $this->_cfg = $this->runtime->xget();
		// hook error404_control_index_before.php
		header('HTTP/1.1 404 Not Found');
		header("status: 404 Not Found");

		$this->_cfg['titles'] = '404 Not Found';
		$this->_var['topcid'] = -1;

        if( !empty($_ENV['_config']['lecms_parseurl']) ){
            $this->_parseurl = 1;
        }

        // hook error404_control_index_seo_after.php

        $this->assign('_uid',$this->_uid);
        $this->assign('_user',$this->_user);
        $this->assign('_group',$this->_group);
        $this->assign('_parseurl', $this->_parseurl);
        $this->assign('_control', $this->_control);
        $this->assign('_action', $this->_action);

		$this->assign('cfg', $this->_cfg);
		$this->assign('cfg_var', $this->_var);

		$GLOBALS['run'] = &$this;
        $_ENV['_theme'] = &$this->_cfg['theme'];
        $tpl = '404.htm';

        // 热门游戏推荐（REQ-04-AC3，url_generator 插件提供数据源）
        $hot_games = array();
        if(class_exists('hot_games_list', false) || is_file(PLUGIN_PATH . 'url_generator/lib/hot_games.func.php')) {
            require_once PLUGIN_PATH . 'url_generator/lib/hot_games.func.php';
            $site_id = defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0;
            $hot_games = hot_games_list($this->db, $site_id, 5);
        }
        $this->assign('hot_games', $hot_games);

		// hook error404_control_index_after.php
		$this->display($tpl);
	}

    // hook error404_control_after.php
}
