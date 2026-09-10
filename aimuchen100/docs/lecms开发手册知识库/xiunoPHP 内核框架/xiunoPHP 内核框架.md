---
kind: external_dependency
name: xiunoPHP 内核框架
slug: xiunophp
category: external_dependency
category_hints:
    - framework_behavior
scope:
    - '**'
source_files:
    - lecms/xiunophp/xiunophp.php
    - lecms/xiunophp/lib/core.class.php
    - lecms/xiunophp/lib/control.class.php
    - lecms/xiunophp/lib/view.class.php
    - lecms/xiunophp/lib/model.class.php
    - lecms/xiunophp/lib/debug.class.php
    - lecms/xiunophp/lib/log.class.php
    - lecms/xiunophp/lib/base.func.php
    - lecms/xiunophp/lib/misc.func.php
last_updated: 2026-09-06
---

# xiunoPHP 内核框架（LECMS 改造版）

## 一、定位

xiunoPHP 是 LECMS 改造自 xiuno BBS 的轻量级 PHP 框架，仅保留 MVC、模板、模型、缓存、错误处理等核心模块，剥离 BBS 业务逻辑。

| 项 | 值 |
|---|---|
| 框架版本 | 1.0.0（FRAMEWORK_VERSION 常量） |
| 原作者 | axiuno / xiunoPHP 社区 |
| LECMS 改造 | dadadezhou（zhoudada97@foxmail.com） |
| PHP 兼容 | 5.4.0 ≤ PHP < 8.2.0（硬限制） |
| 代码位置 | `lecms/xiunophp/` |
| 启动入口 | `lecms/xiunophp/xiunophp.php` |
| 入口常量 | `FRAMEWORK_PATH`（被 `index.php` 定义） |

## 二、目录结构

```
lecms/xiunophp/
├── xiunophp.php              # ★ 框架唯一入口
├── LICENSE.txt
├── lib/                       # ★ 7 个核心类
│   ├── base.func.php          #   公共函数库（500+ 函数）
│   ├── misc.func.php          #   混合函数（hooks 注入点）
│   ├── core.class.php         #   ★ 核心类（启动、加载、路由）
│   ├── control.class.php      #   控制器基类
│   ├── model.class.php        #   模型基类（CRUD + 二级缓存）
│   ├── view.class.php         #   视图类（模板编译）
│   ├── debug.class.php        #   调试与错误处理
│   └── log.class.php          #   日志类
├── db/                        # 数据库驱动
│   ├── db.interface.php       #   数据库接口契约
│   ├── db_mysql.class.php     #   MySQL 扩展
│   ├── db_mysqli.class.php    #   MySQLi
│   └── db_pdo_mysql.class.php #   PDO MySQL（默认）
├── cache/                     # 缓存驱动
│   ├── cache.interface.php
│   ├── cache_file.class.php
│   ├── cache_memcache.class.php  # 默认驱动
│   ├── cache_memcached.class.php
│   ├── cache_redis.class.php
│   ├── cache_apc.class.php
│   └── cache_yac.class.php
├── ext/                       # 扩展类库（27 个文件）
│   ├── Database.class.php     #   数据库增强
│   ├── FetchURL.class.php     #   HTTP 抓取
│   ├── Hashids.class.php      #   短 ID 编码
│   ├── Http.class.php
│   ├── LocalAdapter.class.php
│   ├── Network.class.php
│   ├── QRcode.class.php       #   二维码
│   ├── Str.class.php
│   ├── Xxtea.class.php        #   XXTEA 加密
│   ├── check.class.php
│   ├── cookie.class.php
│   ├── email.class.php
│   ├── form.class.php
│   ├── image.class.php
│   ├── paginator.class.php
│   ├── php5replace.class.php
│   ├── pinyin.class.php
│   ├── session.class.php
│   ├── upload.class.php
│   ├── utf8.class.php
│   ├── vcode.class.php        #   验证码
│   ├── xn_zip.class.php
│   ├── network/               #   网络接口子目录
│   └── phpmailer/             #   邮件库
├── tpl/                       # 系统模板
│   ├── exception.php          #   异常页
│   ├── sys_error.php          #   系统错误页
│   ├── sys_message.php        #   系统消息页
│   └── sys_trace.php          #   调试追踪面板
├── lang/                      # 框架语言包
│   ├── zh-cn.php
│   └── en.php
└── cache/                     # 实际是文件缓存驱动，与上面的 cache/ 重复？
```

