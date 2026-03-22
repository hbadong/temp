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
     * 起名知识页面
     */
    public function zhishi() {
        $seo_title = '起名知识 - 起名网';
        $action = 'zhishi';
        
        $cat = isset($_GET['cat']) ? intval($_GET['cat']) : 0;
        
        $articles = $this->get_articles($cat);
        $categories = $this->get_article_categories();
        
        include template('qiming', 'zhishi');
    }
    
    /**
     * 文章详情页
     */
    public function article() {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        $article = $this->get_article($id);
        if (!$article) {
            showmsg('文章不存在', 'stop');
        }
        
        $seo_title = $article['title'] . ' - 起名知识 - 起名网';
        $action = 'zhishi';
        
        include template('qiming', 'article');
    }
    
    /**
     * 获取文章分类
     */
    private function get_article_categories() {
        return array(
            1 => array('name' => '起名常识', 'icon' => '📚', 'desc' => '了解起名的基本概念和注意事项'),
            2 => array('name' => '八字知识', 'icon' => '� 八字', 'desc' => '生辰八字与五行分析'),
            3 => array('name' => '五行与起名', 'icon' => '☯', 'desc' => '五行属性与起名的关系'),
            4 => array('name' => '诗词起名', 'icon' => '📜', 'desc' => '唐诗宋词中的好名字'),
            5 => array('name' => '周易起名', 'icon' => '☯', 'desc' => '易经智慧与起名'),
            6 => array('name' => '名字鉴赏', 'icon' => '👁', 'desc' => '名人名字赏析')
        );
    }
    
    /**
     * 获取文章列表
     */
    private function get_articles($cat = 0) {
        $articles = array(
            1 => array(
                array('id' => 1, 'title' => '如何给宝宝起一个好名字', 'cat' => 1, 'summary' => '一个好名字对宝宝的一生都有重要影响。本文介绍起名的基本原则和注意事项。', 'author' => '起名网', 'date' => '2026-03-15', 'views' => 2568),
                array('id' => 2, 'title' => '起名时最常见的十大误区', 'cat' => 1, 'summary' => '很多家长在起名时容易犯一些常见错误，本文为您详细解析。', 'author' => '起名网', 'date' => '2026-03-10', 'views' => 1892),
                array('id' => 3, 'title' => '女孩起名常用字及寓意', 'cat' => 1, 'summary' => '本文为您推荐适合女孩起名的常用汉字及其美好寓意。', 'author' => '起名网', 'date' => '2026-03-08', 'views' => 3245),
                array('id' => 4, 'title' => '男孩起名常用字及寓意', 'cat' => 1, 'summary' => '本文为您推荐适合男孩起名的常用汉字及其美好寓意。', 'author' => '起名网', 'date' => '2026-03-05', 'views' => 4123)
            ),
            2 => array(
                array('id' => 5, 'title' => '什么是生辰八字', 'cat' => 2, 'summary' => '生辰八字是中华民族独特的命理学术，本文详细介绍八字的含义和计算方法。', 'author' => '起名网', 'date' => '2026-03-12', 'views' => 1567),
                array('id' => 6, 'title' => '八字五行与起名的关系', 'cat' => 2, 'summary' => '如何根据八字五行来选择适合的名字，本文为您详细解答。', 'author' => '起名网', 'date' => '2026-03-08', 'views' => 2134),
                array('id' => 7, 'title' => '日主强弱与用神选取', 'cat' => 2, 'summary' => '日主是八字的核心，本文介绍如何判断日主强弱及用神的选取。', 'author' => '起名网', 'date' => '2026-03-02', 'views' => 987)
            ),
            3 => array(
                array('id' => 8, 'title' => '五行属金的汉字有哪些', 'cat' => 3, 'summary' => '本文为您详细介绍五行属金的汉字分类及起名用字建议。', 'author' => '起名网', 'date' => '2026-03-14', 'views' => 3567),
                array('id' => 9, 'title' => '五行属木的汉字有哪些', 'cat' => 3, 'summary' => '本文为您详细介绍五行属木的汉字分类及起名用字建议。', 'author' => '起名网', 'date' => '2026-03-11', 'views' => 2890),
                array('id' => 10, 'title' => '五行属水的汉字有哪些', 'cat' => 3, 'summary' => '本文为您详细介绍五行属水的汉字分类及起名用字建议。', 'author' => '起名网', 'date' => '2026-03-09', 'views' => 2456),
                array('id' => 11, 'title' => '五行属火的汉字有哪些', 'cat' => 3, 'summary' => '本文为您详细介绍五行属火的汉字分类及起名用字建议。', 'author' => '起名网', 'date' => '2026-03-07', 'views' => 2234),
                array('id' => 12, 'title' => '五行属土的汉字有哪些', 'cat' => 3, 'summary' => '本文为您详细介绍五行属土的汉字分类及起名用字建议。', 'author' => '起名网', 'date' => '2026-03-04', 'views' => 1987)
            ),
            4 => array(
                array('id' => 13, 'title' => '唐诗中适合起名的诗句', 'cat' => 4, 'summary' => '唐诗中有很多优美的诗句适合提取作为名字，本文为您推荐。', 'author' => '起名网', 'date' => '2026-03-13', 'views' => 4567),
                array('id' => 14, 'title' => '宋词中适合起名的词句', 'cat' => 4, 'summary' => '宋词以婉约秀丽著称，本文为您推荐宋词中适合起名的词句。', 'author' => '起名网', 'date' => '2026-03-06', 'views' => 3789)
            ),
            5 => array(
                array('id' => 15, 'title' => '周易起名与传统智慧', 'cat' => 5, 'summary' => '周易是中华文明的源头活水，本文介绍如何运用易经智慧起名。', 'author' => '起名网', 'date' => '2026-03-14', 'views' => 2123),
                array('id' => 16, 'title' => '八卦与姓名学', 'cat' => 5, 'summary' => '八卦是周易的核心概念，本文介绍八卦与姓名学的关系。', 'author' => '起名网', 'date' => '2026-03-09', 'views' => 1876)
            ),
            6 => array(
                array('id' => 17, 'title' => '明星名字赏析', 'cat' => 6, 'summary' => '很多明星的名字都很有特色，本文为您赏析一些知名明星的名字。', 'author' => '起名网', 'date' => '2026-03-11', 'views' => 5234),
                array('id' => 18, 'title' => '古代名人名字故事', 'cat' => 6, 'summary' => '古代名人的名字往往有深刻的含义和故事，本文为您讲述。', 'author' => '起名网', 'date' => '2026-03-03', 'views' => 3456)
            )
        );
        
        if ($cat > 0) {
            return isset($articles[$cat]) ? $articles[$cat] : array();
        }
        
        $all = array();
        foreach ($articles as $cat_articles) {
            $all = array_merge($all, $cat_articles);
        }
        
        usort($all, function($a, $b) {
            return strcmp($b['date'], $a['date']);
        });
        
        return $all;
    }
    
    /**
     * 获取文章详情
     */
    private function get_article($id) {
        $articles = array(
            1 => array(
                'id' => 1,
                'title' => '如何给宝宝起一个好名字',
                'cat' => 1,
                'cat_name' => '起名常识',
                'author' => '起名网',
                'date' => '2026-03-15',
                'views' => 2568,
                'content' => '一个好名字对宝宝的一生都有重要影响。在中国传统文化中，名字不仅是一个符号，更承载着父母对孩子的期望和祝福。

<h3>一、起名的基本原则</h3>

<p><strong>1. 寓意美好</strong></p>
<p>名字应具有美好的寓意，能够体现积极向上的品质。如"浩然"寓意正气凛然，"雅婷"寓意优雅亭亭玉立。</p>

<p><strong>2. 音韵和谐</strong></p>
<p>好的名字应该朗朗上口，音韵和谐。注意声调搭配，避免谐音歧义。</p>

<p><strong>3. 五行平衡</strong></p>
<p>根据宝宝的生辰八字，分析五行缺失，选择能够补足五行的汉字。</p>

<p><strong>4. 三才五格</strong></p>
<p>参考姓名学中的三才五格理论，确保姓名数理吉利。</p>

<h3>二、起名的注意事项</h3>

<p>1. 避免使用生僻字，以免给日常交往带来不便</p>
<p>2. 避免与长辈名字重复或不雅谐音</p>
<p>3. 注意性别特征，男孩名和女孩名应有所区别</p>
<p>4. 结合家族姓氏文化，传承优秀传统</p>

<h3>三、起名方法推荐</h3>

<p>1. <strong>八字起名</strong>：根据宝宝出生时间分析五行，选择补救五行的字</p>
<p>2. <strong>诗词起名</strong>：从唐诗宋词中选取优美词句</p>
<p>3. <strong>周易起名</strong>：结合易经八卦，选择与命理相合的名字</p>
<p>4. <strong>期望起名</strong>：根据父母对孩子的期望选取名字</p>'
            ),
            2 => array(
                'id' => 2,
                'title' => '起名时最常见的十大误区',
                'cat' => 1,
                'cat_name' => '起名常识',
                'author' => '起名网',
                'date' => '2026-03-10',
                'views' => 1892,
                'content' => '很多家长在给孩子起名时容易犯一些常见错误，本文为您详细解析这些误区，帮助您避免这些问题。

<h3>误区一：盲目追求重名率低</h3>
<p>很多家长过分追求 unique 的名字，导致使用生僻字。其实常用字更容易被接受和记忆，也不影响名字的美观。</p>

<h3>误区二：忽视五行八字</h3>
<p>仅仅根据个人喜好起名，忽视了宝宝的生辰八字和五行属性。一个好的名字应该与宝宝的命理相合。</p>

<h3>误区三：过分追求分数</h3>
<p>三才五格评分只是一个参考指标，不应过分追求高分而忽略了名字的整体美感和寓意。</p>

<h3>误区四：忽视谐音问题</h3>
<p>有些名字单独看不错，但组合起来容易产生不良谐音，给孩子带来困扰。</p>

<h3>误区五：崇洋媚外</h3>
<p>盲目使用洋气或音译名字，忽视了名字的文化内涵和民族特色。</p>'
            ),
            5 => array(
                'id' => 5,
                'title' => '什么是生辰八字',
                'cat' => 2,
                'cat_name' => '八字知识',
                'author' => '起名网',
                'date' => '2026-03-12',
                'views' => 1567,
                'content' => '生辰八字是中华民族独特的命理学术体系，源于古代天文学和历法学。

<h3>一、八字的组成</h3>
<p>生辰八字是根据一个人出生的年、月、日、时四个时间点，分别加上天干和地支，形成八个字，故称"八字"。</p>

<p><strong>年柱：</strong>出生年份的天干地支</p>
<p><strong>月柱：</strong>出生月份的天干地支</p>
<p><strong>日柱：</strong>出生日期的天干地支（代表本人，又称日主）</p>
<p><strong>时柱：</strong>出生时辰的天干地支</p>

<h3>二、天干地支</h3>
<p><strong>十天干：</strong>甲、乙、丙、丁、戊、己、庚、辛、壬、癸</p>
<p><strong>十二地支：</strong>子、丑、寅、卯、辰、巳、午、未、申、酉、戌、亥</p>

<h3>三、八字与五行</h3>
<p>天干地支都有对应的五行属性：</p>
<p>甲乙属木、丙丁属火、戊己属土、庚辛属金、壬癸属水</p>
<p>寅卯属木、巳午属火、申酉属金、亥子属水、辰戌丑未属土</p>

<h3>四、八字在起名中的作用</h3>
<p>通过分析八字中的五行分布，可以了解一个人命理的强弱和喜忌。在起名时，选择能够补足五行缺失的字，达到平衡命理的作用。</p>'
            ),
            8 => array(
                'id' => 8,
                'title' => '五行属金的汉字有哪些',
                'cat' => 3,
                'cat_name' => '五行与起名',
                'author' => '起名网',
                'date' => '2026-03-14',
                'views' => 3567,
                'content' => '五行属金的汉字在起名中有着广泛的应用，本文为您详细介绍五行属金的汉字分类及起名建议。

<h3>一、金的特征</h3>
<p>金曰从革，具有清洁、肃降、收敛等特性。在起名中，属金的名字常寓意坚强、果断、勇敢等品质。</p>

<h3>二、常见属金汉字</h3>

<p><strong>直接属金：</strong>金、鑫、银、钧、铎、铖、锡、铭、镛、铠</p>

<p><strong>庚辛属金：</strong>庚、辛、璋、璘、璇、瑾、瑟、瑞、玛、琦</p>

<p><strong>带"刂"旁：</strong>创、刃、仉、剌、剡、剃、剑、剖、剥、割、劈、剧</p>

<p><strong>带"戈"旁：</strong>戈、戏、成、戒、戍、戟、戡、截、戣、戛、戟</p>

<p><strong>带"玉"旁：</strong>玉、王、珏、珐、琅、琳、瑱、璇、璐、璧</p>

<h3>三、起名推荐</h3>
<p><strong>男孩名：</strong>铭轩、钧天、铎声、锐意、钟岳</p>
<p><strong>女孩名：</strong>瑾瑜、琳琅、瑞雪、玉珍、瑶华</p>'
            ),
            13 => array(
                'id' => 13,
                'title' => '唐诗中适合起名的诗句',
                'cat' => 4,
                'cat_name' => '诗词起名',
                'author' => '起名网',
                'date' => '2026-03-13',
                'views' => 4567,
                'content' => '唐诗是中华文化的瑰宝，其中很多优美的诗句非常适合提取作为名字。本文为您推荐唐诗中适合起名的诗句。

<h3>一、山水意境</h3>
<p><strong>1. "明月松间照，清泉石上流"</strong></p>
<p>可取：松月（高洁）、清泉（纯净）、石上流（坚韧）</p>

<p><strong>2. "大漠孤烟直，长河落日圆"</strong></p>
<p>可取：孤烟（独特）、落日（温暖）、长河（胸怀）</p>

<h3>二、情感抒发</h3>
<p><strong>1. "海上生明月，天涯共此时"</strong></p>
<p>可取：海生（广阔）、明月（明亮）、共时（团圆）</p>

<p><strong>2. "春蚕到死丝方尽，蜡炬成灰泪始干"</strong></p>
<p>可取：春蚕（奉献）、丝方（执着）、蜡炬（燃烧）</p>

<h3>三、志向抱负</h3>
<p><strong>1. "会当凌绝顶，一览众山小"</strong></p>
<p>可取：凌绝（超越）、众山（视野）、小天下（气魄）</p>

<p><strong>2. "长风破浪会有时，直挂云帆济沧海"</strong></p>
<p>可取：长风（志向）、破浪（勇气）、云帆（追求）</p>'
            )
        );
        
        return isset($articles[$id]) ? $articles[$id] : null;
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
     * 百家姓页面
     */
    public function baijiaxing() {
        $seo_title = '百家姓 - 起名网';
        $action = 'baijiaxing';
        
        $surname = isset($_GET['surname']) ? trim($_GET['surname']) : '';
        
        if (!empty($surname)) {
            $surname_data = $this->get_surname_data($surname);
        } else {
            $surname_data = null;
        }
        
        $seo_title = '百家姓 - 起名网';
        include template('qiming', 'baijiaxing');
    }
    
    /**
     * 获取姓氏数据
     */
    private function get_surname_data($surname) {
        $data = array(
            '李' => array('origin' => '源于理姓', 'famous' => '李白、李世民、李时珍', 'distribution' => '全国第一大姓', 'poems' => '李花怒放一树白'),
            '王' => array('origin' => '源于姬姓', 'famous' => '王羲之、王安石、王阳明', 'distribution' => '全国第二大姓', 'poems' => '桃花潭水深千尺'),
            '张' => array('origin' => '源于姬姓', 'famous' => '张飞、张良、张学良', 'distribution' => '全国第三大姓', 'poems' => '张帆出海去'),
            '刘' => array('origin' => '源于祁姓', 'famous' => '刘邦、刘备、刘德华', 'distribution' => '全国第四大姓', 'poems' => '刘郎今又来'),
            '陈' => array('origin' => '源于妫姓', 'famous' => '陈独秀、陈道明、陈奕迅', 'distribution' => '全国第五大姓', 'poems' => '陈王斗酒诗百篇'),
            '杨' => array('origin' => '源于姬姓', 'famous' => '杨坚、杨过、杨幂', 'distribution' => '全国第六大姓', 'poems' => '杨柳依依情'),
            '赵' => array('origin' => '源于嬴姓', 'famous' => '赵匡胤、赵本山、赵丽颖', 'distribution' => '全国第七大姓', 'poems' => '赵客缦胡缨'),
            '黄' => array('origin' => '源于赢姓', 'famous' => '黄帝、黄巢、黄晓明', 'distribution' => '全国第八大姓', 'poems' => '黄沙百战穿金甲'),
            '周' => array('origin' => '源于姬姓', 'famous' => '周恩来、周杰伦、周迅', 'distribution' => '全国第九大姓', 'poems' => '周公吐哺天下归心'),
            '吴' => array('origin' => '源于姬姓', 'famous' => '吴道子、吴亦凡、吴京', 'distribution' => '全国第十大姓', 'poems' => '吴楚东南坼'),
            '徐' => array('origin' => '源于赢姓', 'famous' => '徐悲鸿、徐志摩、徐帆', 'distribution' => '全国第十一大姓', 'poems' => '徐行不须急'),
            '孙' => array('origin' => '源于姬姓', 'famous' => '孙中山、孙武、孙俪', 'distribution' => '全国第十二大姓', 'poems' => '孙子兵法传千古'),
            '马' => array('origin' => '源于赵姓', 'famous' => '马超、马云、马化腾', 'distribution' => '全国第十三上大姓', 'poems' => '马作的卢飞快'),
            '朱' => array('origin' => '源于曹姓', 'famous' => '朱元璋、朱自清、朱珠', 'distribution' => '全国第十四大姓', 'poems' => '朱颜犹对照水云'),
            '胡' => array('origin' => '源于归姓', 'famous' => '胡适、胡歌、胡杏儿', 'distribution' => '全国第十五大姓', 'poems' => '胡马依北风'),
            '郭' => array('origin' => '源于任姓', 'famous' => '郭子仪、郭晶晶、郭富城', 'distribution' => '全国第十七大姓', 'poems' => '城郭依稀报晓钟'),
            '林' => array('origin' => '源于子姓', 'famous' => '林则徐、林黛玉、林青霞', 'distribution' => '全国第十六大姓', 'poems' => '林花谢了春红'),
            '何' => array('origin' => '源于姬姓', 'famous' => '何香凝、何润东、何洁', 'distribution' => '全国第十八大姓', 'poems' => '何当共剪西窗烛'),
            '高' => array('origin' => '源于姜姓', 'famous' => '高圆圆、高晓松、高尔基', 'distribution' => '全国第十九大姓', 'poems' => '高山仰止景行行'),
            '罗' => array('origin' => '源于理姓', 'famous' => '罗贯中、罗伯特、罗志祥', 'distribution' => '全国第二十大姓', 'poems' => '罗衾不耐五更寒')
        );
        
        $first_char = mb_substr($surname, 0, 1);
        return isset($data[$first_char]) ? array_merge(array('surname' => $first_char), $data[$first_char]) : array('surname' => $first_char, 'origin' => '百家姓之一', 'famous' => '历史名人', 'distribution' => '姓氏分布广泛', 'poems' => '姓氏文化悠久');
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
