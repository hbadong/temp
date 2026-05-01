"""User endpoints."""

import uuid

from fastapi import APIRouter, Depends, Query, status
from sqlalchemy.ext.asyncio import AsyncSession

from ai_employee.core.permissions import require_permission
from ai_employee.dependencies import get_current_user_id
from ai_employee.db.session import get_db
from ai_employee.schemas.base import ApiResponse, PaginatedResponse
from ai_employee.schemas.user import UserCreate, UserResponse, UserUpdate
from ai_employee.services.user_service import UserService

router = APIRouter(prefix="/users", tags=["Users"])


# /me endpoints must be before /{user_id} to avoid route conflicts

@router.get("/me", response_model=ApiResponse[UserResponse])
async def get_current_user_profile(
    current_user_id: uuid.UUID = Depends(get_current_user_id),
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[UserResponse]:
    """Get current user's profile."""
    service = UserService(db)
    user = await service.get_by_id(current_user_id)
    return ApiResponse(
        code=status.HTTP_200_OK,
        data=UserResponse.model_validate(user),
    )


@router.patch("/me", response_model=ApiResponse[UserResponse])
async def update_current_user_profile(
    email: str | None = None,
    username: str | None = None,
    full_name: str | None = None,
    current_user_id: uuid.UUID = Depends(get_current_user_id),
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[UserResponse]:
    """Update current user's profile."""
    service = UserService(db)
    user = await service.update_profile(
        user_id=current_user_id,
        email=email,
        username=username,
        full_name=full_name,
    )
    return ApiResponse(
        code=status.HTTP_200_OK,
        message="Profile updated successfully",
        data=UserResponse.model_validate(user),
    )


@router.post("/me/change-password", response_model=ApiResponse[None])
async def change_password(
    current_password: str,
    new_password: str,
    current_user_id: uuid.UUID = Depends(get_current_user_id),
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[None]:
    """Change current user's password."""
    service = UserService(db)
    await service.change_password(
        user_id=current_user_id,
        current_password=current_password,
        new_password=new_password,
    )
    return ApiResponse(
        code=status.HTTP_200_OK,
        message="Password changed successfully",
    )


@router.get(
    "",
    response_model=ApiResponse[PaginatedResponse[UserResponse]],
    dependencies=[Depends(require_permission("user:list"))],
)
async def list_users(
    tenant_id: uuid.UUID | None = Query(None),
    page: int = Query(1, ge=1),
    page_size: int = Query(20, ge=1, le=100),
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[PaginatedResponse[UserResponse]]:
    """List users with optional tenant filter."""
    service = UserService(db)
    skip = (page - 1) * page_size
    users, total = await service.list_users(tenant_id=tenant_id, skip=skip, limit=page_size)

    return ApiResponse(
        code=status.HTTP_200_OK,
        data=PaginatedResponse(
            items=[UserResponse.model_validate(u) for u in users],
            total=total,
            page=page,
            page_size=page_size,
            has_next=(skip + page_size) < total,
            has_prev=page > 1,
        ),
    )


@router.post(
    "",
    response_model=ApiResponse[UserResponse],
    status_code=status.HTTP_201_CREATED,
    dependencies=[Depends(require_permission("user:create"))],
)
async def create_user(
    data: UserCreate,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[UserResponse]:
    """Create a new user."""
    service = UserService(db)
    user = await service.create(data)
    return ApiResponse(
        code=status.HTTP_201_CREATED,
        message="User created successfully",
        data=UserResponse.model_validate(user),
    )


@router.get(
    "/{user_id}",
    response_model=ApiResponse[UserResponse],
    dependencies=[Depends(require_permission("user:read"))],
)
async def get_user(
    user_id: uuid.UUID,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[UserResponse]:
    """Get user by ID."""
    service = UserService(db)
    user = await service.get_by_id(user_id)
    return ApiResponse(
        code=status.HTTP_200_OK,
        data=UserResponse.model_validate(user),
    )


@router.patch(
    "/{user_id}",
    response_model=ApiResponse[UserResponse],
    dependencies=[Depends(require_permission("user:update"))],
)
async def update_user(
    user_id: uuid.UUID,
    data: UserUpdate,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[UserResponse]:
    """Update an existing user."""
    service = UserService(db)
    user = await service.update(user_id, data)
    return ApiResponse(
        code=status.HTTP_200_OK,
        message="User updated successfully",
        data=UserResponse.model_validate(user),
    )


@router.delete(
    "/{user_id}",
    response_model=ApiResponse[None],
    dependencies=[Depends(require_permission("user:delete"))],
)
async def delete_user(
    user_id: uuid.UUID,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[None]:
    """Soft delete a user."""
    service = UserService(db)
    await service.delete(user_id)
    return ApiResponse(
        code=status.HTTP_200_OK,
        message="User deleted successfully",
    )
