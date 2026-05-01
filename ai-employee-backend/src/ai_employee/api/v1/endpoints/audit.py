"""Audit log endpoints."""

import uuid

from fastapi import APIRouter, Depends, Query, status
from sqlalchemy.ext.asyncio import AsyncSession

from ai_employee.core.permissions import require_permission
from ai_employee.db.session import get_db
from ai_employee.schemas.base import ApiResponse, PaginatedResponse
from ai_employee.services.audit_log_service import AuditLogService

router = APIRouter(prefix="/audit-logs", tags=["Audit Logs"])


@router.get(
    "",
    response_model=ApiResponse[PaginatedResponse[dict]],
    dependencies=[Depends(require_permission("audit:read"))],
)
async def list_audit_logs(
    tenant_id: uuid.UUID | None = Query(None),
    user_id: uuid.UUID | None = Query(None),
    action: str | None = Query(None),
    resource_type: str | None = Query(None),
    page: int = Query(1, ge=1),
    page_size: int = Query(20, ge=1, le=100),
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[PaginatedResponse[dict]]:
    """List audit logs with filters and pagination."""
    service = AuditLogService(db)
    skip = (page - 1) * page_size
    logs = await service.list_logs(
        tenant_id=tenant_id,
        user_id=user_id,
        action=action,
        resource_type=resource_type,
        skip=skip,
        limit=page_size,
    )
    total = await service.count_logs(
        tenant_id=tenant_id,
        user_id=user_id,
        action=action,
        resource_type=resource_type,
    )

    return ApiResponse(
        code=status.HTTP_200_OK,
        data=PaginatedResponse(
            items=[_log_to_dict(log) for log in logs],
            total=total,
            page=page,
            page_size=page_size,
            has_next=(skip + page_size) < total,
            has_prev=page > 1,
        ),
    )


@router.get(
    "/{log_id}",
    response_model=ApiResponse[dict],
    dependencies=[Depends(require_permission("audit:read"))],
)
async def get_audit_log(
    log_id: uuid.UUID,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[dict]:
    """Get audit log by ID."""
    service = AuditLogService(db)
    log = await service.get_log_by_id(log_id)
    return ApiResponse(
        code=status.HTTP_200_OK,
        data=_log_to_dict(log),
    )


@router.get(
    "/resources/{resource_type}/{resource_id}",
    response_model=ApiResponse[list[dict]],
    dependencies=[Depends(require_permission("audit:read"))],
)
async def get_resource_audit_logs(
    resource_type: str,
    resource_id: str,
    page: int = Query(1, ge=1),
    page_size: int = Query(20, ge=1, le=100),
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[list[dict]]:
    """Get audit logs for a specific resource."""
    service = AuditLogService(db)
    skip = (page - 1) * page_size
    logs = await service.get_logs_by_resource(
        resource_type=resource_type,
        resource_id=resource_id,
        skip=skip,
        limit=page_size,
    )
    return ApiResponse(
        code=status.HTTP_200_OK,
        data=[_log_to_dict(log) for log in logs],
    )


@router.delete(
    "/{log_id}",
    response_model=ApiResponse[None],
    dependencies=[Depends(require_permission("audit:delete"))],
)
async def delete_audit_log(
    log_id: uuid.UUID,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[None]:
    """Soft delete an audit log."""
    service = AuditLogService(db)
    await service.delete_log(log_id)
    return ApiResponse(
        code=status.HTTP_200_OK,
        message="Audit log deleted successfully",
    )


def _log_to_dict(log) -> dict:
    """Convert audit log model to dictionary response."""
    return {
        "id": str(log.id),
        "user_id": log.user_id,
        "tenant_id": log.tenant_id,
        "action": log.action,
        "resource_type": log.resource_type,
        "resource_id": log.resource_id,
        "description": log.description,
        "ip_address": log.ip_address,
        "user_agent": log.user_agent,
        "old_values": log.old_values,
        "new_values": log.new_values,
        "created_at": log.created_at.isoformat() if log.created_at else None,
        "updated_at": log.updated_at.isoformat() if log.updated_at else None,
    }
