<?php
defined('ROOT_PATH') or exit;

class runtime extends model {
	private $data = array();		// 保证唯一性
	private $changed = array();		// 表示修改过的key

	function __construct() {
		$this->table = 'runtime';	// 表名
		$this->pri = array('k');	// 主键

		// hook runtime_model_construct_after.php
	}

	// 读取缓存
	public function get($k) {
        strlen($k) > 32 AND $k = md5($k);
		$arr = parent::get($k);
		return !empty($arr) && (empty($arr['expiry']) || $arr['expiry'] > $_ENV['_time']) ? _json_decode($arr['v']) : array();
	}

	// 写入缓存
	public function set($k, $s, $life = 0) {
		$s = _json_encode($s);
		$arr = array();
        strlen($k) > 32 AND $k = md5($k);
		$arr['k'] = $k;
		$arr['v'] = $s;
		$arr['expiry'] = $life ? $_ENV['_time'] + $life : 0;
		return parent::set($k, $arr);
	}

	// 读取
	public function xget($key = 'cfg') {
		if(!isset($this->data[$key])) {
			$this->data[$key] = $this->get($key);
			if($key == 'cfg' && empty($this->data[$key])) {
				$cfg = (array)$this->kv->get('cfg');

				empty($cfg['theme']) && $cfg['theme'] = 'default';  //主题

				$cfg['tpl'] = $cfg['webdir'].'view/'.$cfg['theme'].'/'; //模板路径
				$cfg['webroot'] = HTTP.$cfg['webdomain']; //完整域名，不带安装目录
				$cfg['weburl'] = HTTP.$cfg['webdomain'].$cfg['webdir']; //完整域名，带安装目录

				//模型
                $models = $this->models->get_models();
                $table_arr = $mod_name = array();
                foreach ($models as $md){
                    $table_arr[$md['mid']] = $md['tablename'];
                    if($md['mid'] > 1){
                        $mod_name[$md['mid']] = $md['name'];
                    }
                }
				$cfg['table_arr'] = $table_arr;
				$cfg['mod_name'] = $mod_name;

				//用户组
				if(isset($cfg['open_user']) && $cfg['open_user']){
                    $cfg['group_name'] = $this->user_group->get_name();
                }

				//分类
				$categorys = $this->category->get_category_db();
				$cate_arr = array();
				foreach($categorys as $row) {
					$cate_arr[$row['cid']] = $row['alias'];
				}
				$cfg['cate_arr'] = $cate_arr;
                // hook runtime_model_xget_cfg_set_after.php
				$this->data[$key] = &$cfg;
				$this->set('cfg', $this->data[$key]);
			}
		}
        //移动端模板分离
        if($key == 'cfg' && !empty($this->data['cfg']['open_mobile_view']) && is_mobile()==1){
            $this->data['cfg']['theme'] =  isset($this->data['cfg']['mobile_view']) ? $this->data['cfg']['mobile_view'] : 'mobile';
            $this->data['cfg']['tpl'] = $this->data['cfg']['webdir'].'view/'.$this->data['cfg']['theme'].'/';
            $this->data['cfg']['weburl'] = HTTP.$_SERVER['HTTP_HOST'].$this->data['cfg']['webdir'];
        }
        // hook runtime_model_xget_cfg_after.php
		return $this->data[$key];
	}

	// 修改
	public function xset($k, $v, $key = 'cfg') {
		if(!isset($this->data[$key])) {
			$this->data[$key] = $this->get($key);
		}
		if($v && is_string($v) && ($v[0] == '+' || $v[0] == '-')) {
			$v = intval($v);
			$this->data[$key][$k] += $v;
		}else{
			$this->data[$key][$k] = $v;
		}
		$this->changed[$key] = 1;
	}

	// 保存
	public function xsave($key = 'cfg') {
		$this->set($key, $this->data[$key]);
		$this->changed[$key] = 0;
	}

	// 保存所有修改过的key
	public function save_changed() {
		foreach($this->changed as $key=>$v) {
			$v && $this->xsave($key);
		}
	}

	//删除
    public function xdelete($k = ''){
        strlen($k) > 32 AND $k = md5($k);
        return $this->delete($k);
    }

    //设置文件缓存
    public function setFileCache($key = '', $data = array(), $life = 600){
        // hook runtime_model_setFileCache_before.php
        $datas = array(
            'datas' => serialize($data),
            'time' => $_ENV['_time']+$life
        );
        $cacheFile = RUNTIME_PATH.'filecache/' . $key . '.cache';

        // hook runtime_model_setFileCache_after.php
        return FW($cacheFile, _json_encode($datas));
    }

    //读取文件缓存
    public function getFileCache($key = ''){
        // hook runtime_model_getFileCache_before.php
        $cacheFile = RUNTIME_PATH.'filecache/'. $key . '.cache';
        if ( !is_file($cacheFile) ) {
            return array();
        }

        $cacheFile_str = file_get_contents($cacheFile);
        $cache_datas_arr = _json_decode($cacheFile_str);

        if( empty($cache_datas_arr) || $cache_datas_arr['time'] < $_ENV['_time'] ){
            @unlink($cacheFile);
            return array();
        }

        if( isset($cache_datas_arr['datas']) ){
            $datas = unserialize($datas['datas']);
        }else{
            $datas = array();
        }
        // hook runtime_model_getFileCache_after.php
        return $datas;
    }

    // hook runtime_model_after.php
}
