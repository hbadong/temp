<?php
/**
 * 内容重组引擎
 * 混合策略内容防同质化处理：同义词替换 + 段落重组 + 动态字段刷新
 * 本地处理零 AI 成本，必要时触发 AI 深度重组
 */
class content_reorganizer extends model {

    /**
     * 游戏领域内置同义词库（硬编码）
     * 键 = 原文，值 = 替换词
     * @var array
     */
    private $synonym_dict = array(
        // 游戏类型缩写
        'RPG'     => '角色扮演',
        'FPS'     => '第一人称射击',
        'TPS'     => '第三人称射击',
        'MOBA'    => '多人在线战术竞技',
        'ACT'     => '动作游戏',
        'AVG'     => '冒险游戏',
        'SLG'     => '策略游戏',
        'ARPG'    => '动作角色扮演',
        'MMORPG'  => '大型多人在线角色扮演',
        // 游戏术语
        '攻略'    => '指南',
        'BOSS'    => '首领',
        'BOSS战'  => '首领挑战',
        '副本'    => '地下城',
        '技能'    => '能力',
        '角色'    => '人物',
        '任务'    => '冒险',
        '敌人'    => '对手',
        '玩家'    => '游戏者',
        '画面'    => '视觉效果',
        '剧情'    => '故事',
        '系统'    => '机制',
        '战斗'    => '对战',
        '升级'    => '提升',
        '装备'    => '道具',
        '道具'    => '物品',
        '地图'    => '场景',
        '金币'    => '货币',
        '皮肤'    => '外观',
        '排位'    => '竞技',
        '赛季'    => '周期',
        '新手'    => '入门',
        '推荐'    => '精选',
        '评测'    => '测评',
        '热门'    => '流行',
        '手柄'    => '控制器',
        '主机'    => '游戏机',
        'PC'      => '电脑',
        '端游'    => '客户端游戏',
        '手游'    => '手机游戏',
        '页游'    => '网页游戏',
        '独立游戏' => '精品游戏',
        '3A'      => '顶级制作',
        '大作'    => '巨制',
        '神作'    => '杰作',
        'DLC'     => '扩展内容',
        'MOD'     => '模组',
        '联机'    => '在线',
        '单机'    => '离线',
        'PVP'     => '玩家对战',
        'PVE'     => '玩家对环境',
        '开放世界' => '开放宇宙',
        '卡牌'    => '集换式卡牌',
        '解谜'    => '益智',
        '恐怖'    => '惊悚',
        '射击'    => '枪战',
        '格斗'    => '拳脚对战',
        '竞速'    => '极速',
        '模拟'    => '仿真',
        '养成'    => '培养',
        '回合制'  => '回合策略',
        '策略'    => '战术',
        '经营'    => '管理',
        '建造'    => '搭建',
        '探索'    => '探险',
        '奇幻'    => '魔幻',
        '科幻'    => '未来',
        '末日'    => '末世',
        '武侠'    => '江湖',
        '仙侠'    => '修真',
        '像素'    => '复古像素',
        '复古'    => '怀旧',
        '重制'    => '复刻',
        '移植'    => '跨平台',
        '汉化'    => '中文版',
        '公测'    => '公开测试',
        '内测'    => '封闭测试',
        '上线'    => '发布',
        '停服'    => '关闭服务器',
        '停运'    => '终止运营',
        '复活'    => '重启',
        '回归'    => '重返',
        '预约'    => '预定',
        '首发'    => '首次推出',
        '独占'    => '独家',
        '免费'    => '无需付费',
        '内购'    => '内置购买',
        '礼包'    => '奖励包',
        '签到'    => '每日登录',
        '排行榜'  => '天梯',
        '匹配'    => '组队',
        '排位赛'  => '竞技场',
        '巅峰赛'  => '顶级竞技',
        '全球赛'  => '世界大赛',
        '冠军'    => '第一名',
        '硬核'    => '硬派',
        '休闲'    => '轻松',
        '养老'    => '轻松养成',
        '佛系'    => '随缘',
        '翻盘'    => '逆转',
        '逆风'    => '劣势',
        '顺风'    => '优势',
        '配合'    => '协作',
        '意识'    => '判断力',
        '走位'    => '移动',
        '资讯'    => '动态',
        '新闻'    => '消息',
        '精选'    => '甄选',
        '下载'    => '获取',
        '安装'    => '部署',
        '论坛'    => '社区',
        '粉丝'    => '爱好者',
        '水友'    => '观众',
        '云玩家'  => '观察者',
        '萌新'    => '新手',
        '菜鸟'    => '入门玩家',
        '高玩'    => '资深玩家',
        '大佬'    => '高手',
        '真香'    => '实际体验后赞',
        '吹爆'    => '极力推荐',
        '踩雷'    => '遇到问题',
        '翻车'    => '失败',
        '白嫖'    => '零投入',
        '搬砖'    => '获取资源',
        '上头'    => '沉迷',
        '毒奶'    => '不看好',
        '安利'    => '推荐',
        '拔草'    => '打消念头',
        '种草'    => '引起兴趣',
        '剁手'    => '购买',
        '吃土'    => '节省开支',
        '复盘'    => '回顾',
        '吐槽'    => '评论',
        'YYDS'    => '永远的神',
        '脑洞'    => '创意',
        '补刀'    => '终结',
        '躺赢'    => '轻松获胜',
        '超神'    => '超凡表现',
        '五杀'    => '连杀五人',
        '团灭'    => '全军覆没',
    );

