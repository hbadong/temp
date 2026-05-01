"""Base model with common fields for all models."""

import uuid
from datetime import UTC, datetime

from sqlalchemy import Boolean, DateTime, String, func
from sqlalchemy.orm import Mapped, mapped_column

from ai_employee.db.session import Base


class BaseModel(Base):
    """Base class with common fields for all models."""

    __abstract__ = True

    id: Mapped[uuid.UUID] = mapped_column(
        String(36),
        primary_key=True,
        default=lambda: str(uuid.uuid4()),
    )
    tenant_id: Mapped[uuid.UUID | None] = mapped_column(
        String(36),
        nullable=True,
        index=True,
    )
    created_at: Mapped[datetime] = mapped_column(
        DateTime(timezone=True),
        server_default=func.now(),
        nullable=False,
    )
    updated_at: Mapped[datetime] = mapped_column(
        DateTime(timezone=True),
        server_default=func.now(),
        onupdate=func.now(),
        nullable=False,
    )
    is_deleted: Mapped[bool] = mapped_column(
        Boolean,
        default=False,
        nullable=False,
    )

    def soft_delete(self) -> None:
        """Mark record as soft deleted."""
        self.is_deleted = True
        self.updated_at = datetime.now(UTC)
