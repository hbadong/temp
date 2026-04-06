# YzmCMS 开发者指南

## 项目目的

YzmCMS 是一款轻量级开源内容管理系统，采用自主研发的 YZMPHP 框架开发。作为一个全能型建站系统，它可以满足企业网站、新闻网站、个人博客、门户网站、行业网站、电子商城等多种类型的网站需求。

**核心职责**:
- 提供完善的内容管理功能（栏目、内容、模型、字段）
- 实现会员系统和支付系统
- 支持多语言和模板主题
- 内置评论、标签、关键词链接等增强功能
- 提供便捷的二次开发接口

## 环境搭建

### 前置条件

- PHP 5.2+（推荐 PHP7+ 或 PHP8）
- MySQL 5.0+
- Web 服务器（Nginx/Apache/IIS）

### 安装步骤

```bash
# 1. 克隆/下载源码到 Web 根目录
# 2. 访问 http://your-domain/install/
# 3. 按照安装向导完成安装
#    - 检查环境要求
#    - 设置数据库连接
#    - 创建管理员账号
# 4. 安装完成后删除 install 目录
```

### URL 模式配置

在 `index.php` 中修改 `URL_MODEL`:

```php
// URL模式: 0=>mca兼容模式，1=>s简化模式，2=>REWRITE模式，3=>SEO模式，4=>PATHINFO模式
define('URL_MODEL', '3');  // 推荐使用 SEO 模式
```

### 缓存配置

在 `common/config/config.php` 中配置缓存类型:

```php
// 缓存类型: file, redis, memcache
'cache_type' => 'file',

// 文件缓存配置
'file_config' => array(
    'cache_dir' => YZMPHP_PATH.'cache/cache_file/',
    'suffix' => '.cache.php',
    'mode' => '2',
),

// Redis缓存配置（可选）
'redis_config' => array(
    'host' => '127.0.0.1',
    'port' => 6379,
    'password' => '',
    'select' => 0,
    'timeout' => 0,
    'expire' => 3600,
    'persistent' => false,
    'prefix' => '',
),
```

## 开发工作流

### 项目结构规范

```
application/
├── admin/           # 后台管理（模块名: admin）
│   ├── controller/  # 控制器（继承 common.class.php）
│   ├── model/       # 数据模型
│   └── view/        # 视图模板
├── index/          # 前台展示（模块名: index）
├── member/         # 会员中心（模块名: member）
└── custom/          # 自定义模块（模块名: custom）
```

### 控制器开发规范

```php
<?php
// application/custom/controller/example.class.php

defined('IN_YZMPHP') or exit('Access Denied');

// 加载公共控制器（后台控制器必须加载）
yzm_base::load_controller('common', 'admin', 0);

class example extends common {

    // 初始化方法（可选）
    public function __construct() {
        parent::__construct();  // 调用父类构造方法进行权限验证
    }

    /**
     * 默认方法
     */
    public function init() {
        $data = D('mytable')->where(array('status' => 1))->select();
        include $this->admin_tpl('example_list');
    }

    /**
     * 添加数据
     */
    public function add() {
        if(is_post()) {
            // 处理 POST 提交
            $data = $_POST;
            $data['createtime'] = SYS_TIME;
            
            $id = D('mytable')->insert($data);
            if($id) {
                showmsg(L('operation_success'), U('init'), 1);
            } else {
                showmsg(L('operation_failure'), 'stop');
            }
        } else {
            // 显示表单
            include $this->admin_tpl('example_add');
        }
    }

    /**
     * 编辑数据
     */
    public function edit() {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if(is_post()) {
            $data = $_POST;
            if(D('mytable')->update($data, array('id' => $id))) {
                showmsg(L('operation_success'), U('init'), 1);
            } else {
                showmsg(L('operation_failure'));
            }
        } else {
            $data = D('mytable')->where(array('id' => $id))->find();
            include $this->admin_tpl('example_edit');
        }
    }

    /**
     * 删除数据
     */
    public function delete() {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if(D('mytable')->delete(array('id' => $id))) {
            return_json(array('status' => 1, 'message' => L('operation_success')));
        } else {
            return_json(array('status' => 0, 'message' => L('operation_failure')));
        }
    }

    /**
     * 私有方法（不可访问，以 _ 开头）
     */
    private function _helper() {
        // 内部辅助方法
    }
}
```

