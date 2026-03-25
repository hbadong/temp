<?php
/**
 * 黄历模型 - 起名系统数据模型层
 */

defined('IN_YZMPHP') or exit('Access Denied');

class horoscope_model {
    
    private $table = 'yzm_horoscope';
    
    /**
     * 获取指定日期的黄历
     */
    public function get_by_date($date) {
        return D($this->table)->where(array('date' => $date))->find();
    }
    
    /**
     * 获取今日黄历
     */
    public function get_today() {
        $today = date('Y-m-d');
        $data = $this->get_by_date($today);
        
        if (!$data) {
            // 返回默认数据
            return $this->get_default();
        }
        
        return $data;
    }
    
    /**
     * 获取默认黄历数据
     */
    private function get_default() {
        $weekday = array('星期日', '星期一', '星期二', '星期三', '星期四', '星期五', '星期六');
        $zodiac = array('鼠', '牛', '虎', '兔', '龙', '蛇', '马', '羊', '猴', '鸡', '狗', '猪');
        
        $today = date('Y-m-d');
        $year = date('Y');
        $month = date('n');
        $day = date('j');
        $week = date('w');
        
        return array(
            'date' => $today,
            'lunar_date' => '农历' . $this->get_lunar($month, $day),
            'weekday' => $weekday[$week],
            'zodiac' => $zodiac[($year - 1900) % 12],
            'yi' => '嫁娶,祭祀,开光,祈福,求嗣,出行,移徙,入宅',
            'ji' => '动土,伐木,安葬,行丧,破土,动土',
            'caishen' => '东北',
            'xishen' => '西北',
            'fushen' => '西南',
            'jishi' => '子寅卯巳申亥',
            'chongsha' => '冲牛',
        );
    }
    
    /**
     * 获取农历日期（简化版）
     */
    private function get_lunar($month, $day) {
        $months = array('正', '二', '三', '四', '五', '六', '七', '八', '九', '十', '冬', '腊');
        $days = array('初一', '初二', '初三', '初四', '初五', '初六', '初七', '初八', '初九', '初十',
            '十一', '十二', '十三', '十四', '十五', '十六', '十七', '十八', '十九', '二十',
            '廿一', '廿二', '廿三', '廿四', '廿五', '廿六', '廿七', '廿八', '廿九', '三十');
        
        $m = isset($months[$month - 1]) ? $months[$month - 1] : $months[0];
        $d = isset($days[$day - 1]) ? $days[$day - 1] : $days[0];
        
        return $m . '月' . $d;
    }
}
