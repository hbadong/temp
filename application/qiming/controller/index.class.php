<?php
/**
 * 起名系统首页控制器
 */

defined('IN_YZMPHP') or exit('Access Denied');

class index {
    
    /**
     * 首页
     */
    public function init() {
        $seo_title = '起名网 - 专注宝宝起名取名测名字平台';
        $keywords = '起名,宝宝起名,八字起名,诗词起名,姓名测试,公司起名,周易起名,康熙字典';
        $description = '起名网专注科学智能宝宝起名，测名字打分平台，结合传统国学文化的智能起名系统研发和起名学术探索交流，以"只为一个好名字"为宗旨，潜心研发，百次升级，千万级大数据分析，助您轻松起好名。';
        
        $action = 'init';
        
        include template('qiming', 'index');
    }
    
    /**
     * 宝宝起名页面
     */
    public function baobao() {
        $seo_title = '宝宝起名 - 起名网';
        $action = 'baobao';
        include template('qiming', 'baobao');
    }
    
    /**
     * 八字起名页面
     */
    public function bazi() {
        $seo_title = '八字起名 - 起名网';
        $action = 'bazi';
        include template('qiming', 'bazi');
    }
    
    /**
     * 诗词起名页面
     */
    public function shici() {
        $seo_title = '诗词起名 - 起名网';
        $action = 'shici';
        include template('qiming', 'shici');
    }
    
    /**
     * 姓名测试页面
     */
    public function ceshi() {
        $seo_title = '姓名测试 - 起名网';
        $action = 'ceshi';
        include template('qiming', 'ceshi');
    }
    
    /**
     * 周易起名页面
     */
    public function zhouyi() {
        $seo_title = '周易起名 - 起名网';
        $action = 'zhouyi';
        include template('qiming', 'zhouyi');
    }
    
    /**
     * 公司起名页面
     */
    public function gongsi() {
        $seo_title = '公司起名 - 起名网';
        $action = 'gongsi';
        include template('qiming', 'gongsi');
    }
    
    /**
     * 康熙字典页面
     */
    public function kxzd() {
        $seo_title = '康熙字典 - 起名网';
        $action = 'kxzd';
        include template('qiming', 'kxzd');
    }
    
    /**
     * 起名结果页
     */
    public function result() {
        $surname = isset($_GET['surname']) ? trim($_GET['surname']) : '';
        $gender = isset($_GET['gender']) ? intval($_GET['gender']) : 1;
        $birthdate = isset($_GET['birthdate']) ? trim($_GET['birthdate']) : '';
        $birthtime = isset($_GET['birthtime']) ? intval($_GET['birthtime']) : 0;
        
        if (empty($surname) || empty($birthdate)) {
            showmsg('缺少必要参数', 'stop');
        }
        
        // 计算八字
        $birth_year = date('Y', strtotime($birthdate));
        $birth_month = date('n', strtotime($birthdate));
        $birth_day = date('j', strtotime($birthdate));
        
        yzm_base::load_sys_class('bazi', '', 0);
        $bazi = new bazi();
        $bazi_result = $bazi->calculate($birth_year, $birth_month, $birth_day, $birthtime);
        $wuxing_count = $bazi->analyzeWuxing();
        
        // 计算五格
        $name = isset($_GET['name']) ? trim($_GET['name']) : '';
        if (!empty($name)) {
            yzm_base::load_sys_class('wuge', '', 0);
            $wuge = new wuge();
            $wuge_result = $wuge->calculate($surname, $name);
        }
        
        include template('qiming', 'result');
    }
    
    /**
     * 姓名测试结果
     */
    public function test_result() {
        $surname = isset($_GET['surname']) ? trim($_GET['surname']) : '';
        $name = isset($_GET['name']) ? trim($_GET['name']) : '';
        
        if (empty($surname) || empty($name)) {
            showmsg('请输入姓名', 'stop');
        }
        
        // 计算五格
        yzm_base::load_sys_class('wuge', '', 0);
        $wuge = new wuge();
        $result = $wuge->calculate($surname, $name);
        $level = $wuge->get_level($result['total_score']);
        
        include template('qiming', 'test_result');
    }
    
