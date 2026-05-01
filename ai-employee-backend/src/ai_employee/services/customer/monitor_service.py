"""监控任务服务。"""

import uuid

from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession

from ai_employee.models.customer import MonitorTask


class MonitorTaskService:
    """监控任务服务。"""

    def __init__(self, db: AsyncSession) -> None:
        self.db = db

    async def create_task(self, tenant_id: str, **kwargs) -> MonitorTask:
        """创建监控任务。"""
        task = MonitorTask(tenant_id=tenant_id, **kwargs)
        self.db.add(task)
        await self.db.commit()
        await self.db.refresh(task)
        return task

    async def get_task_by_id(self, task_id: uuid.UUID) -> MonitorTask | None:
        """根据 ID 获取任务。"""
        result = await self.db.execute(
            select(MonitorTask).where(MonitorTask.id == str(task_id))
        )
        return result.scalar_one_or_none()

    async def list_tasks(
        self,
        tenant_id: str,
        platform: str | None = None,
        status: str | None = None,
        skip: int = 0,
        limit: int = 20,
    ) -> tuple[list[MonitorTask], int]:
        """获取任务列表。"""
        query = select(MonitorTask).where(MonitorTask.tenant_id == tenant_id)

        if platform:
            query = query.where(MonitorTask.platform == platform)
        if status:
            query = query.where(MonitorTask.status == status)

        count_result = await self.db.execute(query)
        total = len(count_result.scalars().all())

        result = await self.db.execute(
            query.order_by(MonitorTask.created_at.desc()).offset(skip).limit(limit)
        )
        return result.scalars().all(), total

    async def update_task(self, task_id: uuid.UUID, **kwargs) -> MonitorTask | None:
        """更新任务。"""
        task = await self.get_task_by_id(task_id)
        if not task:
            return None
        for key, value in kwargs.items():
            if hasattr(task, key) and value is not None:
                setattr(task, key, value)
        await self.db.commit()
        await self.db.refresh(task)
        return task

    async def delete_task(self, task_id: uuid.UUID) -> bool:
        """删除任务。"""
        task = await self.get_task_by_id(task_id)
        if not task:
            return False
        await self.db.delete(task)
        await self.db.commit()
        return True

    async def increment_run_count(self, task_id: uuid.UUID) -> None:
        """增加运行次数。"""
        task = await self.get_task_by_id(task_id)
        if task:
            task.run_count += 1
            await self.db.commit()

    async def increment_comment_count(self, task_id: uuid.UUID, count: int = 1) -> None:
        """增加评论计数。"""
        task = await self.get_task_by_id(task_id)
        if task:
            task.comment_count += count
            await self.db.commit()

    async def get_active_tasks(
        self,
        tenant_id: str,
        platform: str | None = None,
    ) -> list[MonitorTask]:
        """获取活跃任务。"""
        query = select(MonitorTask).where(
            MonitorTask.tenant_id == tenant_id,
            MonitorTask.status == "active",
        )
        if platform:
            query = query.where(MonitorTask.platform == platform)

        result = await self.db.execute(query)
        return result.scalars().all()
