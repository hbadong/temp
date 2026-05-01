"""RBAC service for role-based access control."""

import uuid

from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession
from sqlalchemy.orm import selectinload

from ai_employee.core.exceptions import ConflictException, NotFoundException
from ai_employee.models.rbac import Permission, Role, RolePermission, UserRole
from ai_employee.schemas.rbac import (
    PermissionCreate,
    RoleCreate,
    RoleUpdate,
    UserPermissionResponse,
)


class RBACService:
    """Service for role and permission management."""

    def __init__(self, db: AsyncSession) -> None:
        self.db = db

    # Permission CRUD

    async def create_permission(self, data: PermissionCreate) -> Permission:
        """Create a new permission."""
        existing = await self.get_permission_by_code(data.code)
        if existing:
            raise ConflictException(f"Permission '{data.code}' already exists")

        permission = Permission(
            code=data.code,
            description=data.description,
        )
        self.db.add(permission)
        await self.db.flush()
        await self.db.refresh(permission)
        return permission

    async def get_permission_by_code(self, code: str) -> Permission | None:
        """Get permission by code."""
        stmt = select(Permission).where(
            Permission.code == code,
            Permission.is_deleted == False,  # noqa: E712
        )
        result = await self.db.execute(stmt)
        return result.scalar_one_or_none()

    async def get_permission_by_id(self, permission_id: uuid.UUID) -> Permission:
        """Get permission by ID."""
        stmt = select(Permission).where(
            Permission.id == str(permission_id),
            Permission.is_deleted == False,  # noqa: E712
        )
        result = await self.db.execute(stmt)
        permission = result.scalar_one_or_none()
        if not permission:
            raise NotFoundException("Permission")
        return permission

    async def list_permissions(self, skip: int = 0, limit: int = 50) -> list[Permission]:
        """List all permissions."""
        stmt = (
            select(Permission)
            .where(Permission.is_deleted == False)  # noqa: E712
            .order_by(Permission.code)
            .offset(skip)
            .limit(limit)
        )
        result = await self.db.execute(stmt)
        return list(result.scalars().all())

    async def delete_permission(self, permission_id: uuid.UUID) -> None:
        """Soft delete a permission."""
        permission = await self.get_permission_by_id(permission_id)
        permission.soft_delete()
        await self.db.flush()

    # Role CRUD

    async def create_role(self, data: RoleCreate) -> Role:
        """Create a new role with optional permissions."""
        existing = await self.get_role_by_name(data.name, data.tenant_id)
        if existing:
            raise ConflictException(f"Role '{data.name}' already exists")

        role = Role(
            name=data.name,
            description=data.description,
            tenant_id=str(data.tenant_id) if data.tenant_id else None,
        )
        self.db.add(role)
        await self.db.flush()

        if data.permission_codes:
            await self._assign_permissions_to_role(role.id, data.permission_codes)

        await self.db.refresh(role)
        return role

    async def get_role_by_name(
        self, name: str, tenant_id: uuid.UUID | None = None
    ) -> Role | None:
        """Get role by name, optionally scoped to tenant."""
        stmt = select(Role).where(
            Role.name == name,
            Role.is_deleted == False,  # noqa: E712
        )
        if tenant_id:
            stmt = stmt.where(Role.tenant_id == str(tenant_id))
        else:
            stmt = stmt.where(Role.tenant_id.is_(None))

        result = await self.db.execute(stmt)
        return result.scalar_one_or_none()

    async def get_role_by_id(self, role_id: uuid.UUID) -> Role:
        """Get role by ID with permissions loaded."""
        stmt = (
            select(Role)
            .options(selectinload(Role.role_permissions).selectinload(RolePermission.permission))
            .where(
                Role.id == str(role_id),
                Role.is_deleted == False,  # noqa: E712
            )
        )
        result = await self.db.execute(stmt)
        role = result.scalar_one_or_none()
        if not role:
            raise NotFoundException("Role")
        return role

    async def list_roles(
        self, tenant_id: uuid.UUID | None = None, skip: int = 0, limit: int = 50
    ) -> list[Role]:
        """List roles, optionally scoped to tenant."""
        stmt = (
            select(Role)
            .options(selectinload(Role.role_permissions).selectinload(RolePermission.permission))
            .where(Role.is_deleted == False)  # noqa: E712
        )

        if tenant_id:
            stmt = stmt.where(Role.tenant_id == str(tenant_id))

        stmt = stmt.order_by(Role.name).offset(skip).limit(limit)

        result = await self.db.execute(stmt)
        return list(result.scalars().all())

    async def update_role(self, role_id: uuid.UUID, data: RoleUpdate) -> Role:
        """Update a role."""
        role = await self.get_role_by_id(role_id)

        if data.name is not None:
            existing = await self.get_role_by_name(data.name, role.tenant_id)
            if existing and existing.id != str(role_id):
                raise ConflictException(f"Role '{data.name}' already exists")
            role.name = data.name

        if data.description is not None:
            role.description = data.description

        await self.db.flush()
        await self.db.refresh(role)
        return role

    async def delete_role(self, role_id: uuid.UUID) -> None:
        """Soft delete a role."""
        role = await self.get_role_by_id(role_id)
        role.soft_delete()
        await self.db.flush()

    async def add_permissions_to_role(
        self, role_id: uuid.UUID, permission_codes: list[str]
    ) -> Role:
        """Add permissions to a role."""
        role = await self.get_role_by_id(role_id)

        for code in permission_codes:
            permission = await self.get_permission_by_code(code)
            if not permission:
                raise NotFoundException(f"Permission '{code}'")

            existing = await self._get_role_permission(role_id, permission.id)
            if not existing:
                role_perm = RolePermission(role_id=str(role_id), permission_id=permission.id)
                self.db.add(role_perm)

        await self.db.flush()
        await self.db.refresh(role)
        return role

    async def remove_permissions_from_role(
        self, role_id: uuid.UUID, permission_codes: list[str]
    ) -> Role:
        """Remove permissions from a role."""
        role = await self.get_role_by_id(role_id)

        for code in permission_codes:
            permission = await self.get_permission_by_code(code)
            if permission:
                role_perm = await self._get_role_permission(role_id, permission.id)
                if role_perm:
                    await self.db.delete(role_perm)

        await self.db.flush()
        await self.db.refresh(role)
        return role

    # User-Role Assignment

    async def assign_role_to_user(self, user_id: uuid.UUID, role_id: uuid.UUID) -> UserRole:
        """Assign a role to a user."""
        await self.get_role_by_id(role_id)

        existing = await self._get_user_role(user_id, role_id)
        if existing:
            raise ConflictException("Role already assigned to user")

        user_role = UserRole(user_id=str(user_id), role_id=str(role_id))
        self.db.add(user_role)
        await self.db.flush()
        await self.db.refresh(user_role)
        return user_role

    async def remove_role_from_user(self, user_id: uuid.UUID, role_id: uuid.UUID) -> None:
        """Remove a role from a user."""
        user_role = await self._get_user_role(user_id, role_id)
        if not user_role:
            raise NotFoundException("User role assignment")

        await self.db.delete(user_role)
        await self.db.flush()

    async def get_user_roles(self, user_id: uuid.UUID) -> list[Role]:
        """Get all roles assigned to a user."""
        stmt = (
            select(Role)
            .options(selectinload(Role.role_permissions).selectinload(RolePermission.permission))
            .join(UserRole, UserRole.role_id == Role.id)
            .where(
                UserRole.user_id == str(user_id),
                Role.is_deleted == False,  # noqa: E712
            )
        )
        result = await self.db.execute(stmt)
        return list(result.scalars().all())

    async def get_user_permissions(self, user_id: uuid.UUID) -> UserPermissionResponse:
        """Get all effective permissions for a user (via roles)."""
        roles = await self.get_user_roles(user_id)

        all_permissions: dict[str, Permission] = {}
        for role in roles:
            for rp in role.role_permissions:
                all_permissions[str(rp.permission.id)] = rp.permission

        permissions = list(all_permissions.values())
        return UserPermissionResponse(
            user_id=user_id,
            permissions=permissions,
            roles=roles,
        )

    async def has_permission(self, user_id: uuid.UUID, permission_code: str) -> bool:
        """Check if a user has a specific permission."""
        stmt = (
            select(Permission)
            .join(RolePermission, RolePermission.permission_id == Permission.id)
            .join(UserRole, UserRole.role_id == RolePermission.role_id)
            .where(
                UserRole.user_id == str(user_id),
                Permission.code == permission_code,
                Permission.is_deleted == False,  # noqa: E712
                RolePermission.is_deleted == False,  # noqa: E712
                UserRole.is_deleted == False,  # noqa: E712
            )
        )
        result = await self.db.execute(stmt)
        return result.scalar_one_or_none() is not None

    # Internal helpers

    async def _assign_permissions_to_role(
        self, role_id: uuid.UUID, permission_codes: list[str]
    ) -> None:
        """Assign multiple permissions to a role."""
        for code in permission_codes:
            permission = await self.get_permission_by_code(code)
            if not permission:
                raise NotFoundException(f"Permission '{code}'")

            existing = await self._get_role_permission(role_id, permission.id)
            if not existing:
                role_perm = RolePermission(role_id=role_id, permission_id=permission.id)
                self.db.add(role_perm)

    async def _get_role_permission(
        self, role_id: uuid.UUID, permission_id: uuid.UUID
    ) -> RolePermission | None:
        """Get a role-permission mapping."""
        stmt = select(RolePermission).where(
            RolePermission.role_id == str(role_id),
            RolePermission.permission_id == permission_id,
            RolePermission.is_deleted == False,  # noqa: E712
        )
        result = await self.db.execute(stmt)
        return result.scalar_one_or_none()

    async def _get_user_role(
        self, user_id: uuid.UUID, role_id: uuid.UUID
    ) -> UserRole | None:
        """Get a user-role mapping."""
        stmt = select(UserRole).where(
            UserRole.user_id == str(user_id),
            UserRole.role_id == str(role_id),
            UserRole.is_deleted == False,  # noqa: E712
        )
        result = await self.db.execute(stmt)
        return result.scalar_one_or_none()