    /**
     * 诗词起名结果
     */
    public function shici_result() {
        $surname = isset($_GET['surname']) ? trim($_GET['surname']) : '';
        $gender = isset($_GET['gender']) ? intval($_GET['gender']) : 1;
        
        if (empty($surname)) {
            showmsg('请输入姓氏', 'stop');
        }
        
        // 加载起名引擎
        yzm_base::load_sys_class('name_engine', '', 0);
        $engine = new name_engine();
        
        // 从诗词中获取好字
        yzm_base::load_model('poetry', 'qiming', 0);
        $poetry_model = new poetry_model();
        $poetry_list = $poetry_model->get_random(1, 10); // 获取唐诗
        
        // 提取诗词中的好字
        $good_chars = array();
        foreach ($poetry_list as $poetry) {
            $chars = $poetry_model->get_good_chars($poetry['id']);
            $good_chars = array_merge($good_chars, $chars);
        }
        $good_chars = array_unique($good_chars);
        
        // 生成诗词风格的名字
        $names = $this->generate_poetry_names($surname, $good_chars, 12);
        
        $seo_title = '诗词起名结果 - 起名网';
        include template('qiming', 'shici_result');
    }
    
    /**
     * 周易起名结果
     */
    public function zhouyi_result() {
        $surname = isset($_GET['surname']) ? trim($_GET['surname']) : '';
        $gender = isset($_GET['gender']) ? intval($_GET['gender']) : 1;
        $birthdate = isset($_GET['birthdate']) ? trim($_GET['birthdate']) : '';
        $birthtime = isset($_GET['birthtime']) ? intval($_GET['birthtime']) : 0;
        
        if (empty($surname) || empty($birthdate)) {
            showmsg('缺少必要参数', 'stop');
        }
        
        // 计算八字
        $birth_year = date('Y', strtotime($birthdate));
        $birth_month = date('n', strtotime($birthdate));
        $birth_day = date('j', strtotime($birthdate));
        
        yzm_base::load_sys_class('bazi', '', 0);
        $bazi = new bazi();
        $bazi_result = $bazi->calculate($birth_year, $birth_month, $birth_day, $birthtime);
        
        // 计算周易卦象
        yzm_base::load_model('bagua', 'qiming', 0);
        $bagua_model = new bagua_model();
        $gua_index = (ord($surname) + $birth_year + $birth_month + $birth_day) % 64;
        if ($gua_index < 1) $gua_index = 1;
        $bagua = $bagua_model->get_detail($gua_index);
        
        // 加载起名引擎
        yzm_base::load_sys_class('name_engine', '', 0);
        $engine = new name_engine();
        
        // 分析五行需求
        $wuxing_count = $bazi->analyzeWuxing();
        $wuxing_need = $engine->analyze_bazi($birth_year, $birth_month, $birth_day, $birthtime);
        
        // 生成周易风格的名字
        $names = $engine->generate_names($surname, $gender, $wuxing_need, 12);
        
        $seo_title = '周易起名结果 - 起名网';
        include template('qiming', 'zhouyi_result');
    }
    
    /**
     * 公司起名结果
     */
    public function gongsi_result() {
        $founder_name = isset($_POST['founder_name']) ? trim($_POST['founder_name']) : '';
        $company_type = isset($_POST['company_type']) ? intval($_POST['company_type']) : 1;
        $industry = isset($_POST['industry']) ? intval($_POST['industry']) : 1;
        $name_style = isset($_POST['name_style']) ? intval($_POST['name_style']) : 0;
        $keywords = isset($_POST['keywords']) ? trim($_POST['keywords']) : '';
        
        if (empty($founder_name)) {
            showmsg('请填写创始人姓名', 'stop');
        }
        
        // 公司类型名称映射
        $company_types = array(
            1 => '科技有限公司', 2 => '实业有限公司', 3 => '贸易有限公司',
            4 => '投资有限公司', 5 => '咨询有限公司', 6 => '文化传媒有限公司',
            7 => '电子商务有限公司', 8 => '教育培训有限公司', 9 => '餐饮管理有限公司', 10 => '其他类型'
        );
        
        // 行业名称映射
        $industries = array(
            1 => '互联网/IT', 2 => '金融/投资', 3 => '制造业',
            4 => '贸易/零售', 5 => '教育培训', 6 => '医疗健康',
            7 => '餐饮/食品', 8 => '房地产/建筑', 9 => '文化/娱乐', 10 => '其他行业'
        );
        
        // 行业五行属性映射
        $industry_wuxing = array(
            1 => '火', 2 => '金', 3 => '土',
            4 => '金', 5 => '木', 6 => '木',
            7 => '火', 8 => '土', 9 => '火', 10 => '土'
        );
        
        // 生成公司名称
        $names = $this->generate_company_names($company_type, $industry, $name_style, $keywords);
        
        $seo_title = '公司起名结果 - 起名网';
        include template('qiming', 'gongsi_result');
    }
    
