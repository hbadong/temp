"""认证端点。

提供用户登录、登出、令牌刷新等功能。
"""

from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession

from ai_employee.core.security import verify_password
from ai_employee.db.session import get_db
from ai_employee.middleware.auth import create_access_token, create_refresh_token, decode_token
from ai_employee.models.user import User
from ai_employee.schemas.base import ApiResponse
from ai_employee.schemas.user import Token, TokenRefresh, UserLogin

router = APIRouter(prefix="/auth", tags=["认证"])


@router.post("/login", response_model=ApiResponse[Token])
async def login(
    login_data: UserLogin,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[Token]:
    """用户登录。

    使用邮箱和密码登录，返回 access_token 和 refresh_token。
    """
    # 查找用户
    result = await db.execute(select(User).where(User.email == login_data.email))
    user = result.scalar_one_or_none()

    if user is None or not verify_password(login_data.password, user.hashed_password):
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="邮箱或密码错误",
            headers={"WWW-Authenticate": "Bearer"},
        )

    if not user.is_active:
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="用户已被禁用",
        )

    # 生成令牌
    token_data = {"sub": str(user.id), "role": user.role}
    access_token = create_access_token(data=token_data)
    refresh_token = create_refresh_token(data=token_data)

    return ApiResponse(
        code=0,
        message="登录成功",
        data=Token(
            access_token=access_token,
            refresh_token=refresh_token,
            token_type="Bearer",
        ),
    )


@router.post("/logout", response_model=ApiResponse[None])
async def logout() -> ApiResponse[None]:
    """用户登出。

    客户端应清除本地存储的 token。
    """
    return ApiResponse(
        code=0,
        message="登出成功",
    )


@router.post("/refresh", response_model=ApiResponse[Token])
async def refresh_token(
    refresh_data: TokenRefresh,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[Token]:
    """刷新 access_token。

    使用 refresh_token 获取新的 access_token。
    """
    try:
        payload = decode_token(refresh_data.refresh_token, "refresh")
        user_id: str | None = payload.get("sub")
        if user_id is None:
            raise ValueError
    except (ValueError, Exception) as e:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="无效的 refresh token",
        ) from e

    # 验证用户仍然存在且活跃
    result = await db.execute(select(User).where(User.id == user_id))
    user = result.scalar_one_or_none()

    if user is None or not user.is_active:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="用户不存在或已被禁用",
        )

    # 生成新的令牌
    token_data = {"sub": str(user.id), "role": user.role}
    access_token = create_access_token(data=token_data)
    new_refresh_token = create_refresh_token(data=token_data)

    return ApiResponse(
        code=0,
        message="令牌刷新成功",
        data=Token(
            access_token=access_token,
            refresh_token=new_refresh_token,
            token_type="Bearer",
        ),
    )


@router.get("/me", response_model=ApiResponse[dict])
async def get_current_user_info(
    current_user: User = Depends(lambda: None),  # 实际由 get_current_user 替换
) -> ApiResponse[dict]:
    """获取当前用户信息。"""
    return ApiResponse(
        code=0,
        message="success",
        data={
            "id": str(current_user.id),
            "email": current_user.email,
            "username": current_user.username,
            "full_name": current_user.full_name,
            "role": current_user.role,
            "tenant_id": str(current_user.tenant_id),
        },
    )
