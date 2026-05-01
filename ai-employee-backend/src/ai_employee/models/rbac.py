"""RBAC models for roles and permissions."""

import uuid

from sqlalchemy import ForeignKey, String, UniqueConstraint
from sqlalchemy.orm import Mapped, mapped_column, relationship

from ai_employee.models.base import BaseModel


class Role(BaseModel):
    """Represents a role in the RBAC system."""

    __tablename__ = "roles"

    name: Mapped[str] = mapped_column(String(100), nullable=False, unique=True, index=True)
    description: Mapped[str | None] = mapped_column(String(500), nullable=True)
    tenant_id: Mapped[str | None] = mapped_column(
        ForeignKey("tenants.id"),
        nullable=True,
        index=True,
    )

    # Relationships
    role_permissions = relationship("RolePermission", back_populates="role", lazy="selectin", cascade="all, delete-orphan")
    permissions = relationship("Permission", secondary="role_permissions", lazy="selectin", viewonly=True)

    def __repr__(self) -> str:
        return f"<Role(id={self.id}, name={self.name})>"


class Permission(BaseModel):
    """Defines available permissions in the system."""

    __tablename__ = "permissions"

    code: Mapped[str] = mapped_column(String(100), nullable=False, unique=True, index=True)
    description: Mapped[str | None] = mapped_column(String(500), nullable=True)

    # Relationships
    roles = relationship("Role", secondary="role_permissions", lazy="selectin", viewonly=True)

    def __repr__(self) -> str:
        return f"<Permission(code={self.code})>"


class RolePermission(BaseModel):
    """Maps roles to permissions (many-to-many)."""

    __tablename__ = "role_permissions"

    role_id: Mapped[str] = mapped_column(
        String(36),
        ForeignKey("roles.id"),
        nullable=False,
    )
    permission_id: Mapped[str] = mapped_column(
        String(36),
        ForeignKey("permissions.id"),
        nullable=False,
    )

    __table_args__ = (
        UniqueConstraint("role_id", "permission_id", name="uq_role_permission"),
    )

    # Relationships
    role = relationship("Role", back_populates="role_permissions", lazy="selectin")
    permission = relationship("Permission", lazy="selectin")

    def __repr__(self) -> str:
        return f"<RolePermission(role_id={self.role_id}, permission_id={self.permission_id})>"


class UserRole(BaseModel):
    """Maps users to roles (many-to-many)."""

    __tablename__ = "user_roles"

    user_id: Mapped[str] = mapped_column(
        String(36),
        ForeignKey("users.id"),
        nullable=False,
        index=True,
    )
    role_id: Mapped[str] = mapped_column(
        String(36),
        ForeignKey("roles.id"),
        nullable=False,
        index=True,
    )

    __table_args__ = (
        UniqueConstraint("user_id", "role_id", name="uq_user_role"),
    )

    def __repr__(self) -> str:
        return f"<UserRole(user_id={self.user_id}, role_id={self.role_id})>"
