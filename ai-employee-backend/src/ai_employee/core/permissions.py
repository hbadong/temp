"""RBAC 权限系统。

实现基于角色的访问控制，提供权限检查装饰器和依赖注入。
"""

from enum import Enum
from functools import wraps

from fastapi import Depends, HTTPException, status

from ai_employee.dependencies import get_current_user
from ai_employee.models.user import User


class Permission(str, Enum):
    """系统权限枚举。"""

    # 租户管理
    TENANT_READ = "tenant:read"
    TENANT_WRITE = "tenant:write"
    TENANT_DELETE = "tenant:delete"

    # 用户管理
    USER_READ = "user:read"
    USER_WRITE = "user:write"
    USER_DELETE = "user:delete"

    # 内容管理
    CONTENT_READ = "content:read"
    CONTENT_WRITE = "content:write"
    CONTENT_DELETE = "content:delete"
    CONTENT_PUBLISH = "content:publish"

    # 智能体管理
    AGENT_READ = "agent:read"
    AGENT_WRITE = "agent:write"
    AGENT_DELETE = "agent:delete"

    # 任务管理
    TASK_READ = "task:read"
    TASK_WRITE = "task:write"
    TASK_DELETE = "task:delete"

    # 线索管理
    LEAD_READ = "lead:read"
    LEAD_WRITE = "lead:write"
    LEAD_DELETE = "lead:delete"

    # 工作流管理
    WORKFLOW_READ = "workflow:read"
    WORKFLOW_WRITE = "workflow:write"
    WORKFLOW_DELETE = "workflow:delete"
    WORKFLOW_EXECUTE = "workflow:execute"

    # 数据分析
    ANALYTICS_READ = "analytics:read"

    # 系统设置
    SETTINGS_READ = "settings:read"
    SETTINGS_WRITE = "settings:write"


# 角色权限映射
ROLE_PERMISSIONS: dict[str, set[str]] = {
    "admin": {p.value for p in Permission},  # 管理员拥有所有权限
    "manager": {
        Permission.CONTENT_READ,
        Permission.CONTENT_WRITE,
        Permission.CONTENT_DELETE,
        Permission.CONTENT_PUBLISH,
        Permission.AGENT_READ,
        Permission.AGENT_WRITE,
        Permission.AGENT_DELETE,
        Permission.TASK_READ,
        Permission.TASK_WRITE,
        Permission.TASK_DELETE,
        Permission.LEAD_READ,
        Permission.LEAD_WRITE,
        Permission.WORKFLOW_READ,
        Permission.WORKFLOW_WRITE,
        Permission.WORKFLOW_EXECUTE,
        Permission.ANALYTICS_READ,
        Permission.SETTINGS_READ,
        Permission.SETTINGS_WRITE,
    },
    "operator": {
        Permission.CONTENT_READ,
        Permission.CONTENT_WRITE,
        Permission.TASK_READ,
        Permission.TASK_WRITE,
        Permission.LEAD_READ,
        Permission.LEAD_WRITE,
        Permission.ANALYTICS_READ,
        Permission.WORKFLOW_READ,
    },
    "viewer": {
        Permission.CONTENT_READ,
        Permission.TASK_READ,
        Permission.LEAD_READ,
        Permission.ANALYTICS_READ,
        Permission.WORKFLOW_READ,
        Permission.SETTINGS_READ,
    },
}


def has_permission(user: User, permission: Permission) -> bool:
    """检查用户是否拥有指定权限。

    Args:
        user: 用户对象
        permission: 权限枚举

    Returns:
        bool: 是否拥有权限
    """
    # 超级管理员拥有所有权限
    if user.is_superuser:
        return True

    # 根据角色检查权限
    role_permissions = ROLE_PERMISSIONS.get(user.role, set())
    return permission.value in role_permissions


class PermissionChecker:
    """权限检查器，用作 FastAPI 依赖。"""

    def __init__(self, required_permissions: Permission | list[Permission]) -> None:
        """初始化权限检查器。

        Args:
            required_permissions: 需要的权限（单个或列表）
        """
        if isinstance(required_permissions, Permission):
            self.permissions = {required_permissions.value}
        else:
            self.permissions = {p.value for p in required_permissions}

    def __call__(self, current_user: User = Depends(get_current_user)) -> bool:
        """检查用户权限。

        Args:
            current_user: 当前用户

        Returns:
            bool: 是否有权限

        Raises:
            HTTPException: 权限不足时抛出
        """
        # 超级管理员直接通过
        if current_user.is_superuser:
            return True

        # 检查是否拥有任一权限
        user_permissions = ROLE_PERMISSIONS.get(current_user.role, set())
        if not self.permissions.intersection(user_permissions):
            raise HTTPException(
                status_code=status.HTTP_403_FORBIDDEN,
                detail="权限不足",
            )
        return True


def require_permission(permission: str):
    """权限检查依赖函数（单权限版本）。

    用于 FastAPI 依赖注入，检查用户是否拥有指定权限。

    Args:
        permission: 权限字符串（如 "user:read"）

    Returns:
        依赖函数
    """
    async def _check(current_user: dict = Depends(get_current_user)) -> bool:
        # 从 token payload 中获取角色
        role = current_user.get("role", "")
        is_superuser = current_user.get("is_superuser", False)

        if is_superuser:
            return True

        user_permissions = ROLE_PERMISSIONS.get(role, set())
        if permission not in user_permissions:
            raise HTTPException(
                status_code=status.HTTP_403_FORBIDDEN,
                detail="权限不足",
            )
        return True
    return _check


def require_permissions(*permissions: Permission):
    """权限检查装饰器。

    用于 FastAPI 端点，检查用户是否拥有指定权限。

    Args:
        *permissions: 需要的权限

    Returns:
        装饰器函数
    """
    def decorator(func):
        @wraps(func)
        async def wrapper(*args, **kwargs):
            # 从 kwargs 中获取 current_user
            current_user = kwargs.get("current_user")
            if current_user is None:
                raise HTTPException(
                    status_code=status.HTTP_401_UNAUTHORIZED,
                    detail="未认证",
                )

            # 超级管理员直接通过
            if current_user.is_superuser:
                return await func(*args, **kwargs)

            # 检查权限
            user_permissions = ROLE_PERMISSIONS.get(current_user.role, set())
            required = {p.value if hasattr(p, "value") else p for p in permissions}
            if not required.intersection(user_permissions):
                raise HTTPException(
                    status_code=status.HTTP_403_FORBIDDEN,
                    detail="权限不足",
                )
            return await func(*args, **kwargs)
        return wrapper
    return decorator
