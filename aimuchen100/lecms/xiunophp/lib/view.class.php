<?php
class view{
	private $vars = array();			//模板变量集合
	private $head_arr = array();		//模板头部代码数组

	public function __construct() {
		$_ENV['_theme'] = 'default';	//主题目录
		$_ENV['_view_diy'] = FALSE;		//DIY模板解析是否开启
	}

	public function assign($k, &$v) {
		$this->vars[$k] = &$v;
	}

	public function assign_value($k, $v) {
		$this->vars[$k] = $v;
	}

	// 注意: 为安全考虑，$filename 尽量限制为 (英文 数字 _ .)
	public function display($filename = null) {
		$_ENV['_tplname'] = is_null($filename) ? $_GET['control'].'_'.$_GET['action'].'.htm' : $filename;
		extract($this->vars, EXTR_SKIP);
        $tplfile = $this->get_tplfile($_ENV['_tplname']);
        if( is_file($tplfile) ){
            // hook view_display_before.php
            ob_start();
            include $tplfile;
            $html = ob_get_clean();
            // hook view_display_after.php（插件可在此对 $html 做运行时注入后 echo）
            // DEBUG=1 时框架源码直载，hook 标记注释不会像编译期那样被内联执行，
            // 需在此显式加载插件的同名 hook 文件（与编译内联行为对齐）
            if(DEBUG && is_dir(PLUGIN_PATH)) {
                $plugins = core::get_plugins();
                if(!empty($plugins['enable'])) {
                    $__hook_file = PLUGIN_PATH.key($plugins['enable']).'/hook/view_display_after.php';
                    foreach($plugins['enable'] as $__p => $__v) {
                        $__hook_file = PLUGIN_PATH.$__p.'/hook/view_display_after.php';
                        if(is_file($__hook_file)) { include $__hook_file; }
                    }
                }
                unset($__p, $__v, $__hook_file);
            }else{
                // hook view_display_echo.php
                echo $html;
            }
        }else{
            if( !DEBUG ){
                echo lang('tpl_file_not_exists', array('tplfile'=>$_ENV['_theme'].'/'.$filename));
                exit();
            }
        }
	}

	private function get_tplfile($filename) {
		$view_dir = APP_NAME.($_ENV['_view_diy'] ? '_view_diy' : '_view').'/';
		$php_file = RUNTIME_PATH.$view_dir.$_ENV['_theme'].','.$filename.'.php';

		if(!is_file($php_file) || DEBUG) {
			$tpl_file = core::get_original_file($filename, VIEW_PATH.$_ENV['_theme'].'/');

			if(!$tpl_file && DEBUG) {
                $msg = lang('tpl_file_not_exists', array('tplfile'=>$_ENV['_theme'].'/'.$filename));
				throw new Exception($msg);
			}

			if($tpl_file && FW($php_file, $this->tpl_process($tpl_file)) === false && DEBUG) {
                $msg = lang('write_tpl_file_failed', array('tplfile'=>$filename));
				throw new Exception($msg);
			}
		}

		return $php_file;
	}

