# 项目文档

本工作区包含两个项目：

1. **YzmCMS** - 基于 YZMPHP 的轻量级 CMS 系统（PHP+MySQL）
2. **AI员工系统** - 多租户 AI 自动化营销平台（Python FastAPI）

---

## AI员工系统

**快速链接**: [后端架构](#ai员工系统架构) | [后端模块](#ai员工系统模块)

### AI员工系统架构

AI员工系统是一个基于 FastAPI 的多租户 SaaS 平台，包含 11 个 AI 智能体。

- **技术栈**: Python 3.11+, FastAPI, SQLAlchemy (async), Alembic, Redis
- **项目位置**: `ai-employee-backend/`
- **启动命令**: `uvicorn ai_employee.main:app --reload`
- **API 文档**: `/docs` (Swagger), `/redoc` (ReDoc)

### AI员工系统模块

| 模块 | 路径 | 描述 |
|------|------|------|
| 用户中心 | `src/ai_employee/api/v1/endpoints/` | 租户、用户、认证管理 |
| 核心层 | `src/ai_employee/core/` | JWT 安全、异常处理 |
| 数据层 | `src/ai_employee/db/` | 数据库连接、Redis 连接池 |
| 模型层 | `src/ai_employee/models/` | SQLAlchemy ORM 模型 |
| 业务层 | `src/ai_employee/services/` | 业务逻辑服务 |
| 配置层 | `src/ai_employee/config.py` | Pydantic Settings 配置 |

### AI员工系统核心概念

| 概念 | 描述 |
|------|------|
| 多租户隔离 | 所有业务表包含 tenant_id，实现数据隔离 |
| 软删除 | BaseModel 包含 is_deleted 字段，逻辑删除 |
| JWT 认证 | 基于 python-jose 的 Token 认证 |
| 异步架构 | 全程使用 async/await，支持高并发 |

---

## YzmCMS

**快速链接**: [架构](./ARCHITECTURE.md) | [接口](./INTERFACES.md) | [开发者指南](./DEVELOPER_GUIDE.md)

### 核心文档

### [架构](./ARCHITECTURE.md)
YzmCMS 系统设计、技术栈、组件结构和数据流程。

### [接口](./INTERFACES.md)
YzmCMS 公开 API、模板标签、控制器方法。

### [开发者指南](./DEVELOPER_GUIDE.md)
YzmCMS 环境搭建、开发规范、常见任务。

### YzmCMS 核心概念

| 概念 | 描述 |
|------|------|
| [栏目](./专有概念/Category.md) | 网站内容分类结构，支持树形层级和多种栏目类型 |
| [内容模型](./专有概念/Model.md) | 定义不同内容类型的数据结构，支持自定义字段 |

### YzmCMS 模块

| 模块 | 描述 |
|------|------|
| `application/admin/` | 后台管理模块 |
| `application/index/` | 前台展示模块 |
| `application/member/` | 会员中心模块 |
| `application/wechat/` | 微信集成模块 |
| `yzmphp/core/` | YZMPHP 框架核心类库 |

---

## 快速参考

### YzmCMS 命令

```bash
# 访问后台管理
http://domain/admin.php

# 访问前台首页
http://domain/
```

### AI员工系统 命令

```bash
# 进入项目目录
cd ai-employee-backend/

# 安装依赖
pip install -e ".[dev]"

# 运行测试
pytest tests/ -v

# 启动开发服务器
uvicorn ai_employee.main:app --reload

# 数据库迁移
alembic revision --autogenerate -m "message"
alembic upgrade head
```