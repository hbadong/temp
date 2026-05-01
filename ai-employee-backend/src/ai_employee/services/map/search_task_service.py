"""地图搜索任务服务。"""

import uuid

from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession

from ai_employee.models.map import MapSearchTask


class MapSearchTaskService:
    """地图搜索任务服务。"""

    def __init__(self, db: AsyncSession) -> None:
        self.db = db

    async def create_task(self, tenant_id: str, **kwargs) -> MapSearchTask:
        """创建地图搜索任务。"""
        task = MapSearchTask(tenant_id=tenant_id, **kwargs)
        self.db.add(task)
        await self.db.commit()
        await self.db.refresh(task)
        return task

    async def get_task_by_id(self, task_id: uuid.UUID) -> MapSearchTask | None:
        """根据 ID 获取任务。"""
        result = await self.db.execute(
            select(MapSearchTask).where(MapSearchTask.id == str(task_id))
        )
        return result.scalar_one_or_none()

    async def list_tasks(
        self,
        tenant_id: str,
        platform: str | None = None,
        status: str | None = None,
        skip: int = 0,
        limit: int = 20,
    ) -> tuple[list[MapSearchTask], int]:
        """获取任务列表。"""
        query = select(MapSearchTask).where(MapSearchTask.tenant_id == tenant_id)

        if platform:
            query = query.where(MapSearchTask.platform == platform)
        if status:
            query = query.where(MapSearchTask.status == status)

        count_result = await self.db.execute(query)
        total = len(count_result.scalars().all())

        result = await self.db.execute(
            query.order_by(MapSearchTask.created_at.desc()).offset(skip).limit(limit)
        )
        return result.scalars().all(), total

    async def update_task(self, task_id: uuid.UUID, **kwargs) -> MapSearchTask | None:
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