    /**
     * 生成公司名称
     */
    private function generate_company_names($company_type, $industry, $name_style, $keywords) {
        // 根据行业选择合适的汉字
        $chars_by_industry = array(
            1 => array('智', '云', '腾', '创', '新', '科', '技', '星', '光', '华'),
            2 => array('盛', '达', '泰', '宏', '伟', '诚', '信', '义', '隆', '昌'),
            3 => array('兴', '业', '盛', '隆', '鑫', '旺', '恒', '源', '荣', '昌'),
            4 => array('投', '资', '信', '达', '盛', '华', '金', '融', '宝', '源'),
            5 => array('博', '学', '思', '远', '文', '明', '智', '慧', '启', '迪'),
            6 => array('健', '康', '福', '寿', '宁', '安', '颐', '生', '众', '仁'),
            7 => array('香', '满', '缘', '福', '味', '轩', '庄', '园', '楼', '阁'),
            8 => array('置', '业', '地', '产', '楼', '宇', '宫', '殿', '府', '邸'),
            9 => array('华', '彩', '文', '艺', '星', '光', '梦', '幻', '族', '林'),
            10 => array('盛', '华', '祥', '瑞', '福', '顺', '达', '通', '广', '聚')
        );
        
        // 风格汉字
        $style_chars = array(
            1 => array('宇', '宙', '天', '地', '洪', '荒', '沧', '海', '腾', '飞'),
            2 => array('简', '悦', '朗', '明', '清', '新', '逸', '雅', '颂', '璟'),
            3 => array('古', '韵', '诗', '画', '琴', '棋', '书', '墨', '香', '斋'),
            4 => array('福', '禄', '寿', '喜', '祥', '瑞', '和', '顺', '昌', '盛'),
            5 => array('欧', '雅', '菲', '德', '美', '瑞', '英', '法', '韩', '日')
        );
        
        $chars = isset($chars_by_industry[$industry]) ? $chars_by_industry[$industry] : $chars_by_industry[1];
        
        if ($name_style > 0 && isset($style_chars[$name_style])) {
            $chars = array_merge($chars, $style_chars[$name_style]);
        }
        
        // 如果有关键词，添加关键词字符
        if (!empty($keywords)) {
            $keyword_chars = preg_split('/[,，\s]+/', $keywords);
            $chars = array_merge($chars, $keyword_chars);
        }
        
        $chars = array_unique($chars);
        
        // 生成名称组合
        $names = array();
        $prefixes = array('中', '华', '盛', '宏', '伟', '祥', '瑞', '金', '银', '宝', '福', '安', '新', '盈', '科', '智', '创', '飞', '腾', '达');
        
        foreach ($prefixes as $prefix) {
            foreach ($chars as $char) {
                $name = $prefix . $char;
                $names[] = array(
                    'name' => $name,
                    'wuxing' => $this->get_company_char_wuxing($char),
                    'industry_match' => rand(75, 98),
                    'yinyun_score' => rand(75, 95),
                    'meaning' => $this->get_company_name_meaning($name),
                    'level' => $this->get_company_name_level(rand(80, 98))
                );
            }
        }
        
        // 打乱顺序并返回前12个
        shuffle($names);
        return array_slice($names, 0, 12);
    }
    
