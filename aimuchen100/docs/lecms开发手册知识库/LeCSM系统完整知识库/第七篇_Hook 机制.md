# LeCMS 开发知识库 — Hook 机制
> 对应原文档：第 七 篇

> **版本**: 1.0
> **更新日期**: 2026-07-25
> **框架版本**: LeCMS 3.0.3 + XiunoPHP 1.0.0
> **PHP 版本**: 5.4.0 - 8.2.x
> **数据库**: MySQL 5.6+ / MariaDB
> **协议**: MIT License

---

## 本章包含章节

- 34. Hook 工作原理
- 34.1 Hook 的本质
- 34.2 Hook 查找位置
- 34.3 Hook 编译示例
- 35. Hook 编译流程详解
- 35.1 process_hook() 方法源码分析
- 35.2 编译流程可视化
- 35.3 clear_code() 清理机制
- 36. Hook 命名规范
- 36.1 命名格式
- 36.2 位置与时机对照表
- 36.3 示例速查表
- 37. Hook 完整列表
- 37.1 控制器层 Hook（前台）
- 37.2 模型层 Hook
- 37.3 后台 Hook
- 37.4 模板 Hook
- 38. Hook 使用技巧与注意事项
- 38.1 编写规范
- 38.2 多插件同名 Hook 合并
- 38.3 常见陷阱

---

## 第七篇：Hook 机制

---

## 34. Hook 工作原理

### 34.1 Hook 的本质

LeCMS 的 Hook 是一种**编译时 Hook**：

```
核心源码中的标记:
    // hook admin_admin_control_init_nav_after.php

编译阶段:
    1. 扫描所有已启用插件
    2. 查找 plugin/插件名/hook/admin_admin_control_init_nav_after.php
    3. 将 Hook 文件内容（去掉 <?php 和 exit 声明）插入标记位置
    4. 写入 runcache/ 缓存文件

运行时:
    直接执行编译后的文件（包含 Hook 内容）
    无额外函数调用开销 → 性能零损耗
```

### 34.1.1 Hook 应用统计

<!-- 新增 -->
根据对 LeCMS 核心源码的全面扫描，全系统共发现 **844 个 Hook 点**，分布如下：

| 分类 | 数量 | 占比 |
|------|------|------|
| Block 模块 | 243个 | 28.8% |
| CMS 内容模型 | 153个 | 18.1% |
| URL 解析 | 84个 | 10.0% |
| 用户系统 | 56个 | 6.6% |
| 站点地图 | 34个 | 4.0% |
| 分类管理 | 33个 | 3.9% |
| 个人中心 | 32个 | 3.8% |
| 其他模块 | 209+ | 24.8% |
| **总计** | **844个** | **100%** |

Block 模块和 CMS 内容模型合计占比接近 47%，是 Hook 使用最密集的区域。
<!-- 新增结束 -->

### 34.2 Hook 查找位置

```
1. plugin/{插件名}/{hook文件名}       ← 插件根目录
2. plugin/{插件名}/hook/{hook文件名}  ← hook 子目录（推荐）
```

### 34.3 Hook 编译示例

**源文件** (`base_control.class.php`)：
```php
class base_control extends control {
    // hook base_control_construct_before.php
    function __construct() {
        $this->_cfg = $this->runtime->xget();
        // ...
    }
}
```

**Hook 文件** (`plugin/le_site_group/hook/base_control_construct_before.php`)：
```php
<?php
// 站点识别和配置合并
$http_host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
$_ENV['_current_site_id'] = 0;

if($http_host && isset($this)) {
    $site_map = $this->kv->get('site_domain_map');
    // ...
}
?>
```

**编译后** (`runcache/lecms_control/base_control.class.php`)：
```php
class base_control extends control {
    // Hook 内容已插入
    $http_host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
    $_ENV['_current_site_id'] = 0;

    if($http_host && isset($this)) {
        // ... Hook 代码
    }

    function __construct() {
        $this->_cfg = $this->runtime->xget();
        // ...
    }
}
```

## 35. Hook 编译流程详解

### 35.1 process_hook() 方法源码分析

LeCMS 的 Hook 编译由 `core.class.php` 中的 `process_hook()` 方法实现，整个流程可分为以下步骤：

```
1. 核心代码中预留标记
   // hook xxx_before.php

2. 编译时 process_all() 检测到 hook 标记
   遍历所有已缓存的 PHP 文件

3. 正则匹配 hook 标记
   模式: #\t*\/\/\s*hook\s+([\w\.]+)[\r\n]#
   捕获组 $1: hook 文件名（如 xxx_before.php）

4. 遍历所有已启用插件，查找对应文件
   查找位置1: plugin/{插件名}/{匹配的文件名}
   查找位置2: plugin/{插件名}/hook/{匹配的文件名}

5. 读取文件内容，调用 clear_code() 清理头尾
   移除 <?php、?>、exit 声明等

6. 将清理后的代码替换原 hook 标记行

7. 写入编译缓存文件
   路径: runcache/lecms_control/ 或 runcache/lecms_model/
```

**核心正则表达式：**
```php
// 匹配 hook 标记行
$pattern = '#\t*\/\/\s*hook\s+([\w\.]+)[\r\n]#';

// 示例匹配结果:
// // hook index_control_index_before.php
// // hook cms_content_model_format_before.php
```

**clear_code() 清理逻辑：**
```php
// 移除的内容:
// - <?php 和 ?> 标签
// - exit; 或 exit(); 语句
// - 文件头尾的空白字符
// - 注意: return 语句也会被清理（Hook 中不应使用 return）
```

<!-- 新增 -->
#### process_hook() 方法完整源码

**文件路径：** `lecms/xiunophp/lib/core.class.php`

```php
public static function process_hook($matches) {
    $str = "\n";

    // 检查插件目录是否存在或全局禁用
    if(!is_dir(PLUGIN_PATH) || !empty($_ENV['_config']['plugin_disable'])) {
        return $str;
    }

    // 获取已启用的插件列表
    $plugins = core::get_plugins();
    if(empty($plugins['enable'])) {
        return $str;
    }

    $plugin_enable = array_keys($plugins['enable']);

    foreach($plugin_enable as $p) {
        // 查找1: 插件根目录下的hook文件
        $file = PLUGIN_PATH.$p.'/'.$matches[1];
        if(is_file($file)) {
            $s = file_get_contents($file);
            $str .= self::clear_code($s);
        }

        // 查找2: 插件hook子目录下的文件
        $file = PLUGIN_PATH.$p.'/hook/'.$matches[1];
        if(is_file($file)) {
            $s = file_get_contents($file);
            $str .= self::clear_code($s);
        }
    }

    return $str;
}
```

**源码逐行解读：**

| 代码行 | 作用 |
|--------|------|
| `$str = "\n";` | 初始化返回字符串，默认换行保持代码格式 |
| `is_dir(PLUGIN_PATH)` | 检查插件目录是否存在，不存在则跳过 |
| `$_ENV['_config']['plugin_disable']` | 检查全局插件禁用开关 |
| `core::get_plugins()` | 从配置中读取已启用的插件列表 |
| `array_keys($plugins['enable'])` | 提取启用插件的名称数组 |
| 查找位置1 | `plugin/{插件名}/{hook文件名}` — 插件根目录 |
| 查找位置2 | `plugin/{插件名}/hook/{hook文件名}` — hook子目录（推荐） |
| `self::clear_code($s)` | 清理PHP标签和exit语句后追加到结果 |

**关键设计决策：**
1. 两个查找位置都支持，hook子目录优先级高于根目录（都找到时会先追加根目录的代码，再追加子目录的代码）
2. 按插件rank升序遍历（rank小的先执行），这是由 `get_plugins()` 的排序决定的
3. 返回的 `$str` 默认以换行开头，确保注入的代码在新行上
<!-- 新增结束 -->

### 35.2 编译流程可视化

```
原始核心代码                          编译后代码
─────────────────────────────────────────────────────────────
// hook xxx_before.php    →    <?php
// 业务代码 A                  // ====== Hook 注入: 插件A ======
// hook xxx_after.php     →    $hook_code_from_plugin_a;
// 业务代码 B                  // ====== 业务代码 A ======
// 业务代码 C                  // ====== Hook 注入: 插件B ======
                              $hook_code_from_plugin_b;
                              // ====== 业务代码 B ======
                              // ====== 业务代码 C ======
```

**多插件 Hook 合并示例：**
```php
// 如果插件A和插件B都定义了 index_control_index_before.php
// 按 rank 升序（从小到大）依次合并
// rank=100 的插件先执行，rank=200 的插件后执行
```

<!-- 新增 -->
#### Hook 编译时机详细对照表

Hook 在系统启动的多个阶段都会被编译处理，每个阶段的触发时机和编译范围如下：

| 阶段 | 触发方法 | 编译范围 | 标记格式 | 说明 |
|------|----------|----------|----------|------|
| **控制器编译** | `core::process_all()` | `lecms/control/` 下所有控制器 | `// hook xxx.php` | 最常用的 Hook 类型 |
| **模型编译** | `core::process_all()` | `lecms/model/` 下所有模型 | `// hook xxx.php` | 内容/用户/分类等模型 |
| **语言包编译** | `core::init_lang()` | 语言包文件 | `// hook xxx.php` | 多语言扩展 |
| **函数库编译** | `core::init_misc()` | `misc.func.php` 等 | `// hook xxx.php` | 公共函数扩展 |
| **视图/模板编译** | `view.class.php` | 模板文件中的 `{hook:xxx}` | `{hook:xxx}` | 模板 Block 层 Hook |

