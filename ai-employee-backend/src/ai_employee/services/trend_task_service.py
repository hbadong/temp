"""追热任务服务。

实现追热任务的创建、查询、状态跟踪等功能。
"""

import uuid

from sqlalchemy import func, select
from sqlalchemy.ext.asyncio import AsyncSession

from ai_employee.core.exceptions import NotFoundException
from ai_employee.models.trend import Trend, AccountProfile, TrendTask
from ai_employee.schemas.trend import TrendTaskCreate, TrendTaskUpdate
from ai_employee.services.relevance_scoring import RelevanceScoringEngine


class TrendTaskService:
    """追热任务服务。"""

    def __init__(self, db: AsyncSession) -> None:
        self.db = db
        self.scoring_engine = RelevanceScoringEngine()

    async def create_task(self, data: TrendTaskCreate) -> TrendTask:
        """创建追热任务（自动计算相关性评分）。"""
        # 获取热点数据
        trend_stmt = select(Trend).where(Trend.id == str(data.trend_id))
        trend_result = await self.db.execute(trend_stmt)
        trend = trend_result.scalar_one_or_none()
        if not trend:
            raise NotFoundException("热点数据")

        # 获取账号画像
        profile_stmt = select(AccountProfile).where(AccountProfile.id == str(data.account_profile_id))
        profile_result = await self.db.execute(profile_stmt)
        profile = profile_result.scalar_one_or_none()
        if not profile:
            raise NotFoundException("账号画像")

        # 计算相关性评分
        relevance_score = self.scoring_engine.calculate_relevance(
            trend_title=trend.title,
            trend_content=trend.content,
            trend_tags=trend.tags,
            trend_category=trend.category,
            account_tags=profile.tags,
            account_target_audience=profile.target_audience,
            account_content_categories=profile.content_categories,
        )

        task = TrendTask(
            trend_id=str(data.trend_id),
            account_profile_id=str(data.account_profile_id),
            relevance_score=relevance_score,
            priority=data.priority,
            status="pending",
        )
        self.db.add(task)
        await self.db.flush()
        await self.db.refresh(task)
        return task

    async def get_by_id(self, task_id: uuid.UUID) -> TrendTask:
        """根据 ID 获取追热任务。"""
        stmt = select(TrendTask).where(
            TrendTask.id == str(task_id),
            TrendTask.is_deleted == False,  # noqa: E712
        )
        result = await self.db.execute(stmt)
        task = result.scalar_one_or_none()
        if not task:
            raise NotFoundException("追热任务")
        return task

    async def list_tasks(
        self,
        status: str | None = None,
        trend_id: uuid.UUID | None = None,
        account_profile_id: uuid.UUID | None = None,
        min_score: float | None = None,
        skip: int = 0,
        limit: int = 20,
    ) -> tuple[list[TrendTask], int]:
        """获取追热任务列表。"""
        where_conditions = [TrendTask.is_deleted == False]  # noqa: E712

        if status:
            where_conditions.append(TrendTask.status == status)
        if trend_id:
            where_conditions.append(TrendTask.trend_id == str(trend_id))
        if account_profile_id:
            where_conditions.append(TrendTask.account_profile_id == str(account_profile_id))
        if min_score is not None:
            where_conditions.append(TrendTask.relevance_score >= min_score)

        count_stmt = select(func.count(TrendTask.id)).where(*where_conditions)
        count_result = await self.db.execute(count_stmt)
        total = count_result.scalar() or 0

        stmt = (
            select(TrendTask)
            .where(*where_conditions)
            .order_by(TrendTask.relevance_score.desc(), TrendTask.created_at.desc())
            .offset(skip)
            .limit(limit)
        )
        result = await self.db.execute(stmt)
        return list(result.scalars().all()), total

    async def update_task(self, task_id: uuid.UUID, data: TrendTaskUpdate) -> TrendTask:
        """更新追热任务。"""
        task = await self.get_by_id(task_id)

        update_data = data.model_dump(exclude_unset=True)
        for key, value in update_data.items():
            setattr(task, key, value)

        await self.db.flush()
        await self.db.refresh(task)
        return task

    async def delete_task(self, task_id: uuid.UUID) -> None:
        """软删除追热任务。"""
        task = await self.get_by_id(task_id)
        task.soft_delete()
        await self.db.flush()

    async def get_pending_tasks(self, limit: int = 50) -> list[TrendTask]:
        """获取待处理的追热任务。"""
        stmt = (
            select(TrendTask)
            .where(
                TrendTask.status == "pending",
                TrendTask.is_deleted == False,  # noqa: E712
            )
            .order_by(TrendTask.priority.desc(), TrendTask.relevance_score.desc())
            .limit(limit)
        )
        result = await self.db.execute(stmt)
        return list(result.scalars().all())

    async def get_tasks_by_account(
        self,
        account_profile_id: uuid.UUID,
        skip: int = 0,
        limit: int = 20,
    ) -> tuple[list[TrendTask], int]:
        """获取指定账号的追热任务。"""
        return await self.list_tasks(
            account_profile_id=account_profile_id,
            skip=skip,
            limit=limit,
        )