	private function tpl_process($tpl_file) {
		//严格要求的变量和数组 $abc[a]['b']["c"][$d] 合法    $abc[$a[b]] 不合法
		$reg_arr = '[a-zA-Z_]\w*(?:\[\w+\]|\[\'\w+\'\]|\[\"\w+\"\]|\[\$[a-zA-Z_]\w*\])*';

		$s = file_get_contents($tpl_file);

		//第1步 包含inc模板
		$s = preg_replace_callback('#\{inc\:([\w|\/\.]+)\}#', array($this, 'process_inc'), $s);

		//第2步 解析模板hook
		$s = preg_replace_callback('#\{hook\:([\w\.]+)\}#', array('core', 'process_hook'), $s);

		//第3步 解析php代码
		$s = preg_replace('#(?:\<\?.*?\?\>|\<\?.*)#s', '', $s);	//清理掉PHP语法(目的统一规范)
		$s = preg_replace('#\{php\}(.*?)\{\/php\}#s', '<?php \\1 ?>', $s);
		//$s = preg_replace('#\{php\}.*?\{\/php\}#s', '', $s);	//特殊需求，不想让模板支持PHP代码

		//第4步 包含block
		$s = preg_replace_callback('#\{block\:([a-zA-Z_]\w*)\040?([^\n\}]*?)\}(.*?){\/block}#s', array($this, 'process_block'), $s);

		//第5步 解析loop
		while(preg_match('#\{loop\:\$'.$reg_arr.'(?:\040\$[a-zA-Z_]\w*){1,2}\}.*?\{\/loop\}#s', $s))
			$s = preg_replace_callback('#\{loop\:(\$'.$reg_arr.'(?:\040\$[a-zA-Z_]\w*){1,2})\}(.*?)\{\/loop\}#s', array($this, 'process_loop'), $s);

		//第6步 解析if (未考虑安全过滤)
		while(preg_match('#\{if\:[^\n\}]+\}.*?\{\/if\}#s', $s))
			$s = preg_replace_callback('#\{if\:([^\n\}]+)\}(.*?)\{\/if\}#s', array($this, 'process_if'), $s);

		//第7步 解析变量
		$s = preg_replace('#\{\@([^\}]+)\}#', '<?php echo(\\1); ?>', $s);	//用于运算时的输出 如 {@$k+2}
		$s = preg_replace_callback('#\{(\$'.$reg_arr.')\}#', array($this, 'process_vars'), $s);

		//第8步 解析语言包 {lang:xxx}
        $s = preg_replace_callback('#\{lang\:([\w\.]+)\}#', array($this, 'process_lang'), $s);

        if(defined('CODE_COMPRESS') && CODE_COMPRESS == 1 && DEBUG == 0) {  //add by dadadezhou zhoudada97@foxmail.com
            $s = str_replace(array("\r\n", "\n", "\t"), '', $s); // 压缩HTML代码
            $s = preg_replace("/\s(?=\s)/","\\1",$s); //去除多个空格并保留一个
            $s = str_replace("> <","><",$s);
        }

        if(isset($_ENV['_view_original']) && !empty($_ENV['_view_original']) ){
            $s = preg_replace_callback('/<(.*?) class=["\'](.*?)["\']>/i',array($this, 'process_view_original'), $s);
        }

		//第9步 组合模板代码
		$head_str = empty($this->head_arr) ? '' : implode("\r\n", $this->head_arr);
		$s = "<?php defined('APP_NAME') || exit('Access Denied'); $head_str\r\n?>$s";
		$s = str_replace('?><?php ', '', $s);

		return $s;
	}

	private function process_view_original($matches){
	    if(isset($matches[1]) && isset($matches[2])){
            $str1 = $matches[2];

            $num = isset($_ENV['_view_original']['css_class_length']) ? (int)$_ENV['_view_original']['css_class_length'] : 6;
            $fen = isset($_ENV['_view_original']['css_class_separator']) ? $_ENV['_view_original']['css_class_separator'] : '-';
            $tou = isset($_ENV['_view_original']['css_class_prefix']) ? $_ENV['_view_original']['css_class_prefix'].$fen : 'l'.'ecms'.$fen;

            $auth_key = $_ENV['_config']['auth_key'];
            if( isset($_ENV['_view_original']['css_class_type']) && intval($_ENV['_view_original']['css_class_type']) == 1 ){
                $m = substr(base64_encode(md5($str1.$auth_key)), 2, $num);
            }else{
                $m = substr(md5($str1.$auth_key), 2, $num);
            }

            return '<'.$matches[1].' class="'.$tou.$m.' '.$str1.'">';
        }
    }

	private function process_inc($matches) {
		// 注意：在可视化设计时需要排除前缀 inc- 的模板，所以不能去掉前缀
        if( strpos($matches[1], '/') == false ){
            $filename = 'inc-'.$matches[1];
        }else{
            // {inc:user/header.htm} 这种格式
            $arr = explode('/', $matches[1]);
            $filename = $arr[0].'/inc-'.$arr[1];
        }

		$tpl_file = core::get_original_file($filename, VIEW_PATH.$_ENV['_theme'].'/');

		if(!$tpl_file) {
		    if( DEBUG ){
                $msg = lang('tpl_file_not_exists', array('tplfile'=>$_ENV['_theme'].'/'.$filename));
                throw new Exception($msg);
            }else{
		        return '';
            }
		}
		return file_get_contents($tpl_file);
	}

