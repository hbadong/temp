"""热点数据服务。

实现热点数据抓取、标准化、去重入库等功能。
"""

import hashlib
import uuid

from sqlalchemy import func, select
from sqlalchemy.ext.asyncio import AsyncSession

from ai_employee.core.exceptions import ConflictException, NotFoundException
from ai_employee.models.trend import Trend
from ai_employee.schemas.trend import TrendCreate, TrendUpdate


class TrendService:
    """热点数据服务。"""

    def __init__(self, db: AsyncSession) -> None:
        self.db = db

    async def create_trend(self, data: TrendCreate) -> Trend:
        """创建热点数据（自动去重）。"""
        existing = await self.get_by_source_id(data.source_id)
        if existing:
            raise ConflictException(f"热点数据已存在 (source_id: {data.source_id})")

        trend = Trend(**data.model_dump())
        self.db.add(trend)
        await self.db.flush()
        await self.db.refresh(trend)
        return trend

    async def bulk_create_trends(self, data_list: list[TrendCreate]) -> list[Trend]:
        """批量创建热点数据（去重）。"""
        source_ids = [d.source_id for d in data_list]
        existing_stmt = select(Trend.source_id).where(
            Trend.source_id.in_(source_ids),
            Trend.is_deleted == False,  # noqa: E712
        )
        existing_result = await self.db.execute(existing_stmt)
        existing_ids = set(existing_result.scalars().all())

        new_trends = []
        for data in data_list:
            if data.source_id not in existing_ids:
                trend = Trend(**data.model_dump())
                self.db.add(trend)
                new_trends.append(trend)

        await self.db.flush()
        for t in new_trends:
            await self.db.refresh(t)
        return new_trends

    async def get_by_id(self, trend_id: uuid.UUID) -> Trend:
        """根据 ID 获取热点数据。"""
        stmt = select(Trend).where(
            Trend.id == str(trend_id),
            Trend.is_deleted == False,  # noqa: E712
        )
        result = await self.db.execute(stmt)
        trend = result.scalar_one_or_none()
        if not trend:
            raise NotFoundException("热点数据")
        return trend

    async def get_by_source_id(self, source_id: str) -> Trend | None:
        """根据 source_id 获取热点数据。"""
        stmt = select(Trend).where(
            Trend.source_id == source_id,
            Trend.is_deleted == False,  # noqa: E712
        )
        result = await self.db.execute(stmt)
        return result.scalar_one_or_none()

    async def list_trends(
        self,
        platform: str | None = None,
        category: str | None = None,
        min_hot_value: int | None = None,
        skip: int = 0,
        limit: int = 20,
    ) -> tuple[list[Trend], int]:
        """获取热点数据列表。"""
        where_conditions = [Trend.is_deleted == False]  # noqa: E712

        if platform:
            where_conditions.append(Trend.platform == platform)
        if category:
            where_conditions.append(Trend.category == category)
        if min_hot_value is not None:
            where_conditions.append(Trend.hot_value >= min_hot_value)

        count_stmt = select(func.count(Trend.id)).where(*where_conditions)
        count_result = await self.db.execute(count_stmt)
        total = count_result.scalar() or 0

        stmt = (
            select(Trend)
            .where(*where_conditions)
            .order_by(Trend.hot_value.desc())
            .offset(skip)
            .limit(limit)
        )
        result = await self.db.execute(stmt)
        return list(result.scalars().all()), total

    async def update_trend(self, trend_id: uuid.UUID, data: TrendUpdate) -> Trend:
        """更新热点数据。"""
        trend = await self.get_by_id(trend_id)

        update_data = data.model_dump(exclude_unset=True)
        for key, value in update_data.items():
            setattr(trend, key, value)

        await self.db.flush()
        await self.db.refresh(trend)
        return trend

    async def delete_trend(self, trend_id: uuid.UUID) -> None:
        """软删除热点数据。"""
        trend = await self.get_by_id(trend_id)
        trend.soft_delete()
        await self.db.flush()

    async def get_hot_trends(
        self,
        platform: str | None = None,
        limit: int = 50,
    ) -> list[Trend]:
        """获取热门热点数据。"""
        stmt = select(Trend).where(Trend.is_deleted == False)  # noqa: E712
        if platform:
            stmt = stmt.where(Trend.platform == platform)

        stmt = stmt.order_by(Trend.hot_value.desc()).limit(limit)
        result = await self.db.execute(stmt)
        return list(result.scalars().all())
