"""Tenant service for business logic."""

import uuid

from sqlalchemy import func, select
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
        stmt = select(Tenant).where(
            Tenant.id == str(tenant_id),
            Tenant.is_deleted == False,  # noqa: E712
        )
        result = await self.db.execute(stmt)
        tenant = result.scalar_one_or_none()

        if not tenant:
            raise NotFoundException("Tenant")

        return tenant

    async def get_by_code(self, code: str) -> Tenant | None:
        """Get tenant by unique code."""
        stmt = select(Tenant).where(
            Tenant.code == code,
            Tenant.is_deleted == False,  # noqa: E712
        )
        result = await self.db.execute(stmt)
        return result.scalar_one_or_none()

    async def get_by_domain(self, domain: str) -> Tenant | None:
        """Get tenant by domain."""
        stmt = select(Tenant).where(
            Tenant.domain == domain,
            Tenant.is_deleted == False,  # noqa: E712
        )
        result = await self.db.execute(stmt)
        return result.scalar_one_or_none()

    async def list_tenants(
        self,
        skip: int = 0,
        limit: int = 20,
        is_active: bool | None = None,
    ) -> tuple[list[Tenant], int]:
        """List tenants with pagination and optional active filter."""
        where_conditions = [Tenant.is_deleted == False]  # noqa: E712

        if is_active is not None:
            where_conditions.append(Tenant.is_active == is_active)

        count_stmt = select(func.count(Tenant.id)).where(*where_conditions)
        count_result = await self.db.execute(count_stmt)
        total = count_result.scalar() or 0

        stmt = (
            select(Tenant)
            .where(*where_conditions)
            .order_by(Tenant.created_at.desc())
            .offset(skip)
            .limit(limit)
        )
        result = await self.db.execute(stmt)
        return list(result.scalars().all()), total

    async def create(self, data: TenantCreate) -> Tenant:
        """Create a new tenant."""
        existing = await self.get_by_code(data.code)
        if existing:
            raise ConflictException(f"Tenant with code '{data.code}' already exists")

        if data.domain:
            existing_domain = await self.get_by_domain(data.domain)
            if existing_domain:
                raise ConflictException(f"Tenant with domain '{data.domain}' already exists")

        tenant = Tenant(**data.model_dump())
        self.db.add(tenant)
        await self.db.flush()
        await self.db.refresh(tenant)
        return tenant

    async def update(self, tenant_id: uuid.UUID, data: TenantUpdate) -> Tenant:
        """Update an existing tenant."""
        tenant = await self.get_by_id(tenant_id)

        update_data = data.model_dump(exclude_unset=True)

        if "domain" in update_data and update_data["domain"]:
            existing = await self.get_by_domain(update_data["domain"])
            if existing and existing.id != tenant_id:
                raise ConflictException(f"Tenant with domain '{update_data['domain']}' already exists")

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

    async def activate(self, tenant_id: uuid.UUID) -> Tenant:
        """Activate a tenant."""
        tenant = await self.get_by_id(tenant_id)
        tenant.is_active = True
        await self.db.flush()
        await self.db.refresh(tenant)
        return tenant

    async def deactivate(self, tenant_id: uuid.UUID) -> Tenant:
        """Deactivate a tenant."""
        tenant = await self.get_by_id(tenant_id)
        tenant.is_active = False
        await self.db.flush()
        await self.db.refresh(tenant)
        return tenant
