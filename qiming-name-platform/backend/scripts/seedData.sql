-- 起名平台系统种子数据

USE qiming_db;

-- 插入热门名字
INSERT INTO `names` (`surname`, `given_name`, `full_name`, `gender`, `pinyin`, `pinyin_initial`, `stroke_count`, `five_element`, `wu_ge_tian`, `wu_ge_di`, `wu_ge_ren`, `wu_ge_wai`, `wu_ge_zong`, `wu_ge_lucky`, `shape_score`, `sound_score`, `meaning_score`, `wu_xing_score`, `total_score`, `meaning`, `source_type`, `is_popular`, `status`) VALUES
('李', '俊豪', '李俊豪', 1, 'li jun hao', 'LJH', 14, '火', 8, 12, 14, 3, 20, '吉', 95, 92, 96, 88, 98, '才智超群、豪迈大气', 'bazi', 1, 1),
('李', '欣怡', '李欣怡', 2, 'li xin yi', 'LXY', 13, '金', 8, 11, 13, 3, 18, '吉', 92, 95, 94, 90, 96, '快乐喜悦、怡然自得', 'poetry', 1, 1),
('李', '煜晨', '李煜晨', 1, 'li yu chen', 'LYC', 15, '火', 8, 13, 15, 3, 21, '吉', 94, 90, 92, 85, 95, '光明照耀、晨曦微露', 'zhouyi', 1, 1),
('李', '梓涵', '李梓涵', 2, 'li zi han', 'LZH', 14, '木', 8, 12, 14, 3, 20, '吉', 90, 88, 94, 92, 94, '生机勃勃、涵养深厚', 'poetry', 1, 1),
('李', '铭轩', '李铭轩', 1, 'li ming xuan', 'LMX', 16, '金', 8, 14, 16, 3, 22, '吉', 88, 86, 90, 92, 92, '铭记于心、气宇轩昂', 'custom', 1, 1),
('王', '思琪', '王思琪', 2, 'wang si qi', 'WSQ', 15, '金', 9, 12, 15, 3, 20, '吉', 92, 88, 90, 86, 91, '才思敏捷、琪花瑶草', 'poetry', 1, 1),
('王', '浩然', '王浩然', 1, 'wang hao ran', 'WHR', 13, '水', 9, 11, 13, 3, 18, '吉', 94, 92, 88, 90, 93, '正气浩然、光明磊落', 'poetry', 1, 1),
('张', '雨桐', '张雨桐', 2, 'zhang yu tong', 'ZYT', 13, '木', 12, 11, 15, 3, 20, '吉', 90, 88, 92, 86, 91, '雨露滋润、梧桐高洁', 'poetry', 1, 1),
('刘', '子轩', '刘子轩', 1, 'liu zi xuan', 'LZX', 11, '金', 12, 9, 11, 3, 16, '吉', 88, 90, 86, 92, 90, '人中龙凤、气宇轩昂', 'custom', 1, 1),
('陈', '雅婷', '陈雅婷', 2, 'chen ya ting', 'CYT', 16, '火', 17, 12, 18, 3, 24, '吉', 86, 88, 90, 84, 89, '雅致端庄、亭亭玉立', 'custom', 1, 1);

-- 插入姓氏数据
INSERT INTO `surnames` (`surname`, `pinyin`, `origin`, `population_rank`, `population_count`) VALUES
('王', 'wang', '出自姬姓，为王子比干之后', 1, '1.02亿'),
('李', 'li', '出自颛顼帝孙理利贞之后', 2, '1.01亿'),
('张', 'zhang', '出自黄帝孙张挥之后', 3, '0.95亿'),
('刘', 'liu', '出自黄帝孙刘累之后', 4, '0.72亿'),
('陈', 'chen', '出自妫满陈国之后', 5, '0.63亿'),
('杨', 'yang', '出自姬姓，晋国羊舌氏之后', 6, '0.47亿'),
('黄', 'huang', '出自赢姓，陆终之后', 7, '0.32亿'),
('赵', 'zhao', '出自赢姓，造父之后', 8, '0.29亿'),
('吴', 'wu', '出自姬姓，周文王之后', 9, '0.27亿'),
('周', 'zhou', '出自姬姓，周文王之后', 10, '0.26亿');

-- 插入文章分类
INSERT INTO `article_categories` (`name`, `slug`, `sort_order`) VALUES
('起名常识', 'qiming-changshi', 1),
('八字知识', 'bazi-zhishi', 2),
('诗词起名', 'shici-qiming', 3),
('周易起名', 'zhouyi-qiming', 4),
('姓名测试', 'xingming-ceshi', 5);

-- 插入文章
INSERT INTO `articles` (`title`, `slug`, `category_id`, `author`, `summary`, `content`, `status`, `is_top`, `view_count`, `published_at`) VALUES
('宝宝起名别跟风！8个独特技巧', 'baobao-qiming-bie-genfeng', 1, '清飞扬', '大家在给宝宝起名时，往往会陷入重名误区。本文分享8个独特技巧，帮助家长给宝宝起一个独特好听的名字。', '<p>给宝宝起名是每个家庭迎接新生命的重要环节...</p>', 1, 1, 256, NOW()),
('八字五行缺火的人应该怎么起名', 'bazi-wuxing-que-huo-ruhe-qiming', 2, '清飞扬', '理论上八字缺啥五行就补什么五行是错误的，但八字缺少某个五行，相对来说这个五行在整个命盘上面的力量会非常的弱，需要适当补救。', '<p>五行学说认为...</p>', 1, 0, 189, NOW()),
('如何起一个富含诗意的好名字', 'ruhe-qiyige-haofufengshi-de-haomingzi', 3, '清飞扬', '名字是父母送给孩子的第一份礼物，一个有诗意的名字能让孩子在人群中脱颖而出。', '<p>诗意的名字...</p>', 1, 0, 143, NOW());

-- 插入行业数据
INSERT INTO `industries` (`name`, `keywords`, `suitable_elements`) VALUES
('科技', '科技,互联网,软件,IT,技术', '金,水'),
('金融', '金融,银行,投资,证券,保险', '金,水'),
('教育', '教育,培训,学校,咨询', '木,火'),
('医疗', '医疗,健康,医药,医院', '木,火'),
('贸易', '贸易,商业,零售,批发', '土,金'),
('制造', '制造,工业,生产,工厂', '土,金'),
('餐饮', '餐饮,美食,酒店,旅游', '火,土'),
('传媒', '传媒,广告,文化,娱乐', '木,火');

SELECT 'Seed data inserted successfully!' as message;