**各阶段编译调用顺序（系统启动流程）：**
```
1. core::init()                    ← 系统初始化入口
2.   core::init_config()           ← 加载配置
3.   core::init_lang()             ← 语言包编译（语言包 Hook）
4.   core::init_misc()             ← 函数库编译（函数库 Hook）
5.   core::process_all()           ← 控制器+模型编译
6.     control/xxx_control.php     ← 控制器 Hook
7.     model/xxx_model.php         ← 模型 Hook
8.   view::xxx()                   ← 视图渲染（模板 Hook）
```
<!-- 新增结束 -->

### 35.3 clear_code() 清理机制

`clear_code()` 是 Hook 编译中的关键清理函数，它的作用是确保 Hook 代码能安全地嵌入到核心代码中：

```php
// Hook 文件原始内容:
<?php
// 这是一段注释
$value = 'test';
exit;
?>

// clear_code() 处理后:
// 这是一段注释
$value = 'test';

// 移除的部分:
// 1. <?php 标签
// 2. ?> 结束标签
// 3. exit; 语句（防止 Hook 终止后续代码执行）
```

## 36. Hook 命名规范

### 36.1 命名格式

LeCMS 的 Hook 文件名遵循严格的命名规范，通过文件名即可知道 Hook 的触发位置和时机：

```
格式: {位置}_{时机}.php

位置 (Position): 标识 Hook 在哪个文件/类中
时机 (Timing): 标识 Hook 在哪个执行阶段触发
```

### 36.2 位置与时机对照表

**位置（Position）：**

| 位置 | 说明 | 示例 |
|------|------|------|
| `base_control` | 基础控制器 | 所有控制器继承的基类 |
| `index_control` | 首页控制器 | 前台首页相关 |
| `show_control` | 详情控制器 | 内容详情页 |
| `cate_control` | 分类控制器 | 分类列表页 |
| `tag_control` | 标签控制器 | 标签相关页面 |
| `search_control` | 搜索控制器 | 搜索结果页 |
| `user_control` | 用户控制器 | 用户中心 |
| `comment_control` | 评论控制器 | 评论相关 |
| `my_control` | 我的控制器 | 个人中心 |
| `runtime_model` | 运行时模型 | runtime 配置模型 |
| `category_model` | 分类模型 | 分类相关模型 |
| `cms_content_model` | CMS内容模型 | 内容核心模型 |
| `kv_model` | KV存储模型 | 键值对存储 |
| `models_model` | 模型管理模型 | 模型配置管理 |
| `admin_*_control` | 后台控制器 | 后台各模块控制器 |
| `admin_*_model` | 后台模型 | 后台各模块模型 |
| `block_{name}` | Block 模块 | 具体 Block 名称 |
| `misc_func` | 公共函数 | 公共工具函数 |

**时机（Timing）：**

| 时机 | 说明 | 示例 |
|------|------|------|
| `_before` | 在方法/逻辑执行之前 | `index_control_index_before.php` |
| `_after` | 在方法/逻辑执行之后 | `index_control_index_after.php` |
| `_start` | 在类初始化时 | `base_control_start.php` |
| `_end` | 在类结束时 | `base_control_after.php` |
| `_success` | 操作成功时 | `base_control_get_user_success.php` |
| `_failed` | 操作失败时 | `base_control_get_user_failed.php` |

### 36.3 示例速查表

| Hook 文件名 | 触发位置 | 触发时机 | 说明 |
|-------------|---------|---------|------|
| `base_control_start.php` | base_control | 类初始化 | 全局初始化 |
| `base_control_construct_before.php` | base_control | 构造函数前 | 构造前修改 |
| `base_control_construct_after.php` | base_control | 构造函数后 | 构造后修改 |
| `base_control_variable_after.php` | base_control | 变量设置后 | 环境变量修改 |
| `index_control_start.php` | index_control | 控制器启动 | 首页加载前 |
| `index_control_index_before.php` | index_control | index() 前 | 首页渲染前 |
| `index_control_index_seo_after.php` | index_control | index() SEO后 | 首页SEO修改 |
| `index_control_index_after.php` | index_control | index() 后 | 首页渲染后 |
| `cms_content_model_format_before.php` | cms_content_model | format() 前 | 内容格式化前 |
| `runtime_model_xdelete_after.php` | runtime_model | xdelete() 后 | 缓存删除后 |

<!-- 新增 -->
### 36.4 Hook 文件格式要求与实际示例

**格式要求：**

Hook 文件是标准的 PHP 文件，放置于插件的 `hook/` 子目录中。基本结构如下：

```php
<?php
// hook {hook文件名}.php

// 你的代码逻辑
// 可以访问当前作用域的所有变量和函数
// 不需要使用 return，代码直接执行
```

**格式要点：**
1. 文件头部注释行 `// hook {文件名}.php` 是编译时的识别标记，但实际编译依赖核心代码中的标记行
2. `<?php` 标签会被 `clear_code()` 自动移除，不是必需的
3. 不能使用 `return` 语句，会被 `clear_code()` 清理
4. 不能使用 `exit` 或 `exit()`，会被 `clear_code()` 清理
5. 代码直接写在文件顶层，不需要包裹在函数中

**实际示例 — 内容添加时处理自定义字段：**

```php
<?php
// hook admin_content_control_add_after.php

// 在后台添加内容时，处理自定义字段内容
$field_arr = $this->models_field->get_file_info_mid($this->_mid, $data, array('edit_cid_id'=>$edit_cid_id));
$field_js = '';
foreach ($field_arr as $f){
    isset($f['js']) && $field_js .= $f['js'];
}

$this->assign('field_arr', $field_arr);
$this->assign('field_js', $field_js);
```

**实际示例 — 内容页SEO自定义：**

```php
<?php
// hook show_control_index_seo_before.php

// 自定义SEO标题
if(isset($this->_cid) && $this->_cid == 1) {
    $this->_cfg['titles'] = '自定义首页标题 - ' . $this->_cfg['webname'];
}
```

**实际示例 — 修改列表数据：**

```php
<?php
// hook block_list_list_arr_after.php

// 在列表数据查询后，为每条数据添加自定义字段
if(!empty($list_arr)) {
    foreach($list_arr as $key => $val) {
        $list_arr[$key]['custom_field'] = format_custom_field($val['id']);
    }
}
```
<!-- 新增结束 -->

## 37. Hook 完整列表

以下是 LeCMS 核心代码中所有可用的 Hook 标记点，基于对核心源码的全面扫描。这些 Hook 标记存在于 `core.class.php`、各 `*_control.class.php` 和各 `*_model.class.php` 文件中。

### 37.1 控制器层 Hook（前台）

### 3.1 base_control 基础控制器钩子 (11个)

| Hook名称 | 触发位置 | 用途 |
|----------|----------|------|
| `base_control_start.php` | 类开始 | 初始化前的处理 |
| `base_control_variable_after.php` | 变量初始化后 | 修改变量配置 |
| `base_control_construct_before.php` | 构造函数前 | 用户认证前处理 |
| `base_control_get_user_before.php` | 获取用户前 | 用户数据获取前 |
| `base_control_get_user_success.php` | 获取用户成功 | 处理登录用户 |
| `base_control_get_user_failed.php` | 获取用户失败 | 处理未登录用户 |
| `base_control_get_user_after.php` | 获取用户后 | 用户后续处理 |
| `base_control_construct_after.php` | 构造函数后 | 初始化后处理 |
| `base_control_close_website_before.php` | 关闭网站检测前 | 自定义关闭逻辑 |
| `base_control_construct_close_website_after.php` | 关闭网站处理后 | 关闭页面渲染 |
| `base_control_after.php` | 类结束 | 清理工作 |

### 3.2 index_control 首页控制器钩子 (5个)

| Hook名称 | 触发位置 | 用途 |
|----------|----------|------|
| `index_control_start.php` | 类开始 | 首页初始化 |
| `index_control_index_before.php` | 首页渲染前 | 数据准备 |
| `index_control_index_seo_after.php` | SEO设置后 | 修改SEO信息 |
| `index_control_index_after.php` | 首页渲染后 | 后续处理 |
| `index_control_after.php` | 类结束 | 清理工作 |

### 3.3 show_control 内容详情控制器钩子 (7个)

| Hook名称 | 触发位置 | 用途 |
|----------|----------|------|
| `show_control_start.php` | 类开始 | 内容页初始化 |
| `show_control_index_before.php` | 内容渲染前 | 数据获取 |
| `show_control_index_param_after.php` | 参数处理后 | URL参数处理 |
| `show_control_index_get_show_after.php` | 内容获取后 | 内容数据处理 |
| `show_control_index_seo_before.php` | SEO设置前 | 准备SEO数据 |
| `show_control_index_seo_after.php` | SEO设置后 | 修改SEO信息 |
| `show_control_index_center.php` | 页面中心区域 | 页面主体渲染 |
| `show_control_index_after.php` | 内容渲染后 | 后续处理 |
| `show_control_after.php` | 类结束 | 清理工作 |

### 3.4 cate_control 分类页控制器钩子 (5个)

| Hook名称 | 触发位置 | 用途 |
|----------|----------|------|
| `cate_control_start.php` | 类开始 | 分类页初始化 |
| `cate_control_index_before.php` | 分类列表前 | 数据准备 |
| `cate_control_index_center.php` | 页面中心 | 列表渲染 |
| `cate_control_index_seo_after.php` | SEO设置后 | 修改SEO |
| `cate_control_index_after.php` | 分类列表后 | 后续处理 |
| `cate_control_after.php` | 类结束 | 清理工作 |

### 3.5 tag_control 标签页控制器钩子 (8个)

