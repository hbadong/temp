"""RBAC schemas for roles and permissions."""

import uuid
from datetime import datetime

from pydantic import BaseModel, Field


class PermissionBase(BaseModel):
    """Base permission schema."""

    code: str = Field(..., min_length=1, max_length=100)
    description: str | None = Field(None, max_length=500)


class PermissionCreate(PermissionBase):
    """Schema for creating a permission."""

    pass


class PermissionResponse(PermissionBase):
    """Schema for permission response."""

    id: uuid.UUID
    created_at: datetime
    updated_at: datetime

    model_config = {"from_attributes": True}


class RoleBase(BaseModel):
    """Base role schema."""

    name: str = Field(..., min_length=1, max_length=100)
    description: str | None = Field(None, max_length=500)
    tenant_id: uuid.UUID | None = None


class RoleCreate(RoleBase):
    """Schema for creating a role."""

    permission_codes: list[str] = Field(default_factory=list)


class RoleUpdate(BaseModel):
    """Schema for updating a role."""

    name: str | None = Field(None, min_length=1, max_length=100)
    description: str | None = Field(None, max_length=500)


class RoleResponse(BaseModel):
    """Schema for role response."""

    id: uuid.UUID
    name: str
    description: str | None
    tenant_id: uuid.UUID | None
    created_at: datetime
    updated_at: datetime
    permissions: list[PermissionResponse] = Field(default_factory=list)

    model_config = {"from_attributes": True}


class UserRoleAssign(BaseModel):
    """Schema for assigning a role to a user."""

    user_id: uuid.UUID
    role_id: uuid.UUID


class UserRoleResponse(BaseModel):
    """Schema for user roles response."""

    id: uuid.UUID
    user_id: uuid.UUID
    role_id: uuid.UUID
    created_at: datetime

    model_config = {"from_attributes": True}


class UserPermissionResponse(BaseModel):
    """Schema for user's effective permissions."""

    user_id: uuid.UUID
    permissions: list[PermissionResponse]
    roles: list[RoleResponse]
