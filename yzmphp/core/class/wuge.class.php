<?php
/**
 * 五格数理计算类 - 起名系统核心算法
 * 
 * 用于计算姓名的天格、地格、人格、外格、总格及其数理吉凶
 */

defined('IN_YZMPHP') or exit('Access Denied');

class wuge {
    
    /**
     * 常用汉字笔画数据（精简版康熙字典笔画）
     * 格式：汉字 => 笔画数
     */
    private static $bihua_data = array(
        '一' => 1, '二' => 2, '三' => 3, '四' => 4, '五' => 5,
        '六' => 6, '七' => 7, '八' => 8, '九' => 9, '十' => 10,
        '百' => 6, '千' => 3, '万' => 3, '亿' => 15,
        '甲' => 5, '乙' => 1, '丙' => 5, '丁' => 2, '戊' => 5,
        '己' => 3, '庚' => 8, '辛' => 7, '壬' => 4, '癸' => 9,
        '子' => 3, '丑' => 4, '寅' => 11, '卯' => 4, '辰' => 7,
        '巳' => 6, '午' => 4, '未' => 8, '申' => 12, '酉' => 7,
        '戌' => 11, '亥' => 12,
        '李' => 7, '王' => 4, '张' => 11, '刘' => 6, '陈' => 7,
        '杨' => 7, '赵' => 9, '黄' => 11, '周' => 8, '吴' => 7,
        '徐' => 11, '孙' => 6, '马' => 10, '朱' => 6, '胡' => 11,
        '郭' => 10, '何' => 7, '高' => 10, '林' => 8, '罗' => 8,
        '郑' => 8, '梁' => 11, '谢' => 17, '宋' => 7, '唐' => 10,
        '许' => 11, '韩' => 12, '冯' => 12, '邓' => 16, '曹' => 11,
        '彭' => 12, '曾' => 12, '田' => 5, '董' => 12,
        '袁' => 10, '潘' => 15, '于' => 3, '蒋' => 15, '蔡' => 17,
        '余' => 7, '杜' => 7, '叶' => 5, '程' => 12, '苏' => 7,
        '魏' => 16, '吕' => 7, '丁' => 2, '任' => 6, '沈' => 7,
        '卢' => 5, '姜' => 9, '崔' => 11, '钟' => 9,
        '谭' => 19, '陆' => 16, '汪' => 7, '戴' => 17, '范' => 8,
        '石' => 5, '韦' => 4, '孟' => 8, '白' => 5,
        '江' => 6, '金' => 8, '雨' => 8, '钱' => 13,
        '天' => 4, '地' => 6, '人' => 2, '和' => 8, '心' => 4,
        '日' => 4, '月' => 4, '水' => 4, '火' => 4, '木' => 4,
        '金' => 8, '土' => 3, '风' => 4, '云' => 4, '山' => 3,
        '川' => 3, '海' => 10, '河' => 8, '湖' => 12,
        '峰' => 10, '涛' => 10, '雷' => 13, '电' => 13,
        '光' => 6, '明' => 8, '春' => 9, '夏' => 10,
        '秋' => 9, '冬' => 5, '雪' => 11,
        '花' => 7, '草' => 9, '鸟' => 5,
        '龙' => 16, '玉' => 5, '珍' => 9,
        '宁' => 5, '安' => 6, '静' => 16, '怡' => 9,
        '悦' => 11, '欣' => 8, '乐' => 5, '嘉' => 14,
        '豪' => 14, '杰' => 12, '俊' => 9, '伟' => 6,
        '勇' => 9, '志' => 7, '强' => 12, '刚' => 6,
        '德' => 15, '文' => 4, '华' => 12, '美' => 9,
        '丽' => 7, '芳' => 7, '兰' => 5, '梅' => 11,
        '松' => 8, '柏' => 9, '桂' => 10, '森' => 12,
        '东' => 5, '南' => 9, '西' => 6, '北' => 5,
        '宇' => 6, '宙' => 8, '博' => 12, '浩' => 11,
        '泽' => 17, '润' => 16, '涵' => 11, '清' => 11,
        '洁' => 9, '淳' => 11, '婷' => 12, '萱' => 12,
        '雯' => 12, '晴' => 12, '岚' => 12,
        '蕊' => 18, '菱' => 11, '菲' => 11, '苑' => 11,
        '芸' => 7, '苒' => 8, '若' => 11, '茜' => 9,
        '梓' => 11, '桐' => 10, '桦' => 10, '枫' => 13,
        '鹏' => 13, '鸿' => 17, '麟' => 23, '麒' => 19,
        '瑶' => 14, '瑾' => 16, '璇' => 15, '琳' => 12,
        '琪' => 12, '珊' => 9, '珠' => 10,
        '颖' => 16, '灵' => 7, '敏' => 11, '慧' => 15,
        '倩' => 10, '雅' => 12, '雯' => 12, '黛' => 17,
        '燕' => 16, '紫' => 12, '翠' => 14, '羽' => 6,
        '翔' => 12, '鸣' => 14, '鹏' => 13, '鹤' => 15,
        '霖' => 16, '霏' => 16, '霓' => 16, '霜' => 17,
        '霞' => 17, '露' => 21, '云' => 4, '霞' => 17,
    );
    