### 模型开发规范

```php
<?php
// application/custom/model/example.class.php

defined('IN_YZMPHP') or exit('Access Denied');

class example {

    /**
     * 获取列表
     */
    public function get_list($where = array(), $order = 'id DESC', $limit = 20) {
        return D('mytable')->where($where)->order($order)->limit($limit)->select();
    }

    /**
     * 获取单条
     */
    public function get_one($id) {
        return D('mytable')->where(array('id' => $id))->find();
    }

    /**
     * 插入数据
     */
    public function add($data) {
        return D('mytable')->insert($data);
    }

    /**
     * 更新数据
     */
    public function update($id, $data) {
        return D('mytable')->update($data, array('id' => $id));
    }

    /**
     * 删除数据
     */
    public function delete($id) {
        return D('mytable')->delete(array('id' => $id));
    }
}
```

### 视图模板开发规范

```php
<?php
// application/custom/view/example_list.html
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>示例列表</title>
</head>
<body>
    <div class="wrap">
        <div class="table-list">
            <table width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th width="50">ID</th>
                        <th>标题</th>
                        <th>创建时间</th>
                        <th width="150">操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($data as $v) { ?>
                    <tr>
                        <td><?php echo $v['id']; ?></td>
                        <td><?php echo $v['title']; ?></td>
                        <td><?php echo date('Y-m-d H:i:s', $v['createtime']); ?></td>
                        <td>
                            <a href="<?php echo U('edit', array('id' => $v['id'])); ?>">编辑</a> |
                            <a href="<?php echo U('delete', array('id' => $v['id'])); ?>" class="J_ajax_del">删除</a>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
```

### 创建新模型步骤

1. **创建数据表**（在数据库中创建）

```sql
CREATE TABLE `yzmcms_mytest` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text,
  `status` tinyint(1) DEFAULT 1,
  `createtime` int(10) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
```

2. **创建控制器**（`application/custom/controller/mytest.class.php`）

3. **创建模型**（`application/custom/model/mytest.class.php`）

4. **创建视图**（`application/custom/view/`）

5. **注册菜单**（后台菜单管理中配置）

### 添加新的内容模型

1. 后台 → 模型管理 → 添加模型
2. 填写模型信息（名称、别名、表名）
3. 为模型添加字段
4. 创建对应的数据表
5. 在栏目管理中为栏目绑定模型

### 模板标签使用

#### 内容列表标签

```html
{yzm:content catid="1,2,3" loop="10" order="id DESC" thumb="1" page="true"}
<div class="item">
    <a href="{$content.url}">
        {if $content.thumb}<img src="{$content.thumb}">{/if}
        <h3>{$content.title}</h3>
    </a>
    <p>{$content.description}</p>
    <span>{$content.inputtime|date='Y-m-d'}</span>
</div>
{/yzm:content}

<!-- 分页 -->
{if $content_page}
<div class="pagination">
    {$content_page}
</div>
{/if}
```

#### 栏目标签

```html
{yzm:category catid="0" type="top" loop="10"}
<a href="{$category.url}">{$category.catname}</a>
{/yzm:category}

<!-- 子栏目 -->
{yzm:category catid="1" type="son" loop="10"}
<a href="{$category.url}">{$category.catname}</a>
{/yzm:category}
```

#### 导航标签

```html
{yzm:nav type="top" loop="10" current="auto"}
<a href="{$nav.url}" {if $nav.current}class="active"{/if}>{$nav.catname}</a>
{/yzm:nav}
```

## 常见任务

### 添加新 API 端点

**需修改的文件**:
1. `application/api/controller/[name].class.php` - API 控制器
2. `application/api/model/[name].class.php` - API 模型（可选）

**步骤**:
1. 在 `application/api/controller/` 下创建控制器
2. 继承 `common` 类（如需权限验证）
3. 实现 API 方法，使用 `return_json()` 返回数据
4. 访问：`/index.php?s=api/[name]/[action]`

### 添加自定义函数

