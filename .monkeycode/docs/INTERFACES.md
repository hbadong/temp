# YzmCMS 接口文档

## 公开 API 接口

YzmCMS 提供了丰富的后台管理接口和前台展示接口，以下为核心接口说明。

### 核心常量定义

| 常量 | 值 | 说明 |
|------|-----|------|
| `IN_YZMPHP` | `true` | 安全常量，防止直接访问 |
| `YZMPHP_PATH` | 框架根目录 | 框架路径 |
| `APP_PATH` | application/ | 应用目录 |
| `SITE_URL` | 当前站点URL | 完整URL地址 |
| `SITE_PATH` | 程序安装路径 | 安装路径 |
| `SYS_TIME` | time() | 系统时间戳 |
| `ROUTE_M` | 模块名 | 当前模块 |
| `ROUTE_C` | 控制器名 | 当前控制器 |
| `ROUTE_A` | 方法名 | 当前方法 |

### 核心函数接口

#### 全局函数库 (yzmphp/core/function/global.func.php)

```php
/**
 * 加载系统类
 * @param string $classname 类名
 * @param string $path 扩展地址
 * @param int $initialize 是否初始化
 * @return object
 */
yzm_base::load_sys_class($classname, $path = '', $initialize = 1)

/**
 * 加载模型
 * @param string $classname 模型名
 * @param string $m 模块名
 * @param int $initialize 是否初始化
 * @return object
 */
yzm_base::load_model($classname, $m = '', $initialize = 1)

/**
 * 加载控制器
 * @param string $c 控制器名
 * @param string $m 模块名
 * @param int $initialize 是否初始化
 * @return object
 */
yzm_base::load_controller($c, $m = '', $initialize = 1)

/**
 * 加载公共函数库
 * @param string $func 函数库名
 */
yzm_base::load_sys_func($func)

/**
 * 获取配置项
 * @param string $name 配置名
 * @param mixed $default 默认值
 * @return mixed
 */
C($name, $default = '')

/**
 * 获取栏目信息
 * @param int $catid 栏目ID
 * @param string $field 字段名
 * @param bool $onlyid 是否只返回ID
 * @return mixed
 */
get_category($catid, $field = '', $onlyid = false)

/**
 * 获取模型信息
 * @param int $modelid 模型ID
 * @param string $field 字段名
 * @return mixed
 */
get_model($modelid, $field = '')

/**
 * 获取站点配置
 * @return array
 */
get_config()

/**
 * 生成内容URL
 * @param int $catid 栏目ID
 * @param int $id 内容ID
 * @return string
 */
get_content_url($catid, $id)

/**
 * 获取Cookie
 * @param string $name Cookie名
 * @param mixed $default 默认值
 * @return mixed
 */
get_cookie($name, $default = '')

/**
 * 设置Cookie
 * @param string $name Cookie名
 * @param mixed $value 值
 * @param int $ttl 生命周期
 * @param bool $encrypt 是否加密
 */
set_cookie($name, $value, $ttl = 0, $encrypt = false)

/**
 * 模板解析
 * @param string $module 模块名
 * @param string $template 模板名
 * @param int $cache 缓存时间
 * @return string
 */
template($module, $template, $cache = 0)

/**
 * 提示消息
 * @param string $msg 消息内容
 * @param string $gourl 跳转地址
 * @param int $limittime 跳转时间
 */
showmsg($msg, $gourl = '', $limittime = 0)

/**
 * 返回JSON
 * @param mixed $data 数据
 * @param bool $exit 是否终止
 */
return_json($data, $exit = true)

/**
 * 获取站点URL
 * @return string
 */
get_site_url()
```

### 数据库模型基类接口

#### D() 函数 - 获取模型实例

```php
/**
 * 获取数据库模型实例
 * @param string $table 表名（不带前缀）
 * @param bool $persistent 是否持久连接
 * @return object DB对象
 */
D($table, $persistent = false)
```

#### DB 对象方法

```php
// 查询方法
$db->where($where)->select()      // 查询多条
$db->where($where)->find()        // 查询一条
$db->where($where)->total()       // 统计数量
$db->where($where)->one()         // 获取单个字段值
$db->field($field)->where($where)->select()  // 指定字段

// 插入方法
$db->insert($data)                // 插入数据，返回ID
$db->insert($data, true, false)    // 插入并忽略错误

// 更新方法
$db->update($data, $where)        // 更新数据
$db->update('`click` = `click`+1', $where)  // 字段运算更新

// 删除方法
$db->delete($where)               // 删除数据

// 排序分页
$db->order($order)->limit($limit)->select()
$db->order('id DESC')->limit('0, 10')->select()

// JOIN查询
$db->alias('a')->join('yzmcms_table b ON a.id=b.aid')->select()
```

### 后台管理接口

#### 内容管理 (content.class.php)

| 方法 | 路由 | 说明 | 参数 |
|------|------|------|------|
| init | content/init | 内容列表 | GET参数: of, or, modelid, catid |
| search | content/search | 内容搜索 | GET参数: searinfo, type, status, catid |
| add | content/add | 添加内容 | POST: modelid, catid, title, content等 |
| edit | content/edit | 编辑内容 | GET: id, modelid |
| del | content/del | 删除内容 | POST: ids[] |
| baidu_push | content/baidu_push | 百度推送 | POST: ids[] |
| remove | content/remove | 移动内容 | POST: ids, catid |
| copy | content/copy | 复制内容 | POST: ids, catid |

