"""账号画像服务。

实现账号画像的 CRUD 和标签配置功能。
"""

import uuid

from sqlalchemy import func, select
from sqlalchemy.ext.asyncio import AsyncSession

from ai_employee.core.exceptions import ConflictException, NotFoundException
from ai_employee.models.trend import AccountProfile
from ai_employee.schemas.trend import AccountProfileCreate, AccountProfileUpdate


class AccountProfileService:
    """账号画像服务。"""

    def __init__(self, db: AsyncSession) -> None:
        self.db = db

    async def create_profile(self, data: AccountProfileCreate) -> AccountProfile:
        """创建账号画像。"""
        stmt = select(AccountProfile).where(
            AccountProfile.platform == data.platform,
            AccountProfile.account_id == data.account_id,
            AccountProfile.is_deleted == False,  # noqa: E712
        )
        result = await self.db.execute(stmt)
        existing = result.scalar_one_or_none()
        if existing:
            raise ConflictException(f"账号画像已存在 ({data.platform}: {data.account_id})")

        profile = AccountProfile(**data.model_dump())
        self.db.add(profile)
        await self.db.flush()
        await self.db.refresh(profile)
        return profile

    async def get_by_id(self, profile_id: uuid.UUID) -> AccountProfile:
        """根据 ID 获取账号画像。"""
        stmt = select(AccountProfile).where(
            AccountProfile.id == str(profile_id),
            AccountProfile.is_deleted == False,  # noqa: E712
        )
        result = await self.db.execute(stmt)
        profile = result.scalar_one_or_none()
        if not profile:
            raise NotFoundException("账号画像")
        return profile

    async def list_profiles(
        self,
        platform: str | None = None,
        skip: int = 0,
        limit: int = 20,
    ) -> tuple[list[AccountProfile], int]:
        """获取账号画像列表。"""
        where_conditions = [AccountProfile.is_deleted == False]  # noqa: E712
        if platform:
            where_conditions.append(AccountProfile.platform == platform)

        count_stmt = select(func.count(AccountProfile.id)).where(*where_conditions)
        count_result = await self.db.execute(count_stmt)
        total = count_result.scalar() or 0

        stmt = (
            select(AccountProfile)
            .where(*where_conditions)
            .order_by(AccountProfile.name)
            .offset(skip)
            .limit(limit)
        )
        result = await self.db.execute(stmt)
        return list(result.scalars().all()), total

    async def update_profile(
        self, profile_id: uuid.UUID, data: AccountProfileUpdate
    ) -> AccountProfile:
        """更新账号画像。"""
        profile = await self.get_by_id(profile_id)

        update_data = data.model_dump(exclude_unset=True)
        for key, value in update_data.items():
            setattr(profile, key, value)

        await self.db.flush()
        await self.db.refresh(profile)
        return profile

    async def delete_profile(self, profile_id: uuid.UUID) -> None:
        """软删除账号画像。"""
        profile = await self.get_by_id(profile_id)
        profile.soft_delete()
        await self.db.flush()

    async def get_profiles_by_platform(self, platform: str) -> list[AccountProfile]:
        """根据平台获取所有账号画像。"""
        stmt = select(AccountProfile).where(
            AccountProfile.platform == platform,
            AccountProfile.is_deleted == False,  # noqa: E712
        ).order_by(AccountProfile.name)
        result = await self.db.execute(stmt)
        return list(result.scalars().all())
