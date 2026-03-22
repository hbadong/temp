<?php
/**
 * 起名系统API接口控制器
 */

defined('IN_YZMPHP') or exit('Access Denied');

class api {
    
    /**
     * 获取黄历数据
     */
    public function huangli() {
        $date = isset($_GET['date']) ? trim($_GET['date']) : date('Y-m-d');
        
        // 示例黄历数据（实际项目中应从数据库获取）
        $data = array(
            'date' => $date,
            'lunar' => '农历' . $this->get_lunar_date($date),
            'zodiac' => $this->get_zodiac($date),
            'yi' => '嫁娶,祭祀,开光,祈福,求嗣,出行',
            'ji' => '动土,伐木,安葬,行丧',
            'caishen' => '东北',
            'xishen' => '西北',
            'fushen' => '西南',
            'jishi' => '子寅卯巳申亥',
        );
        
        echo json_encode(array('status' => 1, 'data' => $data));
    }
    
    /**
     * 获取热门排行
     */
    public function ranking() {
        $type = isset($_GET['type']) ? trim($_GET['type']) : 'boy-char';
        
        $data = array();
        switch ($type) {
            case 'boy-char':
                $data = $this->get_boy_char_ranking();
                break;
            case 'girl-char':
                $data = $this->get_girl_char_ranking();
                break;
            case 'boy-name':
                $data = $this->get_boy_name_ranking();
                break;
            case 'girl-name':
                $data = $this->get_girl_name_ranking();
                break;
        }
        
        echo json_encode(array('status' => 1, 'data' => $data));
    }
    
    /**
     * 搜索汉字
     */
    public function search_char() {
        $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
        
        if (empty($keyword)) {
            echo json_encode(array('status' => 0, 'message' => '关键词不能为空'));
            return;
        }
        
        // 示例数据（实际项目中应从数据库搜索）
        $data = array(
            array('char' => '鑫', 'pinyin' => 'xīn', 'bihua' => 24, 'wuxing' => '金', 'meaning' => '财富兴盛'),
            array('char' => '森', 'pinyin' => 'sēn', 'bihua' => 12, 'wuxing' => '木', 'meaning' => '树木众多'),
            array('char' => '淼', 'pinyin' => 'miǎo', 'bihua' => 12, 'wuxing' => '水', 'meaning' => '水大'),
        );
        
        echo json_encode(array('status' => 1, 'data' => $data));
    }
    
    /**
     * 姓名测试
     */
    public function name_test() {
        $surname = isset($_GET['surname']) ? trim($_GET['surname']) : '';
        $name = isset($_GET['name']) ? trim($_GET['name']) : '';
        
        if (empty($surname) || empty($name)) {
            echo json_encode(array('status' => 0, 'message' => '姓名不能为空'));
            return;
        }
        
        yzm_base::load_sys_class('wuge', '', 0);
        $wuge = new wuge();
        $result = $wuge->calculate($surname, $name);
        
        echo json_encode(array('status' => 1, 'data' => $result));
    }
    
    /**
     * 八字计算
     */
    public function bazi() {
        $year = isset($_GET['year']) ? intval($_GET['year']) : 0;
        $month = isset($_GET['month']) ? intval($_GET['month']) : 0;
        $day = isset($_GET['day']) ? intval($_GET['day']) : 0;
        $hour = isset($_GET['hour']) ? intval($_GET['hour']) : 0;
        
        if (!$year || !$month || !$day) {
            echo json_encode(array('status' => 0, 'message' => '参数不完整'));
            return;
        }
        
        yzm_base::load_sys_class('bazi', '', 0);
        $bazi = new bazi();
        $result = $bazi->calculate($year, $month, $day, $hour);
        
        echo json_encode(array('status' => 1, 'data' => $result));
    }
    
    // ==================== 私有方法 ====================
    
    private function get_lunar_date($date) {
        return '二月初四';
    }
    
    private function get_zodiac($date) {
        $year = date('Y', strtotime($date));
        $zodiac = array('鼠', '牛', '虎', '兔', '龙', '蛇', '马', '羊', '猴', '鸡', '狗', '猪');
        return $zodiac[($year - 1900) % 12];
    }
    
    private function get_boy_char_ranking() {
        return array(
            array('char' => '圣', 'pinyin' => 'shèng', 'meaning' => '吉祥、智慧'),
            array('char' => '杰', 'pinyin' => 'jié', 'meaning' => '杰出、才能'),
            array('char' => '浩', 'pinyin' => 'hào', 'meaning' => '浩大、广阔'),
            array('char' => '旭', 'pinyin' => 'xù', 'meaning' => '旭日、朝阳'),
            array('char' => '尧', 'pinyin' => 'yáo', 'meaning' => '高远、圣明'),
            array('char' => '俊', 'pinyin' => 'jùn', 'meaning' => '俊秀、美好'),
        );
    }
    
    private function get_girl_char_ranking() {
        return array(
            array('char' => '瑾', 'pinyin' => 'jǐn', 'meaning' => '美德、美玉'),
            array('char' => '楠', 'pinyin' => 'nán', 'meaning' => '木材、温暖'),
            array('char' => '莹', 'pinyin' => 'yíng', 'meaning' => '光洁、透明'),
            array('char' => '雪', 'pinyin' => 'xuě', 'meaning' => '纯洁、雪花'),
            array('char' => '晗', 'pinyin' => 'hán', 'meaning' => '天将明'),
            array('char' => '琴', 'pinyin' => 'qín', 'meaning' => '乐器、优雅'),
        );
    }
    
    private function get_boy_name_ranking() {
        return array(
            array('name' => '颜豪', 'pinyin' => 'yán háo'),
            array('name' => '颢宁', 'pinyin' => 'hào níng'),
            array('name' => '璟霆', 'pinyin' => 'jǐng tíng'),
            array('name' => '梓翔', 'pinyin' => 'zǐ xiáng'),
            array('name' => '铭轩', 'pinyin' => 'míng xuān'),
        );
    }
    
    private function get_girl_name_ranking() {
        return array(
            array('name' => '颜菲', 'pinyin' => 'yán fēi'),
            array('name' => '宁汐', 'pinyin' => 'níng xī'),
            array('name' => '宇婷', 'pinyin' => 'yǔ tíng'),
            array('name' => '梓怡', 'pinyin' => 'zǐ yí'),
            array('name' => '熙媛', 'pinyin' => 'xī yuán'),
        );
    }
}