| Hook名称 | 触发位置 | 用途 |
|----------|----------|------|
| `tag_control_start.php` | 类开始 | 标签页初始化 |
| `tag_control_index_before.php` | 标签页渲染前 | 数据准备 |
| `tag_control_index_tags_after.php` | 标签列表后 | 处理标签列表 |
| `tag_control_index_seo_after.php` | SEO设置后 | 修改SEO |
| `tag_control_index_after.php` | 标签页渲染后 | 后续处理 |
| `tag_control_top_before.php` | 热门标签前 | 热门标签数据 |
| `tag_control_top_after.php` | 热门标签后 | 处理热门标签 |
| `tag_control_top_seo_after.php` | 热门标签SEO后 | 修改SEO |
| `tag_control_all_before.php` | 全部标签前 | 全部标签数据 |
| `tag_control_all_after.php` | 全部标签后 | 处理全部标签 |
| `tag_control_all_seo_after.php` | 全部标签SEO后 | 修改SEO |
| `tag_control_after.php` | 类结束 | 清理工作 |

### 3.6 search_control 搜索控制器钩子 (6个)

| Hook名称 | 触发位置 | 用途 |
|----------|----------|------|
| `search_control_start.php` | 类开始 | 搜索页初始化 |
| `search_control_index_before.php` | 搜索页渲染前 | 数据准备 |
| `search_control_index_center.php` | 页面中心 | 搜索结果渲染 |
| `search_control_index_after.php` | 搜索页渲染后 | 后续处理 |
| `search_control_so_before.php` | 搜索框渲染前 | 搜索表单准备 |
| `search_control_so_center.php` | 搜索框中心 | 搜索表单渲染 |
| `search_control_so_after.php` | 搜索框渲染后 | 后续处理 |
| `search_control_after.php` | 类结束 | 清理工作 |

### 3.7 comment_control 评论控制器钩子 (11个)

| Hook名称 | 触发位置 | 用途 |
|----------|----------|------|
| `comment_control_start.php` | 类开始 | 评论页初始化 |
| `comment_control_index_before.php` | 评论列表前 | 数据准备 |
| `comment_control_index_seo_before.php` | SEO设置前 | 准备SEO |
| `comment_control_index_seo_after.php` | SEO设置后 | 修改SEO |
| `comment_control_index_after.php` | 评论列表后 | 后续处理 |
| `comment_control_post_before.php` | 发表评论前 | 数据验证 |
| `comment_control_post_create_before.php` | 创建评论前 | 数据处理 |
| `comment_control_post_after.php` | 发表评论后 | 后续处理 |
| `comment_control_vcode_before.php` | 验证码前 | 验证准备 |
| `comment_control_vcode_after.php` | 验证码后 | 验证处理 |
| `comment_control_after.php` | 类结束 | 清理工作 |

### 3.8 user_control 用户控制器钩子 (25个)

| Hook名称 | 触发位置 | 用途 |
|----------|----------|------|
| `user_control_start.php` | 类开始 | 用户页初始化 |
| `user_control_index_before.php` | 用户首页前 | 数据准备 |
| `user_control_login_before.php` | 登录处理前 | 登录验证 |
| `user_control_login_post_before.php` | 登录提交前 | 表单处理 |
| `user_control_login_post_data_after.php` | 登录数据后 | 数据处理 |
| `user_control_login_post_check_after.php` | 登录验证后 | 验证结果 |
| `user_control_login_post_success.php` | 登录成功 | 成功处理 |
| `user_control_login_post_error.php` | 登录失败 | 错误处理 |
| `user_control_login_after.php` | 登录完成后 | 后续处理 |
| `user_control_register_before.php` | 注册处理前 | 注册验证 |
| `user_control_register_post_before.php` | 注册提交前 | 表单处理 |
| `user_control_register_post_data_after.php` | 注册数据后 | 数据处理 |
| `user_control_register_post_check_after.php` | 注册验证后 | 验证结果 |
| `user_control_register_post_create_failed.php` | 创建失败 | 失败处理 |
| `user_control_register_post_create_success.php` | 创建成功 | 成功处理 |
| `user_control_register_post_create_success_after.php` | 创建后 | 后续处理 |
| `user_control_register_post_after.php` | 注册提交后 | 后续处理 |
| `user_control_register_after.php` | 注册完成后 | 后续处理 |
| `user_control_forget_before.php` | 找回密码前 | 验证处理 |
| `user_control_forget_post_data_after.php` | 找回密码数据后 | 数据处理 |
| `user_control_forget_post_body_after.php` | 邮件内容后 | 邮件处理 |
| `user_control_forget_post_send_email_before.php` | 发送邮件前 | 邮件准备 |
| `user_control_forget_after.php` | 找回密码后 | 后续处理 |
| `user_control_vcode_before.php` | 验证码前 | 验证准备 |
| `user_control_vcode_after.php` | 验证码后 | 验证处理 |
| `user_control_after.php` | 类结束 | 清理工作 |

### 3.9 my_control 个人中心控制器钩子 (32个)

| Hook名称 | 触发位置 | 用途 |
|----------|----------|------|
| `my_control_start.php` | 类开始 | 个人中心初始化 |
| `my_control_index_before.php` | 个人中心首页前 | 数据准备 |
| `my_control_construct_before.php` | 构造函数前 | 初始化处理 |
| `my_control_construct_after.php` | 构造函数后 | 初始化完成 |
| `my_control_check_user_group_before.php` | 用户组检测前 | 权限检测 |
| `my_control_check_user_group_after.php` | 用户组检测后 | 权限处理 |
| `my_control_init_navigation_before.php` | 导航初始化前 | 准备导航 |
| `my_control_init_navigation_content_after.php` | 内容导航后 | 内容菜单 |
| `my_control_init_navigation_my_after.php` | 个人导航后 | 个人菜单 |
| `my_control_init_navigation_after.php` | 导航初始化后 | 导航完成 |
| `my_control_profile_before.php` | 资料编辑前 | 数据准备 |
| `my_control_profile_post_after.php` | 资料提交后 | 数据处理 |
| `my_control_profile_post_success.php` | 资料更新成功 | 成功处理 |
| `my_control_password_before.php` | 密码修改前 | 数据准备 |
| `my_control_password_post_data_after.php` | 密码数据后 | 数据处理 |
| `my_control_password_post_check_after.php` | 密码验证后 | 验证处理 |
| `my_control_password_post_after.php` | 密码提交后 | 后续处理 |
| `my_control_password_post_success.php` | 密码修改成功 | 成功处理 |
| `my_control_contents_before.php` | 我的内容前 | 内容列表 |
| `my_control_contents_where_after.php` | 内容查询后 | 数据处理 |
| `my_control_contents_post_after.php` | 内容操作后 | 后续处理 |
| `my_control_contents_post_del_success.php` | 删除成功 | 成功处理 |
| `my_control_contents_after.php` | 我的内容后 | 后续处理 |
| `my_control_comments_before.php` | 我的评论前 | 评论列表 |
| `my_control_comments_where_after.php` | 评论查询后 | 数据处理 |
| `my_control_comments_post_after.php` | 评论操作后 | 后续处理 |
| `my_control_comments_post_del_success.php` | 评论删除成功 | 成功处理 |
| `my_control_comments_after.php` | 我的评论后 | 后续处理 |
| `my_control_upload_avatar_config_after.php` | 头像配置后 | 配置处理 |
| `my_control_upload_avatar_config_success_before.php` | 上传成功前 | 上传准备 |
| `my_control_logout_before.php` | 退出登录前 | 清理工作 |
| `my_control_after.php` | 类结束 | 清理工作 |

### 3.10 parseurl_control URL解析控制器钩子 (84个)

这是 Hook 最多的控制器，涵盖所有 URL 解析场景：

#### 入口Hook

| Hook名称 | 用途 |
|----------|------|
| `parseurl_control_start.php` | URL解析开始 |
| `parseurl_control_index_before.php` | 解析入口前 |
| `parseurl_control_index_rewrite_before.php` | rewrite参数处理前 |
| `parseurl_control_index_rewrite_after.php` | rewrite参数处理后 |
| `parseurl_control_index_after.php` | 解析入口后 |

#### 分类页URL解析

| Hook名称 | 用途 |
|----------|------|
| `parseurl_control_index_link_cate_before.php` | 分类解析前 |
| `parseurl_control_index_link_cate_after.php` | 分类解析后 |
| `parseurl_control_category_url_before.php` | 分类URL解析前 |
| `parseurl_control_category_url_after.php` | 分类URL解析后 |

#### 内容页URL解析

| Hook名称 | 用途 |
|----------|------|
| `parseurl_control_index_link_show_before.php` | 内容解析前 |
| `parseurl_control_index_link_show_after.php` | 内容解析后 |
| `parseurl_control_content_url_before.php` | 内容URL解析前 |
| `parseurl_control_content_url_after.php` | 内容URL解析后 |

#### 内容页8种URL类型Hook (每种类型前后各2个)

| URL类型 | Hook前缀 |
|---------|----------|
| 数字型 | `parseurl_control_content_url_switch_1_` |
| 推荐型 | `parseurl_control_content_url_switch_2_` |
| 别名型 | `parseurl_control_content_url_switch_3_` |
| 加密型 | `parseurl_control_content_url_switch_4_` |
| ID型 | `parseurl_control_content_url_switch_5_` |
| 别名组合型 | `parseurl_control_content_url_switch_6_` |
| 灵活型 | `parseurl_control_content_url_switch_7_` |
| HashIDS型 | `parseurl_control_content_url_switch_8_` |

#### 灵活型特殊Hook

| Hook名称 | 用途 |
|----------|------|
| `parseurl_control_content_url_switch_7_quote_after.php` | 正则替换后 |
| `parseurl_control_content_url_switch_7_mat_after.php` | 匹配结果处理后 |

#### 标签页URL解析

| Hook名称 | 用途 |
|----------|------|
| `parseurl_control_index_tag_before.php` | 标签解析前 |
| `parseurl_control_index_tag_after.php` | 标签解析后 |
| `parseurl_control_tag_url_before.php` | 标签URL解析前 |
| `parseurl_control_tag_url_after.php` | 标签URL解析后 |

#### 标签URL类型Hook (4种类型)