    /**
     * 同义词替换率
     * @var float
     */
    private $synonym_ratio = 0.18;

    /**
     * 段落随机交换概率
     * @var float
     */
    private $swap_probability = 0.3;

    /**
     * 短段落合并阈值（字符数）
     * @var int
     */
    private $merge_threshold = 50;

    /**
     * 长段落拆分阈值（字符数）
     * @var int
     */
    private $split_threshold = 300;

    /**
     * AI 重组相似度阈值
     * @var float
     */
    private $ai_similarity_threshold = 0.6;

    /**
     * AI 重组最小文章长度
     * @var int
     */
    private $ai_min_length = 1000;

    /**
     * 递归深度计数器（防止 cms_ai_generate hook 导致的无限递归）
     * @var int
     */
    private static $reorganize_depth = 0;

    /**
     * 最大允许递归深度
     * @var int
     */
    const MAX_REORGANIZE_DEPTH = 2;

    // ==================== 主入口 ====================

    /**
     * 内容重组主入口
     *
     * @param string $content 原始内容
     * @param array $config 配置项
     *   - ai_reorganize (bool) 是否开启 AI 深度重组，默认 false
     *   - synonym_ratio (float) 同义词替换率，默认 0.18
     * @return string 重组后内容
     */
    public function reorganize($content, $config = array()) {
        if (empty($content) || !is_string($content)) {
            return $content;
        }

        // 递归深度保护：超过最大深度时跳过 AI 深度重组，防止无限递归
        if (self::$reorganize_depth >= self::MAX_REORGANIZE_DEPTH) {
            if (isset($config['ai_reorganize'])) {
                $config['ai_reorganize'] = false;
            }
        }
        self::$reorganize_depth++;

        // 应用可选配置
        if (isset($config['synonym_ratio'])) {
            $this->synonym_ratio = (float)$config['synonym_ratio'];
        }

        // 步骤 1：同义词替换（本地处理，零 AI 成本）
        $content = $this->replace_synonyms($content);

        // 步骤 2：段落重组（本地处理）
        $content = $this->reorganize_paragraphs($content);

        // 步骤 3：动态字段刷新（本地处理）
        $content = $this->refresh_dynamic_fields($content);

        // 步骤 4：判断是否需要 AI 深度重组
        if ($this->should_ai_reorganize($content, $config)) {
            $content = $this->ai_reorganize($content, $config);
        }

        self::$reorganize_depth--;

        return $content;
    }

    // ==================== 同义词替换 ====================

