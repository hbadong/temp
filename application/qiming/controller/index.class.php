<?php
/**
 * 起名系统首页控制器
 */

defined('IN_YZMPHP') or exit('Access Den Den');

class index {
    
    /**
     * 首页
     */
    public function init() {
        $seo_title = '起名网 - 专注宝宝起名取名测名字平台';
        $keywords = '起名,宝宝起名,八字起名,诗词起名,姓名测试,公司起名,周易起名,康熙字典';
        $description = '起名网专注科学智能宝宝起名，测名字打分平台，结合传统国学文化的智能起名系统研发和起名学术探索交流，以"只为一个好名字"为宗旨，潜心研发，百次升级，千万级大数据分析，助您轻松起好名。';
        
        $today = date('Y-m-d');
        $horoscope = $this->get_horoscope($today);
        $rankings = $this->get_rankings();
        $recent_searches = $this->get_recent_searches();
        
        include template('qiming', 'index');
    }
    
    /**
     * 获取当日黄历
     */
    public function get_horoscope($date = '') {
        if (empty($date)) {
            $date = date('Y-m-d');
        }
        
        yzm_base::load_model('horoscope', 'qiming', 0);
        $horoscope_model = new horoscope_model();
        return $horoscope_model->get_by_date($date);
    }
    
    /**
     * 获取热门排行
     */
    public function get_rankings() {
        yzm_base::load_model('ranking_model', 'qiming', 0);
        $ranking_model = new ranking_model();
        
        return array(
            'boy_chars' => $ranking_model->get_by_type('boy-char', 30),
            'girl_chars' => $ranking_model->get_by_type('girl-char', 30),
            'boy_names' => $ranking_model->get_by_type('boy-name', 30),
            'girl_names' => $ranking_model->get_by_type('girl-name', 30),
        );
    }
    
    /**
     * 获取最近搜索的姓名
     */
    public function get_recent_searches($limit = 12) {
        yzm_base::load_model('character_model', 'qiming', 0);
        $character_model = new character_model();
        return $character_model->get_recent_searches($limit);
    }
    
    /**
     * 搜索功能
     */
    public function search() {
        $k = isset($_GET['k']) ? safe_replace(trim($_GET['k'])) : '';
        $search_type = isset($_GET['type']) ? trim($_GET['type']) : 'all';
        
        $seo_title = '搜索结果 - ' . $k . ' - 起名网';
        
        $results = array();
        if (!empty($k)) {
            yzm_base::load_model('character_model', 'qiming', 0);
            $character_model = new character_model();
            
            if ($search_type == 'char' || $search_type == 'all') {
                $results['chars'] = $character_model->search_chars($k, 20);
            }
            
            if ($search_type == 'name' || $search_type == 'all') {
                $results['names'] = $character_model->search_names($k, 20);
            }
        }
        
        include template('qiming', 'search');
    }
    
    /**
     * 宝宝起名页面
     */
    public function baobao() {
        $seo_title = '宝宝起名 - 起名网';
        $keywords = '宝宝起名,婴儿起名,取名大全';
        $description = '专业宝宝起名服务，基于AI技术和大数据，为您的宝宝取一个吉祥好听的名字';
        
        $recent_articles = $this->get_articles_by_cat('baobao', 6);
        include template('qiming', 'baobao');
    }
    
    /**
     * 八字起名页面
     */
    public function bazi() {
        $seo_title = '八字起名 - 起名网';
        $keywords = '八字起名,生辰八字起名';
        $description = '汇聚多位国内权威易学大师，以深厚经验精准解析八字，量身打造帮扶一生的优质好名';
        
        $recent_articles = $this->get_articles_by_cat('bazi', 6);
        include template('qiming', 'bazi');
    }
    
    /**
     * 诗词起名页面
     */
    public function shici() {
        $seo_title = '诗词起名 - 起名网';
        $keywords = '诗词起名,唐诗起名,宋词起名,诗经起名';
        $description = '结合孩子出生信息和父母期盼，从二十多万诗词古文中取字，确保每个名字意蕴优美、诗情画意';
        
        $recent_articles = $this->get_articles_by_cat('shici', 6);
        include template('qiming', 'shici');
    }
    
    /**
     * 姓名测试页面
     */
    public function ceshi() {
        $seo_title = '姓名测试 - 起名网';
        $keywords = '姓名测试,名字测试,姓名打分';
        $description = '专业的姓名测试评分系统，根据五格数理和音韵学为您分析姓名的好坏';
        
        include template('qiming', 'ceshi');
    }
    
