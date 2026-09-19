<?php
defined('ROOT_PATH') or exit;
/**
 * 游戏分类模型
 * 表 cms_game_category，提供后台分类管理、前台分类列表与前台分类页 URL 登记（type=3）
 */

class game_category extends model {
    public function __construct() {
        $this->table = 'cms_game_category';
        $this->pri = array('id');
        $this->maxid = 'id';
    }

    /**
     * 站点全部分类（后台用，含停用）
     */
    public function admin_all($site_id) {
        return $this->find_fetch(
            array('site_id' => (int)$site_id),
            array('orderby' => 1, 'id' => 1)
        );
    }

    /**
     * 前台启用的分类
     */
    public function front_all($site_id) {
        return $this->find_fetch(
            array('site_id' => (int)$site_id, 'enabled' => 1),
            array('orderby' => 1, 'id' => 1)
        );
    }

    /**
     * 按 URL 别名取分类
     */
    public function get_by_alias($alias, $site_id) {
        $list = $this->find_fetch(
            array('site_id' => (int)$site_id, 'alias' => $alias),
            array(), 0, 1
        );
        return $list ? reset($list) : array();
    }

    /**
     * 分类 alias 去重：重复时追加 -2/-3 后缀
     */
    public function unique_alias($alias, $site_id, $exclude_id = 0) {
        $alias = $alias ? preg_replace('/[^a-z0-9\-]/i', '', $alias) : '';
        if($alias === '') $alias = 'cat';
        $base = $alias;
        $i = 1;
        while(true) {
            $row = $this->get_by_alias($alias, $site_id);
            if(!$row || ($exclude_id > 0 && $row['id'] == $exclude_id)) break;
            $i++;
            $alias = $base . '-' . $i;
        }
        return $alias;
    }

    /**
     * 创建/更新分类并同步 url_map（type=3，/category/{alias}.html）
     */
    public function save_cate($arr) {
        $url = '/category/' . $arr['alias'] . '.html';
        if(!empty($arr['id'])) {
            $old = $this->get($arr['id']);
            $ret = parent::update($arr);
            if($ret && $old) {
                $this->register_url_map($old['alias'], $arr['site_id'], 3); // 旧别名失效
                $this->register_url_map($arr['alias'], $arr['site_id'], 2);
            }
            return !empty($arr['id']) ? $arr['id'] : 0;
        }
        $arr['id'] = 0;
        $id = parent::create($arr);
        if($id) {
            $this->register_url_map($arr['alias'], $arr['site_id'], 2);
        }
        return $id;
    }

    /**
     * 分类页 URL 登记 le_cms_url_map（type=3）
     */
    public function register_url_map($alias, $site_id, $status = 2, $cate_id = 0) {
        $url = '/category/' . $alias . '.html';
        $url_hash = substr(strtolower(hash('sha256', $url)), 0, 40);
        $tablepre = $this->db->tablepre;
        $site_id = (int)$site_id;
        $status = (int)$status;
        $parse_url = '/category/' . $alias . '.html';
        $params = json_encode(array('slug' => $alias));
        $parse_hash = substr(strtolower(hash('sha256', $parse_url)), 0, 40);
        $exists = $this->db->fetch_first("SELECT id FROM `{$tablepre}cms_url_map` WHERE site_id={$site_id} AND url_hash='{$parse_hash}' LIMIT 1");
        if($exists) {
            return $this->db->query("UPDATE `{$tablepre}cms_url_map` SET status={$status}, type=3, control='game', action='index', params='" . addslashes($params) . "', content_id=" . (int)$cate_id . " WHERE id=" . (int)$exists['id']);
        }
        return $this->db->query("INSERT INTO `{$tablepre}cms_url_map` (site_id,url,url_hash,type,control,action,params,score,status,content_id) VALUES ({$site_id},'{$url}','{$url_hash}',3,'game','index','" . addslashes($params) . "',85,{$status}," . (int)$cate_id . ")");
    }

    /**
     * 删除分类（分类下有游戏时拒绝）
     * @return array array(ok=bool, msg=string)
     */
    public function delete_cate($id, $site_id) {
        $row = $this->get($id);
        if(!$row || $row['site_id'] != $site_id) {
            return array(false, '分类不存在');
        }
        $tablepre = $this->db->tablepre;
        $cnt_row = $this->db->fetch_first("SELECT COUNT(*) AS num FROM `{$tablepre}cms_game` WHERE category_id=" . (int)$id);
        $cnt = $cnt_row ? (int)$cnt_row['num'] : 0;
        if($cnt > 0) {
            return array(false, "该分类下还有 {$cnt} 款游戏，请先转移或删除");
        }
        $ret = parent::delete($id);
        if($ret) {
            $this->register_url_map($row['alias'], $site_id, 3);
        }
        return array($ret, $ret ? '已删除' : '删除失败');
    }
}