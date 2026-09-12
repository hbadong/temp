<?php
// 统计程序运行时间
function runtime() {
	return number_format(microtime(1) - $_ENV['_start_time'], 4);
}

// 统计程序内存开销
function runmem() {
	return MEMORY_LIMIT_ON ? get_byte(memory_get_usage() - $_ENV['_start_memory']) : 'unknown';
}

// 安全获取IP
function ip() {
	if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		preg_match('#[\d\.]{7,15}#', $_SERVER['HTTP_X_FORWARDED_FOR'], $mat);
        if($mat){
            $ip = $mat[0];
        }else{
            return ip_c();
        }
	}elseif(isset($_SERVER['HTTP_CLIENT_IP'])) {
		$ip = $_SERVER['HTTP_CLIENT_IP'];
	}elseif(isset($_SERVER['REMOTE_ADDR'])) {
		$ip = $_SERVER['REMOTE_ADDR'];
        if($ip == '::1') {$ip='127.0.0.1';}
	}else{
        $ip = '127.0.0.1';
    }
	return long2ip(ip2long($ip));
}

// 不安全的获取 IP 方式，在开启 CDN 的时候，如果被人猜到真实 IP，则可以伪造。
function ip_c() {
    $ip = '127.0.0.1';
    if(isset($_SERVER['HTTP_CDN_SRC_IP'])) {
        $ip = $_SERVER['HTTP_CDN_SRC_IP'];
    } elseif(isset($_SERVER['HTTP_CLIENTIP'])) {
        $ip = $_SERVER['HTTP_CLIENTIP'];
    } elseif(isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        $arr = array_filter(explode(',', $ip));
        $ip = trim(end($arr));
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
        if($ip == '::1') {$ip='127.0.0.1';}
    }
    $longip = ip2long($ip);
    $longip < 0 AND $longip = sprintf("%u", $longip); // fix 32 位 OS 下溢出的问题
    return long2ip($longip);
}

// 返回消息JSON
function E($err, $msg, $name = '', $extra = array()) {
    $arr = array('err'=>$err, 'msg'=>$msg, 'name'=>$name);
    !empty($extra) && $arr += $extra;
    exit( _json_encode($arr) );
}

/**
 * 无Notice快捷取变量 (Request 的缩写)
 * @param string $k 键值
 * @param string $var 类型 GET|POST|COOKIE|REQUEST|SERVER
 * @return mixed
 */
function R($k, $var = 'G') {
	switch($var) {
		case 'G': $var = &$_GET; break;
		case 'P': $var = &$_POST; break;
		case 'C': $var = &$_COOKIE; break;
		case 'R': $var = isset($_GET[$k]) ? $_GET : (isset($_POST[$k]) ? $_POST : $_COOKIE); break;
		case 'S': $var = &$_SERVER; break;
	}
	return isset($var[$k]) ? $var[$k] : null;
}

//获取session
function _SESSION($k, $def = NULL) {
    global $g_session;
    return isset($_SESSION[$k]) ? $_SESSION[$k] : (isset($g_session[$k]) ? $g_session[$k] : $def);
}

//获取超级全局变量
function _GLOBALS($k, $def = NULL) { return isset($GLOBALS[$k]) ? $GLOBALS[$k] : $def; }

//获取服务器端环境变量的数组。它是PHP中一个超级全局变量
function _ENV($k, $def = NULL) { return isset($_ENV[$k]) ? $_ENV[$k] : $def; }

/*
 *仅支持一维数组的类型强制转换。
 *param_force($val);
*/
function param_force($val, $htmlspecialchars = TRUE, $addslashes = FALSE) {
    $get_magic_quotes_gpc = ini_set("magic_quotes_runtime",0) ? true:false;

    if(is_array($val)) {
        foreach($val as &$v) {
            if(is_string($v)) {
                $addslashes AND !$get_magic_quotes_gpc && $v = addslashes($v);
                !$addslashes AND $get_magic_quotes_gpc && $v = stripslashes($v);
                $htmlspecialchars AND $v = htmlspecialchars($v);
            } else {
                $v = intval($v);
            }
        }
    } else {
        if(is_string($val)) {
            $addslashes AND !$get_magic_quotes_gpc && $val = addslashes($val);
            !$addslashes AND $get_magic_quotes_gpc && $val = stripslashes($val);
            $htmlspecialchars AND $val = htmlspecialchars($val);
        } else {
            $val = intval($val);
        }
    }

    return $val;
}

/**
 * 读取/设置 配置信息 (Config 的缩写)
 * @param string $key 键值
 * @param string $val 设置值
 * @return mixed
 */
function C($key, $val = null) {
	if(is_null($val)) return isset($_ENV['_config'][$key]) ? $_ENV['_config'][$key] : $val;
	return $_ENV['_config'][$key] = $val;
}

/**
 * 具有递归自动创建文件夹和写入文件数据的功能 (File Write 的缩写)
 * @param string filename 要被写入数据的文件名
 * @param string $data 要写入的数据
 * @return boot
 */
function FW($filename, $data) {
	$dir = dirname($filename);
	// 目录不存在则创建
	is_dir($dir) || mkdir($dir, 0755, true);

	return file_put_contents($filename, $data);	// 不使用 LOCK_EX，多线程访问时会有同步问题
}