| Hook名称 | 用途 |
|----------|------|
| `parseurl_control_tag_url_switch_0_before/after.php` | 名称型 |
| `parseurl_control_tag_url_switch_1_before/after.php` | ID型 |
| `parseurl_control_tag_url_switch_2_before/after.php` | 加密型 |
| `parseurl_control_tag_url_switch_3_before/after.php` | HashIDS型 |
| `parseurl_control_tag_url_switch_end.php` | 类型处理结束 |

#### 热门/全部标签

| Hook名称 | 用途 |
|----------|------|
| `parseurl_control_index_tag_like_before.php` | 热门标签前 |
| `parseurl_control_index_tag_like_after.php` | 热门标签后 |
| `parseurl_control_tag_like_url_before.php` | 热门标签URL解析前 |
| `parseurl_control_tag_like_url_after.php` | 热门标签URL解析后 |

#### 搜索页URL解析

| Hook名称 | 用途 |
|----------|------|
| `parseurl_control_index_search_before.php` | 搜索解析前 |
| `parseurl_control_index_search_after.php` | 搜索解析后 |
| `parseurl_control_search_url_before.php` | 搜索URL解析前 |
| `parseurl_control_search_url_after.php` | 搜索URL解析后 |

#### 评论页URL解析

| Hook名称 | 用途 |
|----------|------|
| `parseurl_control_index_comment_before.php` | 评论解析前 |
| `parseurl_control_index_comment_after.php` | 评论解析后 |
| `parseurl_control_comment_url_before.php` | 评论URL解析前 |
| `parseurl_control_comment_url_after.php` | 评论URL解析后 |

#### 首页分页URL解析

| Hook名称 | 用途 |
|----------|------|
| `parseurl_control_index_index_page_before.php` | 首页分页前 |
| `parseurl_control_index_index_page_after.php` | 首页分页后 |
| `parseurl_control_index_page_url_before.php` | 分页URL解析前 |
| `parseurl_control_index_page_url_after.php` | 分页URL解析后 |

#### 用户中心URL解析

| Hook名称 | 用途 |
|----------|------|
| `parseurl_control_index_user_before.php` | 用户中心解析前 |
| `parseurl_control_index_user_after.php` | 用户中心解析后 |
| `parseurl_control_user_url_before.php` | 用户URL解析前 |
| `parseurl_control_user_url_after.php` | 用户URL解析后 |

#### 模型页URL解析

| Hook名称 | 用途 |
|----------|------|
| `parseurl_control_index_model_before.php` | 模型解析前 |
| `parseurl_control_index_model_after.php` | 模型解析后 |
| `parseurl_control_model_url_before.php` | 模型URL解析前 |
| `parseurl_control_model_url_after.php` | 模型URL解析后 |

#### 属性内容URL解析

| Hook名称 | 用途 |
|----------|------|
| `parseurl_control_index_flags_before.php` | 属性解析前 |
| `parseurl_control_index_flags_after.php` | 属性解析后 |
| `parseurl_control_flags_url_before.php` | 属性URL解析前 |
| `parseurl_control_flags_url_after.php` | 属性URL解析后 |

#### 个人空间URL解析

| Hook名称 | 用途 |
|----------|------|
| `parseurl_control_index_space_before.php` | 空间解析前 |
| `parseurl_control_index_space_after.php` | 空间解析后 |
| `parseurl_control_space_url_before.php` | 空间URL解析前 |
| `parseurl_control_space_url_after.php` | 空间URL解析后 |

#### 其他URL解析

| Hook名称 | 用途 |
|----------|------|
| `parseurl_control_index_other_before.php` | 其他URL解析前 |
| `parseurl_control_index_other_after.php` | 其他URL解析后 |
| `parseurl_control_other_url_before.php` | 其他URL处理前 |
| `parseurl_control_other_url_after.php` | 其他URL处理后 |

#### 辅助函数Hook

| Hook名称 | 用途 |
|----------|------|
| `parseurl_control_integer_check_before.php` | 整数验证前 |

### 37.2 模型层 Hook

#### 运行时模型 (runtime_model)

```
runtime_model_construct_after.php
runtime_model_xget_cfg_before.php
runtime_model_xget_cfg_after.php
runtime_model_xset_cfg_before.php
runtime_model_xset_cfg_after.php
runtime_model_xdelete_after.php
```

#### 分类模型 (category_model)

```
category_model_create_before.php
category_model_create_after.php
category_model_update_before.php
category_model_update_after.php
category_model_delete_before.php
category_model_delete_after.php
category_model_get_before.php
category_model_get_after.php
```

#### CMS 内容模型 (cms_content_model)

```
cms_content_model_format_before.php     // 内容格式化前（最常用）
cms_content_model_format_after.php      // 内容格式化后
cms_content_model_create_before.php     // 内容创建前
cms_content_model_create_after.php      // 内容创建后
cms_content_model_update_before.php     // 内容更新前
cms_content_model_update_after.php      // 内容更新后
cms_content_model_delete_before.php     // 内容删除前
cms_content_model_delete_after.php      // 内容删除后
```

#### KV 模型 (kv_model)

```
kv_model_get_before.php
kv_model_get_after.php
kv_model_set_before.php
kv_model_set_after.php
kv_model_delete_before.php
kv_model_delete_after.php
```

#### 模型管理模型 (models_model)

```
models_model_create_before.php
models_model_create_after.php
models_model_update_before.php
models_model_update_after.php
models_model_delete_before.php
models_model_delete_after.php
```

#### user_model 用户模型Hook (56个)

| Hook名称 | 触发位置 | 用途 |
|----------|----------|------|
| `user_model_list_arr_before.php` | 列表查询前 | 修改查询条件 |
| `user_model_list_arr_after.php` | 列表查询后 | 处理查询结果 |
| `user_model_field_length_after.php` | 字段长度处理后 | 字段验证 |

##### 用户Token验证Hook

| Hook名称 | 用途 |
|----------|------|
| `user_model_user_token_check_before.php` | Token验证前 | 准备验证 |
| `user_model_user_token_check_after.php` | Token验证后 | 处理验证结果 |
| `user_model_user_token_check_cookie_after.php` | Cookie验证后 | Cookie处理 |
| `user_model_user_token_check_cookie_success.php` | Cookie验证成功 | 成功处理 |
| `user_model_user_token_check_session_after.php` | Session验证后 | Session处理 |
| `user_model_user_token_check_session_success.php` | Session验证成功 | 成功处理 |

##### 用户登录Hook

| Hook名称 | 用途 |
|----------|------|
| `user_model_user_token_login_before.php` | 登录处理前 | 准备登录 |
| `user_model_user_token_login_after.php` | 登录处理后 | 处理登录结果 |
| `user_model_user_token_login_cookie_after.php` | Cookie登录后 | Cookie处理 |
| `user_model_user_token_login_session_after.php` | Session登录后 | Session处理 |

##### 用户登出Hook

| Hook名称 | 用途 |
|----------|------|
| `user_model_user_token_logout_before.php` | 登出处理前 | 准备登出 |
| `user_model_user_token_logout_after.php` | 登出处理后 | 处理登出结果 |
| `user_model_user_token_logout_cookie_after.php` | Cookie登出后 | 清理Cookie |
| `user_model_user_token_logout_session_after.php` | Session登出后 | 清理Session |

#### user_group_model 用户组模型Hook

| Hook名称 | 用途 |
|----------|------|
| `user_group_model_after.php` | 模型处理后 |
| `user_group_model_field_length_after.php` | 字段长度处理后 |
| `user_group_model_get_groupidhtml_after.php` | 组ID HTML生成后 |
| `user_group_model_list_arr_before.php` | 列表查询前 |
| `user_group_model_list_arr_after.php` | 列表查询后 |

#### only_alias_model 别名模型Hook

| Hook名称 | 用途 |
|----------|------|
| `only_alias_model_get_by_alias_before.php` | 通过别名获取前 |
| `only_alias_model_get_by_alias_after.php` | 通过别名获取后 |

#### urls_model URL模型Hook

| Hook名称 | 用途 |
|----------|------|
| `urls_model_content_url_before.php` | 内容URL生成前 |
| `urls_model_content_url_after.php` | 内容URL生成后 |
| `urls_model_category_url_before.php` | 分类URL生成前 |
| `urls_model_category_url_after.php` | 分类URL生成后 |
| `urls_model_tag_url_before.php` | 标签URL生成前 |
| `urls_model_tag_url_after.php` | 标签URL生成后 |
| `urls_model_search_url_before.php` | 搜索URL生成前 |
| `urls_model_search_url_after.php` | 搜索URL生成后 |
| `urls_model_user_url_before.php` | 用户URL生成前 |
| `urls_model_user_url_after.php` | 用户URL生成后 |

**通用模型模式：**
几乎所有模型文件末尾都有统一的 Hook 标记：
```
// hook {model_name}_after.php
```
例如：
```
// hook category_model_after.php
// hook cms_content_model_after.php
// hook runtime_model_after.php
```

### 37.3 后台管理 Hook

### 7.1 admin_control 管理员控制器Hook (30+个)

