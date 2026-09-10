# User Instruction Memory

This file records user instructions, preferences, and teachings for reference in future interactions.

## Format

### User Instruction Entry
User instruction entries should follow this format:

[User Instruction Summary]
- Date: [YYYY-MM-DD]
- Context: [Mentioned scenario or time]
- Instructions:
  - [Content of user teaching or instruction, described line by line]

### Project Knowledge Entry
Entries discovered by the Agent during task execution should follow this format:

[Project Knowledge Summary]
- Date: [YYYY-MM-DD]
- Context: Discovered by Agent while performing [specific task description]
- Category: [Operations & Deployment|Build Methods|Testing Methods|Troubleshooting & Debugging|Workflow & Collaboration|Environment Configuration]
- Instructions:
  - [Specific knowledge points, described line by line]

## Deduplication Strategy
- Before adding a new entry, check for similar or identical instructions.
- If a duplicate is found, skip the new entry or merge it with the existing one.
- When merging, update the context or date information.
- This helps avoid redundant entries and keeps the memory file tidy.

## Entries

[盘搜 PanSearch 项目启动与运维]
- Date: 2026-08-21
- Context: Agent 开发网盘搜索系统（/workspace/pan-search）时确认
- Category: Operations & Deployment
- Instructions:
  - 一键启动：`/workspace/pan-search/start.sh`，后端 API 监听 3001，前端 Vite 监听 5173（对外预览入口，已配置 /api 反向代理到 3001）
  - 后端必须以 `node --experimental-sqlite --no-warnings src/index.js` 启动（node:sqlite 在 Node 22 需要实验标志）
  - 管理后台令牌由环境变量 ADMIN_TOKEN 控制，默认 admin123；请求头 X-Admin-Token
  - 数据文件为 SQLite：`server/data/pan-search.db`（WAL 模式），删除该文件重启可重新生成种子数据
  - 种子数据量由 SEED_COUNT 控制（默认 2000）；模拟采集间隔 COLLECT_INTERVAL_MS（默认 30 秒）
  - 生产 TG 采集使用 `server/tools/tg_importer.py`（Telethon），需用户自行配置 TG_API_ID/TG_API_HASH/TG_SESSION

[环境限制与检索选型]
- Date: 2026-08-21
- Context: Agent 在沙箱中选型时验证
- Category: Environment Configuration
- Instructions:
  - 本环境无 Docker、无 MySQL 服务，内存约 8GB 但常驻占用高，跑不动 Elasticsearch；检索层用 SQLite FTS5 + 自实现 bigram 中文分词（server/src/tokenizer.js）替代，FTS 同步通过 resources.title_t 列 + SQL 触发器实现
  - SQLite FTS5 trigram 分词器无法命中 2 字中文词，勿改用 trigram

[LECMS 3.0.4 后台 API 架构与关键约束]
- Date: 2026-09-04
- Context: Discovered by Agent while fixing API controller and testing all endpoints
- Category: Operations & Deployment
- Instructions:
  - 入口文件 `/workspace/lecms/api.php`（APP_NAME='api'），所有前端 API 请求经此路由；框架路由 `$_GET['u']='api-{action}'` → `api_control` 类的 `{action}` 方法（如 `content/list` → `content_list()`）
  - 编译缓存：源文件 `lecms/control/api_control.class.php` 改动后必须删除 `runcache/lecms_control/api_control.class.php`，否则改动不生效；同理 `runcache/_lecms.php` 是框架核心编译缓存
  - 鉴权：LECMS 默认 cookie 方式登录（`_login_method='cookie'`），登录写入 `le26UfR_admauth` cookie（前缀见 config.inc.php `cookie_pre`），鉴权用 `$user_model->user_token_check(1)`，不要检查 `$_SESSION['uid']`
  - `_json()` 方法在 api_control 中自定义（control 基类的 `__call` 在 DEBUG 关闭时直接返回 404 页面，不会抛异常，极易误判）
  - 模型 table 属性懒初始化：`cms_content->table` 必须设为 `'cms_article'`，`cms_content_tag->table='cms_article_tag'`，`cms_content_attach->table='cms_article_attach'`，`cms_content_comment->table='cms_comment'`（这些模型默认 `table=''`，不设会查到空表名报错）
  - 表名带 `pre_` 前缀（见 config `tablepre`），但模型层自动加前缀，代码中用 `cms_article` 不带 `pre_`
  - nginx 配置 `/etc/nginx/sites-available/lecms`：端口 8001，`location = /api.php` 优先匹配直连 FPM，避免被 `location /` 的 `try_files` 拦截到 index.php 的 404
  - 设置存储：`$this->kv->xset($k,$v,'cfg')` + `$this->kv->save_changed()` + `$this->runtime->delete('cfg')` 清缓存；`$cfg=$this->kv->xget('cfg')` 读取
  - 前端 `admin-ui`（Vite 5173）通过 `vite.config.ts` 代理 `/api/*` → `8001/api.php?u=*`；后台预览 URL 形如 `https://5173-<token>.monkeycode-ai.online`
  - 后端构建：`cd /workspace/lecms/admin-ui && npm run build`（vue-tsc + vite build，约 8 秒）
  - 旧 Layui 后台保留在 `/admin/` 路径，与新 Arco 后台共存

