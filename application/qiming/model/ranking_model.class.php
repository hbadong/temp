<?php
/**
 * 排行模型 - 起名系统数据模型层
 */

defined('IN_YZMPHP') or exit('Access Denied');

class ranking_model {
    
    private $table = 'yzm_name_rankings';
    
    /**
     * 获取排行列表
     * @param string $type 类型(boy-char/girl-char/boy-name/girl-name)
     * @param int $limit 返回数量
     */
    public function get_list($type, $limit = 30) {
        return D($this->table)->where(array('type' => $type))->order('ranking ASC')->limit($limit)->select();
    }
    
    /**
     * 获取排行列表（按类型）
     * @param string $type 类型(boy-char/girl-char/boy-name/girl-name)
     * @param int $limit 返回数量
     */
    public function get_by_type($type, $limit = 30) {
        $current_month = date('Y-m');
        $data = D($this->table)->where(array('type' => $type, 'month' => $current_month))->order('ranking ASC')->limit($limit)->select();
        
        if (empty($data)) {
            $data = D($this->table)->where(array('type' => $type))->order('ranking ASC')->limit($limit)->select();
        }
        
        return $data;
    }
    
    /**
     * 获取热门男孩用字
     */
    public function get_boy_chars($limit = 30) {
        return $this->get_list('boy-char', $limit);
    }
    
    /**
     * 获取热门女孩用字
     */
    public function get_girl_chars($limit = 30) {
        return $this->get_list('girl-char', $limit);
    }
    
    /**
     * 获取热门男孩名字
     */
    public function get_boy_names($limit = 30) {
        return $this->get_list('boy-name', $limit);
    }
    
    /**
     * 获取热门女孩名字
     */
    public function get_girl_names($limit = 30) {
        return $this->get_list('girl-name', $limit);
    }
    
    /**
     * 更新搜索次数
     */
    public function update_search_count($char_or_name, $type) {
        $data = D($this->table)->where(array('char_or_name' => $char_or_name, 'type' => $type))->find();
        if ($data) {
            D($this->table)->update("search_count = search_count + 1", array('id' => $data['id']));
        }
    }
}
