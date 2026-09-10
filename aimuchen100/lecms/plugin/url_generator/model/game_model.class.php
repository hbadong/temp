<?php
defined('ROOT_PATH') or exit;
/**
 * 游戏元数据模型
 */

class game extends model {
    public function __construct() {
        $this->table = 'cms_game';
        $this->pri = array('id');
        $this->maxid = 'id';
    }

    /**
     * 获取游戏详情
     */
    public function get($id) {
        return parent::get($id);
    }

    /**
     * 通过名称搜索游戏
     */
    public function get_by_name($name, $site_id = 0) {
        $name = safe_str($name);
        $where = array('name' => $name);
        if($site_id > 0) $where['site_id'] = $site_id;
        $list = $this->find_fetch($where, array(), 0, 1);
        return $list ? reset($list) : array();
    }

    /**
     * 获取站点下的游戏列表
     */
    public function get_list($site_id, $page = 1, $pagenum = 20) {
        $where = array('site_id' => (int)$site_id);
        $offset = ($page - 1) * $pagenum;
        return $this->find_fetch($where, array('id' => -1), $offset, $pagenum, 0);
    }

    /**
     * 创建游戏
     */
    public function create($arr) {
        // created_at 列为 DATETIME（install.php 定义），写入 datetime 字符串而非 int 时间戳
        $arr['created_at'] = date('Y-m-d H:i:s', $_ENV['_time']);
        return parent::create($arr);
    }
}
