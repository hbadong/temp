# LeCMS 开发知识库 — MVC 架构
> 对应原文档：第 四 篇

> **版本**: 1.0
> **更新日期**: 2026-07-25
> **框架版本**: LeCMS 3.0.3 + XiunoPHP 1.0.0
> **PHP 版本**: 5.4.0 - 8.2.x
> **数据库**: MySQL 5.6+ / MariaDB
> **协议**: MIT License

---

## 本章包含章节

- 16. Model 层详解
- 16.1 模型基类完整架构
- 16.2 方法签名总表
- 16.3 懒加载机制
- 16.4 `$unique` 内存缓存
- 16.5 自定义模型示例
- 17. 数据库抽象层
- 17.1 接口定义（db_interface）
- 17.2 驱动对比与选型
- 17.3 PDO 驱动三连接模型
- 17.4 键名系统（Key Naming）
- 17.5 Where 条件构建器
- 17.6 读写分离
- 18. Model 二级缓存机制
- 18.1 三级缓存总览
- 18.2 L1 内存缓存（`$unique`）
- 18.3 L2 二级缓存（`l2_cache_get/set`）
- 18.4 缓存辅助方法
- 19. Controller 层
- 19.1 基类继承链
- 19.2 `__get` 懒加载
- 19.3 模板变量管理
- 19.4 `message()` 消息提示
- 19.5 Hook 扩展点
- 20. View 层
- 20.1 模板解析流程
- 20.2 9 步编译流程详解
- 20.3 安全机制

---

## 第四篇：MVC 架构

---

## 16. Model 层详解

> **源码位置**: `lecms/xiunophp/lib/model.class.php`（662 行）
>
> 文件头注释（第 1-30 行）清晰说明了设计目标：**统一 cache+db 接口，并设计了二级缓存，进一步减轻数据库压力。**

### 16.1 模型基类完整架构

```php
class model {
    // ============================================================
    // 1. 必须指定的三项（子类通过 __construct 设置）
    // ============================================================
    public $table;            // 表名（不含前缀，如 'user'、'category'）
    public $pri = array();    // 主键字段数组
                             // 单主键: array('cid')
                             // 联合主键: array('cid', 'id')
                             // 无主键: array() —— 此时不走 pri2key 流程
    public $maxid;            // 自增字段名（如 'cid'、'id'）
                             // 如果表无自增字段，设为 '' 或 false
                             // 设为 false 时: create() 不走 maxid 流程

    // ============================================================
    // 2. 静态属性（连接池）
    // ============================================================
    static $dbs = array();    // 数据库连接池
                              // key: {type}-{host}-{user}-{password}-{name}-{tablepre}
                              // 相同配置的 DB 只创建一次
    static $caches = array(); // 缓存连接池
                              // key: {type}-{host}-{port}

    // ============================================================
    // 3. 实例属性
    // ============================================================
    private $unique = array(); // 内存缓存（防止重复查询）
                               // key: {table}-{pri1}-{val1}-{pri2}-{val2}...

    // ============================================================
    // 4. __construct —— 子类覆盖
    // ============================================================
    function __construct() {
        // 子类在构造函数中设置 $this->table、$this->pri、$this->maxid
    }

    // ============================================================
    // 5. __get —— 核心懒加载（详见 16.3）
    // ============================================================
    function __get($var) { ... }

    // ============================================================
    // 6. __call —— 未定义方法抛出异常
    // ============================================================
    function __call($method, $args) {
        throw new Exception("方法 $method 不存在");
    }
}
```

**设计原则**（来自源码第 127-129 行注释）:

> 所有符合标准表结构（有主键、有自增字段）的表都可以使用基类的 CRUD 方法，无需编写 SQL。自定义模型只需设置 `$table`、`$pri`、`$maxid` 三项。

---

### 16.2 方法签名总表

Model 基类共对外开放 **25 个公共方法**，分为 4 个层级：

#### 16.2.1 CRUD 方法（Cache + DB 双层）

| 方法 | 签名 | 返回值 | 功能说明 |
|------|------|--------|----------|
| `create()` | `create(array $data): mixed` | 自增 ID / FALSE | 插入一条数据。自动处理 maxid +1、count +1、$unique 写入、cache_db_set 双写。如果 $maxid 为空则不处理 maxid/count。 |
| `set()` | `set($key, array $data, int $life = 0): bool` | TRUE / FALSE | 写入数据（自动判断 insert/update）。支持 `$key` 为单个值或值数组。$life 为缓存过期时间（0=永久）。 |
| `get()` | `get($arr): array` | array / NULL | 读取一条数据。自动走 L1 内存缓存（$unique）。$arr 为值数组或单个值。 |
| `mget()` | `mget(array $arr): array` | array | 批量读取多条数据。自动走 L1 内存缓存，未命中部分批量走 cache_db_multi_get。保证返回数组顺序。 |
| `update()` | `update(array $data, int $life = 0): bool` | TRUE / FALSE | 更新一条数据。自动走 L1 缓存、cache_db_update 双写。数据必须包含主键。 |
| `delete()` | `delete($arg1, $arg2, $arg3, $arg4): bool` | TRUE / FALSE | 删除一条数据（最多支持 4 个主键参数简化写法）。自动走 L1 缓存、cache_db_delete 双写、count -1。 |
| `del()` | `del($arr): bool` | TRUE / FALSE | 删除一条数据（值数组写法）。同上。 |
| `truncate()` | `truncate(): bool` | TRUE / FALSE | 清空表。同时清空 cache、DB、L1 缓存。 |

**`create()` 详细执行流程**（源码第 135-153 行）:

```php
public function create($data) {
    if(empty($this->maxid)) {
        // 无自增字段: 直接缓存+DB双写
        $key = $this->pri2key($data);
        return $this->cache_db_set($key, $data);
    } else {
        // 有自增字段: 先获取 maxid+1
        $data[$this->maxid] = $this->maxid('+1');
        $key = $this->pri2key($data);
        $this->count('+1');  // 表总行数 +1
        if($this->cache_db_set($key, $data)) {
            return $data[$this->maxid]; // 成功: 返回自增ID
        } else {
            // 失败: 回滚 maxid 和 count
            $this->maxid('-1');
            $this->count('-1');
            return FALSE;
        }
    }
}
```

**`set()` 自动判断 insert/update 的逻辑**（源码第 168-172 行）:

> `set()` 本身不做 insert/update 判断，判断逻辑在底层 `db->set()` 中。底层根据 key 是否存在决定是 INSERT 还是 UPDATE。

#### 16.2.2 查询方法

| 方法 | 签名 | 返回值 | 功能说明 |
|------|------|--------|----------|
| `find_fetch()` | `find_fetch($where, $order, $start, $limit, $life): array` | array | 条件查询（列表）。支持二级缓存（L2）。先查询 key 列表（L2），再批量 get 数据（L1 + cache）。 |
| `find_fetch_key()` | `find_fetch_key($where, $order, $start, $limit, $life): array` | array | 条件查询返回 key 数组（用于二级缓存中的 key 列表缓存）。 |
| `find_update()` | `find_update($where, $data, $order, $limit, $lowprority): int` | int | 批量更新。超过 2000 条时清空整个表缓存，否则逐条删除缓存。 |
| `find_delete()` | `find_delete($where, $order, $limit, $lowprority): int` | int | 批量删除。超过 2000 条时清空整个表缓存，否则逐条删除缓存。 |
| `find_count()` | `find_count($where): int` | int | 条件计数（走 DB，无缓存，准确但较慢）。 |
| `find_maxid()` | `find_maxid(): int` | int | 准确获取最大 ID（走 DB，无缓存）。 |

#### 16.2.3 辅助方法

| 方法 | 签名 | 返回值 | 功能说明 |
|------|------|--------|----------|
| `maxid()` | `maxid($val = FALSE): int` | int | 读取/设置表最大 ID。$val=FALSE 时读取，'+1' 时基础上 +1，直接值设置。 |
| `count()` | `count($val = FALSE): int` | int | 读取/设置表总行数。$val=FALSE 时读取，100 时设置，'+1' 时 +1，'-1' 时 -1。 |
| `update_views()` | `update_views($key, $n = 1, $field = 'views'): bool` | bool | 浏览量 +1（或其他值）。先删除缓存再更新 DB。 |
| `get_field()` | `get_field(): array` | array | 获取表的所有字段名。走 `$this->db->get_field($this->table)`。 |
| `pri2key()` | `pri2key($arr): string` | string | 关联数组转 Key。格式: `{table}-{pri1}-{val1}-{pri2}-{val2}...` |
| `arr2key()` | `arr2key($arr): string` | string | 数字索引数组转 Key。同 pri2key，但参数为数字数组。 |
| `arg2arr()` | `arg2arr($arg1, $arg2, $arg3, $arg4): array` | array | 多参数转数组（用于 delete(1,2,3,4) 简化写法）。 |
| `index_create()` | `index_create($index): bool` | bool | 创建索引。$index 格式参考 MongoDB 索引格式（兼容设计）。 |
| `index_drop()` | `index_drop($index): bool` | bool | 删除索引。 |

#### 16.2.4 只读方法

| 方法 | 签名 | 返回值 | 功能说明 |
|------|------|--------|----------|
| `read()` | `read($arg1, $arg2, $arg3, $arg4): array` | array | 读取数据的简化写法（最多 4 个主键参数）。内部调用 `get()`。 |
| `get_field()` | `get_field(): array` | array | 获取表的所有字段名。 |

