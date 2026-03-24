# 起名网站后台管理与数据初始化实施计划

---

## 阶段六：后台管理功能开发

### 任务 1：创建后台管理基础架构

- [ ] 1.1 创建后台控制器基类 `application/admin/controller/qiming_common.class.php`
  - 继承 YzmCMS 后台基类
  - 定义权限验证和方法
  - 引入模型层

- [ ] 1.2 创建后台视图目录结构
  - `application/admin/view/qiming/` - 起名系统后台视图目录
  - 创建子目录：characters, poetry, bagua, horoscope, rankings

### 任务 2：汉字管理功能

- [ ] 2.1 创建汉字管理控制器 `application/admin/controller/qiming_characters.class.php`
  - 实现列表页 `characters()` 方法
  - 实现添加页 `add()` 方法
  - 实现编辑页 `edit()` 方法
  - 实现删除方法 `delete()`
  - 实现批量删除方法 `batch_delete()`
  - 实现搜索方法 `search()`

- [ ] 2.2 创建汉字管理视图 `application/admin/view/qiming/characters/`
  - 创建列表页 `list.html`
  - 创建添加/编辑表单页 `form.html`
  - 包含字段：汉字、拼音、注音、部首、笔画、五行、康熙字典解释、词语解释、起名寓意

- [ ] 2.3 创建汉字模型方法
  - 在 `application/qiming/model/character_model.class.php` 添加：
    - `admin_list()` - 后台列表查询
    - `admin_add()` - 添加汉字
    - `admin_edit()` - 编辑汉字
    - `admin_delete()` - 删除汉字
    - `import_from_array()` - 批量导入

### 任务 3：诗词管理功能

- [ ] 3.1 创建诗词管理控制器 `application/admin/controller/qiming_poetry.class.php`
  - 实现列表页 `poetry()` 方法
  - 实现添加页 `add()` 方法
  - 实现编辑页 `edit()` 方法
  - 实现删除方法 `delete()`
  - 实现按类型筛选功能

- [ ] 3.2 创建诗词管理视图 `application/admin/view/qiming/poetry/`
  - 创建列表页 `list.html`
  - 创建添加/编辑表单页 `form.html`
  - 包含字段：标题、作者、类型(唐诗/宋词/诗经/楚辞)、内容、朝代、主题

- [ ] 3.3 创建诗词模型方法
  - 在 `application/qiming/model/poetry_model.class.php` 添加：
    - `admin_list()` - 后台列表查询
    - `admin_add()` - 添加诗词
    - `admin_edit()` - 编辑诗词
    - `admin_delete()` - 删除诗词
    - `get_by_type()` - 按类型获取

### 任务 4：八卦管理功能

- [ ] 4.1 创建八卦管理控制器 `application/admin/controller/qiming_bagua.class.php`
  - 实现列表页 `bagua()` 方法
  - 实现添加页 `add()` 方法
  - 实现编辑页 `edit()` 方法
  - 实现删除方法 `delete()`

- [ ] 4.2 创建八卦管理视图 `application/admin/view/qiming/bagua/`
  - 创建列表页 `list.html`
  - 创建添加/编辑表单页 `form.html`
  - 包含字段：卦名、卦辞、彖辞、象辞、爻辞、五行

- [ ] 4.3 创建八卦模型方法
  - 在 `application/qiming/model/bagua_model.class.php` 添加：
    - `admin_list()` - 后台列表查询
    - `admin_add()` - 添加卦象
    - `admin_edit()` - 编辑卦象
    - `admin_delete()` - 删除卦象

### 任务 5：热门排行管理功能

- [ ] 5.1 创建排行管理控制器 `application/admin/controller/qiming_rankings.class.php`
  - 实现列表页 `rankings()` 方法
  - 实现编辑排行 `edit()` 方法
  - 实现批量更新方法 `batch_update()`
  - 实现手动刷新方法 `refresh()`

- [ ] 5.2 创建排行管理视图 `application/admin/view/qiming/rankings/`
  - 创建列表页 `list.html`
  - 创建编辑表单 `form.html`
  - 支持按月份、类型筛选

- [ ] 5.3 创建排行模型方法
  - 在 `application/qiming/model/ranking_model.class.php` 添加：
    - `admin_list()` - 后台列表查询
    - `admin_update()` - 更新排行数据
    - `refresh_from_search_logs()` - 从搜索日志刷新排行

### 任务 6：黄历数据管理功能

- [ ] 6.1 创建黄历管理控制器 `application/admin/controller/qiming_horoscope.class.php`
  - 实现当前日期黄历 `today()` 方法
  - 实现历史黄历列表 `list()` 方法
  - 实现编辑黄历 `edit()` 方法
  - 实现生成黄历 `generate()` 方法

- [ ] 6.2 创建黄历管理视图 `application/admin/view/qiming/horoscope/`
  - 创建今日黄历页 `today.html`
  - 创建历史列表页 `list.html`
  - 创建编辑表单 `form.html`

- [ ] 6.3 创建黄历模型方法
  - 在 `application/qiming/model/horoscope_model.class.php` 添加：
    - `admin_update()` - 更新黄历数据
    - `generate_for_date()` - 生成指定日期的黄历

### 任务 7：测试记录管理功能

- [ ] 7.1 创建测试记录管理控制器
  - 实现姓名测试记录列表
  - 实现导出功能
  - 实现统计分析

