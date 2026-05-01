"""Audit log service for async audit trail management."""

import uuid
from datetime import datetime
from typing import Any

from sqlalchemy import func, select
from sqlalchemy.ext.asyncio import AsyncSession

from ai_employee.models.audit_log import AuditLog


class AuditLogService:
    """Service for writing and querying audit logs."""

    def __init__(self, db: AsyncSession) -> None:
        self.db = db

    async def create_log(
        self,
        action: str,
        resource_type: str,
        user_id: uuid.UUID | None = None,
        resource_id: str | None = None,
        description: str | None = None,
        ip_address: str | None = None,
        user_agent: str | None = None,
        old_values: dict[str, Any] | None = None,
        new_values: dict[str, Any] | None = None,
        tenant_id: uuid.UUID | None = None,
    ) -> AuditLog:
        """Create a new audit log entry."""
        log = AuditLog(
            user_id=str(user_id) if user_id else None,
            tenant_id=str(tenant_id) if tenant_id else None,
            action=action,
            resource_type=resource_type,
            resource_id=resource_id,
            description=description,
            ip_address=ip_address,
            user_agent=user_agent,
            old_values=old_values,
            new_values=new_values,
        )
        self.db.add(log)
        await self.db.flush()
        await self.db.refresh(log)
        return log

    async def get_log_by_id(self, log_id: uuid.UUID) -> AuditLog:
        """Get audit log by ID."""
        stmt = select(AuditLog).where(
            AuditLog.id == log_id,
            AuditLog.is_deleted == False,  # noqa: E712
        )
        result = await self.db.execute(stmt)
        log = result.scalar_one_or_none()
        if not log:
            from ai_employee.core.exceptions import NotFoundException

            raise NotFoundException("Audit log")
        return log

    async def list_logs(
        self,
        tenant_id: uuid.UUID | None = None,
        user_id: uuid.UUID | None = None,
        action: str | None = None,
        resource_type: str | None = None,
        skip: int = 0,
        limit: int = 20,
    ) -> list[AuditLog]:
        """List audit logs with optional filters."""
        stmt = select(AuditLog).where(AuditLog.is_deleted == False)  # noqa: E712

        if tenant_id:
            stmt = stmt.where(AuditLog.tenant_id == str(tenant_id))
        if user_id:
            stmt = stmt.where(AuditLog.user_id == str(user_id))
        if action:
            stmt = stmt.where(AuditLog.action == action)
        if resource_type:
            stmt = stmt.where(AuditLog.resource_type == resource_type)

        stmt = stmt.order_by(AuditLog.created_at.desc()).offset(skip).limit(limit)

        result = await self.db.execute(stmt)
        return list(result.scalars().all())

    async def count_logs(
        self,
        tenant_id: uuid.UUID | None = None,
        user_id: uuid.UUID | None = None,
        action: str | None = None,
        resource_type: str | None = None,
    ) -> int:
        """Count audit logs matching filters."""
        stmt = select(func.count(AuditLog.id)).where(
            AuditLog.is_deleted == False  # noqa: E712
        )

        if tenant_id:
            stmt = stmt.where(AuditLog.tenant_id == str(tenant_id))
        if user_id:
            stmt = stmt.where(AuditLog.user_id == str(user_id))
        if action:
            stmt = stmt.where(AuditLog.action == action)
        if resource_type:
            stmt = stmt.where(AuditLog.resource_type == resource_type)

        result = await self.db.execute(stmt)
        return result.scalar() or 0

    async def get_logs_by_resource(
        self,
        resource_type: str,
        resource_id: str,
        skip: int = 0,
        limit: int = 20,
    ) -> list[AuditLog]:
        """Get audit logs for a specific resource."""
        stmt = (
            select(AuditLog)
            .where(
                AuditLog.resource_type == resource_type,
                AuditLog.resource_id == resource_id,
                AuditLog.is_deleted == False,  # noqa: E712
            )
            .order_by(AuditLog.created_at.desc())
            .offset(skip)
            .limit(limit)
        )
        result = await self.db.execute(stmt)
        return list(result.scalars().all())

    async def delete_log(self, log_id: uuid.UUID) -> None:
        """Soft delete an audit log."""
        log = await self.get_log_by_id(log_id)
        log.soft_delete()
        await self.db.flush()