---

### 16.3 懒加载机制（`__get`）

**源码位置**: `model.class.php` 第 51-64 行

Model 的 `__get` 魔术方法实现了 4 种属性的懒加载，延迟到首次访问时才创建对象：

```php
function __get($var) {
    switch ($var) {
        case 'db':
            // 1. 加载数据库（单例模式）
            return $this->db = $this->load_db();

        case 'cache':
            // 2. 加载缓存（单例模式）
            return $this->cache = $this->load_cache();

        case 'db_conf':
            // 3. 加载 DB 配置（引用传递）
            return $this->db_conf = &$_ENV['_config']['db'];

        case 'cache_conf':
            // 4. 加载 Cache 配置（引用传递）
            return $this->cache_conf = &$_ENV['_config']['cache'];

        default:
            // 5. 加载其他模型（core::model 自动实例化）
            return $this->$var = core::model($var);
    }
}
```

**5 种加载路径详解**:

| 访问方式 | 触发路径 | 返回对象 | 说明 |
|----------|----------|----------|------|
| `$this->db` | `__get('db')` → `load_db()` → `new db_{type}()` | db 实例 | 数据库连接。通过 static $dbs 连接池单例。 |
| `$this->cache` | `__get('cache')` → `load_cache()` → `new cache_{type}()` | cache 实例 | 缓存连接。通过 static $caches 连接池单例。 |
| `$this->db_conf` | `__get('db_conf')` | array（引用） | DB 全局配置引用。修改会影响整个请求。 |
| `$this->cache_conf` | `__get('cache_conf')` | array（引用） | Cache 全局配置引用。 |
| `$this->user` | `__get('user')` → `core::model('user')` | user_model 实例 | 自动加载 `user_model.class.php`。 |

**`load_db()` 单例逻辑**（源码第 78-98 行）:

```php
public function load_db() {
    $type  = $this->db_conf['type'];
    $m     = $this->db_conf['master'];
    $id    = $type.'-'.$m['host'].'-'.$m['user'].'-'.$m['password'].'-'.$m['name'].'-'.$m['tablepre'];

    if(isset(self::$dbs[$id])) {
        return self::$dbs[$id]; // 已存在: 直接返回
    } else {
        $db = 'db_'.$type;
        self::$dbs[$id] = new $db($this->db_conf); // 创建实例并存入连接池
        return self::$dbs[$id];
    }
}
```

> **连接 ID 格式**: `{type}-{host}-{user}-{password}-{dbname}-{tablepre}`
> 这意味着同一个数据库实例（相同主机、用户、密码、库名、表前缀）在整个请求周期内只会创建一次 db 对象。

---

### 16.4 `$unique` 内存缓存

**源码位置**: `model.class.php` 第 44 行、第 192-196 行

```php
private $unique = array(); // 第 0 级缓存（内存级别，单请求周期有效）
```

`$unique` 是 Model 的**第一级缓存（L1）**，在整个 PHP 请求生命周期内有效。它是一个普通的 PHP 关联数组，Key 格式为: `{table}-{pri1}-{val1}...`

**`get()` 的 L1 缓存逻辑**（源码第 192-196 行）:

```php
public function get($arr) {
    $key = $this->arr2key($arr);
    if(!isset($this->unique[$key])) {
        // L1 未命中: 从 cache_db_get 获取（L2 cache 或 DB）
        $this->unique[$key] = $this->cache_db_get($key);
    }
    // L1 命中: 直接返回内存数据
    return $this->unique[$key];
}
```

**`set()` 的 L1 缓存写入**（源码第 170 行）:

```php
public function set($key, $data, $life = 0) {
    $key = $this->arr2key($key);
    $this->unique[$key] = $data; // 写入 L1 内存缓存
    return $this->cache_db_set($key, $data, $life); // 同时写 L2 + DB
}
```

**`update()` 的 L1 缓存更新**（源码第 228 行）:

```php
public function update($data, $life = 0) {
    $key = $this->pri2key($data);
    $this->unique[$key] = $data; // 更新 L1 内存缓存
    return $this->cache_db_update($key, $data, $life); // 同时更新 L2 + DB
}
```

**`del()` 的 L1 缓存清除**（源码第 252 行）:

```php
public function del($arr) {
    $key = $this->arr2key($arr);
    $ret = $this->cache_db_delete($key);
    if($ret) {
        unset($this->unique[$key]); // 从 L1 中删除
        $this->count('-1');
    }
    return $ret;
}
```

**`mget()` 的 L1 批量读取**（源码第 205-218 行）:

```php
public function mget($arr) {
    $data = array();
    foreach($arr as $k=>&$key) {
        $key = $this->arr2key($key);
        if(isset($this->unique[$key])) {
            // L1 命中: 直接返回
            $data[$key] = $this->unique[$key];
            unset($arr[$k]);
        } else {
            // L1 未命中: 占位（NULL），保证返回数组顺序
            $this->unique[$key] = $data[$key] = NULL;
        }
    }
    // 批量读取 L1 未命中的部分
    $data2 = $this->cache_db_multi_get($arr);
    return array_merge($data, $data2);
}
```

> **L1 缓存的特点**:
> - 生命周期：仅当前 PHP 请求有效（单次请求不重复查询）
> - 无序列化：直接存储 PHP 数组，无任何性能损耗
> - 无过期机制：随请求销毁自动释放
> - 适用场景：同一请求中多次读取同一行数据（如列表页中既有列表又有详情统计）

---

### 16.5 自定义模型示例

```php
<?php
defined('ROOT_PATH') or exit;

class custom extends model {

    function __construct() {
        $this->table = 'my_table';     // 表名（不含前缀）
        $this->pri = array('id');      // 主键
        $this->maxid = 'id';           // 自增字段
    }

    // ============================================================
    // 大数据量翻页优化（来源: 实际源码 custom_model）
    // ============================================================
    // 技巧: 当翻页位置超过总条数的一半时，反向查询
    // 原因: MySQL 的 LIMIT 100000, 20 会扫描 100020 条，比 LIMIT 20 慢得多
    public function list_arr($where = array(), $orderby = 'id',
                          $orderway = 1, $start = 0, $limit = 0,
                          $total = 0, $extra = array()) {

        // hook custom_model_list_arr_before.php

        if($start > 1000 && $total > 2000 && $start > $total/2) {
            // 翻页超过一半: 反向查询
            $orderway = -$orderway;
            $newstart = $total - $start - $limit;
            if($newstart < 0) {
                $limit += $newstart;
                $newstart = 0;
            }
            $list_arr = $this->find_fetch($where, array($orderby => $orderway),
                                         $newstart, $limit);
            $list_arr = array_reverse($list_arr, TRUE);
        } else {
            // 翻页在前半部分: 正向查询
            $list_arr = $this->find_fetch($where, array($orderby => $orderway),
                                         $start, $limit);
        }

        // hook custom_model_list_arr_after.php
        return $list_arr;
    }

    // hook custom_model_after.php
}
```

> **大数据量翻页优化原理**:
> - 条件: `$start > 1000 && $total > 2000 && $start > $total/2`
> - 正向查询 `LIMIT 100000, 20`：MySQL 需要扫描 100020 条记录后丢弃前 100000 条
> - 反向查询 `LIMIT 20 OFFSET 20`：MySQL 只扫描 40 条记录
> - 最后 `array_reverse` 恢复顺序，但此时只需反转 20 条，性能提升巨大

---

## 17. 数据库抽象层

### 17.1 接口定义（db_interface）

> **源码位置**: `lecms/xiunophp/db/db.interface.php`

```php
interface db_interface {
    // ==================== 基础 CRUD ====================
    public function get($key);                     // 读取一条（按 Key）
    public function multi_get($keys);              // 批量读取
    public function set($key, $data);              // 写入一条（自动判断 insert/update）
    public function update($key, $data);           // 更新一条
    public function delete($key);                  // 删除一条
    public function maxid($key, $val = FALSE);     // 读取/设置 maxid
    public function count($key, $val = FALSE);     // 读取/设置 count
    public function truncate($table);              // 清空表

    // ==================== 条件查询 ====================
    public function find_fetch($table, $pri, $where, $order, $start, $limit);
    public function find_fetch_key($table, $pri, $where, $order, $start, $limit);
    public function find_update($table, $where, $data, $order, $limit, $lowprority);
    public function find_delete($table, $where, $order, $limit, $lowprority);
    public function find_maxid($key);
    public function find_count($table, $where);

    // ==================== 索引管理 ====================
    public function index_create($table, $index);
    public function index_drop($table, $index);

    // ==================== 表管理 ====================
    public function get_field($table);         // 获取表字段
    public function exist_table($table);       // 判断表是否存在
    public function table_drop($table);        // 删除表
    public function table_create($table, $cols); // 创建表
    public function delete_db();               // 删除数据库
}
```

> **接口方法总数**: 15 个（基础 CRUD 8 个 + 条件查询 6 个 + 索引管理 2 个 + 表管理 5 个 = 21 个方法）
>
> **设计意义**: db_interface 使得 Model 层不关心底层是 MySQL、MongoDB 还是其他存储。只要实现 db_interface 就可以作为 Model 的存储后端。

---

### 17.2 驱动对比与选型

