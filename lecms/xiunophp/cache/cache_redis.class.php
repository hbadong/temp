<?php
defined('FRAMEWORK_PATH') || exit;
class cache_redis implements cache_interface{
	private $conf;
	private $is_getmulti = FALSE;	//是否支持 getMulti 方法
	public $pre;	//缓存前缀 （防止同一台缓存服务器，有多套程序，键名冲突问题）

	public function __construct(&$conf) {
		$this->conf = &$conf;
		$this->pre = $conf['pre'];
	}

	public function __get($var) {
		if(!isset($this->conf['redis'])){
			throw new Exception('Can not fount redis config. Please check config.inc.php');
		}
		$c = $this->conf['redis'];
		if($var == 'redis') {
			if(extension_loaded('Redis')) {
				$this->redis = new Redis;
			}else{
				throw new Exception('Redis Extension not loaded.');
			}

			if(!$this->redis) {
				throw new Exception('PHP.ini Error: Redis extension not loaded.');
			}

			if($this->redis->connect($c['host'], $c['port'])) {
				if(!empty($c['multi'])) {
					$this->is_getmulti = method_exists($this->redis, 'getMulti');
				}
				return $this->redis;
			}else{
				throw new Exception('Can not connect to Redis host.');
			}
		}
	}

	/**
	 * 读取一条数据
	 * @param string $key	键名
	 * @return array
	 */
	public function get($key) {
		$r = $this->redis->get($this->pre.$key);
		return $r === FALSE ? NULL : _json_decode($r);
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

		// 二级缓存开启时，写入最新微秒时间
		if($this->conf['l2_cache'] === 1) {
			$this->redis->del($this->pre.'_l2_cache_time');
		}

		$v = _json_encode($data);
		$r = $this->redis->set($this->pre.$key, $v);
		$life AND $r AND $this->redis->expire($this->pre.$key, $life);
		return $r;
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
		if($arr !== NULL) {
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
			$this->redis->del($this->pre.'_l2_cache_time');
		}

		return $this->redis->del($this->pre.$key) ? TRUE : FALSE;
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
		return $this->redis->flushdb(); // flushall
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
			$r = $this->redis->set($this->pre.'_l2_cache_time', $l2_cache_time);
			$life AND $r AND $this->redis->expire($this->pre.'_l2_cache_time', $life);
		}

		$r = $this->redis->set($this->pre.$l2_key.'_time', $l2_cache_time);	// 把最后更新数据微秒时间写入缓存
		$l2_cache_time AND $r AND $this->redis->expire($this->pre.$l2_key.'_time', $l2_cache_time);

		return $this->redis->set($this->pre.$l2_key, $keys);	// 把数据写入缓存
	}
}
?>