| Hook名称 | 用途 |
|----------|------|
| `admin_admin_control_before.php` | 后台控制器开始 |
| `admin_admin_control_construct_before.php` | 构造方法前 |
| `admin_admin_control_construct_user_token_check_after.php` | Token验证后 |
| `admin_admin_control_admauth_failed.php` | 认证失败 |
| `admin_admin_control_admauth_success_before.php` | 认证成功前 |
| `admin_admin_control_admauth_success.php` | 认证成功 |
| `admin_admin_control_construct_after.php` | 构造方法后 |
| `admin_admin_control_admin_safe_login_url_before.php` | 安全登录URL前 |
| `admin_admin_control_admin_safe_login_url_after.php` | 安全登录URL后 |
| `admin_admin_control_check_isadmin_before.php` | 管理员检测前 |
| `admin_admin_control_check_user_group_before.php` | 用户组检测前 |
| `admin_admin_control_check_user_group_purviews_before.php` | 权限检测前 |
| `admin_admin_control_clear_cache_before.php` | 清理缓存前 |
| `admin_admin_control_init_navigation_before.php` | 初始化导航前 |
| `admin_admin_control_init_nav_before.php` | 导航初始化前 |
| `admin_admin_control_init_nav_setting_after.php` | 设置导航后 |
| `admin_admin_control_init_nav_category_after.php` | 分类导航后 |
| `admin_admin_control_init_nav_content_center.php` | 内容导航中心 |
| `admin_admin_control_init_nav_content_foreach.php` | 内容导航遍历 |
| `admin_admin_control_init_nav_content_after.php` | 内容导航后 |
| `admin_admin_control_init_nav_user_after.php` | 用户导航后 |
| `admin_admin_control_init_nav_plugin_after.php` | 插件导航后 |
| `admin_admin_control_init_nav_tools_after.php` | 工具导航后 |
| `admin_admin_control_init_nav_after.php` | 导航初始化后 |
| `admin_admin_control_init_nav_purviews_before.php` | 权限检测前 |
| `admin_admin_control_init_nav_purviews_after.php` | 权限检测后 |
| `admin_admin_control_init_navigation_after.php` | 导航初始化后 |
| `admin_admin_control_after.php` | 后台控制器结束 |

### 7.2 content_control 内容控制器Hook

| Hook名称 | 用途 |
|----------|------|
| `admin_content_control_add_post_data_before.php` | 添加数据前 |
| `admin_content_control_add_post_data_after.php` | 添加数据后 |
| `admin_content_control_add_after.php` | 添加内容后 |
| `admin_content_control_edit_post_data_before.php` | 编辑数据前 |
| `admin_content_control_edit_post_data_after.php` | 编辑数据后 |
| `admin_content_control_edit_after.php` | 编辑内容后 |
| `admin_content_control_del_before.php` | 删除内容前 |
| `admin_content_control_del_success.php` | 删除成功 |
| `admin_content_control_index_before.php` | 列表页前 |
| `admin_content_control_index_after.php` | 列表页后 |
| `admin_content_control_get_list_before.php` | 获取列表前 |
| `admin_content_control_get_list_after.php` | 获取列表后 |

### 7.3 category_control 分类控制器Hook

| Hook名称 | 用途 |
|----------|------|
| `admin_category_control_index_before.php` | 分类列表前 |
| `admin_category_control_index_cols_after.php` | 列表列处理后 |
| `admin_category_control_index_after.php` | 分类列表后 |
| `admin_category_control_add_before.php` | 添加分类前 |
| `admin_category_control_add_after.php` | 添加分类后 |
| `admin_category_control_edit_before.php` | 编辑分类前 |
| `admin_category_control_edit_after.php` | 编辑分类后 |
| `admin_category_control_del_before.php` | 删除分类前 |
| `admin_category_control_del_success.php` | 删除成功 |
| `admin_category_control_del_after.php` | 删除分类后 |

### 7.4 attach_control 附件控制器Hook

| Hook名称 | 用途 |
|----------|------|
| `admin_attach_control_upload_pic_before.php` | 上传图片前 |
| `admin_attach_control_upload_image_before.php` | 上传图片前 |
| `admin_attach_control_upload_image_uploads_after.php` | 上传处理后 |
| `admin_attach_control_upload_image_success_after.php` | 上传成功 |
| `admin_attach_control_upload_files_before.php` | 上传文件前 |
| `admin_attach_control_upload_files_uploads_after.php` | 文件上传后 |
| `admin_attach_control_del_attach_before.php` | 删除附件前 |
| `admin_attach_control_del_attach_success.php` | 删除成功 |
| `admin_attach_control_after.php` | 控制器结束 |

### 站点地图 Hook 详解 (34个)

### sitemap_control 控制器Hook

#### 构造方法Hook

| Hook名称 | 用途 |
|----------|------|
| `sitemap_control_construct_before.php` | 构造方法执行前 |
| `sitemap_control_construct_after.php` | 构造方法执行后 |

#### XML格式Hook

| Hook名称 | 用途 |
|----------|------|
| `sitemap_control_xml_before.php` | XML生成前 |
| `sitemap_control_xml_after.php` | XML生成后 |
| `sitemap_control_xml_urlset_after.php` | URLSet标签后 |
| `sitemap_control_xml_urlset_end.php` | URLSet结束 |
| `sitemap_control_xml_url_foreach_after.php` | URL遍历后 |
| `sitemap_control_xml_home_after.php` | 首页URL后 |
| `sitemap_control_xml_category_after.php` | 分类URL后 |
| `sitemap_control_xml_category_url_foreach_after.php` | 分类URL遍历后 |
| `sitemap_control_xml_content_after.php` | 内容URL后 |
| `sitemap_control_xml_content_url_foreach_after.php` | 内容URL遍历后 |
| `sitemap_control_xml_tag_after.php` | 标签URL后 |
| `sitemap_control_xml_tag_url_foreach_after.php` | 标签URL遍历后 |

#### HTML格式Hook

| Hook名称 | 用途 |
|----------|------|
| `sitemap_control_html_before.php` | HTML生成前 |
| `sitemap_control_html_after.php` | HTML生成后 |
| `sitemap_control_html_home_after.php` | 首页链接后 |
| `sitemap_control_html_category_after.php` | 分类链接后 |
| `sitemap_control_html_category_url_foreach_after.php` | 分类URL遍历后 |
| `sitemap_control_html_content_after.php` | 内容链接后 |
| `sitemap_control_html_content_url_foreach_after.php` | 内容URL遍历后 |
| `sitemap_control_html_tag_url_foreach_after.php` | 标签URL遍历后 |
| `sitemap_control_tag_content_after.php` | 标签内容后 |

#### TXT格式Hook

| Hook名称 | 用途 |
|----------|------|
| `sitemap_control_txt_before.php` | TXT生成前 |
| `sitemap_control_txt_after.php` | TXT生成后 |
| `sitemap_control_txt_home_after.php` | 首页行后 |
| `sitemap_control_txt_category_after.php` | 分类行后 |
| `sitemap_control_txt_category_url_foreach_after.php` | 分类URL遍历后 |
| `sitemap_control_txt_content_after.php` | 内容行后 |
| `sitemap_control_txt_content_url_foreach_after.php` | 内容URL遍历后 |
| `sitemap_control_txt_tag_after.php` | 标签行后 |
| `sitemap_control_txt_tag_url_foreach_after.php` | 标签URL遍历后 |

### 后台工具菜单 (admin_tool)

| Hook名称 | 用途 |
|----------|------|
| `admin_tool_control_after.php` | 工具菜单加载后 |

### 37.4 模板 Block 层 Hook (243个)

Block 模块是 LeCMS 中 Hook 使用最密集的区域，共包含 **243 个 Hook 点**，覆盖所有前端数据展示模块。Block 模块的 Hook 文件命名格式为 `block_{模块名}_{时机}.php`。

#### Block 通用Hook格式

| Hook 文件名 | 触发时机 | 说明 |
|-------------|---------|------|
| `block_{name}_before.php` | Block 开始渲染 | 可修改 Block 参数 |
| `block_{name}_after.php` | Block 渲染结束 | 可修改输出内容 |
| `block_{name}_conf_after.php` | 读取 Block 配置后 | 可修改配置参数 |
| `block_{name}_list_arr_after.php` | 数据查询返回后 | 可修改查询结果数组 |
| `block_{name}_foreach_after.php` | 列表循环内部 | 可修改每一项的数据 |

#### 4.1 分类相关 Block

##### block_category (6个)

| Hook名称 | 用途 |
|----------|------|
| `block_category_before.php` | 分类块处理前 |
| `block_category_conf_after.php` | 配置处理后 |
| `block_category_type_after.php` | 类型处理后 |
| `block_category_foreach_after.php` | 遍历处理后 |
| `block_category_set_block_data_cache_before.php` | 缓存设置前 |
| `block_category_after.php` | 分类块处理后 |

##### block_category_info (5个)

| Hook名称 | 用途 |
|----------|------|
| `block_category_info_before.php` | 分类信息处理前 |
| `block_category_info_conf_after.php` | 配置处理后 |
| `block_category_info_cid_after.php` | 分类ID处理后 |
| `block_category_info_set_block_data_cache_before.php` | 缓存设置前 |

##### block_category_new (5个)

| Hook名称 | 用途 |
|----------|------|
| `block_category_new_before.php` | 最新分类处理前 |
| `block_category_new_conf_after.php` | 配置处理后 |
| `block_category_new_foreach_after.php` | 遍历处理后 |
| `block_category_new_set_block_data_cache_before.php` | 缓存设置前 |
| `block_category_new_after.php` | 最新分类处理后 |

##### block_category_tree (4个)

| Hook名称 | 用途 |
|----------|------|
| `block_category_tree_before.php` | 分类树处理前 |
| `block_category_tree_conf_after.php` | 配置处理后 |
| `block_category_tree_set_block_data_cache_before.php` | 缓存设置前 |
| `block_category_tree_after.php` | 分类树处理后 |

#### 4.2 评论相关 Block

##### block_comment (6个)

| Hook名称 | 用途 |
|----------|------|
| `block_comment_before.php` | 评论块处理前 |
| `block_comment_conf_after.php` | 配置处理后 |
| `block_comment_where_after.php` | 条件处理后 |
| `block_comment_foreach_after.php` | 遍历处理后 |
| `block_comment_set_block_data_cache_before.php` | 缓存设置前 |
| `block_comment_after.php` | 评论块处理后 |

##### block_comment_list (7个)

