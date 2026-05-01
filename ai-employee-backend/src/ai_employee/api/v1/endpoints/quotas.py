"""Quota management endpoints."""

import uuid

from fastapi import APIRouter, Depends, Query, status
from sqlalchemy.ext.asyncio import AsyncSession

from ai_employee.core.permissions import require_permission
from ai_employee.db.session import get_db
from ai_employee.schemas.base import ApiResponse, PaginatedResponse
from ai_employee.schemas.quota import (
    QuotaCreate,
    QuotaResponse,
    QuotaUpdate,
    QuotaUsageResponse,
    UsageLogCreate,
    UsageLogResponse,
)
from ai_employee.services.quota_service import QuotaService

router = APIRouter(prefix="/quotas", tags=["Quotas"])


@router.get(
    "/{tenant_id}",
    response_model=ApiResponse[list[QuotaResponse]],
    dependencies=[Depends(require_permission("quota:read"))],
)
async def get_tenant_quotas(
    tenant_id: uuid.UUID,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[list[QuotaResponse]]:
    """Get all quotas for a tenant."""
    service = QuotaService(db)
    quotas = await service.get_all_quotas(tenant_id)
    return ApiResponse(
        code=status.HTTP_200_OK,
        data=[QuotaResponse.model_validate(q) for q in quotas],
    )


@router.get(
    "/{tenant_id}/{resource_type}/usage",
    response_model=ApiResponse[QuotaUsageResponse],
    dependencies=[Depends(require_permission("quota:read"))],
)
async def get_quota_usage(
    tenant_id: uuid.UUID,
    resource_type: str,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[QuotaUsageResponse]:
    """Get quota usage for a specific resource type."""
    service = QuotaService(db)
    usage = await service.get_quota_usage(tenant_id, resource_type)
    return ApiResponse(
        code=status.HTTP_200_OK,
        data=usage,
    )


@router.post(
    "",
    response_model=ApiResponse[QuotaResponse],
    status_code=status.HTTP_201_CREATED,
    dependencies=[Depends(require_permission("quota:create"))],
)
async def create_quota(
    data: QuotaCreate,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[QuotaResponse]:
    """Create a new quota entry."""
    service = QuotaService(db)
    quota = await service.create_quota(data)
    return ApiResponse(
        code=status.HTTP_201_CREATED,
        message="Quota created successfully",
        data=QuotaResponse.model_validate(quota),
    )


@router.patch(
    "/{tenant_id}/{resource_type}",
    response_model=ApiResponse[QuotaResponse],
    dependencies=[Depends(require_permission("quota:update"))],
)
async def update_quota(
    tenant_id: uuid.UUID,
    resource_type: str,
    data: QuotaUpdate,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[QuotaResponse]:
    """Update a quota limit."""
    service = QuotaService(db)
    quota = await service.update_quota(tenant_id, resource_type, data)
    return ApiResponse(
        code=status.HTTP_200_OK,
        message="Quota updated successfully",
        data=QuotaResponse.model_validate(quota),
    )


@router.delete(
    "/{tenant_id}/{resource_type}",
    response_model=ApiResponse[None],
    dependencies=[Depends(require_permission("quota:delete"))],
)
async def delete_quota(
    tenant_id: uuid.UUID,
    resource_type: str,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[None]:
    """Delete a quota entry."""
    service = QuotaService(db)
    await service.delete_quota(tenant_id, resource_type)
    return ApiResponse(
        code=status.HTTP_200_OK,
        message="Quota deleted successfully",
    )


@router.post(
    "/usage",
    response_model=ApiResponse[UsageLogResponse],
    status_code=status.HTTP_201_CREATED,
    dependencies=[Depends(require_permission("quota:create"))],
)
async def record_usage(
    data: UsageLogCreate,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[UsageLogResponse]:
    """Record a usage event."""
    service = QuotaService(db)
    usage_log = await service.record_usage(data)
    return ApiResponse(
        code=status.HTTP_201_CREATED,
        message="Usage recorded",
        data=UsageLogResponse.model_validate(usage_log),
    )


@router.get(
    "/{tenant_id}/usage-logs",
    response_model=ApiResponse[PaginatedResponse[UsageLogResponse]],
    dependencies=[Depends(require_permission("quota:read"))],
)
async def list_usage_logs(
    tenant_id: uuid.UUID,
    resource_type: str | None = Query(None),
    page: int = Query(1, ge=1),
    page_size: int = Query(20, ge=1, le=100),
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[PaginatedResponse[UsageLogResponse]]:
    """List usage logs for a tenant."""
    service = QuotaService(db)
    skip = (page - 1) * page_size
    logs = await service.list_usage_logs(
        tenant_id=tenant_id,
        resource_type=resource_type,
        skip=skip,
        limit=page_size,
    )

    return ApiResponse(
        code=status.HTTP_200_OK,
        data=PaginatedResponse(
            items=[UsageLogResponse.model_validate(log) for log in logs],
            total=len(logs),
            page=page,
            page_size=page_size,
            has_next=len(logs) == page_size,
            has_prev=page > 1,
        ),
    )


@router.post(
    "/{tenant_id}/{resource_type}/reset",
    response_model=ApiResponse[QuotaResponse],
    dependencies=[Depends(require_permission("quota:update"))],
)
async def reset_quota_usage(
    tenant_id: uuid.UUID,
    resource_type: str,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[QuotaResponse]:
    """Reset quota usage (e.g., at period boundary)."""
    service = QuotaService(db)
    quota = await service.reset_usage(tenant_id, resource_type)
    return ApiResponse(
        code=status.HTTP_200_OK,
        message="Quota usage reset",
        data=QuotaResponse.model_validate(quota),
    )
