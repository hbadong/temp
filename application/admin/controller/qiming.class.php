<?php
/**
 * 起名系统后台管理控制器
 */

defined('IN_YZMPHP') or exit('Access Denied');
yzm_base::load_controller('common', 'admin', 0);

class qiming extends common {
    
    /**
     * 仪表盘
     */
    public function init() {
        $stats = array(
            'total_chars' => D('character_model')->total(),
            'total_poetry' => D('poetry_model')->total(),
            'total_tests' => D('name_test_results')->total(),
            'total_bagua' => D('bagua_model')->total(),
            'total_users' => D('member')->total(),
            'today_tests' => D('name_test_results')->where("DATE(created_at) = CURDATE()")->total(),
        );
        
        $recent_tests = D('name_test_results')->order('id DESC')->limit(10)->select();
        $hot_chars = D('name_rankings')->where(array('type' => 'boy-char'))->order('search_count DESC')->limit(10)->select();
        
        include $this->admin_tpl('qiming/dashboard');
    }
    
    /**
     * 汉字管理
     */
    public function characters() {
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        
        $where = $search ? "char LIKE '%{$search}%' OR pinyin LIKE '%{$search}%'" : '';
        $total = D('character')->where($where)->total();
        $page = new page($total, 20);
        $list = D('character')->where($where)->order('id DESC')->limit($page->limit())->select();
        
        include $this->admin_tpl('qiming/characters');
    }
    
    /**
     * 添加汉字
     */
    public function char_add() {
        if (isset($_POST['dosubmit'])) {
            $data = array(
                'char' => trim($_POST['char']),
                'pinyin' => trim($_POST['pinyin']),
                'zhuyin' => trim($_POST['zhuyin']),
                'bushou' => trim($_POST['bushou']),
                'bihua' => intval($_POST['bihua']),
                'wuxing' => intval($_POST['wuxing']),
                'jx' => trim($_POST['jx']),
                'xmxy' => trim($_POST['xmxy']),
            );
            
            $id = D('character')->insert($data);
            if ($id) {
                showmsg('添加成功', U('qiming/characters'));
            } else {
                showmsg('添加失败', 'stop');
            }
        }
        
        include $this->admin_tpl('qiming/char_form');
    }
    
    /**
     * 编辑汉字
     */
    public function char_edit() {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if (isset($_POST['dosubmit'])) {
            $data = array(
                'char' => trim($_POST['char']),
                'pinyin' => trim($_POST['pinyin']),
                'zhuyin' => trim($_POST['zhuyin']),
                'bushou' => trim($_POST['bushou']),
                'bihua' => intval($_POST['bihua']),
                'wuxing' => intval($_POST['wuxing']),
                'jx' => trim($_POST['jx']),
                'xmxy' => trim($_POST['xmxy']),
            );
            
            D('character')->update($data, array('id' => intval($_POST['id'])));
            showmsg('修改成功', U('qiming/characters'));
        }
        
        $data = D('character')->where(array('id' => $id))->find();
        include $this->admin_tpl('qiming/char_form');
    }
    
    /**
     * 删除汉字
     */
    public function char_del() {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        D('character')->delete(array('id' => $id));
        showmsg('删除成功', U('qiming/characters'));
    }
    
    /**
     * 诗词管理
     */
    public function poetry() {
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $type = isset($_GET['type']) ? intval($_GET['type']) : 1;
        
        $where = array('type' => $type);
        $total = D('poetry')->where($where)->total();
        $page = new page($total, 20);
        $list = D('poetry')->where($where)->order('id DESC')->limit($page->limit())->select();
        
        include $this->admin_tpl('qiming/poetry');
    }
    
    /**
     * 添加诗词
     */
    public function poetry_add() {
        if (isset($_POST['dosubmit'])) {
            $data = array(
                'title' => trim($_POST['title']),
                'author' => trim($_POST['author']),
                'type' => intval($_POST['type']),
                'content' => trim($_POST['content']),
                'dynasty' => trim($_POST['dynasty']),
                'theme' => trim($_POST['theme']),
            );
            
            $id = D('poetry')->insert($data);
            if ($id) {
                showmsg('添加成功', U('qiming/poetry'));
            } else {
                showmsg('添加失败', 'stop');
            }
        }
        
        include $this->admin_tpl('qiming/poetry_form');
    }
    
    /**
     * 编辑诗词
     */
    public function poetry_edit() {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if (isset($_POST['dosubmit'])) {
            $data = array(
                'title' => trim($_POST['title']),
                'author' => trim($_POST['author']),
                'type' => intval($_POST['type']),
                'content' => trim($_POST['content']),
                'dynasty' => trim($_POST['dynasty']),
                'theme' => trim($_POST['theme']),
            );
            
            D('poetry')->update($data, array('id' => intval($_POST['id'])));
            showmsg('修改成功', U('qiming/poetry'));
        }
        
        $data = D('poetry')->where(array('id' => $id))->find();
        include $this->admin_tpl('qiming/poetry_form');
    }
    
    /**
     * 删除诗词
     */
    public function poetry_del() {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        D('poetry')->delete(array('id' => $id));
        showmsg('删除成功', U('qiming/poetry'));
    }
    
