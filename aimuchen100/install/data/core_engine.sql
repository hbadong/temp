-- core-engine 数据库表
-- 由 AI沐尘100 安装脚本自动执行

-- 1. 多站点管理表
CREATE TABLE pre_site_manager (
    sid INT PRIMARY KEY AUTO_INCREMENT COMMENT '站点ID',
    site_name VARCHAR(100) NOT NULL COMMENT '站点名称',
    domain VARCHAR(100) NOT NULL COMMENT '域名，支持 *.xxx.com 泛域名',
    theme VARCHAR(50) NOT NULL DEFAULT 'default' COMMENT '当前主题',
    status TINYINT DEFAULT 1 COMMENT '状态: 1启用 0禁用 -1删除',
    config JSON COMMENT '站点级JSON配置(theme_vars, override_templates等)',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    UNIQUE KEY uk_domain (domain)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='多站点管理表';

-- 2. URL 映射表
CREATE TABLE pre_cms_url_map (
    id BIGINT PRIMARY KEY AUTO_INCREMENT COMMENT 'URL ID',
    site_id INT NOT NULL COMMENT '站点ID',
    url VARCHAR(500) NOT NULL COMMENT '完整URL路径',
    url_hash CHAR(40) NOT NULL COMMENT 'SHA1哈希(小写,40字符)',
    type TINYINT NOT NULL COMMENT 'URL类型: 1-8',
    control VARCHAR(50) NOT NULL COMMENT '控制器名',
    action VARCHAR(50) NOT NULL COMMENT '操作方法',
    params TEXT COMMENT 'JSON格式路由参数',
    score TINYINT DEFAULT 80 COMMENT 'SEO质量评分(70-90)',
    status TINYINT DEFAULT 1 COMMENT '状态: 1待生成 2已使用 3已失效',
    content_id INT DEFAULT 0 COMMENT '关联的内容ID',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    UNIQUE KEY uk_site_hash (site_id, url_hash),
    INDEX idx_status (site_id, status),
    INDEX idx_score (score)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='URL映射表';

-- 3. 游戏元数据表
CREATE TABLE pre_cms_game (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT '游戏ID',
    site_id INT NOT NULL COMMENT '站点ID',
    name VARCHAR(200) NOT NULL COMMENT '游戏名称',
    platform VARCHAR(50) DEFAULT '' COMMENT '平台(pc/mobile/console)',
    category_id INT DEFAULT 0 COMMENT '分类ID',
    description TEXT COMMENT '游戏描述',
    cover VARCHAR(500) DEFAULT '' COMMENT '封面图URL',
    tags VARCHAR(500) DEFAULT '' COMMENT '标签(逗号分隔)',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    INDEX idx_site (site_id),
    INDEX idx_category (site_id, category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='游戏元数据表';

-- 4. AI 配置表
CREATE TABLE pre_ai_config (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT '配置ID',
    site_id INT NOT NULL DEFAULT 0 COMMENT '站点ID(0=全局配置)',
    api_base_url VARCHAR(500) DEFAULT 'https://api.deepseek.com/v1' COMMENT 'API Base URL',
    api_key VARCHAR(500) NOT NULL DEFAULT '' COMMENT 'API密钥',
    model VARCHAR(100) DEFAULT 'deepseek-chat' COMMENT '模型名称',
    max_tokens INT DEFAULT 2048 COMMENT '最大生成token数',
    temperature DECIMAL(2,1) DEFAULT 0.7 COMMENT '温度参数',
    timeout INT DEFAULT 60 COMMENT '请求超时(秒)',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    UNIQUE KEY uk_site (site_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='AI API配置表';

-- 5. AI 任务表
CREATE TABLE pre_ai_task (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT '任务ID',
    site_id INT NOT NULL COMMENT '站点ID',
    category_id INT DEFAULT 0 COMMENT '分类ID',
    prompt_template TEXT COMMENT 'Prompt模板',
    batch_size INT DEFAULT 50 COMMENT '每批生成数量',
    total INT DEFAULT 0 COMMENT '计划生成总数',
    success INT DEFAULT 0 COMMENT '成功生成数',
    fail INT DEFAULT 0 COMMENT '失败数',
    status TINYINT DEFAULT 0 COMMENT '状态: 0待处理 1处理中 2完成 3失败',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    INDEX idx_site_status (site_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='AI内容生成任务表';

-- 6. 扩展 le_category 表（SEO 字段）
-- 使用 IF NOT EXISTS 避免重复执行报错
-- ALTER TABLE 不支持 IF NOT EXISTS，用存储过程包裹
DELIMITER $$
DROP PROCEDURE IF EXISTS add_category_seo_fields$$
CREATE PROCEDURE add_category_seo_fields()
BEGIN
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = CONCAT('pre_category')
        AND COLUMN_NAME = 'seo_title') THEN
        ALTER TABLE pre_category
            ADD COLUMN seo_title VARCHAR(200) DEFAULT '' AFTER `name`,
            ADD COLUMN seo_keywords VARCHAR(200) DEFAULT '' AFTER seo_title,
            ADD COLUMN seo_description VARCHAR(500) DEFAULT '' AFTER seo_keywords;
    END IF;
END$$
DELIMITER ;
CALL add_category_seo_fields();
DROP PROCEDURE IF EXISTS add_category_seo_fields;