[LECMS 3.0.4 插件 POST 写入与模板排障要点]
- Date: 2026-09-06
- Context: Discovered by Agent while installing LECMS at /workspace/aimuchen100 and verifying 52 admin pages + 6 plugin POST write tests
- Category: Troubleshooting & Debugging
- Instructions:
  - `db_pdo_mysql` 缺失 `insert()` 方法，但多个插件模型（competitor_site、keyword、sync_queue、cps_config、external_link、enterprise_site 等）调用 `$this->db->insert("`{pre}{table}`", $data)`，会触发 `Call to undefined method db_pdo_mysql::insert()`。一次性修复：在 `/workspace/aimuchen100/lecms/xiunophp/db/db_pdo_mysql.class.php` 类末尾添加 `public function insert($table, $data)`，用 `prepare` + `array_values` 绑定参数，返回 `lastInsertId`
  - `R($k, $var='G')` 第二个参数是来源字符串（'G'/'P'/'R'），不是默认值。误写 `R('page', 1)` 会被 switch 不匹配，$var=1 整数，`$var['page']` 在 PHP 7.4 返回 null，page=0 导致 SQL `LIMIT N OFFSET -N` 查询返回空且无错误。正确写法：(int)R('page', 'R')。已确认 bug 处：spider_blacklist_control:6、spider_dashboard_control:39,49
  - 模板引擎 `{loop:array('a','b') $v}` 内联数组语法不被支持（`$reg_arr` 正则要求 `\$` 前缀），会导致循环体 `{loop:...}...{/loop}` 原样输出、内部 `$v` 未定义，触发 `Undefined variable: v`。正确做法：控制器 `$this->assign('list', array(...))`，模板 `{loop:$list $v}`。注意 `$this->assign($k, &$v)` 是引用传参，**不能传表达式/字面量/三目**（`assign('x', $a ?: '')` 会报 `Cannot pass parameter 2 by reference`），必须先赋给变量再传；`assign_value()` 按值传参无此限制
  - 插件控制器/模板编译缓存在 `/workspace/aimuchen100/runcache/admin_control/*.class.php` 和 `runcache/admin_view/default,*.htm.php`。修改插件源码后必须删除对应缓存文件，否则改动不生效
  - POST 写入测试：FORM_HASH = `substr(md5(substr($_ENV['_time'],0,-5).$_ENV['_config']['auth_key']),16)`，每秒变化但同一秒内多次 POST 共享同一值。必须带 `X-Requested-With: XMLHttpRequest` 头才返回 JSON。返回格式有三种：E() 用 `{"err":0,"msg":"..."}`、message() 用 `{"status":0,"message":"...","jumpurl":...}`、错误页用 `{"error":"[程序异常]..."}`
  - site_manager 插件的 `le_site_manager` 表结构必须是 INT 时间戳（`created_at INT UNSIGNED`），而 core_engine.sql 默认建为 DATETIME，会导致 INSERT 报 `Incorrect datetime value`。插件 install.php 的 source 是权威
  - 容器内 MariaDB/Redis 需手动启动：用 `background_terminal_create` 跑 `mariadbd --user=mysql --datadir=/var/lib/mysql --socket=/var/run/mysqld/mysqld.sock --port=3306 --bind-address=127.0.0.1`（无 systemd）
  - PHP 7.4 FPM/CLI 通过 sury 源安装：`apt-get install -y php7.4-{cli,fpm,mysql,gd,mbstring,curl,zip,xml,opcache,bcmath}`，用 `update-alternatives --set php /usr/bin/php7.4` 切默认
  - LECMS 后台 URL 路由：菜单 href 形如 `?control-action` 或 `/admin/control-action`，通过 PHP 内置服务器 router.php 用 `$_GET['u']` + QUERY_STRING 转发；POST 接口加 `-ajax-1` 后缀（如 `?sensitive-word_add_post-ajax-1`）
  - `core::get_original_file()` 优先查插件根目录同名文件。插件 `url_generator/game_list.htm` 会覆盖主题 `view/game/game_list.htm`，导致分类页编译成插件 stub，`$games` 未定义。插件模板已改名为 `url_generator_game_list.htm` / `url_generator_game_detail.htm`
  - 文章 URL 规则 `link_show_type=2` 是 `{cate_alias}/{id}.html`（如 `/game/1.html`），别名 `/game/best-games-2026.html` 不会命中
  - 分类 `show_tpl` 必须是主题真实存在的模板（`article_show.htm`），默认 `show.htm` 不存在会 404
  - 预览域名访问前台必须先把域名登记进 `le_site_manager`（`domain` 支持 `*.monkeycode-ai.online` 泛解析，`theme='game'`），否则 site_manager 插件返回 404；登记后删 `le_runtime` 的 `site_domain_map` 缓存。精确域名与泛解析各需一行，泛解析行会以字面量 `*.monkeycode-ai.online` 覆写 webdomain 导致站内链接坏掉，必须另加精确域名行让精确匹配优先
  - `url_generator` 插件 hook `parseurl_control_index_rewrite_before.php` 里 `if(!$site_id) return;` 会内联进 parseurl_control::index 提前终止整个路由（所有 URL 退化为首页），已改为 `if($site_id){ ... }` 包裹 url_map 查询，未匹配站点时继续标准解析
  - site_manager 只在 base_control 构造时覆写 controller `_cfg`，model 层（cms_content 等）通过 `runtime_model::xget('cfg')` 独立读取固化缓存拿到旧 webdomain。已加 `$_ENV['_site_override']` 机制：hook 写入覆写值，`runtime_model::xget()` 返回前应用（仅当前请求，不落库）；error404_control 不继承 base_control，需在 index() 内自行按 HTTP_HOST 匹配站点并填 `$_ENV['_site_override']`
  - 改 `parseurl_control.class.php` 时注意别删 `category_url()` 调用块（紧邻 `link_cate_after` hook 注释），误删会导致分类路由全部失效