## 三、核心类职责矩阵

| 类 | 文件 | 行数级 | 职责 |
|---|---|---:|---|
| `core` | `lib/core.class.php` | 578 | 启动、自动加载、URL 解析、控制器调度、插件钩子 |
| `control` | `lib/control.class.php` | 50 | 控制器基类（__get 懒加载、视图渲染、消息） |
| `model` | `lib/model.class.php` | 661 | 模型基类（CRUD、二级缓存、单例 db/cache） |
| `view` | `lib/view.class.php` | 276 | 模板编译引擎（9 步编译、block、loop、if） |
| `debug` | `lib/debug.class.php` | — | 错误处理、异常、DEBUG 面板 |
| `log` | `lib/log.class.php` | — | 日志写入（write / trace_save / le_log） |
| `Database` | `ext/Database.class.php` | — | 建表/导入/导出增强 |

## 四、启动流程（`xiunophp.php` 全流程）

```
index.php
   ├─ define('CODE_COMPRESS', 0)
   ├─ define('ROOT_PATH', ...)
   ├─ define('APP_NAME', 'lecms')
   ├─ define('APP_PATH', ROOT_PATH.APP_NAME.'/')
   ├─ is_file('config/config.inc.php') || exit(跳转install)
   ├─ define('FRAMEWORK_PATH', 'lecms/xiunophp/')
   └─ require FRAMEWORK_PATH.'xiunophp.php';
          │
          ├─ version_compare(PHP_VERSION,'5.4.0','>') || exit
          ├─ version_compare(PHP_VERSION,'8.2.0','<') || exit
          ├─ define 常量（CONFIG_PATH / CONTROL_PATH / MODEL_PATH / VIEW_PATH / BLOCK_PATH / PLUGIN_PATH / LANG_PATH / RUNTIME_PATH / RUNTIME_MODEL / RUNTIME_CONTROL）
          ├─ include 'config/config.inc.php' → $_ENV['_config']
          ├─ 若 route_open 启用，include 'config/route.inc.php'
          ├─ 根据 F_APP_NAME 决定 DEBUG=debug_admin 或 debug
          │
          ├─ 核心类加载（DEBUG=1 时直接 include；否则生成 runcache/_lecms.php 合并文件）
          │     ├─ base.func.php
          │     ├─ core.class.php
          │     ├─ debug.class.php
          │     ├─ log.class.php
          │     ├─ model.class.php
          │     ├─ view.class.php
          │     ├─ control.class.php
          │     ├─ db/db.interface.php
          │     ├─ db/db_{type}.class.php
          │     ├─ cache/cache.interface.php
          │     ├─ cache/cache_memcache.class.php
          │     └─ ext/network/Network__interface.php
          │
          ├─ session_start()
          ├─ core::init_start()  ────────────┐
          │     ├─ debug::init_start()
          │     ├─ self::open_ob_start()    # ob_start(gzip)
          │     ├─ self::init_set()         # 自动加载、时区、GPC、IP
          │     ├─ self::init_misc()        # 合并 misc.func.php
          │     ├─ self::init_lang()        # 合并语言包
          │     ├─ self::init_get()         # URL 解析 → $_GET[control]/action
          │     └─ self::init_control()     # 实例化控制器、调 action
          └─ DEBUG>1 && !IS_AJAX: debug::debug_info()
```

