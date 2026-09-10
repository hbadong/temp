---
kind: build_system
name: LECMS 构建与部署系统
category: build_system
scope:
    - '**'
source_files:
    - index.php
    - install/index.php
    - install/config.sample.php
    - install/route.sample.php
    - install/plugin.sample.php
    - lecms/xiunophp/xiunophp.php
    - runcache/_lecms.php
---

LECMS 是一个基于 XiunoPHP 框架的 PHP+MySQL 内容管理系统，其构建与部署方式相对简单直接，主要依赖 Web 服务器（如 Apache/Nginx）和 PHP 环境运行，没有复杂的自动化构建流程。

**构建系统特点：**
- **无传统构建工具**：项目不包含 Makefile、Dockerfile、CI/CD 配置文件、package.json、composer.json 等现代构建工具配置
- **纯 PHP 直出模式**：通过 index.php 作为统一入口，直接加载 xiunophp 框架核心，无需编译或预处理步骤
- **运行时缓存机制**：使用 runcache 目录存储控制器、模型、视图的编译缓存，提升运行时性能
- **Web 安装向导**：通过 install/index.php 提供图形化安装界面，自动检测环境、创建数据库、生成配置文件

**部署流程：**
1. 将代码上传到 Web 服务器
2. 访问 /install/ 路径启动安装向导
3. 填写数据库信息和站点配置
4. 系统自动生成 lecms/config/config.inc.php 配置文件
5. 删除 install 目录完成部署

**版本管理：**
- 通过 Git 分支管理不同版本（当前分支名为 Q0DeR-MaG1C-BrAnCh-Fo2-NoN-GiT-Or-No-C0MmIt）
- README.md 中提供了 Gitee 仓库链接和 MyISAM/InnoDB 版本说明
- 支持 MIT 协议开源发布

**环境要求：**
- PHP 5.4.0 - 8.2.0 之间（安装程序中有版本检查）
- MySQL/MariaDB 数据库
- 支持 GD 库（图像处理）
- Web 服务器支持伪静态规则

**缓存与优化：**
- 运行时缓存位于 runcache/ 目录
- 支持代码压缩（CODE_COMPRESS 常量控制）
- 模板文件编译缓存
- 语言包缓存

由于这是一个传统的 PHP 项目，构建系统非常简单，主要依赖 PHP 解释器直接执行，没有现代化的构建流水线。