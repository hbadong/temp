<?php
/**
 * 导入日志模型
 * 管理文章批量导入的记录
 */
class import_log extends model {

    public function __construct() {
        $this->table = 'article_import_log';
        $this->pri = array('id');
    }

    /**
     * 创建导入日志
     * @param array $arr 日志数据
     * @return int|bool 新日志ID或false
     */
    public function create($arr) {
        $arr['site_id'] = isset($arr['site_id']) ? (int)$arr['site_id'] : 0;
        $arr['filename'] = isset($arr['filename']) ? trim($arr['filename']) : '';
        $arr['total_rows'] = isset($arr['total_rows']) ? (int)$arr['total_rows'] : 0;
        $arr['imported'] = isset($arr['imported']) ? (int)$arr['imported'] : 0;
        $arr['skipped'] = isset($arr['skipped']) ? (int)$arr['skipped'] : 0;
        $arr['status'] = isset($arr['status']) ? (int)$arr['status'] : 0;
        $arr['report'] = isset($arr['report']) ? (is_array($arr['report']) ? _json_encode($arr['report']) : $arr['report']) : '[]';
        $arr['created_at'] = $_ENV['_time']; // created_at 列为 INT UNSIGNED

        return parent::create($arr);
    }

    /**
     * 更新导入日志（与父类 model::update($data, $life) 签名冲突，另命名为 save）
     * @param int $id 日志ID
     * @param array $arr 更新数据
     * @return bool
     */
    public function save($id, $arr) {
        if (isset($arr['total_rows'])) {
            $arr['total_rows'] = (int)$arr['total_rows'];
        }
        if (isset($arr['imported'])) {
            $arr['imported'] = (int)$arr['imported'];
        }
        if (isset($arr['skipped'])) {
            $arr['skipped'] = (int)$arr['skipped'];
        }
        if (isset($arr['status'])) {
            $arr['status'] = (int)$arr['status'];
        }
        if (isset($arr['report'])) {
            $arr['report'] = is_array($arr['report']) ? _json_encode($arr['report']) : $arr['report'];
        }
        $arr['id'] = (int)$id;

        return parent::update($arr);
    }

    /**
     * 获取站点下所有导入日志
     * @param int $site_id 站点ID
     * @return array 日志列表
     */
    public function get_list($site_id) {
        $site_id = (int)$site_id;
        $where = array('site_id' => $site_id);
        return $this->find_fetch($where, array('id' => 'DESC'));
    }
}