// cookie 设置/删除
function _setcookie($name, $value='', $expire=0, $path='', $domain='', $secure=false, $httponly=false, $samesite='') {
	$name = $_ENV['_config']['cookie_pre'].$name;
	if(!$path) $path = $_ENV['_config']['cookie_path'];
	if(!$domain) $domain = $_ENV['_config']['cookie_domain'];
	$_COOKIE[$name] = $value;
	// cookie_secure=true 时强制 Secure + SameSite=None：https 部署或跨站 iframe 预览环境，
	// 避免登录 cookie 被浏览器当第三方 cookie 拦截（服务端经反代转发无法感知 https）
	if($samesite === '' && !empty($_ENV['_config']['cookie_secure'])){
		$secure = true;
		$samesite = 'None';
	}
	if($samesite !== ''){
		return setcookie($name, $value, array(
			'expires' => $expire,
			'path' => $path,
			'domain' => $domain,
			'secure' => $secure,
			'httponly' => $httponly,
			'samesite' => $samesite,
		));
	}
	return setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
}

// 递归加反斜线
function _addslashes(&$var) {
	if(is_array($var)) {
		foreach($var as $k=>&$v) _addslashes($v);
	}else{
		$var = addslashes($var);
	}
}

// 递归清理反斜线
function _stripslashes(&$var) {
	if(is_array($var)) {
		foreach($var as $k=>&$v) _stripslashes($v);
	}else{
		$var = stripslashes($var);
	}
}

// 递归转换为HTML实体代码
function _htmls(&$var) {
	if(is_array($var)) {
		foreach($var as $k=>&$v) _htmls($v);
	}else{
		$var = htmlspecialchars($var);
	}
}

// 递归清理两端空白字符
function _trim(&$var) {
	if(is_array($var)) {
		foreach($var as $k=>&$v) _trim($v);
	}else{
		$var = trim($var);
	}
}

// 编码 URL 字符串
function _urlencode($s) {
	return str_replace('-', '%2D', urlencode($s));
}