**步骤**:
1. 在 `application/common/function/` 下创建函数文件
2. 在需要的地方 include 或使用 `yzm_base::load_common()`

### 扩展数据库操作

使用 DB 类的链式调用：

```php
// 基本查询
D('table')->where(array('id' => 1))->find();

// 条件查询
D('table')->where(array('status' => 1, 'catid' => array(1, 2, 3, 'in')))->select();

// 模糊查询
D('table')->where(array('title' => array('%$keyword%', 'like')))->select();

// 统计
$total = D('table')->where($where)->total();

// 插入
$id = D('table')->insert($data);

// 更新
D('table')->update($data, array('id' => $id));

// 删除
D('table')->delete(array('id' => $id));

// 关联查询
D('content')
    ->alias('c')
    ->field('c.*, c_cat.catname')
    ->join('yzmcms_category c_cat ON c.catid = c_cat.catid', 'LEFT')
    ->where($where)
    ->order('id DESC')
    ->limit($page->limit())
    ->select();
```

### 添加后台菜单

1. 登录后台
2. 菜单管理 → 添加菜单
3. 填写菜单信息（名称、链接、图标、排序）
4. 为角色分配菜单权限

### 模板继承

```html
<!-- layout.html 基础模板 -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{$SEO_TITLE}</title>
    {block name="head"}{/block}
</head>
<body>
    <header>{block name="header"}{/block}</header>
    <main>{block name="main"}{/block}</main>
    <footer>{block name="footer"}{/block}</footer>
</body>
</html>

<!-- index.html 继承模板 -->
{extend name="layout" /}
{block name="head"}
<style>/* 自定义样式 */</style>
{/block}
{block name="main"}
<div class="content">内容</div>
{/block}
```

## 编码规范

### 文件组织
- 每个文件只包含一个类（除非紧密相关）
- 文件名与类名对应
- 相关文件放在同一目录

### 命名约定

| 类型 | 约定 | 示例 |
|------|------|------|
| 控制器 | 小写，文件名 `.class.php` | `content.class.php` |
| 模型 | 首字母大写 | `ContentModel.class.php` |
| 方法 | 驼峰命名 | `getContentList()` |
| 数据库表 | `yzm_` 前缀 + 下划线命名 | `yzm_content` |
| 模板 | 小写 + 下划线 | `content_list.html` |

### 安全规范

1. **永远不要**直接使用 `$_POST` 或 `$_GET`，先处理：
```php
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$title = isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '';
```

2. **使用表单验证类**：
```php
yzm_base::load_sys_class('form', '', 0);
$form = new form();
// 生成验证码
$form->yzm();
// 验证令牌
$form->check_token();
```

3. **SQL 注入防护**：
- 使用 PDO 预编译
- 数字类型强制 intval
- 字符串类型使用 addslashes 或 htmlspecialchars

### 错误处理

```php
// 显示错误消息并跳转
showmsg(L('operation_success'), U('init'), 1);

// AJAX 返回
return_json(array('status' => 1, 'message' => L('operation_success')));

// 致命错误终止
application::halt('Error message', 404);

// 抛出异常
throw new Exception('Error message');
```

### 分页使用

```php
// 控制器中
$total = D('mytable')->where($where)->total();
$page = new page($total, 15);
$data = D('mytable')->where($where)->order('id DESC')->limit($page->limit())->select();

// 视图中
{$page}
```

## 数据库迁移

### 创建数据表

建议使用独立 SQL 文件或phinx等迁移工具管理数据库变更。

### 常用 SQL 操作

```php
// 创建表（参考系统模型）
$sql = "CREATE TABLE `yzmcms_newtable` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8";

// 执行SQL
M()->query($sql);
```

## 调试技巧

### 开启调试模式

在 `index.php` 中：

```php
define('APP_DEBUG', true);  // 开发阶段开启
```

### 使用 Debug 类

```php
// 添加调试信息
debug::addmsg('调试信息');

// 查看所有调试信息（在页面底部）
debug::message();
```

### 查看 SQL 日志

在数据库配置中开启日志记录（需要修改数据库类）。

### 常用调试输出

```php
// 输出变量并终止
print_r($data);
exit;

// 写入日志
file_put_contents('/tmp/debug.log', var_export($data, true));
```