[LECMS spider 插件 POST 传参与 PDO 连接取用陷阱]
- Date: 2026-09-08
- Context: Discovered by Agent while verifying spider_analytics / data_export 插件的 POST CRUD 闭环
- Category: Troubleshooting & Debugging
- Instructions:
  - `R($k)` 不带第二参数时默认读 `$_GET`（'G'），POST 提交的值会读不到返回空。spider_iprange_control::add() 原写 `R('engine')` 导致 engine/cidr 插入空值；spider_iprange_control::del() 与 data_export_control::delete() 原写 `R('id')` 导致 POST 删的是 id=0、DB 无行。统一改成 `R($k,'P')`
  - 插件模型 delete() 若 `execute()` 后不 `return $stmt->execute(...)` 会返回 null，控制器 `if(!$res)` 恒真报"删除失败"（DELETE 实际已执行，属假失败）。spider_blacklist::delete / spider_iprange::delete 已补 return
  - `db_pdo_mysql` 的 `rlink`/`wlink` 是 `__get` 魔术属性懒加载，**绝不能用 `isset($this->db->rlink)` 判断**——isset 不触发 __get 恒为 false，pdo 拿成 null 导致 `prepare() on null`。直接 `$pdo = $this->db->rlink;` 才会触发连接。data_export_control 原写 `isset($this->db->pdo)`（属性名也错）已修
  - `db_pdo_mysql::exec()` 对以 INSERT/REPLACE 开头的 SQL 返回 `last_insert_id()` 而非受影响行数，依赖其返回值统计"插入条数"会被误导（url_generator 批量生成曾报 4501/5917 荒谬总数）。批量插入统计应改用自统计（如 `count($urls)`）
  - data_export 导出产物流：start 同步执行写入 le_export_log（status=done progress=100），progress 接口返回 `{"progress":"100","current_file":"finalize","status":"done"}`，zip 落在 `runtime/data_export/site-{id}-{ts}.zip`，delete 置 status='deleted' 并 unlink 文件
  - spider 系列插件（blacklist/iprange/pool 等）模型通过 `spider_runtime::init($this->db->rlink, tablepre)` 注入 PDO，控制器已统一用 `R($k,'P')` 读 POST；模型文件不走 runcache 编译（控制器直接 require_once 源码），改模型即时生效，只有改控制器才需 mv `runcache/admin_control/对应_control.class.php`。注意"模型不走 runcache"仅限 spider 系列：url_generator 等经 `runcache/lecms_model/*.class.php` 编译，改模型须 mv 对应缓存才生效
  - multi_language 的 language-edit_post 期望 `$_POST['languages']` 嵌套数组（如 `languages[fr][language_name]=法语`），不是扁平字段，且 `save_site_config` 按 site_id 整体替换配置

