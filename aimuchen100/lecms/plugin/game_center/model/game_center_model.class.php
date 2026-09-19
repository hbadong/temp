<?php
defined('ROOT_PATH') or exit;
/**
 * 游戏库后台管理模型
 * 基于 cms_game 表，提供后台列表/创建/更新/删除/上下架与前台 URL 登记
 * 类名 game_center，与 url_generator 插件的 game 模型区分，避免类名冲突
 */

class game_center extends model {
    public function __construct() {
        $this->table = 'cms_game';
        $this->pri = array('id');
        $this->maxid = 'id';
    }

    /**
     * 获取游戏详情
     */
    public function get($id) {
        return parent::get((int)$id);
    }

    /**
     * 站点内按名称查重
     */
    public function get_by_name($name, $site_id) {
        $where = array('site_id' => (int)$site_id, 'name' => $name);
        $list = $this->find_fetch($where, array(), 0, 1);
        return $list ? reset($list) : array();
    }

    /**
     * 后台游戏列表（复合筛选 + 分页）
     * @param array $filter site_id/cat_id/platform/status/keyword
     */
    public function admin_list($filter, $page = 1, $pagenum = 20) {
        $where = $this->build_where($filter);
        $offset = ($page - 1) * $pagenum;
        return $this->find_fetch($where, array('id' => -1), $offset, $pagenum);
    }

    /**
     * 复合筛选计数
     */
    public function admin_count($filter) {
        return $this->find_count($this->build_where($filter));
    }

    private function build_where($filter) {
        $where = array('site_id' => (int)$filter['site_id']);
        if(!empty($filter['cat_id'])) $where['category_id'] = (int)$filter['cat_id'];
        if(isset($filter['status']) && $filter['status'] !== '') $where['status'] = (int)$filter['status'];
        if(!empty($filter['platform'])) $where['platform'] = array('LIKE' => $filter['platform']);
        if(!empty($filter['keyword'])) $where['name'] = array('LIKE' => $filter['keyword']);
        return $where;
    }

    /**
     * 创建游戏（自动时间戳 + 前台 URL 登记）
     */
    public function create_game($arr) {
        $arr['created_at'] = date('Y-m-d H:i:s', $_ENV['_time']);
        $arr['updated_at'] = date('Y-m-d H:i:s', $_ENV['_time']);
        $id = parent::create($arr);
        if($id) {
            $this->register_url_map($id, $arr['site_id']);
        }
        return $id;
    }

    /**
     * 更新游戏（刷新时间戳，上架时确保前台 URL 有效）
     */
    public function update_game($arr) {
        $arr['updated_at'] = date('Y-m-d H:i:s', $_ENV['_time']);
        $ret = parent::update($arr);
        if($ret && !empty($arr['id'])) {
            $row = $this->get($arr['id']);
            if($row) {
                $this->register_url_map($row['id'], $row['site_id'], $row['status'] == 1 ? 2 : 3);
            }
        }
        return $ret;
    }

    /**
     * 删除游戏（前台 URL 置失效）
     */
    public function delete_game($id) {
        $row = $this->get($id);
        if($row) {
            $this->invalidate_url_map($row['id'], $row['site_id']);
        }
        return parent::delete($id);
    }

    /**
     * 游戏详情 URL 登记 le_cms_url_map（type=2）
     * url_hash 与 url_generator 读写两端一致：SHA256 截断 40 位小写
     */
    public function register_url_map($game_id, $site_id, $status = 2) {
        $url = '/' . (int)$game_id . '.html';
        $url_hash = substr(strtolower(hash('sha256', $url)), 0, 40);
        $tablepre = $this->db->tablepre;
        $site_id = (int)$site_id;
        $game_id = (int)$game_id;
        $status = (int)$status;
        $exists = $this->db->fetch_first("SELECT id FROM `{$tablepre}cms_url_map` WHERE site_id={$site_id} AND url_hash='{$url_hash}' LIMIT 1");
        if($exists) {
            return $this->db->query("UPDATE `{$tablepre}cms_url_map` SET status={$status}, type=2, control='game', action='index', content_id={$game_id} WHERE id=" . (int)$exists['id']);
        }
        $params = json_encode(array('id' => $game_id));
        return $this->db->query("INSERT INTO `{$tablepre}cms_url_map` (site_id,url,url_hash,type,control,action,params,score,status,content_id) VALUES ({$site_id},'{$url}','{$url_hash}',2,'game','index','" . addslashes($params) . "',85,{$status},{$game_id})");
    }

    /**
     * 游戏详情 URL 置失效（删除/下架）
     */
    public function invalidate_url_map($game_id, $site_id) {
        $url = '/' . (int)$game_id . '.html';
        $url_hash = substr(strtolower(hash('sha256', $url)), 0, 40);
        $tablepre = $this->db->tablepre;
        return $this->db->query("UPDATE `{$tablepre}cms_url_map` SET status=3 WHERE site_id=" . (int)$site_id . " AND url_hash='{$url_hash}'");
    }
}