    /**
     * 使用 strtr() 批量替换同义词
     * 随机选取约 15-20% 的同义词进行替换
     *
     * @param string $content
     * @return string
     */
    protected function replace_synonyms($content) {
        // 筛选出内容中实际出现的同义词
        $matched = array();
        foreach ($this->synonym_dict as $original => $replacement) {
            if ($original !== $replacement && strpos($content, $original) !== false) {
                $matched[$original] = $replacement;
            }
        }

        if (empty($matched)) {
            return $content;
        }

        // 随机选取约 15-20% 的同义词条目
        $matched_keys = array_keys($matched);
        shuffle($matched_keys);
        $replace_count = (int)ceil(count($matched_keys) * $this->synonym_ratio);
        $replace_count = max(1, min($replace_count, count($matched_keys)));
        $selected_keys = array_slice($matched_keys, 0, $replace_count);

        // 构建 strtr 替换字典
        $replace_dict = array();
        foreach ($selected_keys as $key) {
            $replace_dict[$key] = $matched[$key];
        }

        return strtr($content, $replace_dict);
    }

    // ==================== 段落重组 ====================

    /**
     * 段落重组
     * - 随机交换相邻段落（概率 30%）
     * - 合并短段落（< 50 字）
     * - 拆分长段落（> 300 字）
     *
     * @param string $content
     * @return string
     */
    protected function reorganize_paragraphs($content) {
        // 按双换行分割段落
        $paragraphs = explode("\n\n", $content);

        if (count($paragraphs) <= 1) {
            return $content;
        }

        $result = array();
        $i = 0;
        $count = count($paragraphs);

        while ($i < $count) {
            $current = $paragraphs[$i];
            $current_len = $this->strlen($current);

            // 合并短段落（< 50 字）与下一段
            if ($current_len < $this->merge_threshold && $i + 1 < $count) {
                $result[] = $current . "\n\n" . $paragraphs[$i + 1];
                $i += 2;
                continue;
            }

            // 随机交换相邻段落（概率 30%）
            if ($i + 1 < $count && mt_rand(0, 100) < ($this->swap_probability * 100)) {
                $result[] = $paragraphs[$i + 1];
                $result[] = $current;
                $i += 2;
                continue;
            }

            // 拆分长段落（> 300 字）
            if ($current_len > $this->split_threshold) {
                $split = $this->split_paragraph($current);
                foreach ($split as $part) {
                    $result[] = $part;
                }
                $i++;
                continue;
            }

            $result[] = $current;
            $i++;
        }

        return implode("\n\n", $result);
    }

    /**
     * 拆分超长段落（按句子边界拆分）
     *
     * @param string $paragraph
     * @return array 拆分后的段落数组
     */
    protected function split_paragraph($paragraph) {
        $result = array();

        if ($this->strlen($paragraph) <= $this->split_threshold) {
            $result[] = $paragraph;
            return $result;
        }

        // 按中文句号和常见分隔符分割
        $sentences = preg_split('/([。！？；\n])/u', $paragraph, -1, PREG_SPLIT_DELIM_CAPTURE);

        $chunk = '';
        $chunk_len = 0;

        foreach ($sentences as $sentence) {
            $chunk .= $sentence;
            $chunk_len += $this->strlen($sentence);

            if ($chunk_len >= $this->split_threshold && trim($chunk) !== '') {
                $result[] = trim($chunk);
                $chunk = '';
                $chunk_len = 0;
            }
        }

        if (trim($chunk) !== '') {
            $result[] = trim($chunk);
        }

        // 如果整段无法拆分（极少数情况），原样返回
        if (empty($result)) {
            $result[] = $paragraph;
        }

        return $result;
    }

    // ==================== 动态字段刷新 ====================

