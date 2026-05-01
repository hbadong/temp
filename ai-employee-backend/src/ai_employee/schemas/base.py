"""Common response schema."""

from typing import Any, Generic, TypeVar

from pydantic import BaseModel, Field

T = TypeVar("T")


class ApiResponse(BaseModel, Generic[T]):
    """Standard API response wrapper."""

    code: int = Field(default=200, description="Response status code")
    message: str = Field(default="Success", description="Response message")
    data: T | None = Field(default=None, description="Response data")
    request_id: str | None = Field(default=None, description="Unique request identifier")


class PaginationParams(BaseModel):
    """Common pagination parameters."""

    page: int = Field(default=1, ge=1, description="Page number")
    page_size: int = Field(default=20, ge=1, le=100, description="Items per page")


class PaginatedResponse(BaseModel, Generic[T]):
    """Paginated response wrapper."""

    items: list[T]
    total: int
    page: int
    page_size: int
    has_next: bool
    has_prev: bool
