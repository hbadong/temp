"""内容模板服务。"""

import uuid

from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession

from ai_employee.models.content import ContentTemplate


class ContentTemplateService:
    """内容模板服务。"""

    def __init__(self, db: AsyncSession) -> None:
        self.db = db

    async def create(self, tenant_id: str, **kwargs) -> ContentTemplate:
        """创建内容模板。"""
        template = ContentTemplate(tenant_id=tenant_id, **kwargs)
        self.db.add(template)
        await self.db.commit()
        await self.db.refresh(template)
        return template

    async def get_by_id(self, template_id: uuid.UUID) -> ContentTemplate | None:
        """根据 ID 获取模板。"""
        result = await self.db.execute(
            select(ContentTemplate).where(ContentTemplate.id == str(template_id))
        )
        return result.scalar_one_or_none()

    async def get_list(
        self,
        tenant_id: str,
        platform: str | None = None,
        content_type: str | None = None,
        skip: int = 0,
        limit: int = 20,
    ) -> tuple[list[ContentTemplate], int]:
        """获取模板列表。"""
        query = select(ContentTemplate).where(ContentTemplate.tenant_id == tenant_id)

        if platform:
            query = query.where(ContentTemplate.platform == platform)
        if content_type:
            query = query.where(ContentTemplate.content_type == content_type)

        count_query = select(ContentTemplate).where(ContentTemplate.tenant_id == tenant_id)
        if platform:
            count_query = count_query.where(ContentTemplate.platform == platform)
        if content_type:
            count_query = count_query.where(ContentTemplate.content_type == content_type)

        count_result = await self.db.execute(count_query)
        total = len(count_result.scalars().all())

        result = await self.db.execute(query.order_by(ContentTemplate.created_at.desc()).offset(skip).limit(limit))
        return result.scalars().all(), total

    async def update(self, template_id: uuid.UUID, **kwargs) -> ContentTemplate | None:
        """更新模板。"""
        template = await self.get_by_id(template_id)
        if not template:
            return None
        for key, value in kwargs.items():
            if hasattr(template, key) and value is not None:
                setattr(template, key, value)
        await self.db.commit()
        await self.db.refresh(template)
        return template

    async def delete(self, template_id: uuid.UUID) -> bool:
        """删除模板。"""
        template = await self.get_by_id(template_id)
        if not template:
            return False
        await self.db.delete(template)
        await self.db.commit()
        return True

    async def increment_usage(self, template_id: uuid.UUID) -> None:
        """增加使用次数。"""
        template = await self.get_by_id(template_id)
        if template:
            template.usage_count += 1
            await self.db.commit()