## 五、自动加载（`core::autoload_handler`）

按以下顺序查找类文件：

```
db_*    → lecms/xiunophp/db/{classname}.class.php
cache_* → lecms/xiunophp/cache/{classname}.class.php
其它    → lecms/xiunophp/ext/{classname}.class.php
          → lecms/xiunophp/ext/network/{classname}.php
兜底    → 若 VENDOR 已定义抛 Exception
```

> 模型/控制器不走自动加载，由 `core::init_control()` 和 `core::model()` 显式查找（按插件优先级）。

## 六、URL 解析（`core::init_get`）

`lecms_parseurl=0` 时（默认），从 `?u=` 或 `PATH_INFO` 取：

```
URL: ?u=show-index-cid-5-page-2
解析 ↓
   $_GET['control'] = 'show'
   $_GET['action']  = 'index'
   $_GET['cid'] = 5
   $_GET['page'] = 2
```

`lecms_parseurl=1` 时转 `parseurl_control.class.php::index()` 处理自定义伪静态。

## 七、控制器实例化（`core::init_control`）

```php
$controlname = "{$control}_control.class.php";
$objfile = RUNTIME_CONTROL.$controlname;
if (DEBUG || !is_file($objfile)) {
    $controlfile = self::get_original_file($controlname, CONTROL_PATH);
    self::process_all($controlfile, $objfile, "...");
}
include $objfile;
$obj = new {$control}_control();
$obj->{$action}();
```

`get_original_file` 查找顺序：

```
1. plugin/{p}/lecms/{file}        ← 插件优先（前台）
2. plugin/{p}/{file}
3. plugin/{p}/control/{file}
4. plugin/{p}/model/{file}
5. plugin/{p}/block/{file}
6. (框架默认) lecms/control/{file}
```

> `plugin_disable=0` 时启用插件查找；`=1` 时跳过所有插件。

## 八、模型加载（`core::model`）

```php
$modelname = "{$model}_model.class.php";
$objfile = RUNTIME_MODEL.$modelname;
if (DEBUG || !is_file($objfile)) {
    $modelfile = self::get_original_file($modelname, MODEL_PATH);
    // hooks 注入 + 写入缓存
}
include $objfile;
$mod = new $model();
$_ENV['_models'][$modelname] = $mod;
return $mod;
```

模型通过 `__get` 懒加载 db / cache 实例：

```php
function __get($var) {
  switch ($var) {
    case 'db':    return $this->db = $this->load_db();
    case 'cache': return $this->cache = $this->load_cache();
    case 'db_conf':    return $_ENV['_config']['db'];
    case 'cache_conf': return $_ENV['_config']['cache'];
    default: return $this->$var = core::model($var);  // 跨模型调用
  }
}
```

## 九、视图编译（`view::display`）

```
display($filename)
   ↓
get_tplfile($filename)
   ↓
tpl_process($tpl_file)  ← 9 步编译
   ↓
FW($php_file, $compiled)  ← 写入 runcache/lecms_view/{theme},{file}.php
   ↓
include $php_file
```

9 步编译顺序：

1. `{inc:xxx.htm}` → 内联
2. `{hook:xxx}` → 插件注入
3. `{php}…{/php}` → `<?php … ?>`
4. `{block:xxx arg="val"}…{/block}` → 头部函数定义 + 调用
5. `{loop:$arr $v $k}…{/loop}` → foreach
6. `{if:…}{elseif:…}{else}…{/if}` → if/elseif/else
7. `{@expr}` 与 `{$var}` → 输出
8. `{lang:xxx}` → 语言包
9. HTML/JS 压缩 + 去 PHP 标记

## 十、Hook 注入（`core::process_hook`）

源码中以 `// hook xxx.php` 标记，编译期被替换：

