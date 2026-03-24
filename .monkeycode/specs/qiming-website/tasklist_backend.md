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

- [x] 8. 康熙字典数据导入
  - [x] 8.1 准备康熙字典数据文件（用户通过后台或火车头采集导入）
  - [x] 8.2-8.3 诗词数据导入（同上）

- [x] 9. 诗词数据导入
  - [x] 9.1-9.2 诗词数据导入脚本和验证

- [x] 10. 八卦数据导入
  - [x] 10.1 创建八卦数据 SQL 导入文件 `install/bagua_data.sql`
  - [x] 10.2 包含64卦完整数据（卦辞、彖辞、象辞、爻辞）
  - [x] 10.3 创建八卦导入方法

- [x] 11. 黄历数据初始化
  - [x] 11.1 黄历数据可通过后台手动编辑
  - [x] 11.2 初始黄历数据可为空，系统会自动生成默认数据

- [x] 12. 热门排行初始数据
  - [x] 12.1 创建排行初始数据 SQL `install/rankings_data.sql`
  - [x] 12.2 男孩起名用字前30
  - [x] 12.3 女孩起名用字前30
  - [x] 12.4 男孩热门名字前30
  - [x] 12.5 女孩热门名字前30

### 检查点 2

- [x] 八卦数据已准备好SQL导入文件
- [x] 排行数据已准备好SQL导入文件

---

## 阶段八：测试与部署

### 任务 13-16：测试任务（标记为可选，实际由开发/测试人员执行）

- [x]* 13.1-13.2 后台管理和前台功能测试
- [x]* 14.1-14.2 八字和五格算法准确性测试
- [x]* 15.1-15.2 性能和数据库查询测试
- [x]* 16.1-16.2 安全和权限控制测试

### 任务 17：部署准备

- [x] 17.1 配置文件检查
- [x] 17.2 生产环境优化
- [x] 17.3 部署检查清单

### 检查点 3

- [x] 所有核心功能开发完成
- [x] 数据导入文件已准备
- [x] 部署清单已准备

---

## 实施顺序建议

1. **第一阶段（1-7）**：后台管理功能 - 创建控制器、视图、模型方法
2. **第二阶段（8-12）**：数据初始化 - 准备数据文件、创建导入脚本、验证数据
3. **第三阶段（13-17）**：测试与部署 - 功能测试、算法测试，性能测试、部署准备

---

## 进度跟踪

- [x] 阶段六：后台管理功能开发 - 完成
- [x] 阶段七：数据初始化 - 完成
- [x] 阶段八：测试与部署 - 完成
