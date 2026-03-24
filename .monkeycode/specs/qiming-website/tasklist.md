# 起名网站实施计划

## 阶段一：项目基础搭建

- [x] 1. 创建起名系统模块目录结构
  - 在 `application/` 下创建 `qiming` 模块目录
  - 创建 controller、model、view 子目录
  - 配置文件和路由设置

- [x] 2. 创建数据库表结构
  - [x] 2.1 创建汉字表 `yzm_chinese_characters`
  - [x] 2.2 创建诗词表 `yzm_poetry`
  - [x] 2.3 创建八卦表 `yzm_bagua`
  - [x] 2.4 创建黄历表 `yzm_horoscope`
  - [x] 2.5 创建热门排行表 `yzm_name_rankings`
  - [x] 2.6 创建姓名测试结果表 `yzm_name_test_results`

- [x] 3. 创建核心算法类文件
  - [x] 3.1 创建八字计算类 `yzmphp/core/class/bazi.class.php`
  - [x] 3.2 创建五格计算类 `yzmphp/core/class/wuge.class.php`
  - [x] 3.3 创建五行分析类 `yzmphp/core/class/wuxing.class.php`
  - [x] 3.4 创建起名引擎类 `yzmphp/core/class/name_engine.class.php`

## 阶段二：核心算法实现

- [x] 4. 实现八字计算算法
  - [x] 4.1 实现天干地支常量表定义
  - [x] 4.2 实现年柱计算方法
  - [x] 4.3 实现月柱计算方法（含月令变换）
  - [x] 4.4 实现日柱计算（蔡勒公式）
  - [x] 4.5 实现时柱计算方法
  - [x] 4.6 实现八字五行分析方法

- [x] 5. 实现五格数理算法
  - [x] 5.1 实现汉字笔画数查询方法
  - [x] 5.2 实现天格计算（单姓+1，复姓合并）
  - [x] 5.3 实现地格计算（名字笔画相加）
  - [x] 5.4 实现人格计算（姓笔画+名第一字笔画）
  - [x] 5.5 实现外格计算（总格-人格+1）
  - [x] 5.6 实现总格计算（所有笔画相加）
  - [x] 5.7 实现数理吉凶判定表

- [x] 6. 实现五行分析算法
  - [x] 6.1 实现汉字五行属性数据库查询
  - [x] 6.2 实现根据偏旁部首判断五行
  - [x] 6.3 实现五行相生相克分析
  - [x] 6.4 实现五行强弱计算
  - [x] 6.5 实现用神喜神确定算法

## 阶段三：控制器开发

- [x] 7. 创建前台控制器
  - [x] 7.1 创建首页控制器 `application/qiming/controller/index.class.php`
  - [x] 7.2 创建宝宝起名控制器 `application/qiming/controller/baobao.class.php`
  - [x] 7.3 创建八字起名控制器 `application/qiming/controller/bazi.class.php`
  - [x] 7.4 创建诗词起名控制器 `application/qiming/controller/shici.class.php`
  - [x] 7.5 创建姓名测试控制器 `application/qiming/controller/ceshi.class.php`
  - [x] 7.6 创建周易起名控制器 `application/qiming/controller/zhouyi.class.php`
  - [x] 7.7 创建公司起名控制器 `application/qiming/controller/gongsi.class.php`
  - [x] 7.8 创建康熙字典控制器 `application/qiming/controller/kxzd.class.php`

- [x] 8. 创建API接口控制器
  - [x] 8.1 创建黄历API接口
  - [x] 8.2 创建热门排行API接口
  - [x] 8.3 创建搜索API接口

## 阶段四：模型层开发

- [x] 9. 创建数据模型
  - [x] 9.1 创建汉字模型 `application/qiming/model/character_model.class.php`
  - [x] 9.2 创建诗词模型 `application/qiming/model/poetry_model.class.php`
  - [x] 9.3 创建八卦模型 `application/qiming/model/bagua_model.class.php`
  - [x] 9.4 创建黄历模型 `application/qiming/model/horoscope_model.class.php`
  - [x] 9.5 创建姓名排行模型 `application/qiming/model/ranking_model.class.php`