[LECMS patch/sync 插件缺失依赖注入与模型 require]
- Date: 2026-09-09
- Context: Discovered by Agent while verifying patch_system 与 self_media_sync 插件的 POST 闭环
- Category: Troubleshooting & Debugging
- Instructions:
  - patch_system 的 patch_control 里 upload_post/verify_post/apply_post 用 `new patch_manager($this->site_id, $this->db)`，rollback_post 用 `new rollback_manager($this->site_id)`，但控制器从未 require 模型文件 → 报"类 patch_manager 不存在"。已在构造函数补 `require_once ROOT_PATH.'lecms/plugin/patch_system/model/patch_manager.class.php'` 和 `rollback_manager.class.php`；patch_manager 内部又依赖 backup_manager（apply_patch 里 `new backup_manager`），已在 patch_manager 顶部补 require
  - self_media_sync 的 sync_control 三处 `new sync_queue($site_id)` 只传 site_id 不传 db，sync_queue 构造函数 `$db = null` 默认值 → `$this->db` 为 null → `insert() on null`。已改 `new sync_queue($site_id, $this->db)`
  - 插件模型构造 `($site_id=0, $db=null)` 模式是常见反模式：控制器 new 时忘传 db 会静默失败。验证插件 POST 时优先检查 new 模型处是否传了 `$this->db`
  - patch 补丁格式：zip 内含 manifest.json（字段 version/files/signature），files[] 每项含 `path` 和 `md5`（不是 hash）；verify 校验 zip 整体 SHA256 签名 + 每文件 md5 + 语义化版本号；apply 把文件解压写入 ROOT_PATH 相对路径（is_safe_path 黑名单拦 xiunophp/、index.php、.env、config/config.php），先 backup 到 runcache 再替换；rollback 恢复 backup_path
  - form_submit() 校验 `R('FORM_HASH','P') == form_hash()`，form_hash=`substr(md5(substr(time(),0,-5).auth_key),16)` 每 100 秒变一次。脚本里直接从页面抓 FORM_HASH 不可靠（页面多是内联调用函数），应直接按公式计算
  - template_manager 表：`le_cms_template`（主题登记）、`le_cms_block_config`（区块，block_save_post）、`le_cms_template_file`（文件版本）；主题同步 theme_sync_post 扫描 view/ 目录自动登记
  - ai_content_factory 的 ai_task：create_post（site_id/category_id/count/prompt_template）→ execute（调 AI API，失败被 try/catch 捕获返回 E(1) 但任务状态正常流转）→ delete；le_ai_task 无 count 列（是 batch_size/total/success/fail）

