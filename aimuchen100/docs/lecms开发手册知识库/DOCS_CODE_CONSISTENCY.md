---
kind: changelog
name: LECMS 文档/代码一致性修复记录
category: meta
scope:
    - '**'
last_updated: 2026-09-06
---

# LECMS 文档/代码一致性修复记录

本文档跟踪 docs 文档与 aimuchen100 项目实际代码之间已发现并修复的不一致点。

## v1（2026-09-06）

### 1. 后台控制器数量

| 项 | 原文档 | 实际代码 | 修复 |
|---|---|---|---|
| 第一篇 §3.5 第 454 行 | "后台控制器（16个）" | 20 个（含 admin_control 基类） | ✅ 已改为 20 个 |
| 第一篇 §3.1 目录表 | "前台控制器（16个）" | 17 个（含 base_control 基类） | ✅ 已改为 17 个 |
| 第一篇 §3.5 第 486 行 | "前台控制器（15个）" | 17 个（含 base_control 基类） | ✅ 已改为 17 个 |
| 第一篇 §3.5 第 763 行 | "前台控制器 (15个)" | 17 个（含 base_control 基类） | ✅ 已改为 17 个 |

### 2. 前台主题目录

| 主题 | 原文档 | aimuchen100 项目实际 | 修复 |
|---|---|---|---|
| default / mobile / jiaoyu / justnews | "LECMS 通用发行版默认主题" | 不在项目中 | ✅ 加注：标注为通用发行版主题 |
| game / game955 / game955m / macfkm | 未提及 | 4 套实际主题 | ✅ 已补充说明 |

### 3. 业务子模块文档元数据

| 子模块 | 标题原写法 | 修复 |
|---|---|---|
| LECMS 前端主题资源 | "LECMS 前端主题资源（blog_Quietlee / default / justnews）" | ✅ 改为 "通用发行版…aimuchen100 项目实际：game / game955 / game955m / macfkm" |

### 4. 文档与代码未对齐（待处理）

| 不一致项 | 说明 |
|---|---|
| `plugin.inc.php` 启用 25 个 vs `plugin/` 仅 4 个 | 文档未提及；建议清理 plugin.inc.php 或补回缺失插件 |
| `lecms/plugin/` 23 个插件 | 文档里说"插件目录在 `lecms/plugin/`"，实际核心内置插件在 `lecms/plugin/`，业务插件在仓库根 `plugin/` |
| `routes` 文档 | 部分路由规则示例来自原 LECMS 通用发行版，不完全适用于 aimuchen100 当前结构 |

## 修复原则

1. **不删除原文**：所有不一致点都用 ✅ 注释或 ⚠️ 警告块补充说明，保留原文作为通用发行版参考
2. **文档标记通用版与项目版**：目录树、表格用"通用发行版" vs "aimuchen100 项目实际"区分
3. **数量以代码为准**：文件计数、控制器数量等均按 `ls` 实测修改
4. **新增一致性追踪**：本文档每次修复都登记

## 相关修复文件

- `docs/lecms开发手册知识库/LeCSM系统完整知识库/第一篇_系统概论.md`
- `docs/lecms开发手册知识库/LECMS 内容管理系统根项目/LECMS 前端主题资源（…）/架构设计.md`
- `docs/lecms开发手册知识库/LECMS 内容管理系统根项目/LECMS 前端主题资源（…）/_module.yaml`
- `docs/README.md`（一致性表格）
- `docs/lecms开发手册知识库/DOCS_ANALYSIS.md`（分析报告第七节）