| 驱动 | 类文件 | PHP 版本 | PECL 扩展 | 推荐度 | 说明 |
|------|--------|---------|-----------|--------|------|
| `mysql` | `db_mysql.class.php` | PHP 5.5- | 无（内置） | ⭐⭐ | 最老的扩展，已废弃。LeCMS 仍保留兼容。 |
| `mysqli` | `db_mysqli.class.php` | PHP 5.0+ | 无（内置） | ⭐⭐⭐⭐ | 面向对象写法，支持预处理。推荐兼容方案。 |
| `pdo_mysql` | `db_pdo_mysql.class.php` | PHP 5.1+ | `pdo_mysql` | ⭐⭐⭐⭐⭐ | **框架默认推荐**。支持多驱动切换、预处理语句、命名参数。 |
| `mongodb` | （未内置） | - | `mongodb` | ⭐⭐⭐ | Model 层已预留条件（源码第 629 行），但需自行实现驱动。 |

**`pdo_mysql` 驱动源码解析**（`db_pdo_mysql` 类）:

```php
class db_pdo_mysql implements db_interface {
    public $tablepre;                          // 表前缀
    public $conf = array();                    // 配置引用

    public function __construct(&$conf) {
        $this->conf = &$conf;
        $this->tablepre = $conf['master']['tablepre'];
    }

    // __get: 三连接模型（详见 17.3）
    public function __get($var) { ... }
}
```

> **配置引用**: `db_pdo_mysql` 接收 `&$conf`（引用传递），对配置的任何修改都会反映到全局 `$_ENV['_config']['db']` 中。

---

### 17.3 PDO 驱动三连接模型

> **源码位置**: `db_pdo_mysql` 的 `__get()` 方法

PDO 驱动使用 **三种连接** 分别对应不同的业务场景：

```php
public function __get($var) {
    // ========== wlink: 写主库 ==========
    if($var == 'wlink') {
        // 连接: master 节点
        // 用途: INSERT、UPDATE、DELETE、DDL 等写操作
        $cfg = $this->conf['master'];
        $this->wlink = $this->connect(...);
        return $this->wlink;
    }
    // ========== rlink: 读从库 ==========
    elseif($var == 'rlink') {
        // 连接: 随机选择 slaves 节点
        // 用途: SELECT 等读操作（不需要实时性的查询）
        if(empty($this->conf['slaves'])) {
            // 无从库时回退到主库
            $this->rlink = $this->wlink;
            return $this->rlink;
        }
        $n = rand(0, count($this->conf['slaves']) - 1);
        $cfg = $this->conf['slaves'][$n];
        $this->rlink = $this->connect(...);
        return $this->rlink;
    }
    // ========== xlink: 单点分发 ==========
    elseif($var == 'xlink') {
        // 连接: arbiter 节点
        // 用途: maxid、count 等需要实时一致性的统计查询
        if(empty($this->conf['arbiter'])) {
            // 无 arbiter 时回退到主库
            $this->xlink = $this->wlink;
            return $this->xlink;
        }
        $cfg = $this->conf['arbiter'];
        $this->xlink = $this->connect(...);
        return $this->xlink;
    }
}
```

| 连接 | 来源 | 用途 | 数据一致性 | 何时创建 |
|------|------|------|-----------|---------|
| `wlink` | `$_ENV['_config']['db']['master']` | **写操作**：INSERT、UPDATE、DELETE | 强一致（主库） | 首次访问 `$this->db->wlink` 时 |
| `rlink` | `$_ENV['_config']['db']['slaves'][随机]` | **读操作**：SELECT（不需要实时性） | 最终一致（从库） | 首次访问 `$this->db->rlink` 时 |
| `xlink` | `$_ENV['_config']['db']['arbiter']` | **统计查询**：maxid、count | 强一致（arbiter） | 首次访问 `$this->db->xlink` 时 |

> **arbiter 节点的作用**:
> - maxid/count 等统计查询需要实时数据，不能从可能延迟的从库读取
> - arbiter 通常是主库的只读副本，延迟极低
> - 如果没有 arbiter，xlink 回退到 wlink（主库）

**DB 配置格式示例**（`_config.php`）:

```php
$_ENV['_config']['db'] = array(
    'type' => 'pdo_mysql',
    'tablepre' => 'lecms_',
    'master' => array(
        'host' => '127.0.0.1',
        'port' => '3306',
        'user' => 'root',
        'password' => 'password',
        'name' => 'lecms_db',
        'tablepre' => 'lecms_',
    ),
    'slaves' => array(
        array(
            'host' => '127.0.0.1',
            'port' => '3307',
            'user' => 'root',
            'password' => 'password',
            'name' => 'lecms_db',
            'tablepre' => 'lecms_',
        ),
    ),
    'arbiter' => array(
        'host' => '127.0.0.1',
        'port' => '3306',
        'user' => 'reader',
        'password' => 'password',
        'name' => 'lecms_db',
        'tablepre' => 'lecms_',
    ),
);
```

---

### 17.4 键名系统（Key Naming）

> **源码位置**: `model.class.php` 第 422-450 行、第 573-614 行

LeCMS 的数据库使用 **Key-Value 存储模型**（键值对存储），所有的 CRUD 操作都通过 Key 进行。Key 的格式是框架设计的核心规范：

#### 17.4.1 Key 格式

| Key 类型 | 格式 | 示例 | 来源方法 |
|----------|------|------|---------|
| 数据 Key | `{table}-{pri1}-{val1}[-{pri2}-{val2}...]` | `user-uid-1` | `pri2key()`、`arr2key()` |
| maxid Key | `{table}-{maxid_field}` | `user-uid` | `cache_db_maxid()` |
| count Key | `{table}` | `user` | `cache_db_count()` |

**`pri2key()`（关联数组 → Key）**（源码第 422-428 行）:

```php
public function pri2key($arr) {
    $s = $this->table;
    foreach($this->pri as $v) {
        $s .= "-$v-".$arr[$v];
    }
    return $s;
    // 示例: $this->table='user', $this->pri=['uid']
    //        $arr=['uid'=>1, 'username'=>'test']
    //        → "user-uid-1"
}
```

**`arr2key()`（数字索引数组 → Key）**（源码第 435-450 行）:

```php
public function arr2key($arr) {
    $arr = (array)$arr;
    $s = $this->table;
    foreach($this->pri as $k=>$v) {
        if(!isset($arr[$k])) {
            // 主键值数量不匹配: 抛出异常
            throw new Exception('非法键名数组...');
        }
        $s .= "-$v-".$arr[$k];
    }
    return $s;
    // 示例: $this->table='user', $this->pri=['uid', 'id']
    //        $arr=[1, 2]
    //        → "user-uid-1-id-2"
}
```

> **Key 设计的思考**:
> - 不依赖自增 ID 作为唯一标识，支持联合主键
> - Key 本身包含表名和主键值，全局唯一
> - 不同表之间的 Key 不会冲突
> - Redis 的 String 类型天然适配此格式

#### 17.4.2 L2 Key 格式

二级缓存（L2）的 Key 用于缓存查询结果（key 列表），格式如下:

```php
$l2_key = $table.'_'.md5(serialize(array($pri, $where, $order, $start, $limit)));
// 示例: "user_8a3b2c1d4e5f6a7b8c9d0e1f2a3b4c5d"
```

> L2 Key 使用 MD5 哈希，因为查询条件的序列化结果可能很长，不适合直接作为 Key 存储。

---

### 17.5 Where 条件构建器

> **源码位置**: `lecms/xiunophp/db/db_mysqli.class.php`、`db_pdo_mysql.class.php`

DB 驱动的 `where` 条件支持以下操作符：

| 操作符 | 语法 | SQL 生成示例 | 说明 |
|--------|------|------------|------|
| `=` | `array('uid' => 1)` | `WHERE uid = 1` | 默认操作符（不写操作符） |
| `>` | `array('uid' => array('>' => 1))` | `WHERE uid > 1` | 大于 |
| `<` | `array('uid' => array('<' => 1))` | `WHERE uid < 1` | 小于 |
| `>=` | `array('uid' => array('>=' => 1))` | `WHERE uid >= 1` | 大于等于 |
| `<=` | `array('uid' => array('<=' => 1))` | `WHERE uid <= 1` | 小于等于 |
| `!=` | `array('uid' => array('!=' => 1))` | `WHERE uid != 1` | 不等于 |
| `LIKE` | `array('title' => array('LIKE' => '%test%'))` | `WHERE title LIKE '%test%'` | 模糊匹配 |
| `IN` | `array('uid' => array('IN' => array(1,2,3)))` | `WHERE uid IN (1,2,3)` | 包含于 |
| `NOT IN` | `array('uid' => array('NOT IN' => array(1,2,3)))` | `WHERE uid NOT IN (1,2,3)` | 不包含于 |
| `BETWEEN` | `array('dateline' => array('BETWEEN' => array(0, 100)))` | `WHERE dateline BETWEEN 0 AND 100` | 区间 |
| `FIND_IN_SET` | `array('tags' => array('FIND_IN_SET' => 'news'))` | `WHERE FIND_IN_SET('news', tags)` | 集合查找 |

**组合条件示例**:

```php
// 查询 uid=1 且 dateline 在 1000-2000 之间且 status=1 的文章
$where = array(
    'AND' => array(
        'uid' => 1,
        'status' => 1,
        'dateline' => array('BETWEEN' => array(1000, 2000)),
    ),
);
// WHERE uid = 1 AND status = 1 AND dateline BETWEEN 1000 AND 2000
```