    /**
     * 刷新动态字段（日期、评分等）
     *
     * @param string $content
     * @return string
     */
    protected function refresh_dynamic_fields($content) {
        // 替换日期为当前日期
        $current_date_full = date('Y年m月d日');
        $current_date_short = date('Y-m-d');

        // 匹配 YYYY年MM月DD日 格式（最多替换 3 处）
        $content = preg_replace(
            '/\d{4}年\d{1,2}月\d{1,2}日/',
            $current_date_full,
            $content,
            3
        );

        // 匹配 YYYY-MM-DD 格式（最多替换 3 处）
        $content = preg_replace(
            '/\d{4}-\d{2}-\d{2}/',
            $current_date_short,
            $content,
            3
        );

        // 替换游戏评分为随机浮动评分
        $content = preg_replace_callback(
            '/(\d+(?:\.\d+)?)\s*分/',
            function ($matches) {
                $score = (float)$matches[1];
                $new_score = $score + (mt_rand(-50, 50) / 100);
                $new_score = round(max(0, min(10, $new_score)), 1);
                return $new_score . '分';
            },
            $content
        );

        // 替换发布/上线日期标注
        $content = preg_replace(
            '/(?:发布|上线|推出)(?:日期|时间)[：:]\s*\S+/u',
            '发布日期：' . $current_date_full,
            $content,
            2
        );

        return $content;
    }

    // ==================== AI 深度重组 ====================

    /**
     * 判断是否需要 AI 深度重组
     *
     * 触发条件：
     * - 本地处理后相似度仍 > 60%
     * - 文章长度 > 1000 字
     * - 配置项 ai_reorganize = true
     *
     * @param string $content 本地处理后内容
     * @param array $config 配置项
     * @return bool
     */
    protected function should_ai_reorganize($content, $config) {
        // 检查配置开关
        $ai_enabled = isset($config['ai_reorganize']) ? (bool)$config['ai_reorganize'] : false;

        if (!$ai_enabled) {
            return false;
        }

        // 检查文章长度
        $content_len = $this->strlen($content);
        if ($content_len <= $this->ai_min_length) {
            return false;
        }

        // 检查相似度（通过内容变化率间接估算）
        $similarity = $this->estimate_similarity($content);

        return $similarity > $this->ai_similarity_threshold;
    }

    /**
     * 估算内容相似度（简化版）
     * 基于标点密度和结构重复度估算模板化程度
     * 实际项目中建议接入 AI 相似度检测
     *
     * @param string $content
     * @return float 相似度 0-1
     */
    protected function estimate_similarity($content) {
        $total_chars = $this->strlen($content);

        if ($total_chars === 0) {
            return 1.0;
        }

        // 统计标点密度（高标点密度通常意味着模板化内容）
        $punctuation_count = preg_match_all('/[。，！？；：""\'\'、\s]/u', $content);
        $density = $punctuation_count / $total_chars;

        // 映射：density 0.1 -> 0.3, density 0.3 -> 0.7
        $similarity = 0.3 + ($density - 0.1) * 2.0;
        $similarity = max(0, min(1, $similarity));

        return $similarity;
    }

    /**
     * AI 深度重组
     * 触发 CMS AI 系统进行深度内容改写
     *
     * @param string $content
     * @param array $config
     * @return string
     */
    protected function ai_reorganize($content, $config) {
        // 递归深度保护：超过最大深度时直接返回原文
        if (self::$reorganize_depth >= self::MAX_REORGANIZE_DEPTH) {
            return $content;
        }

        // 尝试调用 CMS AI 生成接口进行深度重组
        if (function_exists('cms_ai_generate')) {
            return cms_ai_generate($content, array_merge($config, array(
                'mode' => 'reorganize',
                'prompt' => '请对以下游戏文章进行深度改写，保持原意但大幅改变表达方式和行文结构，确保与原文相似度低于60%',
            )));
        }

        // CMS AI 不可用时返回本地处理结果
        return $content;
    }

    // ==================== 工具方法 ====================

    /**
     * 兼容 PHP 5.4 的 strlen 替代（支持多字节字符）
     *
     * @param string $str
     * @return int
     */
    protected function strlen($str) {
        if (function_exists('mb_strlen')) {
            return mb_strlen($str, 'UTF-8');
        }
        return strlen($str);
    }
}
