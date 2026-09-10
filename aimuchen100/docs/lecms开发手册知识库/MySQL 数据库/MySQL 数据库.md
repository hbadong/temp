---
kind: external_dependency
name: MySQL 数据库
slug: mysql
category: external_dependency
category_hints:
    - vendor_identity
scope:
    - '**'
source_files:
    - lecms/config/config.inc.php
    - install/data/mysql.sql
    - install/data/mysql_data.sql
    - install/data/core_engine.sql
last_updated: 2026-09-06
---

# MySQL 数据库（LECMS 集成规范）

## 一、版本与连接驱动

LECMS 默认连接方式：**PDO MySQL**（`db.type=pdo_mysql`），同时支持原生 `mysql`、`mysqli`。

| 项 | 值 |
|---|---|
| 推荐 MySQL | 5.7 / 8.0 / MariaDB 10.3+ |
| 字符集 | `utf8mb4`（配置 `db.master.charset`） |
| 表前缀 | `le_`（`db.master.tablepre`） |
| 默认引擎 | **MyISAM**（部分插件 install.php 已转 InnoDB） |
| 配置文件 | `lecms/config/config.inc.php` |
| 安装 SQL | `install/data/mysql.sql`、`mysql_data.sql`、`core_engine.sql` |
| 驱动类 | `lecms/xiunophp/db/db_pdo_mysql.class.php`（默认） |

## 二、连接配置示例

```php
// lecms/config/config.inc.php
$_ENV['_config'] = array(
  'db' => array(
    'type' => 'pdo_mysql',                  // mysql | mysqli | pdo_mysql
    'master' => array(
      'host'    => '127.0.0.1',
      'port'    => '3306',
      'user'    => 'aiuchen100',
      'password'=> 'aiuchen100',
      'name'    => 'aiuchen100',
      'charset' => 'utf8mb4',
      'tablepre'=> 'le_',
      'engine'  => 'MyISAM',                // 全局默认引擎
    ),
    // 'slaves' => array( ... ),            // 可选：读写分离
  ),
);
```

## 三、表结构总览

安装器按以下顺序导入 SQL：

1. `install/data/core_engine.sql` — 框架核心表（runtime / kv / only_alias / user / user_group）
2. `install/data/mysql.sql` — 业务主表（category / models / cms_content* / cms_page / tag* / comment* / attach* / flag* / views*）
3. `install/data/mysql_data.sql` — 初始数据（默认分类、模型、菜单、初始管理员）

### 3.1 框架核心表

| 表名 | 用途 | 引擎 |
|---|---|---|
| `le_runtime` | 运行时 KV 缓存（站点 cfg、临时数据） | InnoDB（默认） |
| `le_kv` | 业务 KV 存储（无表结构变更的配置项） | InnoDB |
| `le_only_alias` | 别名唯一性约束（URL 别名、用户名） | InnoDB |
| `le_user` | 会员（含密码、积分、登录态） | InnoDB |
| `le_user_group` | 会员组（普通/VIP/管理员） | InnoDB |

### 3.2 内容主表（按 mid 切表）

| mid | 表名 | 用途 |
|---:|---|---|
| 1 | `le_cms_article` | 默认文章 |
| 3 | `le_cms_game` | 游戏 |
| 6 | `le_cms_software` | 软件 |
| 自定义 | `le_cms_{tablename}` | 后台"内容模型"动态生成 |

> 内容主表配对 4 张辅表：

| 辅表 | 用途 |
|---|---|
| `le_cms_{x}_data` | 大字段（正文、扩展字段） |
| `le_cms_{x}_attach` | 附件关联 |
| `le_cms_{x}_comment` | 评论 |
| `le_cms_{x}_views` | 浏览量（分离写压力） |
| `le_cms_{x}_flag` | 属性标记（荐/热/头/精/幻） |

### 3.3 其他业务表

| 表名 | 用途 |
|---|---|
| `le_category` | 分类树（pid 嵌套） |
| `le_models` | 内容模型定义 |
| `le_cms_page` | 单页（可挂评论） |
| `le_cms_content_tag` | 全站标签库 |
| `le_cms_content_tag_data` | 标签 ↔ 内容关联 |
| `le_cms_content_comment_sort` | 评论楼层号 |
| `le_cms_content_comment` | 评论主表 |
| `le_attach` | 附件主表 |
| `le_plugin` | 插件元信息（可选） |
| `le_navigate` | 后台菜单（部分功能） |

## 四、SQL 规范

### 4.1 命名约定

- 表名：`le_` 前缀 + 业务前缀（cms_/user_/cms_content_）+ 功能词
- 字段名：全小写下下划线（`dateline`、`cms_content_id`、`seo_keywords`）
- 主键：单主键 `id`；复合主键 `(cid, id)` 用于 cms_content_attach
- 自增：所有主表用 `id int unsigned NOT NULL AUTO_INCREMENT`
- 时间戳：统一 `int(10) unsigned` 存 Unix 时间戳；不使用 `datetime`（避免时区问题）

### 4.2 建表模板

```sql
CREATE TABLE IF NOT EXISTS `le_demo` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cid` int(10) unsigned NOT NULL DEFAULT '0',
  `title` varchar(200) NOT NULL DEFAULT '',
  `dateline` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_cid` (`cid`),
  KEY `idx_dateline` (`dateline`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='示例表';
```

