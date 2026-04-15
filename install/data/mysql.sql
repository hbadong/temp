# 用户表
DROP TABLE IF EXISTS pre_user;
CREATE TABLE pre_user (
  uid int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '用户ID',
  username char(16) NOT NULL DEFAULT '' COMMENT '用户名',
  password char(32) NOT NULL DEFAULT '' COMMENT '密码',
  salt char(16) NOT NULL DEFAULT '' COMMENT '密码干扰字符',
  author varchar(50) NOT NULL DEFAULT '' COMMENT '昵称',
  groupid smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '用户组ID',
  email char(50) NOT NULL DEFAULT '' COMMENT 'EMAIL',
  homepage varchar(255) NOT NULL DEFAULT '' COMMENT '个人主页',
  intro varchar(255) NOT NULL DEFAULT '' COMMENT '个人介绍',
  regip int(11) unsigned NOT NULL DEFAULT '0' COMMENT '注册IP',
  regdate int(10) unsigned NOT NULL DEFAULT '0' COMMENT '注册日期',
  loginip int(11) unsigned NOT NULL DEFAULT '0' COMMENT '登录IP',
  logindate int(10) unsigned NOT NULL DEFAULT '0' COMMENT '登录日期',
  lastip int(11) unsigned NOT NULL DEFAULT '0' COMMENT '上次登录IP',
  lastdate int(10) unsigned NOT NULL DEFAULT '0' COMMENT '上次登录日期',
  contents int(10) unsigned NOT NULL DEFAULT '0' COMMENT '内容数',
  logins int(10) unsigned NOT NULL DEFAULT '0' COMMENT '登录次数',
  credits int(10) unsigned NOT NULL DEFAULT '0' COMMENT '积分',
  golds int(10) unsigned NOT NULL DEFAULT '0' COMMENT '金币',
  mobile varchar(20) NOT NULL DEFAULT '' COMMENT '手机号',
  avatar varchar(255) NOT NULL DEFAULT '' COMMENT '头像',
  vip_times int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'VIP到期时间',
  PRIMARY KEY (uid),
  UNIQUE KEY username(username),
  KEY email(email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='用户表';

# 用户组表
DROP TABLE IF EXISTS pre_user_group;
CREATE TABLE pre_user_group (
  groupid smallint(5) unsigned NOT NULL AUTO_INCREMENT COMMENT '用户组ID',
  groupname char(20) NOT NULL DEFAULT '' COMMENT '用户组名',
  `system` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否系统内置',
  purviews mediumtext NOT NULL COMMENT '后台权限',
  PRIMARY KEY (groupid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='用户组表';

# 分类栏目表
DROP TABLE IF EXISTS pre_category;
CREATE TABLE pre_category (
  cid int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '分类ID',
  mid tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '内容模型ID',
  type tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '分类类型 (0为列表，1为频道)',
  upid int(10) unsigned NOT NULL DEFAULT '0' COMMENT '上级CID',
  `name` char(50) NOT NULL DEFAULT '' COMMENT '分类名称',
  alias char(50) NOT NULL DEFAULT '' COMMENT 'URL别名',
  pic varchar(255) NOT NULL DEFAULT '' COMMENT '图片地址',
  intro varchar(255) NOT NULL DEFAULT '' COMMENT '分类介绍',
  cate_tpl varchar(80) NOT NULL DEFAULT '' COMMENT '分类页模板',
  show_tpl varchar(80) NOT NULL DEFAULT '' COMMENT '内容页模板',
  `count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '内容数',
  orderby int(10) unsigned NOT NULL DEFAULT '0' COMMENT '排序值',
  seo_title varchar(255) NOT NULL DEFAULT '' COMMENT 'SEO标题',
  seo_keywords varchar(255) NOT NULL DEFAULT '' COMMENT 'SEO关键词',
  seo_description varchar(255) NOT NULL DEFAULT '' COMMENT 'SEO描述',
  contribute tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '允许投稿',
  seo_title_rule varchar(255) NOT NULL DEFAULT '' COMMENT '内容SEO标题规则',
  seo_keywords_rule varchar(255) NOT NULL DEFAULT '' COMMENT '内容SEO关键词规则',
  seo_description_rule varchar(255) NOT NULL DEFAULT '' COMMENT '内容SEO描述规则',
  PRIMARY KEY (cid),
  KEY mid (mid),
  UNIQUE KEY alias (alias)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='分类表';

# 内容模型表
DROP TABLE IF EXISTS pre_models;
CREATE TABLE pre_models (
  mid tinyint(1) unsigned NOT NULL AUTO_INCREMENT COMMENT '模型ID',
  name char(30) NOT NULL DEFAULT '' COMMENT '模型名称',
  tablename char(20) NOT NULL DEFAULT '' COMMENT '模型表名',
  index_tpl varchar(80) NOT NULL DEFAULT '' COMMENT '默认频道页模板',
  cate_tpl varchar(80) NOT NULL DEFAULT '' COMMENT '默认列表页模板',
  show_tpl varchar(80) NOT NULL DEFAULT '' COMMENT '默认内容页模板',
  `system` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否系统内置',
  width smallint(5) unsigned NOT NULL DEFAULT '300' COMMENT '缩略图宽度',
  height smallint(5) unsigned NOT NULL DEFAULT '300' COMMENT '缩略图高度',
  icon varchar(30) NOT NULL DEFAULT 'fa fa-bars' COMMENT '后台菜单图标',
  PRIMARY KEY (mid),
  UNIQUE KEY tablename (tablename)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='模型表';

# 内容模型字段表
DROP TABLE IF EXISTS pre_models_field;
CREATE TABLE pre_models_field (
  id int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  mid tinyint(1) unsigned NOT NULL DEFAULT '2' COMMENT '模型ID',
  field char(20) NOT NULL DEFAULT '' COMMENT '字段名',
  `name` varchar(30) NOT NULL DEFAULT '' COMMENT '显示名',
  inputtype varchar(20) NOT NULL DEFAULT '' COMMENT '类型',
  tips varchar(255) NOT NULL DEFAULT '' COMMENT '提示',
  setting mediumtext NOT NULL COMMENT '设置',
  isbase tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否主表(0附表，1主表)',
  required tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否必填',
  orderby int(10) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  PRIMARY KEY (id),
  KEY (mid),
  UNIQUE KEY mid_field (mid,field)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='模型字段表';

# 唯一别名表 用于伪静态 (只储存内容的别名 分类和其他别名放 kv 表)
DROP TABLE IF EXISTS pre_only_alias;
CREATE TABLE pre_only_alias (
  alias char(80) NOT NULL COMMENT 'URL唯一别名',
  mid tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '模型ID',
  cid int(10) unsigned NOT NULL DEFAULT '0' COMMENT '分类ID',
  id int(10) unsigned NOT NULL DEFAULT '0' COMMENT '内容ID',
  PRIMARY KEY (alias),
  KEY mid_id (mid,id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='URL别名表';

# 单页表
DROP TABLE IF EXISTS pre_cms_page;
CREATE TABLE pre_cms_page (
  cid int(10) unsigned NOT NULL COMMENT '分类ID',
  content mediumtext NOT NULL COMMENT '单页内容',
  PRIMARY KEY (cid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='单页分类表';

# 文章表 (可根据 id 范围分区 审核/定时发布等考虑单独设计一张表)
DROP TABLE IF EXISTS pre_cms_article;
CREATE TABLE pre_cms_article (
  id int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '内容ID',
  cid int(10) unsigned NOT NULL DEFAULT '0' COMMENT '分类ID',
  title varchar(200) NOT NULL DEFAULT '' COMMENT '标题',
  alias varchar(80) NOT NULL DEFAULT '' COMMENT 'URL别名',
  tags varchar(500) NOT NULL DEFAULT '' COMMENT '标签 (json数组)',
  intro varchar(255) NOT NULL DEFAULT '' COMMENT '内容介绍',
  pic varchar(255) NOT NULL DEFAULT '' COMMENT '图片地址',
  uid int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  author varchar(50) NOT NULL DEFAULT '' COMMENT '作者(昵称)',
  source varchar(100) NOT NULL DEFAULT '' COMMENT '来源',
  dateline int(10) unsigned NOT NULL DEFAULT '0' COMMENT '发表时间',
  lasttime int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  ip int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'IP',
  imagenum smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '图片附件数',
  filenum smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '文件附件数',
  iscomment tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '禁止评论',
  comments int(10) unsigned NOT NULL DEFAULT '0' COMMENT '评论数',
  flags varchar(20) NOT NULL DEFAULT '' COMMENT '所有属性 ,分割',
  seo_title varchar(255) NOT NULL DEFAULT '' COMMENT 'SEO标题',
  seo_keywords varchar(255) NOT NULL DEFAULT '' COMMENT 'SEO关键词',
  seo_description varchar(255) NOT NULL DEFAULT '' COMMENT 'SEO描述',
  jumpurl varchar(255) NOT NULL DEFAULT '' COMMENT '跳转URL',
  show_tpl varchar(80) NOT NULL DEFAULT '' COMMENT '内容页模板',
  PRIMARY KEY (id),
  KEY uid (uid),
  KEY cid_id (cid,id),
  KEY cid_dateline (cid,dateline)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='文章主表';

# 文章数据表 (大内容字段表 可根据 id 范围分区)
DROP TABLE IF EXISTS pre_cms_article_data;
CREATE TABLE pre_cms_article_data (
  id int(10) unsigned NOT NULL DEFAULT '0' COMMENT '内容ID',
  content mediumtext NOT NULL COMMENT '内容',
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='文章数据表';

# 文章属性标记表
DROP TABLE IF EXISTS pre_cms_article_flag;
CREATE TABLE pre_cms_article_flag (
  flag tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '属性标记',
  cid int(10) unsigned NOT NULL DEFAULT '0' COMMENT '分类ID',
  id int(10) unsigned NOT NULL DEFAULT '0' COMMENT '内容ID',
  PRIMARY KEY (flag,id),
  KEY flag_cid (flag,cid,id),
  KEY id (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='文章属性表';

# 文章附件表
DROP TABLE IF EXISTS pre_cms_article_attach;
CREATE TABLE pre_cms_article_attach (
  aid int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '附件ID',
  cid int(10) unsigned NOT NULL DEFAULT '0' COMMENT '分类ID',
  uid int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  id int(10) unsigned NOT NULL DEFAULT '0' COMMENT '内容ID',
  filename varchar(200) NOT NULL DEFAULT '' COMMENT '文件原名',
  filetype char(10) NOT NULL DEFAULT '' COMMENT '文件后缀',
  filesize int(10) unsigned NOT NULL DEFAULT '0' COMMENT '文件大小',
  filepath varchar(200) NOT NULL DEFAULT '' COMMENT '文件路径',
  dateline int(10) unsigned NOT NULL DEFAULT '0' COMMENT '上传时间',
  downloads int(10) unsigned NOT NULL DEFAULT '0' COMMENT '下载次数',
  credits int(10) unsigned NOT NULL DEFAULT '0' COMMENT '下载需要积分',
  golds int(10) unsigned NOT NULL DEFAULT '0' COMMENT '下载需要金币',
  isimage tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否是图片',
  PRIMARY KEY (aid),
  KEY id (id, aid),
  KEY uid (uid, aid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='文章附件表';

# 文章查看数表 用来分离主表的写压力
DROP TABLE IF EXISTS pre_cms_article_views;
CREATE TABLE pre_cms_article_views (
  id int(10) unsigned NOT NULL DEFAULT '0' COMMENT '内容ID',
  cid int(10) unsigned NOT NULL DEFAULT '0' COMMENT '分类ID',
  views int(10) unsigned NOT NULL DEFAULT '0' COMMENT '查看次数',
  PRIMARY KEY (id),
  KEY cid (cid,views),
  KEY views (views)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='文章浏览量表';

# 文章标签表
DROP TABLE IF EXISTS pre_cms_article_tag;
CREATE TABLE pre_cms_article_tag (
  tagid int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '标签ID',
  `name` varchar(80) NOT NULL DEFAULT '' COMMENT '标签名',
  `count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '标签内容数量',
  content varchar(255) NOT NULL DEFAULT '' COMMENT '标签说明',
  pic varchar(255) NOT NULL DEFAULT '' COMMENT '标签缩略图',
  seo_title varchar(255) NOT NULL DEFAULT '' COMMENT 'SEO标题',
  seo_keywords varchar(255) NOT NULL DEFAULT '' COMMENT 'SEO关键词',
  seo_description varchar(255) NOT NULL DEFAULT '' COMMENT 'SEO描述',
  orderby int(10) unsigned NOT NULL DEFAULT '0' COMMENT '排序值',
  PRIMARY KEY (tagid),
  UNIQUE KEY tagname (`name`),
  KEY content_count (`count`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='文章标签表';

# 文章标签数据表
DROP TABLE IF EXISTS pre_cms_article_tag_data;
CREATE TABLE pre_cms_article_tag_data (
  tagid int(10) unsigned NOT NULL COMMENT '标签ID',
  id int(10) unsigned NOT NULL DEFAULT '0' COMMENT '内容ID',
  PRIMARY KEY (tagid,id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='文章标签数据表';

# 内容评论排序表 用来减小主表索引 (有评论时才写入 不含单页模型)
DROP TABLE IF EXISTS pre_cms_comment_sort;
CREATE TABLE pre_cms_comment_sort (
  mid tinyint(1) unsigned NOT NULL DEFAULT '2' COMMENT '模型ID',
  id int(10) unsigned NOT NULL DEFAULT '0' COMMENT '内容ID',
  cid int(10) unsigned NOT NULL DEFAULT '0' COMMENT '分类ID',
  comments int(10) unsigned NOT NULL DEFAULT '0' COMMENT '评论数',
  lastdate int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后回复时间',
  UNIQUE KEY mid_id (mid,id),
  KEY cid_comments (cid,comments),
  KEY comments (comments),
  KEY cid_lastdate (cid,lastdate),
  KEY lastdate (lastdate)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='评论排序表';

# 评论表 该功能用的不多 所有的模型内容评论都写入此表(含单页)
DROP TABLE IF EXISTS pre_cms_comment;
CREATE TABLE pre_cms_comment (
  commentid int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '评论ID',
  mid tinyint(1) unsigned NOT NULL DEFAULT '2' COMMENT '模型ID',
  id int(10) unsigned NOT NULL DEFAULT '0' COMMENT '内容或分类ID',
  uid int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  author varchar(50) NOT NULL DEFAULT '' COMMENT '作者',
  content varchar(255) NOT NULL DEFAULT '' COMMENT '评论内容',
  ip int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'IP',
  dateline int(10) unsigned NOT NULL DEFAULT '0' COMMENT '发表时间',
  reply_commentid int(10) unsigned NOT NULL DEFAULT '0' COMMENT '回复某评论ID',
  PRIMARY KEY (commentid),
  KEY uid_id (uid,id),
  KEY mid_id (mid,id),
  KEY ip (ip,commentid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='评论表';

# 持久保存的 key value 数据 (包括设置信息)
DROP TABLE IF EXISTS pre_kv;
CREATE TABLE pre_kv (
  `k` char(32) NOT NULL DEFAULT '' COMMENT '键名',
  `v` mediumtext NOT NULL DEFAULT '' COMMENT '数据',
  `expiry` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '过期时间',
  PRIMARY KEY (`k`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='配置数据表';

# 缓存表
DROP TABLE IF EXISTS pre_runtime;
CREATE TABLE pre_runtime (
  `k` char(32) NOT NULL DEFAULT '' COMMENT '键名',
  `v` mediumtext NOT NULL DEFAULT '' COMMENT '数据',
  `expiry` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '过期时间',
  PRIMARY KEY (`k`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='数据缓存表';

# 记录其它表的总行数
DROP TABLE IF EXISTS pre_framework_count;
CREATE TABLE pre_framework_count (
  `name` char(32) NOT NULL DEFAULT '' COMMENT '表名',
  `count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '总行数',
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='表的总数记录表';

# 记录其它表的最大ID
DROP TABLE IF EXISTS pre_framework_maxid;
CREATE TABLE pre_framework_maxid (
  `name` char(32) NOT NULL DEFAULT '' COMMENT '表名',
  maxid int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最大ID',
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='表的最大ID记录表';
