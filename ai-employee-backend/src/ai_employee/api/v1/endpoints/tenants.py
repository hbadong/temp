"""Tenant endpoints."""

import uuid

from fastapi import APIRouter, Depends, Query, status
from sqlalchemy.ext.asyncio import AsyncSession

from ai_employee.db.session import get_db
from ai_employee.schemas.base import ApiResponse, PaginatedResponse
from ai_employee.schemas.tenant import TenantCreate, TenantResponse, TenantUpdate
from ai_employee.services.tenant_service import TenantService

router = APIRouter(prefix="/tenants", tags=["Tenants"])


@router.get("", response_model=ApiResponse[PaginatedResponse[TenantResponse]])
async def list_tenants(
    page: int = Query(1, ge=1),
    page_size: int = Query(20, ge=1, le=100),
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[PaginatedResponse[TenantResponse]]:
    """List all tenants with pagination."""
    service = TenantService(db)
    skip = (page - 1) * page_size
    tenants = await service.list_tenants(skip=skip, limit=page_size)

    return ApiResponse(
        code=status.HTTP_200_OK,
        data=PaginatedResponse(
            items=[TenantResponse.model_validate(t) for t in tenants],
            total=len(tenants),
            page=page,
            page_size=page_size,
            has_next=len(tenants) == page_size,
            has_prev=page > 1,
        ),
    )


@router.get("/{tenant_id}", response_model=ApiResponse[TenantResponse])
async def get_tenant(
    tenant_id: uuid.UUID,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[TenantResponse]:
    """Get tenant by ID."""
    service = TenantService(db)
    tenant = await service.get_by_id(tenant_id)
    return ApiResponse(
        code=status.HTTP_200_OK,
        data=TenantResponse.model_validate(tenant),
    )


@router.post("", response_model=ApiResponse[TenantResponse], status_code=status.HTTP_201_CREATED)
async def create_tenant(
    data: TenantCreate,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[TenantResponse]:
    """Create a new tenant."""
    service = TenantService(db)
    tenant = await service.create(data)
    return ApiResponse(
        code=status.HTTP_201_CREATED,
        message="Tenant created successfully",
        data=TenantResponse.model_validate(tenant),
    )


@router.patch("/{tenant_id}", response_model=ApiResponse[TenantResponse])
async def update_tenant(
    tenant_id: uuid.UUID,
    data: TenantUpdate,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[TenantResponse]:
    """Update an existing tenant."""
    service = TenantService(db)
    tenant = await service.update(tenant_id, data)
    return ApiResponse(
        code=status.HTTP_200_OK,
        message="Tenant updated successfully",
        data=TenantResponse.model_validate(tenant),
    )


@router.delete("/{tenant_id}", response_model=ApiResponse[None])
async def delete_tenant(
    tenant_id: uuid.UUID,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[None]:
    """Soft delete a tenant."""
    service = TenantService(db)
    await service.delete(tenant_id)
    return ApiResponse(
        code=status.HTTP_200_OK,
        message="Tenant deleted successfully",
    )
