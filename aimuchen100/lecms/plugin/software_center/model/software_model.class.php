<?php
defined('ROOT_PATH') or exit;
/**
 * 软件模型
 */

class software extends model {
    public function __construct() {
        $this->table = 'cms_software';
        $this->pri = array('id');
        $this->maxid = 'id';
    }

    /**
     * 获取软件详情
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
     * 后台软件列表（复合筛选 + 分页）
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
        if(!empty($filter['cat_id'])) $where['cat_id'] = (int)$filter['cat_id'];
        if(isset($filter['status']) && $filter['status'] !== '') $where['status'] = (int)$filter['status'];
        if(!empty($filter['platform'])) $where['platform'] = array('LIKE' => $filter['platform']);
        if(!empty($filter['keyword'])) $where['name'] = array('LIKE' => $filter['keyword']);
        return $where;
    }

    /**
     * 前台：按分类列表（仅上架）
     */
    public function front_list($site_id, $cat_id, $page = 1, $pagenum = 20) {
        $where = array('site_id' => (int)$site_id, 'status' => 1);
        if($cat_id > 0) $where['cat_id'] = (int)$cat_id;
        $offset = ($page - 1) * $pagenum;
        return $this->find_fetch($where, array('id' => -1), $offset, $pagenum);
    }

    public function front_count($site_id, $cat_id = 0) {
        $where = array('site_id' => (int)$site_id, 'status' => 1);
        if($cat_id > 0) $where['cat_id'] = (int)$cat_id;
        return $this->find_count($where);
    }

    /**
     * 前台：下载排行
     */
    public function top_downloads($site_id, $num = 10) {
        return $this->find_fetch(
            array('site_id' => (int)$site_id, 'status' => 1),
            array('downloads' => -1), 0, (int)$num
        );
    }

    /**
     * 下载计数原子递增（field=field+1，避免读改写竞态）
     */
    public function inc_download($id) {
        return $this->update_views($id, 1, 'downloads');
    }

    /**
     * 创建软件（自动时间戳 + url_map 登记）
     */
    public function create_soft($arr) {
        $arr['dateline'] = $_ENV['_time'];
        $arr['updated_at'] = $_ENV['_time'];
        $id = parent::create($arr);
        if($id) {
            $this->register_url_map($id, $arr['site_id']);
        }
        return $id;
    }

    /**
     * 更新软件（刷新时间戳，上架时确保 url_map 有效）
     */
    public function update_soft($arr) {
        $arr['updated_at'] = $_ENV['_time'];
        $ret = parent::update($arr);
        if($ret && !empty($arr['id'])) {
            $row = $this->get($arr['id']);
            if($row) {
                // 上架状态登记/恢复 URL，下架置失效
                $this->register_url_map($row['id'], $row['site_id'], $row['status'] == 1 ? 2 : 3);
            }
        }
        return $ret;
    }

    /**
     * 删除软件（url_map 置失效）
     */
    public function delete_soft($id) {
        $row = $this->get($id);
        if($row) {
            $this->invalidate_url_map($row['id'], $row['site_id']);
        }
        return parent::delete($id);
    }

    /**
     * 软件详情 URL 登记 le_cms_url_map（type=9）
     * url_hash 与 url_generator 读写两端一致：SHA256 截断 40 位小写
     */
    public function register_url_map($soft_id, $site_id, $status = 2) {
        $url = '/soft/' . (int)$soft_id . '.html';
        $url_hash = substr(strtolower(hash('sha256', $url)), 0, 40);
        $tablepre = $this->db->tablepre;
        $site_id = (int)$site_id;
        $soft_id = (int)$soft_id;
        $status = (int)$status;
        $exists = $this->db->fetch_first("SELECT id FROM `{$tablepre}cms_url_map` WHERE site_id={$site_id} AND url_hash='{$url_hash}' LIMIT 1");
        if($exists) {
            $this->db->query("UPDATE `{$tablepre}cms_url_map` SET status={$status}, content_id={$soft_id}, type=9, control='soft', action='detail' WHERE id=" . (int)$exists['id']);
        }else{
            $params = json_encode(array('id' => $soft_id));
            $this->db->query("INSERT INTO `{$tablepre}cms_url_map` (site_id,url,url_hash,type,control,action,params,score,status,content_id) VALUES ({$site_id},'{$url}','{$url_hash}',9,'soft','detail','" . addslashes($params) . "',85,{$status},{$soft_id})");
        }
        // 同步登记下载 URL（/soft/download-{id}.html，独立 action 累计下载次数）
        $dl_url = '/soft/download-' . $soft_id . '.html';
        $dl_hash = substr(strtolower(hash('sha256', $dl_url)), 0, 40);
        $dl_exists = $this->db->fetch_first("SELECT id FROM `{$tablepre}cms_url_map` WHERE site_id={$site_id} AND url_hash='{$dl_hash}' LIMIT 1");
        if($dl_exists) {
            return $this->db->query("UPDATE `{$tablepre}cms_url_map` SET status={$status}, content_id={$soft_id}, type=9, control='soft', action='download' WHERE id=" . (int)$dl_exists['id']);
        }
        $dl_params = json_encode(array('id' => $soft_id));
        return $this->db->query("INSERT INTO `{$tablepre}cms_url_map` (site_id,url,url_hash,type,control,action,params,score,status,content_id) VALUES ({$site_id},'{$dl_url}','{$dl_hash}',9,'soft','download','" . addslashes($dl_params) . "',85,{$status},{$soft_id})");
    }

    /**
     * 软件详情 URL 置失效
     */
    public function invalidate_url_map($soft_id, $site_id) {
        $url = '/soft/' . (int)$soft_id . '.html';
        $url_hash = substr(strtolower(hash('sha256', $url)), 0, 40);
        $tablepre = $this->db->tablepre;
        return $this->db->query("UPDATE `{$tablepre}cms_url_map` SET status=3 WHERE site_id=" . (int)$site_id . " AND url_hash='{$url_hash}'");
    }

    /**
     * 统计概览（总览页用）
     */
    public function overview($site_id) {
        $tablepre = $this->db->tablepre;
        $site_id = (int)$site_id;
        $row = $this->db->fetch_first("SELECT COUNT(*) AS total, SUM(status=1) AS online, SUM(downloads) AS dl_sum FROM `{$tablepre}cms_software` WHERE site_id={$site_id}");
        $today_row = $this->db->fetch_first("SELECT COUNT(*) AS num FROM `{$tablepre}cms_software` WHERE site_id={$site_id} AND dateline >= " . strtotime(date('Y-m-d', $_ENV['_time'])));
        return array(
            'total' => $row ? (int)$row['total'] : 0,
            'online' => $row && $row['online'] ? (int)$row['online'] : 0,
            'downloads' => $row && $row['dl_sum'] ? (int)$row['dl_sum'] : 0,
            'today' => $today_row ? (int)$today_row['num'] : 0,
        );
    }
}