// 数组转JSON
function _json_encode($data) {
    return json_encode($data, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
}
// 对 JSON 格式的字符串进行解码
function _json_decode($json) {
    $json = trim($json, "\xEF\xBB\xBF");
    $json = trim($json, "\xFE\xFF");
    return json_decode($json, 1);
}

// 增强多维数组进行排序，最多支持两个字段排序
function _array_multisort(&$data, $c_1, $c_2 = true, $a_1 = 1, $a_2 = 1) {
	if(!is_array($data)) return $data;

	$col_1 = $col_2 = array();
	foreach($data as $key => $row) {
		$col_1[$key] = $row[$c_1];
		$col_2[$key] = $c_2===true ? $key : $row[$c_2];
	}

	$asc_1 = $a_1 ? SORT_ASC : SORT_DESC;
	$asc_2 = $a_2 ? SORT_ASC : SORT_DESC;
	array_multisort($col_1, $asc_1, $col_2, $asc_2, $data);

	return $data;
}

// 返回安全整数
function _int(&$c, $k, $v = 0) {
	if(isset($c[$k])) {
		$i = intval($c[$k]);
		return $i ? $i : $v;
	}else{
		return $v;
	}
}

// 列出文件和目录
function _scandir($dir) {
	if(function_exists('scandir')) return scandir($dir);	// 有些服务器禁用了scandir
	$dh = opendir($dir);
	$arr = array();
	while($file = readdir($dh)) {
		if($file == '.' || $file == '..') continue;
		$arr[] = $file;
	}
	closedir($dh);
	return $arr;
}

// 递归删除目录
function _rmdir($dir, $keepdir = 0) {
	if(!is_dir($dir) || $dir == '/' || $dir == '../') return FALSE;	// 避免意外删除整站数据
	$files = _scandir($dir);
	foreach($files as $file) {
		if($file == '.' || $file == '..') continue;
		$filepath = $dir.'/'.$file;
		if(!is_dir($filepath)) {
			try{unlink($filepath);}catch(Exception $e){}
		}else{
			_rmdir($filepath);
		}
	}
	if(!$keepdir) try{rmdir($dir);}catch(Exception $e){}
	return TRUE;
}

// 检测文件或目录是否可写 (兼容 windows)
function _is_writable($file) {
	try{
		if(is_dir($file)) {
			$tmpfile = $file.'/_test.tmp';
			$n = @file_put_contents($tmpfile, 'test');
			if($n > 0) {
				unlink($tmpfile);
				return TRUE;
			}else{
				return FALSE;
			}
		}elseif(is_file($file)) {
			if(strpos(strtoupper(PHP_OS), 'WIN') !== FALSE) {
				$fp = @fopen($file, 'a'); // 写入方式打开，将文件指针指向文件末尾。如果文件不存在则尝试创建之。
				@fclose($fp);
				return (bool)$fp;
			}else{
				return is_writable($file);
			}
		}
	}catch(Exception $e) {}
	return FALSE;
}

// 清理PHP代码中的空格和注释
function _strip_whitespace($content) {
	$tokens = token_get_all($content);
	$last = FALSE;
	$s = '';
	for($i = 0, $j = count($tokens); $i < $j; $i++) {
		if(is_string($tokens[$i])) {
			$last = FALSE;
			$s .= $tokens[$i];
		}else{
			switch($tokens[$i][0]) {
				case T_COMMENT: //清理PHP注释
				case T_DOC_COMMENT:
					break;
				case T_WHITESPACE: //清理多余空格
					if(!$last) {
						$s .= ' ';
						$last = TRUE;
					}
					break;
				case T_START_HEREDOC:
					$s .= "<<<FRAMEWORK\n";
					break;
				case T_END_HEREDOC: // 修正 HEREDOC
					$s .= "FRAMEWORK;\n";
					for($k = $i+1; $k < $j; $k++) {
						if(is_string($tokens[$k]) && $tokens[$k] == ';') {
							$i = $k;
							break;
						}elseif($tokens[$k][0] == T_CLOSE_TAG) {
							break;
						}
					}
					break;
				default:
					$last = FALSE;
					$s .= $tokens[$i][1];
			}
		}
	}
	return $s;
}

//字符串长度
function _strlen($s) {
    return mb_strlen($s, 'UTF-8');
}

//字符串截取
function _substr($s, $start, $len) {
    return mb_substr($s, $start, $len, 'UTF-8');
}

//获取文件内容
function file_get_contents_try($file, $times = 3) {
    while($times-- > 0) {
        $fp = fopen($file, 'rb');
        if($fp) {
            $size = filesize($file);
            if($size == 0) return '';
            $s = fread($fp, $size);
            fclose($fp);
            return $s;
        } else {
            sleep(1);
        }
    }
    return FALSE;
}

//写入内容到文件
function file_put_contents_try($file, $s, $times = 3) {
    while($times-- > 0) {
        $fp = fopen($file, 'wb');
        if($fp AND flock($fp, LOCK_EX)){
            $n = fwrite($fp, $s);
            version_compare(PHP_VERSION, '5.3.2', '>=') AND flock($fp, LOCK_UN);
            fclose($fp);
            clearstatcache();
            return $n;
        } else {
            sleep(1);
        }
    }
    return FALSE;
}

/**
 * 产生随机字符串
 * @param int	$length	输出长度
 * @param int	$type	输出类型 1为数字 2为a1 3为Aa1
 * @param string	$chars	随机字符 可自定义
 * @return string
 */
function random($length, $type = 1, $chars = '0123456789abcdefghijklmnopqrstuvwxyz') {
	if($type == 1) {
		$hash = sprintf('%0'.$length.'d', mt_rand(0, pow(10, $length) - 1));
	} else {
		$hash = '';
		if($type == 3) $chars .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$max = strlen($chars) - 1;
		for($i = 0; $i < $length; $i++) $hash .= $chars[mt_rand(0, $max)];
	}
	return $hash;
}

/**
 * 获取数据大小单位
 * @param int $byte 字节
 * @return string
 */
function get_byte($byte) {
	if($byte < 1024) {
		return $byte.' Byte';
	}elseif($byte < 1048576) {
		return round($byte/1024, 2).' KB';
	}elseif($byte < 1073741824) {
		return round($byte/1048576, 2).' MB';
	}elseif($byte < 1099511627776) {
		return round($byte/1073741824, 2).' GB';
	}else{
		return round($byte/1099511627776, 2).' TB';
	}
}

// 转换为人性化时间
function human_date($dateline, $dateformat = 'Y-m-d H:i:s') {
	$second = $_ENV['_time'] - $dateline;
	if($second > 31536000) {
		return date($dateformat, $dateline);
	}elseif($second > 2592000) {
		return floor($second / 2592000).'月前';
	}elseif($second > 86400) {
		return floor($second / 86400).'天前';
	}elseif($second > 3600) {
		return floor($second / 3600).'小时前';
	}elseif($second > 60) {
		return floor($second / 60).'分钟前';
	}else{
		return $second.'秒前';
	}
}

// 安全过滤 (过滤非空格、英文、数字、下划线、中文、日文、朝鲜文，其他语言通过 $ext 添加 Unicode 编码)
// 4E00-9FA5(中文)  30A0-30FF(日文片假名) 3040-309F(日文平假名) 1100-11FF(朝鲜文) 3130-318F(朝鲜文兼容字母) AC00-D7AF(朝鲜文音节)
function safe_str($s, $ext = '') {
	$ext = preg_quote($ext);
	$s = preg_replace('#[^\040\w\x{4E00}-\x{9FA5}\x{30A0}-\x{30FF}\x{3040}-\x{309F}\x{1100}-\x{11FF}\x{3130}-\x{318F}\x{AC00}-\x{D7AF}'.$ext.']+#u', '', $s);
	$s = trim($s);
	return $s;
}

// 获取下级所有目录名 （严格限制目录名只能是 数字 字母 _）
function get_dirs($path, $fullpath = false) {
	$arr = array();
	$dh = opendir($path);
	while($dir = readdir($dh)) {
		if(preg_match('#\W#', $dir) || !is_dir($path.$dir)) continue;
		$arr[] = $fullpath ? $path.$dir.'/' : $dir;
	}
	sort($arr); // 排序方式:目录名升序
	return $arr;
}

/**
 * 字符串只替换一次
 * @param string $search 查找的字符串
 * @param string $replace 替换的字符串
 * @param string $content 执行替换的字符串
 * @return string
 */
function str_replace_once($search, $replace, $content) {
	$pos = strpos($content, $search);
	if($pos === false) return $content;
	return substr_replace($content, $replace, $pos, strlen($search));
}

/**
 * 字符串加密、解密函数
 * @param string $string	字符串
 * @param string $operation	ENCODE为加密，DECODE为解密，可选参数，默认为ENCODE
 * @param string $key		密钥：数字、字母、下划线
 * @param string $expiry	过期时间
 * @return string
 */
function str_auth($string, $operation = 'DECODE', $key = '', $expiry = 0) {
	$ckey_length = 4;
	$key = md5($key != '' ? $key : C('auth_key'));
	$keya = md5(substr($key, 0, 16));
	$keyb = md5(substr($key, 16, 16));
	$keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';

	$cryptkey = $keya.md5($keya.$keyc);
	$key_length = strlen($cryptkey);

	$string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
	$string_length = strlen($string);

	$result = '';
	$box = range(0, 255);

	$rndkey = array();
	for($i = 0; $i <= 255; $i++) {
		$rndkey[$i] = ord($cryptkey[$i % $key_length]);
	}

	for($j = $i = 0; $i < 256; $i++) {
		$j = ($j + $box[$i] + $rndkey[$i]) % 256;
		$tmp = $box[$i];
		$box[$i] = $box[$j];
		$box[$j] = $tmp;
	}

	for($a = $j = $i = 0; $i < $string_length; $i++) {
		$a = ($a + 1) % 256;
		$j = ($j + $box[$a]) % 256;
		$tmp = $box[$a];
		$box[$a] = $box[$j];
		$box[$j] = $tmp;
		$result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
	}

	if($operation == 'DECODE') {
		if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
			return substr($result, 26);
		}else{
			return '';
		}
	}else{
		return $keyc.str_replace('=', '', base64_encode($result));
	}
}

