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
        
        include template('qiming', 'index');
    }
    
    /**
     * 宝宝起名页面
     */
    public function baobao() {
        $seo_title = '宝宝起名 - 起名网';
        $keywords = '宝宝起名,婴儿起名,宝宝起名取名大全,起名网';
        $description = '宝宝起名以先进AI技术和大数据融合千年传统起名智慧，为您提供独一无二、寓意深远的宝宝名字方案';
        include template('qiming', 'baobao');
    }
    
    /**
     * 八字起名页面
     */
    public function bazi() {
        $seo_title = '八字起名 - 起名网';
        $keywords = '八字起名,生辰八字起名,根据八字起名,起名网';
        $description = '起名网八字起名服务，基于传统生辰八字理论，结合五行分析，为您提供专业的八字起名方案';
        include template('qiming', 'bazi');
    }
    
    /**
     * 诗词起名页面
     */
    public function shici() {
        $seo_title = '诗词起名 - 起名网';
        $keywords = '诗词起名,古诗词起名,诗词取名,起名网';
        $description = '诗词起名结合孩子出生信息和父母期盼，从二十多万诗词古文中取字，确保每个名字意蕴优美、诗情画意';
        include template('qiming', 'shici');
    }
    
    /**
     * 姓名测试页面
     */
    public function ceshi() {
        $seo_title = '姓名测试 - 起名网';
        $keywords = '姓名测试,名字打分,姓名测试打分,起名网';
        $description = '起名网姓名测试打分工具，通过分析名字的五行属性和五格数理，为您提供专业的姓名测试结果和运势解读';
        include template('qiming', 'ceshi');
    }
    
    /**
     * 周易起名页面
     */
    public function zhouyi() {
        $seo_title = '周易起名 - 起名网';
        $keywords = '周易起名,易经起名,八卦起名,起名网';
        $description = '起名网周易起名服务，以易经八卦为基础，结合五行相生相克理论，为您提供具有深厚文化底蕴的名字';
        include template('qiming', 'zhouyi');
    }
    
    /**
     * 公司起名页面
     */
    public function gongsi() {
        $seo_title = '公司起名 - 起名网';
        $keywords = '公司起名,企业起名,品牌命名,起名网';
        $description = '起名网公司起名服务，结合行业特征和企业文化，为您打造具有独特寓意和市场竞争力的公司名称';
        include template('qiming', 'gongsi');
    }
    
    /**
     * 康熙字典页面
     */
    public function kxzd() {
        $seo_title = '康熙字典 - 起名网';
        $keywords = '康熙字典,汉字查询,五行属性,拼音,笔画,起名网';
        $description = '起名网康熙字典频道免费提供康熙字典在线查字，提供全面的汉字五行属性、解释、拼音、笔画等助您轻松查询孩子起名所需的汉字信息';
        include template('qiming', 'kxzd');
    }
    
    /**
     * 成人改名页面
     */
    public function gaimingzi() {
        $seo_title = '成人改名 - 起名网';
        $keywords = '成人改名,改名取名,改名网,起名网';
        $description = '起名网成人改名服务，为您提供符合个人命理和期望的好名字，让您的名字更具正能量';
        include template('qiming', 'gaimingzi');
    }
    
    /**
     * 百家姓页面
     */
    public function baijiaxing() {
        $seo_title = '百家姓 - 起名网';
        $keywords = '百家姓,姓氏排名,姓氏大全,姓氏来源,起名网';
        $description = '起名网提供百家姓排名，百家姓大全，百家姓的历史来源，寻根问祖、姓氏来源免费查询服务';
        include template('qiming', 'baijiaxing');
    }
    
    /**
     * 起名知识页面
     */
    public function zhishi() {
        $seo_title = '起名知识 - 起名网';
        $keywords = '起名知识,起名技巧,起名大全,起名网';
        $description = '起名网起名知识栏目，为您提供专业的起名技巧、五行八字知识、姓名学讲解等丰富内容';
        include template('qiming', 'zhishi');
    }
    
    /**
     * 姓名配对页面
     */
    public function xingmingpeidui() {
        $seo_title = '姓名配对 - 起名网';
        $keywords = '姓名配对,名字配对,姓名测试配对,起名网';
        $description = '起名网姓名配对服务，通过分析两人姓名的五行数理，为您提供姓名配对指数和相合度分析';
        include template('qiming', 'xingmingpeidui');
    }
    
    /**
     * 定字起名页面
     */
    public function dingzi() {
        $seo_title = '定字起名 - 起名网';
        $keywords = '定字起名,指定用字起名,定字取名,起名网';
        $description = '起名网定字起名服务，帮助您在起名时锁定某个特定用字，满足您的个性化起名需求';
        include template('qiming', 'dingzi');
    }
    
    /**
     * 男孩起名页面
     */
    public function nanhai() {
        $seo_title = '男孩起名 - 起名网';
        $keywords = '男孩起名,男宝宝起名,男孩名字大全,起名网';
        $description = '起名网为您的男宝宝提供大气、有内涵的男孩名字，结合八字五行和生肖属性，为您精选最适合男孩的名字';
        include template('qiming', 'nanhai');
    }
    
    /**
     * 女孩起名页面
     */
    public function nvhai() {
        $seo_title = '女孩起名 - 起名网';
        $keywords = '女孩起名,女宝宝起名,女孩名字大全,起名网';
        $description = '起名网为您的女宝宝提供优雅、温柔的女孩名字，结合八字五行和生肖属性，为您精选最适合女孩的名字';
        include template('qiming', 'nvhai');
    }
    
    /**
     * 唐诗起名页面
     */
    public function tangshi() {
        $seo_title = '唐诗起名 - 起名网';
        $keywords = '唐诗起名,唐诗取名,古诗词起名,起名网';
        $description = '起名网唐诗起名服务，从优美的唐诗中汲取灵感，为您提供富有诗意和文化底蕴的好名字';
        include template('qiming', 'tangshi');
    }
    
    /**
     * 诗经起名页面
     */
    public function shijing() {
        $seo_title = '诗经起名 - 起名网';
        $keywords = '诗经起名,诗经取名,古诗词起名,起名网';
        $description = '起名网诗经起名服务，从诗经中选取优美字词，为您提供古典优雅、寓意深远的名字';
        include template('qiming', 'shijing');
    }
    
    /**
     * 宋词起名页面
     */
    public function songci() {
        $seo_title = '宋词起名 - 起名网';
        $keywords = '宋词起名,宋词取名,古诗词起名,起名网';
        $description = '起名网宋词起名服务，从婉约或豪放的宋词中为您挑选意境优美的好名字';
        include template('qiming', 'songci');
    }
    
    /**
     * 楚辞起名页面
     */
    public function chuci() {
        $seo_title = '楚辞起名 - 起名网';
        $keywords = '楚辞起名,屈原起名,古诗词起名,起名网';
        $description = '楚辞起名从屈原等楚辞名家的作品中汲取灵感，为您提供浪漫瑰丽的好名字';
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
        
        // SEO标签
        $seo_title = $surname . '起名结果 - 起名网';
        $keywords = '起名结果,' . $surname . '起名,' . ($name ? $surname.$name : '');
        $description = '起名网为您提供专业的起名结果分析，根据八字五行和五格数理为您推荐最适合的名字';
        
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
        
        // SEO标签
        $seo_title = $surname . $name . '测试结果 - 起名网';
        $keywords = '姓名测试,' . $surname . $name . ',名字打分,' . $result['total_score'] . '分';
        $description = '起名网姓名测试结果：' . $surname . $name . '，五格评分：' . $result['total_score'] . '分，评分等级：' . $level;
        
        include template('qiming', 'test_result');
    }
}
