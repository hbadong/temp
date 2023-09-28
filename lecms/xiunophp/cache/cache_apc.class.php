<?php
defined('FRAMEWORK_PATH') || exit;
class cache_apc implements cache_interface{
	private $conf;
	private $is_getmulti = FALSE;	//是否支持 getMulti 方法
	public $pre;	//缓存前缀 （防止同一台缓存服务器，有多套程序，键名冲突问题）

	public function __construct(&$conf) {
		$this->conf = &$conf;
		$this->pre = $conf['pre'];
	}

	public function __get($var) {
		$c = $this->conf['apc'];
		if($var == 'apc') {
			if(!function_exists('apc_fetch')) {
				throw new Exception('APC 扩展没有加载，请检查您的 PHP 版本');
			}
		}
	}

	/**
	 * 读取一条数据
	 * @param string $key	键名
	 * @return array
	 */
	public function get($key) {
		return apc_fetch($this->pre.$key);
	}

	/**
	 * 读取多条数据
	 * @param array $keys	键名数组
	 * @return array
	 */
	public function multi_get($keys) {
		$data = array();
		foreach($keys as $k) {
			$arr = apc_fetch($this->pre.$k);
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
		// 二级缓存开启时，写入最新微秒时间
		if($this->conf['l2_cache'] === 1) {
			apc_delete($this->pre.'_l2_cache_time');
		}
		return apc_store($this->pre.$key, $data, $life);
	}

	/**
	 * 更新一条数据
	 * @param string $key	键名
	 * @param array $data	数据
	 * @param int  $life	缓存时间 (默认为永久)
	 * @return bool
	 */
	public function update($key, $data, $life = 0) {
		//$key = $this->pre.$key;
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
		// 二级缓存开启时，写入最新微秒时间
		if($this->conf['l2_cache'] === 1) {
			apc_delete($this->pre.'_l2_cache_time');
		}
		return apc_delete($this->pre.$key);
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
	 * @param string $pre	前缀
	 * @return boot
	 */
	public function truncate($pre = '') {
		return apc_clear_cache('user');
	}

	/**
	 * 读取一条二级缓存
	 * @param string $l2_key	二级缓存键名
	 * @return boot
	 */
	public function l2_cache_get($l2_key) {
		$l2_cache_time = $this->get('_l2_cache_time');	// 最后更新数据微秒时间，用来控制缓存
		$l2_key_time = $this->get($l2_key.'_time');	// 用来和 $l2_cache_time 对比是否一样
		if($l2_cache_time && $l2_cache_time === $l2_key_time) {
			return $this->get($l2_key);	// 从缓存中读取数据
		}
		return FALSE;
	}

	/**
	 * 写入一条二级缓存
	 * @param string $l2_key	二级缓存键名
	 * @param string $keys		键名数组
	 * @return boot
	 */
	public function l2_cache_set($l2_key, $keys, $life = 0) {
		$l2_cache_time = $this->get('_l2_cache_time');	// 最后更新数据微秒时间，用来控制缓存
		if(empty($l2_cache_time)) {
			$l2_cache_time = microtime(1);
			return apc_store($this->pre.'_l2_cache_time', $l2_cache_time, 0);
		}
		apc_store($this->pre.$l2_key.'_time', $l2_cache_time, $life);	// 把最后更新数据微秒时间写入缓存
		return apc_store($this->pre.$l2_key, $keys, $life);	// 把数据写入缓存
	}
}
?>
