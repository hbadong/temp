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