// 生成 form hash
function form_hash() {
	return substr(md5(substr($_ENV['_time'], 0, -5).$_ENV['_config']['auth_key']), 16);
}

// 校验 form hash
function form_submit() {
	return R('FORM_HASH', 'P') == form_hash();
}

// 远程抓取数据
function fetch_url($url, $timeout = 30) {
	$opts = array ('http'=>array('method'=>'GET', 'timeout'=>$timeout));
	$context = stream_context_create($opts);
	$html = file_get_contents($url, false, $context);
	return $html;
}

//http or https
function http() {
    if ( !empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
        return 'https://';
    } elseif ( isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' ) {
        return 'https://';
    } elseif ( !empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off') {
        return 'https://';
    }
    return 'http://';
}

//格式化文件大小
function humansize($num) {
    static $custom_humansize = NULL;
    if($custom_humansize === NULL) $custom_humansize = function_exists('custom_humansize');
    if($custom_humansize) return custom_humansize($num);

    if($num > 1073741824) {
        return number_format($num / 1073741824, 2, '.', '').'G';
    } elseif($num > 1048576) {
        return number_format($num / 1048576, 2, '.', '').'M';
    } elseif($num > 1024) {
        return number_format($num / 1024, 2, '.', '').'K';
    } else {
        return $num.'B';
    }
}

//跳转URL
function http_location($url = '', $code = '') {
    if(empty($url)){
        exit('URL is empty.');
    }

    if ($code == '301'){
        header('HTTP/1.1 301 Moved Permanently');
    }elseif ($code == '302'){
        header('HTTP/1.1 302 Moved Permanently');
    }elseif($code == '404'){
        header('HTTP/1.1 404 Not Found');
    }

    header('Location:'.$url);
    exit;
}

// 判断是否为手机访问 或者是wap域名
function is_mobile($waphost = 1) {

    //判断是不是移动端域名 wap. 或者 m.
    if($waphost && (substr($_SERVER['HTTP_HOST'],0,4) == 'wap.' || substr($_SERVER['HTTP_HOST'],0,2) == 'm.')){
        return 1;
    }

    if ( isset($_SERVER['HTTP_VIA']) && stristr($_SERVER['HTTP_VIA'], "wap") ){
        return 1;
    } elseif ( isset($_SERVER['HTTP_ACCEPT']) && strpos(strtoupper($_SERVER['HTTP_ACCEPT']), "VND.WAP.WML") ){
        return 1;
    } elseif ( isset($_SERVER['HTTP_X_WAP_PROFILE']) && !empty($_SERVER['HTTP_X_WAP_PROFILE']) ) {
        return 1;
    } elseif ( isset($_SERVER['HTTP_PROFILE']) && !empty($_SERVER['HTTP_PROFILE']) ) {
        return 1;
    }

    $browser = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    if ($browser && preg_match('/(blackberry|configuration\/cldc|hp |hp-|htc |htc_|htc-|iemobile|kindle|midp|mmp|motorola|mobile|nokia|opera mini|opera |Googlebot-Mobile|YahooSeeker\/M1A1-R2D2|android|iphone|ipod|mobi|palm|palmos|pocket|portalmmm|ppc;|smartphone|sonyericsson|sqh|spv|symbian|treo|up.browser|up.link|vodafone|windows ce|xda |xda_)/i', $browser)) {
        return 1;
    }

    $mobile_agents = array(
        'iphone','ipod','android','samsung','sony','meizu','ericsson','mot','htc','sgh','lg','sharp','sie-',
        'philips','panasonic','alcatel','lenovo','blackberry','netfront','symbian','ucweb','windowsce',
        'palm','operamini','operamobi','openwave','nexusone','cldc','midp','wap','mobile',

    );
    $is_mobile = 0;
    foreach($mobile_agents as $agent) {
        if(stripos($browser, $agent) !== false) {
            $is_mobile = 1;
            break;
        }
    }
    return $is_mobile;
}

