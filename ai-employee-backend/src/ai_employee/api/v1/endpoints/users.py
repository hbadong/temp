"""User endpoints."""

import uuid

from fastapi import APIRouter, Depends, Query, status
from sqlalchemy.ext.asyncio import AsyncSession

from ai_employee.db.session import get_db
from ai_employee.schemas.base import ApiResponse, PaginatedResponse
from ai_employee.schemas.user import UserCreate, UserResponse, UserUpdate
from ai_employee.services.user_service import UserService

router = APIRouter(prefix="/users", tags=["Users"])


@router.get("", response_model=ApiResponse[PaginatedResponse[UserResponse]])
async def list_users(
    tenant_id: uuid.UUID | None = Query(None),
    page: int = Query(1, ge=1),
    page_size: int = Query(20, ge=1, le=100),
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[PaginatedResponse[UserResponse]]:
    """List users with optional tenant filter."""
    service = UserService(db)
    skip = (page - 1) * page_size
    users = await service.list_users(tenant_id=tenant_id, skip=skip, limit=page_size)

    return ApiResponse(
        code=status.HTTP_200_OK,
        data=PaginatedResponse(
            items=[UserResponse.model_validate(u) for u in users],
            total=len(users),
            page=page,
            page_size=page_size,
            has_next=len(users) == page_size,
            has_prev=page > 1,
        ),
    )


@router.get("/{user_id}", response_model=ApiResponse[UserResponse])
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


@router.post("", response_model=ApiResponse[UserResponse], status_code=status.HTTP_201_CREATED)
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


@router.patch("/{user_id}", response_model=ApiResponse[UserResponse])
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


@router.delete("/{user_id}", response_model=ApiResponse[None])
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
