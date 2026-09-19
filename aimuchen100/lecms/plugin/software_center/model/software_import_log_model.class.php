<?php
defined('ROOT_PATH') or exit;
/**
 * 软件导入日志模型
 */

class software_import_log extends model {
    public function __construct() {
        $this->table = 'cms_software_import_log';
        $this->pri = array('id');
        $this->maxid = 'id';
    }

    /**
     * 写入导入日志
     */
    public function log($site_id, $filename, $total, $success, $failed, $detail) {
        return parent::create(array(
            'site_id' => (int)$site_id,
            'filename' => substr($filename, 0, 190),
            'total' => (int)$total,
            'success' => (int)$success,
            'failed' => (int)$failed,
            'detail' => substr($detail, 0, 60000),
            'dateline' => $_ENV['_time'],
        ));
    }

    /**
     * 最近导入日志
     */
    public function recent($site_id, $num = 10) {
        return $this->find_fetch(
            array('site_id' => (int)$site_id),
            array('id' => -1), 0, (int)$num
        );
    }
}