//判断是不是搜索引擎
function isSpider() {
    $agent= isset($_SERVER['HTTP_USER_AGENT']) ? strtolower($_SERVER['HTTP_USER_AGENT']) : '';
    if (!empty($agent)) {
        //手百UA  baiduboxapp
        //yisouspider 神马
        //Sogou 搜狗有多个，统一使用Sogou
        $spiderSite= array(
            "Baiduspider",
            "baiduboxapp",
            "Googlebot",
            "Sogou",
            "360Spider",
            "Bytespider",
            "HaosouSpider",
            "bing",
            "yisouspider",
            "soso"
        );
        //从配置文件获取
        $spider_user_agent = C('spider_user_agent');
        if($spider_user_agent){
            $spiderSite = explode(',', $spider_user_agent);
        }

        foreach($spiderSite as $val) {
            $str = strtolower($val);
            if (strpos($agent, $str) !== false) {
                return $str;
            }
        }
        return false;
    } else {
        return false;
    }
}

// 获取用户来路，要排除的url参数数组
function user_http_referer($no = array(), $weburl = ''){
    $referer = R('referer'); // 优先从参数获取
    empty($referer) AND $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
    $referer = str_replace(array('\"', '"', '<', '>', ' ', '*', "\t", "\r", "\n"), '', $referer); // 干掉特殊字符 strip special chars
    
	//排除登录 注册 退出 忘记密码 重置密码 来源页
	if(
        !preg_match('#^(http|https)://[\w\-=/\.]+/[\w\-=.%\#?]*$#is', $referer)
        || strpos($referer, 'login') !== FALSE
        || strpos($referer, 'register') !== FALSE
        || strpos($referer, 'logout') !== FALSE
        || strpos($referer, 'forget') !== FALSE
        || strpos($referer, 'resetpwd') !== FALSE
    ) {
        $referer = './';
    }

	if($no){
	    foreach ($no as $u){
	        if(strpos($referer, $u) !== FALSE){
                $referer = './';
                break;
            }
        }
    }

    if( $referer == './' && !empty($weburl) ){
        $referer = $weburl;
    }elseif( stripos($referer, $weburl) === FALSE ){    //排除站外来路，一般用于第三方登录
        $referer = $weburl;
    }

    return $referer;
}

// 获取 http://xxx.com/path/
function http_url_path() {
    $port = R('SERVER_PORT','S');
    $host = R('HTTP_HOST','S');  // host 里包含 port
    $https = R('HTTPS','S') == null ? 'off' : strtolower(R('HTTPS'));
    $proto = strtolower(R('HTTP_X_FORWARDED_PROTO','S'));
    $path = substr($_SERVER['PHP_SELF'], 0, strrpos($_SERVER['PHP_SELF'], '/'));
    $http = (($port == 443) || $proto == 'https' || ($https && $https != 'off')) ? 'https' : 'http';
    return  "$http://$host$path/";
}

function url($url, $extra = array()) {
    !isset($_ENV['_config']['lecms_parseurl']) AND $_ENV['_config']['lecms_parseurl'] = 0;

    $r = $path = $query = '';
    if(strpos($url, '/') !== FALSE) {
        $path = substr($url, 0, strrpos($url, '/') + 1);
        $query = substr($url, strrpos($url, '/') + 1);
    } else {
        $path = '/';
        $query = $url;
    }

    if($_ENV['_config']['lecms_parseurl'] == 0) {
        $query = str_replace('_','-',$query);
        $r = $path . 'index.php?' . $query;
    } else {
        $query = str_replace('-','_',$query);
        $r = $path . $query. C('url_suffix');
    }
    // 附加参数
    if($extra) {
        $args = http_build_query($extra);
        $sep = strpos($r, '?') === FALSE ? '?' : '&';
        $r .= $sep.$args;
    }

    return $r;
}

// 分割SQL语句
function sql_split($sql, $tablepre) {
    $sql = str_replace('pre_', $tablepre, $sql);
    $sql = str_replace("\r", '', $sql);
    $ret = array();
    $num = 0;
    $queriesarray = explode(";\n", trim($sql));
    unset($sql);
    foreach($queriesarray as $query) {
        $ret[$num] = isset($ret[$num]) ? $ret[$num] : '';
        $queries = explode("\n", trim($query));
        foreach($queries as $query) {
            $ret[$num] .= isset($query[0]) && $query[0] == "#" ? '' : trim(preg_replace('/\#.*/', '', $query));
        }
        $num++;
    }
    return $ret;
}

