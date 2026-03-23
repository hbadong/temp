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
        include template('qiming', 'baobao');
    }
    
    /**
     * 八字起名页面
     */
    public function bazi() {
        $seo_title = '八字起名 - 起名网';
        include template('qiming', 'bazi');
    }
    
    /**
     * 诗词起名页面
     */
    public function shici() {
        $seo_title = '诗词起名 - 起名网';
        include template('qiming', 'shici');
    }
    
    /**
     * 姓名测试页面
     */
    public function ceshi() {
        $seo_title = '姓名测试 - 起名网';
        include template('qiming', 'ceshi');
    }
    
    /**
     * 周易起名页面
     */
    public function zhouyi() {
        $seo_title = '周易起名 - 起名网';
        include template('qiming', 'zhouyi');
    }
    
    /**
     * 公司起名页面
     */
    public function gongsi() {
        $seo_title = '公司起名 - 起名网';
        include template('qiming', 'gongsi');
    }
    
    /**
     * 康熙字典页面
     */
    public function kxzd() {
        $seo_title = '康熙字典 - 起名网';
        include template('qiming', 'kxzd');
    }
    
    /**
     * 成人改名页面
     */
    public function gaimingzi() {
        $seo_title = '成人改名 - 起名网';
        include template('qiming', 'gaimingzi');
    }
    
    /**
     * 百家姓页面
     */
    public function baijiaxing() {
        $seo_title = '百家姓 - 起名网';
        include template('qiming', 'baijiaxing');
    }
    
    /**
     * 起名知识页面
     */
    public function zhishi() {
        $seo_title = '起名知识 - 起名网';
        include template('qiming', 'zhishi');
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
