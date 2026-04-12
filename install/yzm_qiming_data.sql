-- 起名系统数据库初始化数据
-- 版本: 1.0
-- 更新日期: 2026-03-25

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- 汉字数据表初始化
-- ----------------------------
DROP TABLE IF EXISTS `yzm_character`;
CREATE TABLE `yzm_character` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `char` varchar(10) NOT NULL COMMENT '汉字',
  `pinyin` varchar(50) NOT NULL COMMENT '拼音',
  `zhuyin` varchar(50) DEFAULT '' COMMENT '注音',
  `bushou` varchar(10) DEFAULT '' COMMENT '部首',
  `bihua` tinyint(3) unsigned DEFAULT '0' COMMENT '笔画数',
  `wuxing` tinyint(1) unsigned DEFAULT '0' COMMENT '五行:1金2木3水4火5土',
  `jx` text COMMENT '解释',
  `xmxy` text COMMENT '姓名寓意',
  `is_lucky` tinyint(1) unsigned DEFAULT '1' COMMENT '是否吉字:0否1是',
  `is_common` tinyint(1) unsigned DEFAULT '0' COMMENT '是否常用:0否1是',
  `is_boy` tinyint(1) unsigned DEFAULT '1' COMMENT '男孩常用:0否1是',
  `is_girl` tinyint(1) unsigned DEFAULT '1' COMMENT '女孩常用:0否1是',
  `create_time` int(10) unsigned DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `char` (`char`),
  KEY `pinyin` (`pinyin`),
  KEY `wuxing` (`wuxing`),
  KEY `bihua` (`bihua`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='汉字表';

-- 插入常用汉字数据
INSERT INTO `yzm_character` (`char`, `pinyin`, `zhuyin`, `bushou`, `bihua`, `wuxing`, `jx`, `xmxy`, `is_lucky`, `is_common`, `is_boy`, `is_girl`, `create_time`) VALUES
('博', 'bó', '博', '十', 12, 1, '博学多才，知识渊博', '寓意学识渊博，前程远大', 1, 1, 1, 0, UNIX_TIMESTAMP()),
('浩', 'hào', '浩', '氵', 11, 3, '浩大，广阔', '寓意胸怀宽广，气势磅礴', 1, 1, 1, 0, UNIX_TIMESTAMP()),
('杰', 'jié', '杰', '木', 8, 2, '才能出众', '寓意才智出众，超群绝伦', 1, 1, 1, 0, UNIX_TIMESTAMP()),
('梓', 'zǐ', '梓', '木', 11, 2, '梓树，故乡', '寓意生机勃勃，充满希望', 1, 1, 1, 0, UNIX_TIMESTAMP()),
('晨', 'chén', '晨', '日', 11, 4, '早晨，阳光', '寓意朝气蓬勃，充满活力', 1, 1, 1, 0, UNIX_TIMESTAMP()),
('宇', 'yǔ', '宇', '宀', 6, 3, '宇宙，空间', '寓意胸襟开阔，志向高远', 1, 1, 1, 1, UNIX_TIMESTAMP()),
('轩', 'xuān', '轩', '车', 7, 2, '高扬，气度', '寓意才华横溢，气质不凡', 1, 1, 1, 0, UNIX_TIMESTAMP()),
('泽', 'zé', '泽', '氵', 8, 3, '光泽，恩泽', '寓意恩泽深厚，福气连连', 1, 1, 1, 0, UNIX_TIMESTAMP()),
('睿', 'ruì', '睿', '目', 14, 2, '明智，通达', '寓意聪明睿智，深谋远虑', 1, 1, 1, 0, UNIX_TIMESTAMP()),
('哲', 'zhé', '哲', '口', 10, 2, '明智，聪明', '寓意聪明伶俐，智慧超群', 1, 1, 1, 0, UNIX_TIMESTAMP()),
('明', 'míng', '明', '日', 8, 4, '明亮，清楚', '寓意前途光明，聪明睿智', 1, 1, 1, 0, UNIX_TIMESTAMP()),
('远', 'yuǎn', '远', '辶', 7, 3, '距离大，深远', '寓意志向远大，前程似锦', 1, 1, 1, 0, UNIX_TIMESTAMP()),
('瑾', 'jǐn', '瑾', '王', 16, 1, '美玉，美德', '寓意美玉无瑕，品德高尚', 1, 1, 0, 1, UNIX_TIMESTAMP()),
('琪', 'qí', '琪', '王', 13, 1, '美玉，珍异', '寓意珍贵美好，品德如玉', 1, 1, 0, 1, UNIX_TIMESTAMP()),
('瑶', 'yáo', '瑶', '王', 14, 2, '美玉，美好', '寓意美玉珍贵，温柔善良', 1, 1, 0, 1, UNIX_TIMESTAMP()),
('婷', 'tíng', '婷', '女', 12, 2, '美好，优美', '寓意亭亭玉立，优雅大方', 1, 1, 0, 1, UNIX_TIMESTAMP()),
('欣', 'xīn', '欣', '斤', 8, 2, '喜悦，喜爱', '寓意心情愉悦，乐观开朗', 1, 1, 0, 1, UNIX_TIMESTAMP()),
('怡', 'yí', '怡', '忄', 9, 2, '和悦，愉快', '寓意温和可亲，快乐幸福', 1, 1, 0, 1, UNIX_TIMESTAMP()),
('宁', 'níng', '宁', '宀', 14, 3, '安定，宁静', '寓意平安宁静，幸福安康', 1, 1, 1, 1, UNIX_TIMESTAMP()),
('静', 'jìng', '静', '青', 16, 2, '安静，宁静', '寓意文静优雅，心态平和', 1, 1, 0, 1, UNIX_TIMESTAMP()),
('涵', 'hán', '涵', '氵', 11, 3, '包含，包容', '寓意学识渊博，胸怀宽广', 1, 1, 1, 0, UNIX_TIMESTAMP()),
('蕊', 'ruǐ', '蕊', '艹', 18, 2, '花蕊，精华', '寓意美丽动人，才华出众', 1, 1, 0, 1, UNIX_TIMESTAMP()),
('雪', 'xuě', '雪', '雨', 11, 3, '雪花，洁白', '寓意纯洁无瑕，心灵美好', 1, 1, 0, 1, UNIX_TIMESTAMP()),
('琳', 'lín', '琳', '王', 13, 1, '美玉，美好', '寓意美玉珍贵，品德高尚', 1, 1, 0, 1, UNIX_TIMESTAMP()),
('旭', 'xù', '旭', '日', 6, 4, '日出，光明', '寓意朝气蓬勃，充满希望', 1, 1, 1, 0, UNIX_TIMESTAMP()),
('尧', 'yáo', '尧', '垚', 12, 2, '高远，圣明', '寓意品德高尚，志向远大', 1, 1, 1, 0, UNIX_TIMESTAMP()),
('俊', 'jùn', '俊', '亻', 9, 2, '才智出众', '寓意才貌双全，超群绝伦', 1, 1, 1, 0, UNIX_TIMESTAMP()),
('豪', 'háo', '豪', '豕', 14, 2, '豪迈，气魄', '寓意才华横溢，气度非凡', 1, 1, 1, 0, UNIX_TIMESTAMP()),
('炜', 'wěi', '炜', '火', 9, 4, '光明，光辉', '寓意光彩照人，前程光明', 1, 1, 1, 0, UNIX_TIMESTAMP()),
('彬', 'bīn', '彬', '彡', 11, 2, '文雅，有礼貌', '寓意文质彬彬，温文尔雅', 1, 1, 1, 0, UNIX_TIMESTAMP());

-- ----------------------------
-- 诗词数据表初始化
-- ----------------------------
DROP TABLE IF EXISTS `yzm_poetry`;
CREATE TABLE `yzm_poetry` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL COMMENT '诗词标题',
  `author` varchar(50) NOT NULL COMMENT '作者',
  `dynasty` varchar(20) DEFAULT '' COMMENT '朝代',
  `type` tinyint(1) unsigned DEFAULT '1' COMMENT '类型:1唐诗2宋词3诗经4楚辞',
  `content` text NOT NULL COMMENT '内容',
  `theme` varchar(50) DEFAULT '' COMMENT '主题',
  `interpretation` text COMMENT '译文',
  `appreciation` text COMMENT '赏析',
  `create_time` int(10) unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `type` (`type`),
  KEY `author` (`author`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='诗词表';

-- 插入诗词数据
INSERT INTO `yzm_poetry` (`title`, `author`, `dynasty`, `type`, `content`, `theme`, `interpretation`, `appreciation`, `create_time`) VALUES
('静夜思', '李白', '唐', 1, '床前明月光，疑是地上霜。举头望明月，低头思故乡。', '思念', '明亮的月光洒在床前，就像是地上铺了一层霜。抬起头望着那轮明月，不由得低下头思念起故乡来。', '这首诗描写了秋夜望月思乡的情景，语言清新朴素，明白如话。', UNIX_TIMESTAMP()),
('春晓', '孟浩然', '唐', 1, '春眠不觉晓，处处闻啼鸟。夜来风雨声，花落知多少。', '春天', '春天睡醒时天已经亮了，醒来时到处可以听见鸟的叫声。想起昨夜的风雨声，不知道有多少花朵被吹落了。', '这首诗抓住春晨生活的一个片段，表达了对春光的珍惜和对自然的热爱。', UNIX_TIMESTAMP()),
('登鹳雀楼', '王之涣', '唐', 1, '白日依山尽，黄河入海流。欲穷千里目，更上一层楼。', '励志', '夕阳靠着山峦渐渐西沉，黄河朝着大海汹涌奔流。想要看到千里之外的风光，那就再登上一层楼吧。', '这首诗情景交融，意境开阔，表达了积极向上的精神。', UNIX_TIMESTAMP()),
('相思', '王维', '唐', 1, '红豆生南国，春来发几枝。愿君多采撷，此物最相思。', '思念', '红豆生长在南方，春天到了它又发出新枝。希望你能多多采摘它，因为它最能寄托相思之情。', '这首诗借红豆寄托相思之情，语言明快，感情真挚。', UNIX_TIMESTAMP()),
('蒹葭', '诗经', '先秦', 3, '蒹葭苍苍，白露为霜。所谓伊人，在水一方。溯洄从之，道阻且长。溯游从之，宛在水中央。', '爱情', '河边芦苇青苍苍，露水凝结成霜。我所怀念的心上人啊，就在对岸那一边。', '这是一首著名的爱情诗，描写了对意中人的思念和追求。', UNIX_TIMESTAMP()),
('关雎', '诗经', '先秦', 3, '关关雎鸠，在河之洲。窈窕淑女，君子好逑。参差荇菜，左右流之。窈窕淑女，寤寐求之。', '爱情', '雎鸠鸟在河中小岛上鸣叫，美丽贤淑的女子，是君子的好配偶。', '这是《诗经》的开篇之作，描写了青年男子对美丽女子的追求。', UNIX_TIMESTAMP());

-- ----------------------------
-- 排行榜数据表初始化
-- ----------------------------
DROP TABLE IF EXISTS `yzm_name_rankings`;
CREATE TABLE `yzm_name_rankings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `char_or_name` varchar(50) NOT NULL COMMENT '汉字或名字',
  `pinyin` varchar(100) DEFAULT '' COMMENT '拼音',
  `type` varchar(20) NOT NULL COMMENT '类型:boy-char/girl-char/boy-name/girl-name',
  `ranking` smallint(5) unsigned DEFAULT '0' COMMENT '排名',
  `month` varchar(10) DEFAULT '' COMMENT '统计月份',
  `search_count` int(10) unsigned DEFAULT '0' COMMENT '搜索次数',
  `is_lucky` tinyint(1) unsigned DEFAULT '1' COMMENT '是否吉字',
  `create_time` int(10) unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `type` (`type`),
  KEY `ranking` (`ranking`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='排行榜表';

-- 插入男孩用字排行
INSERT INTO `yzm_name_rankings` (`char_or_name`, `pinyin`, `type`, `ranking`, `month`, `search_count`, `is_lucky`) VALUES
('博', 'bó', 'boy-char', 1, '2026-03', 15820, 1),
('杰', 'jié', 'boy-char', 2, '2026-03', 14560, 1),
('浩', 'hào', 'boy-char', 3, '2026-03', 13980, 1),
('旭', 'xù', 'boy-char', 4, '2026-03', 12890, 1),
('尧', 'yáo', 'boy-char', 5, '2026-03', 11540, 1),
('俊', 'jùn', 'boy-char', 6, '2026-03', 10870, 1),
('豪', 'háo', 'boy-char', 7, '2026-03', 9850, 1),
('宇', 'yǔ', 'boy-char', 8, '2026-03', 9240, 1),
('轩', 'xuān', 'boy-char', 9, '2026-03', 8890, 1),
('泽', 'zé', 'boy-char', 10, '2026-03', 8560, 1);

-- 插入女孩用字排行
INSERT INTO `yzm_name_rankings` (`char_or_name`, `pinyin`, `type`, `ranking`, `month`, `search_count`, `is_lucky`) VALUES
('瑾', 'jǐn', 'girl-char', 1, '2026-03', 15230, 1),
('琪', 'qí', 'girl-char', 2, '2026-03', 14380, 1),
('婷', 'tíng', 'girl-char', 3, '2026-03', 13890, 1),
('欣', 'xīn', 'girl-char', 4, '2026-03', 12450, 1),
('怡', 'yí', 'girl-char', 5, '2026-03', 11870, 1),
('瑶', 'yáo', 'girl-char', 6, '2026-03', 11230, 1),
('雪', 'xuě', 'girl-char', 7, '2026-03', 10680, 1),
('琳', 'lín', 'girl-char', 8, '2026-03', 9980, 1),
('静', 'jìng', 'girl-char', 9, '2026-03', 9450, 1),
('宁', 'níng', 'girl-char', 10, '2026-03', 8920, 1);

-- 插入男孩名字排行
INSERT INTO `yzm_name_rankings` (`char_or_name`, `pinyin`, `type`, `ranking`, `month`, `search_count`) VALUES
('浩然', 'hào rán', 'boy-name', 1, '2026-03', 25680),
('梓轩', 'zǐ xuān', 'boy-name', 2, '2026-03', 23890),
('晨宇', 'chén yǔ', 'boy-name', 3, '2026-03', 21540),
('明哲', 'míng zhé', 'boy-name', 4, '2026-03', 19870),
('泽轩', 'zé xuān', 'boy-name', 5, '2026-03', 18760),
('俊杰', 'jùn jié', 'boy-name', 6, '2026-03', 17650),
('宇轩', 'yǔ xuān', 'boy-name', 7, '2026-03', 16540),
('子轩', 'zǐ xuān', 'boy-name', 8, '2026-03', 15430),
('浩宇', 'hào yǔ', 'boy-name', 9, '2026-03', 14320),
('志豪', 'zhì háo', 'boy-name', 10, '2026-03', 13210);

-- 插入女孩名字排行
INSERT INTO `yzm_name_rankings` (`char_or_name`, `pinyin`, `type`, `ranking`, `month`, `search_count`) VALUES
('欣怡', 'xīn yí', 'girl-name', 1, '2026-03', 24350),
('梓涵', 'zǐ hán', 'girl-name', 2, '2026-03', 22870),
('欣瑶', 'xīn yáo', 'girl-name', 3, '2026-03', 21360),
('雨婷', 'yǔ tíng', 'girl-name', 4, '2026-03', 19890),
('静怡', 'jìng yí', 'girl-name', 5, '2026-03', 18450),
('思琪', 'sī qí', 'girl-name', 6, '2026-03', 17230),
('雨欣', 'yǔ xīn', 'girl-name', 7, '2026-03', 16890),
('雅婷', 'yǎ tíng', 'girl-name', 8, '2026-03', 15540),
('雨萱', 'yǔ xuān', 'girl-name', 9, '2026-03', 14230),
('欣妍', 'xīn yán', 'girl-name', 10, '2026-03', 13120);

-- ----------------------------
-- 八卦数据表初始化
-- ----------------------------
DROP TABLE IF EXISTS `yzm_bagua`;
CREATE TABLE `yzm_bagua` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `gua_name` varchar(10) NOT NULL COMMENT '卦名',
  `gua_ci` varchar(100) DEFAULT '' COMMENT '卦辞',
  `tuan_ci` varchar(100) DEFAULT '' COMMENT '彖传',
  `xiang_ci` text COMMENT '象传',
  `yao_ci` text COMMENT '爻辞',
  `wuxing` varchar(10) DEFAULT '' COMMENT '五行',
  `create_time` int(10) unsigned DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='八卦表';

INSERT INTO `yzm_bagua` (`gua_name`, `gua_ci`, `tuan_ci`, `xiang_ci`, `yao_ci`, `wuxing`, `create_time`) VALUES
('乾', '元亨利贞', '大哉乾元，万物资始', '天行健，君子以自强不息', '初九：潜龙勿用。九二：见龙在田，利见大人。', '金', UNIX_TIMESTAMP()),
('坤', '元亨利牝马之贞', '至哉坤元，万物资生', '地势坤，君子以厚德载物', '初六：履霜，坚冰至。', '土', UNIX_TIMESTAMP()),
('屯', '元亨利贞，勿用有攸往', '屯，刚柔始交而难生', '云雷屯，君子以经纶', '初九：磐桓，利居贞，利建侯。', '水', UNIX_TIMESTAMP()),
('蒙', '亨，匪我求童蒙，童蒙求我', '蒙，山下有险', '山下出泉，蒙', '初六：发蒙，利用刑人，用说桎梏。', '土', UNIX_TIMESTAMP()),
('需', '有孚，光亨，贞吉，位乎天位', '需，须也，险在前也', '云上于天，需', '初九：需于郊，利用恒，无咎。', '水', UNIX_TIMESTAMP()),
('讼', '有孚，窒，惕，中吉', '讼，上刚下险', '天与水违行，讼', '初六：不永所事，小有言，终吉。', '金', UNIX_TIMESTAMP());

-- ----------------------------
-- 黄历数据表初始化
-- ----------------------------
DROP TABLE IF EXISTS `yzm_horoscope`;
CREATE TABLE `yzm_horoscope` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL COMMENT '日期',
  `lunar_date` varchar(50) DEFAULT '' COMMENT '农历日期',
  `weekday` varchar(20) DEFAULT '' COMMENT '星期',
  `zodiac` varchar(20) DEFAULT '' COMMENT '星座',
  `zodiac_year` varchar(20) DEFAULT '' COMMENT '年柱',
  `zodiac_month` varchar(20) DEFAULT '' COMMENT '月柱',
  `zodiac_day` varchar(20) DEFAULT '' COMMENT '日柱',
  `yi` varchar(200) DEFAULT '' COMMENT '宜',
  `ji` varchar(200) DEFAULT '' COMMENT '忌',
  `jieri` varchar(100) DEFAULT '' COMMENT '节日',
  `jishi` varchar(100) DEFAULT '' COMMENT '吉时',
  `chongsha` varchar(50) DEFAULT '' COMMENT '冲煞',
  `caishen` varchar(20) DEFAULT '' COMMENT '财神方位',
  `xishen` varchar(20) DEFAULT '' COMMENT '喜神方位',
  `fushen` varchar(20) DEFAULT '' COMMENT '福神方位',
  `create_time` int(10) unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='黄历表';

-- 插入今日黄历数据
INSERT INTO `yzm_horoscope` (`date`, `lunar_date`, `weekday`, `zodiac`, `zodiac_year`, `zodiac_month`, `zodiac_day`, `yi`, `ji`, `jieri`, `jishi`, `chongsha`, `caishen`, `xishen`, `fushen`, `create_time`) VALUES
(CURDATE(), '农历二月初七', '星期三', '白羊座', '丙午年', '辛卯月', '戊戌日', '纳采,交易,立券,安床,安机械,安葬', '嫁娶,开光,作灶', '春分', '子寅巳申酉亥', '冲龙', '正北', '东南', '东北', UNIX_TIMESTAMP());

-- ----------------------------
-- 姓名测试记录表
-- ----------------------------
DROP TABLE IF EXISTS `yzm_name_test_results`;
CREATE TABLE `yzm_name_test_results` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `surname` varchar(10) NOT NULL COMMENT '姓',
  `name` varchar(50) NOT NULL COMMENT '名',
  `gender` tinyint(1) unsigned DEFAULT '1' COMMENT '性别:1男2女',
  `score` smallint(4) DEFAULT '0' COMMENT '综合评分',
  `tian_ge` smallint(3) DEFAULT '0' COMMENT '天格',
  `di_ge` smallint(3) DEFAULT '0' COMMENT '地格',
  `ren_ge` smallint(3) DEFAULT '0' COMMENT '人格',
  `wai_ge` smallint(3) DEFAULT '0' COMMENT '外格',
  `zong_ge` smallint(3) DEFAULT '0' COMMENT '总格',
  `wuxing_info` text COMMENT '五行信息JSON',
  `status` tinyint(1) unsigned DEFAULT '1' COMMENT '状态:0无效1有效',
  `ip` varchar(50) DEFAULT '' COMMENT 'IP地址',
  `created_at` datetime DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='姓名测试记录表';

SET FOREIGN_KEY_CHECKS = 1;