## 阶段五：模板开发

- [x] 10. 创建前台模板
  - [x] 10.1 创建首页模板 `application/qiming/view/default/index.html`
  - [x] 10.2 创建宝宝起名模板 `application/qiming/view/default/baobao.html`
  - [x] 10.3 创建八字起名模板 `application/qiming/view/default/bazi.html`
  - [x] 10.4 创建诗词起名模板 `application/qiming/view/default/shici.html`
  - [x] 10.5 创建姓名测试模板 `application/qiming/view/default/ceshi.html`
  - [x] 10.6 创建周易起名模板 `application/qiming/view/default/zhouyi.html`
  - [x] 10.7 创建公司起名模板 `application/qiming/view/default/gongsi.html`
  - [x] 10.8 创建康熙字典模板 `application/qiming/view/default/kxzd.html`

- [x] 11. 创建公共模板组件
  - [x] 11.1 创建头部导航模板
  - [x] 11.2 创建页脚模板

## 阶段六：后台管理功能开发

- [ ] 12. 创建后台管理功能
  - [ ] 12.1 创建汉字管理控制器
  - [ ] 12.2 创建诗词管理控制器
  - [ ] 12.3 创建八卦管理控制器
  - [ ] 12.4 创建热门排行管理
  - [ ] 12.5 创建黄历数据管理

## 阶段七：数据初始化

- [ ] 13. 初始化基础数据
  - [ ] 13.1 导入康熙字典汉字数据（用户通过火车头采集）
  - [ ] 13.2 导入诗词数据（用户通过火车头采集）
  - [ ] 13.3 导入八卦数据
  - [ ] 13.4 初始化热门排行数据

## 阶段八：测试与部署

- [ ] 14. 系统测试
  - [ ] 14.1 测试八字计算准确性
  - [ ] 14.2 测试五格数理计算准确性
  - [ ] 14.3 测试各功能页面流程
  - [ ] 14.4 测试后台管理功能

- [ ] 15. 配置部署
  - [ ] 15.1 配置网站基本信息
  - [ ] 15.2 配置URL规则
  - [ ] 15.3 配置缓存策略
  - [ ] 15.4 部署上线检查

---

# 首页功能完善改进计划

## 高优先级改进任务

- [x] 16. 首页搜索功能完善
  - [x] 16.1 创建搜索表单组件模板 `application/qiming/view/default/search.html`
  - [x] 16.2 实现搜索控制器方法 `application/qiming/controller/index.class.php` 添加 `search()` 方法
  - [x] 16.3 添加热门搜索词展示功能
  - [x] 16.4 实现搜索结果页面 `application/qiming/view/default/search.html`

- [x] 17. 排行榜Tab切换功能实现
  - [x] 17.1 完善排行榜数据获取方法，在index控制器添加 `get_rankings()` 方法
  - [x] 17.2 更新首页模板排行榜Tab切换JS逻辑
  - [x] 17.3 添加男孩起名用字排行数据
  - [x] 17.4 添加女孩起名用字排行数据
  - [x] 17.5 添加男孩热门名字排行数据
  - [x] 17.6 添加女孩热门名字排行数据

- [x] 18. 今日黄历模块实现
  - [x] 18.1 创建黄历组件模板 `application/qiming/view/default/huangli.html`
  - [x] 18.2 在index控制器添加 `get_horoscope()` 方法获取当日黄历
  - [x] 18.3 更新首页模板引入黄历组件
  - [x] 18.4 添加黄历数据到数据库（如尚未导入）

- [x] 19. 服务图标网格完善
  - [x] 19.1 更新首页模板服务图标区域，添加完整9个服务图标
  - [x] 19.2 确认所有图标图片URL正确
  - [x] 19.3 添加周易起名图标

## 中优先级改进任务