- [ ] 7.2 创建测试记录视图
  - 创建记录列表页
  - 创建统计分析页

### 检查点 1

- 验证所有后台管理功能正常访问
- 验证表单提交和数据保存正确
- 验证权限控制有效

---

## 阶段七：数据初始化

### 任务 8：康熙字典数据导入

- [ ] 8.1 准备康熙字典数据文件
  - 数据格式：JSON 或 CSV
  - 包含字段：汉字、拼音、注音、部首、笔画、五行、康熙字典解释、词语解释、起名寓意

- [ ] 8.2 创建数据导入脚本 `application/admin/controller/qiming_import.class.php`
  - 实现 `import_characters()` 方法
  - 支持文件上传和批量导入
  - 实现进度显示
  - 实现错误回滚

- [ ] 8.3 创建导入视图 `import_characters.html`
  - 文件上传表单
  - 导入进度显示
  - 错误报告展示

- [ ] 8.4 验证数据完整性
  - 检查导入数量
  - 抽查数据准确性
  - 验证索引正确

### 任务 9：诗词数据导入

- [ ] 9.1 准备诗词数据文件
  - 数据格式：JSON
  - 包含字段：标题、作者、类型、内容、朝代、主题

- [ ] 9.2 创建诗词导入脚本
  - 实现 `import_poetry()` 方法
  - 支持分批导入
  - 实现进度显示

- [ ] 9.3 验证诗词数据
  - 检查唐诗数量
  - 检查宋词数量
  - 检查诗经楚辞

### 任务 10：八卦数据导入

- [ ] 10.1 创建八卦数据 SQL 导入文件
  - 包含64卦完整数据
  - 包含卦辞、彖辞、象辞、爻辞

- [ ] 10.2 创建八卦导入方法
  - 实现 `import_bagua()` 方法

### 任务 11：黄历数据初始化

- [ ] 11.1 创建黄历数据生成脚本
  - 实现年度黄历批量生成
  - 支持农历转换
  - 支持节气计算

- [ ] 11.2 生成当年黄历数据
  - 生成12个月黄历
  - 包含宜忌事项
  - 包含吉凶方位

### 任务 12：热门排行初始数据

- [ ] 12.1 创建排行初始数据 SQL
  - 男孩起名用字前30
  - 女孩起名用字前30
  - 男孩热门名字前30
  - 女孩热门名字前30

- [ ] 12.2 导入初始排行数据

### 检查点 2

- 验证所有数据导入成功
- 验证数据查询功能正常
- 验证前台页面数据展示正确

---

## 阶段八：测试与部署

### 任务 13：功能测试

- [ ] 13.1 后台管理功能测试
  - [ ]* 测试汉字管理增删改查
  - [ ]* 测试诗词管理增删改查
  - [ ]* 测试八卦管理增删改查
  - [ ]* 测试排行管理功能
  - [ ]* 测试黄历管理功能

- [ ] 13.2 前台功能测试
  - [ ]* 测试首页各模块显示
  - [ ]* 测试搜索功能
  - [ ]* 测试排行榜Tab切换
  - [ ]* 测试表单提交和结果展示

### 任务 14：算法准确性测试

- [ ] 14.1 八字计算测试
  - [ ]* 测试已知生辰的八字结果
  - [ ]* 验证天干地支计算正确性
  - [ ]* 验证五行分析正确性

- [ ] 14.2 五格数理测试
  - [ ]* 测试已知姓名的五格结果
  - [ ]* 验证笔画数查询正确性
  - [ ]* 验证吉凶判定正确性

### 任务 15：性能测试

- [ ] 15.1 页面加载速度测试
  - [ ]* 测试首页加载时间 < 3秒
  - [ ]* 测试起名计算响应时间 < 2秒
  - [ ]* 测试八字计算响应时间 < 1秒

- [ ] 15.2 数据库查询测试
  - [ ]* 测试汉字查询效率
  - [ ]* 测试诗词检索效率

### 任务 16：安全测试

- [ ] 16.1 输入验证测试
  - [ ]* 测试 XSS 过滤有效性
  - [ ]* 测试 SQL 注入防护
  - [ ]* 测试 CSRF 令牌

- [ ] 16.2 权限控制测试
  - [ ]* 测试未登录访问后台
  - [ ]* 测试越权访问

### 任务 17：部署准备

- [ ] 17.1 配置文件检查
  - 检查数据库配置
  - 检查缓存配置
  - 检查URL路由配置

- [ ] 17.2 生产环境优化
  - 开启缓存
  - 压缩CSS/JS
  - 配置CDN（可选）

- [ ] 17.3 部署检查清单
  - 验证所有功能正常
  - 验证数据完整性
  - 验证备份完成

### 检查点 3

- 所有测试通过
- 性能指标达标
- 安全漏洞已修复

---

## 实施顺序建议

1. **第一阶段（1-7）**：后台管理功能 - 创建控制器、视图、模型方法
2. **第二阶段（8-12）**：数据初始化 - 准备数据文件、创建导入脚本、验证数据
3. **第三阶段（13-17）**：测试与部署 - 功能测试、算法测试、性能测试、部署准备

---

## 进度跟踪

- [ ] 阶段六：后台管理功能开发 - 待开始
- [ ] 阶段七：数据初始化 - 待开始
- [ ] 阶段八：测试与部署 - 待开始
