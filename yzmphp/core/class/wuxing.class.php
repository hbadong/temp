<?php
/**
 * 五行分析类 - 起名系统核心算法
 * 
 * 用于分析汉字五行属性、五行相生相克、用神喜神确定
 */

defined('IN_YZMPHP') or exit('Access Denied');

class wuxing {
    
    // 五行常量
    const WUXING_JIN = 1;  // 金
    const WUXING_MU = 2;    // 木
    const WUXING_SHUI = 3;   // 水
    const WUXING_HUO = 4;   // 火
    const WUXING_TU = 5;    // 土
    
    // 五行名称
    private static $wuxing_names = array(
        1 => '金',
        2 => '木',
        3 => '水',
        4 => '火',
        5 => '土'
    );
    
    // 五行相生相克
    // 相生：木生火 -> 火生土 -> 土生金 -> 金生水 -> 水生木
    // 相克：木克土 -> 土克水 -> 水克火 -> 火克金 -> 金克木
    private static $wuxing_xiangsheng = array(
        2 => 4,  // 木生火
        4 => 5,  // 火生土
        5 => 1,  // 土生金
        1 => 3,  // 金生水
        3 => 2,  // 水生木
    );
    
    private static $wuxing_xiangke = array(
        2 => 5,  // 木克土
        5 => 3,  // 土克水
        3 => 4,  // 水克火
        4 => 1,  // 火克金
        1 => 2,  // 金克木
    );
    
    // 部首偏旁五行归属
    private static $bushou_wuxing = array(
        // 金部首
        '金' => self::WUXING_JIN,
        '钅' => self::WUXING_JIN,
        '刂' => self::WUXING_JIN,
        '刀' => self::WUXING_JIN,
        '戈' => self::WUXING_JIN,
        '弓' => self::WUXING_JIN,
        '矛' => self::WUXING_JIN,
        '矢' => self::WUXING_JIN,
        '殳' => self::WUXING_JIN,
        
        // 木部首
        '木' => self::WUXING_MU,
        '艹' => self::WUXING_MU,
        '卄' => self::WUXING_MU,
        '耒' => self::WUXING_MU,
        '网' => self::WUXING_MU,
        
        // 水部首
        '水' => self::WUXING_SHUI,
        '氵' => self::WUXING_SHUI,
        '冫' => self::WUXING_SHUI,
        '冰' => self::WUXING_SHUI,
        '雨' => self::WUXING_SHUI,
        '冖' => self::WUXING_SHUI,
        
        // 火部首
        '火' => self::WUXING_HUO,
        '灬' => self::WUXING_HUO,
        '日' => self::WUXING_HUO,
        'lightning' => self::WUXING_HUO,
        '月' => self::WUXING_HUO,
        
        // 土部首
        '土' => self::WUXING_TU,
        '玉' => self::WUXING_TU,
        '王' => self::WUXING_TU,
        '比' => self::WUXING_TU,
        '瓦' => self::WUXING_TU,
        '皿' => self::WUXING_TU,
        '血' => self::WUXING_TU,
    );
    
    /**
     * 获取汉字五行属性
     * @param string $char 汉字
     * @return int 五行编号(1-5)
     */
    public function get_char_wuxing($char) {
        // 首先检查部首偏旁
        $len = mb_strlen($char, 'utf-8');
        if ($len > 0) {
            // 获取最后一个字（通常是形声字的声旁）
            $last_char = mb_substr($char, -1, 1, 'utf-8');
            $first_char = mb_substr($char, 0, 1, 'utf-8');
            
            // 检查第一个字是否是部首
            if (isset(self::$bushou_wuxing[$first_char])) {
                return self::$bushou_wuxing[$first_char];
            }
            
            // 检查最后一个字
            if (isset(self::$bushou_wuxing[$last_char])) {
                return self::$bushou_wuxing[$last_char];
            }
        }
        
        // 根据字形判断
        return $this->guess_wuxing_by_shape($char);
    }
    
