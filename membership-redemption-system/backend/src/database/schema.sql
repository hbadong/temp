-- 会员兑换系统数据库表结构

-- 用户表
CREATE TABLE IF NOT EXISTS users (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    phone VARCHAR(11) NOT NULL UNIQUE COMMENT '手机号',
    password_hash VARCHAR(255) NULL COMMENT '密码哈希(可选)',
    status TINYINT NOT NULL DEFAULT 1 COMMENT '状态: 0-禁用, 1-正常',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_phone (phone),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户表';

-- 管理员表
CREATE TABLE IF NOT EXISTS admins (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE COMMENT '用户名',
    password_hash VARCHAR(255) NOT NULL COMMENT '密码哈希',
    role VARCHAR(20) NOT NULL DEFAULT 'admin' COMMENT '角色: super_admin, admin',
    status TINYINT NOT NULL DEFAULT 1 COMMENT '状态: 0-禁用, 1-正常',
    last_login_at DATETIME NULL COMMENT '最后登录时间',
    failed_login_attempts INT NOT NULL DEFAULT 0 COMMENT '连续登录失败次数',
    locked_until DATETIME NULL COMMENT '账户锁定截止时间',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_role (role),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员表';

-- 套餐表
CREATE TABLE IF NOT EXISTS products (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    platform VARCHAR(20) NOT NULL COMMENT '平台: iqiyi, youku, tencent',
    name VARCHAR(100) NOT NULL COMMENT '套餐名称',
    description TEXT NULL COMMENT '套餐描述',
    duration_days INT NOT NULL COMMENT '时长(天)',
    price DECIMAL(10,2) NOT NULL COMMENT '价格',
    stock INT NOT NULL DEFAULT 0 COMMENT '库存',
    status TINYINT NOT NULL DEFAULT 1 COMMENT '状态: 0-下架, 1-上架',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_platform (platform),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='套餐表';

-- 卡密批次表
CREATE TABLE IF NOT EXISTS card_batches (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    batch_no VARCHAR(32) NOT NULL UNIQUE COMMENT '批次号',
    product_id BIGINT NOT NULL COMMENT '关联套餐ID',
    prefix VARCHAR(10) NOT NULL DEFAULT 'CRS' COMMENT '卡密前缀',
    total_count INT NOT NULL COMMENT '生成数量',
    used_count INT NOT NULL DEFAULT 0 COMMENT '已使用数量',
    valid_from DATETIME NOT NULL COMMENT '有效期开始',
    valid_until DATETIME NOT NULL COMMENT '有效期结束',
    created_by BIGINT NOT NULL COMMENT '创建管理员ID',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_batch_no (batch_no),
    INDEX idx_product_id (product_id),
    INDEX idx_valid_dates (valid_from, valid_until),
    FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='卡密批次表';

-- 卡密表
CREATE TABLE IF NOT EXISTS cards (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    card_no VARCHAR(32) NOT NULL UNIQUE COMMENT '卡密号',
    batch_id BIGINT NOT NULL COMMENT '关联批次ID',
    password VARCHAR(64) NOT NULL COMMENT '卡密密码(加密存储)',
    product_id BIGINT NOT NULL COMMENT '关联套餐ID',
    status TINYINT NOT NULL DEFAULT 0 COMMENT '状态: 0-未使用, 1-已使用, 2-已作废',
    used_by BIGINT NULL COMMENT '使用用户ID',
    used_at DATETIME NULL COMMENT '使用时间',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_card_no (card_no),
    INDEX idx_batch_id (batch_id),
    INDEX idx_product_id (product_id),
    INDEX idx_status (status),
    INDEX idx_used_by (used_by),
    FOREIGN KEY (batch_id) REFERENCES card_batches(id),
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (used_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='卡密表';

-- 订单表
CREATE TABLE IF NOT EXISTS orders (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    order_no VARCHAR(32) NOT NULL UNIQUE COMMENT '订单号',
    user_id BIGINT NOT NULL COMMENT '用户ID',
    product_id BIGINT NOT NULL COMMENT '套餐ID',
    type TINYINT NOT NULL COMMENT '类型: 1-手机兑换, 2-卡密充值',
    card_id BIGINT NULL COMMENT '卡密ID(卡密充值时)',
    target_account VARCHAR(100) NOT NULL COMMENT '目标充值账号(手机号或会员账号)',
    amount DECIMAL(10,2) NOT NULL COMMENT '订单金额',
    status TINYINT NOT NULL DEFAULT 0 COMMENT '状态: 0-待支付, 1-处理中, 2-成功, 3-失败',
    platform_order_no VARCHAR(64) NULL COMMENT '第三方平台订单号',
    failure_reason VARCHAR(255) NULL COMMENT '失败原因',
    processed_at DATETIME NULL COMMENT '处理完成时间',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_order_no (order_no),
    INDEX idx_user_id (user_id),
    INDEX idx_product_id (product_id),
    INDEX idx_type (type),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (card_id) REFERENCES cards(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='订单表';

-- 短信记录表
CREATE TABLE IF NOT EXISTS sms_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    phone VARCHAR(11) NOT NULL COMMENT '接收手机号',
    type VARCHAR(20) NOT NULL COMMENT '类型: verify_code, notification',
    template_code VARCHAR(30) NOT NULL COMMENT '短信模板码',
    content TEXT NULL COMMENT '短信内容',
    status TINYINT NOT NULL DEFAULT 0 COMMENT '状态: 0-待发送, 1-成功, 2-失败',
    error_code VARCHAR(20) NULL COMMENT '错误码',
    error_message VARCHAR(255) NULL COMMENT '错误信息',
    send_at DATETIME NULL COMMENT '发送时间',
    response TEXT NULL COMMENT '第三方返回结果',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_phone (phone),
    INDEX idx_type (type),
    INDEX idx_status (status),
    INDEX idx_send_at (send_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='短信记录表';

-- 操作日志表
CREATE TABLE IF NOT EXISTS operation_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    admin_id BIGINT NOT NULL COMMENT '管理员ID',
    action VARCHAR(50) NOT NULL COMMENT '操作类型',
    target_type VARCHAR(30) NULL COMMENT '目标类型',
    target_id BIGINT NULL COMMENT '目标ID',
    detail JSON NULL COMMENT '操作详情',
    ip VARCHAR(45) NULL COMMENT 'IP地址',
    user_agent TEXT NULL COMMENT '用户代理',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_admin_id (admin_id),
    INDEX idx_action (action),
    INDEX idx_target (target_type, target_id),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (admin_id) REFERENCES admins(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='操作日志表';

-- 系统配置表
CREATE TABLE IF NOT EXISTS system_configs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    config_key VARCHAR(50) NOT NULL UNIQUE COMMENT '配置键',
    config_value TEXT NULL COMMENT '配置值(加密存储)',
    description VARCHAR(255) NULL COMMENT '配置描述',
    is_encrypted TINYINT NOT NULL DEFAULT 0 COMMENT '是否加密: 0-否, 1-是',
    updated_by BIGINT NULL COMMENT '最后更新管理员ID',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_config_key (config_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统配置表';