//自动生成摘要和摘要处理
function auto_intro($intro = '', $content = '', $len = 255) {
    $s_arr = array('&nbsp;', '&amp;', '&quot;', '&#039;', '&ldquo;', '&rdquo;', '&mdash;', '&lt;', '&gt;', '&middot;', '&hellip;');
    $r_arr = array(' ', '&', '"', "'", '“', '”', '—', '<', '>', '·', '…');
    if(empty($intro)) {
        $content = str_replace($s_arr, $r_arr, $content);
        $intro = preg_replace('/\s{2,}/', ' ', strip_tags($content));
        return trim(utf8::cutstr_cn($intro, $len, ''));
    }else{
        $intro = str_replace($s_arr, $r_arr, $intro);
        return str_replace(array("\r\n", "\r", "\n"), '<br />', strip_tags($intro));
    }
}

// curl 和 file_get_contents 封装
function _file_get_contents($url, $timeout=10, $referer='', $post_data=''){
    if(function_exists('curl_init')){
        $ch = curl_init();
        curl_setopt ($ch, CURLOPT_URL, $url);
        curl_setopt ($ch, CURLOPT_HEADER, 0);
        curl_setopt ($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $referer AND curl_setopt ($ch, CURLOPT_REFERER, $referer);
        curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1);
        //post
        if($post_data){
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        }
        //https
        $http = parse_url($url);
        if(isset($http['scheme']) && $http['scheme'] == 'https'){
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        }
        $content = curl_exec($ch);
        curl_close($ch);
        if($content){
            return $content;
        }
    }
    $ctx = stream_context_create(array('http'=>array('timeout'=>$timeout)));
    $content = @file_get_contents($url, 0, $ctx);
    if($content){
        return $content;
    }
    return false;
}

//页面显示 中文网，源代码显示&#20013;&#25991;&#32593; ， 后面两个参数不能改动
function unicode_encode($str, $encoding = 'UTF-8', $prefix = '&#', $postfix = ';') {
    $str = iconv($encoding, 'UCS-2', $str);
    $arrstr = str_split($str, 2);
    $unistr = '';
    for($i = 0, $len = count($arrstr); $i < $len; $i++) {
        $dec = hexdec(bin2hex($arrstr[$i]));
        $unistr .= $prefix . $dec . $postfix;
    }
    return $unistr;
}

/**
 * 判断某个插件是否开启
 * @param string $plugin_dir 插件文件夹名
 * @param int $force 强制重新获取
 * @return boolean
 */
function plugin_is_enable($plugin_dir = '',$force = 0){
    static $plugins = array();
    if(!empty($plugins) && !$force){
        if( !isset($plugin_arr[$plugin_dir]) ){ //未安装的插件
            return false;
        }elseif ( empty($plugin_arr[$plugin_dir]['enable']) ){  //已安装未启用的插件
            return false;
        }else{
            return true;
        }
    }

    if(!is_dir(PLUGIN_PATH)) return false;

    $plugin_arr = is_file(CONFIG_PATH.'plugin.inc.php') ? (array)include(CONFIG_PATH.'plugin.inc.php') : array();

    if(empty($plugin_arr)){
        return false;
    }

    if( !isset($plugin_arr[$plugin_dir]) ){ //未安装的插件
        return false;
    }elseif ( empty($plugin_arr[$plugin_dir]['enable']) ){  //已安装未启用的插件
        return false;
    }else{
        return true;
    }
}

//获取语言包语言
function lang($key, $arr = array()) {
    $lang = isset($_SERVER['lang']) ? $_SERVER['lang'] : array();
    if(!isset($lang[$key])) return 'lang['.$key.']';
    $s = $lang[$key];
    if(!empty($arr)) {
        foreach($arr as $k=>$v) {
            $s = str_replace('{'.$k.'}', $v, $s);
        }
    }
    return $s;
}

//兼容一下已使用的pages_bootstrap分页函数
function pages_bootstrap($page = 1, $maxpage = 0, $url = '', $offset = 5, $lang = array('&#171;', '&#187;')){
    return paginator::pages_bootstrap($page, $maxpage, $url, $offset, $lang);
}
//兼容一下已使用的pages分页函数
function pages($page = 1, $maxpage = 0, $url = '', $offset = 5, $lang = array('&#171;', '&#187;')){
    return paginator::pages($page, $maxpage, $url, $offset, $lang);
}

//加密解密函数 start，密文是字母和数字组合。目前只在内容url加密型用到了--------------------
//加密
function encrypt($str = '', $key = '') {
    $key = $key == '' ? C('auth_key') : $key;
    $md5str = preg_replace('|[0-9/]+|','',md5($key));
    $key = substr($md5str, 0, 2);
    $texlen = strlen($str);
    $rand_key = md5($key);
    $reslutstr = "";
    for ($i = 0; $i < $texlen; $i++) {
        $reslutstr .= $str[$i] ^ $rand_key[$i % 32];
    }
    $reslutstr = trim(base64_encode($reslutstr), "==");
    $reslutstr = $key.substr(md5($reslutstr), 0, 3) . $reslutstr;
    return $reslutstr;
}

