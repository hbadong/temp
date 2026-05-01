"""内容草稿服务。"""

import uuid

from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession

from ai_employee.models.content import ContentDraft


class ContentDraftService:
    """内容草稿服务。"""

    def __init__(self, db: AsyncSession) -> None:
        self.db = db

    async def create(self, tenant_id: str, **kwargs) -> ContentDraft:
        """创建内容草稿。"""
        draft = ContentDraft(tenant_id=tenant_id, **kwargs)
        self.db.add(draft)
        await self.db.commit()
        await self.db.refresh(draft)
        return draft

    async def create_multiple(self, tenant_id: str, drafts_data: list[dict]) -> list[ContentDraft]:
        """批量创建内容草稿（用于多版本生成）。"""
        drafts = []
        for data in drafts_data:
            draft = ContentDraft(tenant_id=tenant_id, **data)
            self.db.add(draft)
            drafts.append(draft)
        await self.db.commit()
        for draft in drafts:
            await self.db.refresh(draft)
        return drafts

    async def get_by_id(self, draft_id: uuid.UUID) -> ContentDraft | None:
        """根据 ID 获取草稿。"""
        result = await self.db.execute(
            select(ContentDraft).where(ContentDraft.id == str(draft_id))
        )
        return result.scalar_one_or_none()

    async def get_by_id_with_relations(self, draft_id: uuid.UUID) -> ContentDraft | None:
        """根据 ID 获取草稿（包含关联数据）。"""
        from sqlalchemy.orm import selectinload

        result = await self.db.execute(
            select(ContentDraft)
            .options(selectinload(ContentDraft.template), selectinload(ContentDraft.persona))
            .where(ContentDraft.id == str(draft_id))
        )
        return result.scalar_one_or_none()

    async def get_list(
        self,
        tenant_id: str,
        platform: str | None = None,
        status: str | None = None,
        skip: int = 0,
        limit: int = 20,
    ) -> tuple[list[ContentDraft], int]:
        """获取草稿列表。"""
        query = select(ContentDraft).where(ContentDraft.tenant_id == tenant_id)

        if platform:
            query = query.where(ContentDraft.platform == platform)
        if status:
            query = query.where(ContentDraft.status == status)

        count_query = select(ContentDraft).where(ContentDraft.tenant_id == tenant_id)
        if platform:
            count_query = count_query.where(ContentDraft.platform == platform)
        if status:
            count_query = count_query.where(ContentDraft.status == status)

        count_result = await self.db.execute(count_query)
        total = len(count_result.scalars().all())

        result = await self.db.execute(query.order_by(ContentDraft.created_at.desc()).offset(skip).limit(limit))
        return result.scalars().all(), total

    async def update(self, draft_id: uuid.UUID, **kwargs) -> ContentDraft | None:
        """更新草稿。"""
        draft = await self.get_by_id(draft_id)
        if not draft:
            return None
        for key, value in kwargs.items():
            if hasattr(draft, key) and value is not None:
                setattr(draft, key, value)
        await self.db.commit()
        await self.db.refresh(draft)
        return draft

    async def delete(self, draft_id: uuid.UUID) -> bool:
        """删除草稿。"""
        draft = await self.get_by_id(draft_id)
        if not draft:
            return False
        await self.db.delete(draft)
        await self.db.commit()
        return True