    /**
     * 根据字形猜测五行
     */
    private function guess_wuxing_by_shape($char) {
        // 圆形、方形多为土
        if ($this->is_circle_shape($char)) {
            return self::WUXING_TU;
        }
        
        // 尖锐形状为金
        if ($this->is_sharp_shape($char)) {
            return self::WUXING_JIN;
        }
        
        // 直线性、生长性为木
        if ($this->is_vertical_shape($char)) {
            return self::WUXING_MU;
        }
        
        // 流动形状为水
        if ($this->is_flowing_shape($char)) {
            return self::WUXING_SHUI;
        }
        
        // 燃烧、红色为火
        if ($this->is_fire_shape($char)) {
            return self::WUXING_HUO;
        }
        
        // 默认返回土
        return self::WUXING_TU;
    }
    
    private function is_circle_shape($char) {
        return false;
    }
    
    private function is_sharp_shape($char) {
        return false;
    }
    
    private function is_vertical_shape($char) {
        return false;
    }
    
    private function is_flowing_shape($char) {
        return false;
    }
    
    private function is_fire_shape($char) {
        return false;
    }
    
    /**
     * 获取五行名称
     */
    public function get_wuxing_name($wuxing) {
        return isset(self::$wuxing_names[$wuxing]) ? self::$wuxing_names[$wuxing] : '未知';
    }
    
    /**
     * 分析五行相生
     * @param int $from 来源五行
     * @param int $to 目标五行
     * @return bool 是否相生
     */
    public function is_xiangsheng($from, $to) {
        return isset(self::$wuxing_xiangsheng[$from]) && self::$wuxing_xiangsheng[$from] == $to;
    }
    
    /**
     * 分析五行相克
     * @param int $from 来源五行
     * @param int $to 目标五行
     * @return bool 是否相克
     */
    public function is_xiangke($from, $to) {
        return isset(self::$wuxing_xiangke[$from]) && self::$wuxing_xiangke[$from] == $to;
    }
    
    /**
     * 计算五行强弱
     * @param array $wuxing_count 五行计数数组
     * @return array 分析结果
     */
    public function analyze_strength($wuxing_count) {
        $total = array_sum($wuxing_count);
        if ($total == 0) return array('strong' => array(), 'weak' => array());
        
        $avg = $total / 5;
        $strong = array();
        $weak = array();
        
        foreach ($wuxing_count as $wx => $count) {
            if ($count > $avg + 1) {
                $strong[] = $wx;
            } elseif ($count < $avg - 1 || $count == 0) {
                $weak[] = $wx;
            }
        }
        
        return array('strong' => $strong, 'weak' => $weak);
    }
    
    /**
     * 确定用神（需要补的五行）
     * @param array $wuxing_count 五行计数
     * @return array 需要补充的五行列表
     */
    public function get_yongshen($wuxing_count) {
        $analysis = $this->analyze_strength($wuxing_count);
        return $analysis['weak'];
    }
    
    /**
     * 获取适合的名字五行组合
     * @param array $need_wuxing 需要补充的五行
     * @return array 推荐的五行组合
     */
    public function get_recommended_wuxing($need_wuxing) {
        $recommended = array();
        
        foreach ($need_wuxing as $wx) {
            // 用神需要生扶
            if (isset(self::$wuxing_xiangsheng[$wx])) {
                $recommended[] = self::$wuxing_xiangsheng[$wx];  // 被生者
            }
            if (array_search($wx, self::$wuxing_xiangsheng)) {
                $recommended[] = $wx;  // 生助者
            }
        }
        
        return array_unique($recommended);
    }
    
    /**
     * 评估姓名五行与八字的匹配度
     * @param array $name_wuxing 名字五行数组
     * @param array $bazi_wuxing 八字五行数组
     * @return int 匹配度分数(0-100)
     */
    public function evaluate_match($name_wuxing, $bazi_wuxing) {
        $score = 50;  // 基础分
        
        foreach ($name_wuxing as $wx) {
            if (in_array($wx, $bazi_wuxing['need'])) {
                $score += 15;  // 补了需要的五行
            }
            if (in_array($wx, $bazi_wuxing['strong'])) {
                $score -= 10;  // 增强了本来的强项
            }
        }
        
        return max(0, min(100, $score));
    }
}
