# application/admin/ - 后台管理模块

后台管理模块是 YzmCMS 的核心组成部分，提供了网站内容管理、用户管理、系统配置等全部后台功能。

## 结构

```
application/admin/
├── controller/               # 控制器目录
│   ├── index.class.php       # 后台首页
│   ├── common.class.php      # 基础控制器（含权限验证）
│   ├── content.class.php     # 内容管理
│   ├── category.class.php     # 栏目管理
│   ├── module.class.php      # 模型管理
│   ├── model_field.class.php  # 字段管理
│   ├── admin_manage.class.php # 管理员管理
│   ├── role.class.php        # 角色权限管理
│   ├── member.class.php      # 会员管理
│   ├── menu.class.php        # 菜单管理
│   ├── tag.class.php         # 标签管理
│   ├── urlrule.class.php     # URL规则管理
│   ├── system_manage.class.php # 系统设置
│   ├── database.class.php    # 数据库管理
│   ├── sitemodel.class.php   # 站点模型管理
│   ├── sql.class.php         # SQL执行
│   ├── clear_cache.class.php  # 缓存清理
│   ├── update_urls.class.php  # URL更新
│   ├── sitemap.class.php     # 站点地图
│   ├── keyword_link.class.php # 关键词链接
│   ├── admin_log.class.php   # 管理日志
│   └── ...
├── model/                    # 数据模型
│   ├── admin.class.php       # 管理员模型
│   └── content_model.class.php # 内容模型
├── view/                     # 视图模板
│   ├── index.html            # 后台首页
│   ├── content_list.html     # 内容列表
│   ├── content_add.html      # 内容添加
│   ├── content_edit.html     # 内容编辑
│   ├── category_list.html    # 栏目列表
│   ├── category_add.html     # 栏目添加
│   ├── category_edit.html    # 栏目编辑
│   ├── member_list.html      # 会员列表
│   ├── system_set.html       # 系统设置
│   └── ...
└── common/                   # 公共资源
    ├── lib/                  # 公共类库
    │   ├── content_form.class.php  # 内容表单处理
    │   ├── page_form.class.php     # 单页表单处理
    │   ├── module_api.class.php    # 模块API
    │   ├── sql.class.php          # SQL处理
    │   └── update.class.php        # 更新类
    ├── function/             # 公共函数
    │   └── function.php      # 后台全局函数
    └── language/             # 语言包
```

## 关键文件

| 文件 | 目的 |
|------|------|
| `controller/common.class.php` | 基础控制器，所有后台控制器必须继承，包含权限验证、CSRF防护等 |
| `controller/index.class.php` | 后台首页控制器 |
| `controller/content.class.php` | 内容管理核心控制器，处理内容的增删改查 |
| `model/content_model.class.php` | 内容模型处理类，封装内容相关的数据库操作 |
| `common/lib/content_form.class.php` | 内容表单生成和处理类 |

## 控制器规范

所有后台控制器都必须：

1. **继承 common 类**:
```php
class content extends common {
    public function __construct() {
        parent::__construct();  // 权限验证等
    }
}
```

2. **使用 `is_post()` 判断表单提交**:
```php
if(is_post()) {
    // 处理表单提交
} else {
    // 显示表单
}
```

3. **使用 `admin_tpl()` 加载模板**:
```php
include $this->admin_tpl('content_list');
```

4. **使用语言包**:
```php
showmsg(L('operation_success'), U('init'), 1);
```

## 权限控制

权限控制通过以下机制实现：

1. **角色权限表** (`yzmcms_admin_role_priv`)
2. **控制器继承** - `common` 类自动验证登录状态
3. **后台菜单** - 菜单关联角色权限

```php
// 检查特定权限
private function _all_priv() {
    if($_SESSION['roleid'] == 1) return true;  // 超级管理员
    $res = D('admin_role_priv')->field('roleid')
        ->where(array('roleid'=>$_SESSION['roleid'],'m'=>'admin','c'=>'content','a'=>'all_content'))
        ->find();
    return $res ? true : false;
}
```

## 依赖

**本模块依赖**:
- `yzmphp/core/class/` - 框架核心类
- `common/config/config.php` - 配置文件
- `application/index/` - 前台模块（部分功能依赖）

**依赖本模块的**:
- 无（后台模块不依赖其他应用模块）