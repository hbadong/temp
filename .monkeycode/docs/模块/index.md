# application/index/ - 前台展示模块

前台展示模块负责处理网站所有面向访客的页面展示，包括首页、栏目列表页、内容详情页等。

## 结构

```
application/index/
├── controller/               # 控制器目录
│   └── index.class.php       # 主控制器
│       ├── init()            # 首页
│       ├── lists()           # 栏目列表页
│       └── show()            # 内容详情页
├── model/                    # 数据模型
│   └── content.class.php     # 内容模型
├── view/                     # 视图模板目录
│   └── default/              # 默认主题
│       ├── index.html        # 首页模板
│       ├── list_*.html       # 列表页模板
│       ├── show_*.html       # 内容页模板
│       └── config.php        # 主题配置
└── common/                   # 公共资源
    ├── function/
    └── language/
```

## 关键文件

| 文件 | 目的 |
|------|------|
| `controller/index.class.php` | 前台核心控制器，处理页面路由和展示逻辑 |
| `model/content.class.php` | 内容模型，提供内容相关的数据操作 |
| `view/default/` | 默认主题模板目录 |

## URL 路由对应

| URL 模式 | 控制器 | 方法 | 说明 |
|----------|--------|------|------|
| `/` 或 `/index/init` | index | init | 网站首页 |
| `/catdir/` 或 `/index/lists/catid/1` | index | lists | 栏目列表页 |
| `/catdir/id.html` 或 `/index/show/catid/1/id/1` | index | show | 内容详情页 |

## 控制器方法

### init() - 首页

```php
public function init() {
    $site = get_config();           // 获取站点配置
    $seo_title = $site['site_name'];  // SEO标题
    $keywords = $site['site_keyword']; // 关键词
    $description = $site['site_description'];  // 描述
    include template('index', 'index');  // 渲染模板
}
```

### lists() - 栏目列表页

```php
public function lists() {
    $catid = isset($_GET['catid']) ? intval($_GET['catid']) : 0;
    $catinfo = get_category($catid);  // 获取栏目信息
    
    // SEO 设置
    $seo_title = $catinfo['seo_title'] ?: $catinfo['catname'];
    
    // 单页面处理
    if($catinfo['type'] == 1) {
        $r = D('page')->where(array('catid'=>$catid))->find();
    }
    
    // 渲染模板
    include template('index', $template);
}
```

### show() - 内容详情页

```php
public function show() {
    $catid = isset($_GET['catid']) ? intval($_GET['catid']) : 0;
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    // 获取内容数据
    $tablename = get_model($modelid);
    $data = D($tablename)->where(array('id'=>$id))->find();
    
    // 会员权限检测
    if($data['groupids_view']) {
        $groupid = intval(get_cookie('_groupid'));
        if(!$groupid || $groupid < $data['groupids_view']) {
            showmsg(L('insufficient_authority'), 'stop');
        }
    }
    
    // 阅读收费检测
    if($data['readpoint']) {
        $allow_read = content::check_readpoint($data);
    }
    
    include template('index', $template);
}
```

## 模板渲染

使用 `template()` 函数渲染模板：

```php
/**
 * @param string $module 模块名
 * @param string $template 模板名（不含 .html）
 * @param int $cache 缓存时间（秒）
 */
template('index', 'index');      // 渲染 application/index/view/default/index.html
template('index', 'list_news'); // 渲染 application/index/view/default/list_news.html
```

## 模板变量

控制器通过 `extract()` 将数据展开为模板变量：

| 变量 | 来源 | 说明 |
|------|------|------|
| `$site` | `get_config()` | 站点配置数组 |
| `$category` 或 `$catinfo` | `get_category($catid)` | 栏目信息 |
| `$data` 或 `$content` | 内容表查询结果 | 内容数据 |
| `$seo_title` | 拼接生成 | SEO标题 |
| `$keywords` | 栏目或站点配置 | 页面关键词 |
| `$description` | 栏目或站点配置 | 页面描述 |

## 依赖

**本模块依赖**:
- `yzmphp/core/class/yzm_tpl.class.php` - 模板引擎
- `yzmphp/core/class/yzm_tag.class.php` - 标签解析
- `application/admin/model/content_model.class.php` - 内容数据模型
- `common/function/` - 全局函数

**依赖本模块的**:
- 前端浏览器访问