| Hook名称 | 用途 |
|----------|------|
| `block_comment_list_before.php` | 评论列表处理前 |
| `block_comment_list_conf_after.php` | 配置处理后 |
| `block_comment_list_where_after.php` | 条件处理后 |
| `block_comment_list_foreach_after.php` | 遍历处理后 |
| `block_comment_list_cms_foreach_after.php` | 内容遍历后 |
| `block_comment_list_set_block_data_cache_before.php` | 缓存设置前 |
| `block_comment_list_after.php` | 评论列表处理后 |

#### 4.3 全局通用 Block

##### block_global_show (9个)

| Hook名称 | 用途 |
|----------|------|
| `block_global_show_before.php` | 全局内容处理前 |
| `block_global_show_conf_after.php` | 配置处理后 |
| `block_global_show_data_before.php` | 数据获取前 |
| `block_global_show_data_after.php` | 数据获取后 |
| `block_global_show_field_format_after.php` | 字段格式化后 |
| `block_global_show_data_error.php` | 数据错误处理 |
| `block_global_show_center.php` | 页面中心区域 |
| `block_global_show_cache_before.php` | 缓存处理前 |
| `block_global_show_after.php` | 全局内容处理后 |

##### block_global_cate (8个)

| Hook名称 | 用途 |
|----------|------|
| `block_global_cate_before.php` | 全局分类处理前 |
| `block_global_cate_conf_after.php` | 配置处理后 |
| `block_global_cate_where_after.php` | 条件处理后 |
| `block_global_cate_list_arr_before.php` | 列表数据前 |
| `block_global_cate_list_arr_after.php` | 列表数据后 |
| `block_global_cate_foreach_after.php` | 遍历处理后 |
| `block_global_cate_set_block_data_cache_before.php` | 缓存设置前 |
| `block_global_cate_after.php` | 全局分类处理后 |

##### block_global_comment (8个)

| Hook名称 | 用途 |
|----------|------|
| `block_global_comment_before.php` | 全局评论处理前 |
| `block_global_comment_conf_after.php` | 配置处理后 |
| `block_global_comment_list_arr_before.php` | 列表数据前 |
| `block_global_comment_list_arr_after.php` | 列表数据后 |
| `block_global_comment_foreach_after.php` | 遍历处理后 |
| `block_global_comment_reply_foreach_after.php` | 回复遍历后 |
| `block_global_comment_set_block_data_cache_before.php` | 缓存设置前 |
| `block_global_comment_after.php` | 全局评论处理后 |

##### block_global_search (7个)

| Hook名称 | 用途 |
|----------|------|
| `block_global_search_before.php` | 全局搜索处理前 |
| `block_global_search_conf_after.php` | 配置处理后 |
| `block_global_search_where_after.php` | 条件处理后 |
| `block_global_search_list_arr_before.php` | 列表数据前 |
| `block_global_search_list_arr_after.php` | 列表数据后 |
| `block_global_foreach_search_after.php` | 搜索遍历后 |
| `block_global_search_after.php` | 全局搜索处理后 |

##### block_global_tag (8个)

| Hook名称 | 用途 |
|----------|------|
| `block_global_tag_before.php` | 全局标签处理前 |
| `block_global_tag_conf_after.php` | 配置处理后 |
| `block_global_tag_where_after.php` | 条件处理后 |
| `block_global_tag_list_arr_before.php` | 列表数据前 |
| `block_global_tag_list_arr_after.php` | 列表数据后 |
| `block_global_tag_list_foreach_after.php` | 遍历处理后 |
| `block_global_tag_set_block_data_cache_before.php` | 缓存设置前 |
| `block_global_tag_after.php` | 全局标签处理后 |

##### block_global_space (8个)

| Hook名称 | 用途 |
|----------|------|
| `block_global_space_before.php` | 全局空间处理前 |
| `block_global_space_conf_after.php` | 配置处理后 |
| `block_global_space_where_after.php` | 条件处理后 |
| `block_global_space_list_arr_before.php` | 列表数据前 |
| `block_global_space_list_arr_after.php` | 列表数据后 |
| `block_global_space_foreach_after.php` | 遍历处理后 |
| `block_global_space_set_block_data_cache_before.php` | 缓存设置前 |
| `block_global_space_after.php` | 全局空间处理后 |

##### block_global_flags (7个)

| Hook名称 | 用途 |
|----------|------|
| `block_global_flags_before.php` | 全局属性处理前 |
| `block_global_flags_conf_after.php` | 配置处理后 |
| `block_global_flags_where_after.php` | 条件处理后 |
| `block_global_flags_list_arr_before.php` | 列表数据前 |
| `block_global_flags_list_arr_after.php` | 列表数据后 |
| `block_global_flags_set_block_data_cache_before.php` | 缓存设置前 |
| `block_global_flags_after.php` | 全局属性处理后 |

#### 4.4 列表相关 Block

##### block_list (18个)

| Hook名称 | 用途 |
|----------|------|
| `block_list_before.php` | 列表处理前 |
| `block_list_conf_after.php` | 配置处理后 |
| `block_list_flag_before.php` | 属性处理前 |
| `block_list_flag_conf_after.php` | 属性配置后 |
| `block_list_flag_foreach_after.php` | 属性遍历后 |
| `block_list_flag_where_after.php` | 属性条件后 |
| `block_list_list_arr_after.php` | 列表数据后 |
| `block_list_foreach_after.php` | 遍历处理后 |
| `block_list_where_after.php` | 条件处理后 |
| `block_list_prev_next_before.php` | 上下篇处理前 |
| `block_list_prev_next_conf_after.php` | 上下篇配置后 |
| `block_list_prev_next_center.php` | 上下篇中心区域 |
| `block_list_prev_next_foreach_after.php` | 上下篇遍历后 |
| `block_list_prev_next_where_after.php` | 上下篇条件后 |
| `block_list_prev_next_set_block_data_cache_before.php` | 缓存设置前 |
| `block_list_prev_next_after.php` | 上下篇处理后 |
| `block_list_set_block_data_cache_before.php` | 缓存设置前 |
| `block_list_after.php` | 列表处理后 |

##### block_list_by_field (7个)

| Hook名称 | 用途 |
|----------|------|
| `block_list_by_field_before.php` | 字段筛选处理前 |
| `block_list_by_field_conf_after.php` | 配置处理后 |
| `block_list_by_field_foreach_after.php` | 遍历处理后 |
| `block_list_by_field_list_arr_after.php` | 列表数据后 |
| `block_list_by_field_set_block_data_cache_before.php` | 缓存设置前 |
| `block_list_by_field_where_after.php` | 条件处理后 |
| `block_list_by_field_after.php` | 字段筛选处理后 |

##### block_list_by_id (4个)

| Hook名称 | 用途 |
|----------|------|
| `block_list_by_id_before.php` | ID获取处理前 |
| `block_list_by_id_conf_after.php` | 配置处理后 |
| `block_list_by_id_set_block_data_cache_before.php` | 缓存设置前 |
| `block_list_by_id_after.php` | ID获取处理后 |

##### block_list_by_ids_foreach (1个)

| Hook名称 | 用途 |
|----------|------|
| `block_list_by_ids_foreach_after.php` | 多ID遍历后 |

##### block_list_by_keywords (5个)

| Hook名称 | 用途 |
|----------|------|
| `block_list_by_keywords_before.php` | 关键词搜索处理前 |
| `block_list_by_keywords_conf_after.php` | 配置处理后 |
| `block_list_by_keywords_foreach_after.php` | 遍历处理后 |
| `block_list_by_keywords_set_block_data_cache_before.php` | 缓存设置前 |
| `block_list_by_keywords_after.php` | 关键词搜索处理后 |

##### block_list_by_tagid (5个)

| Hook名称 | 用途 |
|----------|------|
| `block_list_by_tagid_before.php` | 标签ID处理前 |
| `block_list_by_tagid_conf_after.php` | 配置处理后 |
| `block_list_by_tagid_foreach_after.php` | 遍历处理后 |
| `block_list_by_tagid_set_block_data_cache_before.php` | 缓存设置前 |
| `block_list_by_tagid_after.php` | 标签ID处理后 |

##### block_list_by_tagids_foreach (1个)

| Hook名称 | 用途 |
|----------|------|
| `block_list_by_tagids_foreach_after.php` | 多标签ID遍历后 |

##### block_list_by_tagname (5个)

| Hook名称 | 用途 |
|----------|------|
| `block_list_by_tagname_before.php` | 标签名处理前 |
| `block_list_by_tagname_conf_after.php` | 配置处理后 |
| `block_list_by_tagname_foreach_after.php` | 遍历处理后 |
| `block_list_by_tagname_set_block_data_cache_before.php` | 缓存设置前 |
| `block_list_by_tagname_after.php` | 标签名处理后 |

##### block_list_by_uid (6个)

| Hook名称 | 用途 |
|----------|------|
| `block_list_by_uid_before.php` | 用户ID处理前 |
| `block_list_by_uid_conf_after.php` | 配置处理后 |
| `block_list_by_uid_foreach_after.php` | 遍历处理后 |
| `block_list_by_uid_where_after.php` | 条件处理后 |
| `block_list_by_uid_set_block_data_cache_before.php` | 缓存设置前 |
| `block_list_by_uid_after.php` | 用户ID处理后 |

##### block_list_rand (14个)

| Hook名称 | 用途 |
|----------|------|
| `block_list_rand_before.php` | 随机列表处理前 |
| `block_list_rand_conf_after.php` | 配置处理后 |
| `block_list_rand_list_arr_after.php` | 列表数据后 |
| `block_list_rand_foreach.php` | 随机遍历 |
| `block_list_rand_set_block_data_cache_before.php` | 缓存设置前 |
| `block_list_rand_after.php` | 随机列表处理后 |
| `block_list_rand_by_cid_before.php` | 按分类随机前 |
| `block_list_rand_by_cid_conf_after.php` | 按分类随机配置后 |
| `block_list_rand_by_cid_list_arr_after.php` | 按分类随机列表后 |
| `block_list_rand_by_cid_foreach.php` | 按分类随机遍历 |
| `block_list_rand_by_cid_set_block_data_cache_before.php` | 缓存设置前 |

