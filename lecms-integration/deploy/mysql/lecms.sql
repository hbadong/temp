/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.11.14-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: lecms
-- ------------------------------------------------------
-- Server version	10.11.14-MariaDB-0ubuntu0.24.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `lecms_category`
--

DROP TABLE IF EXISTS `lecms_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lecms_category` (
  `cid` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '分类ID',
  `mid` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT '内容模型ID',
  `type` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT '分类类型 (0为列表，1为频道)',
  `upid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '上级CID',
  `name` char(50) NOT NULL DEFAULT '' COMMENT '分类名称',
  `alias` char(50) NOT NULL DEFAULT '' COMMENT 'URL别名',
  `pic` varchar(255) NOT NULL DEFAULT '' COMMENT '图片地址',
  `intro` varchar(255) NOT NULL DEFAULT '' COMMENT '分类介绍',
  `cate_tpl` varchar(80) NOT NULL DEFAULT '' COMMENT '分类页模板',
  `show_tpl` varchar(80) NOT NULL DEFAULT '' COMMENT '内容页模板',
  `count` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '内容数',
  `orderby` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '排序值',
  `seo_title` varchar(255) NOT NULL DEFAULT '' COMMENT 'SEO标题',
  `seo_keywords` varchar(255) NOT NULL DEFAULT '' COMMENT 'SEO关键词',
  `seo_description` varchar(255) NOT NULL DEFAULT '' COMMENT 'SEO描述',
  `contribute` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT '允许投稿',
  `seo_title_rule` varchar(255) NOT NULL DEFAULT '' COMMENT '内容SEO标题规则',
  `seo_keywords_rule` varchar(255) NOT NULL DEFAULT '' COMMENT '内容SEO关键词规则',
  `seo_description_rule` varchar(255) NOT NULL DEFAULT '' COMMENT '内容SEO描述规则',
  PRIMARY KEY (`cid`),
  UNIQUE KEY `alias` (`alias`),
  KEY `mid` (`mid`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='分类表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lecms_category`
--

LOCK TABLES `lecms_category` WRITE;
/*!40000 ALTER TABLE `lecms_category` DISABLE KEYS */;
INSERT INTO `lecms_category` VALUES
(1,2,1,0,'八字起名','bazi','','八字起名相关原创资讯与知识','article_list.htm','article_show.htm',6,0,'','','八字起名栏目：讲清生辰八字与取名用字的五行关系，包含排盘、喜用神判断、起名流程与常见误区，提供免费起名的完整思路。',1,'','',''),
(2,2,1,0,'诗词起名','shici','','诗词起名相关原创资讯与知识','article_list.htm','article_show.htm',6,0,'','','诗词起名栏目：从诗经、楚辞、唐诗、宋词中提炼名字，逐条标注出处与原句，附字义解析与音律点评，兼顾文化底蕴与去重技巧。',1,'','',''),
(3,2,1,0,'周易起名','zhouyi','','周易起名相关原创资讯与知识','article_list.htm','article_show.htm',6,0,'','','周易起名栏目：依据六十四卦卦辞爻辞取意命名，详解乾坤谦晋等卦象的取名方法，附实操案例与数理核对要点。',1,'','',''),
(4,2,1,0,'起名知识','zhishi','','起名知识相关原创资讯与知识','article_list.htm','article_show.htm',6,0,'','','起名知识栏目：涵盖声调搭配、姓氏笔画、用字宜忌、二胎呼应、评分解读与取名潮流变迁，提供可自查的实用方法与清单。',1,'','','');
/*!40000 ALTER TABLE `lecms_category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lecms_cms_article`
--

DROP TABLE IF EXISTS `lecms_cms_article`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lecms_cms_article` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '内容ID',
  `cid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '分类ID',
  `title` varchar(200) NOT NULL DEFAULT '' COMMENT '标题',
  `alias` varchar(80) NOT NULL DEFAULT '' COMMENT 'URL别名',
  `tags` varchar(500) NOT NULL DEFAULT '' COMMENT '标签 (json数组)',
  `intro` varchar(255) NOT NULL DEFAULT '' COMMENT '内容介绍',
  `pic` varchar(255) NOT NULL DEFAULT '' COMMENT '图片地址',
  `uid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '用户ID',
  `author` varchar(50) NOT NULL DEFAULT '' COMMENT '作者(昵称)',
  `source` varchar(100) NOT NULL DEFAULT '' COMMENT '来源',
  `dateline` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '发表时间',
  `lasttime` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '更新时间',
  `ip` int(11) unsigned NOT NULL DEFAULT 0 COMMENT 'IP',
  `imagenum` smallint(5) unsigned NOT NULL DEFAULT 0 COMMENT '图片附件数',
  `filenum` smallint(5) unsigned NOT NULL DEFAULT 0 COMMENT '文件附件数',
  `iscomment` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT '禁止评论',
  `comments` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '评论数',
  `flags` varchar(20) NOT NULL DEFAULT '' COMMENT '所有属性 ,分割',
  `seo_title` varchar(255) NOT NULL DEFAULT '' COMMENT 'SEO标题',
  `seo_keywords` varchar(255) NOT NULL DEFAULT '' COMMENT 'SEO关键词',
  `seo_description` varchar(255) NOT NULL DEFAULT '' COMMENT 'SEO描述',
  `jumpurl` varchar(255) NOT NULL DEFAULT '' COMMENT '跳转URL',
  `show_tpl` varchar(80) NOT NULL DEFAULT '' COMMENT '内容页模板',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `cid_id` (`cid`,`id`),
  KEY `cid_dateline` (`cid`,`dateline`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='文章主表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lecms_cms_article`
--

LOCK TABLES `lecms_cms_article` WRITE;
/*!40000 ALTER TABLE `lecms_cms_article` DISABLE KEYS */;
INSERT INTO `lecms_cms_article` VALUES
(1,1,'生辰八字与名字的关系，一篇讲清楚','','{\n    \"1\": \"起名\",\n    \"2\": \"bazi\"\n}','八字不是玄学玄谈，本质是用出生时间建立一组五行模型。名字如何参与这个模型？','/upload/article/art-bazi-1.jpg',1,'好名网编辑','好名网原创',1792547826,1792547826,2130706433,0,0,0,0,'','','','八字如何影响取名？本文通俗讲解日主旺衰、五行喜用神与名字用字的对应关系，说明名字在命理模型中承担的作用，并给出可直接操作的判断方法与常见误区。','',''),
(2,1,'免费根据生辰八字起名的完整思路','','{\n    \"1\": \"起名\",\n    \"2\": \"bazi\"\n}','从排四柱、定旺衰、选喜用神到挑字组合，拆解每个步骤。','/upload/article/art-naming-2.jpg',1,'好名网编辑','好名网原创',1792461426,1792547826,2130706433,0,0,0,0,'','','','从排四柱、定旺衰、选喜用神到挑字组合，一步步拆解八字起名的完整流程，附常见坑点与用字校验清单，照着做即可得到合格的候选名字。按本文流程逐步操作，即可得到兼顾命理与寓意的候选名字。','',''),
(3,1,'宝宝取名先看八字还是先看寓意？','','{\n    \"1\": \"起名\",\n    \"2\": \"bazi\"\n}','八字定方向，寓意定质感，两者不冲突。','/upload/article/art-parents-3.jpg',1,'好名网编辑','好名网原创',1792375026,1792547827,2130706433,0,0,0,0,'','','','家长最纠结的顺序问题。本文说明八字与寓意并不冲突：八字定方向与五行，寓意定质感与观感，两者按先后顺序推进，缺一不可。文中用真实案例对比两种路径的实际差异，新手也能一次看懂。','',''),
(4,1,'新生儿起名的十个常见误区','','{\n    \"1\": \"起名\",\n    \"2\": \"bazi\"\n}','生僻字、多音字、谐音梗……这些坑每年都有人踩。','/upload/article/art-mistakes-4.jpg',1,'好名网编辑','好名网原创',1792288626,1792547827,2130706433,0,0,0,0,'','','','生僻字、多音字、谐音梗、过度跟风……盘点新生儿起名最常踩的十个坑，逐条给出识别方法与替代方案，收藏这份清单，起名少走弯路。每条误区都配了替代方案，照着改即可避开大多数起名雷区。','',''),
(5,1,'五行缺什么就补什么？不一定','','{\n    \"1\": \"起名\",\n    \"2\": \"bazi\"\n}','缺未必是弱，旺未必是喜，要看整体结构。','/upload/article/art-wuxing-5.jpg',1,'好名网编辑','好名网原创',1792202226,1792547827,2130706433,0,0,0,0,'','','','很多人以为缺什么补什么，其实缺未必是弱、旺未必是喜。本文讲清喜用神的判断逻辑，告诉你为何补偏要以全局结构为准而非做加减法。读懂这一层，就能避免盲目补五行导致的用字失衡问题。','',''),
(6,1,'时辰不知道怎么办？八字起名的降级方案','','{\n    \"1\": \"起名\",\n    \"2\": \"bazi\"\n}','出生时辰缺失时的替代策略。','/upload/article/art-hour-6.jpg',1,'好名网编辑','好名网原创',1792115826,1792547827,2130706433,0,0,0,0,'','','','出生时辰缺失照样能起名。本文给出以日柱为主、参考月令与父母期望的替代策略，说明降级方案的可靠性边界，并列出可补救的查询途径。方法同样能得到可靠候选名，不必因缺少时辰而放弃八字起名。','',''),
(7,2,'女孩取名字寓意好的诗词名字精选','','{\n    \"1\": \"起名\",\n    \"3\": \"shici\"\n}','从诗经楚辞到唐诗宋词的精选名字。','/upload/article/art-girlpoem-7.jpg',1,'好名网编辑','好名网原创',1792029426,1792547827,2130706433,0,0,0,0,'','','','精选适合女孩的古诗词名字，涵盖诗经花草、宋词意象与唐诗清句，逐条标注出处与原句，附字义解析与音律点评，可直接取用或组合搭配。按寓意分类整理，可直接取用，也可与姓氏组合后再校验数理配置。','',''),
(8,2,'男孩取什么名字好？经典诗词中的男孩名','','{\n    \"1\": \"起名\",\n    \"3\": \"shici\"\n}','豪放派诗词里藏着大量大气的字眼。','/upload/article/art-boypoem-8.jpg',1,'好名网编辑','好名网原创',1791943026,1792547827,2130706433,0,0,0,0,'','','','从楚辞的厚重、唐诗的开阔到宋词的沉雄，精选大气且不失文气的男孩名字，附典籍出处、字义解析与数理参考，解决男孩取名难的问题。按意境分类整理，可直接取用，也可与姓氏组合后再核对笔画数理。','',''),
(9,2,'古诗词名句取名：如何避免烂大街','','{\n    \"1\": \"起名\",\n    \"3\": \"shici\"\n}','同样的句子，为什么有人取得惊艳有人取得俗套？','/upload/article/art-cliche-9.jpg',1,'好名网编辑','好名网原创',1791856626,1792547827,2130706433,0,0,0,0,'','','','热门诗句容易重名怎么办？本文给出四个降重技巧：避开高频名、取偏句不用名句、换同义意象、调整节奏，让名字有出处又不流俗。四个技巧配合康熙字典校验，能在有出处与不重名之间取得平衡。','',''),
(10,2,'出自《诗经》的女孩名字大全','','{\n    \"1\": \"起名\",\n    \"3\": \"shici\"\n}','诗经是取之不尽的名字宝库。','/upload/article/art-shijing-10.jpg',1,'好名网编辑','好名网原创',1791770226,1792547827,2130706433,0,0,0,0,'','','','汇总《诗经》中适合女孩取名的句子，按风、雅、颂分类，标注篇目与原句，附字义与寓意解析，是「女诗经」传统的实用速查表。按风雅颂分类，可直接取用，也可二次组合后再核对用字的数理。','',''),
(11,2,'楚辞取名为什么被称为「男楚辞」','','{\n    \"1\": \"起名\",\n    \"3\": \"shici\"\n}','香草美人与天地日月，楚辞的浪漫适合男孩。','/upload/article/art-chuci-11.jpg',1,'好名网编辑','好名网原创',1791683826,1792547827,2130706433,0,0,0,0,'','','','「男楚辞」的说法从何而来？本文从《离骚》《九歌》的语言气质切入，说明楚辞意象为何更适合男孩取名，并给出可直接使用的候选清单。按意象分类整理，配合音律与字形校验即可得到厚重又不拗口的名字。','',''),
(12,2,'唐诗宋词里适合双胞胎的名字','','{\n    \"1\": \"起名\",\n    \"3\": \"shici\"\n}','一联诗句拆出两个名字，对仗又呼应。','/upload/article/art-twins-12.jpg',1,'好名网编辑','好名网原创',1791597426,1792547827,2130706433,0,0,0,0,'','','','双胞胎取名讲究呼应不雷同。本文从唐诗宋词的对仗句、并列意象中提炼成套名字，兼顾音律对称与寓意关联，附多组完整搭配示例。每组都标注了共用的典籍出处，方便向家人解释名字的内在关联。','',''),
(13,3,'周易起名的底层逻辑是什么','','{\n    \"1\": \"起名\",\n    \"4\": \"zhouyi\"\n}','六十四卦是一套符号系统，取的是卦象气质而非算卦。','/upload/article/art-yijing-13.jpg',1,'好名网编辑','好名网原创',1791511026,1792547827,2130706433,0,0,0,0,'','','','周易起名不是简单查卦。本文说明从卦象到意象再到用字的三层转化逻辑，厘清八卦类象、卦辞取意与命理补益之间的关系。读懂这三层转化，就能判断一个「周易名字」是否真的有理据支撑。','',''),
(14,3,'周易免费起名的正确打开方式','','{\n    \"1\": \"起名\",\n    \"4\": \"zhouyi\"\n}','卦名入名、卦辞取字、爻位定数理，三种路径。','/upload/article/art-freeyi-14.jpg',1,'好名网编辑','好名网原创',1791424626,1792547827,2130706433,0,0,0,0,'','','','想把周易起好用，顺序很重要：先定意象、再择字、后核数理。本文给出完整流程与常见误用，避免只堆典故却忽略音形义与命理的错配。按此顺序推进，可避免只堆典故却忽略命理与音形义的常见错配。','',''),
(15,3,'乾坤两卦里藏着多少好名字','','{\n    \"1\": \"起名\",\n    \"4\": \"zhouyi\"\n}','「自强不息」与「厚德载物」是取名的富矿。','/upload/article/art-qiankun-15.jpg',1,'好名网编辑','好名网原创',1791338226,1792547827,2130706433,0,0,0,0,'','','','乾卦刚健、坤卦厚德，两卦的彖象辞句衍生出大量经典名字。本文逐句拆解可提炼的用字与组合，并给出数理与寓意的核对建议。逐句对照使用，可快速找到兼具气势与稳重的名字组合方案。','',''),
(16,3,'周易取名字大全：谦卦篇','','{\n    \"1\": \"起名\",\n    \"4\": \"zhouyi\"\n}','谦卦六爻皆吉，气质沉静。','/upload/article/art-qiangua-16.jpg',1,'好名网编辑','好名网原创',1791251826,1792547827,2130706433,0,0,0,0,'','','','谦卦是六十四卦中少见的六爻皆吉之卦，最适合取名。本篇汇总谦卦卦辞爻辞中可提炼的名字用字，附字义出处与搭配示例。谦卦取数兼顾数理与寓意，是周易起名中最稳妥的取法之一。','',''),
(17,3,'名字里的「元亨利贞」怎么用','','{\n    \"1\": \"起名\",\n    \"4\": \"zhouyi\"\n}','乾卦四德对应四种取名风格。','/upload/article/art-yuanheng-17.jpg',1,'好名网编辑','好名网原创',1791165426,1792547827,2130706433,0,0,0,0,'','','','「元亨利贞」四德常见于名字，但用法各有讲究。本文讲清四字的本义、适用性别与搭配宜忌，避免落入空洞拼字的误区。附常见搭配示例，帮你把四德用得自然、不落空洞拼字的俗套。','',''),
(18,3,'新生儿取名周易免费起名实操案例','','{\n    \"1\": \"起名\",\n    \"4\": \"zhouyi\"\n}','完整案例：从排卦到定名。','/upload/article/art-yicase-18.jpg',1,'好名网编辑','好名网原创',1791079026,1792547827,2130706433,0,0,0,0,'','','','完整演示一次周易起名：从出生信息排盘定喜用，到选定卦象意象，再到择字组合与数理核对，全程可复现，附结果点评与调整建议。按案例步骤操作，即可得到兼顾卦象意象与数理配置的完整方案。','',''),
(19,4,'名字的声调搭配入门指南','','{\n    \"1\": \"起名\",\n    \"5\": \"zhishi\"\n}','平仄相间的名字念起来朗朗上口。','/upload/article/art-tone-19.jpg',1,'好名网编辑','好名网原创',1790992626,1792547827,2130706433,0,0,0,0,'','','','好名字要读起来顺口。本文讲清平仄交替、避免连仄与拗口的实操规则，用常见姓氏举例说明声调如何搭配，附快速自查方法。按规则自查三分钟即可判断名字是否拗口，附常见姓氏搭配示例。','',''),
(20,4,'姓氏笔画会影响名字的整体美感吗','','{\n    \"1\": \"起名\",\n    \"5\": \"zhishi\"\n}','从书写角度聊聊搭配。','/upload/article/art-strokes-20.jpg',1,'好名网编辑','好名网原创',1790906226,1792547827,2130706433,0,0,0,0,'','','','姓氏笔画决定三才五格的起手配置，也影响书写疏密。本文说明不同笔画姓氏的搭配要点，给出字形均衡与数理吉利的兼顾方法。文中按常见姓氏笔画分组给出搭配建议，可直接对照自家姓氏参考。','',''),
(21,4,'这些字看起来美，写在名字里要慎重','','{\n    \"1\": \"起名\",\n    \"5\": \"zhishi\"\n}','容易踩雷的字与替代方案。','/upload/article/art-risky-21.jpg',1,'好名网编辑','好名网原创',1790819826,1792547827,2130706433,0,0,0,0,'','','','有些字义美却在姓名学中有隐患：多音、贬义引申、生僻难认、谐音尴尬等。本文逐字列出风险点与更稳妥的替代方案供参考。每个字都给出更稳妥的替代方案，避免等改完名才发现问题。','',''),
(22,4,'给二胎取名如何与大宝呼应','','{\n    \"1\": \"起名\",\n    \"5\": \"zhishi\"\n}','共用一字、同源一诗、平仄互补三种思路。','/upload/article/art-sibling-22.jpg',1,'好名网编辑','好名网原创',1790733426,1792547827,2130706433,0,0,0,0,'','','','二胎名字要和大宝呼应又不雷同。本文给出四种呼应方式：共用偏旁、对仗意象、同出一典、音律对称，并附多组兄妹搭配示例。每种方式都附真实兄妹名字示例，可直接套用到自家的情况。','',''),
(23,4,'名字评分 90 分就一定是好名字吗','','{\n    \"1\": \"起名\",\n    \"5\": \"zhishi\"\n}','数理评分只是参考维度之一。','/upload/article/art-score-23.jpg',1,'好名网编辑','好名网原创',1790647026,1792547827,2130706433,0,0,0,0,'','','','高分名字未必适合。本文分析姓名打分的算法边界：数理只占一维，还要看八字适配、生肖宜忌、谐音与观感，教你正确看待分数。看完就能正确解读评分结果，不再被单一分数牵着鼻子走。','',''),
(24,4,'改革开放以来取名潮流变迁史','','{\n    \"1\": \"起名\",\n    \"5\": \"zhishi\"\n}','四十多年的取名审美折射时代。','/upload/article/art-trends-24.jpg',1,'好名网编辑','好名网原创',1790560626,1792547827,2130706433,0,0,0,0,'','','','从建国初期的重字辈到如今的诗经楚辞风，名字折射时代审美。本文梳理四十余年取名潮流的演变轨迹，并给出避免年代感的建议。文末给出避免年代感的具体方法，让名字十年后依然不过时。','','');
/*!40000 ALTER TABLE `lecms_cms_article` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lecms_cms_article_attach`
--

DROP TABLE IF EXISTS `lecms_cms_article_attach`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lecms_cms_article_attach` (
  `aid` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '附件ID',
  `cid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '分类ID',
  `uid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '用户ID',
  `id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '内容ID',
  `filename` varchar(200) NOT NULL DEFAULT '' COMMENT '文件原名',
  `filetype` char(10) NOT NULL DEFAULT '' COMMENT '文件后缀',
  `filesize` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '文件大小',
  `filepath` varchar(200) NOT NULL DEFAULT '' COMMENT '文件路径',
  `dateline` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '上传时间',
  `downloads` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '下载次数',
  `credits` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '下载需要积分',
  `golds` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '下载需要金币',
  `isimage` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT '是否是图片',
  PRIMARY KEY (`aid`),
  KEY `id` (`id`,`aid`),
  KEY `uid` (`uid`,`aid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='文章附件表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lecms_cms_article_attach`
--

LOCK TABLES `lecms_cms_article_attach` WRITE;
/*!40000 ALTER TABLE `lecms_cms_article_attach` DISABLE KEYS */;
/*!40000 ALTER TABLE `lecms_cms_article_attach` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lecms_cms_article_data`
--

DROP TABLE IF EXISTS `lecms_cms_article_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lecms_cms_article_data` (
  `id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '内容ID',
  `content` mediumtext NOT NULL COMMENT '内容',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='文章数据表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lecms_cms_article_data`
--

LOCK TABLES `lecms_cms_article_data` WRITE;
/*!40000 ALTER TABLE `lecms_cms_article_data` DISABLE KEYS */;
INSERT INTO `lecms_cms_article_data` VALUES
(1,'<p>很多家长第一次接触八字起名时，都会被「四柱」「喜用神」这些词吓退。其实把概念拆开看，逻辑并不复杂：出生的年、月、日、时各对应一组干支，每组干支携带金木水火土中的若干属性，合计八个字，这就是「八字」。</p><p><img src=\"/upload/article/art-bazi-1.jpg\" alt=\"文章插图\" style=\"width:100%;border-radius:8px;\"></p><p>八个字放在一起，就构成了一张五行结构图。有的五行出现得多，有的少，甚至完全没有。传统命理认为，偏弱的五行可以通过名字用字来补益，这就是「八字起名」的基本原理。</p><p>需要强调的是，本站的起名引擎采用简化的演示模型：取五行中数量最少者为喜用神。真正的命理分析远比这复杂，结果仅供娱乐参考。</p><p>把八字起名落到操作层面，步骤可以归纳为四步：先排盘，再看日主强弱，然后定喜用神，最后选字。排盘环节要注意真太阳时的校正——出生地在东经 120 度以西的话，当地时间要比北京时间晚，真太阳时每差 1 度经线就相差 4 分钟，跨时辰的边界案例尤其需要留意。日主强弱看的是同类五行的多寡：生扶日主的多则身强，克泄耗的多则身弱。喜用神取「缺什么补什么、弱什么扶什么」的平衡原则，再据此筛选五行属相匹配的字。本站的八字起名工具把上述流程完整线上化，输入姓氏、出生日期与时辰即可获得候选方案，每个名字都附带五行与数理解读。</p><p>需要提醒家长的是，八字起名只是参考维度之一，不必过度焦虑。名字最终是伴随一生的符号，音、形、义的美感与书写便利同样重要。如果对命理模型半信半疑，可以把八字起名当作「排除法」——用五行喜忌过滤掉明显冲突的字，再在剩下的候选里凭语感挑选。想验证一个字到底适不适合，可以用康熙字典查字工具核对笔画与部首，再用姓名打分功能看整体三才五格的配合度，多维度交叉验证比单一指标更可靠。</p>'),
(2,'<p>第一步：排四柱。把出生时间换算成干支纪年，年柱以立春为界，日柱可以用儒略日公式推算，时柱按两小时一个时辰划分。</p><p><img src=\"/upload/article/art-naming-2.jpg\" alt=\"文章插图\" style=\"width:100%;border-radius:8px;\"></p><p>第二步：数五行。把四柱天干地支的五行属性逐个统计，得到金木水火土的分布。第三步：定喜用。简化的做法是取最弱的五行作为补益方向。</p><p>第四步：挑字组合。在对应五行的字库里筛选音、形、义俱佳的字，两两组合成候选名，再用五格数理筛选一轮，剩下的就是可交付的名单。</p><p>把八字起名落到操作层面，步骤可以归纳为四步：先排盘，再看日主强弱，然后定喜用神，最后选字。排盘环节要注意真太阳时的校正——出生地在东经 120 度以西的话，当地时间要比北京时间晚，真太阳时每差 1 度经线就相差 4 分钟，跨时辰的边界案例尤其需要留意。日主强弱看的是同类五行的多寡：生扶日主的多则身强，克泄耗的多则身弱。喜用神取「缺什么补什么、弱什么扶什么」的平衡原则，再据此筛选五行属相匹配的字。本站的八字起名工具把上述流程完整线上化，输入姓氏、出生日期与时辰即可获得候选方案，每个名字都附带五行与数理解读。</p><p>需要提醒家长的是，八字起名只是参考维度之一，不必过度焦虑。名字最终是伴随一生的符号，音、形、义的美感与书写便利同样重要。如果对命理模型半信半疑，可以把八字起名当作「排除法」——用五行喜忌过滤掉明显冲突的字，再在剩下的候选里凭语感挑选。想验证一个字到底适不适合，可以用康熙字典查字工具核对笔画与部首，再用姓名打分功能看整体三才五格的配合度，多维度交叉验证比单一指标更可靠。</p>'),
(3,'<p>这是后台被问得最多的问题之一。我们的建议是：先看八字确定五行方向，相当于划定一个范围；再在范围之内，用寓意、声调、字形来精选。</p><p><img src=\"/upload/article/art-parents-3.jpg\" alt=\"文章插图\" style=\"width:100%;border-radius:8px;\"></p><p>只看八字容易选出「凑五行」的怪名字，只看寓意又可能完全脱离出生信息。两条线索交叉验证，才是完整的流程。</p><p>把八字起名落到操作层面，步骤可以归纳为四步：先排盘，再看日主强弱，然后定喜用神，最后选字。排盘环节要注意真太阳时的校正——出生地在东经 120 度以西的话，当地时间要比北京时间晚，真太阳时每差 1 度经线就相差 4 分钟，跨时辰的边界案例尤其需要留意。日主强弱看的是同类五行的多寡：生扶日主的多则身强，克泄耗的多则身弱。喜用神取「缺什么补什么、弱什么扶什么」的平衡原则，再据此筛选五行属相匹配的字。本站的八字起名工具把上述流程完整线上化，输入姓氏、出生日期与时辰即可获得候选方案，每个名字都附带五行与数理解读。</p><p>需要提醒家长的是，八字起名只是参考维度之一，不必过度焦虑。名字最终是伴随一生的符号，音、形、义的美感与书写便利同样重要。如果对命理模型半信半疑，可以把八字起名当作「排除法」——用五行喜忌过滤掉明显冲突的字，再在剩下的候选里凭语感挑选。想验证一个字到底适不适合，可以用康熙字典查字工具核对笔画与部首，再用姓名打分功能看整体三才五格的配合度，多维度交叉验证比单一指标更可靠。</p>'),
(4,'<p>误区一：追求生僻字显得有文化，结果老师同学都不认识。误区二：用多音字，让名字在不同语境下被读出不同效果。</p><p><img src=\"/upload/article/art-mistakes-4.jpg\" alt=\"文章插图\" style=\"width:100%;border-radius:8px;\"></p><p>误区三：只看字典释义不看连读谐音。误区四：盲目跟风年度热字，导致重名率飙升。误区五：笔画数理满分但读音拗口。</p><p>误区六：用繁体字入名但户籍不支持。误区七：名字与姓氏声调全同。误区八：忽视辈分用字与家族习惯。误区九：迷信免费打分软件的单一维度。误区十：临产前才匆忙决定。</p><p>把八字起名落到操作层面，步骤可以归纳为四步：先排盘，再看日主强弱，然后定喜用神，最后选字。排盘环节要注意真太阳时的校正——出生地在东经 120 度以西的话，当地时间要比北京时间晚，真太阳时每差 1 度经线就相差 4 分钟，跨时辰的边界案例尤其需要留意。日主强弱看的是同类五行的多寡：生扶日主的多则身强，克泄耗的多则身弱。喜用神取「缺什么补什么、弱什么扶什么」的平衡原则，再据此筛选五行属相匹配的字。本站的八字起名工具把上述流程完整线上化，输入姓氏、出生日期与时辰即可获得候选方案，每个名字都附带五行与数理解读。</p><p>需要提醒家长的是，八字起名只是参考维度之一，不必过度焦虑。名字最终是伴随一生的符号，音、形、义的美感与书写便利同样重要。如果对命理模型半信半疑，可以把八字起名当作「排除法」——用五行喜忌过滤掉明显冲突的字，再在剩下的候选里凭语感挑选。想验证一个字到底适不适合，可以用康熙字典查字工具核对笔画与部首，再用姓名打分功能看整体三才五格的配合度，多维度交叉验证比单一指标更可靠。</p>'),
(5,'<p>网上流传最广的简化说法是「缺什么补什么」。但从命理角度看，缺的五行未必是需要补的，有时缺失的元素恰恰是命局不需要的。</p><p><img src=\"/upload/article/art-wuxing-5.jpg\" alt=\"文章插图\" style=\"width:100%;border-radius:8px;\"></p><p>更严谨的做法是分析日主强弱与整体格局，判断出真正的喜用神。本站演示引擎采用「最弱者补之」的近似规则，仅供参考。</p><p>对普通家长而言，与其纠结命理细节，不如把注意力放在名字的音、形、义上——这才是日常使用中真正重要的部分。</p><p>把八字起名落到操作层面，步骤可以归纳为四步：先排盘，再看日主强弱，然后定喜用神，最后选字。排盘环节要注意真太阳时的校正——出生地在东经 120 度以西的话，当地时间要比北京时间晚，真太阳时每差 1 度经线就相差 4 分钟，跨时辰的边界案例尤其需要留意。日主强弱看的是同类五行的多寡：生扶日主的多则身强，克泄耗的多则身弱。喜用神取「缺什么补什么、弱什么扶什么」的平衡原则，再据此筛选五行属相匹配的字。本站的八字起名工具把上述流程完整线上化，输入姓氏、出生日期与时辰即可获得候选方案，每个名字都附带五行与数理解读。</p><p>需要提醒家长的是，八字起名只是参考维度之一，不必过度焦虑。名字最终是伴随一生的符号，音、形、义的美感与书写便利同样重要。如果对命理模型半信半疑，可以把八字起名当作「排除法」——用五行喜忌过滤掉明显冲突的字，再在剩下的候选里凭语感挑选。想验证一个字到底适不适合，可以用康熙字典查字工具核对笔画与部首，再用姓名打分功能看整体三才五格的配合度，多维度交叉验证比单一指标更可靠。</p>'),
(6,'<p>不少早产或夜间出生的孩子，家长并不清楚准确时辰。没有时柱，八字只剩六个字，五行统计会失真。</p><p><img src=\"/upload/article/art-hour-6.jpg\" alt=\"文章插图\" style=\"width:100%;border-radius:8px;\"></p><p>替代方案一：以日柱为主。日柱的天干代表命主自身，参考性最强。方案二：用 plausible 的时辰区间分别计算，取各方案都指向的五行作为稳妥选项。</p><p>方案三：暂时不引入命理维度，以寓意、声调、家族传承为主导取名，等确认时辰后再微调。</p><p>把八字起名落到操作层面，步骤可以归纳为四步：先排盘，再看日主强弱，然后定喜用神，最后选字。排盘环节要注意真太阳时的校正——出生地在东经 120 度以西的话，当地时间要比北京时间晚，真太阳时每差 1 度经线就相差 4 分钟，跨时辰的边界案例尤其需要留意。日主强弱看的是同类五行的多寡：生扶日主的多则身强，克泄耗的多则身弱。喜用神取「缺什么补什么、弱什么扶什么」的平衡原则，再据此筛选五行属相匹配的字。本站的八字起名工具把上述流程完整线上化，输入姓氏、出生日期与时辰即可获得候选方案，每个名字都附带五行与数理解读。</p><p>需要提醒家长的是，八字起名只是参考维度之一，不必过度焦虑。名字最终是伴随一生的符号，音、形、义的美感与书写便利同样重要。如果对命理模型半信半疑，可以把八字起名当作「排除法」——用五行喜忌过滤掉明显冲突的字，再在剩下的候选里凭语感挑选。想验证一个字到底适不适合，可以用康熙字典查字工具核对笔画与部首，再用姓名打分功能看整体三才五格的配合度，多维度交叉验证比单一指标更可靠。</p>'),
(7,'<p>诗词取名的魅力在于「自带出处」：名字一报出来，懂的人立刻能会心一笑。</p><p><img src=\"/upload/article/art-girlpoem-7.jpg\" alt=\"文章插图\" style=\"width:100%;border-radius:8px;\"></p><p>诗经一脉适合女孩：「灼华」出自「桃之夭夭，灼灼其华」；「清扬」出自「有美一人，清扬婉兮」；「静姝」出自「静女其姝」。</p><p>唐诗宋词里也不乏适合女孩的名字：「婵娟」出自苏轼「千里共婵娟」，「锦书」出自李清照「云中谁寄锦书来」。</p><p>从典籍里摘名字，讲究「出处可考、意境相协」。诗经偏重赋比兴，字词温润，适合气质内敛的女孩；楚辞瑰丽磅礴，香草美人的意象大气，更适合男孩；唐诗气象万千，李杜王孟各有所长，摘字时注意避开过于直白的叙事句；宋词婉约与豪放并济，词牌本身就有韵律参考。选字时优先取意象完整的词，比如「清扬」「琼瑶」「浩然」这类双字词，比单字更不容易撞名，语感也更完整。本站诗经起名、楚辞起名、唐诗起名、宋词起名四个专题页收录了各典籍的经典名句，每个名字都标注了出处与原句，方便你逐句比对挑选。</p><p>诗词起名还有一个常见误区：只看字义好看，不顾声调搭配。好的名字读起来应该抑扬顿挫，避免三字同调。建议把候选名字用普通话和家乡话各读几遍，绕口的、谐音尴尬的要果断放弃。拿不准的时候，可以用姓名测试工具做一轮系统检查，它会从音律、字形、寓意、五行多个维度给出参考分。名字是送给孩子的第一份礼物，值得多花一周时间慢慢斟酌。</p>'),
(8,'<p>楚辞被称为「男楚辞」不是没有道理：「修远」出自「路漫漫其修远兮」，「齐光」出自「与日月兮齐光」。</p><p><img src=\"/upload/article/art-boypoem-8.jpg\" alt=\"文章插图\" style=\"width:100%;border-radius:8px;\"></p><p>唐诗方面：「云帆」出自李白「直挂云帆济沧海」，寓意乘风破浪；「凌绝」出自杜甫「会当凌绝顶」，志存高远。</p><p>给男孩选诗词名，优先看气象开阔的句子，再从中挑读音沉稳的字。</p><p>从典籍里摘名字，讲究「出处可考、意境相协」。诗经偏重赋比兴，字词温润，适合气质内敛的女孩；楚辞瑰丽磅礴，香草美人的意象大气，更适合男孩；唐诗气象万千，李杜王孟各有所长，摘字时注意避开过于直白的叙事句；宋词婉约与豪放并济，词牌本身就有韵律参考。选字时优先取意象完整的词，比如「清扬」「琼瑶」「浩然」这类双字词，比单字更不容易撞名，语感也更完整。本站诗经起名、楚辞起名、唐诗起名、宋词起名四个专题页收录了各典籍的经典名句，每个名字都标注了出处与原句，方便你逐句比对挑选。</p><p>诗词起名还有一个常见误区：只看字义好看，不顾声调搭配。好的名字读起来应该抑扬顿挫，避免三字同调。建议把候选名字用普通话和家乡话各读几遍，绕口的、谐音尴尬的要果断放弃。拿不准的时候，可以用姓名测试工具做一轮系统检查，它会从音律、字形、寓意、五行多个维度给出参考分。名字是送给孩子的第一份礼物，值得多花一周时间慢慢斟酌。</p>'),
(9,'<p>热门句子取热门字，重名率自然飙升。规避方法是换角度选字：别人取首尾字，你可以取中间的虚词实配。</p><p><img src=\"/upload/article/art-cliche-9.jpg\" alt=\"文章插图\" style=\"width:100%;border-radius:8px;\"></p><p>例如「呦呦鹿鸣，食野之苹」，取「鹿鸣」已经很常见，但取「野苹」就独特许多。再配合姓氏读几遍，检查谐音即可。</p><p>本站诗词库为每句诗标注了多个候选，冷热搭配，方便错峰选名。</p><p>从典籍里摘名字，讲究「出处可考、意境相协」。诗经偏重赋比兴，字词温润，适合气质内敛的女孩；楚辞瑰丽磅礴，香草美人的意象大气，更适合男孩；唐诗气象万千，李杜王孟各有所长，摘字时注意避开过于直白的叙事句；宋词婉约与豪放并济，词牌本身就有韵律参考。选字时优先取意象完整的词，比如「清扬」「琼瑶」「浩然」这类双字词，比单字更不容易撞名，语感也更完整。本站诗经起名、楚辞起名、唐诗起名、宋词起名四个专题页收录了各典籍的经典名句，每个名字都标注了出处与原句，方便你逐句比对挑选。</p><p>诗词起名还有一个常见误区：只看字义好看，不顾声调搭配。好的名字读起来应该抑扬顿挫，避免三字同调。建议把候选名字用普通话和家乡话各读几遍，绕口的、谐音尴尬的要果断放弃。拿不准的时候，可以用姓名测试工具做一轮系统检查，它会从音律、字形、寓意、五行多个维度给出参考分。名字是送给孩子的第一份礼物，值得多花一周时间慢慢斟酌。</p>'),
(10,'<p>《诗经》三百篇，几乎每一篇都能翻出一个好名字。关雎篇的「河洲」，桃夭篇的「灼华」，子衿篇的「子衿」本身就可入名。</p><p><img src=\"/upload/article/art-shijing-10.jpg\" alt=\"文章插图\" style=\"width:100%;border-radius:8px;\"></p><p>选诗经名有个技巧：先确定想要的气质——质朴、灵秀还是端庄，再去对应国风、小雅、大雅里找句子。</p><p>本站诗词起名库已按「诗经/楚辞/唐诗/宋词」分类，点击名字可直接查看数理评分。</p><p>从典籍里摘名字，讲究「出处可考、意境相协」。诗经偏重赋比兴，字词温润，适合气质内敛的女孩；楚辞瑰丽磅礴，香草美人的意象大气，更适合男孩；唐诗气象万千，李杜王孟各有所长，摘字时注意避开过于直白的叙事句；宋词婉约与豪放并济，词牌本身就有韵律参考。选字时优先取意象完整的词，比如「清扬」「琼瑶」「浩然」这类双字词，比单字更不容易撞名，语感也更完整。本站诗经起名、楚辞起名、唐诗起名、宋词起名四个专题页收录了各典籍的经典名句，每个名字都标注了出处与原句，方便你逐句比对挑选。</p><p>诗词起名还有一个常见误区：只看字义好看，不顾声调搭配。好的名字读起来应该抑扬顿挫，避免三字同调。建议把候选名字用普通话和家乡话各读几遍，绕口的、谐音尴尬的要果断放弃。拿不准的时候，可以用姓名测试工具做一轮系统检查，它会从音律、字形、寓意、五行多个维度给出参考分。名字是送给孩子的第一份礼物，值得多花一周时间慢慢斟酌。</p>'),
(11,'<p>文人之名多出自楚辞：李白的「云帆」气质、屈原的「修远」风骨，都在《离骚》《九歌》里。</p><p><img src=\"/upload/article/art-chuci-11.jpg\" alt=\"文章插图\" style=\"width:100%;border-radius:8px;\"></p><p>楚辞的特点是意象宏大——天地、日月、长风、青云，这类字眼放进男孩名，天然大气。</p><p>当然楚辞也适合女孩：「霓裳」出自《九歌·东君》，柔美飘逸。不必拘泥「男楚辞女诗经」的老话。</p><p>从典籍里摘名字，讲究「出处可考、意境相协」。诗经偏重赋比兴，字词温润，适合气质内敛的女孩；楚辞瑰丽磅礴，香草美人的意象大气，更适合男孩；唐诗气象万千，李杜王孟各有所长，摘字时注意避开过于直白的叙事句；宋词婉约与豪放并济，词牌本身就有韵律参考。选字时优先取意象完整的词，比如「清扬」「琼瑶」「浩然」这类双字词，比单字更不容易撞名，语感也更完整。本站诗经起名、楚辞起名、唐诗起名、宋词起名四个专题页收录了各典籍的经典名句，每个名字都标注了出处与原句，方便你逐句比对挑选。</p><p>诗词起名还有一个常见误区：只看字义好看，不顾声调搭配。好的名字读起来应该抑扬顿挫，避免三字同调。建议把候选名字用普通话和家乡话各读几遍，绕口的、谐音尴尬的要果断放弃。拿不准的时候，可以用姓名测试工具做一轮系统检查，它会从音律、字形、寓意、五行多个维度给出参考分。名字是送给孩子的第一份礼物，值得多花一周时间慢慢斟酌。</p>'),
(12,'<p>双胞胎取名的最高境界是「同源」。一句诗拆出两个名字，既呼应又各自成立。</p><p><img src=\"/upload/article/art-twins-12.jpg\" alt=\"文章插图\" style=\"width:100%;border-radius:8px;\"></p><p>例如「沉舟侧畔千帆过，病树前头万木春」可以拆出「千帆」「万春」；「云中谁寄锦书来，雁字回时，月满西楼」可以拆出「锦书」「月满」。</p><p>拆名时注意两个名字的声调错开，避免连读时混淆。</p><p>从典籍里摘名字，讲究「出处可考、意境相协」。诗经偏重赋比兴，字词温润，适合气质内敛的女孩；楚辞瑰丽磅礴，香草美人的意象大气，更适合男孩；唐诗气象万千，李杜王孟各有所长，摘字时注意避开过于直白的叙事句；宋词婉约与豪放并济，词牌本身就有韵律参考。选字时优先取意象完整的词，比如「清扬」「琼瑶」「浩然」这类双字词，比单字更不容易撞名，语感也更完整。本站诗经起名、楚辞起名、唐诗起名、宋词起名四个专题页收录了各典籍的经典名句，每个名字都标注了出处与原句，方便你逐句比对挑选。</p><p>诗词起名还有一个常见误区：只看字义好看，不顾声调搭配。好的名字读起来应该抑扬顿挫，避免三字同调。建议把候选名字用普通话和家乡话各读几遍，绕口的、谐音尴尬的要果断放弃。拿不准的时候，可以用姓名测试工具做一轮系统检查，它会从音律、字形、寓意、五行多个维度给出参考分。名字是送给孩子的第一份礼物，值得多花一周时间慢慢斟酌。</p>'),
(13,'<p>《周易》六十四卦，每卦由六爻构成，卦名、卦辞、爻辞层层展开。周易起名取的是这套符号系统承载的精神气质。</p><p><img src=\"/upload/article/art-yijing-13.jpg\" alt=\"文章插图\" style=\"width:100%;border-radius:8px;\"></p><p>比如谦卦，六爻皆吉，卦辞讲「卑以自牧」，用「谦」入名的孩子，名字里自带一份沉静。</p><p>这与「算卦」完全不同——我们借用的是哲学文本的美学资源，而不是预测吉凶。</p><p>周易起名的底层是「象」与「数」：卦有卦象，名有数理。实际操作上有两条路径——一是从卦象取意，比如谦卦六爻皆吉，「谦」字入名自带内敛自持的寓意；乾卦「元亨利贞」四德，摘「元」「亨」等字取开端通达之意。二是从数理配合，算出姓氏笔画后，挑选能使天格、人格、地格、外格、总格五行相生的名字。两条路径可以叠加：先定意象方向，再用数理校验。本站周易起名工具内置六十四卦卦辞库与笔画计算，输入姓氏即可看到卦象解释与候选字推荐。</p><p>初学者最容易犯的错是「见卦就摘」——既不看卦德与孩子性情的匹配，也不管字是否常用。建议把范围收窄到十来种意象明确的卦（谦、晋、恒、益、鼎等），每个卦选一两个字，再结合康熙字典核对笔画。卦象终究是文化符号的加持，名字好不好，最终还是要落到「好认、好读、好写、不尴尬」这四条硬标准上。拿不准寓意时，可以用汉字详情工具查每个字的古今义与使用建议。</p>'),
(14,'<p>路径一：卦名直接入名。六十四个卦名里，「谦」「晋」「恒」「益」「丰」「观」都是可直接使用的好字。</p><p><img src=\"/upload/article/art-freeyi-14.jpg\" alt=\"文章插图\" style=\"width:100%;border-radius:8px;\"></p><p>路径二：从卦辞取字。「天行健」取「健」，「厚德载物」取「厚」，「明出地上」取「晋」。</p><p>路径三：配合数理。把选定的字与姓氏组合后，用五格数理验证一遍，三才配置相生的优先。</p><p>周易起名的底层是「象」与「数」：卦有卦象，名有数理。实际操作上有两条路径——一是从卦象取意，比如谦卦六爻皆吉，「谦」字入名自带内敛自持的寓意；乾卦「元亨利贞」四德，摘「元」「亨」等字取开端通达之意。二是从数理配合，算出姓氏笔画后，挑选能使天格、人格、地格、外格、总格五行相生的名字。两条路径可以叠加：先定意象方向，再用数理校验。本站周易起名工具内置六十四卦卦辞库与笔画计算，输入姓氏即可看到卦象解释与候选字推荐。</p><p>初学者最容易犯的错是「见卦就摘」——既不看卦德与孩子性情的匹配，也不管字是否常用。建议把范围收窄到十来种意象明确的卦（谦、晋、恒、益、鼎等），每个卦选一两个字，再结合康熙字典核对笔画。卦象终究是文化符号的加持，名字好不好，最终还是要落到「好认、好读、好写、不尴尬」这四条硬标准上。拿不准寓意时，可以用汉字详情工具查每个字的古今义与使用建议。</p>'),
(15,'<p>乾卦讲刚健进取：健、强、行、天都是可用之字。坤卦讲厚德承载：厚、德、容、载气质沉稳。</p><p><img src=\"/upload/article/art-qiankun-15.jpg\" alt=\"文章插图\" style=\"width:100%;border-radius:8px;\"></p><p>乾坤两卦合起来，恰好是中国人理想人格的两面——既能开拓，也能守成。给男孩取乾卦意，给女孩取坤卦意，是经典思路。</p><p>本站周易起名引擎内置了十二个吉卦的取意字，一键即可生成组合。</p><p>周易起名的底层是「象」与「数」：卦有卦象，名有数理。实际操作上有两条路径——一是从卦象取意，比如谦卦六爻皆吉，「谦」字入名自带内敛自持的寓意；乾卦「元亨利贞」四德，摘「元」「亨」等字取开端通达之意。二是从数理配合，算出姓氏笔画后，挑选能使天格、人格、地格、外格、总格五行相生的名字。两条路径可以叠加：先定意象方向，再用数理校验。本站周易起名工具内置六十四卦卦辞库与笔画计算，输入姓氏即可看到卦象解释与候选字推荐。</p><p>初学者最容易犯的错是「见卦就摘」——既不看卦德与孩子性情的匹配，也不管字是否常用。建议把范围收窄到十来种意象明确的卦（谦、晋、恒、益、鼎等），每个卦选一两个字，再结合康熙字典核对笔画。卦象终究是文化符号的加持，名字好不好，最终还是要落到「好认、好读、好写、不尴尬」这四条硬标准上。拿不准寓意时，可以用汉字详情工具查每个字的古今义与使用建议。</p>'),
(16,'<p>在六十四卦中，谦卦是唯一六爻皆吉的卦。卦辞「亨，君子有终」，讲的是谦逊者必有好结果。</p><p><img src=\"/upload/article/art-qiangua-16.jpg\" alt=\"文章插图\" style=\"width:100%;border-radius:8px;\"></p><p>从谦卦取名的思路：直接用「谦」，或取「卑以自牧」的「牧」，或取「裒多益寡」的「益」。</p><p>「谦」字配姓氏时注意声调：阳平声的姓（如陈、林）配「谦」读起来尤其顺口。</p><p>周易起名的底层是「象」与「数」：卦有卦象，名有数理。实际操作上有两条路径——一是从卦象取意，比如谦卦六爻皆吉，「谦」字入名自带内敛自持的寓意；乾卦「元亨利贞」四德，摘「元」「亨」等字取开端通达之意。二是从数理配合，算出姓氏笔画后，挑选能使天格、人格、地格、外格、总格五行相生的名字。两条路径可以叠加：先定意象方向，再用数理校验。本站周易起名工具内置六十四卦卦辞库与笔画计算，输入姓氏即可看到卦象解释与候选字推荐。</p><p>初学者最容易犯的错是「见卦就摘」——既不看卦德与孩子性情的匹配，也不管字是否常用。建议把范围收窄到十来种意象明确的卦（谦、晋、恒、益、鼎等），每个卦选一两个字，再结合康熙字典核对笔画。卦象终究是文化符号的加持，名字好不好，最终还是要落到「好认、好读、好写、不尴尬」这四条硬标准上。拿不准寓意时，可以用汉字详情工具查每个字的古今义与使用建议。</p>'),
(17,'<p>乾卦卦辞「元亨利贞」四个字，古人分别解释为：元，始也；亨，通也；利，和也；贞，正也。</p><p><img src=\"/upload/article/art-yuanheng-17.jpg\" alt=\"文章插图\" style=\"width:100%;border-radius:8px;\"></p><p>对应到取名：取「元」寄托开端与创造力，取「亨」寄愿通达顺利，取「利」取和顺之意（今多避用），取「贞」取坚正之志。</p><p>四德里「元」「亨」最为常用，气质开朗；「贞」字古雅，适合搭配柔声调的姓。</p><p>周易起名的底层是「象」与「数」：卦有卦象，名有数理。实际操作上有两条路径——一是从卦象取意，比如谦卦六爻皆吉，「谦」字入名自带内敛自持的寓意；乾卦「元亨利贞」四德，摘「元」「亨」等字取开端通达之意。二是从数理配合，算出姓氏笔画后，挑选能使天格、人格、地格、外格、总格五行相生的名字。两条路径可以叠加：先定意象方向，再用数理校验。本站周易起名工具内置六十四卦卦辞库与笔画计算，输入姓氏即可看到卦象解释与候选字推荐。</p><p>初学者最容易犯的错是「见卦就摘」——既不看卦德与孩子性情的匹配，也不管字是否常用。建议把范围收窄到十来种意象明确的卦（谦、晋、恒、益、鼎等），每个卦选一两个字，再结合康熙字典核对笔画。卦象终究是文化符号的加持，名字好不好，最终还是要落到「好认、好读、好写、不尴尬」这四条硬标准上。拿不准寓意时，可以用汉字详情工具查每个字的古今义与使用建议。</p>'),
(18,'<p>以王姓男宝为例。父母期望孩子沉静有定力，我们选定谦卦作为气质来源。</p><p><img src=\"/upload/article/art-yicase-18.jpg\" alt=\"文章插图\" style=\"width:100%;border-radius:8px;\"></p><p>第一步取卦名「谦」，第二步配辅助字「牧」，组成「王谦牧」。检查读音：wáng qiān mù，声调为阳平-阴平-去声，错落有致。</p><p>第三步数理验证：按康熙笔画计算五格，三才配置相生，综合评分达标，定名。全程可以在本站工具中复现。</p><p>周易起名的底层是「象」与「数」：卦有卦象，名有数理。实际操作上有两条路径——一是从卦象取意，比如谦卦六爻皆吉，「谦」字入名自带内敛自持的寓意；乾卦「元亨利贞」四德，摘「元」「亨」等字取开端通达之意。二是从数理配合，算出姓氏笔画后，挑选能使天格、人格、地格、外格、总格五行相生的名字。两条路径可以叠加：先定意象方向，再用数理校验。本站周易起名工具内置六十四卦卦辞库与笔画计算，输入姓氏即可看到卦象解释与候选字推荐。</p><p>初学者最容易犯的错是「见卦就摘」——既不看卦德与孩子性情的匹配，也不管字是否常用。建议把范围收窄到十来种意象明确的卦（谦、晋、恒、益、鼎等），每个卦选一两个字，再结合康熙字典核对笔画。卦象终究是文化符号的加持，名字好不好，最终还是要落到「好认、好读、好写、不尴尬」这四条硬标准上。拿不准寓意时，可以用汉字详情工具查每个字的古今义与使用建议。</p>'),
(19,'<p>普通话四声里，一声二声为平，三声四声为仄。三字名的理想声调结构是平仄交错，避免三连平或三连仄。</p><p><img src=\"/upload/article/art-tone-19.jpg\" alt=\"文章插图\" style=\"width:100%;border-radius:8px;\"></p><p>检验方法很简单：把名字连读十遍，中途需要换气或者含混不清的，就是声调搭配有问题。</p><p>本站打分工具暂未引入声调维度，建议家长人工完成这一步——它恰恰是机器最难替代的环节。</p><p>评价一个名字，业内通常看四个维度：音、形、义、数。音律上，声母韵母的搭配要避免拗口，声调上平仄相间为佳；字形上，三字的繁简、结构最好错落有致，全是左右结构或全是上下结构都会显得单调；字义上，寓意要积极但不宜过满，「伟」「杰」「豪」用了几十年，换些清雅的字反而别致；数理上，三才五格的配合是传统姓名的参考体系。本站的姓名打分工具把这四个维度量化呈现，输入姓名就能看到分项得分与改进建议，适合作为初筛工具。</p><p>最后想说，名字没有标准答案，只有适不适合。别人的爆款名字未必适合你家孩子——声调、姓氏搭配、家族避讳、书写习惯都是个性化的。比较稳妥的做法是：先用宝宝起名工具按生辰与期望生成一轮候选，再用姓名测试逐个筛查音形义，最后让家人分别读几遍做感性筛选。技术工具负责扩大候选面、排除明显问题，最终拍板的应该是父母的判断。取名这件事，科学的方法加一点直觉，往往就是最好的组合。</p>'),
(20,'<p>签名是名字最常见的使用场景。姓氏笔画多的（如戴、魏），名字宜选笔画较少的字，整体疏密有致。</p><p><img src=\"/upload/article/art-strokes-20.jpg\" alt=\"文章插图\" style=\"width:100%;border-radius:8px;\"></p><p>反之，笔画少的姓（如丁、王），配一个笔画稍多的名字，视觉更平衡。</p><p>康熙笔画还决定五格数理，所以「好看」和「数理好」在多数情况下并不冲突。</p><p>评价一个名字，业内通常看四个维度：音、形、义、数。音律上，声母韵母的搭配要避免拗口，声调上平仄相间为佳；字形上，三字的繁简、结构最好错落有致，全是左右结构或全是上下结构都会显得单调；字义上，寓意要积极但不宜过满，「伟」「杰」「豪」用了几十年，换些清雅的字反而别致；数理上，三才五格的配合是传统姓名的参考体系。本站的姓名打分工具把这四个维度量化呈现，输入姓名就能看到分项得分与改进建议，适合作为初筛工具。</p><p>最后想说，名字没有标准答案，只有适不适合。别人的爆款名字未必适合你家孩子——声调、姓氏搭配、家族避讳、书写习惯都是个性化的。比较稳妥的做法是：先用宝宝起名工具按生辰与期望生成一轮候选，再用姓名测试逐个筛查音形义，最后让家人分别读几遍做感性筛选。技术工具负责扩大候选面、排除明显问题，最终拍板的应该是父母的判断。取名这件事，科学的方法加一点直觉，往往就是最好的组合。</p>'),
(21,'<p>第一类：多音字，如「乐」「重」「行」，不同人会读出不同音。第二类：形近字，如「己已巳」，证件录入易错。</p><p><img src=\"/upload/article/art-risky-21.jpg\" alt=\"文章插图\" style=\"width:100%;border-radius:8px;\"></p><p>第三类：含义随时代变化的字。第四类：生僻但在姓氏中常见的字，如「单」「解」作姓时读音特殊。</p><p>遇到心动的「危险字」，可以先在打分工具里看看与姓氏组合后的数理，再综合判断。</p><p>评价一个名字，业内通常看四个维度：音、形、义、数。音律上，声母韵母的搭配要避免拗口，声调上平仄相间为佳；字形上，三字的繁简、结构最好错落有致，全是左右结构或全是上下结构都会显得单调；字义上，寓意要积极但不宜过满，「伟」「杰」「豪」用了几十年，换些清雅的字反而别致；数理上，三才五格的配合是传统姓名的参考体系。本站的姓名打分工具把这四个维度量化呈现，输入姓名就能看到分项得分与改进建议，适合作为初筛工具。</p><p>最后想说，名字没有标准答案，只有适不适合。别人的爆款名字未必适合你家孩子——声调、姓氏搭配、家族避讳、书写习惯都是个性化的。比较稳妥的做法是：先用宝宝起名工具按生辰与期望生成一轮候选，再用姓名测试逐个筛查音形义，最后让家人分别读几遍做感性筛选。技术工具负责扩大候选面、排除明显问题，最终拍板的应该是父母的判断。取名这件事，科学的方法加一点直觉，往往就是最好的组合。</p>'),
(22,'<p>思路一：共用一个字，最直白的呼应，如「子涵」「子墨」。思路二：同出一首诗，如「灼华」「其华」。</p><p><img src=\"/upload/article/art-sibling-22.jpg\" alt=\"文章插图\" style=\"width:100%;border-radius:8px;\"></p><p>思路三：平仄互补，两个字音相对，念起来像一副对子。</p><p>本站诗词库的每条目都提供多个候选，双胞胎家庭可以在同一句诗下挑选两个名字。</p><p>评价一个名字，业内通常看四个维度：音、形、义、数。音律上，声母韵母的搭配要避免拗口，声调上平仄相间为佳；字形上，三字的繁简、结构最好错落有致，全是左右结构或全是上下结构都会显得单调；字义上，寓意要积极但不宜过满，「伟」「杰」「豪」用了几十年，换些清雅的字反而别致；数理上，三才五格的配合是传统姓名的参考体系。本站的姓名打分工具把这四个维度量化呈现，输入姓名就能看到分项得分与改进建议，适合作为初筛工具。</p><p>最后想说，名字没有标准答案，只有适不适合。别人的爆款名字未必适合你家孩子——声调、姓氏搭配、家族避讳、书写习惯都是个性化的。比较稳妥的做法是：先用宝宝起名工具按生辰与期望生成一轮候选，再用姓名测试逐个筛查音形义，最后让家人分别读几遍做感性筛选。技术工具负责扩大候选面、排除明显问题，最终拍板的应该是父母的判断。取名这件事，科学的方法加一点直觉，往往就是最好的组合。</p>'),
(23,'<p>五格数理评分衡量的只是笔画组合的吉凶，它是名字质量的一个切面，而不是全部。</p><p><img src=\"/upload/article/art-score-23.jpg\" alt=\"文章插图\" style=\"width:100%;border-radius:8px;\"></p><p>音——声调是否上口；形——书写是否平衡；义——寓意是否端正；理——数理是否吉祥。四者兼顾才是好名字。</p><p>所以看到 90 分的名字，先别急着定稿，把名字大声读一周，写在纸上看看，再做决定。</p><p>评价一个名字，业内通常看四个维度：音、形、义、数。音律上，声母韵母的搭配要避免拗口，声调上平仄相间为佳；字形上，三字的繁简、结构最好错落有致，全是左右结构或全是上下结构都会显得单调；字义上，寓意要积极但不宜过满，「伟」「杰」「豪」用了几十年，换些清雅的字反而别致；数理上，三才五格的配合是传统姓名的参考体系。本站的姓名打分工具把这四个维度量化呈现，输入姓名就能看到分项得分与改进建议，适合作为初筛工具。</p><p>最后想说，名字没有标准答案，只有适不适合。别人的爆款名字未必适合你家孩子——声调、姓氏搭配、家族避讳、书写习惯都是个性化的。比较稳妥的做法是：先用宝宝起名工具按生辰与期望生成一轮候选，再用姓名测试逐个筛查音形义，最后让家人分别读几遍做感性筛选。技术工具负责扩大候选面、排除明显问题，最终拍板的应该是父母的判断。取名这件事，科学的方法加一点直觉，往往就是最好的组合。</p>'),
(24,'<p>五十到七十年代，「建国」「建军」「卫东」承载集体记忆。八十年代，单名流行，「伟」「磊」「静」「丽」遍地开花。</p><p><img src=\"/upload/article/art-trends-24.jpg\" alt=\"文章插图\" style=\"width:100%;border-radius:8px;\"></p><p>九十年代开始追求寓意，「楠」「婷」「鑫」兴起。新世纪后，回归古典，「梓」「涵」「轩」「宸」成为新一代热字。</p><p>潮流循环往复，但「独特而不生僻、好听而有出处」始终是好名字的不变标准。</p><p>评价一个名字，业内通常看四个维度：音、形、义、数。音律上，声母韵母的搭配要避免拗口，声调上平仄相间为佳；字形上，三字的繁简、结构最好错落有致，全是左右结构或全是上下结构都会显得单调；字义上，寓意要积极但不宜过满，「伟」「杰」「豪」用了几十年，换些清雅的字反而别致；数理上，三才五格的配合是传统姓名的参考体系。本站的姓名打分工具把这四个维度量化呈现，输入姓名就能看到分项得分与改进建议，适合作为初筛工具。</p><p>最后想说，名字没有标准答案，只有适不适合。别人的爆款名字未必适合你家孩子——声调、姓氏搭配、家族避讳、书写习惯都是个性化的。比较稳妥的做法是：先用宝宝起名工具按生辰与期望生成一轮候选，再用姓名测试逐个筛查音形义，最后让家人分别读几遍做感性筛选。技术工具负责扩大候选面、排除明显问题，最终拍板的应该是父母的判断。取名这件事，科学的方法加一点直觉，往往就是最好的组合。</p>');
/*!40000 ALTER TABLE `lecms_cms_article_data` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lecms_cms_article_flag`
--

DROP TABLE IF EXISTS `lecms_cms_article_flag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lecms_cms_article_flag` (
  `flag` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT '属性标记',
  `cid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '分类ID',
  `id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '内容ID',
  PRIMARY KEY (`flag`,`id`),
  KEY `flag_cid` (`flag`,`cid`,`id`),
  KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='文章属性表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lecms_cms_article_flag`
--

LOCK TABLES `lecms_cms_article_flag` WRITE;
/*!40000 ALTER TABLE `lecms_cms_article_flag` DISABLE KEYS */;
/*!40000 ALTER TABLE `lecms_cms_article_flag` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lecms_cms_article_tag`
--

DROP TABLE IF EXISTS `lecms_cms_article_tag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lecms_cms_article_tag` (
  `tagid` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '标签ID',
  `name` varchar(80) NOT NULL DEFAULT '' COMMENT '标签名',
  `count` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '标签内容数量',
  `content` varchar(255) NOT NULL DEFAULT '' COMMENT '标签说明',
  `pic` varchar(255) NOT NULL DEFAULT '' COMMENT '标签缩略图',
  `seo_title` varchar(255) NOT NULL DEFAULT '' COMMENT 'SEO标题',
  `seo_keywords` varchar(255) NOT NULL DEFAULT '' COMMENT 'SEO关键词',
  `seo_description` varchar(255) NOT NULL DEFAULT '' COMMENT 'SEO描述',
  `orderby` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '排序值',
  PRIMARY KEY (`tagid`),
  UNIQUE KEY `tagname` (`name`),
  KEY `content_count` (`count`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='文章标签表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lecms_cms_article_tag`
--

LOCK TABLES `lecms_cms_article_tag` WRITE;
/*!40000 ALTER TABLE `lecms_cms_article_tag` DISABLE KEYS */;
INSERT INTO `lecms_cms_article_tag` VALUES
(1,'起名',24,'','','','','',0),
(2,'bazi',6,'','','','','',0),
(3,'shici',6,'','','','','',0),
(4,'zhouyi',6,'','','','','',0),
(5,'zhishi',6,'','','','','',0);
/*!40000 ALTER TABLE `lecms_cms_article_tag` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lecms_cms_article_tag_data`
--

DROP TABLE IF EXISTS `lecms_cms_article_tag_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lecms_cms_article_tag_data` (
  `tagid` int(10) unsigned NOT NULL COMMENT '标签ID',
  `id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '内容ID',
  PRIMARY KEY (`tagid`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='文章标签数据表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lecms_cms_article_tag_data`
--

LOCK TABLES `lecms_cms_article_tag_data` WRITE;
/*!40000 ALTER TABLE `lecms_cms_article_tag_data` DISABLE KEYS */;
INSERT INTO `lecms_cms_article_tag_data` VALUES
(1,1),
(1,2),
(1,3),
(1,4),
(1,5),
(1,6),
(1,7),
(1,8),
(1,9),
(1,10),
(1,11),
(1,12),
(1,13),
(1,14),
(1,15),
(1,16),
(1,17),
(1,18),
(1,19),
(1,20),
(1,21),
(1,22),
(1,23),
(1,24),
(2,1),
(2,2),
(2,3),
(2,4),
(2,5),
(2,6),
(3,7),
(3,8),
(3,9),
(3,10),
(3,11),
(3,12),
(4,13),
(4,14),
(4,15),
(4,16),
(4,17),
(4,18),
(5,19),
(5,20),
(5,21),
(5,22),
(5,23),
(5,24);
/*!40000 ALTER TABLE `lecms_cms_article_tag_data` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lecms_cms_article_views`
--

DROP TABLE IF EXISTS `lecms_cms_article_views`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lecms_cms_article_views` (
  `id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '内容ID',
  `cid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '分类ID',
  `views` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '查看次数',
  PRIMARY KEY (`id`),
  KEY `cid` (`cid`,`views`),
  KEY `views` (`views`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='文章浏览量表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lecms_cms_article_views`
--

LOCK TABLES `lecms_cms_article_views` WRITE;
/*!40000 ALTER TABLE `lecms_cms_article_views` DISABLE KEYS */;
INSERT INTO `lecms_cms_article_views` VALUES
(2,1,154),
(1,1,176),
(3,1,183),
(4,1,242),
(5,1,254),
(6,1,317),
(7,2,356),
(8,2,367),
(9,2,400),
(10,2,447),
(11,2,473),
(12,2,512),
(13,3,580),
(14,3,605),
(15,3,624),
(16,3,660),
(17,3,696),
(18,3,733),
(19,4,802),
(20,4,819),
(21,4,874),
(22,4,881),
(23,4,922),
(24,4,990);
/*!40000 ALTER TABLE `lecms_cms_article_views` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lecms_cms_comment`
--

DROP TABLE IF EXISTS `lecms_cms_comment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lecms_cms_comment` (
  `commentid` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '评论ID',
  `mid` tinyint(1) unsigned NOT NULL DEFAULT 2 COMMENT '模型ID',
  `id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '内容或分类ID',
  `uid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '用户ID',
  `author` varchar(50) NOT NULL DEFAULT '' COMMENT '作者',
  `content` varchar(255) NOT NULL DEFAULT '' COMMENT '评论内容',
  `ip` int(11) unsigned NOT NULL DEFAULT 0 COMMENT 'IP',
  `dateline` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '发表时间',
  `reply_commentid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '回复某评论ID',
  PRIMARY KEY (`commentid`),
  KEY `uid_id` (`uid`,`id`),
  KEY `mid_id` (`mid`,`id`),
  KEY `ip` (`ip`,`commentid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='评论表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lecms_cms_comment`
--

LOCK TABLES `lecms_cms_comment` WRITE;
/*!40000 ALTER TABLE `lecms_cms_comment` DISABLE KEYS */;
/*!40000 ALTER TABLE `lecms_cms_comment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lecms_cms_comment_sort`
--

DROP TABLE IF EXISTS `lecms_cms_comment_sort`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lecms_cms_comment_sort` (
  `mid` tinyint(1) unsigned NOT NULL DEFAULT 2 COMMENT '模型ID',
  `id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '内容ID',
  `cid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '分类ID',
  `comments` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '评论数',
  `lastdate` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '最后回复时间',
  UNIQUE KEY `mid_id` (`mid`,`id`),
  KEY `cid_comments` (`cid`,`comments`),
  KEY `comments` (`comments`),
  KEY `cid_lastdate` (`cid`,`lastdate`),
  KEY `lastdate` (`lastdate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='评论排序表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lecms_cms_comment_sort`
--

LOCK TABLES `lecms_cms_comment_sort` WRITE;
/*!40000 ALTER TABLE `lecms_cms_comment_sort` DISABLE KEYS */;
/*!40000 ALTER TABLE `lecms_cms_comment_sort` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lecms_cms_page`
--

DROP TABLE IF EXISTS `lecms_cms_page`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lecms_cms_page` (
  `cid` int(10) unsigned NOT NULL COMMENT '分类ID',
  `content` mediumtext NOT NULL COMMENT '单页内容',
  PRIMARY KEY (`cid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='单页分类表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lecms_cms_page`
--

LOCK TABLES `lecms_cms_page` WRITE;
/*!40000 ALTER TABLE `lecms_cms_page` DISABLE KEYS */;
/*!40000 ALTER TABLE `lecms_cms_page` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lecms_framework_count`
--

DROP TABLE IF EXISTS `lecms_framework_count`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lecms_framework_count` (
  `name` char(32) NOT NULL DEFAULT '' COMMENT '表名',
  `count` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '总行数',
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='表的总数记录表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lecms_framework_count`
--

LOCK TABLES `lecms_framework_count` WRITE;
/*!40000 ALTER TABLE `lecms_framework_count` DISABLE KEYS */;
INSERT INTO `lecms_framework_count` VALUES
('category',4),
('cms_article',24),
('cms_article_tag',5),
('runtime',0);
/*!40000 ALTER TABLE `lecms_framework_count` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lecms_framework_maxid`
--

DROP TABLE IF EXISTS `lecms_framework_maxid`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lecms_framework_maxid` (
  `name` char(32) NOT NULL DEFAULT '' COMMENT '表名',
  `maxid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '最大ID',
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='表的最大ID记录表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lecms_framework_maxid`
--

LOCK TABLES `lecms_framework_maxid` WRITE;
/*!40000 ALTER TABLE `lecms_framework_maxid` DISABLE KEYS */;
INSERT INTO `lecms_framework_maxid` VALUES
('category',4),
('cms_article',24),
('cms_article_tag',5);
/*!40000 ALTER TABLE `lecms_framework_maxid` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lecms_kv`
--

DROP TABLE IF EXISTS `lecms_kv`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lecms_kv` (
  `k` char(32) NOT NULL DEFAULT '' COMMENT '键名',
  `v` mediumtext NOT NULL DEFAULT '' COMMENT '数据',
  `expiry` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '过期时间',
  PRIMARY KEY (`k`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='配置数据表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lecms_kv`
--

LOCK TABLES `lecms_kv` WRITE;
/*!40000 ALTER TABLE `lecms_kv` DISABLE KEYS */;
INSERT INTO `lecms_kv` VALUES
('article_user_habits_uid_1','{\n    \"last_add_cid\": 4\n}',0),
('cfg','{\"webname\": \"好名网\", \"webdomain\": \"127.0.0.1:8090\", \"webdir\": \"\\/\", \"webmail\": \"admin@qq.com\", \"webqq\": \"\", \"webweixin\": \"\", \"webtel\": \"\", \"tongji\": \"\", \"beian\": \"\", \"copyright\": \"© 2026 好名网 · 原创功能演示系统（LECMS 整合版）\", \"seo_title\": \"好名网 - 专注宝宝起名、取名、测名打分平台\", \"seo_keywords\": \"宝宝起名,八字起名,诗词起名,姓名测试,公司起名\", \"seo_description\": \"好名网：原创起名功能演示系统，提供宝宝起名、八字起名、诗词起名、公司起名与姓名测试打分工具，基于 LECMS 构建。\", \"show_seo_title_rule\": \"{title}-{webname}\", \"show_seo_keywords_rule\": \"{title},{webname}\", \"show_seo_description_rule\": \"{webname}\\uff1a{intro}\", \"admin_layout\": 1, \"auto_pic\": 1, \"close_website\": 0, \"close_search\": 0, \"close_views\": 0, \"open_attach\": 0, \"open_comment\": 0, \"open_comment_vcode\": 0, \"comment_default_author\": \"\\u6e38\\u5ba2\", \"open_user\": 0, \"open_user_register\": 0, \"open_user_register_vcode\": 0, \"open_user_login\": 0, \"open_user_login_vcode\": 0, \"open_user_reset_password\": 0, \"user_avatar_width\": 200, \"user_avatar_height\": 200, \"open_no_login_comment\": 0, \"open_mobile_view\": 0, \"open_title_check\": 0, \"mobile_view\": \"mobile\", \"content_min_len\": 5, \"open_email\": 0, \"email_smtp\": \"smtp.qq.com\", \"email_port\": 465, \"email_account\": \"\", \"email_account_name\": \"\", \"email_password\": \"\", \"link_show\": \"{cate_alias}\\/{id}.html\", \"link_show_type\": 2, \"link_show_end\": \".html\", \"link_cate_page_pre\": \"\\/page_\", \"link_cate_page_end\": \"\\/\", \"link_cate_end\": \"\\/\", \"link_tag_type\": 0, \"link_tag_pre\": \"tag\\/\", \"link_tag_end\": \"\\/\", \"link_tag_top\": \"tag_top\", \"link_comment_pre\": \"comment\\/\", \"link_space_pre\": \"space\\/\", \"link_space_end\": \"\\/\", \"up_img_ext\": \"jpg,jpeg,gif,png,webp\", \"up_img_max_size\": \"3074\", \"up_file_ext\": \"zip,gz,rar,iso,xls,xlsx,csv,doc,docx,ppt,wps,txt,pdf\", \"up_file_max_size\": \"20480\", \"thumb_type\": 2, \"thumb_quality\": 90, \"watermark_pos\": 0, \"watermark_pct\": 90, \"admin_vcode\": 0, \"admin_safe_entrance\": 0, \"admin_safe_auth\": \"g6x9wy\", \"url_path\": 0, \"theme\": \"haoiming\"}',0),
('link_keywords','[\"tag\",\"tag_top\",\"tag_all\",\"comment\",\"index\",\"sitemap\",\"admin\",\"adminpanel\",\"user\",\"space\",\"login\",\"logout\",\"register\",\"static\",\"upload\",\"log\",\"runtime\",\"install\",\"attach\",\"special\",\"search\",\"so\"]',0);
/*!40000 ALTER TABLE `lecms_kv` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lecms_models`
--

DROP TABLE IF EXISTS `lecms_models`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lecms_models` (
  `mid` tinyint(1) unsigned NOT NULL AUTO_INCREMENT COMMENT '模型ID',
  `name` char(30) NOT NULL DEFAULT '' COMMENT '模型名称',
  `tablename` char(20) NOT NULL DEFAULT '' COMMENT '模型表名',
  `index_tpl` varchar(80) NOT NULL DEFAULT '' COMMENT '默认频道页模板',
  `cate_tpl` varchar(80) NOT NULL DEFAULT '' COMMENT '默认列表页模板',
  `show_tpl` varchar(80) NOT NULL DEFAULT '' COMMENT '默认内容页模板',
  `system` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT '是否系统内置',
  `width` smallint(5) unsigned NOT NULL DEFAULT 300 COMMENT '缩略图宽度',
  `height` smallint(5) unsigned NOT NULL DEFAULT 300 COMMENT '缩略图高度',
  `icon` varchar(30) NOT NULL DEFAULT 'fa fa-bars' COMMENT '后台菜单图标',
  PRIMARY KEY (`mid`),
  UNIQUE KEY `tablename` (`tablename`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='模型表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lecms_models`
--

LOCK TABLES `lecms_models` WRITE;
/*!40000 ALTER TABLE `lecms_models` DISABLE KEYS */;
INSERT INTO `lecms_models` VALUES
(1,'单页','page','','page_show.htm','',1,0,0,''),
(2,'文章','article','article_index.htm','article_list.htm','article_show.htm',1,160,120,'fa fa-book');
/*!40000 ALTER TABLE `lecms_models` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lecms_models_field`
--

DROP TABLE IF EXISTS `lecms_models_field`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lecms_models_field` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `mid` tinyint(1) unsigned NOT NULL DEFAULT 2 COMMENT '模型ID',
  `field` char(20) NOT NULL DEFAULT '' COMMENT '字段名',
  `name` varchar(30) NOT NULL DEFAULT '' COMMENT '显示名',
  `inputtype` varchar(20) NOT NULL DEFAULT '' COMMENT '类型',
  `tips` varchar(255) NOT NULL DEFAULT '' COMMENT '提示',
  `setting` mediumtext NOT NULL COMMENT '设置',
  `isbase` tinyint(1) unsigned NOT NULL DEFAULT 1 COMMENT '是否主表(0附表，1主表)',
  `required` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT '是否必填',
  `orderby` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '排序',
  PRIMARY KEY (`id`),
  UNIQUE KEY `mid_field` (`mid`,`field`),
  KEY `mid` (`mid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='模型字段表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lecms_models_field`
--

LOCK TABLES `lecms_models_field` WRITE;
/*!40000 ALTER TABLE `lecms_models_field` DISABLE KEYS */;
/*!40000 ALTER TABLE `lecms_models_field` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lecms_only_alias`
--

DROP TABLE IF EXISTS `lecms_only_alias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lecms_only_alias` (
  `alias` char(80) NOT NULL COMMENT 'URL唯一别名',
  `mid` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT '模型ID',
  `cid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '分类ID',
  `id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '内容ID',
  PRIMARY KEY (`alias`),
  KEY `mid_id` (`mid`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='URL别名表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lecms_only_alias`
--

LOCK TABLES `lecms_only_alias` WRITE;
/*!40000 ALTER TABLE `lecms_only_alias` DISABLE KEYS */;
/*!40000 ALTER TABLE `lecms_only_alias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lecms_runtime`
--

DROP TABLE IF EXISTS `lecms_runtime`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lecms_runtime` (
  `k` char(32) NOT NULL DEFAULT '' COMMENT '键名',
  `v` mediumtext NOT NULL DEFAULT '' COMMENT '数据',
  `expiry` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '过期时间',
  PRIMARY KEY (`k`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='数据缓存表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lecms_runtime`
--

LOCK TABLES `lecms_runtime` WRITE;
/*!40000 ALTER TABLE `lecms_runtime` DISABLE KEYS */;
INSERT INTO `lecms_runtime` VALUES
('cate_1','{\n    \"cid\": \"1\",\n    \"mid\": \"2\",\n    \"type\": \"1\",\n    \"upid\": \"0\",\n    \"name\": \"八字起名\",\n    \"alias\": \"bazi\",\n    \"pic\": \"\",\n    \"intro\": \"八字起名相关原创资讯与知识\",\n    \"cate_tpl\": \"article_list.htm\",\n    \"show_tpl\": \"article_show.htm\",\n    \"count\": \"6\",\n    \"orderby\": \"0\",\n    \"seo_title\": \"\",\n    \"seo_keywords\": \"\",\n    \"seo_description\": \"八字起名栏目：讲清生辰八字与取名用字的五行关系，包含排盘、喜用神判断、起名流程与常见误区，提供免费起名的完整思路。\",\n    \"contribute\": \"1\",\n    \"seo_title_rule\": \"\",\n    \"seo_keywords_rule\": \"\",\n    \"seo_description_rule\": \"\",\n    \"place\": [\n        {\n            \"cid\": \"1\",\n            \"name\": \"八字起名\",\n            \"url\": \"/index.php?cate--cid-1.html\"\n        }\n    ],\n    \"topcid\": \"1\",\n    \"table\": \"article\",\n    \"son_list\": [],\n    \"son_cids\": []\n}',0),
('cate_2','{\n    \"cid\": \"2\",\n    \"mid\": \"2\",\n    \"type\": \"1\",\n    \"upid\": \"0\",\n    \"name\": \"诗词起名\",\n    \"alias\": \"shici\",\n    \"pic\": \"\",\n    \"intro\": \"诗词起名相关原创资讯与知识\",\n    \"cate_tpl\": \"article_list.htm\",\n    \"show_tpl\": \"article_show.htm\",\n    \"count\": \"6\",\n    \"orderby\": \"0\",\n    \"seo_title\": \"\",\n    \"seo_keywords\": \"\",\n    \"seo_description\": \"诗词起名栏目：从诗经、楚辞、唐诗、宋词中提炼名字，逐条标注出处与原句，附字义解析与音律点评，兼顾文化底蕴与去重技巧。\",\n    \"contribute\": \"1\",\n    \"seo_title_rule\": \"\",\n    \"seo_keywords_rule\": \"\",\n    \"seo_description_rule\": \"\",\n    \"place\": [\n        {\n            \"cid\": \"2\",\n            \"name\": \"诗词起名\",\n            \"url\": \"/index.php?cate--cid-2.html\"\n        }\n    ],\n    \"topcid\": \"2\",\n    \"table\": \"article\",\n    \"son_list\": [],\n    \"son_cids\": []\n}',0),
('cate_3','{\n    \"cid\": \"3\",\n    \"mid\": \"2\",\n    \"type\": \"1\",\n    \"upid\": \"0\",\n    \"name\": \"周易起名\",\n    \"alias\": \"zhouyi\",\n    \"pic\": \"\",\n    \"intro\": \"周易起名相关原创资讯与知识\",\n    \"cate_tpl\": \"article_list.htm\",\n    \"show_tpl\": \"article_show.htm\",\n    \"count\": \"6\",\n    \"orderby\": \"0\",\n    \"seo_title\": \"\",\n    \"seo_keywords\": \"\",\n    \"seo_description\": \"周易起名栏目：依据六十四卦卦辞爻辞取意命名，详解乾坤谦晋等卦象的取名方法，附实操案例与数理核对要点。\",\n    \"contribute\": \"1\",\n    \"seo_title_rule\": \"\",\n    \"seo_keywords_rule\": \"\",\n    \"seo_description_rule\": \"\",\n    \"place\": [\n        {\n            \"cid\": \"3\",\n            \"name\": \"周易起名\",\n            \"url\": \"/index.php?cate--cid-3.html\"\n        }\n    ],\n    \"topcid\": \"3\",\n    \"table\": \"article\",\n    \"son_list\": [],\n    \"son_cids\": []\n}',0),
('cate_4','{\n    \"cid\": \"4\",\n    \"mid\": \"2\",\n    \"type\": \"1\",\n    \"upid\": \"0\",\n    \"name\": \"起名知识\",\n    \"alias\": \"zhishi\",\n    \"pic\": \"\",\n    \"intro\": \"起名知识相关原创资讯与知识\",\n    \"cate_tpl\": \"article_list.htm\",\n    \"show_tpl\": \"article_show.htm\",\n    \"count\": \"6\",\n    \"orderby\": \"0\",\n    \"seo_title\": \"\",\n    \"seo_keywords\": \"\",\n    \"seo_description\": \"起名知识栏目：涵盖声调搭配、姓氏笔画、用字宜忌、二胎呼应、评分解读与取名潮流变迁，提供可自查的实用方法与清单。\",\n    \"contribute\": \"1\",\n    \"seo_title_rule\": \"\",\n    \"seo_keywords_rule\": \"\",\n    \"seo_description_rule\": \"\",\n    \"place\": [\n        {\n            \"cid\": \"4\",\n            \"name\": \"起名知识\",\n            \"url\": \"/index.php?cate--cid-4.html\"\n        }\n    ],\n    \"topcid\": \"4\",\n    \"table\": \"article\",\n    \"son_list\": [],\n    \"son_cids\": []\n}',0),
('cfg','{\n    \"webname\": \"好名网\",\n    \"webdomain\": \"127.0.0.1:8090\",\n    \"webdir\": \"/\",\n    \"webmail\": \"admin@qq.com\",\n    \"webqq\": \"\",\n    \"webweixin\": \"\",\n    \"webtel\": \"\",\n    \"tongji\": \"\",\n    \"beian\": \"\",\n    \"copyright\": \"© 2026 好名网 · 原创功能演示系统（LECMS 整合版）\",\n    \"seo_title\": \"好名网 - 专注宝宝起名、取名、测名打分平台\",\n    \"seo_keywords\": \"宝宝起名,八字起名,诗词起名,姓名测试,公司起名\",\n    \"seo_description\": \"好名网：原创起名功能演示系统，提供宝宝起名、八字起名、诗词起名、公司起名与姓名测试打分工具，基于 LECMS 构建。\",\n    \"show_seo_title_rule\": \"{title}-{webname}\",\n    \"show_seo_keywords_rule\": \"{title},{webname}\",\n    \"show_seo_description_rule\": \"{webname}：{intro}\",\n    \"admin_layout\": 1,\n    \"auto_pic\": 1,\n    \"close_website\": 0,\n    \"close_search\": 0,\n    \"close_views\": 0,\n    \"open_attach\": 0,\n    \"open_comment\": 0,\n    \"open_comment_vcode\": 0,\n    \"comment_default_author\": \"游客\",\n    \"open_user\": 0,\n    \"open_user_register\": 0,\n    \"open_user_register_vcode\": 0,\n    \"open_user_login\": 0,\n    \"open_user_login_vcode\": 0,\n    \"open_user_reset_password\": 0,\n    \"user_avatar_width\": 200,\n    \"user_avatar_height\": 200,\n    \"open_no_login_comment\": 0,\n    \"open_mobile_view\": 0,\n    \"open_title_check\": 0,\n    \"mobile_view\": \"mobile\",\n    \"content_min_len\": 5,\n    \"open_email\": 0,\n    \"email_smtp\": \"smtp.qq.com\",\n    \"email_port\": 465,\n    \"email_account\": \"\",\n    \"email_account_name\": \"\",\n    \"email_password\": \"\",\n    \"link_show\": \"{cate_alias}/{id}.html\",\n    \"link_show_type\": 2,\n    \"link_show_end\": \".html\",\n    \"link_cate_page_pre\": \"/page_\",\n    \"link_cate_page_end\": \"/\",\n    \"link_cate_end\": \"/\",\n    \"link_tag_type\": 0,\n    \"link_tag_pre\": \"tag/\",\n    \"link_tag_end\": \"/\",\n    \"link_tag_top\": \"tag_top\",\n    \"link_comment_pre\": \"comment/\",\n    \"link_space_pre\": \"space/\",\n    \"link_space_end\": \"/\",\n    \"up_img_ext\": \"jpg,jpeg,gif,png,webp\",\n    \"up_img_max_size\": \"3074\",\n    \"up_file_ext\": \"zip,gz,rar,iso,xls,xlsx,csv,doc,docx,ppt,wps,txt,pdf\",\n    \"up_file_max_size\": \"20480\",\n    \"thumb_type\": 2,\n    \"thumb_quality\": 90,\n    \"watermark_pos\": 0,\n    \"watermark_pct\": 90,\n    \"admin_vcode\": 0,\n    \"admin_safe_entrance\": 0,\n    \"admin_safe_auth\": \"g6x9wy\",\n    \"url_path\": 0,\n    \"theme\": \"haoiming\",\n    \"tpl\": \"/view/haoiming/\",\n    \"webroot\": \"http://127.0.0.1:8090\",\n    \"weburl\": \"http://127.0.0.1:8090/\",\n    \"table_arr\": {\n        \"2\": \"article\",\n        \"1\": \"page\"\n    },\n    \"mod_name\": {\n        \"2\": \"文章\"\n    },\n    \"mod_url\": {\n        \"article\": \"http://127.0.0.1:8090/index.php?model--mid-2.html\"\n    },\n    \"cate_arr\": {\n        \"1\": \"bazi\",\n        \"2\": \"shici\",\n        \"3\": \"zhouyi\",\n        \"4\": \"zhishi\"\n    }\n}',0);
/*!40000 ALTER TABLE `lecms_runtime` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lecms_user`
--

DROP TABLE IF EXISTS `lecms_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lecms_user` (
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '用户ID',
  `username` char(16) NOT NULL DEFAULT '' COMMENT '用户名',
  `password` char(32) NOT NULL DEFAULT '' COMMENT '密码',
  `salt` char(16) NOT NULL DEFAULT '' COMMENT '密码干扰字符',
  `author` varchar(50) NOT NULL DEFAULT '' COMMENT '昵称',
  `groupid` smallint(5) unsigned NOT NULL DEFAULT 0 COMMENT '用户组ID',
  `email` char(50) NOT NULL DEFAULT '' COMMENT 'EMAIL',
  `homepage` varchar(255) NOT NULL DEFAULT '' COMMENT '个人主页',
  `intro` varchar(255) NOT NULL DEFAULT '' COMMENT '个人介绍',
  `regip` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '注册IP',
  `regdate` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '注册日期',
  `loginip` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '登录IP',
  `logindate` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '登录日期',
  `lastip` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '上次登录IP',
  `lastdate` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '上次登录日期',
  `contents` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '内容数',
  `logins` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '登录次数',
  `credits` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '积分',
  `golds` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '金币',
  `mobile` varchar(20) NOT NULL DEFAULT '' COMMENT '手机号',
  `avatar` varchar(255) NOT NULL DEFAULT '' COMMENT '头像',
  `vip_times` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'VIP到期时间',
  PRIMARY KEY (`uid`),
  UNIQUE KEY `username` (`username`),
  KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='用户表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lecms_user`
--

LOCK TABLES `lecms_user` WRITE;
/*!40000 ALTER TABLE `lecms_user` DISABLE KEYS */;
INSERT INTO `lecms_user` VALUES
(1,'admin','d2caf3d41db5f641501f494aea3a0c0e','tZ6eShsOd2MhYU1d','admin',1,'','','',2130706433,1792547150,2130706433,1792547826,2130706433,1792547819,24,4,0,0,'','',0);
/*!40000 ALTER TABLE `lecms_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lecms_user_group`
--

DROP TABLE IF EXISTS `lecms_user_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lecms_user_group` (
  `groupid` smallint(5) unsigned NOT NULL AUTO_INCREMENT COMMENT '用户组ID',
  `groupname` char(20) NOT NULL DEFAULT '' COMMENT '用户组名',
  `system` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT '是否系统内置',
  `purviews` mediumtext NOT NULL COMMENT '后台权限',
  PRIMARY KEY (`groupid`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='用户组表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lecms_user_group`
--

LOCK TABLES `lecms_user_group` WRITE;
/*!40000 ALTER TABLE `lecms_user_group` DISABLE KEYS */;
INSERT INTO `lecms_user_group` VALUES
(1,'管理员组',1,''),
(2,'主编组',1,''),
(3,'编辑组',1,''),
(6,'待验证用户组',1,''),
(7,'禁止用户组',1,''),
(10,'永久VIP',1,''),
(11,'注册用户',1,''),
(12,'VIP用户',1,'');
/*!40000 ALTER TABLE `lecms_user_group` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-25 15:52:43