> **注意**: 这里的 WHERE 构建是 DB 层的方法（如 `find_fetch`）的功能，Model 层的 `find_fetch` 直接将 `$where` 传递给 DB 层处理。

---

### 17.6 读写分离

**流量分配规则**:

```
写操作（INSERT/UPDATE/DELETE）
    └── wlink ──→ master（主库）
         │
         └── PDO: 强一致，实时写入

读操作（SELECT，不需要实时性）
    └── rlink ──→ slaves[]（随机选择一个从库）
         │
         └── PDO: 最终一致，可能有延迟

统计查询（maxid/count）
    └── xlink ──→ arbiter（仲裁节点）
         │
         └── PDO: 强一致，低延迟
```

> **Model 层不直接操作连接**: Model 层通过 `$this->db->get()`、`$this->db->set()` 等 db_interface 方法与 DB 层交互，DB 层内部根据操作类型自动选择 wlink/rlink/xlink。

---

## 18. Model 二级缓存机制

> **源码位置**: `model.class.php` 第 473-660 行、`cache.interface.php` 第 12-14 行
>
> 文件头注释（第 1-3 行）:
> ```
> 统一 cache+db 接口，并设计了二级缓存，进一步减轻数据库压力。
> ```

### 18.1 三级缓存总览

LeCMS 的 Model 层设计了**三级缓存体系**，每一级的速度递增，成本递增:

```
读取流程:
┌─────────────────────────────────────────────────────────────────┐
│ 第 1 级: $unique（内存数组）                                     │
│   └── 速度: ~0μs（PHP 数组直接访问）                             │
│   └── 生命周期: 当前请求                                          │
└─────────────────────────────────────────────────────────────────┘
           │ 未命中
           ▼
┌─────────────────────────────────────────────────────────────────┐
│ 第 2 级: Cache（Redis/File/Memcache）                            │
│   └── 速度: ~0.5ms（网络/IO）                                    │
│   └── 生命周期: 可配置（$cache_life）                              │
└─────────────────────────────────────────────────────────────────┘
           │ 未命中
           ▼
┌─────────────────────────────────────────────────────────────────┐
│ 第 3 级: Database（MySQL）                                       │
│   └── 速度: ~1ms（磁盘/网络）                                    │
│   └── 生命周期: 永久                                              │
└─────────────────────────────────────────────────────────────────┘
           │ 命中
           ▼
    ┌──────────────┐
    │ 回填 L2 Cache │ → 写入 Redis/File
    └──────────────┘
           │
    ┌──────────────┐
    │ 回填 L1 Cache │ → 写入 $unique（内存数组）
    └──────────────┘

写入流程:
┌─────────────────────────────────────────────────────────────────┐
│ 1. 写入 Database（MySQL）                                        │
│ 2. 写入 Cache（Redis/File/Memcache）                              │
│ 3. 写入内存（$unique）                                            │
└─────────────────────────────────────────────────────────────────┘
```

> **Cache + DB 双写原则**（文件头第 5-6 行）:
> ```
> 开启 cache 时：
> 1、读取：先读cache，缓存没有时读db，并写入cache。
> 2、写入：同时写入 cache 和 db。
> ```

---

### 18.2 L1 内存缓存（`$unique`）

> **源码位置**: `model.class.php` 第 44 行、第 190-196 行、第 205-218 行

L1 缓存即 `$unique` 数组（详见 16.4），是整个缓存体系中最快的一级:

```
特性:
├── 类型: PHP 关联数组
├── Key: {table}-{pri1}-{val1}-{pri2}-{val2}...
├── 生命周期: 当前 PHP 请求（随请求销毁自动释放）
├── 序列化: 无（直接存储 PHP 变量）
├── 过期机制: 无
└── 适用场景: 同一请求中多次访问同一行数据
```

**实际应用场景**:

```php
// 场景: 文章列表页，每篇文章需要读取作者信息
$articles = $article->find_fetch($where, $order, $start, $limit);
// ↑ find_fetch 内部调用 mget，mget 会自动缓存到 $unique

foreach($articles as $article) {
    $author = $user->get($article['uid']);
    // ↑ get() 会先查 $unique，因为是第一次访问所以走到 L2/DB
}

// 场景: 文章详情页，需要读取文章本身 + 分类 + 标签
$article = $article->get($cid);
// ↑ 第一次 get()，走到 L2/DB，写入 $unique

$category = $category->get($article['cid']);
// ↑ 分类的 get()，与文章无关

// 后续如果有其他逻辑需要读取同一篇文章:
$article2 = $article->get($cid);
// ↑ 第二次 get()，$unique 命中，直接返回内存数据（~0μs）
```

---

### 18.3 L2 二级缓存（`l2_cache_get/set`）

> **源码位置**: `model.class.php` 第 648-660 行、`cache.interface.php` 第 12-14 行

L2 二级缓存（Level 2 Cache）是 Model 层特有的缓存机制，用于缓存**条件查询的 Key 列表**，避免每次条件查询都去 DB 查 Key:

```
使用场景:
find_fetch($where, ...)
    ├── 内部调用 cache_db_find_fetch($table, $pri, $where, ...)
    │       ├── 调用 cache_db_find_fetch_key($table, $pri, $where, ...)
    │       │       ├── L2 缓存: l2_cache_get($l2_key)
    │       │       │       └── 返回: array('user-uid-1', 'user-uid-2', ...)
    │       │       ├── 未命中: DB find_fetch_key()
    │       │       │       └── 返回: array('user-uid-1', 'user-uid-2', ...)
    │       │       └── L2 写入: l2_cache_set($l2_key, $keys)
    │       └── 得到 Key 列表
    │           └── cache_db_multi_get($keys)
    │               ├── L1 缓存: 逐条查 $unique
    │               ├── L2 缓存: 逐条查 Cache
    │               └── 未命中: DB get()
    └── 返回: array('user-uid-1' => array(...), 'user-uid-2' => array(...), ...)
```

**L2 缓存源码**（`cache_db_find_fetch_key`，第 648-660 行）:

```php
public function cache_db_find_fetch_key($table, $pri, $where, $order, $start, $limit, $life) {
    if($this->cache_conf['enable'] && $this->cache_conf['l2_cache'] === 1) {
        // L2 缓存开启
        $key = $table.'_'.md5(serialize(array($pri, $where, $order, $start, $limit)));
        // 生成 L2 Key: 表名_md5(主键+条件+排序+分页)
        $keys = $this->cache->l2_cache_get($key);
        // L2 读取
        if(empty($keys)) {
            // L2 未命中: 从 DB 读取 Key 列表
            $keys = $this->db->find_fetch_key($table, $pri, $where, $order, $start, $limit);
            // L2 写入
            $this->cache->l2_cache_set($key, $keys, $life);
        }
    } else {
        // L2 缓存未开启: 直接从 DB 读取 Key 列表
        $keys = $this->db->find_fetch_key($table, $pri, $where, $order, $start, $limit);
    }
    return $keys;
}
```

**`l2_cache_get/set` 接口定义**（`cache_interface`）:

```php
interface cache_interface {
    // ... 其他方法

    public function l2_cache_get($l2_key);
    // 读取 L2 缓存（二级缓存）
    // $l2_key: L2 Key（md5 哈希后的查询条件）

    public function l2_cache_set($l2_key, $keys, $life = 0);
    // 写入 L2 缓存
    // $keys: Key 列表（array('user-uid-1', 'user-uid-2', ...）)
    // $life: 过期时间（0=永久）
}
```

> **L2 缓存的 Key 设计逻辑**:
> - L2 Key = `{table}_` + `md5(serialize([$pri, $where, $order, $start, $limit]))`
> - md5 哈希的原因: 查询条件序列化后可能很长，不适合直接作为 Key
> - 不同查询条件（哪怕只差一个排序方式）会产生完全不同的 L2 Key

**L2 缓存的开启方式**（配置文件）:

```php
// _config.php 中 cache 配置
$_ENV['_config']['cache'] = array(
    'enable' => 1,      // 启用缓存
    'l2_cache' => 1,    // 启用 L2 二级缓存（关闭则只用 L1 + DB）
    'type' => 'redis',  // 缓存类型
    'redis' => array(
        'host' => '127.0.0.1',
        'port' => '6379',
    ),
);
```

---

### 18.4 缓存辅助方法

> **源码位置**: `model.class.php` 第 473-614 行

Model 基类内部封装了 **10 个 `cache_db_*` 系列方法**，这些方法是 Cache + DB 双层写入/读取的核心:

| 方法 | 读取流程 | 写入流程 | 源码行 |
|------|----------|----------|-------|
| `cache_db_get()` | L2 → DB → 写 L2 | — | 473-484 |
| `cache_db_multi_get()` | L2 → L1 → DB → 写 L2 | — | 491-511 |
| `cache_db_set()` | — | 写 L2 + 写 DB | 520-523 |
| `cache_db_update()` | — | 更新 L2 + 更新 DB | 532-535 |
| `cache_db_delete()` | — | 删除 L2 + 删除 DB | 554-557 |
| `cache_db_truncate()` | — | 清空 L2 + 清空 DB | 563-566 |
| `cache_db_maxid()` | L2 → DB → 写 L2 | 写 DB + 写 L2 | 573-590 |
| `cache_db_count()` | L2 → DB → 写 L2 | 写 DB + 写 L2 | 597-614 |
| `cache_db_find_fetch()` | L2(Key) → L1+DB(数据) | — | 627-635 |
| `cache_db_find_fetch_key()` | L2(L2) → DB | 写 L2(L2) | 648-660 |
| `cache_db_update_views()` | — | 删除 L2 + 更新 DB | 544-547 |

