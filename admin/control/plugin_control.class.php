<?php
defined('ROOT_PATH') or exit;

class plugin_control extends admin_control {
	// 插件管理
	public function index() {
        // hook admin_plugin_control_index_before.php
		$plugins = core::get_plugins();

		// 检查是否有图标和设置功能
		foreach($plugins as &$arr) {
			if(isset($arr) && is_array($arr)) {
				foreach($arr as $dir => &$v) {
					is_file(PLUGIN_PATH.$dir.'/show.jpg') && $v['is_show'] = 1;
				}
			}
		}

		$plugin_disable = (int)$_ENV['_config']['plugin_disable'];

        // hook admin_plugin_control_index_after.php
		$this->assign('plugins', $plugins);
        $this->assign('plugin_disable', $plugin_disable);
		$this->display();
	}

	// 插件启用
	public function enable() {
        // hook admin_plugin_control_enable_before.php
		$dir = R('dir', 'P');
		$this->check_plugin($dir);
		$plugins = $this->get_plugin_config();
		isset($plugins[$dir]) || E(1, lang('data_error'));

		// 如果是编辑器插件，卸载其他编辑器插件
		if(substr($dir, 0, 7) == 'editor_') {
			foreach($plugins as $k => $v) {
				substr($k, 0, 7) == 'editor_' && $plugins[$k]['enable'] = 0;
			}
		}

		$plugins[$dir]['enable'] = 1;
		if($this->set_plugin_config($plugins)) {
			$this->clear_cache();

            //重新获取一次，更新缓存
            $this->runtime->xget();

            // hook admin_plugin_control_enable_success.php
			E(0, lang('enable_successfully'));
		}else{
			E(1, lang('write_config_failed'));
		}
	}

	// 插件停用
	public function disabled() {
        // hook admin_plugin_control_disabled_before.php
		$dir = R('dir', 'P');
		$this->check_plugin($dir);
		$plugins = $this->get_plugin_config();
		isset($plugins[$dir]) || E(1, lang('data_error'));

		$plugins[$dir]['enable'] = 0;
		if($this->set_plugin_config($plugins)) {
			$this->clear_cache();

            //重新获取一次，更新缓存
            $this->runtime->xget();

            // hook admin_plugin_control_disabled_success.php
			E(0, lang('disable_successfully'));
		}else{
			E(1, lang('write_config_failed'));
		}
	}

    //插件卸载，不会删除插件文件夹
    public function unstall(){
        $dir = R('dir', 'P');
        $this->check_plugin($dir);
        $plugins = $this->get_plugin_config();
        // 只允许卸载停用的插件
        if(isset($plugins[$dir]) && empty($plugins[$dir]['enable'])) {
            // 检测有 uninstall.php 文件，则执行卸载
            $uninstall = PLUGIN_PATH.$dir.'/uninstall.php';
            if(is_file($uninstall)) {
                include $uninstall;
            }

            unset($plugins[$dir]);
            if(!$this->set_plugin_config($plugins)) {
                E(1, lang('write_config_failed'));
            }
            $this->clear_cache();   //安装了，可能没有启用，但是也会写入一些配置信息，这里卸载的时候，再次执行删除缓存

            //重新获取一次，更新缓存
            $this->runtime->xget();

            E(0, lang('unstall_successfully'));
        }else{
            E(1, lang('opt_failed'));
        }
    }

	// 插件删除（240824增加了卸载操作，其实这里不需要再次执行卸载操作， 加上这个只是为了兼容之前的）
	public function delete() {
        // hook admin_plugin_control_delete_before.php
		$dir = R('dir', 'P');
		$this->check_plugin($dir);

		$plugins = $this->get_plugin_config();

		// 只允许删除停用或未安装的插件
		if(empty($plugins[$dir]['enable'])) {
			// 检测有 uninstall.php 文件，则执行卸载
			$uninstall = PLUGIN_PATH.$dir.'/uninstall.php';
			if(is_file($uninstall)) {
				include $uninstall;
			}

			if(_rmdir(PLUGIN_PATH.$dir)) {
				if(isset($plugins[$dir])) {
					unset($plugins[$dir]);
					if(!$this->set_plugin_config($plugins)) {
						E(1, lang('write_config_failed'));
					}
				}
                // hook admin_plugin_control_delete_success.php
				E(0, lang('delete_successfully'));
			}else{
				E(1, lang('delete_failed'));
			}
		}else{
			E(1, lang('delete_failed'));
		}
	}

	// 本地插件安装
	public function install() {
        // hook admin_plugin_control_install_before.php
		$dir = R('dir', 'P');
		$this->check_plugin($dir);

		$plugins = $this->get_plugin_config();
		isset($plugins[$dir]) && E(1, lang('plugin_is_installed'));

		$cms_version = $this->get_version($dir);
		$cms_version && version_compare($cms_version, C('version'), '>') && E(1, lang('plugin_version_failed').'：'.$cms_version);

		// 检测有 install.php 文件，则执行安装
		$install = PLUGIN_PATH.$dir.'/install.php';
		if(is_file($install)) include $install;

		$plugins[$dir] = array('enable' => 0);
		if(!$this->set_plugin_config($plugins)) E(1, lang('write_config_failed'));

        // hook admin_plugin_control_install_after.php

		E(0, lang('install_successfully'));
	}

	// 检查是否为合法的插件名
	private function check_plugin($dir) {
		if(empty($dir)) {
			E(1, lang('plugin_dir_no_empty'));
		}elseif(preg_match('/\W/', $dir)) {
			E(1, lang('plugin_dir_no_safe'));
		}elseif(!is_dir(PLUGIN_PATH.$dir)) {
			E(1, lang('plugin_dir_no_exists'));
		}
        // hook admin_plugin_control_check_plugin_after.php
	}

	// 检查版本
	private function get_version($dir) {
        // hook admin_plugin_control_get_version_before.php

		$cfg = is_file(PLUGIN_PATH.$dir.'/conf.php') ? (array)include(PLUGIN_PATH.$dir.'/conf.php') : array();
		return isset($cfg['cms_version']) ? $cfg['cms_version'] : 0;
	}

	// 获取插件配置信息
	private function get_plugin_config() {
        // hook admin_plugin_control_get_plugin_config_before.php

		return is_file(CONFIG_PATH.'plugin.inc.php') ? (array)include(CONFIG_PATH.'plugin.inc.php') : array();
	}

	// 设置插件配置信息
	private function set_plugin_config($plugins) {
		$file = CONFIG_PATH.'plugin.inc.php';
		!is_file($file) && _is_writable(dirname($file)) && file_put_contents($file, '');
		if(!_is_writable($file)) return FALSE;

        // hook admin_plugin_control_set_plugin_config_after.php
		return file_put_contents($file, "<?php\nreturn ".var_export($plugins, TRUE).";\n?>");
	}

    // hook admin_plugin_control_after.php
}