//解密
function decrypt($str = '') {
    $key = substr($str, 0, 2);
    $str = substr($str, 2);
    $verity_str = substr($str, 0, 3);
    $str = substr($str, 3);
    if ($verity_str != substr(md5($str), 0, 3)) {
        //完整性验证失败
        return false;
    }
    $str = base64_decode($str);
    $texlen = strlen($str);
    $reslutstr = "";
    $rand_key = md5($key);
    for($i = 0; $i < $texlen; $i++) {
        $reslutstr .= $str[$i] ^ $rand_key[$i % 32];
    }
    return $reslutstr;
}
//HashID 内容和标签URL 加密解密 ， 标签是 mid , tagid
function hashids_encrypt($cid = 0, $id = 0){
    $hashids = Hashids::instance(C('auth_key'));
    return $hashids->encode($cid, $id);
}

function hashids_decrypt($str = ''){
    $hashids = Hashids::instance(C('auth_key'));
    return $hashids->decode($str);
}

//加密解密函数 end

//判断启用主题下的模板是否存在
function view_tpl_exists($tpl = ''){
    if(file_exists(VIEW_PATH.$_ENV['_theme'].'/'.$tpl)){
        return $tpl;
    }else{
        return false;
    }
}

// txt 转换到 html
function xn_txt_to_html($s) {
    $s = htmlspecialchars($s);
    $s = str_replace(" ", '&nbsp;', $s);
    $s = str_replace("\t", ' &nbsp; &nbsp; &nbsp; &nbsp;', $s);
    $s = str_replace("\r\n", "\n", $s);
    $s = str_replace("\n", '<br>', $s);
    return $s;
}

// 判断一个字符串是否在另外一个字符串里面，分隔符 ,
function xn_in_string($s, $str) {
    if(!$s || !$str) return FALSE;
    $s = ",$s,";
    $str = ",$str,";
    return strpos($str, $s) !== FALSE;
}

// 创建目录
function xn_mkdir($dir, $mod = NULL, $recusive = NULL) {
    $r = !is_dir($dir) ? mkdir($dir, $mod, $recusive) : FALSE;
    return $r;
}

// 删除一个空的目录，但是不能删除非空的目录
function xn_rmdir($dir) {
    $r = is_dir($dir) ? rmdir($dir) : FALSE;
    return $r;
}

// 删除文件
function xn_unlink($file) {
    $r = is_file($file) ? unlink($file) : FALSE;
    return $r;
}

// 获取文件的最后修改时间
function xn_filemtime($file) {
    return is_file($file) ? filemtime($file) : 0;
}

/*
    实例：
    xn_set_dir(123, ROOT_PATH.'upload');

    000/000/1.jpg
    000/000/100.jpg
    000/000/100.jpg
    000/000/999.jpg
    000/001/1000.jpg
    000/001/001.jpg
    000/002/001.jpg
*/
function xn_set_dir($id, $dir = './') {

    $id = sprintf("%09d", $id);
    $s1 = substr($id, 0, 3);
    $s2 = substr($id, 3, 3);
    $dir1 = $dir.$s1;
    $dir2 = $dir."$s1/$s2";

    !is_dir($dir1) && mkdir($dir1, 0777);
    !is_dir($dir2) && mkdir($dir2, 0777);
    return "$s1/$s2";
}

// 取得路径：001/123
function xn_get_dir($id) {
    $id = sprintf("%09d", $id);
    $s1 = substr($id, 0, 3);
    $s2 = substr($id, 3, 3);
    return "$s1/$s2";
}

function xn_urlencode($s) {
    $s = urlencode($s);
    $s = str_replace('_', '_5f', $s);
    $s = str_replace('-', '_2d', $s);
    $s = str_replace('.', '_2e', $s);
    $s = str_replace('+', '_2b', $s);
    $s = str_replace('=', '_3d', $s);
    $s = str_replace('%', '_', $s);
    return $s;
}

function xn_urldecode($s) {
    $s = str_replace('_', '%', $s);
    $s = urldecode($s);
    return $s;
}

/**
 * 获取内容中的图片地址
 * @param string $content 内容
 * @param boolean $return_img_str 是否返回图片字符串
 * @return array
 */
function match_img($content = '', $return_img_str = false){
    $pattern = "/<[img|IMG].*?src=[\'|\"](.*?(?:[\.gif|\.jpg|\.png|\.jpeg|\.bmp|\.webp]))[\'|\"].*?[\/]?>/";
    preg_match_all($pattern, $content, $match);

    if(isset($match[1]) && !empty($match[1])){
        $imgurl = $match[1];
    }else{
        $imgurl = array();
    }

    if($return_img_str){
        if(isset($match[0]) && !empty($match[0])){
            $imgs = $match[0];
        }else{
            $imgs = array();
        }
    }else{
        $imgs = array();
    }

    if($return_img_str){
        return array('imgurl'=>$imgurl, 'imgs'=>$imgs);
    }else{
        return $imgurl;
    }
}

