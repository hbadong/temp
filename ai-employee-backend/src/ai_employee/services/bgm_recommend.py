"""BGM 推荐服务。"""

import uuid
from typing import Any

from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession

from ai_employee.models.publish import BGM


class BGMRecommendService:
    """BGM 推荐服务。

    根据内容类型、情绪、风格等推荐合适的背景音乐。
    """

    def __init__(self, db: AsyncSession) -> None:
        self.db = db

    async def add_bgm(self, **kwargs) -> BGM:
        """添加 BGM。"""
        bgm = BGM(**kwargs)
        self.db.add(bgm)
        await self.db.commit()
        await self.db.refresh(bgm)
        return bgm

    async def get_bgm_by_id(self, bgm_id: uuid.UUID) -> BGM | None:
        """根据 ID 获取 BGM。"""
        result = await self.db.execute(
            select(BGM).where(BGM.id == str(bgm_id))
        )
        return result.scalar_one_or_none()

    async def list_bgms(
        self,
        genre: str | None = None,
        mood: str | None = None,
        is_popular: bool | None = None,
        skip: int = 0,
        limit: int = 20,
    ) -> tuple[list[BGM], int]:
        """获取 BGM 列表。"""
        query = select(BGM)

        if genre:
            query = query.where(BGM.genre == genre)
        if mood:
            query = query.where(BGM.mood == mood)
        if is_popular is not None:
            query = query.where(BGM.is_popular == is_popular)

        count_result = await self.db.execute(query)
        total = len(count_result.scalars().all())

        result = await self.db.execute(
            query.order_by(BGM.usage_count.desc(), BGM.created_at.desc()).offset(skip).limit(limit)
        )
        return result.scalars().all(), total

    async def update_bgm(self, bgm_id: uuid.UUID, **kwargs) -> BGM | None:
        """更新 BGM。"""
        bgm = await self.get_bgm_by_id(bgm_id)
        if not bgm:
            return None
        for key, value in kwargs.items():
            if hasattr(bgm, key) and value is not None:
                setattr(bgm, key, value)
        await self.db.commit()
        await self.db.refresh(bgm)
        return bgm

    async def delete_bgm(self, bgm_id: uuid.UUID) -> bool:
        """删除 BGM。"""
        bgm = await self.get_bgm_by_id(bgm_id)
        if not bgm:
            return False
        await self.db.delete(bgm)
        await self.db.commit()
        return True

    async def recommend(
        self,
        content_type: str,
        mood: str | None = None,
        genre: str | None = None,
        duration_min: float | None = None,
        duration_max: float | None = None,
        limit: int = 10,
    ) -> list[BGM]:
        """推荐 BGM。

        Args:
            content_type: 内容类型
            mood: 情绪
            genre: 风格
            duration_min: 最小时长
            duration_max: 最大时长
            limit: 推荐数量

        Returns:
            list[BGM]: 推荐的 BGM 列表
        """
        query = select(BGM)

        # 根据内容类型映射到推荐风格
        content_type_genres = self._get_content_type_genres(content_type)

        filters = []
        if genre:
            filters.append(BGM.genre == genre)
        elif content_type_genres:
            filters.append(BGM.genre.in_(content_type_genres))

        if mood:
            filters.append(BGM.mood == mood)

        if duration_min is not None:
            filters.append(BGM.duration >= duration_min)
        if duration_max is not None:
            filters.append(BGM.duration <= duration_max)

        for f in filters:
            query = query.where(f)

        # 优先返回热门 BGM
        result = await self.db.execute(
            query.order_by(
                BGM.is_popular.desc(),
                BGM.usage_count.desc(),
            ).limit(limit)
        )
        return result.scalars().all()

    def _get_content_type_genres(self, content_type: str) -> list[str]:
        """根据内容类型获取推荐的 BGM 风格。"""
        genre_mapping = {
            "vlog": ["轻快", "流行", "电子"],
            "tutorial": ["轻音乐", "钢琴", "环境"],
            "review": ["摇滚", "电子", "流行"],
            "story": ["古典", "钢琴", "弦乐"],
            "comedy": ["轻快", "流行", "爵士"],
            "travel": ["世界音乐", "轻快", "民谣"],
            "food": ["爵士", "轻快", "流行"],
            "tech": ["电子", "环境", "合成器"],
        }
        return genre_mapping.get(content_type, ["流行", "轻快"])

    async def increment_usage(self, bgm_id: uuid.UUID) -> None:
        """增加 BGM 使用次数。"""
        bgm = await self.get_bgm_by_id(bgm_id)
        if bgm:
            bgm.usage_count += 1
            await self.db.commit()
