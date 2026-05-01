"""Tenant schemas."""

import uuid
from datetime import datetime

from pydantic import BaseModel, Field


class TenantBase(BaseModel):
    """Base tenant schema with common fields."""

    name: str = Field(..., min_length=1, max_length=255)
    code: str = Field(..., min_length=1, max_length=100)
    description: str | None = None
    domain: str | None = None


class TenantCreate(TenantBase):
    """Schema for creating a new tenant."""

    pass


class TenantUpdate(BaseModel):
    """Schema for updating an existing tenant."""

    name: str | None = Field(None, min_length=1, max_length=255)
    description: str | None = None
    domain: str | None = None
    is_active: bool | None = None


class TenantResponse(TenantBase):
    """Schema for tenant response."""

    id: uuid.UUID
    is_active: bool
    created_at: datetime
    updated_at: datetime

    model_config = {"from_attributes": True}
