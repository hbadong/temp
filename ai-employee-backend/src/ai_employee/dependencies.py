"""FastAPI dependencies."""

import uuid
from typing import Any

from fastapi import Depends, Request
from jose import JWTError
from sqlalchemy.ext.asyncio import AsyncSession

from ai_employee.core.exceptions import UnauthorizedException
from ai_employee.core.security import decode_access_token
from ai_employee.db.session import get_db
from ai_employee.schemas.user import UserResponse


async def get_current_user(
    request: Request,
    db: AsyncSession = Depends(get_db),
) -> dict[str, Any]:
    """Get current authenticated user from JWT token."""
    authorization = request.headers.get("Authorization")
    if not authorization:
        raise UnauthorizedException("Authorization header missing")

    scheme, _, token = authorization.partition(" ")
    if scheme.lower() != "bearer":
        raise UnauthorizedException("Invalid authorization scheme")

    try:
        payload = decode_access_token(token)
    except ValueError as e:
        raise UnauthorizedException(str(e)) from e

    return payload


async def get_current_user_id(
    current_user: dict[str, Any] = Depends(get_current_user),
) -> uuid.UUID:
    """Extract user ID from current user payload."""
    return uuid.UUID(current_user["sub"])


async def require_superuser(
    current_user: dict[str, Any] = Depends(get_current_user),
) -> dict[str, Any]:
    """Require superuser privileges."""
    if not current_user.get("is_superuser"):
        raise UnauthorizedException("Superuser privileges required")
    return current_user


def get_tenant_id_from_token(
    current_user: dict[str, Any] = Depends(get_current_user),
) -> uuid.UUID:
    """Extract tenant ID from current user token."""
    tenant_id = current_user.get("tenant_id")
    if not tenant_id:
        raise UnauthorizedException("Tenant ID not found in token")
    return uuid.UUID(tenant_id)