[LECMS 前台控制器站点匹配与 DEBUG hook 幂等]
- Date: 2026-09-09
- Context: Discovered by Agent while locating CPS download 中间页 404 根因并验证前台下载闭环
- Category: Troubleshooting & Debugging
- Instructions:
  - site_manager 插件 hook `base_control_construct_before.php` 结尾对**未匹配站点**（HTTP_HOST 不在 le_site_manager.domain 映射）直接 `core::error404(); exit();`，会拦截所有继承 base_control 的前台控制器（含插件控制器），`new Xxx_control()` 构造即 404 且无异常抛出。调试前台页面必须用已登记的 Host：localhost→sid=1、127.0.0.1→sid=2；用未登记端口（如 8094）访问会命中此 404，与控制器逻辑无关
  - cps_integration 的 download_control::index 用 `get_best_link((int)CURRENT_SITE_ID, game_id)` 查 le_cms_cps_config（WHERE site_id=当前匹配站点 sid），配置须登记在当前访问站点的 sid 下才能命中；访问 URL `index.php?download-index-id-{game_id}` 经 parseurl other_url 解析出 control=download
  - router.php 对非 /admin 路径设 `$_GET['rewrite']=ltrim($path,'/')`，但**当 path 是真实文件时 `return false` 交给 PHP 内置服务器直跑**。因此前台插件 query 模式 URL 必须带 `index.php?` 前缀（如 `index.php?download-index-id-1`）才会保留 query 进 other_url；裸 `?download-index-id-1`（path=/）或 `/download-index-id-1` 会被 rewrite 分支吃掉 → 404
  - DEBUG 模式（debug=2）下 `view::display()` 不 echo 而是逐个 include 所有启用插件的 `hook/view_display_after.php`（view.class.php），生产 DEBUG=0 走 else echo 分支不 include。单请求多次 display（页面模板/中间页）会重复 include 同一 hook 文件 → 顶层函数重复声明 `Cannot redeclare` fatal。template_manager 的 view_display_after.php 顶层定义 `tm_get_settings_safe()` 已用 `if(!function_exists(...))` 包裹保证幂等；**插件 hook 文件顶层函数一律要 function_exists 保护**
  - 浏览器/curl 访问前台返回 404 排查顺序：①HTTP_HOST 是否在 le_site_manager 映射（site_manager hook 直接 error404）→ ②parseurl 路由解析 → ③控制器文件是否找到 → ④new 构造是否被 hook 拦截。曾误判 download 插件不可用，实为站点匹配层拦截

[LECMS 插件后台兜底初始化与前台域名匹配四连排障]
- Date: 2026-09-10
- Context: Discovered by Agent while 修复 spider_analytics 持续报错并验证前台 URL 闭环
- Category: Troubleshooting & Debugging
- Instructions:
  - 判断 `$controller->db` 是否存在用 `isset()` 恒为 false：control 基类的 `db` 是 `__get` 魔术属性，构造期间未访问过时不是真实属性，`isset` 不会触发 `__get`。spider_analytics 的 admin hook 原写 `isset($controller->db)` 导致 `spider_runtime::init` 被跳过、`self::$pdo` 为 null，随后 aggregator 对 null 调 `prepare()` 抛异常。必须直接访问 `$controller->db->rlink`（懒创建）+ 在 `try_run()` 开头加 `if(!spider_runtime::$pdo) return;` 兜底
  - LECMS 的 `class_exists('X')` 不带第二参数会触发 `core::autoload_handler`，类不存在时直接 `throw new Exception("类 X 不存在")`（非返回 false），被 hook 的 try/catch 捕获后记成"类 X 不存在"刷屏。判断可选 mock/测试类一律 `class_exists('X', false)`
  - site_manager 的 `match_domain_host()` 精确/泛解析匹配前必须去掉 Host 端口号：带 `:8080` 的 Host 匹配不到无端口登记域名，前台全部 404。url_generator 的 parseurl hook 已剥端口，site_manager 端漏了，已统一在函数开头 `substr($host,0,strpos($host,':'))`
  - 后台控制器编译缓存链：`index_control extends admin_control`，index_control 编译产物内嵌 `include RUNTIME_CONTROL.'admin_control.class.php'`。只 mv 掉 `runcache/admin_control/admin_control.class.php` 不够——请求走的是已缓存的 index_control，不会触发父类重编译，include 直接失败。必须同时 mv 掉 index_control 缓存（或请求的控制器缓存），下一次请求才会递归重编译父类

