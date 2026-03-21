-- 起名平台系统数据库初始化脚本

CREATE DATABASE IF NOT EXISTS qiming_db DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE qiming_db;

-- 用户表
CREATE TABLE IF NOT EXISTS `users` (
  `id` BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT COMMENT '用户ID',
  `username` VARCHAR(50) NOT NULL UNIQUE COMMENT '用户名',
  `password_hash` VARCHAR(255) NOT NULL COMMENT '密码哈希',
  `phone` VARCHAR(20) UNIQUE COMMENT '手机号',
  `email` VARCHAR(100) UNIQUE COMMENT '邮箱',
  `nickname` VARCHAR(50) COMMENT '昵称',
  `avatar` VARCHAR(255) COMMENT '头像URL',
  `gender` TINYINT DEFAULT 0 COMMENT '性别 0未知 1男 2女',
  `birth_date` DATE COMMENT '出生日期',
  `birth_time` VARCHAR(10) COMMENT '出生时辰',
  `status` TINYINT DEFAULT 1 COMMENT '状态 0禁用 1正常 2待验证',
  `last_login_time` DATETIME COMMENT '最后登录时间',
  `last_login_ip` VARCHAR(45) COMMENT '最后登录IP',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_phone` (`phone`),
  INDEX `idx_email` (`email`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户表';

-- 登录日志表
CREATE TABLE IF NOT EXISTS `login_logs` (
  `id` BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `ip` VARCHAR(45) NOT NULL,
  `user_agent` VARCHAR(500),
  `login_status` TINYINT NOT NULL COMMENT '0失败 1成功',
  `fail_reason` VARCHAR(100),
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='登录日志表';

-- 名字库表
CREATE TABLE IF NOT EXISTS `names` (
  `id` BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  `surname` VARCHAR(10) NOT NULL COMMENT '姓',
  `given_name` VARCHAR(10) NOT NULL COMMENT '名',
  `full_name` VARCHAR(20) NOT NULL COMMENT '全名',
  `gender` TINYINT NOT NULL COMMENT '1男 2女 0通用',
  `pinyin` VARCHAR(100) NOT NULL COMMENT '拼音',
  `pinyin_initial` VARCHAR(50) COMMENT '拼音首字母',
  `stroke_count` INT NOT NULL COMMENT '总笔画数',
  `five_element` VARCHAR(10) COMMENT '五行属性',
  `wu_ge_tian` INT COMMENT '天格数',
  `wu_ge_di` INT COMMENT '地格数',
  `wu_ge_ren` INT COMMENT '人格数',
  `wu_ge_wai` INT COMMENT '外格数',
  `wu_ge_zong` INT COMMENT '总格数',
  `wu_ge_lucky` VARCHAR(50) COMMENT '三才五格吉凶',
  `shape_score` INT COMMENT '形美评分 1-100',
  `sound_score` INT COMMENT '音顺评分 1-100',
  `meaning_score` INT COMMENT '义深评分 1-100',
  `wu_xing_score` INT COMMENT '五行评分 1-100',
  `total_score` INT COMMENT '综合评分 1-100',
  `meaning` TEXT COMMENT '寓意解释',
  `usage_count` INT DEFAULT 0 COMMENT '使用次数',
  `source_type` VARCHAR(20) COMMENT '来源类型 poetry/bazi/zhouyi/custom',
  `source_detail` VARCHAR(255) COMMENT '来源详情',
  `kanxi_explain` TEXT COMMENT '康熙字典解释',
  `is_popular` TINYINT DEFAULT 0 COMMENT '是否热门',
  `status` TINYINT DEFAULT 1 COMMENT '状态 0下架 1上架',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_full_name` (`full_name`),
  INDEX `idx_gender` (`gender`),
  INDEX `idx_five_element` (`five_element`),
  INDEX `idx_total_score` (`total_score` DESC),
  INDEX `idx_pinyin_initial` (`pinyin_initial`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='名字库表';

-- 名字期望标签关联表
CREATE TABLE IF NOT EXISTS `name_expect_tags` (
  `id` BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  `name_id` BIGINT UNSIGNED NOT NULL,
  `tag` VARCHAR(50) NOT NULL COMMENT '期望标签 如:聪明,勇敢,善良',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_name_id` (`name_id`),
  INDEX `idx_tag` (`tag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='名字期望标签表';

-- 起名记录表
CREATE TABLE IF NOT EXISTS `name_records` (
  `id` BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED COMMENT '用户ID',
  `session_id` VARCHAR(64) COMMENT '会话ID 匿名用户',
  `surname` VARCHAR(10) NOT NULL COMMENT '姓氏',
  `gender` TINYINT NOT NULL COMMENT '性别 1男 2女',
  `birth_date` DATE COMMENT '出生日期',
  `birth_time` VARCHAR(10) COMMENT '出生时辰',
  `name_type` VARCHAR(20) COMMENT '起名类型 bazi/poetry/zhouyi/normal',
  `expect_tags` VARCHAR(255) COMMENT '期望标签',
  `search_params` JSON COMMENT '搜索参数',
  `result_names` JSON COMMENT '返回的名字列表',
  `selected_name_id` BIGINT UNSIGNED COMMENT '用户选择的名字ID',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='起名记录表';

-- 用户收藏名字表
CREATE TABLE IF NOT EXISTS `name_favorites` (
  `id` BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `name_id` BIGINT UNSIGNED NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_user_name` (`user_id`, `name_id`),
  INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户收藏名字表';

-- 管理员表
CREATE TABLE IF NOT EXISTS `admins` (
  `id` BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL UNIQUE COMMENT '管理员用户名',
  `password_hash` VARCHAR(255) NOT NULL COMMENT '密码',
  `real_name` VARCHAR(50) COMMENT '真实姓名',
  `role` VARCHAR(20) NOT NULL COMMENT '角色 super_admin/admin/editor',
  `permissions` JSON COMMENT '权限列表',
  `status` TINYINT DEFAULT 1 COMMENT '状态 0禁用 1正常',
  `last_login_time` DATETIME COMMENT '最后登录时间',
  `last_login_ip` VARCHAR(45) COMMENT '最后登录IP',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_role` (`role`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员表';

-- 操作日志表
CREATE TABLE IF NOT EXISTS `admin_logs` (
  `id` BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  `admin_id` BIGINT UNSIGNED NOT NULL COMMENT '管理员ID',
  `admin_name` VARCHAR(50) NOT NULL COMMENT '管理员用户名',
  `action` VARCHAR(50) NOT NULL COMMENT '操作类型',
  `target_type` VARCHAR(50) COMMENT '操作对象类型',
  `target_id` BIGINT UNSIGNED COMMENT '操作对象ID',
  `detail` JSON COMMENT '操作详情',
  `ip` VARCHAR(45) COMMENT 'IP地址',
  `user_agent` VARCHAR(500) COMMENT '浏览器UA',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_admin_id` (`admin_id`),
  INDEX `idx_action` (`action`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员操作日志表';

-- 订单表
CREATE TABLE IF NOT EXISTS `orders` (
  `id` BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  `order_no` VARCHAR(50) NOT NULL UNIQUE COMMENT '订单号',
  `user_id` BIGINT UNSIGNED NOT NULL COMMENT '用户ID',
  `service_type` VARCHAR(20) NOT NULL COMMENT '服务类型 bazi/shici/zhouyi/company/normal',
  `service_name` VARCHAR(100) NOT NULL COMMENT '服务名称',
  `price` DECIMAL(10,2) NOT NULL COMMENT '价格',
  `discount` DECIMAL(10,2) DEFAULT 0 COMMENT '优惠金额',
  `actual_price` DECIMAL(10,2) NOT NULL COMMENT '实付金额',
  `status` TINYINT DEFAULT 1 COMMENT '状态 1待付款 2已付款 3服务中 4已完成 5已取消 6已退款',
  `payment_method` VARCHAR(20) COMMENT '支付方式 wechat/alipay/manual',
  `payment_time` DATETIME COMMENT '支付时间',
  `complete_time` DATETIME COMMENT '完成时间',
  `user_name` VARCHAR(50) COMMENT '被起名人姓名',
  `user_gender` TINYINT COMMENT '性别',
  `user_birth_date` DATE COMMENT '出生日期',
  `user_birth_time` VARCHAR(10) COMMENT '出生时辰',
  `requirements` TEXT COMMENT '用户需求描述',
  `service_result` JSON COMMENT '服务结果JSON',
  `selected_name_id` BIGINT UNSIGNED COMMENT '用户选择的名字ID',
  `admin_remark` TEXT COMMENT '管理员备注',
  `customer_remark` TEXT COMMENT '客户备注',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_order_no` (`order_no`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_service_type` (`service_type`),
  INDEX `idx_status` (`status`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='订单表';

-- 管理员初始化数据
INSERT INTO `admins` (`username`, `password_hash`, `real_name`, `role`, `status`) VALUES
('admin', '$2a$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewKyNiAYvxqFKqXe', '系统管理员', 'super_admin', 1);

SELECT 'Database initialized successfully!' as message;
