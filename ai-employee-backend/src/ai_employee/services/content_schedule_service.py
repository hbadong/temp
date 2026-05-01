"""内容排期服务。"""

import uuid

from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession

from ai_employee.models.content import ContentSchedule


class ContentScheduleService:
    """内容排期服务。"""

    def __init__(self, db: AsyncSession) -> None:
        self.db = db

    async def create(self, tenant_id: str, **kwargs) -> ContentSchedule:
        """创建内容排期。"""
        schedule = ContentSchedule(tenant_id=tenant_id, **kwargs)
        self.db.add(schedule)
        await self.db.commit()
        await self.db.refresh(schedule)
        return schedule

    async def get_by_id(self, schedule_id: uuid.UUID) -> ContentSchedule | None:
        """根据 ID 获取排期。"""
        result = await self.db.execute(
            select(ContentSchedule).where(ContentSchedule.id == str(schedule_id))
        )
        return result.scalar_one_or_none()

    async def get_list(
        self,
        tenant_id: str,
        platform: str | None = None,
        status: str | None = None,
        skip: int = 0,
        limit: int = 20,
    ) -> tuple[list[ContentSchedule], int]:
        """获取排期列表。"""
        query = select(ContentSchedule).where(ContentSchedule.tenant_id == tenant_id)

        if platform:
            query = query.where(ContentSchedule.platform == platform)
        if status:
            query = query.where(ContentSchedule.status == status)

        count_query = select(ContentSchedule).where(ContentSchedule.tenant_id == tenant_id)
        if platform:
            count_query = count_query.where(ContentSchedule.platform == platform)
        if status:
            count_query = count_query.where(ContentSchedule.status == status)

        count_result = await self.db.execute(count_query)
        total = len(count_result.scalars().all())

        result = await self.db.execute(query.order_by(ContentSchedule.scheduled_at).offset(skip).limit(limit))
        return result.scalars().all(), total

    async def get_calendar_view(
        self,
        tenant_id: str,
        start_date: str,
        end_date: str,
    ) -> list[ContentSchedule]:
        """获取日历视图排期。"""
        query = (
            select(ContentSchedule)
            .where(
                ContentSchedule.tenant_id == tenant_id,
                ContentSchedule.scheduled_at >= start_date,
                ContentSchedule.scheduled_at <= end_date,
            )
            .order_by(ContentSchedule.scheduled_at)
        )
        result = await self.db.execute(query)
        return result.scalars().all()

    async def update(self, schedule_id: uuid.UUID, **kwargs) -> ContentSchedule | None:
        """更新排期。"""
        schedule = await self.get_by_id(schedule_id)
        if not schedule:
            return None
        for key, value in kwargs.items():
            if hasattr(schedule, key) and value is not None:
                setattr(schedule, key, value)
        await self.db.commit()
        await self.db.refresh(schedule)
        return schedule

    async def delete(self, schedule_id: uuid.UUID) -> bool:
        """删除排期。"""
        schedule = await self.get_by_id(schedule_id)
        if not schedule:
            return False
        await self.db.delete(schedule)
        await self.db.commit()
        return True

    async def increment_retry(self, schedule_id: uuid.UUID) -> ContentSchedule | None:
        """增加重试次数。"""
        schedule = await self.get_by_id(schedule_id)
        if schedule:
            schedule.retry_count += 1
            await self.db.commit()
            await self.db.refresh(schedule)
        return schedule
