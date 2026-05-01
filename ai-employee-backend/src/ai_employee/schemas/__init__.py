"""Schemas module."""

from ai_employee.schemas.base import ApiResponse, PaginatedResponse, PaginationParams
from ai_employee.schemas.tenant import TenantCreate, TenantResponse, TenantUpdate
from ai_employee.schemas.user import Token, UserCreate, UserLogin, UserResponse, UserUpdate
from ai_employee.schemas.quota import (
    QuotaCreate,
    QuotaResponse,
    QuotaUpdate,
    QuotaUsageResponse,
    UsageLogCreate,
    UsageLogResponse,
)
from ai_employee.schemas.rbac import (
    PermissionCreate,
    PermissionResponse,
    RoleCreate,
    RoleResponse,
    RoleUpdate,
    UserRoleAssign,
    UserRoleResponse,
    UserPermissionResponse,
)

__all__ = [
    "ApiResponse",
    "PaginatedResponse",
    "PaginationParams",
    "TenantCreate",
    "TenantResponse",
    "TenantUpdate",
    "UserCreate",
    "UserLogin",
    "UserResponse",
    "UserUpdate",
    "Token",
    "QuotaCreate",
    "QuotaResponse",
    "QuotaUpdate",
    "QuotaUsageResponse",
    "UsageLogCreate",
    "UsageLogResponse",
    "PermissionCreate",
    "PermissionResponse",
    "RoleCreate",
    "RoleResponse",
    "RoleUpdate",
    "UserRoleAssign",
    "UserRoleResponse",
    "UserPermissionResponse",
]
