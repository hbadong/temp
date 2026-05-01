"""Authentication endpoints."""

from fastapi import APIRouter, Depends, status
from sqlalchemy.ext.asyncio import AsyncSession

from ai_employee.core.exceptions import UnauthorizedException
from ai_employee.core.security import create_access_token
from ai_employee.db.session import get_db
from ai_employee.schemas.base import ApiResponse
from ai_employee.schemas.user import Token, UserLogin
from ai_employee.services.user_service import UserService

router = APIRouter(prefix="/auth", tags=["Authentication"])


@router.post("/login", response_model=ApiResponse[Token])
async def login(
    credentials: UserLogin,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[Token]:
    """Authenticate user and return access token."""
    user_service = UserService(db)
    user = await user_service.authenticate(credentials.email, credentials.password)

    if not user:
        raise UnauthorizedException("Invalid email or password")

    token = create_access_token(
        subject=user.id,
        tenant_id=user.tenant_id,
        is_superuser=user.is_superuser,
    )

    return ApiResponse(
        code=status.HTTP_200_OK,
        message="Login successful",
        data=Token(access_token=token),
    )


@router.post("/logout", response_model=ApiResponse[None])
async def logout() -> ApiResponse[None]:
    """Logout current user (client-side token removal)."""
    return ApiResponse(
        code=status.HTTP_200_OK,
        message="Logout successful",
    )