**`cache_db_get()` 完整流程**（源码第 473-484 行）:

```php
public function cache_db_get($key) {
    if($this->cache_conf['enable']) {
        // 1. 读 L2 Cache
        $data = $this->cache->get($key);
        if(empty($data)) {
            // 2. L2 未命中: 读 DB
            $data = $this->db->get($key);
            // 3. DB 命中: 回写 L2 Cache
            if($data !== NULL) {
                $this->cache->set($key, $data);
            }
        }
        return $data;
    } else {
        // 缓存未开启: 直接读 DB
        return $this->db->get($key);
    }
}
```

**`cache_db_maxid()` 读取/设置分离逻辑**（源码第 573-590 行）:

```php
public function cache_db_maxid($val = FALSE) {
    $key = $this->table.'-'.$this->maxid; // Key: 表名-字段名
    if($this->cache_conf['enable']) {
        if($val === FALSE) {
            // 读取模式: 先读 L2，未命中读 DB，回写 L2
            $maxid = $this->cache->maxid($key, $val);
            if(empty($maxid)) {
                $maxid = $this->db->maxid($key, $val);
                $this->cache->maxid($key, $maxid);
            }
            return $maxid;
        } else {
            // 设置模式: 先写 DB，再写 L2
            $maxid = $this->db->maxid($key, $val);
            return $this->cache->maxid($key, $maxid);
        }
    } else {
        return $this->db->maxid($key, $val);
    }
}
```

> **maxid/count 的 Key 设计**: `{table}-{field}` 而不是 `{table}-{value}`，因为 maxid/count 是表级别的统计值，不需要区分具体哪一行。

---

## 19. Controller 层

> **源码位置**: `lecms/xiunophp/lib/control.class.php`（50 行）

### 19.1 基类继承链

LeCMS 的 Controller 层采用**单继承 + Hook 扩展**的模式:

```
继承链:
control（xiunophp 基类）
    ├── base_control（前台共用控制器）
    │       ├── home_base_control（前台模块基类，如有）
    │       └── 具体业务控制器（如 article_control、page_control）
    │
    └── 后台共用控制器（如有 base_admin_control）
            └── 具体后台控制器
```

**`control` 基类**（`lecms/xiunophp/lib/control.class.php`）:

```php
class control {
    public function __get($var) {
        if($var == 'view') {
            // 访问 view: 创建 view 实例（单例）
            return $this->view = new view();
        } elseif($var == 'db') {
            // 访问 db: 创建 db 实例（用于调试，不推荐在 Controller 中操作 DB）
            $db = 'db_'.$_ENV['_config']['db']['type'];
            return $this->db = new $db($_ENV['_config']['db']);
        } else {
            // 访问其他属性: 加载对应的 model
            return $this->$var = core::model($var);
        }
    }

    public function assign($k, &$v) { $this->view->assign($k, $v); }
    public function assign_value($k, $v) { $this->view->assign_value($k, $v); }
    public function display($filename = null) { $this->view->display($filename); }
    public function message($status, $message, $jumpurl = '', $delay = 2, $sys_message_file = '');
    public function __call($method, $args);
}
```

**`base_control` 基类**（`lecms/control/base_control.class.php`）:

```php
class base_control extends control {
    // ==================== 用户相关属性 ====================
    public $_cfg = array();          // 全站运行时配置
    public $_var = array();          // 各模块页参数

    public $_user = array();         // 登录用户信息
    public $_uid = 0;               // 用户 ID
    public $_group = array();        // 用户组
    public $_user_avatar = '';       // 用户头像
    public $_author = '';            // 用户昵称

    // ==================== URL 相关属性 ====================
    public $_login_url = '';         // 登录链接
    public $_register_url = '';      // 注册链接
    public $_my_url = '';            // 个人中心链接
    public $_logout_url = '';        // 退出登录链接
    public $_search_url = '';        // 搜索页面链接
    public $_current_url = '';       // 当前页面 URL

    // ==================== 请求相关属性 ====================
    public $_parseurl = 0;           // 是否开启 URL 伪静态
    public $_control = 0;            // 当前访问的控制器
    public $_action = 0;             // 当前访问的方法函数

    function __construct() {
        // 1. 加载运行时配置
        $this->_cfg = $this->runtime->xget();

        // hook base_control_construct_before.php

        // 2. 获取登录用户信息（如果开启用户功能）
        if(isset($this->_cfg['open_user']) && !empty($this->_cfg['open_user'])) {
            $r = $this->user->user_token_check(0);
            if($r['err'] == 0 && isset($r['user']) && $r['user_group']) {
                $this->_uid = $r['user']['uid'];
                $this->_user = $r['user'];
                $this->user->format($this->_user);
                $this->_group = $r['user_group'];
                // hook base_control_get_user_success.php
            } else {
                // hook base_control_get_user_failed.php
            }
        }

        // 3. 初始化 URL 模板变量
        // hook base_control_get_user_after.php

        // 4. 初始化请求参数
        // hook base_control_variable_after.php

        // 5. 站点关闭检查
        $this->close_website();

        // hook base_control_construct_after.php
    }
}
```

> **base_control 的设计意义**:
> - 在 `__construct()` 中自动处理用户认证、URL 模板变量等通用逻辑
> - 具体业务控制器（如 `article_control`）无需重复处理这些逻辑
> - 所有模板变量通过 `assign()` 提前注入，业务控制器可以直接使用

### 19.2 `__get` 懒加载

`control.__get` 实现了 **3 种属性的懒加载**（同 Model 的 `__get` 思路）:

| 访问方式 | 触发路径 | 返回对象 | 说明 |
|----------|----------|----------|------|
| `$this->view` | `__get('view')` → `new view()` | view 实例 | 模板引擎实例（单例）。 |
| `$this->db` | `__get('db')` → `new db_{type}()` | db 实例 | 数据库连接（调试用）。**不推荐**在 Controller 中操作 DB，应通过 Model。 |
| `$this->user` | `__get('user')` → `core::model('user')` | user_model | 自动加载 `user_model.class.php`。 |

### 19.3 模板变量管理

| 方法 | 签名 | 传递方式 | 说明 |
|------|------|---------|------|
| `assign()` | `assign(string $k, &$v)` | **引用传递** | 模板变量（引用）。修改变量会同步到模板。用于大数据量对象（如数组）。 |
| `assign_value()` | `assign_value(string $k, $v)` | **值传递** | 模板变量（值）。复制一份给模板。用于基本类型（字符串、数字）。 |

> **引用 vs 值传递的区别**:
> ```php
> // assign（引用）
> $arr = array('title' => 'test');
> $this->assign('data', $arr);
> $arr['title'] = 'changed'; // 模板中 {{data.title}} 输出 'changed'
>
> // assign_value（值）
> $str = 'test';
> $this->assign_value('data', $str);
> $str = 'changed'; // 模板中 {{data}} 输出 'test'（不受影响）
> ```

### 19.4 `message()` 消息提示

**源码位置**: `control.class.php` 第 26-40 行

```php
public function message($status, $message, $jumpurl = '', $delay = 2, $sys_message_file = '') {
    if(R('ajax', 'R')) {
        // AJAX 请求: 返回 JSON
        echo json_encode(array(
            'status' => $status,
            'message' => $message,
            'jumpurl' => $jumpurl,
            'delay' => $delay,
        ));
    } else {
        // 普通请求: 渲染消息模板
        if(empty($jumpurl)) {
            $jumpurl = empty($_SERVER['HTTP_REFERER']) ? '' : $_SERVER['HTTP_REFERER'];
        }
        if($sys_message_file && is_file($sys_message_file)) {
            include $sys_message_file;
        } else {
            include FRAMEWORK_PATH.'tpl/sys_message.php';
        }
    }
    exit; // 终止请求
}
```

| 参数 | 类型 | 默认值 | 说明 |
|------|------|--------|------|
| `$status` | int | 必填 | 状态码（1=成功，0=失败） |
| `$message` | string | 必填 | 消息内容 |
| `$jumpurl` | string | '' | 跳转 URL（空时取 HTTP_REFERER） |
| `$delay` | int | 2 | 跳转延迟（秒） |
| `$sys_message_file` | string | '' | 自定义消息模板路径 |

> **AJAX 检测方式**: `R('ajax', 'R')` 读取 `$_GET['ajax']`、`$_POST['ajax']` 或 `$_SERVER['HTTP_X_REQUESTED_WITH']`。

### 19.5 Hook 扩展点

`base_control` 中内置了 **8 个 Hook 点**，可通过插件系统扩展:

```
base_control Hook 点列表:
├── hook base_control_start.php               ← 文件头部
├── hook base_control_construct_before.php    ← __construct 开始前
├── hook base_control_get_user_before.php     ← 获取用户信息前
├── hook base_control_get_user_success.php    ← 获取用户信息成功后
├── hook base_control_get_user_failed.php     ← 获取用户信息失败后
├── hook base_control_get_user_after.php      ← 获取用户信息完成后
├── hook base_control_construct_after.php     ← __construct 完成后
├── hook base_control_close_website_before.php ← 站点关闭前
└── hook base_control_close_website_after.php  ← 站点关闭后
```