#### 栏目管理 (category.class.php)

| 方法 | 路由 | 说明 |
|------|------|------|
| init | category/init | 栏目列表 |
| add | category/add | 添加栏目 |
| adds | category/adds | 批量添加栏目 |
| edit | category/edit | 编辑栏目 |
| delete | category/delete | 删除栏目 |
| order | category/order | 栏目排序 |
| page_content | category/page_content | 单页内容编辑 |

#### 模型管理 (module.class.php)

| 方法 | 路由 | 说明 |
|------|------|------|
| init | module/init | 模型列表 |
| add | module/add | 添加模型 |
| edit | module/edit | 编辑模型 |
| delete | module/delete | 删除模型 |
| import | module/import | 导入模型 |
| export | module/export | 导出模型 |

#### 会员管理 (member.class.php)

| 方法 | 路由 | 说明 |
|------|------|------|
| init | member/init | 会员列表 |
| add | member/add | 添加会员 |
| edit | member/edit | 编辑会员 |
| del | member/del | 删除会员 |
| check | member/check | 待审核会员 |
| lock | member/lock | 锁定会员 |
| unlock | member/unlock | 解锁会员 |
| recharge | member/recharge | 在线充值 |

### 前台展示接口

#### 站点首页 (index/index)

| 方法 | 说明 | URL模式 |
|------|------|---------|
| init | 首页 | /index/init 或 / |
| lists | 栏目列表页 | /catdir/ 或 /index/lists/catid/1 |
| show | 内容详情页 | /catdir/id.html 或 /index/show/catid/1/id/1 |

### 会员中心接口 (member/index)

| 方法 | 路由 | 说明 |
|------|------|------|
| init | 会员首页 | /member/init |
| register | 注册 | /member/register |
| login | 登录 | /member/login |
| logout | 退出 | /member/logout |
| info | 个人资料 | /member/info |
| password | 修改密码 | /member/password |
| myhome | 我的主页 | /member/myhome |
| member_content | 我的内容 | /member/member_content |
| member_pay | 消费记录 | /member/member_pay |

### 微信模块接口 (wechat/index)

| 方法 | 说明 | 路由 |
|------|------|------|
| index | 入口 | /wechat/index/index |
| callback | 回调 | /wechat/index/callback |

### 模板标签接口

#### 常用模板标签

```html
<!-- 栏目调用 -->
{yzm:category catid="1" loop="10"}
  <a href="{$category.url}">{$category.catname}</a>
{/yzm:category}

<!-- 内容列表 -->
{yzm:content catid="1" loop="10" order="id DESC"}
  <a href="{$content.url}">{$content.title}</a>
{/yzm:content}

<!-- 频道列表 -->
{yzm:channel type="top" loop="10"}
  <a href="{$channel.url}">{$channel.catname}</a>
{/yzm:channel}

<!-- 导航 -->
{yzm:nav loop="10"}
  <a href="{$nav.url}">{$nav.catname}</a>
{/yzm:nav}

<!-- 链接 -->
{yzm:link type="1" loop="10"}
  <a href="{$link.url}" target="_blank">{$link.name}</a>
{/yzm:link}

<!-- 广告 -->
{yzm:adver id="1"}
  {$adver.content}
{/yzm:adver}

<!-- 投票 -->
{yzm:vote id="1"}
  <form>{$vote.content}</form>
{/yzm:vote}
```

#### 模板变量

| 变量 | 说明 | 示例 |
|------|------|------|
| `{$site_name}` | 网站名称 | 配置的网站名称 |
| `{$site_keyword}` | 网站关键词 | 配置的SEO关键词 |
| `{$site_description}` | 网站描述 | 配置的SEO描述 |
| `{$SEO_TITLE}` | SEO标题 | 页面SEO标题 |
| `{$category.catname}` | 栏目名称 | 当前栏目名 |
| `{$content.title}` | 内容标题 | 文章标题 |
| `{$content.content}` | 内容正文 | 文章内容 |
| `{$content.thumb}` | 缩略图 | 文章缩略图 |
| `{$content.url}` | 内容URL | 文章链接 |
| `{$content.inputtime}` | 发布时间 | 格式化的日期 |
| `{$yzm.csrf_token}` | CSRF令牌 | 表单令牌 |

### 钩子接口

系统支持以下钩子位置：

| 钩子名称 | 触发时机 | 参数 |
|---------|---------|------|
| `content_add` | 内容添加后 | $id, $data |
| `content_edit` | 内容修改后 | $id, $data |
| `content_delete` | 内容删除后 | $id |
| `member_register` | 会员注册后 | $userid, $data |
| `member_login` | 会员登录后 | $userid, $username |

### API 扩展接口

系统支持通过 `application/api/` 目录扩展 API 接口。

```php
// application/api/controller/index.class.php
defined('IN_YZMPHP') or exit('Access Den Den');

class index{
    
    public function init() {
        return_json(array('status' => 1, 'message' => 'API OK'));
    }
    
    // 自定义API方法
    public function custom_api() {
        // 处理逻辑
        return_json($result);
    }
}
```

访问方式：`/index.php?s=api/index/custom_api`