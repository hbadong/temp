<?php
/**
 * 八字计算类 - 起名系统核心算法
 * 
 * 用于计算生辰八字（年柱、月柱、日柱、时柱）及其五行属性
 */

defined('IN_YZMPHP') or exit('Access Denied');

class bazi {
    
    // 天干数组
    private static $tiangan = array('甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸');
    
    // 地支数组
    private static $dizhi = array('子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥');
    
    // 五行对应天干
    private static $tiangan_wuxing = array(
        '甲' => '木', '乙' => '木',
        '丙' => '火', '丁' => '火',
        '戊' => '土', '己' => '土',
        '庚' => '金', '辛' => '金',
        '壬' => '水', '癸' => '水'
    );
    
    // 五行对应地支
    private static $dizhi_wuxing = array(
        '子' => '水', '丑' => '土', '寅' => '木', '卯' => '木',
        '辰' => '土', '巳' => '火', '午' => '火', '未' => '土',
        '申' => '金', '酉' => '金', '戌' => '土', '亥' => '水'
    );
    
    // 地支对应生肖
    private static $zodiac = array(
        '子' => '鼠', '丑' => '牛', '寅' => '虎', '卯' => '兔',
        '辰' => '龙', '巳' => '蛇', '午' => '马', '未' => '羊',
        '申' => '猴', '酉' => '鸡', '戌' => '狗', '亥' => '猪'
    );
    
    // 月支地支
    private static $yuezhi = array('寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥', '子', '丑');
    
    // 八字结果
    private $year_gz = '';  // 年柱
    private $month_gz = ''; // 月柱
    private $day_gz = '';   // 日柱
    private $hour_gz = '';  // 时柱
    
    private $year_gan = '';  // 年干
    private $year_zhi = '';  // 年支
    private $month_gan = ''; // 月干
    private $month_zhi = ''; // 月支
    private $day_gan = '';   // 日干
    private $day_zhi = '';   // 日支
    private $hour_zhi = '';  // 时支
    private $hour_gan = '';  // 时干
    
    private $year_wuxing = '';  // 年柱五行
    private $month_wuxing = ''; // 月柱五行
    private $day_wuxing = '';   // 日柱五行
    private $hour_wuxing = '';  // 时柱五行
    
    /**
     * 构造函数
     */
    public function __construct() {
        
    }
    
    /**
     * 计算八字
     * @param int $year 年份
     * @param int $month 月份
     * @param int $day 日期
     * @param int $hour 小时 (0-23)
     * @return array 八字信息
     */
    public function calculate($year, $month, $day, $hour) {
        // 计算年柱
        $this->calcYearGz($year);
        
        // 计算月柱
        $this->calcMonthGz($year, $month);
        
        // 计算日柱
        $this->calcDayGz($year, $month, $day);
        
        // 计算时柱
        $this->calcHourGz($hour);
        
        return $this->getResult();
    }
    
    /**
     * 计算年柱
     */
    private function calcYearGz($year) {
        $offset = $year - 1984;  // 以1984年为基准（甲子年）
        $gan_index = ($offset % 10 + 10) % 10;
        $zhi_index = ($offset % 12 + 12) % 12;
        
        $this->year_gan = self::$tiangan[$gan_index];
        $this->year_zhi = self::$dizhi[$zhi_index];
        $this->year_gz = $this->year_gan . $this->year_zhi;
    }
    
    /**
     * 计算月柱
     */
    private function calcMonthGz($year, $month) {
        // 月干计算口诀：甲己之年丙作首，乙庚之年戊为头
        // 丙辛必定寻庚起，丁壬壬位顺行流，戊癸之年何方发，甲寅之上好追求
        $year_gan = $this->year_gan;
        
        // 确定月干基数
        $month_gan_base = 0;
        switch ($year_gan) {
            case '甲': case '己': $month_gan_base = 3; break;  // 丙
            case '乙': case '庚': $month_gan_base = 5; break;  // 戊
            case '丙': case '辛': $month_gan_base = 7; break;  // 庚
            case '丁': case '壬': $month_gan_base = 9; break;  // 壬
            case '戊': case '癸': $month_gan_base = 1; break;  // 甲
        }
        
        // 月份从1开始，但数组从0开始
        $month_index = $month - 1;
        $gan_index = ($month_gan_base + $month_index - 1) % 10;
        $zhi_index = $month_index;
        
        $this->month_gan = self::$tiangan[$gan_index];
        $this->month_zhi = self::$yuezhi[$zhi_index];
        $this->month_gz = $this->month_gan . $this->month_zhi;
    }
    