    /**
     * 热门排行管理
     */
    public function rankings() {
        $type = isset($_GET['type']) ? $_GET['type'] : 'boy-char';
        $list = D('name_rankings')->where(array('type' => $type))->order('ranking ASC')->select();
        include $this->admin_tpl('qiming/rankings');
    }
    
    /**
     * 更新排行数据
     */
    public function rankings_update() {
        if (isset($_POST['dosubmit'])) {
            $type = trim($_POST['type']);
            $data = json_decode($_POST['data'], true);
            
            foreach ($data as $item) {
                $exists = D('name_rankings')->where(array('char_or_name' => $item['char'], 'type' => $type))->find();
                if ($exists) {
                    D('name_rankings')->update(array('ranking' => $item['rank']), array('id' => $exists['id']));
                } else {
                    D('name_rankings')->insert(array(
                        'char_or_name' => $item['char'],
                        'type' => $type,
                        'ranking' => $item['rank'],
                        'month' => date('Y-m'),
                    ));
                }
            }
            
            showmsg('更新成功', U('qiming/rankings', array('type' => $type)));
        }
        
        include $this->admin_tpl('qiming/rankings_form');
    }
    
    /**
     * 姓名测试记录
     */
    public function test_records() {
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $total = D('name_test_results')->total();
        $page = new page($total, 20);
        $list = D('name_test_results')->order('id DESC')->limit($page->limit())->select();
        
        include $this->admin_tpl('qiming/test_records');
    }
    
    /**
     * 八卦管理
     */
    public function bagua() {
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $total = D('bagua')->total();
        $page = new page($total, 20);
        $list = D('bagua')->order('id ASC')->limit($page->limit())->select();
        
        include $this->admin_tpl('qiming/bagua');
    }
    
    /**
     * 添加卦象
     */
    public function bagua_add() {
        if (isset($_POST['dosubmit'])) {
            $data = array(
                'gua_name' => trim($_POST['gua_name']),
                'gua_ci' => trim($_POST['gua_ci']),
                'tuan_ci' => trim($_POST['tuan_ci']),
                'xiang_ci' => trim($_POST['xiang_ci']),
                'yao_ci' => trim($_POST['yao_ci']),
                'wuxing' => trim($_POST['wuxing']),
            );
            
            $id = D('bagua')->insert($data);
            if ($id) {
                showmsg('添加成功', U('qiming/bagua'));
            } else {
                showmsg('添加失败', 'stop');
            }
        }
        
        include $this->admin_tpl('qiming/bagua_form');
    }
    
    /**
     * 编辑卦象
     */
    public function bagua_edit() {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if (isset($_POST['dosubmit'])) {
            $data = array(
                'gua_name' => trim($_POST['gua_name']),
                'gua_ci' => trim($_POST['gua_ci']),
                'tuan_ci' => trim($_POST['tuan_ci']),
                'xiang_ci' => trim($_POST['xiang_ci']),
                'yao_ci' => trim($_POST['yao_ci']),
                'wuxing' => trim($_POST['wuxing']),
            );
            
            D('bagua')->update($data, array('id' => intval($_POST['id'])));
            showmsg('修改成功', U('qiming/bagua'));
        }
        
        $data = D('bagua')->where(array('id' => $id))->find();
        include $this->admin_tpl('qiming/bagua_form');
    }
    
    /**
     * 删除卦象
     */
    public function bagua_del() {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        D('bagua')->delete(array('id' => $id));
        showmsg('删除成功', U('qiming/bagua'));
    }
    
    /**
     * 黄历管理
     */
    public function horoscope() {
        $date = isset($_GET['date']) ? trim($_GET['date']) : date('Y-m-d');
        $data = D('horoscope')->where(array('date' => $date))->find();
        
        if (!$data) {
            $data = array(
                'date' => $date,
                'lunar_date' => '',
                'zodiac' => '',
                'zodiac_year' => '',
                'zodiac_month' => '',
                'zodiac_day' => '',
                'yi' => '',
                'ji' => '',
                'jishi' => '',
                'caishen' => '',
                'xishen' => '',
                'fushen' => '',
                'chongsha' => '',
            );
        }
        
        include $this->admin_tpl('qiming/horoscope');
    }
    
    /**
     * 更新黄历
     */
    public function horoscope_update() {
        if (isset($_POST['dosubmit'])) {
            $date = trim($_POST['date']);
            $data = array(
                'lunar_date' => trim($_POST['lunar_date']),
                'zodiac' => trim($_POST['zodiac']),
                'zodiac_year' => trim($_POST['zodiac_year']),
                'zodiac_month' => trim($_POST['zodiac_month']),
                'zodiac_day' => trim($_POST['zodiac_day']),
                'yi' => trim($_POST['yi']),
                'ji' => trim($_POST['ji']),
                'jishi' => trim($_POST['jishi']),
                'caishen' => trim($_POST['caishen']),
                'xishen' => trim($_POST['xishen']),
                'fushen' => trim($_POST['fushen']),
                'chongsha' => trim($_POST['chongsha']),
            );
            
            $exists = D('horoscope')->where(array('date' => $date))->find();
            if ($exists) {
                D('horoscope')->update($data, array('date' => $date));
            } else {
                $data['date'] = $date;
                D('horoscope')->insert($data);
            }
            
            showmsg('更新成功', U('qiming/horoscope', array('date' => $date)));
        }
    }
}
