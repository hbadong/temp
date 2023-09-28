<?php
defined('ROOT_PATH') or exit;

class theme_control extends admin_control {
	// 主题设置
	public function index() {
		// hook admin_theme_control_index_before.php

		$cfg = $this->runtime->xget('cfg');
		$k = &$cfg['theme'];
		$themes = self::get_theme_all();

		// 启用的主题放在第一
		if(isset($themes[$k])) {
			$tmp = array();
			$tmp[$k] = $themes[$k];
			unset($themes[$k]);
			$themes = $tmp + $themes;
		}

		$this->assign('themes', $themes);
		$this->assign('theme', $cfg['theme']);

		// hook admin_theme_control_index_after.php

		$this->display();
	}

	// 启用主题
	public function enable() {
        // hook admin_theme_control_enable_before.php
		$theme = R('theme', 'P');
		$this->check_theme($theme);

		$this->kv->xset('theme', $theme, 'cfg');
		$this->kv->save_changed();
		$this->runtime->delete('cfg');
		$this->clear_cache();

        // hook admin_theme_control_enable_after.php

		E(0, lang('opt_successfully'));
	}

	// 删除主题
	public function delete() {
        // hook admin_theme_control_delete_before.php
		$theme = R('theme', 'P');
		$this->check_theme($theme);

		if(_rmdir(ROOT_PATH.'view/'.$theme)) {
            // hook admin_theme_control_delete_success.php
			E(0, lang('delete_successfully'));
		}else{
			E(1, lang('delete_failed'));
		}
	}

	// 检查是否为合法的主题名以及是否依赖某功能才能安装
	private function check_theme($dir) {
        // hook admin_theme_control_check_theme_before.php
		if(empty($dir)) {
			E(1, lang('theme_dir_no_empty'));
		}elseif(preg_match('/\W/', $dir)) {
			E(1, lang('theme_dir_no_safe'));
		}elseif(!is_dir(ROOT_PATH.'view/'.$dir)) {
			E(1, lang('theme_dir_no_exists'));
		}

        // 检测有 enable.php 文件，有则执行
        $enable = ROOT_PATH.'view/'.$dir.'/enable.php';
        if(is_file($enable)) include $enable;

        // hook admin_theme_control_check_theme_after.php
	}

	// 读取所有主题
	private function get_theme_all() {
        // hook admin_theme_control_get_theme_all_before.php
		$dir = ROOT_PATH.'view/';
		$files = _scandir($dir);
		$themes = array();
		foreach($files as $file) {
			if(preg_match('/\W/', $file)) continue;
			$path = $dir.'/'.$file;
			$info = $path.'/info.ini';
			if(filetype($path) == 'dir' && is_file($info) && $lines = file($info)) {
				$themes[$file] = self::get_theme_info($lines);

                $img = $path.'/show.jpg';
                if(is_file($img)){
                    $themes[$file]['pic'] = "../view/{$file}/show.jpg";
                }else{
                    $themes[$file]['pic'] = "../static/admin/images/theme.jpg";
                }
			}
		}

        // hook admin_theme_control_get_theme_all_after.php
		return $themes;
	}

	// 读取主题信息
	private function get_theme_info($lines) {
		$res = array();
		foreach($lines as $str) {
			$arr = explode('=', trim($str));
			$k = trim($arr[0]);
			$v = isset($arr[1]) ? trim($arr[1]) : '';
			if($k == 'brief') {
				$res[$k] = strip_tags($v, '<br>');
			}elseif(in_array($k, array('name', 'version', 'update', 'author', 'authorurl'))) {
				$res[$k] = strip_tags($v);
			}
		}
        // hook admin_theme_control_get_theme_info_after.php
		return $res;
	}

	// hook admin_theme_control_after.php
}