    /**
     * 计算日柱（使用蔡勒公式）
     */
    private function calcDayGz($year, $month, $day) {
        // 蔡勒公式计算儒略日
        if ($month <= 2) {
            $year -= 1;
            $month += 12;
        }
        
        $A = floor($year / 100);
        $B = 2 - $A + floor($A / 4);
        
        $jd = floor(365.25 * ($year + 4716)) + floor(30.6001 * ($month + 1)) + $day + $B - 1524.5;
        
        // 计算总天数
        $days = floor($jd - 2451545) + 1;
        
        // 日干支计算（以1984年1月1日为甲子日）
        // 1984年1月1日是甲子日
        $gan_index = ($days % 10 + 10) % 10;
        $zhi_index = ($days % 12 + 12) % 12;
        
        $this->day_gan = self::$tiangan[$gan_index];
        $this->day_zhi = self::$dizhi[$zhi_index];
        $this->day_gz = $this->day_gan . $this->day_zhi;
    }
    
    /**
     * 计算时柱
     */
    private function calcHourGz($hour) {
        // 时支计算：23-1子时，1-3丑时，3-5寅时...
        $zhi_index = (floor(($hour + 1) / 2) % 12 + 12) % 12;
        $this->hour_zhi = self::$dizhi[$zhi_index];
        
        // 日干决定时干
        // 口诀：甲己还生甲，乙庚丙作初，丙辛从戊起，丁壬庚子居，戊癸何方发，壬子是真途
        $day_gan = $this->day_gan;
        
        $hour_gan_base = 0;
        switch ($day_gan) {
            case '甲': case '己': $hour_gan_base = 0; break;  // 甲
            case '乙': case '庚': $hour_gan_base = 2; break;  // 丙
            case '丙': case '辛': $hour_gan_base = 4; break;  // 戊
            case '丁': case '壬': $hour_gan_base = 6; break;  // 庚
            case '戊': case '癸': $hour_gan_base = 8; break;  // 壬
        }
        
        $gan_index = ($hour_gan_base + $zhi_index) % 10;
        $hour_gan = self::$tiangan[$gan_index];
        
        $this->hour_gz = $hour_gan . $this->hour_zhi;
    }
    
    /**
     * 获取计算结果
     */
    public function getResult() {
        $this->year_wuxing = self::$tiangan_wuxing[$this->year_gan];
        $this->month_wuxing = self::$tiangan_wuxing[$this->month_gan];
        $this->day_wuxing = self::$tiangan_wuxing[$this->day_gan];
        $this->hour_wuxing = self::$dizhi_wuxing[$this->hour_zhi];
        
        return array(
            'year_gz' => $this->year_gz,
            'month_gz' => $this->month_gz,
            'day_gz' => $this->day_gz,
            'hour_gz' => $this->hour_gz,
            'year_gan' => $this->year_gan,
            'year_zhi' => $this->year_zhi,
            'month_gan' => $this->month_gan,
            'month_zhi' => $this->month_zhi,
            'day_gan' => $this->day_gan,
            'day_zhi' => $this->day_zhi,
            'hour_zhi' => $this->hour_zhi,
            'zodiac' => isset(self::$zodiac[$this->year_zhi]) ? self::$zodiac[$this->year_zhi] : '',
            'year_wuxing' => self::$tiangan_wuxing[$this->year_gan],
            'month_wuxing' => self::$tiangan_wuxing[$this->month_gan],
            'day_wuxing' => self::$tiangan_wuxing[$this->day_gan],
            'hour_wuxing' => self::$dizhi_wuxing[$this->hour_zhi],
        );
    }
    
    /**
     * 分析五行
     */
    public function analyzeWuxing() {
        $wuxing_count = array(
            '木' => 0,
            '火' => 0,
            '土' => 0,
            '金' => 0,
            '水' => 0
        );
        
        $wuxing_count[$this->year_wuxing]++;
        $wuxing_count[$this->month_wuxing]++;
        $wuxing_count[$this->day_wuxing]++;
        $wuxing_count[$this->hour_wuxing]++;
        
        return $wuxing_count;
    }
    
    /**
     * 获取天干列表
     */
    public static function getTiangan() {
        return self::$tiangan;
    }
    
    /**
     * 获取地支列表
     */
    public static function getDizhi() {
        return self::$dizhi;
    }
    
    /**
     * 获取天干对应的五行
     */
    public static function getTianganWuxing($gan) {
        return isset(self::$tiangan_wuxing[$gan]) ? self::$tiangan_wuxing[$gan] : '';
    }
    
    /**
     * 获取地支对应的五行
     */
    public static function getDizhiWuxing($zhi) {
        return isset(self::$dizhi_wuxing[$zhi]) ? self::$dizhi_wuxing[$zhi] : '';
    }
}
