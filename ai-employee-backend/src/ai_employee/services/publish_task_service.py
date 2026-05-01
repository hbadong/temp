"""发布任务服务。"""

import uuid
from typing import Any

from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession

from ai_employee.models.publish import PublishTask
from ai_employee.services.publish_adapters import PublishAdapterFactory


class PublishTaskService:
    """发布任务服务。"""

    def __init__(self, db: AsyncSession) -> None:
        self.db = db

    async def create_task(self, tenant_id: str, **kwargs) -> PublishTask:
        """创建发布任务。"""
        task = PublishTask(tenant_id=tenant_id, **kwargs)
        self.db.add(task)
        await self.db.commit()
        await self.db.refresh(task)
        return task

    async def get_task_by_id(self, task_id: uuid.UUID) -> PublishTask | None:
        """根据 ID 获取任务。"""
        result = await self.db.execute(
            select(PublishTask).where(PublishTask.id == str(task_id))
        )
        return result.scalar_one_or_none()

    async def list_tasks(
        self,
        tenant_id: str,
        platform: str | None = None,
        status: str | None = None,
        skip: int = 0,
        limit: int = 20,
    ) -> tuple[list[PublishTask], int]:
        """获取任务列表。"""
        query = select(PublishTask).where(PublishTask.tenant_id == tenant_id)

        if platform:
            query = query.where(PublishTask.platform == platform)
        if status:
            query = query.where(PublishTask.status == status)

        count_result = await self.db.execute(query)
        total = len(count_result.scalars().all())

        result = await self.db.execute(
            query.order_by(PublishTask.created_at.desc()).offset(skip).limit(limit)
        )
        return result.scalars().all(), total

    async def update_task(self, task_id: uuid.UUID, **kwargs) -> PublishTask | None:
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

    async def execute_publish(self, task_id: uuid.UUID) -> dict[str, Any]:
        """执行发布任务。

        Args:
            task_id: 任务 ID

        Returns:
            dict: 发布结果
        """
        task = await self.get_task_by_id(task_id)
        if not task:
            raise ValueError(f"发布任务不存在: {task_id}")

        # 获取对应平台的适配器
        adapter = PublishAdapterFactory.get_adapter(task.platform)

        # 解析媒体文件
        media_files = None
        if task.media_files:
            import json
            media_files = json.loads(task.media_files)

        # 执行发布
        try:
            result = await adapter.publish(
                title=task.title,
                content=task.content,
                media_files=media_files,
            )

            # 更新任务状态
            task.status = "published"
            task.publish_url = result.get("publish_url")
            task.publish_result = str(result)
            await self.db.commit()

            return result
        except Exception as e:
            task.status = "failed"
            task.error_message = str(e)
            task.retry_count += 1
            await self.db.commit()
            raise

    async def retry_task(self, task_id: uuid.UUID) -> dict[str, Any]:
        """重试失败的任务。"""
        task = await self.get_task_by_id(task_id)
        if not task:
            raise ValueError(f"发布任务不存在: {task_id}")

        if task.status != "failed":
            raise ValueError("只能重试失败的任务")

        task.status = "pending"
        task.error_message = None
        await self.db.commit()

        return await self.execute_publish(task_id)

    async def get_pending_tasks(
        self,
        tenant_id: str,
        limit: int = 50,
    ) -> list[PublishTask]:
        """获取待发布的任务。"""
        query = (
            select(PublishTask)
            .where(
                PublishTask.tenant_id == tenant_id,
                PublishTask.status == "pending",
            )
            .order_by(PublishTask.created_at)
            .limit(limit)
        )
        result = await self.db.execute(query)
        return result.scalars().all()
