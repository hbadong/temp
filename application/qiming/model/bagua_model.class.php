<?php
/**
 * 八卦模型 - 起名系统数据模型层
 */

defined('IN_YZMPHP') or exit('Access Denied');

class bagua_model {
    
    private $table = 'bagua';
    
    /**
     * 获取八卦列表
     */
    public function get_list() {
        return D($this->table)->select();
    }
    
    /**
     * 获取八卦详情
     */
    public function get_detail($id) {
        return D($this->table)->where(array('id' => $id))->find();
    }
    
    /**
     * 根据卦名获取
     */
    public function get_by_name($name) {
        return D($this->table)->where(array('gua_name' => $name))->find();
    }
    
    /**
     * 获取八卦解释
     */
    public function get_explanation($id) {
        $bagua = $this->get_detail($id);
        if (!$bagua) return null;
        
        return array(
            'name' => $bagua['gua_name'],
            'gua_ci' => $bagua['gua_ci'],
            'tuan_ci' => $bagua['tuan_ci'],
            'xiang_ci' => $bagua['xiang_ci'],
            'wuxing' => $bagua['wuxing'],
        );
    }
}