/**
 * 获取远程图片并把它保存到本地, 确定您有把文件写入本地服务器的权限
 * @param string $content 内容
 * @param string $path 存储相对路径
 * @param string $targeturl 可选参数，对方网站的网址，防止对方网站的图片使用"/upload/1.jpg"这种相对图片地址
 * @return string $content 处理后的内容
 */
function grab_remote_img($content = '', $path = '', $targeturl = ''){
    set_time_limit(0);
    $img_array = match_img($content);
    if(!$img_array){
        return $content;
    }

    empty($path) AND $path = date('Ymd');
    $uploadpath = 'upload/'.$path;

    $updir = ROOT_PATH.$uploadpath; //文件存储绝对路径
    $weburl = http_url_path();
    $urlpath = $weburl.$uploadpath; //文件存储网址

    if(!is_dir($updir) && !mkdir($updir, 0755, true)) {
        return $content;
    }

    foreach($img_array as $value){
        $val = $value;
        //匹配到的是相对图片地址，就需要补充完整
        if(strpos($value, 'http') === false){
            if(!$targeturl) continue;
            $value = $targeturl.$value;
        }

        if(strpos($value, '?')){
            $value = explode('?', $value);
            $value = $value[0];
        }

        if(substr($value, 0, 4) != 'http'){
            continue;
        }elseif (stripos($value, $weburl) !== FALSE){   //排除站内
            continue;
        }

        //简单的获取扩展名
        $ext = strtolower(trim(substr(strrchr($value, '.'), 1, 10)));
        if(!in_array(strtolower($ext), array('png', 'jpg', 'jpeg', 'gif', 'webp', 'bmp', 'ico'))){
            continue;
        }

        $imgname = date('YmdHis').rand(100, 999).'.'.$ext;
        $filename = $updir.'/'.$imgname;
        $imgurl = $urlpath.'/'.$imgname;

        ob_start();
        @readfile($value);
        $data = ob_get_contents();
        ob_end_clean();
        $data && file_put_contents($filename, $data);

        if(is_file($filename)){
            $content = str_replace($val, $imgurl, $content);
        }
    }
    return $content;
}

/**
 * 发送HTTP状态
 * @param integer $code 状态码
 * @return void
 */
function send_http_status($code){
    static $_status = array(
        // Informational 1xx
        100 => 'Continue',
        101 => 'Switching Protocols',
        // Success 2xx
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        // Redirection 3xx
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Moved Temporarily ',  // 1.1
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        // 306 is deprecated but reserved
        307 => 'Temporary Redirect',
        // Client Error 4xx
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        // Server Error 5xx
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        509 => 'Bandwidth Limit Exceeded',
        550 => 'Can not connect to MySQL server'
    );
    if(isset($_status[$code])) {
        header('HTTP/1.1 '.$code.' '.$_status[$code]);
        // 确保FastCGI模式下正常
        header('Status:'.$code.' '.$_status[$code]);
    }
}

// 不区分大小写的in_array实现
function in_array_case($value = '', $array = array()){
    return in_array(strtolower($value), array_map('strtolower', $array));
}

/**
 * 根据PHP各种类型变量生成唯一标识号
 * @param mixed $mix 变量
 * @return string
 */
function to_guid_string($mix) {
    if (is_object($mix)) {
        return spl_object_hash($mix);
    } elseif (is_resource($mix)) {
        $mix = get_resource_type($mix) . strval($mix);
    } else {
        $mix = serialize($mix);
    }
    return md5($mix);
}

/**
 * URL重定向
 * @param string $url 重定向的URL地址
 * @param integer $time 重定向的等待时间（秒）
 * @param string $msg 重定向前的提示信息
 * @return void
 */
function redirect($url, $time = 0, $msg = '') {
    //多行URL地址支持
    $url        = str_replace(array("\n", "\r"), '', $url);
    if (empty($msg))
        $msg    = "系统将在{$time}秒之后自动跳转到{$url}！";
    if (!headers_sent()) {
        if (0 === $time) {
            header('Location: ' . $url);
        } else {
            header("refresh:{$time};url={$url}");
            echo($msg);
        }
        exit();
    } else {
        $str    = "<meta http-equiv='Refresh' content='{$time};URL={$url}'>";
        if ($time != 0)
            $str .= $msg;
        exit($str);
    }
}

// 自动转换字符集 支持数组转换
function auto_charset($fContents, $from = 'gbk', $to = 'utf-8') {
    $from = strtoupper($from) == 'UTF8' ? 'utf-8' : $from;
    $to = strtoupper($to) == 'UTF8' ? 'utf-8' : $to;
    if (strtoupper($from) === strtoupper($to) || empty($fContents) || (is_scalar($fContents) && !is_string($fContents))) {
        //如果编码相同或者非字符串标量则不转换
        return $fContents;
    }
    if (is_string($fContents)) {
        if (function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($fContents, $to, $from);
        } elseif (function_exists('iconv')) {
            return iconv($from, $to, $fContents);
        } else {
            return $fContents;
        }
    } elseif (is_array($fContents)) {
        foreach ($fContents as $key => $val) {
            $_key = auto_charset($key, $from, $to);
            $fContents[$_key] = auto_charset($val, $from, $to);
            if ($key != $_key)
                unset($fContents[$key]);
        }
        return $fContents;
    }
    else {
        return $fContents;
    }
}