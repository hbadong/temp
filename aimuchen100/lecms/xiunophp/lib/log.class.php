<?php
class log {
	/**
	 * 写入日志
	 * @param string $s 写入字符串
	 * @param string $file 保存文件名
	 * @return boot
	 */
	public static function write($s, $file = 'php_error.php') {

		$file_key = substr($file, 0, -4);
		if( isset($_ENV['_config'][$file_key]) && (int)$_ENV['_config'][$file_key] ){
			$time = isset($_ENV['_time']) ? date('Y-m-d H:i:s', (int)$_ENV['_time']) : date('Y-m-d H:i:s');
			$ip = $_ENV['_ip'];
			$url = self::to_str($_SERVER['REQUEST_URI']);
			$s = self::to_str($s);
			self::write_log('<?php exit;?>'."$time  $ip  $url  $s\r\n", $file);
		}

		return TRUE;
	}

	/**
	 * 清理空白字符
	 * @param string $s 字符串
	 * @return string
	 */
	public static function to_str($s) {
		return str_replace(array("\r\n", "\r", "\n", "\t"), ' ', $s);
	}

	/**
	 * 文件末尾写入日志
	 * @param string $s 写入字符串
	 * @param string $file 保存文件名
	 * @return boot
	 */
	public static function write_log($s, $file) {
		$logfile = LOG_PATH.$file;
		try{
			$fp = fopen($logfile, 'ab+');
			if(!$fp) {
				throw new Exception("写入日志失败，可能文件 $file 不可写或磁盘已满。");
			}
			fwrite($fp, $s);
			fclose($fp);
		}catch(Exception $e) {}
		return TRUE;
	}

	/**
	 * 跟踪调试
	 * @param string $s 描述
	 */
	public static function trace($s) {
		if(!DEBUG) return;
		empty($_ENV['_trace']) && $_ENV['_trace'] = '';
		$_ENV['_trace'] .= $s.' - '.number_format(microtime(1) - $_ENV['_start_time'], 4)."\r\n";
	}

	/**
	 * 保存 trace
	 * @param string $file 保存文件名
	 */
	public static function trace_save($file = 'php_trace.php') {
		if(empty($_ENV['_trace'])) return;
		$s = "<?php exit;?>\r\n========================================================================\r\n";
		$s .= $_SERVER['REQUEST_URI']."\r\nPOST:".print_r($_POST, 1)."\r\nSQL:".print_r($_ENV['_sqls'], 1)."\r\n";
		$s .= $_ENV['_trace']."\r\n\r\n";
		self::write_log($s, $file);
	}

    // 日志记录
    public static function le_log($s, $file = 'error') {
        if(DEBUG == 0 && strpos($file, 'error') === FALSE) return;
        $time = $_ENV['_time'];
        $ip = $_ENV['_ip'];
        $day = date('Ym', $time); // 按照月存放，否则 Ymd 目录太多。
        $mtime = date('Y-m-d H:i:s'); // 默认值为 time()
        $url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        $logpath = LOG_PATH.'/'.$day;
        !is_dir($logpath) AND mkdir($logpath, 0777, true);

        if(is_array($s)){$s = serialize($s);}

        $s = str_replace(array("\r\n", "\n", "\t"), ' ', $s);
        $s = "<?php exit;?>\t$mtime\t$ip\t$url\t$s\r\n";

        @error_log($s, 3, $logpath."/$file.php");
    }
}
