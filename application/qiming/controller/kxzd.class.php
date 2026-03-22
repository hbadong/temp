<?php
/**
 * 康熙字典控制器
 */

defined('IN_YZMPHP') or exit('Access Denied');
yzm_base::load_model('character', 'qiming', 0);

class kxzd {
    
    private $model;
    
    public function __construct() {
        $this->model = new character_model();
    }
    
    /**
     * 字典首页
     */
    public function index() {
        $seo_title = '康熙字典 - 起名网';
        include template('qiming', 'kxzd_index');
    }
    
    /**
     * 汉字详情
     */
    public function show() {
        $char = isset($_GET['char']) ? trim($_GET['char']) : '';
        if (empty($char)) {
            showmsg('参数错误', 'stop');
        }
        
        $data = $this->model->get_char($char);
        if (!$data) {
            showmsg('未找到该汉字', 'stop');
        }
        
        $seo_title = '汉字' . $char . ' - 康熙字典 - 起名网';
        include template('qiming', 'kxzd_show');
    }
    
    /**
     * 搜索
     */
    public function search() {
        $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
        $wuxing = isset($_GET['wuxing']) ? intval($_GET['wuxing']) : 0;
        
        if (empty($keyword) && empty($wuxing)) {
            include template('qiming', 'kxzd_search');
            return;
        }
        
        if ($keyword) {
            $list = $this->model->search_by_pinyin($keyword);
        } elseif ($wuxing) {
            $list = $this->model->search_by_wuxing($wuxing);
        }
        
        $seo_title = '搜索结果 - 康熙字典 - 起名网';
        include template('qiming', 'kxzd_search');
    }
    
    /**
     * 五行分类浏览
     */
    public function wuxing() {
        $wuxing = isset($_GET['wuxing']) ? intval($_GET['wuxing']) : 1;
        
        $list = $this->model->search_by_wuxing($wuxing);
        $wuxing_names = array(1 => '金', 2 => '木', 3 => '水', 4 => '火', 5 => '土');
        $current_wuxing = isset($wuxing_names[$wuxing]) ? $wuxing_names[$wuxing] : '金';
        
        $seo_title = $current_wuxing . '属性汉字 - 康熙字典 - 起名网';
        include template('qiming', 'kxzd_wuxing');
    }
}