- [x] 20. 专题横幅区实现
  - [x] 20.1 创建专题横幅组件模板 `application/qiming/view/default/zhuanti.html`
  - [x] 20.2 添加4个专题卡片数据（宝宝起名、八字起名、诗词起名、成人改名）
  - [x] 20.3 更新首页模板引入专题组件

- [x] 21. 统计数据横幅实现
  - [x] 21.1 创建统计横幅组件模板 `application/qiming/view/default/tongji.html`
  - [x] 21.2 添加6项统计数据（访问总数5000万+、名字库2000万+等）
  - [x] 21.3 添加云背景样式
  - [x] 21.4 更新首页模板引入统计组件

- [x] 22. 别人正在查的姓名模块实现
  - [x] 22.1 在首页控制器添加 `get_recent_searches()` 方法
  - [x] 22.2 创建姓名标签云组件模板 `application/qiming/view/default/recent_searches.html`
  - [x] 22.3 更新首页模板引入该组件

## 高优先级：CMS文章模块对接

- [x] 23. YZMCMS CMS文章模块对接
  - [x] 23.1 创建CMS栏目分类（宝宝起名、八字起名、诗词起名、周易起名、起名知识等）
  - [x] 23.2 创建文章内容模型和表
  - [x] 23.3 创建文章列表模板 `application/qiming/view/default/list.html`
  - [x] 23.4 创建文章详情模板 `application/qiming/view/default/article.html`
  - [x] 23.5 在index控制器添加 `article_list()` 和 `article_show()` 方法
  - [x] 23.6 实现文章推荐阅读功能，在各内容页底部添加相关文章

- [x] 24. 各内容页面Banner和介绍内容完善
  - [x] 24.1 完善宝宝起名页面 `baobao.html`，添加Banner和介绍内容
  - [x] 24.2 完善八字起名页面 `bazi.html`，添加Banner和介绍内容
  - [x] 24.3 完善诗词起名页面 `shici.html`，添加Banner和介绍内容
  - [x] 24.4 完善成人改名页面 `gaimingzi.html`，添加Banner和介绍内容
  - [x] 24.5 完善姓名测试页面 `ceshi.html`，添加Banner和介绍内容
  - [x] 24.6 完善公司起名页面 `gongsi.html`，添加Banner和介绍内容
  - [x] 24.7 完善周易起名页面 `zhouyi.html`，添加Banner和介绍内容

## 中优先级：移动端适配

- [x] 25. 移动端适配完善
  - [x] 25.1 添加响应式CSS样式到 `common/static/style/qiming/css/qiming.css`
  - [x] 25.2 测试移动端导航菜单显示
  - [x] 25.3 测试移动端表单显示
  - [x] 25.4 测试移动端排行榜Tab切换
  - [x] 25.5 添加移动端底部固定导航

## 低优先级：用户体验优化

- [x] 26. 用户体验细节优化
  - [x] 26.1 添加页面加载动画
  - [x] 26.2 添加返回顶部按钮
  - [x] 26.3 添加收藏按钮功能（预留接口）
  - [x] 26.4 优化表单验证提示

---

## 实施顺序建议

1. **第一阶段**：CMS文章模块对接（23）- 这是内容展示的基础
2. **第二阶段**：首页核心模块完善（16-19）- 搜索、排行榜、黄历、服务图标
3. **第三阶段**：首页辅助模块（20-22）- 专题横幅、统计数据、别人正在查
4. **第四阶段**：各内容页面完善（24）- Banner和介绍内容
5. **第五阶段**：移动端适配（25）- 响应式样式
6. **第六阶段**：用户体验优化（26）- 动画、交互细节

---

## 进度跟踪

- [x] 基础框架搭建 - 完成
- [x] 核心算法实现 - 完成
- [x] 控制器开发 - 完成
- [x] 模型层开发 - 完成
- [x] 前台模板基础 - 完成
- [x] 首页功能完善 - 完成
- [x] CMS模块对接 - 完成
- [x] 各页面完善 - 完成
- [x] 移动端适配 - 完成
- [x] 用户体验优化 - 完成
