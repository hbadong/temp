<?php
/**
 * 汉字模型 - 起名系统数据模型层
 */

defined('IN_YZMPHP') or exit('Access Denied');

class character_model {
    
    private $table = 'chinese_characters';
    
    /**
     * 获取汉字信息
     */
    public function get_char($char) {
        return D($this->table)->where(array('char' => $char))->find();
    }
    
    /**
     * 根据拼音搜索汉字
     */
    public function search_by_pinyin($pinyin, $limit = 20) {
        return D($this->table)->where("pinyin LIKE '%{$pinyin}%'")->limit($limit)->select();
    }
    
    /**
     * 根据五行搜索
     */
    public function search_by_wuxing($wuxing, $limit = 50) {
        return D($this->table)->where(array('wuxing' => $wuxing))->limit($limit)->select();
    }
    
    /**
     * 根据笔画搜索
     */
    public function search_by_bihua($bihua, $limit = 50) {
        return D($this->table)->where(array('bihua' => $bihua))->limit($limit)->select();
    }
    
    /**
     * 获取汉字笔画数
     */
    public function get_bihua($char) {
        $data = $this->get_char($char);
        return $data ? $data['bihua'] : 7; // 默认返回7画
    }
    
    /**
     * 获取汉字五行
     */
    public function get_wuxing($char) {
        $data = $this->get_char($char);
        return $data ? $data['wuxing'] : 5; // 默认返回土
    }
}
