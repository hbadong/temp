"""Quota service for managing tenant quotas and usage tracking."""

import uuid
from datetime import UTC, datetime

from sqlalchemy import func, select
from sqlalchemy.ext.asyncio import AsyncSession

from ai_employee.core.exceptions import ConflictException, NotFoundException
from ai_employee.models.quota import Quota, UsageLog
from ai_employee.schemas.quota import QuotaCreate, QuotaUpdate, QuotaUsageResponse, UsageLogCreate


class QuotaService:
    """Service for quota management and usage tracking."""

    def __init__(self, db: AsyncSession) -> None:
        self.db = db

    async def get_quota(
        self, tenant_id: uuid.UUID, resource_type: str
    ) -> Quota | None:
        """Get quota for a tenant and resource type."""
        tenant_id_str = str(tenant_id)
        stmt = select(Quota).where(
            Quota.tenant_id == tenant_id_str,
            Quota.resource_type == resource_type,
            Quota.is_deleted == False,  # noqa: E712
        )
        result = await self.db.execute(stmt)
        return result.scalar_one_or_none()

    async def get_all_quotas(self, tenant_id: uuid.UUID) -> list[Quota]:
        """Get all quotas for a tenant."""
        tenant_id_str = str(tenant_id)
        stmt = select(Quota).where(
            Quota.tenant_id == tenant_id_str,
            Quota.is_deleted == False,  # noqa: E712
        ).order_by(Quota.resource_type)
        result = await self.db.execute(stmt)
        return list(result.scalars().all())

    async def get_quota_usage(
        self, tenant_id: uuid.UUID, resource_type: str
    ) -> QuotaUsageResponse:
        """Get quota usage info for a tenant and resource type."""
        quota = await self.get_quota(tenant_id, resource_type)
        if not quota:
            raise NotFoundException(f"Quota for resource '{resource_type}'")

        remaining = max(0, quota.limit - quota.used)
        return QuotaUsageResponse(
            resource_type=quota.resource_type,
            limit=quota.limit,
            used=quota.used,
            remaining=remaining,
            period=quota.period,
            is_exceeded=quota.used >= quota.limit,
        )

    async def create_quota(self, data: QuotaCreate) -> Quota:
        """Create a new quota entry."""
        existing = await self.get_quota(data.tenant_id, data.resource_type)
        if existing:
            raise ConflictException(
                f"Quota for resource '{data.resource_type}' already exists"
            )

        quota = Quota(
            tenant_id=str(data.tenant_id),
            resource_type=data.resource_type,
            limit=data.limit,
            period=data.period,
        )
        self.db.add(quota)
        await self.db.flush()
        await self.db.refresh(quota)
        return quota

    async def update_quota(
        self, tenant_id: uuid.UUID, resource_type: str, data: QuotaUpdate
    ) -> Quota:
        """Update a quota limit."""
        quota = await self.get_quota(tenant_id, resource_type)
        if not quota:
            raise NotFoundException(f"Quota for resource '{resource_type}'")

        if data.limit is not None:
            quota.limit = data.limit

        await self.db.flush()
        await self.db.refresh(quota)
        return quota

    async def delete_quota(self, tenant_id: uuid.UUID, resource_type: str) -> None:
        """Soft delete a quota entry."""
        quota = await self.get_quota(tenant_id, resource_type)
        if not quota:
            raise NotFoundException(f"Quota for resource '{resource_type}'")

        quota.soft_delete()
        await self.db.flush()

    async def record_usage(self, data: UsageLogCreate, user_id: uuid.UUID | None = None) -> UsageLog:
        """Record a usage event and update quota used count."""
        quota = await self.get_quota(data.tenant_id, data.resource_type)
        if not quota:
            raise NotFoundException(f"Quota for resource '{data.resource_type}'")

        usage_log = UsageLog(
            tenant_id=str(data.tenant_id),
            user_id=str(user_id) if user_id else None,
            resource_type=data.resource_type,
            amount=data.amount,
            description=data.description,
        )
        self.db.add(usage_log)

        quota.used += data.amount
        await self.db.flush()
        await self.db.refresh(usage_log)
        return usage_log

    async def check_quota_available(
        self, tenant_id: uuid.UUID, resource_type: str, amount: int = 1
    ) -> bool:
        """Check if quota is available for the given amount."""
        quota = await self.get_quota(tenant_id, resource_type)
        if not quota:
            return True

        return (quota.used + amount) <= quota.limit

    async def reset_usage(self, tenant_id: uuid.UUID, resource_type: str) -> Quota:
        """Reset the used count for a quota (e.g., at period boundary)."""
        quota = await self.get_quota(tenant_id, resource_type)
        if not quota:
            raise NotFoundException(f"Quota for resource '{resource_type}'")

        quota.used = 0
        await self.db.flush()
        await self.db.refresh(quota)
        return quota

    async def list_usage_logs(
        self,
        tenant_id: uuid.UUID,
        resource_type: str | None = None,
        skip: int = 0,
        limit: int = 20,
    ) -> list[UsageLog]:
        """List usage logs for a tenant."""
        tenant_id_str = str(tenant_id)
        stmt = select(UsageLog).where(
            UsageLog.tenant_id == tenant_id_str,
            UsageLog.is_deleted == False,  # noqa: E712
        )

        if resource_type:
            stmt = stmt.where(UsageLog.resource_type == resource_type)

        stmt = stmt.order_by(UsageLog.created_at.desc()).offset(skip).limit(limit)

        result = await self.db.execute(stmt)
        return list(result.scalars().all())

    async def get_total_usage(
        self, tenant_id: uuid.UUID, resource_type: str
    ) -> int:
        """Get total usage for a tenant and resource type."""
        tenant_id_str = str(tenant_id)
        stmt = select(func.sum(UsageLog.amount)).where(
            UsageLog.tenant_id == tenant_id_str,
            UsageLog.resource_type == resource_type,
            UsageLog.is_deleted == False,  # noqa: E712
        )
        result = await self.db.execute(stmt)
        total = result.scalar()
        return total or 0
