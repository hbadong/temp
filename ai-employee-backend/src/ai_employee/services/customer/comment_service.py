"""评论抓取服务。"""

import json
import uuid
from typing import Any

from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession

from ai_employee.models.customer import Comment


class CommentService:
    """评论抓取与入库服务。"""

    def __init__(self, db: AsyncSession) -> None:
        self.db = db

    async def add_comment(self, tenant_id: str, **kwargs) -> Comment:
        """添加评论。"""
        comment = Comment(tenant_id=tenant_id, **kwargs)
        self.db.add(comment)
        await self.db.commit()
        await self.db.refresh(comment)
        return comment

    async def add_comments_batch(self, tenant_id: str, comments_data: list[dict]) -> list[Comment]:
        """批量添加评论。"""
        comments = []
        for data in comments_data:
            comment = Comment(tenant_id=tenant_id, **data)
            self.db.add(comment)
            comments.append(comment)
        await self.db.commit()
        for comment in comments:
            await self.db.refresh(comment)
        return comments

    async def get_comment_by_id(self, comment_id: uuid.UUID) -> Comment | None:
        """根据 ID 获取评论。"""
        result = await self.db.execute(
            select(Comment).where(Comment.id == str(comment_id))
        )
        return result.scalar_one_or_none()

    async def list_comments(
        self,
        tenant_id: str,
        platform: str | None = None,
        target_id: str | None = None,
        sentiment: str | None = None,
        intent: str | None = None,
        is_replied: bool | None = None,
        skip: int = 0,
        limit: int = 20,
    ) -> tuple[list[Comment], int]:
        """获取评论列表。"""
        query = select(Comment).where(Comment.tenant_id == tenant_id)

        if platform:
            query = query.where(Comment.platform == platform)
        if target_id:
            query = query.where(Comment.target_id == target_id)
        if sentiment:
            query = query.where(Comment.sentiment == sentiment)
        if intent:
            query = query.where(Comment.intent == intent)
        if is_replied is not None:
            query = query.where(Comment.is_replied == is_replied)

        count_result = await self.db.execute(query)
        total = len(count_result.scalars().all())

        result = await self.db.execute(
            query.order_by(Comment.created_at.desc()).offset(skip).limit(limit)
        )
        return result.scalars().all(), total

    async def update_comment(self, comment_id: uuid.UUID, **kwargs) -> Comment | None:
        """更新评论。"""
        comment = await self.get_comment_by_id(comment_id)
        if not comment:
            return None
        for key, value in kwargs.items():
            if hasattr(comment, key) and value is not None:
                setattr(comment, key, value)
        await self.db.commit()
        await self.db.refresh(comment)
        return comment

    async def fetch_comments_from_platform(
        self,
        tenant_id: str,
        platform: str,
        target_id: str,
        **kwargs: Any,
    ) -> list[Comment]:
        """从平台抓取评论（模拟实现）。

        Args:
            tenant_id: 租户 ID
            platform: 平台名称
            target_id: 目标内容 ID
            **kwargs: 其他参数

        Returns:
            list[Comment]: 抓取到的评论列表
        """
        # 模拟平台 API 返回的评论数据
        mock_comments = [
            {
                "source_id": f"{platform}_comment_1",
                "platform": platform,
                "target_type": kwargs.get("target_type", "video"),
                "target_id": target_id,
                "author_id": f"user_1",
                "author_name": "用户A",
                "content": "这个内容很好，想了解一下详细信息",
                "like_count": 5,
                "reply_count": 0,
            },
            {
                "source_id": f"{platform}_comment_2",
                "platform": platform,
                "target_type": kwargs.get("target_type", "video"),
                "target_id": target_id,
                "author_id": f"user_2",
                "author_name": "用户B",
                "content": "价格是多少？有优惠吗？",
                "like_count": 2,
                "reply_count": 0,
            },
            {
                "source_id": f"{platform}_comment_3",
                "platform": platform,
                "target_type": kwargs.get("target_type", "video"),
                "target_id": target_id,
                "author_id": f"user_3",
                "author_name": "用户C",
                "content": "内容质量不错，继续加油",
                "like_count": 10,
                "reply_count": 1,
            },
        ]

        # 去重：检查 source_id 是否已存在
        existing_ids = set()
        for mock in mock_comments:
            result = await self.db.execute(
                select(Comment).where(Comment.source_id == mock["source_id"])
            )
            if result.scalar_one_or_none():
                existing_ids.add(mock["source_id"])

        new_comments = [c for c in mock_comments if c["source_id"] not in existing_ids]

        if new_comments:
            return await self.add_comments_batch(tenant_id, new_comments)

        return []
