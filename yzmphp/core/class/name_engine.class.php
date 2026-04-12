<?php
/**
 * 起名引擎类 - 起名系统核心业务逻辑
 * 
 * 整合八字计算、五格数理、五行分析，生成候选姓名
 */

defined('IN_YZMPHP') or exit('Access Denied');

class name_engine {
    
    private $wuge = null;
    private $wuxing = null;
    private $bihua_data = array();
    
    public function __construct() {
        yzm_base::load_sys_class('wuge', '', 0);
        yzm_base::load_sys_class('wuxing', '', 0);
        $this->wuge = new wuge();
        $this->wuxing = new wuxing();
    }
    
    /**
     * 生成候选姓名
     * @param string $surname 姓氏
     * @param int $gender 性别(1男2女)
     * @param array $birthinfo 出生信息
     * @param int $limit 返回数量
     * @return array 候选姓名列表
     */
    public function generate_names($surname, $gender, $birthinfo, $limit = 20) {
        $bazi_result = isset($birthinfo['bazi']) ? $birthinfo['bazi'] : null;
        $wuxing_need = isset($birthinfo['wuxing_need']) ? $birthinfo['wuxing_need'] : array();
        
        // 获取适合的汉字列表
        $chars = $this->get_suitable_chars($wuxing_need, $gender, $limit * 3);
        
        // 组合生成姓名
        $names = $this->combine_names($surname, $chars, $limit);
        
        // 评估每个姓名
        $result = array();
        foreach ($names as $name) {
            $eval = $this->evaluate_name($name, $bazi_result);
            $eval['name'] = $name;
            $result[] = $eval;
        }
        
        // 按评分排序
        usort($result, function($a, $b) {
            return $b['total_score'] - $a['total_score'];
        });
        
        return array_slice($result, 0, $limit);
    }
    
    /**
     * 获取适合的汉字
     */
    private function get_suitable_chars($wuxing_need, $gender, $limit) {
        // 基础常用字库
        $common_chars = $this->get_common_chars();
        
        // 根据五行筛选
        if (!empty($wuxing_need)) {
            $recommended_wuxing = $this->wuxing->get_recommended_wuxing($wuxing_need);
            $filtered = array();
            foreach ($common_chars as $char) {
                $char_wx = $this->wuxing->get_char_wuxing($char);
                if (in_array($char_wx, $recommended_wuxing)) {
                    $filtered[] = $char;
                }
            }
            if (count($filtered) >= $limit / 2) {
                $common_chars = $filtered;
            }
        }
        
        return array_slice($common_chars, 0, $limit);
    }
    
    /**
     * 基础常用汉字库
     */
    private function get_common_chars() {
        return array(
            '博', '浩', '瀚', '晨', '轩', '泽', '睿', '哲', '明', '远',
            '涛', '杰', '豪', '俊', '伟', '勇', '志', '强', '刚', '磊',
            '涵', '清', '洁', '润', '渊', '淳', '波', '涛', '洁',
            '瑶', '瑾', '璇', '琳', '琪', '珊', '珠', '珍', '玉', '琳',
            '宁', '安', '静', '怡', '悦', '欣', '乐', '嘉', '雅', '懿',
            '婷', '萱', '雯', '晴', '岚', '蕊', '菱', '菲', '苑', '芸',
            '梓', '桐', '桦', '枫', '松', '柏', '桂', '楠', '榆', '槐',
            '鹏', '鸿', '麟', '麒', '龙', '凤', '鸣', '翔', '鹤', '鸾',
            '颖', '灵', '敏', '慧', '倩', '妍', '媚', '娇', '黛', '燕',
            '紫', '翠', '羽', '翎', '珊', '婷', '兰', '梅', '竹', '菊',
        );
    }
    
    /**
     * 组合姓名
     */
    private function combine_names($surname, $chars, $limit) {
        $names = array();
        $len = count($chars);
        
        for ($i = 0; $i < $len && count($names) < $limit * 2; $i++) {
            for ($j = 0; $j < $len && count($names) < $limit * 2; $j++) {
                if ($i != $j) {
                    $names[] = $chars[$i] . $chars[$j];
                }
            }
        }
        
        // 打乱顺序
        shuffle($names);
        
        return array_slice($names, 0, $limit);
    }
    
    /**
     * 评估姓名
     */
    private function evaluate_name($name, $bazi_result) {
        // 计算五格
        $wuge_result = $this->wuge->calculate('', $name);
        
        // 基础分
        $score = $wuge_result['total_score'];
        
        // 五行加分
        $wuxing_bonus = 0;
        if ($bazi_result && isset($bazi_result['wuxing_count'])) {
            foreach (mb_str_split($name) as $char) {
                $char_wx = $this->wuxing->get_char_wuxing($char);
                if (in_array($char_wx, $bazi_result['wuxing_need'])) {
                    $wuxing_bonus += 10;
                }
            }
        }
        
        $total_score = min(100, $score + $wuxing_bonus);
        
        return array(
            'name' => $name,
            'wuge' => $wuge_result,
            'wuxing' => $this->wuxing->get_char_wuxing($name),
            'total_score' => $total_score,
            'level' => $this->wuge->get_level($total_score),
        );
    }
    
    /**
     * 分析八字并返回五行需求
     */
    public function analyze_bazi($year, $month, $day, $hour) {
        yzm_base::load_sys_class('bazi', '', 0);
        $bazi = new bazi();
        $result = $bazi->calculate($year, $month, $day, $hour);
        $wuxing_count = $bazi->analyzeWuxing();
        $wuxing_need = $this->wuxing->get_yongshen($wuxing_count);
        
        return array(
            'bazi' => $result,
            'wuxing_count' => $wuxing_count,
            'wuxing_need' => $wuxing_need,
        );
    }
}
