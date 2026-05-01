"""爆款视频分析服务。

实现爆款视频的分析、存储和查询功能。
"""

import uuid

from sqlalchemy import func, select
from sqlalchemy.ext.asyncio import AsyncSession

from ai_employee.core.exceptions import ConflictException, NotFoundException
from ai_employee.models.trend import ViralVideoAnalysis
from ai_employee.schemas.trend import ViralVideoAnalysisCreate, ViralVideoAnalysisUpdate


class ViralVideoAnalysisService:
    """爆款视频分析服务。"""

    def __init__(self, db: AsyncSession) -> None:
        self.db = db

    async def create_analysis(self, data: ViralVideoAnalysisCreate) -> ViralVideoAnalysis:
        """创建爆款视频分析。"""
        stmt = select(ViralVideoAnalysis).where(
            ViralVideoAnalysis.video_id == data.video_id,
            ViralVideoAnalysis.is_deleted == False,  # noqa: E712
        )
        result = await self.db.execute(stmt)
        existing = result.scalar_one_or_none()
        if existing:
            raise ConflictException(f"视频分析已存在 (video_id: {data.video_id})")

        analysis = ViralVideoAnalysis(**data.model_dump())
        self.db.add(analysis)
        await self.db.flush()
        await self.db.refresh(analysis)
        return analysis

    async def get_by_id(self, analysis_id: uuid.UUID) -> ViralVideoAnalysis:
        """根据 ID 获取爆款视频分析。"""
        stmt = select(ViralVideoAnalysis).where(
            ViralVideoAnalysis.id == str(analysis_id),
            ViralVideoAnalysis.is_deleted == False,  # noqa: E712
        )
        result = await self.db.execute(stmt)
        analysis = result.scalar_one_or_none()
        if not analysis:
            raise NotFoundException("爆款视频分析")
        return analysis

    async def get_by_video_id(self, video_id: str) -> ViralVideoAnalysis | None:
        """根据 video_id 获取爆款视频分析。"""
        stmt = select(ViralVideoAnalysis).where(
            ViralVideoAnalysis.video_id == video_id,
            ViralVideoAnalysis.is_deleted == False,  # noqa: E712
        )
        result = await self.db.execute(stmt)
        return result.scalar_one_or_none()

    async def list_analyses(
        self,
        platform: str | None = None,
        min_viral_score: float | None = None,
        min_views: int | None = None,
        skip: int = 0,
        limit: int = 20,
    ) -> tuple[list[ViralVideoAnalysis], int]:
        """获取爆款视频分析列表。"""
        where_conditions = [ViralVideoAnalysis.is_deleted == False]  # noqa: E712

        if platform:
            where_conditions.append(ViralVideoAnalysis.platform == platform)
        if min_viral_score is not None:
            where_conditions.append(ViralVideoAnalysis.viral_score >= min_viral_score)
        if min_views is not None:
            where_conditions.append(ViralVideoAnalysis.view_count >= min_views)

        count_stmt = select(func.count(ViralVideoAnalysis.id)).where(*where_conditions)
        count_result = await self.db.execute(count_stmt)
        total = count_result.scalar() or 0

        stmt = (
            select(ViralVideoAnalysis)
            .where(*where_conditions)
            .order_by(ViralVideoAnalysis.viral_score.desc())
            .offset(skip)
            .limit(limit)
        )
        result = await self.db.execute(stmt)
        return list(result.scalars().all()), total

    async def update_analysis(
        self, analysis_id: uuid.UUID, data: ViralVideoAnalysisUpdate
    ) -> ViralVideoAnalysis:
        """更新爆款视频分析。"""
        analysis = await self.get_by_id(analysis_id)

        update_data = data.model_dump(exclude_unset=True)
        for key, value in update_data.items():
            setattr(analysis, key, value)

        await self.db.flush()
        await self.db.refresh(analysis)
        return analysis

    async def delete_analysis(self, analysis_id: uuid.UUID) -> None:
        """软删除爆款视频分析。"""
        analysis = await self.get_by_id(analysis_id)
        analysis.soft_delete()
        await self.db.flush()

    async def get_top_viral_videos(
        self,
        platform: str | None = None,
        limit: int = 20,
    ) -> list[ViralVideoAnalysis]:
        """获取最热门的视频分析。"""
        stmt = select(ViralVideoAnalysis).where(
            ViralVideoAnalysis.is_deleted == False,  # noqa: E712
        )
        if platform:
            stmt = stmt.where(ViralVideoAnalysis.platform == platform)

        stmt = stmt.order_by(
            ViralVideoAnalysis.viral_score.desc(),
            ViralVideoAnalysis.view_count.desc(),
        ).limit(limit)
        result = await self.db.execute(stmt)
        return list(result.scalars().all())
