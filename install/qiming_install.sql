-- YzmCMS 起名系统数据库初始化脚本
-- 执行前请确保已创建数据库并选中

-- -------------------------------------------------

-- 汉字表
CREATE TABLE IF NOT EXISTS `yzmcms_chinese_characters` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `char` varchar(10) NOT NULL COMMENT '汉字',
  `pinyin` varchar(50) NOT NULL COMMENT '拼音',
  `zhuyin` varchar(50) DEFAULT NULL COMMENT '注音',
  `bushou` varchar(10) DEFAULT NULL COMMENT '部首',
  `bihua` tinyint(3) NOT NULL DEFAULT '0' COMMENT '笔画数',
  `wuxing` tinyint(1) NOT NULL DEFAULT '5' COMMENT '五行(1金2木3水4火5土)',
  `jx` text COMMENT '康熙字典解释',
  `cy` text COMMENT '词语解释',
  `xmxy` text COMMENT '起名寓意',
  PRIMARY KEY (`id`),
  UNIQUE KEY `char` (`char`),
  KEY `pinyin` (`pinyin`),
  KEY `wuxing` (`wuxing`),
  KEY `bihua` (`bihua`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='汉字表';

-- 诗词表
CREATE TABLE IF NOT EXISTS `yzmcms_poetry` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL COMMENT '标题',
  `author` varchar(50) NOT NULL COMMENT '作者',
  `type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '类型(1唐诗2宋词3诗经4楚辞)',
  `content` text NOT NULL COMMENT '内容',
  `dynasty` varchar(20) DEFAULT NULL COMMENT '朝代',
  `theme` varchar(50) DEFAULT NULL COMMENT '主题',
  PRIMARY KEY (`id`),
  KEY `type` (`type`),
  KEY `author` (`author`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='诗词表';

-- 八卦表
CREATE TABLE IF NOT EXISTS `yzmcms_bagua` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `gua_name` varchar(20) NOT NULL COMMENT '卦名',
  `gua_ci` text COMMENT '卦辞',
  `tuan_ci` text COMMENT '彖辞',
  `xiang_ci` text COMMENT '象辞',
  `yao_ci` text COMMENT '爻辞',
  `wuxing` varchar(10) DEFAULT NULL COMMENT '五行',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='八卦表';

-- 黄历表
CREATE TABLE IF NOT EXISTS `yzmcms_horoscope` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL COMMENT '日期',
  `lunar_date` varchar(50) DEFAULT NULL COMMENT '农历日期',
  `zodiac` varchar(10) DEFAULT NULL COMMENT '生肖',
  `zodiac_year` varchar(20) DEFAULT NULL COMMENT '干支年',
  `zodiac_month` varchar(20) DEFAULT NULL COMMENT '干支月',
  `zodiac_day` varchar(20) DEFAULT NULL COMMENT '干支日',
  `yi` text COMMENT '宜事项',
  `ji` text COMMENT '忌事项',
  `jishi` varchar(100) DEFAULT NULL COMMENT '吉时',
  `caishen` varchar(20) DEFAULT NULL COMMENT '财神方位',
  `xishen` varchar(20) DEFAULT NULL COMMENT '喜神方位',
  `fushen` varchar(20) DEFAULT NULL COMMENT '福神方位',
  `chongsha` varchar(50) DEFAULT NULL COMMENT '冲煞信息',
  PRIMARY KEY (`id`),
  UNIQUE KEY `date` (`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='黄历表';

-- 热门排行表
CREATE TABLE IF NOT EXISTS `yzmcms_name_rankings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `char_or_name` varchar(50) NOT NULL COMMENT '汉字或姓名',
  `type` varchar(20) NOT NULL COMMENT '类型(boy-char/girl-char/boy-name/girl-name)',
  `ranking` int(5) NOT NULL DEFAULT '0' COMMENT '排名',
  `month` varchar(7) DEFAULT NULL COMMENT '统计月份',
  `search_count` int(10) NOT NULL DEFAULT '0' COMMENT '搜索次数',
  PRIMARY KEY (`id`),
  KEY `type` (`type`),
  KEY `ranking` (`ranking`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='热门排行表';

-- 姓名测试记录表
CREATE TABLE IF NOT EXISTS `yzmcms_name_test_results` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `surname` varchar(10) NOT NULL COMMENT '姓氏',
  `name` varchar(20) NOT NULL COMMENT '名字',
  `tiange` tinyint(3) DEFAULT '0' COMMENT '天格',
  `dice` tinyint(3) DEFAULT '0' COMMENT '地格',
  `renge` tinyint(3) DEFAULT '0' COMMENT '人格',
  `waige` tinyint(3) DEFAULT '0' COMMENT '外格',
  `zongge` tinyint(3) DEFAULT '0' COMMENT '总格',
  `total_score` tinyint(3) DEFAULT '0' COMMENT '总分',
  `wuxing_analysis` text COMMENT '五行分析',
  `created_at` int(10) DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `surname` (`surname`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='姓名测试记录表';

-- -------------------------------------------------
-- 初始化热门排行数据
-- -------------------------------------------------

INSERT INTO `yzmcms_name_rankings` (`char_or_name`, `type`, `ranking`, `month`, `search_count`) VALUES
('圣', 'boy-char', 1, '2026-03', 1520),
('杰', 'boy-char', 2, '2026-03', 1480),
('浩', 'boy-char', 3, '2026-03', 1350),
('旭', 'boy-char', 4, '2026-03', 1280),
('尧', 'boy-char', 5, '2026-03', 1150),
('俊', 'boy-char', 6, '2026-03', 1080),
('天', 'boy-char', 7, '2026-03', 1020),
('磊', 'boy-char', 8, '2026-03', 980),
('伟', 'boy-char', 9, '2026-03', 920),
('博', 'boy-char', 10, '2026-03', 880),
('瑾', 'girl-char', 1, '2026-03', 1680),
('楠', 'girl-char', 2, '2026-03', 1520),
('莹', 'girl-char', 3, '2026-03', 1380),
('雪', 'girl-char', 4, '2026-03', 1290),
('晗', 'girl-char', 5, '2026-03', 1180),
('琴', 'girl-char', 6, '2026-03', 1050),
('晴', 'girl-char', 7, '2026-03', 980),
('丽', 'girl-char', 8, '2026-03', 920),
('瑶', 'girl-char', 9, '2026-03', 870),
('茜', 'girl-char', 10, '2026-03', 820);

-- -------------------------------------------------
-- 初始化八卦数据
-- -------------------------------------------------

INSERT INTO `yzmcms_bagua` (`gua_name`, `gua_ci`, `tuan_ci`, `xiang_ci`, `wuxing`) VALUES
('乾', '乾，元亨利贞。', '大通必将得志，然不可乱也。', '天行健，君子以自强不息。', '金'),
('坤', '坤，元亨，利牝马之贞。', '柔顺利贞。', '地势坤，君子以厚德载物。', '土'),
('屯', '屯，元亨利贞，勿用有攸往。', '刚柔始交而难生，动乎险中。', '云雷屯，君子以经纶。', '水'),
('蒙', '蒙，亨。匪我求童蒙，童蒙求我。', '蒙以养正，圣功也。', '山下出泉，蒙。君子以果行育德。', '水'),
('需', '需，有孚，光亨，贞吉。', '需，须险而能涉。', '云上于天，需。君子以饮食宴乐。', '水');

-- -------------------------------------------------
-- 完成
-- -------------------------------------------------
