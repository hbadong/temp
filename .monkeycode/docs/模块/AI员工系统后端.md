# AI员工系统后端模块

## 概述

AI员工系统后端是一个基于 FastAPI 的多租户 SaaS 平台，采用 Python 3.11+ 和异步架构。

**项目位置**: `ai-employee-backend/`

## 项目结构

```
ai-employee-backend/
├── src/ai_employee/
│   ├── main.py              # FastAPI 应用入口
│   ├── config.py            # Pydantic Settings 配置
│   ├── dependencies.py      # FastAPI 依赖注入
│   ├── api/                 # API 路由层
│   │   ├── router.py        # 路由聚合
│   │   └── v1/endpoints/    # v1 版本端点
│   ├── core/                # 核心组件
│   │   ├── security.py      # JWT、密码哈希
│   │   └── exceptions.py    # 自定义异常
│   ├── models/              # SQLAlchemy ORM 模型
│   │   ├── base.py          # 基础模型（含 tenant_id、软删除）
│   │   ├── tenant.py        # 租户模型
│   │   ├── user.py          # 用户模型
│   │   └── audit_log.py     # 审计日志模型
│   ├── schemas/             # Pydantic 请求/响应模型
│   │   ├── base.py          # 统一响应格式
│   │   ├── tenant.py        # 租户 Schema
│   │   └── user.py          # 用户 Schema
│   ├── services/            # 业务逻辑层
│   │   ├── tenant_service.py
│   │   └── user_service.py
│   ├── db/                  # 数据库层
│   │   ├── session.py       # 异步 SQLAlchemy 会话
│   │   └── redis.py         # Redis 连接池
│   └── utils/               # 工具函数
├── tests/                   # 测试
├── alembic/                 # 数据库迁移
├── pyproject.toml           # 项目配置
└── .env.example             # 环境变量模板
```

## 技术栈

| 组件 | 技术 |
|------|------|
| Web 框架 | FastAPI 0.115+ |
| 数据库 ORM | SQLAlchemy 2.0 (async) |
| 数据库驱动 | asyncpg (PostgreSQL), aiosqlite (测试) |
| 数据库迁移 | Alembic |
| 缓存/队列 | Redis (redis-py async) |
| 认证 | python-jose (JWT), passlib+bcrypt |
| 数据验证 | Pydantic 2.x |
| 测试 | pytest + pytest-asyncio + httpx |
| 代码质量 | ruff, mypy, pre-commit |

## 核心设计

### 多租户架构

所有业务模型继承自 `BaseModel`，包含 `tenant_id` 字段实现数据隔离：

```python
class BaseModel(Base):
    __abstract__ = True
    id: Mapped[uuid.UUID] = mapped_column(UUID(as_uuid=True), primary_key=True)
    tenant_id: Mapped[uuid.UUID | None] = mapped_column(index=True)
    created_at: Mapped[datetime] = mapped_column(server_default=func.now())
    updated_at: Mapped[datetime] = mapped_column(onupdate=func.now())
    is_deleted: Mapped[bool] = mapped_column(default=False)
```

### 统一响应格式

所有 API 响应使用 `ApiResponse[T]` 包装：

```json
{
  "code": 0,
  "message": "success",
  "data": {...},
  "request_id": "req_abc123",
  "timestamp": 1704067200000
}
```

### 异步架构

全程使用 async/await，支持高并发：

```python
async def get_db() -> AsyncGenerator[AsyncSession, None]:
    async with async_session() as session:
        try:
            yield session
            await session.commit()
        except Exception:
            await session.rollback()
            raise
```

## API 端点

| 端点 | 方法 | 描述 |
|------|------|------|
| `/health` | GET | 健康检查 |
| `/api/v1/auth/login` | POST | 用户登录 |
| `/api/v1/auth/logout` | POST | 用户登出 |
| `/api/v1/tenants` | GET/POST | 租户管理 |
| `/api/v1/users` | GET/POST | 用户管理 |

## 开发指南

### 环境配置

```bash
# 复制环境变量模板
cp .env.example .env

# 安装依赖
pip install -e ".[dev]"

# 运行测试
pytest tests/ -v

# 启动开发服务器
uvicorn ai_employee.main:app --reload
```

### 数据库迁移

```bash
# 创建新迁移
alembic revision --autogenerate -m "add new table"

# 执行迁移
alembic upgrade head

# 回滚
alembic downgrade -1
```

### 代码规范

```bash
# 格式化
ruff format src/ tests/

# 检查
ruff check src/ tests/

# 类型检查
mypy src/
```