    /**
     * 数理吉凶表（1-81）
     */
    private static $shuli_jixing = array(
        1 => array('name' => '一元复始', 'good' => true, 'desc' => '宇宙起源，天地开泰'),
        2 => array('name' => '两仪之数', 'good' => false, 'desc' => '根基不固，摇摇欲坠'),
        3 => array('name' => '三才之数', 'good' => true, 'desc' => '进取如意，名利双收'),
        4 => array('name' => '四象之数', 'good' => false, 'desc' => '破败不幸，天折凶险'),
        5 => array('name' => '五行之数', 'good' => true, 'desc' => '五行齐全，厚重安稳'),
        6 => array('name' => '六爻之数', 'good' => true, 'desc' => '安稳余庆，天福幸福'),
        7 => array('name' => '七政之数', 'good' => true, 'desc' => '刚毅果断，精明勤勉'),
        8 => array('name' => '八卦之数', 'good' => true, 'desc' => '勤勉必成，努力发达'),
        9 => array('name' => '大成之数', 'good' => false, 'desc' => '盛衰交加，虽有成就'),
        10 => array('name' => '退运之数', 'good' => false, 'desc' => '万事终局，天赋凶祸'),
        11 => array('name' => '稳健之数', 'good' => true, 'desc' => '草木逢春，万事如意'),
        12 => array('name' => '薄弱之数', 'good' => false, 'desc' => '脆弱凶变，意志薄弱'),
        13 => array('name' => '春日牡丹', 'good' => true, 'desc' => '智能超群，意外成功'),
        14 => array('name' => '破败之数', 'good' => false, 'desc' => '破家亡身，辛苦一生'),
        15 => array('name' => '福寿之数', 'good' => true, 'desc' => '福寿双全，侥幸成功'),
        16 => array('name' => '厚重之数', 'good' => true, 'desc' => '德望厚重，富贵荣达'),
        17 => array('name' => '刚强之数', 'good' => true, 'desc' => '突破万难，刚柔兼备'),
        18 => array('name' => '有志之数', 'good' => true, 'desc' => '志在千里，刚柔并济'),
        19 => array('name' => '多难之数', 'good' => false, 'desc' => '表面荣华，实则不幸'),
        20 => array('name' => '屋下藏金', 'good' => true, 'desc' => '智慧聪明，反应灵敏'),
        21 => array('name' => '明月之数', 'good' => true, 'desc' => '光风霁月，独立权威'),
        22 => array('name' => '秋草之数', 'good' => false, 'desc' => '秋草逢霜，忧患累身'),
        23 => array('name' => '壮丽之数', 'good' => true, 'desc' => '旭日东升，壮丽壮观'),
        24 => array('name' => '财富之数', 'good' => true, 'desc' => '锦绣前程，财帛丰盈'),
        25 => array('name' => '英迈之数', 'good' => true, 'desc' => '聪明敏锐，忍耐力强'),
        26 => array('name' => '变怪之数', 'good' => false, 'desc' => '奇祸怪变，疑难多病'),
        27 => array('name' => '临事何愁', 'good' => true, 'desc' => '自我心强，欲望难遂'),
        28 => array('name' => '遭难之数', 'good' => false, 'desc' => '祸害并发，身遭厄运'),
        29 => array('name' => '智谋之数', 'good' => true, 'desc' => '财力归集，名望可就'),
        30 => array('name' => '一帆风顺', 'good' => false, 'desc' => '成非容易，侥幸者多'),
        31 => array('name' => '春日花开', 'good' => true, 'desc' => '智勇得志，幸运成就'),
        32 => array('name' => '宝马金鞍', 'good' => true, 'desc' => '幸运多望，权贵发达'),
        33 => array('name' => '旭日升天', 'good' => true, 'desc' => '家门隆昌，光明豪意'),
        34 => array('name' => '破兆之数', 'good' => false, 'desc' => '是非兼烦，沦落天涯'),
        35 => array('name' => '高楼赏月', 'good' => true, 'desc' => '保守平安，温和平安'),
        36 => array('name' => '波澜之数', 'good' => false, 'desc' => '风浪不平，惨遭不幸'),
        37 => array('name' => '猛虎出林', 'good' => true, 'desc' => '权威显达，慈祥可敬'),
        38 => array('name' => '磨刀之数', 'good' => false, 'desc' => '半吉半凶，中途障碍'),
        39 => array('name' => '云游四海', 'good' => true, 'desc' => '权贵实厚，繁华尊荣'),
        40 => array('name' => '退安之数', 'good' => false, 'desc' => '智谋进退，责难惩罚'),
        41 => array('name' => '德望之数', 'good' => true, 'desc' => '天赋吉运，德望兼备'),
        42 => array('name' => '十九数', 'good' => false, 'desc' => '智谋兼备，职业易成'),
        43 => array('name' => '散财破家', 'good' => false, 'desc' => '祸福并作，身遭厄运'),
        44 => array('name' => '秋草逢霜', 'good' => false, 'desc' => '寂寞孤独，悲惨难堪'),
        45 => array('name' => '顺风之数', 'good' => true, 'desc' => '新生泰和，意外成功'),
        46 => array('name' => '浪里淘金', 'good' => false, 'desc' => '虽有成就，凶变危险'),
        47 => array('name' => '点石成金', 'good' => true, 'desc' => '开花结果，权威显达'),
        48 => array('name' => '古稀之数', 'good' => false, 'desc' => '根深蒂固，兴业成功'),
        49 => array('name' => '转变之分', 'good' => false, 'desc' => '吉凶难分，不宜冒险'),
        50 => array('name' => '一成一败', 'good' => false, 'desc' => '吉凶相伴，侥幸者多'),
        51 => array('name' => '四属之地', 'good' => false, 'desc' => '盛衰交加，浮沉不定'),
        52 => array('name' => '达眼之数', 'good' => true, 'desc' => '大志难伸，先苦后甜'),
        53 => array('name' => '外美之数', 'good' => true, 'desc' => '虽成亦败，只能守身'),
        54 => array('name' => '多忧之数', 'good' => false, 'desc' => '外貌是非，优苦一生'),
        55 => array('name' => '善恶之数', 'good' => false, 'desc' => '外美内苦，困难重重'),
        56 => array('name' => '日没之数', 'good' => false, 'desc' => '风波不断，辛苦度日'),
        57 => array('name' => '月桂之数', 'good' => true, 'desc' => '幸遇贵人，逢凶化吉'),
        58 => array('name' => '晚达之数', 'good' => false, 'desc' => '先甘后苦，迟缓可解'),
        59 => array('name' => '寒蝉之数', 'good' => false, 'desc' => '意志不坚，忧愁竟生'),
        60 => array('name' => '争折之数', 'good' => false, 'desc' => '黑暗临头，发达无期'),
        61 => array('name' => '车路之数', 'good' => true, 'desc' => '先苦后甘，获得幸运'),
        62 => array('name' => '衰败之数', 'good' => false, 'desc' => '基础不稳，忧愁不断'),
        63 => array('name' => '富荣之数', 'good' => true, 'desc' => '福禄俱望，繁荣发达'),
        64 => array('name' => '破花残红', 'good' => false, 'desc' => '悲欢交集，空虚无限'),
        65 => array('name' => '珠宝之数', 'good' => true, 'desc' => '天福享受，繁荣发达'),
        66 => array('name' => '巨浪克身', 'good' => false, 'desc' => '进退失据，灾害不绝'),
        67 => array('name' => '刚风之数', 'good' => true, 'desc' => '鲤化龙，发达有权'),
        68 => array('name' => '顺风吹火', 'good' => true, 'desc' => '如意荣达，致富创业'),
        69 => array('name' => '穷途之数', 'good' => false, 'desc' => '灾难不绝，身处困境'),
        70 => array('name' => '残菊随风', 'good' => false, 'desc' => '精神失号，忧愁不绝'),
        71 => array('name' => '石上金花', 'good' => false, 'desc' => '有些作为，奈不长久'),
        72 => array('name' => '精悍之数', 'good' => false, 'desc' => '权威强硬，时被排挤'),
        73 => array('name' => '黍离之数', 'good' => false, 'desc' => '半吉半凶，浮沉不定'),
        74 => array('name' => '残关之数', 'good' => false, 'desc' => '无权无势，惨淡经营'),
        75 => array('name' => '寿比渊何', 'good' => false, 'desc' => '保守安全，不易变更'),
        76 => array('name' => '离散之数', 'good' => false, 'desc' => '倾家亡婚，悲哀孤独'),
        77 => array('name' => '家庭有克', 'good' => false, 'desc' => '喜中有忧，先吉后凶'),
        78 => array('name' => '晚苦之数', 'good' => false, 'desc' => '精神失号，忧愁不断'),
        79 => array('name' => '云腾月忌', 'good' => false, 'desc' => '智能兼备，奈运有限'),
        80 => array('name' => '凶因恶熟', 'good' => false, 'desc' => '一生勤奋，奈运有限'),
        81 => array('name' => '万物回春', 'good' => true, 'desc' => '还原复始，最定为吉'),
    );
    