##### block_list_top (8个)

| Hook名称 | 用途 |
|----------|------|
| `block_list_top_before.php` | 排行列表处理前 |
| `block_list_top_conf_after.php` | 配置处理后 |
| `block_list_top_list_arr_after.php` | 列表数据后 |
| `block_list_top_views_foreach_after.php` | 浏览量遍历后 |
| `block_list_top_comments_foreach_after.php` | 评论数遍历后 |
| `block_list_top_lastdate_foreach_after.php` | 最新时间遍历后 |
| `block_list_top_set_block_data_cache_before.php` | 缓存设置前 |
| `block_list_top_after.php` | 排行列表处理后 |

#### 4.5 用户相关 Block

##### block_user_list (6个)

| Hook名称 | 用途 |
|----------|------|
| `block_user_list_before.php` | 用户列表处理前 |
| `block_user_list_conf_after.php` | 配置处理后 |
| `block_user_list_foreach_after.php` | 遍历处理后 |
| `block_user_list_where_after.php` | 条件处理后 |
| `block_user_list_set_block_data_cache_before.php` | 缓存设置前 |
| `block_user_list_after.php` | 用户列表处理后 |

##### block_user_info (4个)

| Hook名称 | 用途 |
|----------|------|
| `block_user_info_before.php` | 用户信息处理前 |
| `block_user_info_conf_after.php` | 配置处理后 |
| `block_userinfo_set_block_data_cache_before.php` | 缓存设置前 |

##### block_global_user (8个)

| Hook名称 | 用途 |
|----------|------|
| `block_global_user_before.php` | 全局用户处理前 |
| `block_global_user_conf_after.php` | 配置处理后 |
| `block_global_user_list_arr_before.php` | 列表数据前 |
| `block_global_user_list_arr_after.php` | 列表数据后 |
| `block_global_user_foreach_after.php` | 遍历处理后 |
| `block_global_user_where_after.php` | 条件处理后 |
| `block_global_user_set_block_data_cache_before.php` | 缓存设置前 |
| `block_global_user_after.php` | 全局用户处理后 |

#### 4.6 标签相关 Block

##### block_taglist (11个)

| Hook名称 | 用途 |
|----------|------|
| `block_taglist_before.php` | 标签列表处理前 |
| `block_taglist_conf_after.php` | 配置处理后 |
| `block_taglist_foreach_after.php` | 遍历处理后 |
| `block_taglist_set_block_data_cache_before.php` | 缓存设置前 |
| `block_taglist_where_after.php` | 条件处理后 |
| `block_taglist_rand_before.php` | 随机标签处理前 |
| `block_taglist_rand_conf_after.php` | 随机标签配置后 |
| `block_taglist_rand_foreach_after.php` | 随机标签遍历后 |
| `block_taglist_rand_set_block_data_cache_before.php` | 缓存设置前 |
| `block_taglist_rand_after.php` | 随机标签处理后 |
| `block_taglist_after.php` | 标签列表处理后 |

##### block_taglike (5个)

| Hook名称 | 用途 |
|----------|------|
| `block_taglike_before.php` | 相似标签处理前 |
| `block_taglike_conf_after.php` | 配置处理后 |
| `block_taglike_foreach_after.php` | 遍历处理后 |
| `block_taglike_set_block_data_cache_before.php` | 缓存设置前 |
| `block_taglike_after.php` | 相似标签处理后 |

#### 4.7 其他 Block

##### block_navigate (5个)

| Hook名称 | 用途 |
|----------|------|
| `block_navigate_before.php` | 导航处理前 |
| `block_navigate_conf_after.php` | 配置处理后 |
| `block_navigate_foreach_after.php` | 遍历处理后 |
| `block_navigate_set_block_data_cache_before.php` | 缓存设置前 |
| `block_navigate_after.php` | 导航处理后 |

##### block_links (2个)

| Hook名称 | 用途 |
|----------|------|
| `block_links_before.php` | 友情链接处理前 |
| `block_links_after.php` | 友情链接处理后 |

##### block_lists (6个)

| Hook名称 | 用途 |
|----------|------|
| `block_lists_before.php` | 多分类列表处理前 |
| `block_lists_conf_after.php` | 配置处理后 |
| `block_lists_foreach_after.php` | 遍历处理后 |
| `block_lists_cids_foreach_after.php` | 分类遍历后 |
| `block_lists_set_block_data_cache_before.php` | 缓存设置前 |
| `block_lists_after.php` | 多分类列表处理后 |

##### block_listeach (6个)

| Hook名称 | 用途 |
|----------|------|
| `block_listeach_before.php` | 分类独立列表处理前 |
| `block_listeach_conf_after.php` | 配置处理后 |
| `block_listeach_foreach_after.php` | 遍历处理后 |
| `block_listeach_list_foreach_after.php` | 列表遍历后 |
| `block_listeach_set_block_data_cache_before.php` | 缓存设置前 |
| `block_listeach_after.php` | 分类独立列表处理后 |

##### block_data_total (4个)

| Hook名称 | 用途 |
|----------|------|
| `block_data_total_before.php` | 数据统计处理前 |
| `block_data_total_conf_after.php` | 配置处理后 |
| `block_data_total_set_block_data_cache_before.php` | 缓存设置前 |
| `block_data_total_after.php` | 数据统计处理后 |

##### block_content_total_by_date (5个)

| Hook名称 | 用途 |
|----------|------|
| `block_content_total_by_date_before.php` | 按日期统计处理前 |
| `block_content_total_by_date_conf_after.php` | 配置处理后 |
| `block_content_total_by_date_type_after.php` | 类型处理后 |
| `block_content_total_by_date_set_block_data_cache_before.php` | 缓存设置前 |
| `block_content_total_by_date_after.php` | 按日期统计处理后 |

##### block_global_blog (7个)

| Hook名称 | 用途 |
|----------|------|
| `block_global_blog_before.php` | 全局博客处理前 |
| `block_global_blog_conf_after.php` | 配置处理后 |
| `block_global_blog_list_arr_before.php` | 列表数据前 |
| `block_global_blog_list_arr_after.php` | 列表数据后 |
| `block_global_blog_foreach_after.php` | 遍历处理后 |
| `block_global_blog_set_block_data_cache_before.php` | 缓存设置前 |
| `block_global_blog_after.php` | 全局博客处理后 |

##### block_global_page (2个)

| Hook名称 | 用途 |
|----------|------|
| `block_global_page_before.php` | 全局单页处理前 |
| `block_global_page_after.php` | 全局单页处理后 |

##### block_sql (4个)

| Hook名称 | 用途 |
|----------|------|
| `block_sql_before.php` | SQL查询处理前 |
| `block_sql_conf_after.php` | 配置处理后 |
| `block_sql_set_block_data_cache_before.php` | 缓存设置前 |
| `block_sql_after.php` | SQL查询处理后 |

### 8.1 模板中使用 Hook

**格式：** `{hook:hook_name}`

**示例：**
```html
<!-- 在模板中使用 -->
<div class="custom-area">
    {hook:my_custom_hook}
</div>
```

### 8.2 模板 Hook 处理

**文件：** `lecms/xiunophp/lib/view.class.php`

```php
// 处理模板中的 {hook:xxx} 标记
$s = preg_replace_callback('#\{hook\:([\w\.]+)\}#', array('core', 'process_hook'), $s);

// 处理lib文件中的hook
$lib_str = preg_replace_callback('#\t*\/\/\s*hook\s+([\w\.]+)[\r\n]#', array('core', 'process_hook'), $lib_str);
```

## 38. Hook 使用技巧与注意事项

### 38.1 编写规范

**Hook 文件基本结构：**
```php
<?php
// 注释说明本 Hook 的作用
// 插件: my_plugin
// 功能: 在内容详情页添加统计代码

// 业务代码（直接执行，不需要 return）
$content_id = isset($GLOBALS['forum']['aid']) ? $GLOBALS['forum']['aid'] : 0;
if($content_id) {
    // 执行你的逻辑
    // 例如: 记录浏览统计、注入自定义变量、修改全局数据等
}
```

**关键规则：**
- Hook 代码不需要 `return` 语句，直接执行即可
- 不需要包裹函数，代码直接写在文件顶层
- 可以访问当前作用域的所有变量
- 对于 `_before` 类型的 Hook，修改变量会影响后续业务逻辑
- 对于 `_after` 类型的 Hook，可以读取业务执行后的结果

### 38.2 多插件同名 Hook 合并

当多个插件定义了同一个 Hook 文件名时，系统按以下规则处理：

```php
// 插件A: rank=100, 提供 index_control_index_before.php
// 插件B: rank=200, 提供 index_control_index_before.php
// 插件C: rank=300, 提供 index_control_index_before.php

// 编译后执行顺序: 插件A → 插件B → 插件C
// （按 rank 从小到大依次执行）
```

**合并后的编译结果：**
```php
// ====== Hook 注入: 插件A (rank=100) ======
// 插件A 的代码...
// ====== Hook 注入: 插件B (rank=200) ======
// 插件B 的代码...
// ====== Hook 注入: 插件C (rank=300) ======
// 插件C 的代码...

// ====== 原始业务代码 ======
// 业务逻辑...
```

**设置 rank 的方法：**
```php
// conf.php
return array(
    'name' => '我的插件',
    'rank' => 50,    // 越小越先执行，默认 100
);
```

### 38.3 常见陷阱