**Hook 调用位置示例**（`close_website` 方法）:

```php
protected function close_website(){
    // hook base_control_close_website_before.php
    if(isset($this->_cfg['close_website']) && !empty($this->_cfg['close_website'])){
        $this->_cfg['titles'] = empty($this->_cfg['seo_title']) ? $this->_cfg['webname'] : $this->_cfg['seo_title'];
        // hook base_control_construct_close_website_after.php
        $this->display($tpl);
        exit();
    }
}
```

---

## 20. View 层

> **源码位置**: `lecms/xiunophp/lib/view.class.php`（257 行）

### 20.1 模板解析流程

View 层的核心流程是 **模板编译 → 缓存 → 执行**:

```
模板解析总流程:
┌─────────────────────────────────────────────────────────────────┐
│ 1. display($filename)                                           │
│   ├── 确定模板名: {control}_{action}.htm                        │
│   ├── extract($vars) 提取模板变量                               │
│   ├── get_tplfile() 获取编译后模板路径                          │
│   └── include 编译后的 PHP 文件                                  │
└─────────────────────────────────────────────────────────────────┘
           │
           ▼
┌─────────────────────────────────────────────────────────────────┐
│ 2. get_tplfile() — 模板缓存管理                                 │
│   ├── 已编译 + 非 DEBUG → 直接返回缓存文件                      │
│   ├── 未编译 / DEBUG → 重新编译                                 │
│   │   ├── 读取原始模板文件 (.htm)                                │
│   │   ├── tpl_process() 编译（9 步）                              │
│   │   └── 写入缓存文件 (RUNTIME_PATH/{app}_view/{theme},{tpl}.php)│
│   └── 返回编译后的 PHP 文件路径                                  │
└─────────────────────────────────────────────────────────────────┘
```

**缓存路径格式**:

```php
// RUNTIME_PATH = LeCMS 运行目录（通常为 LeCMS/LeCMS_runtime/）
$php_file = RUNTIME_PATH
    . APP_NAME . '_view/'       // 应用名_view/
    . $_ENV['_theme'] . ','     // 主题名,
    . $tplname . '.php';         // {control}_{action}.php

// 示例:
// LeCMS/LeCMS_runtime/lecms_view/default,article_index.htm.php
```

**DEBUG 模式对编译的影响**:

```php
if(!is_file($php_file) || DEBUG) {
    // DEBUG 模式: 每次请求都重新编译模板
    // 非 DEBUG 模式: 仅在模板不存在时编译一次
    $tpl_file = core::get_original_file($filename, VIEW_PATH.$_ENV['_theme'].'/');
    // 读取 .htm 原始模板
    if($tpl_file && FW($php_file, $this->tpl_process($tpl_file)) === false && DEBUG) {
        throw new Exception('write_tpl_file_failed');
    }
}
```

### 20.2 9 步编译流程详解

> **源码位置**: `view.class.php` 第 55-106 行（`tpl_process()` 方法）

```php
private function tpl_process($tpl_file) {
    $s = file_get_contents($tpl_file); // 读取原始模板

    // ============================================================
    // 第 1 步: {inc:xxx} — 子模板包含
    // ============================================================
    $s = preg_replace_callback('#\{inc\:([\w|\/\.]+)\}#', array($this, 'process_inc'), $s);

    // ============================================================
    // 第 2 步: {hook:xxx} — Hook 标签替换
    // ============================================================
    $s = preg_replace_callback('#\{hook\:([\w\.]+)\}#', array('core', 'process_hook'), $s);

    // ============================================================
    // 第 3 步: {php}...{/php} — PHP 代码块
    // ============================================================
    $s = preg_replace('#(?:\<\?.*?\?\>|\<\?.*)#s', '', $s); // 清除原生 PHP 语法
    $s = preg_replace('#\{php\}(.*?)\{\/php\}#s', '<?php \\1 ?>', $s); // 规范 PHP 代码块

    // ============================================================
    // 第 4 步: {block:xxx} — Block 标签
    // ============================================================
    $s = preg_replace_callback('#\{block\:([a-zA-Z_]\w*)\040?([^\n\}]*?)\}(.*?){\/block}#s',
        array($this, 'process_block'), $s);

    // ============================================================
    // 第 5 步: {loop:xxx} — 循环语句
    // ============================================================
    while(preg_match('#\{loop\:\$'.$reg_arr.'(?:\040\$[a-zA-Z_]\w*){1,2}\}.*?\{\/loop\}#s', $s))
        $s = preg_replace_callback('#\{loop\:(\$'.$reg_arr.'(?:\040\$[a-zA-Z_]\w*){1,2})\}(.*?)\{\/loop\}#s',
            array($this, 'process_loop'), $s);

    // ============================================================
    // 第 6 步: {if:xxx} — 条件判断
    // ============================================================
    while(preg_match('#\{if\:[^\n\}]+\}.*?\{\/if\}#s', $s))
        $s = preg_replace_callback('#\{if\:([^\n\}]+)\}(.*?)\{\/if\}#s',
            array($this, 'process_if'), $s);

    // ============================================================
    // 第 7 步: {$xxx} — 变量输出
    // ============================================================
    $s = preg_replace('#\{\@([^\}]+)\}#', '<?php echo(\\1); ?>', $s); // 运算输出
    $s = preg_replace_callback('#\{(\$'.$reg_arr.')\}#', array($this, 'process_vars'), $s);

    // ============================================================
    // 第 8 步: {lang:xxx} — 语言包替换
    // ============================================================
    $s = preg_replace_callback('#\{lang\:([\w\.]+)\}#', array($this, 'process_lang'), $s);

    // ============================================================
    // 第 9 步: 代码压缩 + 安全头
    // ============================================================
    if(defined('CODE_COMPRESS') && CODE_COMPRESS == 1 && DEBUG == 0) {
        $s = str_replace(array("\r\n", "\n", "\t"), '', $s);
        $s = preg_replace("/\s(?=\s)/","\\1",$s);
        $s = str_replace("> <","><",$s);
    }
    $head_str = empty($this->head_arr) ? '' : implode("\r\n", $this->head_arr);
    $s = "<?php defined('APP_NAME') || exit('Access Denied'); $head_str\r\n?>$s";
    $s = str_replace('?><?php ', '', $s);

    return $s;
}
```

#### 20.2.1 第 1 步: 子模板包含（`process_inc`）

**源码位置**: `view.class.php` 第 127-148 行

```php
private function process_inc($matches) {
    if(strpos($matches[1], '/') == false) {
        $filename = 'inc-'.$matches[1];          // {inc:header} → inc-header.htm
    } else {
        $arr = explode('/', $matches[1]);
        $filename = $arr[0].'/inc-'.$arr[1];    // {inc:user/header} → user/inc-header.htm
    }

    $tpl_file = core::get_original_file($filename, VIEW_PATH.$_ENV['_theme'].'/');
    if(!$tpl_file) return ''; // 模板不存在时返回空字符串（非 DEBUG 模式）
    return file_get_contents($tpl_file); // 直接返回子模板内容（嵌入到父模板中）
}
```

| 模板语法 | 编译结果 | 说明 |
|---------|---------|------|
| `{inc:header}` | 读取 `inc-header.htm` 的内容嵌入 | 子模板内容直接替换标签 |
| `{inc:user/header}` | 读取 `user/inc-header.htm` 的内容嵌入 | 支持子目录 |

> **与第 4 步 Block 的区别**: `{inc}` 是简单的文件包含（直接嵌入子模板内容），而 `{block}` 是函数调用（先执行 block 函数再嵌入结果）。

#### 20.2.2 第 2 步: Hook 标签（`process_hook`）

**源码位置**: `core.class.php`（`core::process_hook`）

```php
// 模板中的 Hook 标签
{hook:my_hook_point}

// 编译时: 查找 hooks/my_hook_point.php 文件内容，直接替换标签
// 如果没有 Hook 文件: 标签被替换为空字符串
```

> **Hook 的两种模式**:
> - 编译时 Hook（模板中的 `{hook:xxx}`）: 在模板编译时替换，每次请求都执行
> - 运行时 Hook（控制器中的 `hook_xxx.php`）: 在请求处理时执行

#### 20.2.3 第 3 步: PHP 代码块

```php
// 模板语法
{php}
    $sum = 0;
    foreach($list as $v) {
        $sum += $v['num'];
    }
{/php}

// 编译结果
<?php
    $sum = 0;
    foreach($list as $v) {
        $sum += $v['num'];
    }
?>
```

> **安全措施**（源码第 68 行）: 模板中的原生 `<?php ... ?>` 语法被清除，只能通过 `{php}...{/php}` 规范写法使用。

#### 20.2.4 第 4 步: Block 标签（`process_block`）

**源码位置**: `view.class.php` 第 164-211 行

