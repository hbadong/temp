<?php
/**
 * 诗词模型 - 起名系统数据模型层
 */

defined('IN_YZMPHP') or exit('Access Denied');

class poetry_model {
    
    private $table = 'yzm_poetry';
    
    /**
     * 获取诗词列表
     * @param int $type 类型(1唐诗2宋词3诗经4楚辞)
     * @param int $limit 返回数量
     */
    public function get_list($type = 1, $limit = 20) {
        return D($this->table)->where(array('type' => $type))->limit($limit)->select();
    }
    
    /**
     * 获取随机诗词
     */
    public function get_random($type = 1, $limit = 10) {
        return D($this->table)->where(array('type' => $type))->order('RAND()')->limit($limit)->select();
    }
    
    /**
     * 获取诗词列表（按类型）
     * @param int $type 类型(1唐诗2宋词3诗经4楚辞)
     * @param int $limit 返回数量
     */
    public function get_by_type($type, $limit = 20) {
        return D($this->table)->where(array('type' => $type))->limit($limit)->select();
    }
    
    /**
     * 搜索诗词
     */
    public function search($keyword, $limit = 20) {
        return D($this->table)->where("title LIKE '%{$keyword}%' OR content LIKE '%{$keyword}%'")->limit($limit)->select();
    }
    
    /**
     * 获取诗词详情
     */
    public function get_detail($id) {
        return D($this->table)->where(array('id' => $id))->find();
    }
    
    /**
     * 根据主题获取诗词
     */
    public function get_by_theme($theme, $limit = 20) {
        return D($this->table)->where(array('theme' => $theme))->limit($limit)->select();
    }
    
    /**
     * 获取诗词中的好字
     */
    public function get_good_chars($poetry_id) {
        $poetry = $this->get_detail($poetry_id);
        if (!$poetry) return array();
        
        // 提取诗词内容中的汉字
        $content = $poetry['content'];
        $chars = array();
        for ($i = 0; $i < mb_strlen($content); $i++) {
            $char = mb_substr($content, $i, 1);
            if ($this->is_good_name_char($char)) {
                $chars[] = $char;
            }
        }
        
        return array_unique($chars);
    }
    
    /**
     * 判断是否为适合起名的汉字
     */
    private function is_good_name_char($char) {
        // 常用起名汉字列表
        $good_chars = array(
            '博', '浩', '瀚', '晨', '轩', '泽', '睿', '哲', '明', '远',
            '涛', '杰', '豪', '俊', '伟', '勇', '志', '强', '刚', '磊',
            '涵', '清', '润', '渊', '淳', '瑶', '瑾', '璇', '琳', '琪',
            '宁', '安', '静', '怡', '悦', '欣', '乐', '嘉', '雅', '懿',
            '婷', '萱', '雯', '晴', '岚', '蕊', '菱', '菲', '苑', '芸',
            '梓', '桐', '桦', '枫', '松', '柏', '桂', '楠', '榆', '槐',
            '鹏', '鸿', '麟', '麒', '龙', '凤', '鸣', '翔', '鹤', '鸾',
            '颖', '灵', '敏', '慧', '倩', '妍', '媚', '娇', '黛', '燕',
            '紫', '翠', '羽', '珊', '婷', '兰', '梅', '竹', '菊', '芹'
        );
        
        return in_array($char, $good_chars);
    }
}
