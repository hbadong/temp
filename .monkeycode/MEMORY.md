# 用户指令记忆

本文件记录了用户的指令、偏好和教导，用于在未来的交互中指导未来的交互和定制。

## 格式

### 用户指令条目
用户指令条目应遵循以下格式：

[用户指令摘要]
- Date: [YYYY-MM-DD]
- Context: [提及的场景或时间]
- Instructions:
  - [用户教导或指示的内容，逐行描述]

### 项目知识条目
Agent 在任务执行过程中发现的条目应遵循以下格式：

[项目知识摘要]
- Date: [YYYY-MM-DD]
- Context: Agent 在执行 [具体任务描述] 时发现
- Category: [代码结构|代码模式|代码生成|构建方法|测试方法|依赖关系|环境配置]
- Instructions:
  - [具体的知识点，逐行描述]

## 去重策略
- 添加新条目前，检查是否存在相似或相同的指令
- 若发现重复，跳过新条目或与已有条目合并
- 合并时，更新上下文或日期信息
- 这有助于避免冗余条目，保持记忆文件整洁

## 条目

[项目知识摘要]
- Date: 2026-03-22
- Context: Agent 在开发起名网站项目时发现
- Category: 代码结构
- Instructions:
  - 项目采用 YzmCMS V7.5 框架，单一入口 index.php
  - 起名系统模块位于 application/qiming/ 目录
  - 前台控制器：application/qiming/controller/index.class.php（包含 init, baobao, bazi, shici, ceshi, zhouyi, gongsi, kxzd, result, test_result 方法）
  - API控制器：application/qiming/controller/api.class.php
  - 康熙字典控制器：application/qiming/controller/kxzd.class.php
  - 数据模型位于 application/qiming/model/ 目录
  - 前台模板位于 application/qiming/view/default/ 目录
  - 后台管理位于 application/admin/controller/qiming.class.php
  - 后台视图位于 application/admin/view/qiming/ 目录
  - 核心算法类位于 yzmphp/core/class/ 目录（bazi.class.php, wuge.class.php, wuxing.class.php, name_engine.class.php）
  - 数据库脚本位于 install/qiming_install.sql

[项目知识摘要]
- Date: 2026-03-22
- Context: Agent 在开发起名网站项目时发现
- Category: 代码模式
- Instructions:
  - 控制器中使用 `include template('qiming', 'template_name')` 渲染模板
  - 模型调用使用 `D('model_name')` 方式
  - 后台视图使用 `$this->admin_tpl('qiming/view_name')` 方法
  - 模板中使用 `{U('qiming/index/action')}` 生成 URL
  - 模板中使用 `{foreach}` 和 `{if}` 等标签进行循环和条件判断
  - 核心算法类通过 `yzm_base::load_sys_class()` 加载

[项目知识摘要]
- Date: 2026-03-22
- Context: Agent 在开发起名网站项目时发现
- Category: 构建方法
- Instructions:
  - 数据库初始化：运行 install/qiming_install.sql 脚本
  - 核心算法包括：八字计算（bazi.class.php）、五格计算（wuge.class.php）、五行分析（wuxing.class.php）、起名引擎（name_engine.class.php）
  - 前台路由：/m=qiming&c=index&a=action
  - 后台路由：/m=admin&c=qiming&a=action

[项目知识摘要]
- Date: 2026-03-22
- Context: Agent 在开发起名网站项目时发现
- Category: 测试方法
- Instructions:
  - 起名结果页面：/m=qiming&c=index&a=result&surname=姓&gender=1&birthdate=2024-01-01&birthtime=0
  - 姓名测试页面：/m=qiming&c=index&a=ceshi
  - 康熙字典页面：/m=qiming&c=kxzd&a=init
  - 黄历API：/m=qiming&c=api&a=huangli&date=2024-01-01
  - 排行API：/m=qiming&c=api&a=ranking&type=boy-char
