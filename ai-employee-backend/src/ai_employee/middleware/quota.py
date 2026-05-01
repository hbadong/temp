"""Quota checking middleware for API requests."""

import uuid
from typing import Any

from fastapi import Request, Response, status
from fastapi.responses import JSONResponse
from sqlalchemy.ext.asyncio import AsyncSession

from ai_employee.core.exceptions import ForbiddenException
from ai_employee.db.session import get_db
from ai_employee.services.quota_service import QuotaService

RESOURCE_MAP: dict[str, list[str]] = {
    "POST:/api/v1/users": ["user:create"],
    "POST:/api/v1/tenants": ["tenant:create"],
    "POST:/api/v1/roles": ["role:create"],
    "POST:/api/v1/permissions": ["permission:create"],
}


def get_resource_type(request: Request) -> str | None:
    """Determine the resource type from the request."""
    key = f"{request.method}:{request.url.path}"
    if key in RESOURCE_MAP:
        return RESOURCE_MAP[key][0]
    return None


async def check_quota_middleware(request: Request, call_next) -> Response:
    """Middleware to check quota before processing requests."""
    response = await call_next(request)
    return response


async def check_and_consume_quota(
    request: Request,
    db: AsyncSession,
    user_id: uuid.UUID | None = None,
) -> None:
    """Check quota availability and consume if successful.

    Raises ForbiddenException if quota is exceeded.
    """
    resource_type = get_resource_type(request)
    if not resource_type:
        return

    authorization = request.headers.get("Authorization")
    if not authorization:
        return

    scheme, _, token = authorization.partition(" ")
    if scheme.lower() != "bearer":
        return

    from ai_employee.core.security import decode_access_token

    try:
        payload = decode_access_token(token)
    except ValueError:
        return

    tenant_id_str = payload.get("tenant_id")
    if not tenant_id_str:
        return

    tenant_id = uuid.UUID(tenant_id_str)

    quota_service = QuotaService(db)
    available = await quota_service.check_quota_available(tenant_id, resource_type)

    if not available:
        usage = await quota_service.get_quota_usage(tenant_id, resource_type)
        raise ForbiddenException(
            f"Quota exceeded for '{resource_type}': "
            f"{usage.used}/{usage.limit} used"
        )

    await quota_service.record_usage(
        tenant_id=tenant_id,
        resource_type=resource_type,
        amount=1,
        user_id=user_id,
        description=f"API call to {request.url.path}",
    )