```php
// 模板语法
{block:article_list title="最新文章" num="5"}
    <h3>{$title}</h3>
    <ul>
    {loop:$list $v}
        <li><a href="{$v.url}">{$v.title}</a></li>
    {/loop}
    </ul>
{/block}

// 编译流程:
// 1. 读取 block 函数库文件: block_article_list.lib.php
// 2. 将 block 函数代码放到模板头部（避免重复 IO）
// 3. 解析配置参数: title="最新文章" num="5" → array('title' => '最新文章', 'num' => '5')
// 4. 生成函数调用: block_article_list(array('title' => '最新文章', 'num' => '5'));
// 5. 嵌入 Block 内容
```

**编译后的 PHP 代码**:

```php
<?php $data = block_article_list(array('title' => '最新文章', 'num' => '5')); ?>
<h3>{$title}</h3>
<ul>
<?php if(isset($list) && is_array($list)) { foreach($list as $k&$v) { ?>
    <li><a href="<?php echo(isset($vars) ? $vars : ''); ?>"><?php echo(isset($vars) ? $vars : ''); ?></a></li>
<?php }} ?>
</ul>
<?php unset($data); ?>
```

**公共 Block（`global_` 前缀）的编译差异**:

```php
// 公共 Block: 函数调用放到模板头部
if(substr($func, 0, 7) == 'global_') {
    $this->head_arr[$func] = '$gdata = '.$func_str;
}
// → 模板头部: $gdata = block_global_xxx(array(...));
```

**DIY 模板支持**（`_view_diy` 模式）:

```php
if($_ENV['_view_diy']) {
    $this->block_id++;
    $before .= '<span block_diy="before" block_id="'.$this->block_id.'"></span>';
    $after .= '<span block_diy="after" block_id="'.$this->block_id.'"></span>';
}
// → 编译结果中插入 DIY 标记，用于可视化编辑器的定位
```

#### 20.2.5 第 5 步: 循环语句（`process_loop`）

**源码位置**: `view.class.php` 第 214-222 行

```php
// 模板语法
{loop:$list $item $key}
    <p>{$key}: {$item.title}</p>
{/loop}

// 编译结果
<?php if(isset($list) && is_array($list)) { foreach($list as $k&$v) { ?>
    <p><?php echo(isset($vars) ? $vars : ''); ?></p>
<?php }} ?>
```

| 参数 | 默认值 | 说明 |
|------|--------|------|
| 第 1 个变量 | `$v` | 值变量 |
| 第 2 个变量 | 无 | 键变量（`$k&` 表示同时输出键） |

**严格格式要求**:

```
{loop:$arr[a] $v $k}
```

> **不支持** `{$arr[$a]}` 的写法，必须使用 `$arr[a]` 形式（空格分隔）。

#### 20.2.6 第 6 步: 条件判断（`process_if`）

**源码位置**: `view.class.php` 第 224-236 行

```php
// 模板语法
{if:$user.uid > 0}
    <p>欢迎, {$user.username}</p>
{elseif:$user.group == 1}
    <p>管理员模式</p>
{else}
    <p>请登录</p>
{/if}

// 编译结果
<?php if ($expr) { ?>
    <p><?php echo(isset($vars) ? $vars : ''); ?></p>
<?php }elseif($expr) { ?>
    <p>管理员模式</p>
<?php }else{ ?>
    <p>请登录</p>
<?php } ?>
```

| 模板语法 | 编译结果 |
|---------|---------|
| `{if:expr}` | `<?php if ($expr) { ?>` |
| `{elseif:expr}` | `<?php }elseif($expr) { ?>` |
| `{else}` | `<?php }else{ ?>` |
| `{/if}` | `<?php } ?>` |

> **注意**: 条件判断编译使用 `while` 循环处理嵌套（同 `{loop}`），支持嵌套条件。

#### 20.2.7 第 7 步: 变量输出（`process_vars`）

**源码位置**: `view.class.php` 第 238-242 行

```php
// 模板语法
{$title}
{$user.username}
{$arr[a]['b']}
{$sum + 1}
{$list.0.title}

// 编译结果
<?php echo(isset($vars) ? $vars : ''); ?>
<?php echo(isset($vars) ? $vars : ''); ?>
<?php echo(isset($vars) ? $vars : ''); ?>
<?php echo(1); ?>
<?php echo(isset($vars) ? $vars : ''); ?>
```

**两种变量输出模式**:

| 语法 | 功能 | 安全机制 |
|------|------|---------|
| `{$xxx}` | 普通变量输出 | `isset($vars) ? $vars : ''` — 防止未定义变量报错 |
| `{@xxx}` | 运算输出 | `echo(xxx)` — 直接输出表达式结果 |

**`rep_vars()` 变量格式化**（源码第 250-255 行）:

```php
private function rep_vars($s) {
    $s = preg_replace('#\[(\w+)\]#', "['\\1']", $s);      // $arr[a] → $arr['a']
    $s = preg_replace('#\[\"(\w+)\"\]#', "['\\1']", $s);   // $arr["a"] → $arr['a']
    $s = preg_replace('#\[\'(\d+)\'\]#', '[\\1]', $s);    // $arr['0'] → $arr[0]
    return $s;
}
```

> **变量格式化的作用**: 将模板中的 `$arr[a]` 转换为合法的 PHP `$arr['a']`，避免解析错误。

#### 20.2.8 第 8 步: 语言包替换（`process_lang`）

**源码位置**: `view.class.php` 第 151-162 行

```php
// 模板语法
{lang:article_list_title}
{lang:comment.default_author}

// 编译结果
<?php echo '最新文章'; ?>
<?php echo '游客'; ?>
```

> **语言包 Key 的格式**: `模块.语言项`，点号表示层级（如 `comment.default_author`）。

#### 20.2.9 第 9 步: 安全头 + 代码压缩

**源码位置**: `view.class.php` 第 100-106 行

```php
// 第 9 步: 组合模板代码
$head_str = empty($this->head_arr) ? '' : implode("\r\n", $this->head_arr);
// head_arr 中存放: Block 函数定义、公共 Block 调用

$s = "<?php defined('APP_NAME') || exit('Access Denied'); $head_str\r\n?>$s";
// 安全头: defined('APP_NAME') || exit('Access Denied')
//   → 防止模板文件被直接访问（跨站包含保护）
//   → APP_NAME 在入口文件中 define，未定义时拒绝执行

$s = str_replace('?><?php ', '', $s);
// 去除 PHP 标签间的多余空格
```

**代码压缩**（DEBUG=0 且 CODE_COMPRESS=1 时）:

```php
if(defined('CODE_COMPRESS') && CODE_COMPRESS == 1 && DEBUG == 0) {
    $s = str_replace(array("\r\n", "\n", "\t"), '', $s); // 去除换行和 Tab
    $s = preg_replace("/\s(?=\s)/","\\1",$s);           // 压缩多个空格为一个
    $s = str_replace("> <","><",$s);                     // 压缩 HTML 标签间空格
}
```

> **代码压缩的作用**: 减少编译后 PHP 文件的大小，降低磁盘占用和 IO。

### 20.3 安全机制

View 层设计了 **3 层安全保护**:

#### 20.3.1 第 1 层: 访问保护（安全头）

```php
<?php defined('APP_NAME') || exit('Access Denied');
// 每份编译后的模板文件都以这行开头
// 如果直接访问编译后的 .php 文件（而非通过框架路由）:
//   APP_NAME 未定义 → 直接退出，拒绝执行
```

> **原理**: 只有通过 `index.php` 入口文件访问时，`APP_NAME` 才会被 `define()`。直接访问 `runtime/...php` 文件时 `APP_NAME` 未定义，触发退出。

#### 20.3.2 第 2 层: 变量未定义保护

```php
// 所有 {$xxx} 变量输出都包裹 isset 检查
<?php echo(isset($vars) ? $vars : ''); ?>
// 如果模板变量未通过 assign() 传递:
//   不报错，而是输出空字符串
```

#### 20.3.3 第 3 层: PHP 语法清除

```php
// 第 3 步: 清除原生 PHP 标签
$s = preg_replace('#(?:\<\?.*?\?\>|\<\?.*)#s', '', $s);
// 模板中的 <?php ... ?> 被清除，只能通过 {php}...{/php} 使用
// 防止恶意 PHP 代码注入
```

#### 20.3.4 第 4 层: Block 函数安全性

```php
// Block 函数库中的 Hook 标签被编译时替换
$lib_str = preg_replace_callback('#\t*\/\/\s*hook\s+([\w\.]+)[\r\n]#', array('core', 'process_hook'), $lib_str);
// Block 函数中的 // hook xxx 注释被替换为实际的 Hook 执行代码
```

#### 20.3.5 第 5 层: 模板文件路径限制

```php
// display() 方法的安全限制
public function display($filename = null) {
    $_ENV['_tplname'] = is_null($filename) ? $_GET['control'].'_'.$_GET['action'].'.htm' : $filename;
    // 模板名只能包含 (英文 数字 _ .)
    // 来源: view.class.php 注释: "为安全考虑，$filename 尽量限制为 (英文 数字 _ .)"
}
```

---

## 20.4 控制器基类职责详细分析

> **源码位置**: `lecms/xiunophp/lib/control.class.php`（50 行）

`control` 基类是 LeCMS 控制器层的最小实现，仅提供 50 行代码，但承担着整个控制器体系的枢纽作用：