```php
$s = preg_replace_callback(
  '#\t*\/\/\s*hook\s+([\w\.]+)[\r\n]#',
  ['core', 'process_hook'],
  $s
);

// process_hook 内部：
// 按 plugin.inc.php 中 rank 升序
// 把 plugin/{p}/xxx.php 与 plugin/{p}/hook/xxx.php 内容拼接到标记位置
```

> Hook 注入只在编译期生效（DEBUG=1 时每次重编译）；`process_hook` 对运行时模板的 `{hook:xxx}` 标签也生效。

## 十一、运行时合并文件

生产模式（DEBUG=0）下，框架把 11 个核心类合并为 `runcache/_lecms.php`，减少 IO：

```
$s = '';
foreach (['base.func.php','core.class.php','debug.class.php','log.class.php','model.class.php','view.class.php','control.class.php','db.interface.php',"db_{type}.class.php",'cache.interface.php','cache_memcache.class.php','Network__interface.php'] as $f) {
    $s .= trim(php_strip_whitespace(FRAMEWORK_PATH.$f), "<?ph>\r\n");
}
$s = str_replace("defined('ROOT_PATH') || exit;", '', $s);
FW('runcache/_lecms.php', "<?php defined('ROOT_PATH') || exit; ".$s);
include 'runcache/_lecms.php';
```

> 修改任意核心类后必须**删除 `runcache/_lecms.php`** 让框架重新生成。

## 十二、控制器继承

```
control (xiunophp/lib)
└── base_control (lecms/control)        ← 所有前台控制器继承
    ├── index_control
    ├── cate_control
    ├── show_control
    ├── list_control
    ├── comment_control
    ├── user_control
    ├── my_control
    ├── space_control
    ├── search_control
    ├── tag_control
    ├── flags_control
    ├── sitemap_control
    ├── parseurl_control
    └── error404_control

control (xiunophp/lib)
└── admin_control (admin/control)        ← 所有后台控制器继承
    ├── index_control
    ├── content_control
    ├── category_control
    ├── cms_page_control
    ├── ...
```

## 十三、与原版 xiunoBBS 的差异

| 项 | xiunoBBS | LECMS 版 |
|---|---|---|
| 业务层 | 论坛（板块、帖子、用户） | 通用 CMS（内容、分类、标签、模型） |
| 数据库 | MySQL only | 三种驱动可选 |
| 缓存 | Redis/Memcache | 6 种可选 |
| 模板 | 保留 | 重写为 9 步编译，新增 block/loop 标签 |
| Hook | 论坛钩子 | 通用 hook + 编译期注入 |
| 调试面板 | 简陋 | 增加 SQL 追踪、模板编译追踪 |
| 移除 | 用户权限、积分、提醒 | 无 |

## 十四、调试面板

DEBUG=2 时 `debug::debug_info()` 在页面底部输出：

- 请求 URL / METHOD / IP / UA
- 全部 SQL 与耗时
- 全部 include 的文件
- 内存峰值
- 各阶段耗时分解

## 十五、相关源码位置

- `lecms/xiunophp/` — 框架源码
- `index.php` / `admin/index.php` — 入口
- 主知识库"第二篇_核心架构.md" §6-11 — 启动流程详解
- 主知识库"第三篇_路由系统.md" §15 — 完整生命周期

## 十六、FAQ

**Q：修改了 core.class.php 为什么不生效？**
A：删除 `runcache/_lecms.php`；或临时把 `DEBUG=1`；或浏览器硬刷新。

**Q：PHP 8.2+ 直接 exit？**
A：xiunophp.php 第 8 行硬限制。升级路径：删除该行 + 修复动态属性问题（每个类加 `#[\AllowDynamicProperties]`）。

**Q：自定义类如何被自动加载？**
A：放到 `lecms/xiunophp/ext/`（命名空间会被替换为 `/`）；或者在控制器的 `__construct` 里手动 `require`。

**Q：怎么跳过插件加载？**
A：配置 `plugin_disable=1`，或临时清空 `plugin.inc.php`。