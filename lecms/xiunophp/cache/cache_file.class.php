<?php
defined('FRAMEWORK_PATH') || exit;
class cache_file implements cache_interface{
	private $conf;
	private $is_getmulti = FALSE;	//是否支持 getMulti 方法
	public $pre;	//缓存前缀 （防止同一台缓存服务器，有多套程序，键名冲突问题）

	public $options = array(
		'expire'        => 0,
		'cache_subdir'  => true,
		'prefix'        => '',
		'path'          => '',
		'data_compress' => false,
	);
	protected $expire;

	public function __construct(&$conf) {
		$this->conf = &$conf;
		$this->options['path'] = RUNTIME_PATH.'filecache';
		$this->conf = array_merge($this->options, $this->conf);
		$this->pre = $conf['pre'];

		if (substr($this->conf['path'], -1) != DS) {
			$this->conf['path'] .= DS;
		}
		$this->init();
	}

	public function __get($var) {

	}

	/**
	 * 初始化检查
	 * @access private
	 * @return boolean
	 */
	private function init(){
		// 创建项目缓存目录
		if (!is_dir($this->conf['path'])) {
			if (mkdir($this->conf['path'], 0755, true)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * 取得变量的存储文件名
	 * @access protected
	 * @param  string $name 缓存变量名
	 * @param  bool   $auto 是否自动创建目录
	 * @return string
	 */
	protected function getCacheKey($name, $auto = false){
		$name = md5($name);
		if ($this->conf['cache_subdir']) {
			// 使用子目录
			$name = substr($name, 0, 2) . DS . substr($name, 2);
		}
		if ($this->pre) {
			$name = $this->pre . DS . $name;
		}
		$filename = $this->conf['path'] . $name . '.php';
		$dir      = dirname($filename);

		if ($auto && !is_dir($dir)) {
			mkdir($dir, 0755, true);
		}
		return $filename;
	}

	/**
	 * 判断缓存是否存在
	 * @access public
	 * @param string $name 缓存变量名
	 * @return bool
	 */
	public function has($name){
		return $this->get($name) ? true : false;
	}

	/**
	 * 读取一条数据
	 * @param string $key	键名
	 * @return array
	 */
	public function get($key) {
		$filename = $this->getCacheKey($key);
		if (!is_file($filename)) {
			return false;
		}
		$content      = file_get_contents($filename);
		$this->expire = null;
		if (false !== $content) {
			$expire = (int) substr($content, 8, 12);
			if (0 != $expire && time() > filemtime($filename) + $expire) {
				return false;
			}
			$this->expire = $expire;
			$content      = substr($content, 32);
			if ($this->conf['data_compress'] && function_exists('gzcompress')) {
				//启用数据压缩
				$content = gzuncompress($content);
			}
			$content = unserialize($content);
			return $content;
		}else{
			return false;
		}
	}

	/**
	 * 读取多条数据
	 * @param array $keys	键名数组
	 * @return array
	 */
	public function multi_get($keys) {
		$data = array();
		foreach($keys as $k) {
			$arr = $this->get($k);
			if(empty($arr)) {
				$data[$k] = FALSE;
			}else{
				$data[$k] = $arr;
			}
		}
		return $data;
	}

	/**
	 * 写入一条数据
	 * @param string $key	键名
	 * @param array $data	数据
	 * @param int  $life	缓存时间 (默认为永久)
	 * @return bool
	 */
	public function set($key, $data, $life = 0) {
		if (is_null($life)) {
			$life = $this->conf['expire'];
		}
		if ($life instanceof \DateTime) {
			$life = $life->getTimestamp() - time();
		}
		$filename = $this->getCacheKey($key, true);
		if ($this->tag && !is_file($filename)) {
			$first = true;
		}
		$data = serialize($data);
		if ($this->conf['data_compress'] && function_exists('gzcompress')) {
			//数据压缩
			$data = gzcompress($data, 3);
		}
		$data   = "<?php\n//" . sprintf('%012d', $life) . "\n exit();?>\n" . $data;
		$result = file_put_contents($filename, $data);
		if ($result) {
			isset($first) && $this->setTagItem($filename);
			clearstatcache();
			return true;
		} else {
			return false;
		}
	}

	/**
	 * 自增缓存（针对数值缓存）
	 * @access public
	 * @param string    $name 缓存变量名
	 * @param int       $step 步长
	 * @return false|int
	 */
	public function inc($name, $step = 1){
		if ($this->has($name)) {
			$value  = $this->get($name) + $step;
			$expire = $this->expire;
		} else {
			$value  = $step;
			$expire = 0;
		}

		return $this->set($name, $value, $expire) ? $value : false;
	}

	/**
	 * 自减缓存（针对数值缓存）
	 * @access public
	 * @param string    $name 缓存变量名
	 * @param int       $step 步长
	 * @return false|int
	 */
	public function dec($name, $step = 1){
		if ($this->has($name)) {
			$value  = $this->get($name) - $step;
			$expire = $this->expire;
		} else {
			$value  = -$step;
			$expire = 0;
		}

		return $this->set($name, $value, $expire) ? $value : false;
	}

	/**
	 * 更新标签
	 * @access public
	 * @param string $name 缓存标识
	 * @return void
	 */
	protected function setTagItem($name){
		if ($this->tag) {
			$key       = 'tag_' . md5($this->tag);
			$this->tag = null;
			if ($this->has($key)) {
				$value   = explode(',', $this->get($key));
				$value[] = $name;
				$value   = implode(',', array_unique($value));
			} else {
				$value = $name;
			}
			$this->set($key, $value, 0);
		}
	}

	/**
	 * 获取标签包含的缓存标识
	 * @access public
	 * @param string $tag 缓存标签
	 * @return array
	 */
	protected function getTagItem($tag){
		$key   = 'tag_' . md5($tag);
		$value = $this->get($key);
		if ($value) {
			return array_filter(explode(',', $value));
		} else {
			return [];
		}
	}

	/**
	 * 更新一条数据
	 * @param string $key	键名
	 * @param array $data	数据
	 * @param int  $life	缓存时间 (默认为永久)
	 * @return bool
	 */
	public function update($key, $data, $life = 0) {
		$key = $this->pre.$key;
		$arr = $this->get($key);
		if($arr !== FALSE) {
			is_array($arr) && is_array($data) && $arr = array_merge($arr, $data);
			return $this->set($key, $arr, $life);
		}
		return FALSE;
	}

	/**
	 * 删除一条数据
	 * @param string $key	键名
	 * @return bool
	 */
	public function delete($key) {
		$filename = $this->getCacheKey($key);
		try {
			return $this->unlink($filename);
		} catch (\Exception $e) {
		}
	}

	/**
	 * 判断文件是否存在后，删除
	 * @param $path
	 * @return bool
	 * @author byron sampson <xiaobo.sun@qq.com>
	 * @return boolean
	 */
	private function unlink($path){
		return is_file($path) && unlink($path);
	}

	/**
	 * 获取/设置最大ID
	 * @param string $table	表名
	 * @param boot/int $val	值	（为 FALSE 时为获取）
	 * @return int
	 */
	public function maxid($table, $val = FALSE) {
		$key = $table.'-Auto_increment';
		if($val === FALSE) {
			return intval($this->get($key));
		}else{
			 $this->set($key, $val);
			 return $val;
		}
	}

	/**
	 * 获取/设置总条数
	 * @param string $table	表名
	 * @param boot/int $val	值	（为 FALSE 时为获取）
	 * @return int
	 */
	public function count($table, $val = FALSE) {
		$key = $table.'-Rows';
		if($val === FALSE) {
			return intval($this->get($key));
		}else{
			$this->set($key, $val);
			return $val;
		}
	}

	/**
	 * 清空缓存
	 * @param string $tag 标签名
	 * @return boot
	 */
	public function truncate($tag = null) {
		if ($tag) {
			// 指定标签清除
			$keys = $this->getTagItem($tag);
			foreach ($keys as $key) {
				$this->unlink($key);
			}
			$this->rm('tag_' . md5($tag));
			return true;
		}
		$files = (array) glob($this->conf['path'] . ($this->conf['prefix'] ? $this->conf['prefix'] . DS : '') . '*');
		foreach ($files as $path) {
			if (is_dir($path)) {
				$matches = glob($path . '/*.php');
				if (is_array($matches)) {
					array_map('unlink', $matches);
				}
				rmdir($path);
			} else {
				unlink($path);
			}
		}
		return true;
	}

	/**
	 * 删除缓存
	 * @access public
	 * @param string $name 缓存变量名
	 * @return boolean
	 */
	public function rm($name){
		$filename = $this->getCacheKey($name);
		try {
			return $this->unlink($filename);
		} catch (\Exception $e) {
		}
	}

	/**
	 * 读取一条二级缓存
	 * @param string $l2_key	二级缓存键名
	 * @return boot
	 */
	public function l2_cache_get($l2_key) {

	}

	/**
	 * 写入一条二级缓存
	 * @param string $l2_key	二级缓存键名
	 * @param string $keys		键名数组
	 * @return boot
	 */
	public function l2_cache_set($l2_key, $keys, $life = 0) {

	}
}
?>