```php
class control {
    public function __get($var) {
        if($var == 'view') {
            return $this->view = new view();           // 视图实例
        } elseif($var == 'db') {
            $db = 'db_'.$_ENV['_config']['db']['type'];
            return $this->db = new $db($_ENV['_config']['db']);  // 数据库实例
        } else {
            return $this->$var = core::model($var);   // 模型实例
        }
    }

    public function assign($k, &$v) { $this->view->assign($k, $v); }
    public function assign_value($k, $v) { $this->view->assign_value($k, $v); }
    public function display($filename = null) { $this->view->display($filename); }
    public function message($status, $message, $jumpurl = '', $delay = 2, $sys_message_file = '');
    public function __call($method, $args);
}
```

**职责矩阵**:

| 职责 | 实现方式 | 说明 |
|------|---------|------|
| **视图访问** | `__get('view')` → `new view()` | 创建视图引擎实例 |
| **数据库访问** | `__get('db')` → `new db_{type}()` | 创建数据库连接（调试用） |
| **模型加载** | `__get('other')` → `core::model('other')` | 自动加载并缓存模型 |
| **变量传递** | `assign()` / `assign_value()` | 委托给 view 类 |
| **模板渲染** | `display()` | 委托给 view 类 |
| **消息提示** | `message()` | AJAX/页面自适应 |
| **异常保护** | `__call()` | 未定义方法抛出异常 |

**与 base_control 的继承关系**:

```
control（xiunophp 基类，50 行）
  │  提供: view/db 懒加载、assign/display/message/__call
  │
  └── base_control（LeCMS 前台基类，147 行）
        │  继承 control 全部能力
        │  增加: 运行时配置加载、用户认证、URL 变量初始化
        │  增加: 8 个 Hook 扩展点（before/after 模式）
        │  增加: close_website() 站点关闭检查
        │
        ├── index_control ── 首页
        ├── cate_control ── 分类列表
        ├── show_control ── 内容详情
        └── ...（14 个具体控制器）
```

**base_control 的 8 个 Hook 点**:

```
base_control Hook 点列表:
├── hook base_control_start.php               ← 文件头部
├── hook base_control_construct_before.php    ← __construct 开始前
├── hook base_control_get_user_before.php     ← 获取用户信息前
├── hook base_control_get_user_success.php    ← 获取用户信息成功后
├── hook base_control_get_user_failed.php     ← 获取用户信息失败后
├── hook base_control_get_user_after.php      ← 获取用户信息完成后
├── hook base_control_construct_after.php     ← __construct 完成后
├── hook base_control_close_website_before.php ← 站点关闭前
└── hook base_control_close_website_after.php  ← 站点关闭后
```

## 20.5 视图层职责详细分析

> **源码位置**: `lecms/xiunophp/lib/view.class.php`（257 行）

`view` 类承担着模板从文本到 HTML 的完整转换流程，职责可归纳为 7 个方面：

| 职责 | 说明 | 关键方法 |
|------|------|---------|
| **变量上下文管理** | 管理模板变量，支持引用/值两种传递方式 | `assign()` / `assign_value()` |
| **模板编译** | 将 .htm 模板通过 9 步正则替换编译为 PHP 代码 | `tpl_process()` |
| **编译缓存** | 将编译结果写入 `runcache/lecms_view/{主题},{模板}.php` | `get_tplfile()` |
| **安全保护** | 4 层安全机制（安全头、isset 检查、PHP 标签清除、路径限制） | 第 9 步编译 |
| **代码压缩** | 生产模式下（DEBUG=0, CODE_COMPRESS=1）压缩输出 | 第 9 步 |
| **Block 头部管理** | 将 `global_` 前缀 Block 的函数调用放到模板头部 | `$this->head_arr` |
| **DIY 标记** | 在编译结果中插入可视化编辑器定位标记 | `_view_diy` 模式 |

**编译缓存路径格式**:

```php
$php_file = RUNTIME_PATH
    . APP_NAME . '_view/'       // lecms_view/
    . $_ENV['_theme'] . ','     // default,
    . $tplname . '.php';         // index_show.htm.php

// 实际路径示例:
// runcache/lecms_view/default,index_show.htm.php
```

## 20.6 模型基类职责详细分析

> **源码位置**: `lecms/xiunophp/lib/model.class.php`（662 行）

`model` 基类是 LeCMS 数据层的核心，承担着 ORM 映射、缓存协调和连接池管理三大职责：

| 职责 | 说明 | 对应方法/属性 |
|------|------|-------------|
| **表结构定义** | 子类通过 $table / $pri / $maxid 定义表结构 | `__construct()` |
| **ORM CRUD** | 统一的创建/读取/更新/删除接口 | `create()` / `get()` / `set()` / `update()` / `delete()` |
| **条件查询** | 支持 WHERE / ORDER / LIMIT 的复杂查询 | `find_fetch()` / `find_fetch_key()` |
| **批量操作** | 批量更新/删除，超过 2000 条时自动清空缓存 | `find_update()` / `find_delete()` |
| **统计查询** | 表级统计（maxid / count） | `maxid()` / `count()` |
| **L1 内存缓存** | 单请求周期内的 PHP 数组缓存，零序列化开销 | `$unique` 数组 |
| **L2 二级缓存** | 外部缓存（Redis/Memcache/File），可配置 TTL | `cache_db_*` 系列方法 |
| **连接池** | 数据库和缓存连接的单例管理 | `static $dbs` / `static $caches` |
| **键名系统** | 统一的 Key-Value 存储格式 | `pri2key()` / `arr2key()` |
| **模型懒加载** | 通过 `core::model()` 自动加载其他模型 | `__get('other_model')` |

**模型 API 签名模板（25 个公共方法）**:

```php
class model {
    // ==================== CRUD 方法 ====================
    public function create(array $data): mixed;                    // 插入，返回自增 ID
    public function set($key, array $data, int $life = 0): bool;   // 写入（自动判断 insert/update）
    public function get($arr): array;                              // 读取单条（支持简化调用）
    public function mget(array $arr): array;                       // 批量读取
    public function update(array $data, int $life = 0): bool;      // 更新
    public function delete($arg1, $arg2, $arg3, $arg4): bool;     // 删除（最多 4 个主键参数）
    public function del($arr): bool;                               // 删除（值数组写法）
    public function truncate(): bool;                              // 清空表

    // ==================== 查询方法 ====================
    public function find_fetch($where, $order, $start, $limit, $life): array;      // 条件查询
    public function find_fetch_key($where, $order, $start, $limit, $life): array;   // 条件查询返回 key
    public function find_update($where, $data, $order, $limit, $lowpriority): int;  // 批量更新
    public function find_delete($where, $order, $limit, $lowpriority): int;         // 批量删除
    public function find_count($where): int;                                        // 条件计数
    public function find_maxid(): int;                                              // 准确最大 ID

    // ==================== 辅助方法 ====================
    public function maxid($val = FALSE): int;           // 读取/设置表最大 ID
    public function count($val = FALSE): int;           // 读取/设置表记录数
    public function update_views($key, $n = 1, $field = 'views'): bool; // 浏览量 +1
    public function get_field(): array;                 // 获取表字段名
    public function pri2key($arr): string;              // 关联数组转 Key
    public function arr2key($arr): string;              // 数字索引数组转 Key
    public function arg2arr($arg1, $arg2, $arg3, $arg4): array; // 多参数转数组
    public function index_create($index): bool;         // 创建索引
    public function index_drop($index): bool;           // 删除索引

    // ==================== 只读方法 ====================
    public function read($arg1, $arg2, $arg3, $arg4): array; // 读取简化写法
}
```

---

## 附录: Model 层关键路径索引

| 方法 | 调用链 | 涉及缓存级别 |
|------|--------|-------------|
| `create()` | create() → maxid('+1') → cache_db_set() | L1(写入) + L2 + DB |
| `get()` | get() → cache_db_get() | L1 → L2 → DB |
| `set()` | set() → cache_db_set() | L1(写入) + L2 + DB |
| `update()` | update() → cache_db_update() | L1 → L2 + DB |
| `delete()` | delete() → cache_db_delete() | L1(清除) + L2(清除) + DB |
| `find_fetch()` | find_fetch() → cache_db_find_fetch() → cache_db_find_fetch_key() + cache_db_multi_get() | L2(Key) + L1 + L2(数据) + DB |
| `maxid()` | maxid() → cache_db_maxid() | L1 → L2 → DB |
| `count()` | count() → cache_db_count() | L1 → L2 → DB |

---

## 附录: 完整文件索引

| 文件 | 路径 | 说明 |
|------|------|------|
| Model 基类 | `lecms/xiunophp/lib/model.class.php` | 662 行，Model 层核心 |
| Control 基类 | `lecms/xiunophp/lib/control.class.php` | 50 行，Controller 层核心 |
| View 类 | `lecms/xiunophp/lib/view.class.php` | 257 行，View 层核心 |
| Base Control | `lecms/control/base_control.class.php` | 147 行，前台共用控制器 |
| DB 接口 | `lecms/xiunophp/db/db.interface.php` | 33 行，数据库驱动接口 |
| Cache 接口 | `lecms/xiunophp/cache/cache.interface.php` | 15 行，缓存驱动接口 |
| PDO MySQL 驱动 | `lecms/xiunophp/db/db_pdo_mysql.class.php` | 框架默认数据库驱动 |
| KV 模型 | `lecms/model/kv_model.class.php` | 通用键值对存储模型 |
| Runtime 模型 | `lecms/model/runtime_model.class.php` | 运行时配置 + Block 缓存 |