    /**
     * 周易起名页面
     */
    public function zhouyi() {
        $seo_title = '周易起名 - 起名网';
        $keywords = '周易起名,易经起名';
        $description = '汲取千年国学智慧，融汇《周易》精髓，为您提供文化深厚、寓意吉祥的好名字';
        
        $recent_articles = $this->get_articles_by_cat('zhouyi', 6);
        include template('qiming', 'zhouyi');
    }
    
    /**
     * 公司起名页面
     */
    public function gongsi() {
        $seo_title = '公司起名 - 起名网';
        $keywords = '公司起名,企业起名,品牌命名';
        $description = '资深命名专家与品牌策划大师联手打造，为企业量身定制独特专属好名';
        
        include template('qiming', 'gongsi');
    }
    
    /**
     * 康熙字典页面
     */
    public function kxzd() {
        $seo_title = '康熙字典 - 起名网';
        $keywords = '康熙字典,汉字查询,拼音查字';
        $description = '在线康熙字典，支持拼音、部首、笔画等多种查询方式，帮您了解汉字的五行属性和起名寓意';
        
        $char = isset($_GET['char']) ? trim($_GET['char']) : '';
        $wuxing = isset($_GET['wuxing']) ? intval($_GET['wuxing']) : 0;
        $bihua = isset($_GET['bihua']) ? intval($_GET['bihua']) : 0;
        
        if (!empty($char)) {
            yzm_base::load_model('character_model', 'qiming', 0);
            $character_model = new character_model();
            $char_info = $character_model->get_by_char($char);
        } else {
            $char_info = null;
        }
        
        include template('qiming', 'kxzd');
    }
    
    /**
     * 成人改名页面
     */
    public function gaimingzi() {
        $seo_title = '成人改名 - 起名网';
        $keywords = '成人改名,改名';
        $description = '专业改名服务，帮助成年人找到更适合自己的名字，开启新人生';
        
        $recent_articles = $this->get_articles_by_cat('gaimingzi', 6);
        include template('qiming', 'gaimingzi');
    }
    
    /**
     * 百家姓页面
     */
    public function baijiaxing() {
        $seo_title = '百家姓 - 起名网';
        $keywords = '百家姓,姓氏';
        $description = '收录中国百家姓，查看各姓氏的来源、分布和起名推荐';
        
        include template('qiming', 'baijiaxing');
    }
    
    /**
     * 起名知识页面
     */
    public function zhishi() {
        $seo_title = '起名知识 - 起名网';
        $keywords = '起名知识,起名常识';
        $description = '专业的起名知识栏目，为您提供八字起名、诗词起名、周易起名等各类起名知识的详细介绍';
        
        include template('qiming', 'zhishi');
    }
    
    /**
     * 获取指定栏目的文章
     */
    private function get_articles_by_cat($cat, $limit = 6) {
        return array();
    }
    
    /**
     * 姓名配对页面
     */
    public function xingmingpeidui() {
        $seo_title = '姓名配对 - 起名网';
        include template('qiming', 'xingmingpeidui');
    }
    
    /**
     * 定字起名页面
     */
    public function dingzi() {
        $seo_title = '定字起名 - 起名网';
        include template('qiming', 'dingzi');
    }
    
    /**
     * 男孩起名页面
     */
    public function nanhai() {
        $seo_title = '男孩起名 - 起名网';
        include template('qiming', 'nanhai');
    }
    
    /**
     * 女孩起名页面
     */
    public function nvhai() {
        $seo_title = '女孩起名 - 起名网';
        include template('qiming', 'nvhai');
    }
    
    /**
     * 唐诗起名页面
     */
    public function tangshi() {
        $seo_title = '唐诗起名 - 起名网';
        include template('qiming', 'tangshi');
    }
    
    /**
     * 诗经起名页面
     */
    public function shijing() {
        $seo_title = '诗经起名 - 起名网';
        include template('qiming', 'shijing');
    }
    
    /**
     * 宋词起名页面
     */
    public function songci() {
        $seo_title = '宋词起名 - 起名网';
        include template('qiming', 'songci');
    }
    
    /**
     * 楚辞起名页面
     */
    public function chuci() {
        $seo_title = '楚辞起名 - 起名网';
        include template('qiming', 'chuci');
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
}