    /**
     * 计算五格
     * @param string $surname 姓氏
     * @param string $name 名字
     * @return array 五格计算结果
     */
    public function calculate($surname, $name) {
        $surname_bihua = $this->get_bihua($surname);
        $name_chars = $this->mb_str_split($name);
        $name_bihua = array();
        foreach ($name_chars as $char) {
            $name_bihua[] = $this->get_bihua($char);
        }
        
        $surname_len = mb_strlen($surname, 'utf-8');
        
        // 天格：单姓+1，复姓合并
        $tiange = $surname_len == 1 ? $surname_bihua + 1 : $surname_bihua + $this->get_bihua(mb_substr($surname, 0, 1, 'utf-8'));
        
        // 地格：名字笔画数相加
        $dige = 0;
        foreach ($name_bihua as $bh) {
            $dige += $bh;
        }
        
        // 人格：姓笔画+名第一字笔画
        $renge = $surname_bihua + (isset($name_bihua[0]) ? $name_bihua[0] : 0);
        
        // 总格：姓名所有笔画相加
        $zongge = $surname_bihua;
        foreach ($name_bihua as $bh) {
            $zongge += $bh;
        }
        
        // 外格：总格-人格+1
        $waige = $zongge - $renge + 1;
        if ($waige < 1) $waige = 1;
        
        // 计算各格吉凶
        $tiange_info = $this->evaluate($tiange);
        $dige_info = $this->evaluate($dige);
        $renge_info = $this->evaluate($renge);
        $waige_info = $this->evaluate($waige);
        $zongge_info = $this->evaluate($zongge);
        
        // 计算总分（基于人格和总格）
        $total_score = $this->calculate_total_score($renge_info, $zongge_info, $tiange_info, $dige_info, $waige_info);
        
        return array(
            'surname' => $surname,
            'name' => $name,
            'surname_bihua' => $surname_bihua,
            'name_bihua' => $name_bihua,
            'tiange' => $tiange,
            'dige' => $dige,
            'renge' => $renge,
            'waige' => $waige,
            'zongge' => $zongge,
            'tiange_info' => $tiange_info,
            'dige_info' => $dige_info,
            'renge_info' => $renge_info,
            'waige_info' => $waige_info,
            'zongge_info' => $zongge_info,
            'total_score' => $total_score,
        );
    }
    