| 陷阱 | 说明 | 解决方案 |
|------|------|---------|
| **使用 return** | Hook 中写 `return` 会被 `clear_code()` 清理，导致后续代码跳过 | 不要使用 `return`，直接写执行代码 |
| **输出内容** | Hook 中直接 `echo` 会破坏页面结构 | 使用变量赋值，让模板负责输出 |
| **变量覆盖** | 多个 Hook 修改变量时产生冲突 | 使用唯一变量名前缀 |
| **语法错误** | Hook 文件有语法错误会导致编译失败 | 开发时先在独立文件中测试 |
| **路径引用** | Hook 中引用其他文件使用相对路径 | 使用 `PLUGIN_PATH` 常量 |
| **Hook 未生效** | 插件未启用或 rank 设置不当 | 检查 `plugin.inc.php` 中的启用状态 |
| **无限循环** | Hook 修改了触发自身的逻辑 | 避免在 Hook 中调用会触发同一 Hook 的方法 |
| **使用 dirname(__FILE__)** | Hook 被编译内联到 runcache/，`__FILE__` 指向错误路径 | 使用 `PLUGIN_PATH` 或 `ROOT_PATH` 绝对路径 |
| **调用全局 db()** | LeCMS 没有全局 `db()` 函数，会导致 Fatal Error | 使用 `$this->db`（Hook 被内联到 control 方法中，$this 可用） |

**dirname(__FILE__) 陷阱详解：**

```php
<?php
// ❌ 错误：Hook 文件中使用 dirname(__FILE__)
require_once dirname(__FILE__) . '/model/my_model.class.php';
// 编译后 __FILE__ 指向 runcache/lecms_control/base_control.class.php
// 路径变为 runcache/model/my_model.class.php — 完全错误！

// ✅ 正确：使用 PLUGIN_PATH 常量
require_once PLUGIN_PATH . 'my_plugin/model/my_model.class.php';

// ✅ 正确：使用 ROOT_PATH 绝对路径
require_once ROOT_PATH . 'plugin/my_plugin/model/my_model.class.php';
```

**Hook 中的数据库访问：**

```php
<?php
// Hook 文件被编译内联到 control 方法中，因此 $this 可用
// 可以通过 $this->db 访问数据库

// ✅ 正确：使用 $this->db
$tablepre = $this->db->tablepre;
$this->db->query("INSERT INTO `{$tablepre}my_table` (`name`) VALUES ('test')");

// ✅ 正确：使用 $_ENV 全局变量
$site_id = CURRENT_SITE_ID;
$kv = $this->kv->get('my_config');

// ❌ 错误：不要调用不存在的 db() 全局函数
$rows = db()->fetch_all("SELECT ...");  // Fatal Error!
```

**调试技巧：**
```php
// 方法1: 在 Hook 中写日志
file_put_contents(RUNTIME_PATH.'hook_debug.log',
    date('Y-m-d H:i:s')." Hook triggered\n",
    FILE_APPEND
);

// 方法2: 临时输出（仅在开发环境使用）
if(DEBUG) {
    echo "<!-- Hook: index_control_index_before executed -->";
}
```

---

<!-- 新增 -->
## 39. Hook 冲突处理与最佳实践

### 39.1 多插件同名 Hook 合并与冲突处理

当多个插件定义了同一个 Hook 文件名时，系统按插件 `rank` 值**从小到大**依次合并执行。rank 值越小，执行越靠前。

```
// 插件A: rank=100, 提供 index_control_index_before.php
// 插件B: rank=200, 提供 index_control_index_before.php
// 插件C: rank=300, 提供 index_control_index_before.php

// 编译后执行顺序: 插件A → 插件B → 插件C
// （按 rank 从小到大依次执行）
```

**合并后的编译结果：**
```php
// ====== Hook 注入: 插件A (rank=100) ======
// 插件A 的代码...
// ====== Hook 注入: 插件B (rank=200) ======
// 插件B 的代码...
// ====== Hook 注入: 插件C (rank=300) ======
// 插件C 的代码...

// ====== 原始业务代码 ======
// 业务逻辑...
```

**设置 rank 的方法：**
```php
// conf.php
return array(
    'name' => '我的插件',
    'rank' => 50,    // 越小越先执行，默认 100
);
```

**冲突处理策略：**

| 冲突场景 | 说明 | 解决方案 |
|----------|------|---------|
| 变量覆盖 | 多个 Hook 修改同一个变量 | 使用唯一变量名前缀（如 `my_plugin_xxx`） |
| 执行顺序依赖 | 插件A依赖插件B的执行结果 | 调整 rank 值，确保依赖顺序正确 |
| 数据竞争 | 同时修改数据库 | 使用数据库事务或锁定机制 |
| 模板变量冲突 | 多个 Hook assign 同一变量 | 使用 `isset()` 检查后再覆盖 |
| 逻辑矛盾 | 不同 Hook 对同一逻辑产生相反效果 | 合并到一个 Hook 文件中，或使用开关配置 |

**推荐做法：**
1. 使用 `_after` 类型的 Hook 而非 `_before`，避免干扰业务逻辑
2. 在变量名中加入插件标识，避免与其他插件冲突
3. 使用 `isset()` 检查后再修改变量，避免覆盖其他插件的数据

### 39.2 Hook 命名规范

**命名规则：**
```
{模块名}_{控制器/模型名}_{方法/功能名}_{位置}.php
```

**位置后缀：**

| 后缀 | 含义 |
|------|------|
| `_before.php` | 执行前 |
| `_after.php` | 执行后 |
| `_start.php` | 开始处 |
| `_end.php` | 结束处 |
| `_center.php` | 中心区域 |
| `_success.php` | 成功处理 |
| `_failed.php` | 失败处理 |

**常用前缀：**

| 前缀 | 模块 |
|------|------|
| `admin_` | 后台控制器 |
| `base_control_` | 基础控制器 |
| `parseurl_` | URL 解析 |
| `block_` | Block 模块 |
| `cms_` | 内容模型 |
| `user_` | 用户模型 |
| `sitemap_` | 站点地图 |

### 39.3 常见使用场景示例

#### 场景1：修改内容页 SEO

```php
<?php
// hook show_control_index_seo_before.php

// 自定义SEO标题
if($this->_cid == 1) {
    $this->_cfg['titles'] = '自定义标题 - ' . $this->_cfg['webname'];
}
```

#### 场景2：添加内容处理逻辑

```php
<?php
// hook cms_content_model_xadd_cms_content_data_before.php

// 添加内容前处理自定义字段
$data['custom_field'] = htmlspecialchars($_POST['custom_field']);
```

#### 场景3：修改列表数据

```php
<?php
// hook block_list_list_arr_after.php

// 修改列表数据
foreach($list_arr as $key => $val) {
    $list_arr[$key]['custom_field'] = format_custom_field($val['id']);
}
```

#### 场景4：添加统计代码

```php
<?php
// hook index_control_index_after.php

// 在首页底部添加统计代码
$this->assign('统计代码', '<script>...</script>');
```

### 39.4 编写规范

**Hook 文件基本结构：**
```php
<?php
// 注释说明本 Hook 的作用
// 插件: my_plugin
// 功能: 在内容详情页添加统计代码

// 业务代码（直接执行，不需要 return）
$content_id = isset($GLOBALS['forum']['aid']) ? $GLOBALS['forum']['aid'] : 0;
if($content_id) {
    // 执行你的逻辑
    // 例如: 记录浏览统计、注入自定义变量、修改全局数据等
}
```

**关键规则：**
- Hook 代码不需要 `return` 语句，直接执行即可
- 不需要包裹函数，代码直接写在文件顶层
- 可以访问当前作用域的所有变量
- 对于 `_before` 类型的 Hook，修改变量会影响后续业务逻辑
- 对于 `_after` 类型的 Hook，可以读取业务执行后的结果

### 39.5 性能与维护建议

1. **合理使用**：不要过度使用 Hook，每个 Hook 都会增加编译时间和运行时开销
2. **命名规范**：遵循系统的 Hook 命名规范，便于维护和排查问题
3. **错误处理**：Hook 文件中要做好错误处理，避免语法错误导致编译失败
4. **缓存注意**：修改 Hook 后需要清理缓存（删除 `runcache/` 目录下的缓存文件）
5. **版本兼容**：LeCMS 版本升级后，检查 Hook 点是否有变动
6. **测试充分**：在开发环境中充分测试 Hook 效果后再部署到生产环境

---

## 40. Hook 机制总结

### 40.1 Hook 机制优点

1. **无侵入性**：不修改核心代码即可扩展功能
2. **灵活性**：可在任意位置插入自定义逻辑
3. **可维护性**：插件与核心代码分离
4. **可复用性**：插件可在多个项目中使用
5. **零运行时开销**：Hook 在编译时完成，运行时直接执行编译后的代码

### 40.2 Hook 分布统计

| 模块 | Hook 数量 | 占比 |
|------|-----------|------|
| Block 模块 | 243 | 28.8% |
| CMS 内容模型 | 153 | 18.1% |
| URL 解析 | 84 | 10.0% |
| 用户系统 | 56 | 6.6% |
| 站点地图 | 34 | 4.0% |
| 个人中心 | 32 | 3.8% |
| 分类管理 | 33 | 3.9% |
| 其他 | 209+ | 24.8% |
| **总计** | **844** | **100%** |

### 40.3 核心流程回顾

```
1. 开发者创建 Hook 文件: plugin/{插件名}/hook/{hook名}.php
2. 系统启动时编译: core::process_all() 扫描所有 Hook 标记
3. 正则匹配: preg_replace_callback 匹配 // hook xxx.php
4. 文件查找: 在已启用插件的根目录和 hook/ 子目录查找
5. 代码清理: clear_code() 移除 PHP 标签和 exit 声明
6. 合并写入: 按 rank 排序后合并写入 runcache/ 缓存文件
7. 运行时执行: 直接执行编译后的缓存文件
```查找
5. 代码清理: clear_code() 移除 PHP 标签和 exit 声明
6. 合并写入: 按 rank 排序后合并写入 runcache/ 缓存文件
7. 运行时执行: 直接执行编译后的缓存文件
```
<!-- 新增结束 -->

---

