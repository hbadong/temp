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
  - [ ] 3.3 创建五行分析类 `yzmphp/core/class/wuxing.class.php`
  - [ ] 3.4 创建起名引擎类 `yzmphp/core/class/name_engine.class.php`

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

- [ ] 6. 实现五行分析算法
  - [ ] 6.1 实现汉字五行属性数据库查询
  - [ ] 6.2 实现根据偏旁部首判断五行
  - [ ] 6.3 实现五行相生相克分析
  - [ ] 6.4 实现五行强弱计算
  - [ ] 6.5 实现用神喜神确定算法

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

- [ ] 8. 创建API接口控制器
  - [ ] 8.1 创建黄历API接口
  - [ ] 8.2 创建热门排行API接口
  - [ ] 8.3 创建搜索API接口

## 阶段四：模型层开发

- [ ] 9. 创建数据模型
  - [ ] 9.1 创建汉字模型 `application/qiming/model/character_model.class.php`
  - [ ] 9.2 创建诗词模型 `application/qiming/model/poetry_model.class.php`
  - [ ] 9.3 创建八卦模型 `application/qiming/model/bagua_model.class.php`
  - [ ] 9.4 创建黄历模型 `application/qiming/model/horoscope_model.class.php`
  - [ ] 9.5 创建姓名排行模型 `application/qiming/model/ranking_model.class.php`

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

- [ ] 11. 创建公共模板组件
  - [ ] 11.1 创建头部导航模板
  - [ ] 11.2 创建页脚模板
  - [ ] 11.3 创建黄历组件模板
  - [ ] 11.4 创建热门排行组件模板

## 阶段六：后台功能开发

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