    /**
     * 获取汉字笔画数
     * @param string $char 汉字
     * @return int 笔画数
     */
    public function get_bihua($char) {
        if (isset(self::$bihua_data[$char])) {
            return self::$bihua_data[$char];
        }
        // 默认返回7画（未收录的汉字默认为7画）
        return 7;
    }
    
    /**
     * 评估数理吉凶
     * @param int $num 数理
     * @return array 吉凶信息
     */
    public function evaluate($num) {
        $num = $num > 81 ? $num % 81 : $num;
        if ($num < 1) $num = 1;
        
        if (isset(self::$shuli_jixing[$num])) {
            return self::$shuli_jixing[$num];
        }
        
        return array('name' => '未知', 'good' => false, 'desc' => '无法判断');
    }
    
    /**
     * 计算总分
     */
    private function calculate_total_score($renge_info, $zongge_info, $tiange_info, $dige_info, $waige_info) {
        $score = 0;
        
        // 人格最重要，占40%
        if ($renge_info['good']) $score += 40;
        
        // 总格占30%
        if ($zongge_info['good']) $score += 30;
        
        // 地格占15%
        if ($dige_info['good']) $score += 15;
        
        // 天格占10%
        if ($tiange_info['good']) $score += 10;
        
        // 外格占5%
        if ($waige_info['good']) $score += 5;
        
        return $score;
    }
    
    /**
     * 获取评分等级
     * @param int $score 分数
     * @return string 等级
     */
    public function get_level($score) {
        if ($score >= 95) return '大吉';
        if ($score >= 80) return '吉';
        if ($score >= 60) return '中';
        if ($score >= 40) return '凶';
        return '大凶';
    }
    
    /**
     * 字符串分割为数组（支持中文）
     */
    private function mb_str_split($string) {
        $len = mb_strlen($string, 'utf-8');
        $chars = array();
        for ($i = 0; $i < $len; $i++) {
            $chars[] = mb_substr($string, $i, 1, 'utf-8');
        }
        return $chars;
    }
}
