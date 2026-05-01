"""Quota model for tenant usage tracking."""

import uuid

from sqlalchemy import BigInteger, ForeignKey, Integer, String, UniqueConstraint
from sqlalchemy.orm import Mapped, mapped_column

from ai_employee.models.base import BaseModel


class Quota(BaseModel):
    """Tracks quota limits and usage for each tenant."""

    __tablename__ = "quotas"

    tenant_id: Mapped[uuid.UUID] = mapped_column(
        String(36),
        ForeignKey("tenants.id"),
        nullable=False,
        index=True,
    )
    resource_type: Mapped[str] = mapped_column(String(100), nullable=False)
    limit: Mapped[int] = mapped_column(Integer, nullable=False, default=0)
    used: Mapped[int] = mapped_column(Integer, nullable=False, default=0)
    period: Mapped[str] = mapped_column(String(50), nullable=False, default="monthly")

    __table_args__ = (
        UniqueConstraint("tenant_id", "resource_type", name="uq_tenant_resource"),
    )

    def __repr__(self) -> str:
        return f"<Quota(tenant_id={self.tenant_id}, resource={self.resource_type}, used={self.used}/{self.limit})>"


class UsageLog(BaseModel):
    """Logs individual usage events for auditing."""

    __tablename__ = "usage_logs"

    tenant_id: Mapped[uuid.UUID] = mapped_column(
        String(36),
        ForeignKey("tenants.id"),
        nullable=False,
        index=True,
    )
    user_id: Mapped[uuid.UUID | None] = mapped_column(
        String(36),
        ForeignKey("users.id"),
        nullable=True,
    )
    resource_type: Mapped[str] = mapped_column(String(100), nullable=False, index=True)
    amount: Mapped[int] = mapped_column(Integer, nullable=False, default=1)
    description: Mapped[str | None] = mapped_column(String(500), nullable=True)

    def __repr__(self) -> str:
        return f"<UsageLog(tenant_id={self.tenant_id}, resource={self.resource_type}, amount={self.amount})>"