### 4.3 索引策略

- 内容表必备：`KEY idx_cid (cid)`、`KEY idx_dateline (dateline)`、`FULLTEXT KEY ft_title (title)`
- 唯一约束：放在 `only_alias` 表而不是 `UNIQUE KEY`
- 关联表用 `(content_id, attach_id)` 复合索引

## 五、引擎选择

| 表类型 | 推荐引擎 | 理由 |
|---|---|---|
| 内容主表（cms_article / cms_game） | **MyISAM** | 全文检索快、表级锁对纯读场景影响小 |
| 浏览量表（cms_article_views） | MyISAM | 高频写但只追加，不冲突 |
| 用户表（user） | InnoDB | 写入频繁、需要事务 |
| 配置表（runtime / kv） | InnoDB | 涉及事务一致性 |
| 标签关联（tag_data） | InnoDB | 复合唯一约束 |
| 附件元数据（attach） | InnoDB | 写多读少 |

> LECMS 插件 install.php 中已显式指定 `ENGINE=InnoDB` 的，应按表业务选择引擎，不要一刀切。

## 六、读写分离

配置示例：

```php
'db' => array(
  'type' => 'pdo_mysql',
  'master' => array( /* 主库 */ ),
  'slaves' => array(
    array( /* 从库 1 */ ),
    array( /* 从库 2 */ ),
  ),
)
```

> 框架当前**未自动启用从库**（`lecms/xiunophp/lib/model.class.php` 仅缓存 `$id = $type` 单连接）。需要扩展 `load_db()` 才能用。需要做读写分离时请同步改造。

## 七、备份与恢复

### 7.1 mysqldump 备份

```bash
# 全库
mysqldump -u aimumen100 -p aimumen100 \
  --default-character-set=utf8mb4 \
  --triggers --routines --events \
  --single-transaction \
  aimumen100 | gzip > backup_$(date +%Y%m%d).sql.gz

# 仅结构
mysqldump -u aimumen100 -p aimumen100 --no-data > schema.sql
```

### 7.2 仅备份内容

```bash
mysqldump -u aimumen100 -p aimumen100 \
  le_cms_article le_cms_article_data le_category le_kv le_runtime \
  | gzip > content_$(date +%Y%m%d).sql.gz
```

### 7.3 还原

```bash
gunzip < backup.sql.gz | mysql -u aimumen100 -p aimumen100
```

## 八、性能优化建议

1. **大表分表**：cms_article 超过 100 万行建议按年/月分表
2. **避免 `SELECT *`**：业务代码用 `find_fetch(where, order, 0, limit)` 取主表字段，按需 `get()` 取 `_data`
3. **二级缓存**：开启 `cache.enable=1` + `cache.l2_cache=1`（默认 Memcache）
4. **慢查询日志**：`SET GLOBAL slow_query_log='ON'; SET GLOBAL long_query_time=1;`
5. **EXPLAIN 检查**：所有新增索引必须 EXPLAIN 验证
6. **批写**：模型 `find_update` / `find_delete` 命中 > 2000 行会 `cache.truncate()` 避免大 key 失效风暴

## 九、迁移到 PHP 8.x / MySQL 8.x 的注意事项

| 项 | 风险点 | 建议 |
|---|---|---|
| MySQL 8.0 移除 `utf8mb3` 默认排序规则 | utf8 → utf8mb3 转换警告 | 全量统一 utf8mb4 + utf8mb4_unicode_ci |
| `GROUP BY` 隐式排序失效 | SQL_MODE=ONLY_FULL_GROUP_BY | 检查所有带 GROUP BY 的查询 |
| MyISAM + 外键 | MyISAM 不支持外键 | 业务表可保持 MyISAM，关系约束放应用层 |
| PDO 默认模拟预处理 | 老代码可能有 SQL 注入 | 升级时审计所有 `$this->db->query()` 直拼 SQL |

## 十、相关源码位置

- `lecms/xiunophp/db/db_pdo_mysql.class.php` — PDO MySQL 驱动
- `lecms/xiunophp/db/db_mysqli.class.php` — MySQLi 驱动
- `lecms/xiunophp/db/db_mysql.class.php` — MySQL 扩展驱动（已弃用）
- `lecms/xiunophp/db/db.interface.php` — 数据库接口契约
- `install/data/*.sql` — 全部建表 SQL
- 主知识库"第十篇_数据库与缓存.md"、"第七篇_附录.md §106" — 表结构全表

## 十一、FAQ

**Q：MyISAM 表损坏怎么办？**
A：`REPAIR TABLE le_xxx;`；如数据可丢则 `TRUNCATE` 后重新导入。

**Q：字符集乱码？**
A：检查表 `SHOW CREATE TABLE` 的 CHARSET 是否 utf8mb4；连接串 `charset=utf8mb4`；PHP 文件 BOM 头去掉。

**Q：表前缀如何批量替换？**
A：`RENAME TABLE le_xxx TO my_xxx;`，并同步修改 `config.inc.php` 的 `tablepre`。

**Q：怎么排查慢 SQL？**
A：开启 DEBUG=2 后页面底部 `$_ENV['_sqls']` 会列出全部 SQL 与执行时间。