    //模板里面调用 {lang:xxx}
    private function process_lang($matches){
        global $lang;
        empty($lang) && $lang = isset($_SERVER['lang']) ? $_SERVER['lang'] : array();

        if(isset($matches[1]) && $matches[1]){
            $language = lang($matches[1]);
        }else{
            $language = '';
        }

        return "<?php echo '".$language."'; ?>";
    }

	private function process_block($matches) {
		$func = $matches[1];
		$config = $matches[2];
		$s = $matches[3];

		$lib_file = core::get_original_file('block_'.$func.'.lib.php', BLOCK_PATH);
		if(!is_file($lib_file)) return '';

		//为减少IO，把需要用到的函数代码放到模板解析代码头部
		$lib_str = file_get_contents($lib_file);
		$lib_str = preg_replace_callback('#\t*\/\/\s*hook\s+([\w\.]+)[\r\n]#', array('core', 'process_hook'), $lib_str);
		if(!DEBUG) $lib_str = _strip_whitespace($lib_str);
		$lib_str = core::clear_code($lib_str);

		//先判断函数是否已经存在，避免重复引入
		if(!function_exists('block_'.$func)){
            $this->head_arr['block_'.$func] = $lib_str;
        }

		$s = $this->rep_double($s);
		$config = $this->rep_double($config);

		//解析设置数组并生成执行函数
		$config_arr = array();
		preg_match_all('#([a-zA-Z_]\w*)="(.*?)" #', $config.' ', $m);
		foreach($m[2] as $k=>$v) {
			if(isset($v)) $config_arr[strtolower($m[1][$k])] = addslashes($v);
		}
		unset($m);
		$func_str = 'block_'.$func.'('.var_export($config_arr, 1).');';

		//-----------定义转换后的首尾代码-----------
		$before = $after = '';
		//公共块移到模板解析代码头部
		if(substr($func, 0, 7) == 'global_') {
			$this->head_arr[$func] = '$gdata = '.$func_str;
		}else{
			$before .= '<?php $data = '.$func_str.' ?>';
			$after .= '<?php unset($data); ?>';
		}
		//DIY模板时才能用到
		if($_ENV['_view_diy']) {
			$this->block_id++;
			$before .= '<span block_diy="before" block_id="'.$this->block_id.'"></span>';
			$after .= '<span block_diy="after" block_id="'.$this->block_id.'"></span>';
		}
		return $before.$s.$after;
	}

	//严格要求格式 {loop:$arr[a] $v $k}
	private function process_loop($matches) {
		$args = explode(' ', $this->rep_double($matches[1]));
		$s = $this->rep_double($matches[2]);

		$arr = $this->rep_vars($args[0]);
		$v = empty($args[1]) ? '$v' : $args[1];
		$k = empty($args[2]) ? '' : $args[2].'=>';
		return "<?php if(isset($arr) && is_array($arr)) { foreach($arr as $k&$v) { ?>$s<?php }} ?>";
	}

	private function process_if($matches) {
		$expr = $this->rep_double($matches[1]);
		$expr = $this->rep_vars($expr);
		$s = preg_replace_callback('#\{elseif\:([^\n\}]+)\}#', array($this, 'rep_elseif'), $this->rep_double($matches[2]));
		$s = str_replace('{else}', '<?php }else{ ?>', $s);
		return "<?php if ($expr) { ?>$s<?php } ?>";
	}

	private function rep_elseif($matches) {
		$expr = $this->rep_double($matches[1]);
		$expr = $this->rep_vars($expr);
		return "<?php }elseif($expr) { ?>";
	}

	private function process_vars($matches) {
		$vars = $this->rep_double($matches[1]);
		$vars = $this->rep_vars($vars);
		return "<?php echo(isset($vars) ? $vars : ''); ?>";
	}

	//替换 " 号， 注意只能是 " 号
	private function rep_double($s) {
		return str_replace('\"', '"', $s);
	}

	//转$abc[a]['b']["c"][$d] 为 $abc['a']['b']['c'][$d]
	private function rep_vars($s) {
		$s = preg_replace('#\[(\w+)\]#', "['\\1']", $s);
		$s = preg_replace('#\[\"(\w+)\"\]#', "['\\1']", $s);
		$s = preg_replace('#\[\'(\d+)\'\]#', '[\\1]', $s);
		return $s;
	}
}
