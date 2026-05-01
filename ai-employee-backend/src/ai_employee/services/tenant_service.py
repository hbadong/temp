"""Tenant service for business logic."""

import uuid

from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession

from ai_employee.core.exceptions import ConflictException, NotFoundException
from ai_employee.models.tenant import Tenant
from ai_employee.schemas.tenant import TenantCreate, TenantUpdate


class TenantService:
    """Service for tenant CRUD operations."""

    def __init__(self, db: AsyncSession) -> None:
        self.db = db

    async def get_by_id(self, tenant_id: uuid.UUID) -> Tenant:
        """Get tenant by ID."""
        stmt = select(Tenant).where(Tenant.id == tenant_id, Tenant.is_deleted == False)  # noqa: E712
        result = await self.db.execute(stmt)
        tenant = result.scalar_one_or_none()

        if not tenant:
            raise NotFoundException("Tenant")

        return tenant

    async def get_by_code(self, code: str) -> Tenant | None:
        """Get tenant by unique code."""
        stmt = select(Tenant).where(Tenant.code == code, Tenant.is_deleted == False)  # noqa: E712
        result = await self.db.execute(stmt)
        return result.scalar_one_or_none()

    async def list_tenants(self, skip: int = 0, limit: int = 20) -> list[Tenant]:
        """List all active tenants with pagination."""
        stmt = (
            select(Tenant)
            .where(Tenant.is_deleted == False)  # noqa: E712
            .order_by(Tenant.created_at.desc())
            .offset(skip)
            .limit(limit)
        )
        result = await self.db.execute(stmt)
        return list(result.scalars().all())

    async def create(self, data: TenantCreate) -> Tenant:
        """Create a new tenant."""
        # Check if code already exists
        existing = await self.get_by_code(data.code)
        if existing:
            raise ConflictException(f"Tenant with code '{data.code}' already exists")

        tenant = Tenant(**data.model_dump())
        self.db.add(tenant)
        await self.db.flush()
        await self.db.refresh(tenant)
        return tenant

    async def update(self, tenant_id: uuid.UUID, data: TenantUpdate) -> Tenant:
        """Update an existing tenant."""
        tenant = await self.get_by_id(tenant_id)

        update_data = data.model_dump(exclude_unset=True)
        for key, value in update_data.items():
            setattr(tenant, key, value)

        await self.db.flush()
        await self.db.refresh(tenant)
        return tenant

    async def delete(self, tenant_id: uuid.UUID) -> None:
        """Soft delete a tenant."""
        tenant = await self.get_by_id(tenant_id)
        tenant.soft_delete()
        await self.db.flush()
