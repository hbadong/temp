"""RBAC endpoints for roles, permissions, and user role assignment."""

import uuid

from fastapi import APIRouter, Depends, Query, status
from sqlalchemy.ext.asyncio import AsyncSession

from ai_employee.core.permissions import require_permission
from ai_employee.db.session import get_db
from ai_employee.schemas.base import ApiResponse, PaginatedResponse
from ai_employee.schemas.rbac import (
    PermissionCreate,
    PermissionResponse,
    RoleCreate,
    RoleResponse,
    RoleUpdate,
    UserRoleAssign,
    UserRoleResponse,
    UserPermissionResponse,
)
from ai_employee.services.rbac_service import RBACService

router = APIRouter(prefix="", tags=["RBAC"])


# Permission endpoints

@router.get(
    "/permissions",
    response_model=ApiResponse[PaginatedResponse[PermissionResponse]],
    dependencies=[Depends(require_permission("permission:read"))],
)
async def list_permissions(
    page: int = Query(1, ge=1),
    page_size: int = Query(50, ge=1, le=100),
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[PaginatedResponse[PermissionResponse]]:
    """List all permissions."""
    service = RBACService(db)
    skip = (page - 1) * page_size
    permissions = await service.list_permissions(skip=skip, limit=page_size)

    return ApiResponse(
        code=status.HTTP_200_OK,
        data=PaginatedResponse(
            items=[PermissionResponse.model_validate(p) for p in permissions],
            total=len(permissions),
            page=page,
            page_size=page_size,
            has_next=len(permissions) == page_size,
            has_prev=page > 1,
        ),
    )


@router.post(
    "/permissions",
    response_model=ApiResponse[PermissionResponse],
    status_code=status.HTTP_201_CREATED,
    dependencies=[Depends(require_permission("permission:create"))],
)
async def create_permission(
    data: PermissionCreate,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[PermissionResponse]:
    """Create a new permission."""
    service = RBACService(db)
    permission = await service.create_permission(data)
    return ApiResponse(
        code=status.HTTP_201_CREATED,
        message="Permission created successfully",
        data=PermissionResponse.model_validate(permission),
    )


@router.delete(
    "/permissions/{permission_id}",
    response_model=ApiResponse[None],
    dependencies=[Depends(require_permission("permission:delete"))],
)
async def delete_permission(
    permission_id: uuid.UUID,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[None]:
    """Delete a permission."""
    service = RBACService(db)
    await service.delete_permission(permission_id)
    return ApiResponse(
        code=status.HTTP_200_OK,
        message="Permission deleted successfully",
    )


# Role endpoints

@router.get(
    "/roles",
    response_model=ApiResponse[PaginatedResponse[RoleResponse]],
    dependencies=[Depends(require_permission("role:read"))],
)
async def list_roles(
    tenant_id: uuid.UUID | None = Query(None),
    page: int = Query(1, ge=1),
    page_size: int = Query(50, ge=1, le=100),
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[PaginatedResponse[RoleResponse]]:
    """List all roles."""
    service = RBACService(db)
    skip = (page - 1) * page_size
    roles = await service.list_roles(tenant_id=tenant_id, skip=skip, limit=page_size)

    return ApiResponse(
        code=status.HTTP_200_OK,
        data=PaginatedResponse(
            items=[RoleResponse.model_validate(r) for r in roles],
            total=len(roles),
            page=page,
            page_size=page_size,
            has_next=len(roles) == page_size,
            has_prev=page > 1,
        ),
    )


@router.post(
    "/roles",
    response_model=ApiResponse[RoleResponse],
    status_code=status.HTTP_201_CREATED,
    dependencies=[Depends(require_permission("role:create"))],
)
async def create_role(
    data: RoleCreate,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[RoleResponse]:
    """Create a new role."""
    service = RBACService(db)
    role = await service.create_role(data)
    return ApiResponse(
        code=status.HTTP_201_CREATED,
        message="Role created successfully",
        data=RoleResponse.model_validate(role),
    )


@router.get(
    "/roles/{role_id}",
    response_model=ApiResponse[RoleResponse],
    dependencies=[Depends(require_permission("role:read"))],
)
async def get_role(
    role_id: uuid.UUID,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[RoleResponse]:
    """Get role by ID."""
    service = RBACService(db)
    role = await service.get_role_by_id(role_id)
    return ApiResponse(
        code=status.HTTP_200_OK,
        data=RoleResponse.model_validate(role),
    )


@router.patch(
    "/roles/{role_id}",
    response_model=ApiResponse[RoleResponse],
    dependencies=[Depends(require_permission("role:update"))],
)
async def update_role(
    role_id: uuid.UUID,
    data: RoleUpdate,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[RoleResponse]:
    """Update a role."""
    service = RBACService(db)
    role = await service.update_role(role_id, data)
    return ApiResponse(
        code=status.HTTP_200_OK,
        message="Role updated successfully",
        data=RoleResponse.model_validate(role),
    )


@router.delete(
    "/roles/{role_id}",
    response_model=ApiResponse[None],
    dependencies=[Depends(require_permission("role:delete"))],
)
async def delete_role(
    role_id: uuid.UUID,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[None]:
    """Delete a role."""
    service = RBACService(db)
    await service.delete_role(role_id)
    return ApiResponse(
        code=status.HTTP_200_OK,
        message="Role deleted successfully",
    )


@router.post(
    "/roles/{role_id}/permissions",
    response_model=ApiResponse[RoleResponse],
    dependencies=[Depends(require_permission("role:update"))],
)
async def add_permissions_to_role(
    role_id: uuid.UUID,
    permission_codes: list[str],
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[RoleResponse]:
    """Add permissions to a role."""
    service = RBACService(db)
    role = await service.add_permissions_to_role(role_id, permission_codes)
    return ApiResponse(
        code=status.HTTP_200_OK,
        message="Permissions added to role",
        data=RoleResponse.model_validate(role),
    )


@router.delete(
    "/roles/{role_id}/permissions",
    response_model=ApiResponse[RoleResponse],
    dependencies=[Depends(require_permission("role:update"))],
)
async def remove_permissions_from_role(
    role_id: uuid.UUID,
    permission_codes: list[str],
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[RoleResponse]:
    """Remove permissions from a role."""
    service = RBACService(db)
    role = await service.remove_permissions_from_role(role_id, permission_codes)
    return ApiResponse(
        code=status.HTTP_200_OK,
        message="Permissions removed from role",
        data=RoleResponse.model_validate(role),
    )


# User-Role assignment endpoints

@router.post(
    "/user-roles",
    response_model=ApiResponse[UserRoleResponse],
    status_code=status.HTTP_201_CREATED,
    dependencies=[Depends(require_permission("role:update"))],
)
async def assign_role_to_user(
    data: UserRoleAssign,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[UserRoleResponse]:
    """Assign a role to a user."""
    service = RBACService(db)
    user_role = await service.assign_role_to_user(data.user_id, data.role_id)
    return ApiResponse(
        code=status.HTTP_201_CREATED,
        message="Role assigned to user",
        data=UserRoleResponse.model_validate(user_role),
    )


@router.delete(
    "/user-roles",
    response_model=ApiResponse[None],
    dependencies=[Depends(require_permission("role:update"))],
)
async def remove_role_from_user(
    user_id: uuid.UUID = Query(...),
    role_id: uuid.UUID = Query(...),
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[None]:
    """Remove a role from a user."""
    service = RBACService(db)
    await service.remove_role_from_user(user_id, role_id)
    return ApiResponse(
        code=status.HTTP_200_OK,
        message="Role removed from user",
    )


@router.get(
    "/users/{user_id}/roles",
    response_model=ApiResponse[list[RoleResponse]],
    dependencies=[Depends(require_permission("role:read"))],
)
async def get_user_roles(
    user_id: uuid.UUID,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[list[RoleResponse]]:
    """Get all roles assigned to a user."""
    service = RBACService(db)
    roles = await service.get_user_roles(user_id)
    return ApiResponse(
        code=status.HTTP_200_OK,
        data=[RoleResponse.model_validate(r) for r in roles],
    )


@router.get(
    "/users/{user_id}/permissions",
    response_model=ApiResponse[UserPermissionResponse],
    dependencies=[Depends(require_permission("role:read"))],
)
async def get_user_permissions(
    user_id: uuid.UUID,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[UserPermissionResponse]:
    """Get all effective permissions for a user."""
    service = RBACService(db)
    perms = await service.get_user_permissions(user_id)
    return ApiResponse(
        code=status.HTTP_200_OK,
        data=perms,
    )
