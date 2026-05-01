"""Quota schemas."""

import uuid
from datetime import datetime

from pydantic import BaseModel, Field


class QuotaCreate(BaseModel):
    """Schema for creating a quota entry."""

    tenant_id: uuid.UUID
    resource_type: str = Field(..., min_length=1, max_length=100)
    limit: int = Field(..., ge=0)
    period: str = Field(default="monthly", max_length=50)


class QuotaUpdate(BaseModel):
    """Schema for updating a quota entry."""

    limit: int | None = Field(None, ge=0)


class QuotaResponse(BaseModel):
    """Schema for quota response."""

    id: uuid.UUID
    tenant_id: uuid.UUID
    resource_type: str
    limit: int
    used: int
    period: str
    created_at: datetime
    updated_at: datetime

    model_config = {"from_attributes": True}


class QuotaUsageResponse(BaseModel):
    """Schema for quota usage with remaining info."""

    resource_type: str
    limit: int
    used: int
    remaining: int
    period: str
    is_exceeded: bool


class UsageLogCreate(BaseModel):
    """Schema for recording usage."""

    tenant_id: uuid.UUID
    resource_type: str = Field(..., min_length=1, max_length=100)
    amount: int = Field(default=1, ge=1)
    description: str | None = Field(None, max_length=500)


class UsageLogResponse(BaseModel):
    """Schema for usage log response."""

    id: uuid.UUID
    tenant_id: uuid.UUID
    user_id: uuid.UUID | None
    resource_type: str
    amount: int
    description: str | None
    created_at: datetime

    model_config = {"from_attributes": True}
