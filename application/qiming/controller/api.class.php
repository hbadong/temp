<?php
/**
 * 起名系统API接口控制器
 */

defined('IN_YZMPHP') or exit('Access Denecd');

class api {
    
    /**
     * 获取黄历数据
     */
    public function huangli() {
        $date = isset($_GET['date']) ? trim($_GET['date']) : date('Y-m-d');
        
        require_once APP_PATH . 'qiming/model/horoscope_model.class.php';
        $horoscope_model = new horoscope_model();
        $data = $horoscope_model->get_by_date($date);
        
        if (!$data) {
            $data = $horoscope_model->get_today();
        }
        
        echo json_encode(array('status' => 1, 'data' => $data));
    }
    
    /**
     * 获取热门排行
     */
    public function ranking() {
        $type = isset($_GET['type']) ? trim($_GET['type']) : 'boy-char';
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 30;
        
        require_once APP_PATH . 'qiming/model/ranking_model.class.php';
        $ranking_model = new ranking_model();
        $data = $ranking_model->get_by_type($type, $limit);
        
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
        
        require_once APP_PATH . 'qiming/model/character_model.class.php';
        $character_model = new character_model();
        $chars = $character_model->search_chars($keyword, 20);
        
        echo json_encode(array('status' => 1, 'data' => $chars));
    }
    
    /**
     * 搜索姓名
     */
    public function search_name() {
        $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
        
        if (empty($keyword)) {
            echo json_encode(array('status' => 0, 'message' => '关键词不能为空'));
            return;
        }
        
        require_once APP_PATH . 'qiming/model/character_model.class.php';
        $character_model = new character_model();
        $names = $character_model->search_names($keyword, 20);
        
        echo json_encode(array('status' => 1, 'data' => $names));
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
    
    /**
     * 获取汉字信息
     */
    public function get_char() {
        $char = isset($_GET['char']) ? trim($_GET['char']) : '';
        
        if (empty($char)) {
            echo json_encode(array('status' => 0, 'message' => '汉字不能为空'));
            return;
        }
        
        require_once APP_PATH . 'qiming/model/character_model.class.php';
        $character_model = new character_model();
        $data = $character_model->get_by_char($char);
        
        if ($data) {
            echo json_encode(array('status' => 1, 'data' => $data));
        } else {
            echo json_encode(array('status' => 0, 'message' => '未找到该汉字'));
        }
    }
    
    /**
     * 根据五行获取汉字
     */
    public function get_by_wuxing() {
        $wuxing = isset($_GET['wuxing']) ? intval($_GET['wuxing']) : 0;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
        
        if ($wuxing < 1 || $wuxing > 5) {
            echo json_encode(array('status' => 0, 'message' => '五行参数错误'));
            return;
        }
        
        require_once APP_PATH . 'qiming/model/character_model.class.php';
        $character_model = new character_model();
        $data = $character_model->search_by_wuxing($wuxing, $limit);
        
        echo json_encode(array('status' => 1, 'data' => $data));
    }
    
    /**
     * 获取诗词列表
     */
    public function poetry_list() {
        $type = isset($_GET['type']) ? intval($_GET['type']) : 1;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
        
        require_once APP_PATH . 'qiming/model/poetry_model.class.php';
        $poetry_model = new poetry_model();
        $data = $poetry_model->get_list($type, $limit);
        
        echo json_encode(array('status' => 1, 'data' => $data));
    }
    
    /**
     * 获取随机诗词
     */
    public function poetry_random() {
        $type = isset($_GET['type']) ? intval($_GET['type']) : 1;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
        
        require_once APP_PATH . 'qiming/model/poetry_model.class.php';
        $poetry_model = new poetry_model();
        $data = $poetry_model->get_random($type, $limit);
        
        echo json_encode(array('status' => 1, 'data' => $data));
    }
    
    /**
     * 搜索诗词
     */
    public function poetry_search() {
        $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
        
        if (empty($keyword)) {
            echo json_encode(array('status' => 0, 'message' => '关键词不能为空'));
            return;
        }
        
        require_once APP_PATH . 'qiming/model/poetry_model.class.php';
        $poetry_model = new poetry_model();
        $data = $poetry_model->search($keyword, 20);
        
        echo json_encode(array('status' => 1, 'data' => $data));
    }
    
    /**
     * 起名推荐
     */
    public function recommend() {
        $surname = isset($_GET['surname']) ? trim($_GET['surname']) : '';
        $gender = isset($_GET['gender']) ? intval($_GET['gender']) : 1;
        $birthdate = isset($_GET['birthdate']) ? trim($_GET['birthdate']) : '';
        
        if (empty($surname) || empty($birthdate)) {
            echo json_encode(array('status' => 0, 'message' => '参数不完整'));
            return;
        }
        
        $birth_year = date('Y', strtotime($birthdate));
        $birth_month = date('n', strtotime($birthdate));
        $birth_day = date('j', strtotime($birthdate));
        $birthtime = isset($_GET['birthtime']) ? intval($_GET['birthtime']) : 0;
        
        yzm_base::load_sys_class('bazi', '', 0);
        $bazi = new bazi();
        $bazi_result = $bazi->calculate($birth_year, $birth_month, $birth_day, $birthtime);
        $wuxing_count = $bazi->analyzeWuxing();
        
        require_once APP_PATH . 'qiming/model/character_model.class.php';
        $character_model = new character_model();
        
        $names = array();
        $chars = $character_model->search_by_wuxing($wuxing_count['need_wuxing'], 20);
        
        foreach ($chars as $char) {
            $full_name = $surname . $char['char'];
            yzm_base::load_sys_class('wuge', '', 0);
            $wuge = new wuge();
            $wuge_result = $wuge->calculate($surname, $char['char']);
            
            $names[] = array(
                'name' => $full_name,
                'char' => $char['char'],
                'pinyin' => $char['pinyin'],
                'wuge_score' => $wuge_result['total_score'],
                'wuxing' => $char['wuxing'],
            );
        }
        
        usort($names, function($a, $b) {
            return $b['wuge_score'] - $a['wuge_score'];
        });
        
        $names = array_slice($names, 0, 20);
        
        echo json_encode(array(
            'status' => 1, 
            'data' => array(
                'names' => $names,
                'bazi' => $bazi_result,
                'wuxing' => $wuxing_count,
            )
        ));
    }
}