[LECMS url_generator URL 池哈希一致性三连 bug]
- Date: 2026-09-10
- Context: Discovered by Agent while 验证 url_generator 插件 8 种 URL 类型前台 rewrite 闭环
- Category: Troubleshooting & Debugging
- Instructions:
  - le_cms_url_map.url_hash 列是 **char(40)**，生成端 make_url_hash 写完整 64 位 SHA256 被 MySQL 静默截断为前 40 位；凡用完整 64 位 hash 查询的代码永不命中。共修 3 处：①hook `parseurl_control_index_rewrite_before.php`（rewrite 路由查询）②model `make_url_hash`（生成端，统一截断 40）③game_control `associate_url`（访问置已用状态，改调 make_url_hash）。**char(40) 哈希列 + SHA256 的组合必须在读写两端统一 substr(...,0,40)**
  - hashid 型 URL 生成端原为随机 6 位 hash、解码端 decode_hashid 用 `base_convert($hash,36,10)` 解 id——随机 hash 解出的 id 几乎必然不存在 → hashid 型 URL 永远 404。已改生成端为 `base_convert((string)$game_id,10,36)` 与解码对称；注意 base36(1)='1' 会与数字型 /1.html 撞车去重，小 id 游戏需 game_id≥10 才有独立 hashid URL
  - 后台插件**模型文件也有编译缓存** `runcache/lecms_model/*.class.php`（core::model 走 RUNTIME_MODEL），后台 debug_admin=0 时改模型源码必须 mv 对应缓存才生效；控制器缓存同理在 runcache/admin_control/。前台 debug=2 时 control/model 每次重编译无需清缓存
  - url_generator 8 种类型 rewrite URL 全部验证通过：/list-{p}.html、/{id}.html、/category/{slug}.html、/tag/{name}.html、/{year}/{month}/{slug}.html、/{alias}.html（需 le_only_alias 登记 alias→id）、/{cid}/{id}/{yyyymmdd}/game.html、/{base36(id)}.html；生成入口 admin_url-generate_post（site_id+count，按比例生成，flexible/hashid 比例 0.05 需 count≥20 才出）
  - 后台隔夜登录态过期（admauth/session），重登走 /tmp/admin_login.php 流程：先 GET /admin/ 抓页面内 FORM_HASH（登录页有真实 input hidden），POST `index-login-ajax-1`（action 是 login 不是 login_post）



[LECMS 后台语言包加载与模板缓存]
- Date: 2026-09-10
- Context: Discovered by Agent while 统一插件 UI 语言时发现后台大量 `lang[xxx]` 字面占位符
- Category: Troubleshooting & Debugging
- Instructions:
  - `lecms/config/config.inc.php` 的 `admin_lang` 若为空串，`core::init_lang` 的 F_APP_NAME 分支 `is_file(FRAMEWORK_PATH.'lang/.php')` 恒 false，语言包**完全不加载**（$_SERVER['lang'] 空），后台所有 `{lang:xxx}` 显示成字面 `lang[xxx]`；设为 'zh-cn' 后正常。前台 `lang` 同理
  - 后台语言包 = `lecms/xiunophp/lang/{admin_lang}.php` + `lecms/lang/{admin_lang}_admin.php` 两文件合并
  - **模板编译缓存写死语言值**：view.class.php 的 `process_lang` 在编译模板时就把 `{lang:xxx}` 替换成当时的语言文本，模板缓存 `runcache/admin_view/{theme},{模板}.htm.php` 只判 `is_file`（不校验源 mtime/语言文件 mtime）。改语言文件或 {lang:} 标签后，必须 mv 掉对应 admin_view 编译缓存（整目录清空可一并重建），否则页面仍显示旧占位符

[LECMS 后台 GET 中文参数解码与列表页分页筛选]
- Date: 2026-09-10
- Context: Discovered by Agent while 给后台列表页补搜索/分页（sensitive/url_generator/sync/ai_task/spider_hit）
- Category: Troubleshooting & Debugging
- Instructions:
  - 后台 GET 参数走 `core::init_get`：当 URL 直接命中真实文件（`admin/index.php?...`，router.php `is_file` 时 return false 不转发）时无 `$_GET['u']`，框架从 QUERY_STRING 解析出 control/action 及键值，**键值未 urldecode** → 中文搜索词变成 `%e6%94%bf...` 原样进 SQL 匹配不到。已在 init_get 键值赋值处加 `urldecode()`（对 ASCII/已解码值幂等，无副作用）。凡新增 GET 搜索参数必须验证中文值能回显
  - `admin_control::get_pagebar($total,$pagenum,$page,$show_pages=5,$extra=array())` 第 5 参 extra 数组会拼到分页链接 query（`&key=val`），翻页时保留筛选/搜索条件；不传 extra 翻页会丢参数
  - 列表页打磨三类缺口的补法：①服务端渲染表格无分页→控制器算 total + `$pagebar=$this->get_pagebar(...);$this->assign('pagebar',$pagebar)`，模板表格下放 `{$pagebar}`；②无搜索→控制器 SQL 拼 LIKE + addslashes，模板加 GET 搜索表单（hidden 提交 control-action）+ 清除链接；③layui table 前端渲染页（url: 返回 code/count/data）自带分页 UI，数据量小无需改
  - sync/spider_hit 等控制器用 `$this->db->fetch_*` 直查时表名写 `$_ENV['_config']['db']['master']['tablepre']`（`$this->tablepre` 在非敏感词控制器未定义，会触发 `__get` 找 tablepre_model 报"类不存在"）