    /**
     * 获取公司名称用字五行
     */
    private function get_company_char_wuxing($char) {
        $wuxing_map = array(
            '智' => '火', '云' => '水', '腾' => '火', '创' => '金', '新' => '金',
            '科' => '木', '技' => '木', '星' => '金', '光' => '火', '华' => '水',
            '盛' => '金', '达' => '火', '泰' => '火', '宏' => '水', '伟' => '土',
            '诚' => '金', '信' => '金', '义' => '木', '隆' => '火', '昌' => '火',
            '投' => '木', '资' => '金', '金' => '金', '融' => '火', '宝' => '火',
            '源' => '水', '博' => '水', '学' => '水', '思' => '金', '远' => '土',
            '文' => '水', '明' => '火', '慧' => '水', '启' => '木', '迪' => '火',
            '健' => '木', '康' => '木', '福' => '水', '寿' => '金', '宁' => '火',
            '安' => '土', '颐' => '土', '生' => '金', '众' => '火', '仁' => '金',
            '香' => '水', '满' => '水', '缘' => '土', '味' => '水', '轩' => '土',
            '庄' => '金', '园' => '土', '楼' => '木', '阁' => '木', '置' => '火',
            '业' => '木', '地' => '土', '产' => '土', '楼' => '木', '宇' => '土',
            '宫' => '木', '殿' => '火', '府' => '土', '邸' => '火', '彩' => '金',
            '艺' => '木', '梦' => '木', '幻' => '水', '族' => '木', '林' => '木',
            '中' => '火', '祥' => '金', '瑞' => '金', '银' => '金', '盈' => '水',
            '飞' => '水', '沧' => '水', '荒' => '水', '海' => '水', '天' => '火',
            '地' => '土', '洪' => '水', '简' => '木', '悦' => '金', '朗' => '火',
            '清' => '水', '逸' => '土', '雅' => '木', '颂' => '木', '璟' => '木',
            '古' => '木', '韵' => '土', '诗' => '金', '画' => '水', '琴' => '木',
            '棋' => '木', '书' => '土', '墨' => '土', '斋' => '火', '禄' => '火',
            '喜' => '水', '和' => '水', '顺' => '金', '通' => '火', '广' => '木',
            '聚' => '金', '欧' => '土', '雅' => '木', '菲' => '木', '德' => '火',
            '美' => '水', '英' => '木', '法' => '水', '韩' => '木', '日' => '火'
        );
        
        return isset($wuxing_map[$char]) ? $wuxing_map[$char] : '土';
    }
    
    /**
     * 获取公司名称寓意
     */
    private function get_company_name_meaning($name) {
        $meanings = array(
            '中' => '中正、稳重',
            '华' => '华丽、尊贵',
            '盛' => '兴盛、旺盛',
            '宏' => '宏大、宏伟',
            '伟' => '伟大、卓越',
            '祥' => '吉祥、瑞祥',
            '瑞' => '祥瑞、吉祥',
            '金' => '金色、财富',
            '银' => '银光、贵重',
            '宝' => '珍贵、宝物',
            '福' => '福气、吉祥',
            '安' => '平安、稳定',
            '新' => '创新、新兴',
            '盈' => '盈满、丰盛',
            '科' => '科技、科学',
            '智' => '智慧、聪明',
            '创' => '创造、创新',
            '飞' => '飞跃、发展',
            '腾' => '腾飞、上升',
            '达' => '通达、显达'
        );
        
        $first = mb_substr($name, 0, 1);
        $second = mb_substr($name, 1, 1);
        
        $m1 = isset($meanings[$first]) ? $meanings[$first] : '美好';
        $m2 = isset($meanings[$second]) ? $meanings[$second] : '吉祥';
        
        return '寓意' . $m1 . '、' . $m2;
    }
    
    /**
     * 获取公司名称等级
     */
    private function get_company_name_level($score) {
        if ($score >= 95) return '大吉';
        if ($score >= 85) return '吉';
        if ($score >= 75) return '中吉';
        return '一般';
    }
    
    /**
     * 生成诗词风格的名字
     */
    private function generate_poetry_names($surname, $chars, $limit) {
        $names = array();
        $len = count($chars);
        
        if ($len < 2) {
            // 如果没有足够的好字，使用默认字库
            $chars = array('梓', '轩', '墨', '涵', '瑶', '琪', '浩', '然', '明', '月', '云', '烟');
            $len = count($chars);
        }
        
        for ($i = 0; $i < $len && count($names) < $limit * 2; $i++) {
            for ($j = 0; $j < $len && count($names) < $limit * 2; $j++) {
                if ($i != $j) {
                    $name = $chars[$i] . $chars[$j];
                    // 计算五格评分
                    yzm_base::load_sys_class('wuge', '', 0);
                    $wuge = new wuge();
                    $wuge_result = $wuge->calculate($surname, $name);
                    $names[] = array(
                        'name' => $name,
                        'full_name' => $surname . $name,
                        'score' => $wuge_result['total_score'],
                        'level' => $wuge->get_level($wuge_result['total_score']),
                        'wuge' => $wuge_result,
                    );
                }
            }
        }
        
        // 按评分排序
        usort($names, function($a, $b) {
            return $b['score'] - $a['score'];
        });
        
        return array_slice($names, 0, $limit);
    }
}
