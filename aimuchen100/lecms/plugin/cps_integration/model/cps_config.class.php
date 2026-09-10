<?php
defined('ROOT_PATH') or exit;
/**
 * CpsConfig 模型 — CPS 推广链接
 *
 * 修复（对照验证发现）：桩代码 → 真实 DB 操作
 * - 继承 model 复用 db/缓存（$this->db 懒加载 db_pdo_mysql）
 * - REQ-06-AC2 get_best_link：按 enabled=1 + weight DESC 取最优链接
 * - REQ-06-AC1 create_cps：支持权重分配
 * - 全部 SQL 使用 addslashes() 内联转义（框架 db 层无参数绑定）
 */
class CpsConfig extends model {
    public function __construct() {
        $this->table = 'cms_cps_config';
        $this->pri = array('id');
        $this->maxid = 'id';
    }

    /**
     * 获取最佳链接（按启用状态和权重降序）
     * REQ-06-AC2
     * @return array|false
     */
    public function get_best_link($site_id, $game_id) {
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];
        return $this->db->fetch_first("
            SELECT * FROM `{$tablepre}cms_cps_config`
            WHERE site_id = " . (int)$site_id . " AND game_id = " . (int)$game_id . " AND enabled = 1
            ORDER BY weight DESC, id ASC
            LIMIT 1
        ");
    }

    /**
     * 创建推广链接（REQ-06-AC1，支持权重分配）
     * @param int $site_id 站点ID
     * @param int $game_id 游戏ID
     * @param array $data ['name','url','backup_url','weight','enabled']
     * @return int|false 配置ID
     */
    public function create_cps($site_id, $game_id, $data) {
        $now = (int)$_ENV['_time'];
        return $this->create(array(
            'site_id' => (int)$site_id,
            'game_id' => (int)$game_id,
            'name' => isset($data['name']) ? (string)$data['name'] : '',
            'url' => isset($data['url']) ? (string)$data['url'] : '',
            'backup_url' => isset($data['backup_url']) ? (string)$data['backup_url'] : '',
            'weight' => isset($data['weight']) ? min(100, max(0, (int)$data['weight'])) : 50,
            'enabled' => isset($data['enabled']) ? (int)$data['enabled'] : 1,
            'click_count' => 0,
            'unique_ip_count' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ));
    }

    /**
     * 读取配置
     * @param int $id
     * @return array
     */
    public function read_cps($id) {
        return $this->get((int)$id);
    }

    /**
     * 更新配置
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update_cps($id, $data) {
        $id = (int)$id;
        if($id <= 0) return false;
        $update = array('id' => $id, 'updated_at' => (int)$_ENV['_time']);
        if(isset($data['name']))     $update['name'] = (string)$data['name'];
        if(isset($data['url']))      $update['url'] = (string)$data['url'];
        if(isset($data['backup_url'])) $update['backup_url'] = (string)$data['backup_url'];
        if(isset($data['weight']))   $update['weight'] = min(100, max(0, (int)$data['weight']));
        if(isset($data['enabled']))  $update['enabled'] = (int)$data['enabled'];
        return $this->update($update);
    }

    /**
     * 删除配置
     */
    public function delete_cps($id) {
        return $this->delete((int)$id);
    }

    /**
     * 分页列出配置
     * @return array ['list'=>array, 'total'=>int]
     */
    public function list_cps($site_id = 0, $game_id = null, $page = 1, $pagenum = 20) {
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];
        $where = ' WHERE 1';
        if($site_id > 0) $where .= ' AND site_id = ' . (int)$site_id;
        if($game_id !== null && $game_id !== '') $where .= ' AND game_id = ' . (int)$game_id;
        $page = max(1, (int)$page);
        $pagenum = max(1, (int)$pagenum);
        $offset = ($page - 1) * $pagenum;

        $list = $this->db->fetch_all("
            SELECT * FROM `{$tablepre}cms_cps_config`{$where}
            ORDER BY id DESC LIMIT {$offset}, {$pagenum}
        ");
        $count_row = $this->db->fetch_first("SELECT COUNT(*) AS num FROM `{$tablepre}cms_cps_config`{$where}");
        return array('list' => $list, 'total' => (int)$count_row['num']);
    }

    /**
     * 点击计数累加（REQ-06-AC4 后更新）
     * @param int $id 配置ID
     * @param bool $is_unique 是否 24h 内首击（影响唯一IP计数）
     */
    public function increment_click($id, $is_unique) {
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];
        $sql = "UPDATE `{$tablepre}cms_cps_config`
            SET click_count = click_count + 1" .
            ($is_unique ? ", unique_ip_count = unique_ip_count + 1" : '') .
            ", updated_at = " . (int)$_ENV['_time'] .
            " WHERE id = " . (int)$id;
        $this->db->query($sql);
    }
}
